/**
 * Scene recorder for the demo videos (project_asset/docs/video-production.md §5).
 *
 *   node record-scenes.mjs --video v1 --probe           # geometry + readiness report + probe.png, records nothing
 *   node record-scenes.mjs --video v1 --only scene-01   # one or more scenes (comma-separated)
 *   node record-scenes.mjs --video v1                   # every scene + stills + delivery-note.md
 *   options: --fps 30  --crf 16  --keep-frames  --force (record even when the readiness audit fails)
 *            --rehearsal (run a scene's preflight but carry on when it fails; write to recordings/rehearsal/)
 *
 * Per video, scenes.json may also set (all optional; V1 uses none of them):
 *   "wpPath"     the WordPress root WP-CLI targets (default: the ch site) — e.g. V8's throw-away ch-v8 store
 *   "preflight"  a command, relative to the ch root, run before any scene marked "preflight": true
 *                (V8: ["scripts/v8-site.sh", "check"]); a failure skips the scene unless --force / --rehearsal
 *   "allowNotices"  notice texts the readiness audit accepts (substring match)
 * and per scene: "context": "visitor" (a separate logged-out browser) · "preflight": true.
 * Steps beyond V1's: type, press, goto, back, wait-for, scroll-to, timer-start / timer-stop (an on-screen mm:ss
 * clock that survives page loads, for one-take scenes), note (a text fact for the delivery note).
 *
 * Plays ../../content/videos/<video>/scenes.json against the local ch store: logs in through the
 * local auto-login helper (no password), switches the active build (pro / free) with WP-CLI when a
 * scene needs it, waits until the page is really ready (no spinner, notice, error or empty card),
 * then records with Chrome's own screencast at 1920×1080 CSS px × 2 (3840×2160 frames), a drawn
 * highlighted cursor, and encodes each scene with ffmpeg (H.264, constant frame rate).
 *
 * Output: ../../content/videos/<video>/recordings/  (video files are gitignored)
 * Needs: Google Chrome (playwright-core channel "chrome"), ffmpeg, MAMP running.
 */
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const here  = path.dirname(new URL(import.meta.url).pathname);
const ROOT  = path.resolve(here, '../../..');                       // the ch site root
const PHP   = '/Applications/MAMP/bin/php/php8.2.0/bin/php';
const WP    = '/Applications/MAMP/Library/bin/wp';
const SOCK  = '/Applications/MAMP/tmp/mysql/mysql.sock';

const args  = process.argv.slice(2);
const opt   = (n, d = null) => { const i = args.indexOf(n); return i >= 0 ? args[i + 1] : d; };
const has   = (n) => args.includes(n);
const VIDEO = opt('--video', 'v1');
const ONLY  = opt('--only') ? opt('--only').split(',') : null;
const PROBE = has('--probe');
const FORCE = has('--force');
const KEEP  = has('--keep-frames');
const REHEARSAL = has('--rehearsal');
const FPS   = Number(opt('--fps', 30));
const CRF   = Number(opt('--crf', 16));

const vdir  = path.join(ROOT, 'project_asset/content/videos', VIDEO);
const plan  = JSON.parse(fs.readFileSync(path.join(vdir, 'scenes.json'), 'utf8'));
const OUT   = path.join(vdir, 'recordings', REHEARSAL ? 'rehearsal' : '');
fs.mkdirSync(OUT, { recursive: true });

const BASE   = plan.base || 'http://localhost/ch';
const WPPATH = plan.wpPath || ROOT;
const VP     = Object.assign({ width: 1920, height: 1080, scale: 2 }, plan.viewport || {});
const BUILDS = { pro: 'puredevs-customer-history-for-woocommerce-pro', free: 'puredevs-customer-history-for-woocommerce' };

const log   = (...a) => console.log(...a);
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const now   = () => performance.now() / 1000;

// ── site side (WP-CLI through MAMP's PHP, deprecation noise silenced) ────────────────────────
function wp(...a) {
  // WP-CLI re-enables display_errors, so PHP 8.2 deprecation lines reach stdout: drop them.
  const out = execFileSync(PHP, ['-d', 'error_reporting=0', '-d', 'display_errors=0', `-d`, `mysqli.default_socket=${SOCK}`, WP, ...a, `--path=${WPPATH}`], { encoding: 'utf8' });
  return out.split('\n').filter(l => !/^(PHP +)?(Deprecated|Warning|Notice):/.test(l)).join('\n').trim();
}
function activeBuild() {
  const active = wp('plugin', 'list', '--status=active', '--field=name').split('\n').map(s => s.trim());
  return active.includes(BUILDS.pro) ? 'pro' : active.includes(BUILDS.free) ? 'free' : null;
}
function switchBuild(to) {
  const from = activeBuild();
  if (from === to) return false;
  log(`  build: ${from || 'none'} → ${to}`);
  if (from) wp('plugin', 'deactivate', BUILDS[from]);
  wp('plugin', 'activate', BUILDS[to]);
  return true;
}
function siteInfo() {
  return wp('eval', 'echo wp_json_encode(array("wp"=>get_bloginfo("version"),"wc"=>defined("WC_VERSION")?WC_VERSION:null,"plugin"=>defined("PDWCHS_PRO_VERSION")?PDWCHS_PRO_VERSION:(defined("PDWCHS_CUSTOMER_HISTORY_VERSION")?PDWCHS_CUSTOMER_HISTORY_VERSION:null)));');
}

