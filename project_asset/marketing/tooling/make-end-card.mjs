/**
 * End card for a demo video (project_asset/docs/video-production.md §5).
 *
 *   node make-end-card.mjs --video v1            # → recordings/end-card.png + end-card.mp4
 *   node make-end-card.mjs --video v8 --seconds 5
 *
 * Reads the "endCard" block of ../../content/videos/<video>/scenes.json:
 *
 *   "endCard": {
 *     "image":     "recordings/answer-frame-crop.png",   // the answer-frame crop, relative to the video folder
 *     "trimX":     16,                                   // image px cut from left and right (grey page margin the crop carries)
 *     "highlight": "{biggest-card}",                     // a key in <image>.json "rects" (written by record-scenes.mjs),
 *                                                        //   or {"x","y","w","h"} in image px, or null for no outline
 *     "title":     "PureDevs Customer History",
 *     "cta":       "Start the trial · puredevs.com/pricing",   // Pro-answered → trial; free-answered → install free
 *     "label":     "Demo Store",                         // honesty label, bottom right
 *     "size":      [2560, 1440],                         // match the master's frame
 *     "seconds":   4
 *   }
 *
 * Renders the card with the installed Google Chrome (headless, no browser download) and encodes the
 * clip with ffmpeg (H.264, 30 fps). render-edit.mjs appends it to the last segment with a short crossfade
 * (edit.json "endCard"); the lower-third / "Demo Store" overlays end where the card starts (it carries both).
 */
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const here   = path.dirname(new URL(import.meta.url).pathname);
const ROOT   = path.resolve(here, '../../..');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const args  = process.argv.slice(2);
const opt   = (n, d = null) => { const i = args.indexOf(n); return i >= 0 ? args[i + 1] : d; };
const VIDEO = opt('--video', 'v1');
const vdir  = path.join(ROOT, 'project_asset/content/videos', VIDEO);
const plan  = JSON.parse(fs.readFileSync(path.join(vdir, 'scenes.json'), 'utf8'));
const cfg   = plan.endCard;
if (!cfg) throw new Error(`${VIDEO}/scenes.json has no "endCard" block`);

const [W, H] = cfg.size || [2560, 1440];
const SECONDS = Number(opt('--seconds', cfg.seconds ?? 4));
const k = H / 1440;                                                   // every size below is designed at 1440p
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

// image size and the highlight rect (image px)
const img = path.join(vdir, cfg.image);
const [imgW, imgH] = execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'stream=width,height', '-of', 'csv=p=0', img], { encoding: 'utf8' }).trim().split(',').map(Number);
let hl = cfg.highlight ?? null;
if (typeof hl === 'string') {
  const side = img.replace(/\.png$/, '.json');
  const rects = fs.existsSync(side) ? JSON.parse(fs.readFileSync(side, 'utf8')).rects || {} : {};
  if (!rects[hl]) throw new Error(`highlight "${hl}" not found in ${path.basename(side)} — re-record the still or give {"x","y","w","h"}`);
  hl = rects[hl];
}

// layout: the screenshot panel, then the product name, then the CTA pill, as one group
const trimX  = cfg.trimX ?? 0;
const visW   = imgW - 2 * trimX;
const s      = Math.min((W - 320 * k) / visW, (420 * k) / imgH);    // fit the panel width, never taller than 420 px
const shotW  = Math.round(visW * s), shotH = Math.round(imgH * s);
const padY   = 28 * k;
const panelH = shotH + 2 * padY;
const gapT   = 160 * k, gapC = 220 * k, ctaH = 164 * k;
const top    = Math.max(80 * k, Math.round((H - (panelH + gapT + gapC + ctaH)) / 2 - 83 * k));
const titleY = top + panelH + gapT;
const ctaY   = titleY + gapC;
const pad    = 9 * k;
const hlBox  = hl ? { x: (hl.x - trimX) * s - pad, y: hl.y * s - pad, w: hl.w * s + 2 * pad, h: hl.h * s + 2 * pad } : null;

const html = `<!doctype html><html><head><meta charset="utf-8"><style>
html,body{margin:0;width:${W}px;height:${H}px;overflow:hidden}
body{background:#f0f0f1;color:#1d2327;position:relative;font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.panel{position:absolute;left:${(W - shotW) / 2}px;top:${top}px;width:${shotW}px;padding:${padY}px 0;background:#fff;border-radius:${18 * k}px;box-shadow:0 ${10 * k}px ${40 * k}px rgba(0,0,0,.08),0 1px 3px rgba(0,0,0,.06)}
.shot{position:relative;width:${shotW}px;height:${shotH}px;overflow:hidden}
.shot img{display:block;width:${imgW * s}px;height:${shotH}px;margin-left:${-trimX * s}px}
.hl{position:absolute;box-sizing:border-box;border:${5 * k}px solid #00a32a;border-radius:${10 * k}px}
.name{position:absolute;left:0;right:0;top:${titleY}px;text-align:center;font-size:${132 * k}px;font-weight:700;letter-spacing:${-2 * k}px}
.cta{position:absolute;left:50%;top:${ctaY}px;transform:translateX(-50%);white-space:nowrap;background:#2271b1;color:#fff;font-size:${70 * k}px;font-weight:600;padding:${40 * k}px ${88 * k}px;border-radius:999px;box-shadow:0 ${8 * k}px ${24 * k}px rgba(34,113,177,.25)}
.demo{position:absolute;right:0;bottom:0;background:#cfe2f3;color:#1d2327;font-size:${40 * k}px;padding:${10 * k}px ${20 * k}px}
</style></head><body>
<div class="panel"><div class="shot"><img src="${new URL('file://' + img).href}" alt="">${hlBox ? `<div class="hl" style="left:${hlBox.x}px;top:${hlBox.y}px;width:${hlBox.w}px;height:${hlBox.h}px"></div>` : ''}</div></div>
<div class="name">${esc(cfg.title)}</div>
${cfg.cta ? `<div class="cta">${esc(cfg.cta)}</div>` : ''}
${cfg.label ? `<div class="demo">${esc(cfg.label)}</div>` : ''}
</body></html>`;

const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'pdwchs-endcard-'));
const htmlFile = path.join(tmp, 'end-card.html');
fs.writeFileSync(htmlFile, html);
const outDir = path.join(vdir, 'recordings');
fs.mkdirSync(outDir, { recursive: true });
const png = path.join(outDir, 'end-card.png');
const mp4 = path.join(outDir, 'end-card.mp4');

execFileSync(CHROME, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--force-device-scale-factor=1', `--window-size=${W},${H}`, `--screenshot=${png}`, `file://${htmlFile}`], { stdio: 'ignore' });
const dims = execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'stream=width,height', '-of', 'csv=p=0', png], { encoding: 'utf8' }).trim();
if (dims !== `${W},${H}`) throw new Error(`rendered ${dims}, expected ${W},${H}`);
execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-loop', '1', '-i', png, '-t', String(SECONDS), '-r', '30', '-c:v', 'libx264', '-preset', 'slow', '-crf', '14', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', mp4], { stdio: 'inherit' });
fs.rmSync(tmp, { recursive: true, force: true });

console.log(`✓ ${path.relative(ROOT, png)}  ${W}×${H}`);
console.log(`✓ ${path.relative(ROOT, mp4)}  ${SECONDS} s, H.264 30 fps`);
console.log(`  title "${cfg.title}" · CTA "${cfg.cta || '(none)'}" · highlight ${hl ? `${hl.w}×${hl.h} at ${hl.x},${hl.y} (image px)` : 'none'}`);
