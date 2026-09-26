/**
 * Screenshot capture for the marketing brief.
 *
 *   node capture-screens.mjs                 # every shot in shots.json
 *   node capture-screens.mjs --only DASH-00,PROF-06
 *   node capture-screens.mjs --tier free     # only shots of that tier
 *   node capture-screens.mjs --outline <url> # print the page outline (ids/classes/headings) to build selectors
 *
 * Needs: .auth/creds.json  {"user","pass","base"}  (a local admin account, never committed)
 *        Google Chrome installed (playwright-core uses channel "chrome", nothing is downloaded)
 *        the pdwchs-capture-tier mu-plugin when shots of both tiers are captured (see README.md)
 */
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const here   = path.dirname(new URL(import.meta.url).pathname);
const creds  = JSON.parse(fs.readFileSync(path.join(here, '.auth/creds.json'), 'utf8'));
const shots  = JSON.parse(fs.readFileSync(path.join(here, 'shots.json'), 'utf8'));
const outDir = path.join(here, '..', 'screenshots');
fs.mkdirSync(outDir, { recursive: true });

const args = process.argv.slice(2);
const opt  = (name) => { const i = args.indexOf(name); return i >= 0 ? args[i + 1] : null; };
const only = opt('--only') ? opt('--only').split(',') : null;
const tierFilter = opt('--tier');
const outlineUrl = opt('--outline');

// Site-side helpers run through WP-CLI (MAMP PHP so `localhost` resolves to MAMP's socket).
const SITE = creds.site || path.resolve(here, '../../..');   // ch site root (project_asset/marketing/tooling)
const PHP  = creds.php  || '/Applications/MAMP/bin/php/php8.2.0/bin/php';
const WP   = creds.wp   || '/Applications/MAMP/Library/bin/wp';
function wp(...a) {
  return execFileSync(PHP, ['-d', 'error_reporting=0', WP, ...a, `--path=${SITE}`], { encoding: 'utf8' }).trim();
}
function setTier(tier) {
  wp('option', 'update', 'pdwchs_capture_tier', tier);
  // Re-show dismissed upsell banners / trial notice for the shot user.
  wp('eval', `$u=get_user_by('login','${creds.user}'); foreach(get_user_meta($u->ID) as $k=>$v){ if(strpos($k,'pdwchs_upsell_dismissed_')===0||strpos($k,'pdwchs_trial_notice_dismissed_')===0) delete_user_meta($u->ID,$k);} delete_transient('pdwchs_rfm_distribution');`);
}
// Hero: the dev seeder's option, else the ch demo-tools ledger (Northline dataset).
const vars = JSON.parse(wp('eval', `$h=(int)(get_option('pdwchs_demo_seed_hero_id') ?: (class_exists('PDWCHS_Demo_Ledger') ? PDWCHS_Demo_Ledger::hero_user_id() : 0)); echo wp_json_encode(array('HERO'=>$h, 'HERO_ORDER'=>(int)(wc_get_orders(array('customer_id'=>$h,'limit'=>1,'orderby'=>'date','order'=>'DESC','return'=>'ids'))[0] ?? 0), 'CART'=>(int)$GLOBALS['wpdb']->get_var("SELECT id FROM {$GLOBALS['wpdb']->prefix}pdwchs_carts WHERE status='abandoned' AND user_id IS NOT NULL ORDER BY abandoned_at DESC LIMIT 1")));`));
const sub = (s) => typeof s === 'string' ? s.replace(/\{(\w+)\}/g, (_, k) => vars[k] ?? `{${k}}`) : s;

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
const page = await ctx.newPage();
page.setDefaultTimeout(20000);

async function login() {
  await page.goto(creds.base + '/wp-login.php');
  await page.fill('#user_login', creds.user);
  await page.fill('#user_pass', creds.pass);
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
}
await login();

if (outlineUrl) {
  await page.goto(creds.base + sub(outlineUrl), { waitUntil: 'networkidle' });
  const outline = await page.evaluate(() => {
    const out = [];
    const sel = 'h1,h2,h3,h4,.notice,table,canvas,form,fieldset,select,button,.pdwchs-card,[class*="pdwchs-"],[class*="pd-woo"],[class*="pd-ch"],[id^="pdwchs"],[id^="pd-"]';
    document.querySelectorAll(sel).forEach(el => {
      const r = el.getBoundingClientRect();
      if (r.width < 8 || r.height < 8) return;
      const id = el.id ? '#' + el.id : '';
      const cls = el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : '';
      const txt = (el.tagName.match(/^H\d$/) || el.tagName === 'BUTTON' || el.classList.contains('notice')) ? ' "' + el.innerText.replace(/\s+/g, ' ').slice(0, 60) + '"' : '';
      out.push(`${Math.round(r.top + window.scrollY)},${Math.round(r.left)} ${Math.round(r.width)}x${Math.round(r.height)}  ${el.tagName.toLowerCase()}${id}${cls}${txt}`);
    });
    return out.join('\n');
  });
  console.log(outline);
  await browser.close();
  process.exit(0);
}