// ── in-page helpers: cursor overlay, smooth scroll, selector resolver ─────────────────────────
const INIT = `
(() => {
  const install = () => {
    if (document.getElementById('__rec_cursor') || !document.body) return;
    const c = document.createElement('div');
    c.id = '__rec_cursor';
    c.style.cssText = 'position:fixed;left:0;top:0;width:0;height:0;z-index:2147483647;pointer-events:none;transform:translate(-300px,-300px);';
    c.innerHTML = '<div id="__rec_ring" style="position:absolute;left:-24px;top:-24px;width:48px;height:48px;border-radius:50%;background:rgba(255,193,7,.30);box-shadow:0 0 0 2px rgba(255,193,7,.55);transition:transform .16s ease"></div>'
      + '<svg style="position:absolute;left:-1px;top:-1px" width="22" height="32" viewBox="0 0 22 32"><path d="M2 2 L2 24 L8 18.5 L12 28 L16 26.4 L12 17 L20 17 Z" fill="#111" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/></svg>';
    document.body.appendChild(c);
    const s = window.__rec = { x: -300, y: -300 };
    s.set = (x, y) => { s.x = x; s.y = y; c.style.transform = 'translate(' + x + 'px,' + y + 'px)'; };
    s.pulse = () => { const r = document.getElementById('__rec_ring'); r.style.transform = 'scale(.62)'; setTimeout(() => { r.style.transform = 'scale(1)'; }, 170); };
    s.scrollTo = (y, ms) => new Promise(res => {
      const y0 = window.scrollY, d = y - y0, t0 = performance.now();
      const ease = t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
      const step = () => { const p = Math.min(1, (performance.now() - t0) / ms); window.scrollTo(0, y0 + d * ease(p)); if (p < 1) requestAnimationFrame(step); else res(); };
      requestAnimationFrame(step);
    });
    document.addEventListener('mousemove', e => s.set(e.clientX, e.clientY), true);
    // One-take clock: started by the timer-start step, kept in sessionStorage so it survives page loads.
    s.timer = () => {
      let t0 = 0; try { t0 = Number(sessionStorage.getItem('__rec_timer_t0') || 0); } catch (e) {}
      let t = document.getElementById('__rec_timer');
      clearInterval(s.timerIv);
      if (!t0) { if (t) t.remove(); return; }
      if (!t) {
        t = document.createElement('div');
        t.id = '__rec_timer';
        t.style.cssText = 'position:fixed;right:28px;bottom:84px;z-index:2147483646;pointer-events:none;display:flex;align-items:center;gap:10px;padding:10px 18px 10px 14px;border-radius:12px;background:rgba(17,17,17,.84);color:#fff;font:600 26px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-variant-numeric:tabular-nums;box-shadow:0 4px 18px rgba(0,0,0,.25)';
        t.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5M9.5 2.5h5"/></svg><span id="__rec_timer_v">0:00</span>';
        document.body.appendChild(t);
      }
      const v = document.getElementById('__rec_timer_v');
      const tick = () => { const sec = Math.max(0, Math.floor((Date.now() - t0) / 1000)); v.textContent = Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0'); };
      tick(); s.timerIv = setInterval(tick, 200);
    };
    s.timer();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install); else install();
})();`;

const RESOLVER = `window.__resolve = (sel) => {
  const norm = s => (s || '').replace(/\\s+/g, ' ').trim();
  const visible = el => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden'; };
  let m;
  if ((m = sel.match(/^heading:(.+)$/))) {
    const t = m[1].trim();
    const hs = [...document.querySelectorAll('h1,h2,h3,h4,legend,label,th')].filter(visible);
    return hs.find(e => norm(e.textContent).startsWith(t)) || hs.find(e => norm(e.textContent).includes(t)) || null;
  }
  if ((m = sel.match(/^text:(.+)$/))) {
    const t = m[1].trim();
    const all = [...document.querySelectorAll('body *')].filter(e => visible(e) && norm(e.textContent).includes(t) && !['SCRIPT','STYLE'].includes(e.tagName));
    all.sort((a, b) => { const ra = a.getBoundingClientRect(), rb = b.getBoundingClientRect(); return ra.width * ra.height - rb.width * rb.height; });
    return all[0] || null;
  }
  return document.querySelector(sel);
};`;

// ── readiness audit: what must be true before a scene is recorded ────────────────────────────
const AUDIT = `(input) => {
  const checks = Array.isArray(input) ? input : (input.checks || []);
  const allow  = Array.isArray(input) ? [] : (input.allow || []);
  const vis = el => { if (!el) return false; const r = el.getBoundingClientRect(); const cs = getComputedStyle(el); return r.width > 0 && r.height > 0 && cs.display !== 'none' && cs.visibility !== 'hidden'; };
  const norm = s => (s || '').replace(/\\s+/g, ' ').trim();
  const problems = [];
  const spinners = [...document.querySelectorAll('.spinner.is-active, .pdwchs-revenue-loading')].filter(vis);
  if (checks.includes('no-spinner') && spinners.length) problems.push(spinners.length + ' spinner(s) still visible');
  if (checks.includes('no-notice')) {
    const notices = [...document.querySelectorAll('#wpbody-content .notice, #wpbody-content .updated, #wpbody-content .error, .update-nag, .woocommerce-message, .fs-notice, .welcome-panel')].filter(vis).map(n => norm(n.innerText)).filter(t => !allow.some(a => t.includes(a))).map(t => t.slice(0, 90));
    if (notices.length) problems.push('notice(s) on screen: ' + JSON.stringify(notices));
    const bubbles = [...document.querySelectorAll('#wp-admin-bar-updates, #adminmenu .update-plugins, #adminmenu .awaiting-mod')].filter(vis).map(b => norm(b.textContent));
    if (bubbles.length) problems.push('update / count bubble(s): ' + JSON.stringify(bubbles));
  }
  for (const c of checks) {                                   // generic page-text checks: "text:…" must show, "no-text:…" must not
    const body = norm(document.body.innerText);
    if (c.startsWith('text:') && !body.includes(c.slice(5))) problems.push('text not found: ' + c.slice(5));
    if (c.startsWith('no-text:') && body.includes(c.slice(8))) problems.push('page shows "' + c.slice(8) + '"');
  }
  if (checks.includes('no-error')) {
    const txt = norm(document.body.innerText);
    for (const bad of ['Failed to load', 'Could not load', 'Error establishing', 'No data', 'No revenue data', 'Data Mismatch']) if (txt.includes(bad)) problems.push('page text contains "' + bad + '"');
  }
  if (checks.includes('split-loaded')) {
    const vals = [...document.querySelectorAll('.pdwchs-revenue-split-value')].map(e => norm(e.textContent));
    if (vals.length !== 3 || vals.some(v => !v || v === '—' || v === '-')) problems.push('Revenue by Customer Type cards not loaded: ' + JSON.stringify(vals));
    const kpi = [...document.querySelectorAll('.pdwchs-revenue-card-value')].map(e => norm(e.textContent));
    if (kpi.some(v => !v || v === '—')) problems.push('KPI cards not loaded: ' + JSON.stringify(kpi));
  }
  if (checks.includes('badge-verified')) {
    const b = norm(document.querySelector('.pdwchs-integrity-label')?.textContent);
    if (!/Verified/.test(b)) problems.push('integrity badge reads "' + b + '"');
  }
  if (checks.includes('repeat-rate-loaded')) {
    const el = window.__resolve ? window.__resolve('text:Repeat Purchase Rate') : null;
    const card = el ? (el.closest('.pd-woo-panel, .pdwchs-card, .card') || el.parentElement || el) : null;
    const t = norm(card?.textContent);
    if (!el) problems.push('Repeat Purchase Rate card not found');
    else if (/(^|\\s)0(\\.0)?\\s*%/.test(t)) problems.push('Repeat Purchase Rate reads 0 %');
  }
  return problems;
}`;

// ── browser ───────────────────────────────────────────────────────────────────────────────────
const browser = await chromium.launch({ channel: 'chrome', headless: true, args: ['--hide-scrollbars', '--force-device-scale-factor=' + VP.scale] });
const CTX_OPTS = { viewport: { width: VP.width, height: VP.height }, deviceScaleFactor: VP.scale, colorScheme: 'light', reducedMotion: 'no-preference' };
let ctx = await browser.newContext(CTX_OPTS);
await ctx.addInitScript(INIT);
let page = await ctx.newPage();
page.setDefaultTimeout(30000);
const cur = { x: -300, y: -300 };
// "admin" is the logged-in browser; "visitor" (and any other name) is a separate, logged-out one.
const contexts = { admin: { ctx, page } };
async function useContext(name = 'admin') {
  if (!contexts[name]) {
    const c = await browser.newContext(CTX_OPTS);
    await c.addInitScript(INIT);
    const p = await c.newPage();
    p.setDefaultTimeout(30000);
    contexts[name] = { ctx: c, page: p };
  }
  if (contexts[name].page !== page) { ({ ctx, page } = contexts[name]); cur.x = -300; cur.y = -300; }
}