function pw(sel) {
  const m = sel && sel.match(/^(panel|heading):(.+?)(?: >> (.+))?$/);
  if (!m) return sel;
  const h = `:is(h1,h2,h3,h4,legend,label):has-text("${m[2].trim()}")`;
  if (m[1] === 'heading') return h;
  const container = `:is(.pd-woo-panel,.pdwchs-dashboard-widget,.pdwchs-churn-dashboard-widget,.pd-ch-panel,.pdwchs-settings-card,.postbox,.pdwchs-analytics-card,.pdwchs-prediction-panel,.pdwchs-settings-section,.pdwchs-cart-detail-meta-box,.pdwchs-churn-card,.pdwchs-health-card,.pdwchs-lifecycle-card,.notice):has(${h})`;
  return m[3] ? `${container} ${m[3]}` : container;
}
async function runActions(actions = []) {
  for (const a of actions) {
    const s = pw(sub(a.selector));
    if (a.type === 'click') { await page.locator(s).first().click(); }
    else if (a.type === 'select') { await page.locator(s).first().selectOption(sub(a.value)); }
    else if (a.type === 'fill') { await page.locator(s).first().fill(sub(a.value)); }
    else if (a.type === 'press') { await page.locator(s).first().press(a.key); }
    else if (a.type === 'goto') { await page.goto(creds.base + sub(a.url), { waitUntil: 'networkidle' }); }
    else if (a.type === 'wait') { await page.waitForTimeout(a.ms || 800); }
    else if (a.type === 'waitFor') { await page.locator(s).first().waitFor({ state: 'visible' }); }
    else if (a.type === 'evaluate') { await page.evaluate(a.js); }
    else if (a.type === 'scroll') { await page.locator(s).first().scrollIntoViewIfNeeded(); }
    if (a.settle !== false) { await page.waitForLoadState('networkidle').catch(() => {}); }
  }
}


// In-page selector resolver. Besides plain CSS it understands:
//   "panel:Heading text"            → the widget/card/panel that contains a heading with that text
//   "heading:Heading text"          → the heading element itself
//   "panel:Heading text >> css"     → a css descendant inside that panel
const RESOLVER = `
  window.__pdwchsResolve = function (sel) {
    const climb = (el) => { let n = el; while (n && n !== document.body) { const c = (n.className || '') + ''; if (/(card|panel|widget|postbox|section|box|notice)(\\s|$)/.test(c) && !/header|title|body|footer|-row|filters/.test(c) && n.getBoundingClientRect().width > 250) return n; n = n.parentElement; } return el; };
    const findHeading = (t) => { const hs = [...document.querySelectorAll('h1,h2,h3,h4,legend,label,th,strong')]; return hs.find(h => h.textContent.replace(/\\s+/g, ' ').trim().startsWith(t)) || hs.find(h => h.textContent.replace(/\\s+/g, ' ').includes(t)); };
    let m;
    if ((m = sel.match(/^(panel|heading):(.+?)(?: >> (.+))?$/))) {
      const h = findHeading(m[2].trim()); if (!h) return null;
      const base = m[1] === 'panel' ? climb(h) : h;
      return m[3] ? base.querySelector(m[3]) : base;
    }
    return document.querySelector(sel);
  };`;
async function installResolver() { await page.evaluate(RESOLVER); }