async function login(user) {
  await page.goto(`${BASE}/?local_login=1&user_email=${encodeURIComponent(user)}`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('load').catch(() => {});
  const name = await page.locator('#wp-admin-bar-my-account .display-name').first().textContent().catch(() => null);
  if (!name) throw new Error(`auto-login failed for ${user} (is WP_ENVIRONMENT_TYPE=local and the mu-plugin present?)`);
  return name.trim();
}
async function prep() {
  await page.evaluate(RESOLVER);
  await page.evaluate(([x, y]) => { if (window.__rec) window.__rec.set(x, y); }, [cur.x, cur.y]);
}
const sub = (s) => typeof s === 'string' ? s.replace(/\{dashboard\}/g, plan.dashboard || '/wp-admin/admin.php?page=pd-woo-dashboard').replace(/\{biggest-card\}/g, state.biggestSelector || '.pdwchs-revenue-split-returning') : s;

async function rect(sel) {
  await page.evaluate(RESOLVER);
  const r = await page.evaluate((s) => {
    const el = window.__resolve(s); if (!el) return null;
    const b = el.getBoundingClientRect();
    const range = document.createRange(); range.selectNodeContents(el); const t = range.getBoundingClientRect();   // the text itself, for block elements wider than their text
    return { x: b.left, y: b.top, w: b.width, h: b.height, docY: b.top + window.scrollY, tx: t.left, tw: t.width };
  }, sub(sel));
  if (!r) throw new Error(`selector not found: ${sel}`);
  return r;
}
function pointFor(r, at = 'center', dx = 0, dy = 0) {
  const gap = 18;
  const p = at === 'below' ? { x: r.x + r.w / 2, y: r.y + r.h + gap }
        : at === 'below-right' ? { x: r.x + r.w + 28, y: r.y + r.h + gap }   // the row under a label, right of what sits there
        : at === 'text-right' ? { x: (r.tx ?? r.x) + (r.tw ?? r.w) + 28, y: r.y + r.h / 2 }   // beside the text of a full-width block
        : at === 'right'  ? { x: r.x + r.w + gap, y: r.y + r.h / 2 }
        : at === 'left'   ? { x: r.x - gap, y: r.y + r.h / 2 }
        : at === 'above'  ? { x: r.x + r.w / 2, y: r.y - gap }
        :                   { x: r.x + r.w / 2, y: r.y + r.h / 2 };
  return { x: Math.round(p.x + dx), y: Math.round(p.y + dy) };
}
async function moveTo(x, y, s = 1.2) {
  if (cur.x < 0) { await page.mouse.move(x, y); cur.x = x; cur.y = y; await page.evaluate(([x, y]) => window.__rec && window.__rec.set(x, y), [x, y]); return; }
  const x0 = cur.x, y0 = cur.y, t0 = now();
  const ease = t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  let p = 0;
  while (p < 1) {
    p = Math.min(1, (now() - t0) / s);
    const e = ease(p);
    await page.mouse.move(x0 + (x - x0) * e, y0 + (y - y0) * e);
    await sleep(14);
  }
  cur.x = x; cur.y = y;
}
async function homeY() {
  const a = await rect(plan.home.anchor);
  return Math.max(0, Math.round(a.docY + a.h + (plan.home.bottomGap ?? 40) - VP.height));
}
async function homeReport() {
  const y = await homeY();
  const cur_y = await page.evaluate(() => window.scrollY);
  const vis = {};
  for (const s of (plan.home.mustBeVisible || [])) { try { const r = await rect(s); vis[s] = r.docY - y >= 0 && r.docY + r.h - y <= VP.height; } catch { vis[s] = 'not found'; } }
  for (const s of (plan.home.mustBeHidden || [])) { try { const r = await rect(s); vis[s] = (r.docY - y >= VP.height || r.docY + r.h - y <= 0) ? 'hidden ok' : 'VISIBLE (should be off screen)'; } catch { vis[s] = 'not found'; } }
  return { homeY: y, scrollY: cur_y, ...vis };
}
async function parkPoint(where) {
  if (where === 'below-cards') { const g = await rect('.pdwchs-revenue-split-grid'); return { x: Math.round(g.x + g.w / 2), y: Math.round(Math.min(VP.height - 30, g.y + g.h + 34)) }; }
  if (where === 'menu-gap')    { return { x: 90, y: VP.height - 60 }; }                                  // bottom of the admin menu column
  // "empty space away from the boxes": the blank header row between the widget title and the date box —
  // inside the frame at the home position and outside beat 1's crop (the split section only).
  const h = await rect('heading:Revenue Analytics').catch(() => null);
  const d = await rect('#pdwchs-revenue-date-picker').catch(() => null);
  if (h && d && d.x > h.x + h.w + 120) return { x: Math.round((h.x + h.w + d.x) / 2), y: Math.round(h.y + h.h / 2) };
  const g = await rect('.pdwchs-revenue-split-grid').catch(() => null);
  if (g) return { x: Math.round(g.x + g.w - 36), y: Math.round(Math.min(VP.height - 40, g.y + g.h + 30)) };
  return { x: VP.width - 60, y: Math.round(VP.height * 0.55) };
}
async function waitReady(checks, timeoutMs = 90000) {
  const t0 = Date.now();
  let problems = [];
  await page.waitForLoadState('load').catch(() => {});
  while (Date.now() - t0 < timeoutMs) {
    try {
      await page.evaluate(RESOLVER);
      problems = await page.evaluate(new Function('return ' + AUDIT)(), { checks, allow: plan.allowNotices || [] });
    } catch (e) {
      if (/Execution context was destroyed|navigation/i.test(e.message)) { await sleep(300); continue; }   // a document is still loading
      throw e;
    }
    const transient = problems.filter(p => /spinner|not loaded|Verifying|not found/.test(p));
    if (!problems.length || (problems.length === transient.length && false)) break;
    if (!transient.length) break;                         // only hard problems remain: no point waiting
    await sleep(300);
  }
  await sleep(600);
  await page.evaluate(RESOLVER);
  problems = await page.evaluate(new Function('return ' + AUDIT)(), { checks, allow: plan.allowNotices || [] });
  return { ok: !problems.length, problems, waitedMs: Date.now() - t0 };
}
async function splitValues() {
  return page.evaluate(() => {
    const num = s => Number((s || '').replace(/[^0-9.]/g, '')) || 0;
    const card = k => document.querySelector(`.pdwchs-revenue-split-${k}`);
    const out = {};
    for (const k of ['new', 'returning', 'guest']) { const c = card(k); out[k] = c ? { value: num(c.querySelector('.pdwchs-revenue-split-value')?.textContent), orders: num(c.querySelector('.pdwchs-revenue-split-orders')?.textContent) } : null; }
    return out;
  });
}
const state = { biggestSelector: null, facts: null, notes: [] };
// While a clip records, every step is logged with its start/end (seconds into the clip) and, for steps
// aimed at an element, that element's rect in CSS px at the time: <scene>.steps.json, for the edit.
const stepLog = { t0: null, events: [] };
// The A/B and C/D facts come from the three cards, so only a populated dashboard can set them.
async function ensureFacts() {
  if (state.facts) return state.facts;
  const v = await splitValues().catch(() => null);
  if (!v || !v.new || !v.returning || !v.guest) return null;
  return decideFacts(v);
}
function decideFacts(v) {
  const rev = { new: v.new?.value ?? 0, returning: v.returning?.value ?? 0, guest: v.guest?.value ?? 0 };
  const ranked = Object.keys(rev).sort((a, b) => rev[b] - rev[a]);
  const label = { new: 'New Customers', returning: 'Returning Customers', guest: 'Guest Orders' };
  const facts = {
    biggest: ranked[0], biggestLabel: label[ranked[0]],
    option: ranked[0] === 'returning' ? 'A' : ranked[0] === 'new' ? 'B' : 'STOP — Guest Orders is the largest card: reseed',
    guest: ranked[2] === 'guest' ? 'the smallest of the three' : `bigger than ${label[ranked[2]]}`,
    guestOption: ranked[2] === 'guest' ? 'C' : 'D',
    margin: rev[ranked[1]] > 0 ? (rev[ranked[0]] / rev[ranked[1]]).toFixed(2) : '∞',
  };
  state.biggestSelector = `.pdwchs-revenue-split-${ranked[0]}`;
  state.facts = facts;
  return facts;
}

// ── screencast recording ──────────────────────────────────────────────────────────────────────
async function record(sceneId, run) {
  const fdir = path.join(OUT, `${sceneId}.frames`);
  fs.rmSync(fdir, { recursive: true, force: true });
  fs.mkdirSync(fdir, { recursive: true });
  const cdp = await ctx.newCDPSession(page);
  const frames = [];
  let n = 0;
  let live = true;
  cdp.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
    if (live) {
      const file = path.join(fdir, `f${String(n++).padStart(6, '0')}.jpg`);
      try { fs.writeFileSync(file, Buffer.from(data, 'base64')); frames.push({ file, t: metadata.timestamp, at: now() }); } catch (e) { /* folder already removed */ }
    }
    cdp.send('Page.screencastFrameAck', { sessionId }).catch(() => {});
  });
  await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 93, maxWidth: VP.width * VP.scale, maxHeight: VP.height * VP.scale, everyNthFrame: 1 });
  const t0 = now();
  let tEnd = t0;
  stepLog.t0 = t0; stepLog.events = [];
  try {
    await run();
  } finally {                                                 // a failed step must not leave the screencast running
    tEnd = now();
    live = false;
    await cdp.send('Page.stopScreencast').catch(() => {});
    await sleep(200);
    await cdp.detach().catch(() => {});
    fs.writeFileSync(path.join(OUT, `${sceneId}.steps.json`), JSON.stringify({ scene: sceneId, seconds: +(tEnd - t0).toFixed(3), viewport: VP, events: stepLog.events }, null, 2) + '\n');
    stepLog.t0 = null;
  }
  if (!frames.length) throw new Error(`${sceneId}: the screencast produced no frames`);

  // concat list with per-frame durations (from Chrome's own timestamps; the last frame runs to the end)
  const lines = ['ffconcat version 1.0'];
  for (let i = 0; i < frames.length; i++) {
    const d = i + 1 < frames.length ? frames[i + 1].t - frames[i].t : Math.max(0.04, tEnd - frames[i].at);
    lines.push(`file '${path.basename(frames[i].file)}'`, `duration ${Math.max(0.001, d).toFixed(4)}`);
  }
  // concat quirk: the last entry's duration only counts when another entry follows it, so repeat the
  // last frame with a one-frame duration and cut the output at the scene's real length.
  lines.push(`file '${path.basename(frames[frames.length - 1].file)}'`, 'duration 0.0400');
  fs.writeFileSync(path.join(fdir, 'list.txt'), lines.join('\n') + '\n');

  const mp4 = path.join(OUT, `${sceneId}.mp4`);
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', path.join(fdir, 'list.txt'),
    '-vf', `fps=${FPS},scale=trunc(iw/2)*2:trunc(ih/2)*2,format=yuv420p`, '-c:v', 'libx264', '-preset', 'fast', '-crf', String(CRF), '-movflags', '+faststart', '-r', String(FPS), '-t', (tEnd - t0).toFixed(3), mp4], { stdio: 'inherit' });
  const probe = execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height,nb_frames,duration', '-of', 'csv=p=0', mp4], { encoding: 'utf8' }).trim();
  if (!KEEP) fs.rmSync(fdir, { recursive: true, force: true });
  return { mp4, frames: frames.length, seconds: +(tEnd - t0).toFixed(1), probe };
}

// ── step interpreter ──────────────────────────────────────────────────────────────────────────
async function runSteps(steps) {
  for (const st of steps) {
    const ev = stepLog.t0 !== null ? { i: stepLog.events.length, do: st.do, ...(st.to || st.sel ? { target: st.to || st.sel } : {}), start: +(now() - stepLog.t0).toFixed(3) } : null;
    if (ev && (st.to || st.sel)) { try { const r = await rect(st.to || st.sel); ev.rect = { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.w), h: Math.round(r.h) }; } catch { /* not on the page yet (wait-for) */ } }
    if (st.do === 'hold')        { await sleep(st.s * 1000); }
    else if (st.do === 'move')   {
      const r = await rect(st.to); const p = pointFor(r, st.at, st.dx || 0, st.dy || 0);
      if (p.x < 0 || p.y < 0 || p.x > VP.width || p.y > VP.height) throw new Error(`move target off screen: ${st.to} at (${p.x}, ${p.y}) — add a scroll-to step first`);
      await moveTo(p.x, p.y, st.s ?? 1.2);
    }
    else if (st.do === 'move-xy'){ await moveTo(st.x, st.y, st.s ?? 1.2); }
    else if (st.do === 'park')   { const p = await parkPoint(st.where); await moveTo(p.x, p.y, st.s ?? 0); }
    else if (st.do === 'click')  {
      await page.evaluate(() => window.__rec && window.__rec.pulse());
      const press = async () => { await page.mouse.down(); await sleep(70); await page.mouse.up(); };
      if (st.nav) { await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: (st.timeout ?? 30) * 1000 }), press()]); await prep().catch(() => {}); }   // the click loads a new document (redirects included)
      else { await press(); }
    }
    else if (st.do === 'type')   { await page.keyboard.type(st.text, { delay: st.delay ?? 110 }); }
    else if (st.do === 'press')  {
      if (st.nav) { await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: (st.timeout ?? 30) * 1000 }), page.keyboard.press(st.key)]); await prep().catch(() => {}); }
      else { await page.keyboard.press(st.key); }
    }
    else if (st.do === 'back')   { await page.goBack({ waitUntil: 'domcontentloaded' }); await page.waitForLoadState('load').catch(() => {}); await prep(); }
    else if (st.do === 'goto')   { await page.goto(BASE + sub(st.url), { waitUntil: 'domcontentloaded' }); await page.waitForLoadState('load').catch(() => {}); await prep(); }
    else if (st.do === 'wait-for') {                          // until the element exists and is visible (an AJAX install, a redirect chain…)
      const t0 = Date.now(), limit = (st.timeout ?? 60) * 1000;
      for (;;) {
        try {
          await page.evaluate(RESOLVER);
          const ok = await page.evaluate((s) => { const el = window.__resolve(s); if (!el) return false; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0; }, sub(st.sel));
          if (ok) break;
        } catch (e) { if (!/Execution context was destroyed|navigation/i.test(e.message)) throw e; }
        if (Date.now() - t0 > limit) throw new Error(`wait-for timed out after ${st.timeout ?? 60} s: ${st.sel}`);
        await sleep(250);
      }
      await prep();
    }
    else if (st.do === 'scroll-to') { const r = await rect(st.to); const y = Math.max(0, Math.round(r.docY - (st.offset ?? 140))); await page.evaluate(([y, ms]) => window.__rec.scrollTo(y, ms), [y, (st.s ?? 1.5) * 1000]); }
    else if (st.do === 'timer-start') { await page.evaluate(() => { sessionStorage.setItem('__rec_timer_t0', String(Date.now())); window.__rec && window.__rec.timer(); }); }
    else if (st.do === 'timer-stop')  { await page.evaluate(() => { sessionStorage.removeItem('__rec_timer_t0'); window.__rec && window.__rec.timer(); }); }
    else if (st.do === 'note')   { await page.evaluate(RESOLVER); const t = await page.evaluate((s) => { const el = window.__resolve(s); return el ? el.innerText.split(/\n+/).map(t => t.replace(/\s+/g, ' ').trim()).filter(Boolean).join(' · ') : null; }, sub(st.sel)); state.notes.push([st.label, t ?? `(not found: ${st.sel})`]); }
    else if (st.do === 'home')   { const y = await homeY(); await page.evaluate((y) => window.scrollTo(0, y), y); await sleep(250); }
    else if (st.do === 'scroll-home') { const y = await homeY(); await page.evaluate(([y, ms]) => window.__rec.scrollTo(y, ms), [y, (st.s ?? 4) * 1000]); }
    else if (st.do === 'wait-ready') { const r = await waitReady(plan.ready[st.ready] || st.checks || []); await prep(); if (!r.ok) throw new Error('not ready after the click: ' + r.problems.join('; ')); await ensureFacts(); }
    else throw new Error(`unknown step: ${JSON.stringify(st)}`);
    if (ev) {
      ev.end = +(now() - stepLog.t0).toFixed(3);
      if (st.do === 'wait-for' || st.do === 'wait-ready' || (st.do === 'click' && st.nav)) { try { const r = await rect(st.sel || st.to || 'body'); if (st.sel) ev.rect = { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.w), h: Math.round(r.h) }; } catch { } }
      ev.url = page.url().replace(BASE, '');
      stepLog.events.push(ev);
    }
  }
}