async function drawCallouts(callouts = []) {
  await installResolver();
  await page.evaluate((callouts) => {
    document.querySelectorAll('.pdwchs-shot-callout').forEach(e => e.remove());
    const sy = window.scrollY, sx = window.scrollX;
    for (const c of callouts) {
      const el = window.__pdwchsResolve(c.selector);
      if (!el) { window.__pdwchsMissing = (window.__pdwchsMissing || []).concat(c.selector); continue; }
      const r = el.getBoundingClientRect();
      const pad = c.pad ?? 4;
      const box = document.createElement('div');
      box.className = 'pdwchs-shot-callout';
      box.style.cssText = `position:absolute;left:${r.left + sx - pad}px;top:${r.top + sy - pad}px;width:${r.width + pad * 2}px;height:${r.height + pad * 2}px;border:3px solid #e11d48;border-radius:6px;box-shadow:0 0 0 2px rgba(255,255,255,.9);pointer-events:none;z-index:2147483000;box-sizing:border-box;`;
      const badge = document.createElement('div');
      badge.className = 'pdwchs-shot-callout';
      badge.textContent = String(c.n);
      badge.style.cssText = `position:absolute;left:${r.left + sx - pad - 14}px;top:${r.top + sy - pad - 14}px;width:28px;height:28px;border-radius:50%;background:#e11d48;color:#fff;font:700 15px/28px -apple-system,Segoe UI,Arial,sans-serif;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.4);z-index:2147483001;pointer-events:none;`;
      document.body.append(box, badge);
    }
  }, callouts.map(c => ({ ...c, selector: sub(c.selector) })));
  const missing = await page.evaluate(() => { const m = window.__pdwchsMissing || []; window.__pdwchsMissing = []; return m; });
  for (const m of missing) console.log(`  callout not found: ${m}`);
}

async function clipFor(shot) {
  if (!shot.clip) return null;
  const sels = Array.isArray(shot.clip) ? shot.clip : [shot.clip];
  let box = null;
  for (const s of sels) {
    await installResolver();
    const b = await page.evaluate((s) => {
      const el = window.__pdwchsResolve(s); if (!el) return null;
      const r = el.getBoundingClientRect();
      return { x: r.left + window.scrollX, y: r.top + window.scrollY, w: r.width, h: r.height };
    }, sub(s));
    if (!b) { console.warn(`  clip selector not found: ${s}`); continue; }
    box = box ? { x: Math.min(box.x, b.x), y: Math.min(box.y, b.y), w: Math.max(box.x + box.w, b.x + b.w) - Math.min(box.x, b.x), h: Math.max(box.y + box.h, b.y + b.h) - Math.min(box.y, b.y) } : b;
  }
  if (!box) return null;
  const pad = shot.pad ?? 24;
  const h = shot.maxHeight ? Math.min(box.h, shot.maxHeight) : box.h;
  return { x: Math.max(0, box.x - pad), y: Math.max(0, box.y - pad), width: box.w + pad * 2, height: h + pad * 2 };
}

const results = [];
let currentTier = null;
const list = shots.filter(s => (!only || only.includes(s.id)) && (!tierFilter || (s.tier || 'pro') === tierFilter));
for (const shot of list) {
  const tier = shot.tier || 'pro';
  try {
    if (tier !== currentTier) { setTier(tier); currentTier = tier; await page.goto(creds.base + '/wp-admin/', { waitUntil: 'networkidle' }); }
    await page.goto(creds.base + sub(shot.url), { waitUntil: 'networkidle' });
    if (shot.waitFor) { await page.locator(pw(sub(shot.waitFor))).first().waitFor({ state: 'visible' }); }
    await runActions(shot.actions);
    await page.waitForTimeout(shot.settleMs ?? 600);
    if (shot.hide) { await page.evaluate((sel) => document.querySelectorAll(sel).forEach(e => e.style.display = 'none'), shot.hide); }
    await drawCallouts(shot.callouts);
    const clip = await clipFor(shot);
    const file = path.join(outDir, `${shot.id}.png`);
    await page.screenshot({ path: file, fullPage: true, ...(clip ? { clip } : {}) });
    const kb = Math.round(fs.statSync(file).size / 1024);
    console.log(`ok   ${shot.id}  ${kb} KB  ${shot.title || ''}`);
    results.push({ ...shot, ok: true, kb });
  } catch (e) {
    console.log(`FAIL ${shot.id}  ${e.message.split('\n')[0]}`);
    results.push({ ...shot, ok: false, error: e.message.split('\n')[0] });
  }
}
if (currentTier && currentTier !== 'pro') { setTier('pro'); }

// INDEX.md for the doc's screenshot index (only when a full run happened).
if (!only && !tierFilter) {
  const rows = results.map(r => `| ${r.id} | ${r.tier || 'pro'} | ${r.title || ''} | ${(r.callouts || []).map(c => `${c.n} ${c.label}`).join('; ')} | ${r.ok ? 'ok' : 'FAILED: ' + r.error} |`);
  fs.writeFileSync(path.join(outDir, 'INDEX.md'), `# Screenshot index\n\nGenerated by tooling/capture-screens.mjs on ${new Date().toISOString().slice(0, 10)}.\n\n| ID | Tier | Screen | Callouts | Status |\n|---|---|---|---|---|\n${rows.join('\n')}\n`);
}
await browser.close();
const failed = results.filter(r => !r.ok).length;
console.log(`\n${results.length - failed} ok, ${failed} failed`);
process.exit(failed ? 1 : 0);