// ── main ──────────────────────────────────────────────────────────────────────────────────────
const startedAt = new Date();
const results = [];
let currentBuild = activeBuild();
const originalBuild = currentBuild;
try {
  const scenes = plan.scenes.filter(s => !ONLY || ONLY.includes(s.id));
  if (!scenes.length) throw new Error('no scene matched --only');
  const who = await login(plan.user);
  log(`logged in as "${who}" (${plan.user}); site ${siteInfo()}; build ${currentBuild}`);

  if (PROBE && !plan.home) {
    // Generic probe (videos without V1's home-position geometry): each selected scene's start page —
    // readiness audit and a screenshot, no steps. Without --only, the first scene only.
    const PDIR = path.join(os.tmpdir(), 'pdwchs-probe'); fs.mkdirSync(PDIR, { recursive: true });
    for (const s of (ONLY ? scenes : scenes.slice(0, 1))) {
      await useContext(s.context || 'admin');
      const t0 = Date.now();
      await page.goto(BASE + sub(s.url), { waitUntil: 'domcontentloaded' });
      const ready = await waitReady(plan.ready[s.ready] || []);
      const shot = path.join(PDIR, `probe-${s.id}.png`);
      await page.screenshot({ path: shot });
      log(JSON.stringify({ scene: s.id, context: s.context || 'admin', url: s.url, loadMs: Date.now() - t0, ready, screenshot: shot }, null, 2));
    }
  } else if (PROBE) {
    const s = scenes[0];
    if (s.build && s.build !== currentBuild) { switchBuild(s.build); currentBuild = s.build; }
    const t0 = Date.now();
    await page.goto(BASE + sub(s.url), { waitUntil: 'domcontentloaded' });
    const ready = await waitReady(plan.ready[s.ready] || []);
    await prep();
    const geo = {};
    for (const sel of ['#adminmenu', '#wpadminbar', '.pdwchs-revenue-widget', 'heading:Revenue Analytics', '#pdwchs-revenue-date-picker', '.pdwchs-data-integrity', '.pdwchs-revenue-cards-grid', '#pdwchs-revenue-chart', '.pdwchs-revenue-split-section', '.pdwchs-revenue-split-grid', 'heading:Retention']) {
      try { const r = await rect(sel); geo[sel] = { docY: Math.round(r.docY), x: Math.round(r.x), w: Math.round(r.w), h: Math.round(r.h) }; } catch { geo[sel] = 'not found'; }
    }
    const home = await homeReport();
    await page.evaluate((y) => window.scrollTo(0, y), home.homeY); await sleep(300);
    const p = await parkPoint('empty'); await moveTo(p.x, p.y, 0);
    const PDIR = path.join(os.tmpdir(), 'pdwchs-probe'); fs.mkdirSync(PDIR, { recursive: true });   // never into the deliverables folder
    await page.screenshot({ path: path.join(PDIR, 'probe.png') });
    const facts = decideFacts(await splitValues());
    // screencast frame size check
    const cdp = await ctx.newCDPSession(page);
    let size = null;
    cdp.on('Page.screencastFrame', async ({ data, sessionId }) => { if (!size) { const b = Buffer.from(data, 'base64'); fs.writeFileSync(path.join(PDIR, 'probe-frame.jpg'), b); size = b.length; } cdp.send('Page.screencastFrameAck', { sessionId }).catch(() => {}); });
    await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 93, maxWidth: VP.width * VP.scale, maxHeight: VP.height * VP.scale, everyNthFrame: 1 });
    await page.mouse.move(p.x + 5, p.y + 5); await sleep(800);
    await cdp.send('Page.stopScreencast').catch(() => {}); await cdp.detach().catch(() => {});
    const dims = size ? execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'stream=width,height', '-of', 'csv=p=0', path.join(PDIR, 'probe-frame.jpg')], { encoding: 'utf8' }).trim() : 'no frame';
    log(JSON.stringify({ loadMs: Date.now() - t0, ready, geometry: geo, home, park: p, facts, screencastFrame: { bytes: size, dims } }, null, 2));
    log(`probe.png written to ${PDIR}`);
  } else {
    for (const s of scenes) {
      log(`\n▶ ${s.id}  (${s.note || ''})`);
      try {
        if (s.build && s.build !== currentBuild) { switchBuild(s.build); currentBuild = s.build; }
        if (s.preflight && plan.preflight) {
          const [cmd, ...cargs] = plan.preflight;
          let pf = { ok: true, out: '' };
          try { pf.out = execFileSync(path.join(ROOT, cmd), cargs, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }); }
          catch (e) { pf = { ok: false, out: `${e.stdout || ''}${e.stderr || ''}` }; }
          const fails = pf.out.split('\n').filter(l => /^FAIL|Error:/.test(l));
          if (!pf.ok) {
            log(`  preflight FAILED (${plan.preflight.join(' ')}):\n    ${fails.join('\n    ')}`);
            if (!REHEARSAL && !FORCE) { results.push({ id: s.id, ok: false, error: 'preflight failed: ' + fails.join('; ') }); log('  STOP — not recorded (--rehearsal or --force to override)'); continue; }
            log(`  ${REHEARSAL ? 'rehearsal' : '--force'}: recording anyway`);
          } else log('  preflight PASS');
        }
        await useContext(s.context || 'admin');
        await page.goto(BASE + sub(s.url), { waitUntil: 'domcontentloaded' });
        const ready = await waitReady(plan.ready[s.ready] || []);
        await prep();
        log(`  ready in ${ready.waitedMs} ms${ready.ok ? '' : ' — PROBLEMS: ' + ready.problems.join('; ')}`);
        if (!ready.ok && !FORCE) { results.push({ id: s.id, ok: false, error: ready.problems.join('; ') }); log(`  STOP — not recorded (use --force to override)`); continue; }
        await ensureFacts();
        const notes0 = state.notes.length;
        if (s.setup) { cur.x = -300; await runSteps(s.setup); await sleep(400); }
        const home = s.setup?.some(x => x.do === 'home') ? await homeReport() : null;
        if (home) log(`  home: ${JSON.stringify(home)}`);
        const r = await record(s.id, () => runSteps(s.steps));
        await page.evaluate(() => { try { sessionStorage.removeItem('__rec_timer_t0'); window.__rec && window.__rec.timer(); } catch (e) {} }).catch(() => {});
        log(`  ✓ ${path.basename(r.mp4)}  ${r.seconds} s, ${r.frames} screencast frames → ${r.probe}`);
        results.push({ id: s.id, ok: true, ...r });
        if (state.notes.length > notes0) fs.writeFileSync(path.join(OUT, `${s.id}.notes.json`), JSON.stringify(state.notes.slice(notes0), null, 2) + '\n');
        // stills from the answer-frame scene
        for (const [name, spec] of Object.entries(plan.stills || {})) {
          if (spec.scene !== s.id) continue;
          if (spec.kind === 'viewport') { await page.screenshot({ path: path.join(OUT, `${name}.png`) }); log(`  ✓ ${name}.png`); }
          else if (spec.kind === 'clip') {
            const rr = await rect(spec.selector); const pad = spec.pad ?? 24;
            const clip = { x: Math.max(0, rr.x - pad), y: Math.max(0, rr.y - pad), width: rr.w + pad * 2, height: rr.h + pad * 2 };
            await page.screenshot({ path: path.join(OUT, `${name}.png`), clip });
            log(`  ✓ ${name}.png`);
            // sidecar for make-end-card.mjs: rects of the "measure" selectors, in the still's own pixels
            if (spec.measure?.length) {
              const rects = {};
              for (const m of spec.measure) {
                const r = await rect(m).catch(() => null);
                if (r) rects[m] = { x: Math.round((r.x - clip.x) * VP.scale), y: Math.round((r.y - clip.y) * VP.scale), w: Math.round(r.w * VP.scale), h: Math.round(r.h * VP.scale) };
              }
              fs.writeFileSync(path.join(OUT, `${name}.json`), JSON.stringify({ image: `${name}.png`, scale: VP.scale, rects }, null, 2) + '\n');
              log(`  ✓ ${name}.json (${Object.keys(rects).join(', ')})`);
            }
          }
        }
      } catch (e) {
        // one broken scene must not end the run: log it, leave any half-written clip out, carry on
        const msg = e.message.split('\n')[0];
        log(`  ✗ ${s.id} failed: ${msg}`);
        results.push({ id: s.id, ok: false, error: msg });
        fs.rmSync(path.join(OUT, `${s.id}.frames`), { recursive: true, force: true });
      }
    }
    // delivery note (which card is bigger — never the amounts). The clip table comes from the files on
    // disk, so a partial re-run (--only scene-02) refreshes the note without losing the other clips.
    if (state.facts) {
      const f = state.facts;
      const failed = Object.fromEntries(results.filter(r => !r.ok).map(r => [r.id, r.error]));
      const rows = plan.scenes.map(s => {
        const mp4 = path.join(OUT, `${s.id}.mp4`);
        if (failed[s.id]) return `| ${s.id} | — | NOT RECORDED: ${failed[s.id]} |`;
        if (!fs.existsSync(mp4)) return `| ${s.id} | — | missing |`;
        const [w, h, dur] = execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height,duration', '-of', 'csv=p=0', mp4], { encoding: 'utf8' }).trim().split(',');
        const when = fs.statSync(mp4).mtime.toISOString().replace('T', ' ').slice(0, 16);
        return `| ${s.id} | ${Number(dur).toFixed(1)} s | ${w}×${h}, recorded ${when} UTC |`;
      });
      const note = `# ${VIDEO.toUpperCase()} delivery note\n\nRecorded by \`record-scenes.mjs\` on the local store (last run ${startedAt.toISOString().replace('T', ' ').slice(0, 16)} UTC) — site ${siteInfo()}; frame 1920 × 1080 CSS px at 2× (3840 × 2160), ${FPS} fps, H.264 CRF ${CRF}.\n\n1. **Which box shows the largest amount:** ${f.biggestLabel} → script option ${f.option}.\n2. **Where the Guest Orders box sits:** ${f.guest} → option ${f.guestOption}.\n3. **Margin:** the largest card is ${f.margin}× the next one.\n4. **Clips:**\n\n| Clip | Length | Source |\n|---|---|---|\n${rows.join('\n')}\n\nNo amounts in this note by rule; the answer-frame stills carry what the screen showed.\n`;
      fs.writeFileSync(path.join(OUT, 'delivery-note.md'), note);
      log(`\n✓ delivery-note.md`);
    } else if (!plan.home) {
      // Generic note (no V1 card facts): what the page showed, as text — never amounts — and the clip table.
      const failed = Object.fromEntries(results.filter(r => !r.ok).map(r => [r.id, r.error]));
      const rows = plan.scenes.map(s => {
        const mp4 = path.join(OUT, `${s.id}.mp4`);
        if (failed[s.id]) return `| ${s.id} | — | NOT RECORDED: ${failed[s.id]} |`;
        if (!fs.existsSync(mp4)) return `| ${s.id} | — | missing |`;
        const [w, h, dur] = execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height,duration', '-of', 'csv=p=0', mp4], { encoding: 'utf8' }).trim().split(',');
        const when = fs.statSync(mp4).mtime.toISOString().replace('T', ' ').slice(0, 16);
        return `| ${s.id} | ${Number(dur).toFixed(1)} s | ${w}×${h}, recorded ${when} UTC |`;
      });
      // facts from every scene's sidecar (so a partial re-run keeps the other scenes' facts)
      const all = plan.scenes.flatMap(s => { const f = path.join(OUT, `${s.id}.notes.json`); return fs.existsSync(f) ? JSON.parse(fs.readFileSync(f, 'utf8')).map(([k, v]) => [`${k} (${s.id})`, v]) : []; });
      const facts = all.map(([k, v], i) => `${i + 1}. **${k}:** ${v}`).join('\n');
      const note = `# ${VIDEO.toUpperCase()} delivery note${REHEARSAL ? ' — REHEARSAL, not for the editor' : ''}\n\nRecorded by \`record-scenes.mjs\` on ${BASE} (last run ${startedAt.toISOString().replace('T', ' ').slice(0, 16)} UTC) — site ${siteInfo()}; frame 1920 × 1080 CSS px at 2× (3840 × 2160), ${FPS} fps, H.264 CRF ${CRF}.\n\n**What the screens showed** (text only; no amounts by rule):\n\n${facts || '(no notes)'}\n\n**Clips:**\n\n| Clip | Length | Source |\n|---|---|---|\n${rows.join('\n')}\n`;
      fs.writeFileSync(path.join(OUT, 'delivery-note.md'), note);
      log(`\n✓ delivery-note.md`);
    } else {
      log(`\n(delivery-note.md not written: no scene loaded the dashboard, so the A/B and C/D facts are unknown)`);
    }
  }
} finally {
  if (originalBuild && currentBuild !== originalBuild) { switchBuild(originalBuild); }
  await browser.close();
}
const failed = results.filter(r => !r.ok).length;
if (!PROBE) log(`\n${results.length - failed} recorded, ${failed} not recorded`);
process.exit(failed ? 1 : 0);
