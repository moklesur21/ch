/**
 * Edit renderer for the demo videos: turns the recorder's clips into the finished video — 1080p segments (one
 * per narration chunk or group of chunks) with picture, labels, voice and UI sounds, then the master and the cut.
 *
 *   node render-edit.mjs --video v8                 # every segment + picture.mp4 (all segments, stitched)
 *   node render-edit.mjs --video v8 --only V8_b2    # one segment
 *   node render-edit.mjs --video v8 --master        # also <VIDEO>_reference_master.mp4: every segment joined (re-encoded,
 *                                                   # no drift), captions from recordings/captions.srt burned in
 *   node render-edit.mjs --video v8 --cut           # only the 20–30 s cut, from the existing master (edit.json "cut")
 *   node render-edit.mjs --video v8 --master --cut  # both; each is checked by qc-video.mjs (loudness fixed in place)
 *
 * Reads ../../content/videos/<video>/edit.json. Per segment:
 *   "source"   a recorder clip id (scene-02) — or "still": an image path, with "duration"
 *   "ranges"   [[srcStart, srcEnd, speed], …] in source seconds (the recorder's <scene>.steps.json has
 *              the exact step times). speed > 1 shows the speed badge ("2× speed") while it plays.
 *   "zoom"     [[srcTime, x, y, w], …] keyframes in source seconds and CSS px (the 1920×1080 page);
 *              height follows 16:9. Smoothstep between keyframes; before the first / after the last: held.
 *   "labels"   [[labelId, srcFrom, srcTo|null, at?], …] overlays from the "labels" block (fade in / out);
 *              "at" overrides the label's placement. "badge": false hides the speed badge (only the
 *              one-take segment needs it: there the clock and the badge show the speed-up honestly)
 *   "endCard"  {"file": "recordings/end-card.mp4", "xfade": 0.5} appended with a crossfade
 *   "captionsTop" true → this segment's burned-in captions sit at the top (when its subject is at the bottom)
 *   "audio"    [[file, outStart, inStart?, inEnd?], …] optional — voice (or a piece of a voice file, trimmed
 *              to inStart–inEnd) placed at output seconds. Mixed, upmixed to stereo, AAC 192k
 * The "cut" block (top level) is the short cut, taken from the finished master (captions and all):
 *   "pieces"   [[segmentId, from, to|null], …] in that segment's own seconds; a negative "from" counts back from
 *              the segment's end (["V8_b5", -4.5, null] = its last 4.5 s, the end card)
 *   "xfade"    [s, …] the crossfade at each join, picture and voice together
 * → <VIDEO>_cut_<N>s.mp4.
 * The "sound" block (top level, optional) adds the shared audio library (audio-lib.mjs, outside the repo):
 *   "music"    a library id ("music/calm-minimal-01"): one bed under the whole master, faded in and out, ducked
 *              under the voice; level and ducking from audio-lib.json "mix"
 *   "clicks"   library ids, used in turn: a click on every click in the recorder's <scene>.steps.json
 *   "typing"   a library id, looped under every "type" step
 *   Times follow the speed ranges; a segment with "sfx": false gets no clicks or typing.
 * Labels are drawn once as transparent PNGs with the installed Chrome (playwright-core), so they use the
 * system font. Output: ../../content/videos/<video>/recordings/edit/ (video files are gitignored).
 * Needs: ffmpeg 7+, Google Chrome.
 */
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { qc } from './qc-video.mjs';
import { LIBCFG, assetFile, manifest } from './audio-lib.mjs';

const here = path.dirname(new URL(import.meta.url).pathname);
const ROOT = path.resolve(here, '../../..');
const args = process.argv.slice(2);
const opt = (n, d = null) => { const i = args.indexOf(n); return i >= 0 ? args[i + 1] : d; };
const VIDEO = opt('--video', 'v8');
const ONLY = opt('--only') ? opt('--only').split(',') : null;
const MASTER = args.includes('--master');
const CUT = args.includes('--cut');
const EDIT = !CUT || MASTER;                        // --cut alone re-cuts the existing master
const vdir = path.join(ROOT, 'project_asset/content/videos', VIDEO);
const edit = JSON.parse(fs.readFileSync(path.join(vdir, 'edit.json'), 'utf8'));
const REC = path.join(vdir, 'recordings');
const OUT = path.join(REC, 'edit');
const LDIR = path.join(OUT, 'labels');
fs.mkdirSync(LDIR, { recursive: true });
const [W, H] = edit.size || [1920, 1080];
const SOUND = edit.sound || null;
const MIX = LIBCFG.mix;
const lib = (id) => {
  const f = assetFile(id), a = manifest().assets[id];
  if (!fs.existsSync(f) || !a) throw new Error(`audio library: ${id} missing — node audio-lib.mjs --make ${id}`);
  return { file: f, lufs: a.lufs };
};
const FPS = edit.fps || 30;
const log = (...a) => console.log(...a);
const r3 = (n) => Math.round(n * 1000) / 1000;

// ── labels → transparent PNGs ───────────────────────────────────────────────────────────────────
const STYLES = {
  'lower-third': 'display:inline-flex;align-items:center;gap:14px;padding:16px 26px 16px 20px;border-radius:12px;background:rgba(17,17,17,.88);color:#fff;font:600 34px/1.1 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;border-left:7px solid #ffc107;box-shadow:0 6px 24px rgba(0,0,0,.25)',
  'pill': 'display:inline-block;padding:7px 16px;border-radius:999px;background:rgba(255,255,255,.92);color:#1d2327;font:500 21px/1.2 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;border:1px solid rgba(0,0,0,.18)',
  'badge': 'display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:12px;background:#ffc107;color:#111;font:700 26px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-variant-numeric:tabular-nums;box-shadow:0 4px 18px rgba(0,0,0,.25)',
};
async function drawLabels() {
  const defs = { ...(edit.labels || {}) };
  for (const s of edit.segments) if (s.badge !== false) for (const [, , sp] of (s.ranges || [])) if (sp > 1) defs[`speed-${sp}`] = { text: `${sp}× speed`, style: 'badge', at: edit.speedBadgeAt || 'bottom-right-above-timer' };
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
  const domId = (id) => 'l-' + id.replace(/[^a-z0-9-]/gi, '_');
  const html = Object.entries(defs).map(([id, d]) => `<div style="margin:20px"><span id="${domId(id)}" style='${STYLES[d.style]}'>${d.text}</span></div>`).join('');
  await page.setContent(`<html><body style="margin:0;background:transparent">${html}</body></html>`);
  const out = {};
  for (const [id, d] of Object.entries(defs)) {
    const file = path.join(LDIR, `${id}.png`);
    const el = page.locator(`#${domId(id)}`);
    await el.screenshot({ path: file, omitBackground: true });
    const box = await el.boundingBox();
    out[id] = { ...d, file, w: Math.round(box.width), h: Math.round(box.height) };
  }
  await browser.close();
  return out;
}
function placeLabel(l, at) {
  const m = 28;
  switch (at || l.at) {
    case 'bottom-left': return [m, H - m - l.h];
    case 'lower-third': return [m + 8, H - 200 - l.h];                     // clear of the burned-in captions
    case 'lower-third-right': return [W - m - 8 - l.w, H - 200 - l.h];
    case 'bottom-right-above-timer': return [W - 28 - l.w, H - 84 - 52 - 14 - l.h];   // the recorder's clock sits at right 28, bottom 84, ~52 px tall
    case 'top-right': return [W - m - l.w, m + 40];
    default: return [m, m];
  }
}

// ── time mapping: source seconds → output seconds through the speed ranges ───────────────────────
function mapper(ranges) {
  const segs = []; let o = 0;
  for (const [a, b, sp] of ranges) { segs.push({ a, b, sp, o }); o += (b - a) / sp; }
  const total = o;
  const f = (t) => {
    if (t <= segs[0].a) return 0;
    for (const s of segs) if (t <= s.b) return s.o + (Math.max(t, s.a) - s.a) / s.sp;
    return total;
  };
  return { f, segs, total };
}

// ── zoom keyframes → zoompan expressions (on a 2× source, CSS px × 2) ──────────────────────────────
function curve(kf, idx, scale) {
  // kf: [[tOut, x, y, w], …] sorted; returns an ffmpeg expression in 'it' (input time) for column idx
  const v = (k) => kf[k][idx] * scale;
  let expr = `${r3(v(kf.length - 1))}`;
  for (let k = kf.length - 2; k >= 0; k--) {
    const t0 = r3(kf[k][0]), t1 = r3(kf[k + 1][0]), a = r3(v(k)), b = r3(v(k + 1));
    const seg = a === b || t1 <= t0 ? `${a}` : `(${a}+(${r3(b - a)})*(st(0,clip((it-${t0})/${r3(t1 - t0)},0,1))*ld(0)*(3-2*ld(0))))`;
    expr = `if(lt(it,${t1}),${seg},${expr})`;
  }
  return `if(lt(it,${r3(kf[0][0])}),${r3(v(0))},${expr})`;
}
function zoomFilter(kfOut, srcW, srcH) {
  const k = srcW / W;                                   // source px per CSS px (2 for the recorder's clips)
  const zExpr = (() => {                                // zoom = srcW / (w * k) → expression on w
    const wcurve = curve(kfOut, 3, k);
    return `${srcW}/(${wcurve})`;
  })();
  return `zoompan=z='${zExpr}':x='${curve(kfOut, 1, k)}':y='${curve(kfOut, 2, k)}':d=1:s=${srcW}x${srcH}:fps=${FPS}`;
}

function probe(file) {
  const [w, h, d] = execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:format=duration', '-of', 'csv=p=0:s=,', file], { encoding: 'utf8' }).trim().split(/[\n,]+/);
  return { w: +w, h: +h, d: +d };
}

// ── one segment ────────────────────────────────────────────────────────────────────────────────
function renderSegment(s, labels) {
  const outFile = path.join(OUT, `${s.id}.mp4`);
  const inputs = [];
  const fc = [];
  let srcW, srcH, ranges, m;
  if (s.still) {
    const img = path.join(vdir, s.still);
    ({ w: srcW, h: srcH } = probe(img));
    inputs.push('-loop', '1', '-framerate', String(FPS), '-t', String(s.duration), '-i', img);
    ranges = [[0, s.duration, 1]];
    m = mapper(ranges);
    fc.push(`[0:v]fps=${FPS},format=yuv420p,setpts=PTS-STARTPTS[sp]`);
  } else {
    const clip = path.join(REC, `${s.source}.mp4`);
    ({ w: srcW, h: srcH } = probe(clip));
    inputs.push('-i', clip);
    ranges = s.ranges || [[0, probe(clip).d, 1]];
    m = mapper(ranges);
    const parts = ranges.map(([a, b, sp], i) => `[0:v]trim=start=${a}:end=${b},setpts=(PTS-STARTPTS)/${sp},fps=${FPS}[r${i}]`);
    fc.push(...parts, `${ranges.map((_, i) => `[r${i}]`).join('')}concat=n=${ranges.length}:v=1:a=0[sp]`);
  }
  const dur = m.total;
  // picture: zoom or plain downscale
  if (s.zoom && s.zoom.length) {
    const kfOut = s.zoom.map(([t, x, y, w]) => [m.f(t), x, y, w]);
    fc.push(`[sp]${zoomFilter(kfOut, srcW, srcH)},scale=${W}:${H}:flags=lanczos,setsar=1[pic]`);
  } else {
    fc.push(`[sp]scale=${W}:${H}:flags=lanczos,setsar=1[pic]`);
  }
  // overlays: labels + speed badges
  const ovs = [];
  for (const [id, a, b, at] of (s.labels || [])) ovs.push({ id, at, from: m.f(a), to: b === null ? dur : m.f(b) });
  if (s.badge !== false) for (const g of m.segs) if (g.sp > 1) ovs.push({ id: `speed-${g.sp}`, from: g.o, to: g.o + (g.b - g.a) / g.sp, hard: true });
  let last = 'pic';
  ovs.forEach((o, i) => {
    const l = labels[o.id];
    if (!l) throw new Error(`${s.id}: unknown label ${o.id}`);
    const idx = inputs.filter(x => x === '-i').length;
    inputs.push('-loop', '1', '-framerate', String(FPS), '-t', String(r3(dur + 1)), '-i', l.file);
    const [x, y] = placeLabel(l, o.at);
    // a label that starts the segment is there from the first frame (no fade-in): "PureDevs Customer History" in frame one
    const fadeIn = o.from > 0.05 ? `,fade=t=in:st=${r3(o.from)}:d=0.35:alpha=1` : '';
    const fade = o.hard ? '' : `${fadeIn},fade=t=out:st=${r3(Math.max(o.from, o.to - 0.35))}:d=0.35:alpha=1`;
    fc.push(`[${idx}:v]format=rgba${fade}[l${i}]`);
    fc.push(`[${last}][l${i}]overlay=x=${x}:y=${y}:enable='between(t,${r3(o.from)},${r3(o.to)})':shortest=0[o${i}]`);
    last = `o${i}`;
  });
  // end card with a crossfade
  let total = dur;
  if (s.endCard) {
    const ec = path.join(vdir, s.endCard.file);
    const idx = inputs.filter(x => x === '-i').length;
    inputs.push('-i', ec);
    const xf = s.endCard.xfade ?? 0.5;
    fc.push(`[${idx}:v]scale=${W}:${H},fps=${FPS},format=yuv420p,setsar=1[ec]`);
    fc.push(`[${last}]format=yuv420p,trim=duration=${r3(dur)}[pre]`);
    fc.push(`[pre][ec]xfade=transition=fade:duration=${xf}:offset=${r3(dur - xf)}[xf]`);
    last = 'xf';
    total = dur - xf + probe(ec).d;
  }
  fc.push(`[${last}]format=yuv420p,trim=duration=${r3(total)}[vout]`);
  // optional voice
  const aIn = [];
  for (const [file, at, a0, a1] of (s.audio || [])) {
    const f = path.join(vdir, file);
    if (!fs.existsSync(f)) throw new Error(`${s.id}: voice file missing: ${file}`);
    aIn.push({ idx: inputs.filter(x => x === '-i').length, at, a0, a1 });
    inputs.push('-i', f);
  }
  // UI sounds from the shared library: a click on every recorded click, typing under every typed stretch
  const fx = [];
  if (SOUND && s.source && s.sfx !== false) {
    const steps = path.join(REC, `${s.source}.steps.json`);
    const inside = (t) => m.segs.some(g => t >= g.a && t <= g.b);
    let k = 0;
    for (const e of fs.existsSync(steps) ? JSON.parse(fs.readFileSync(steps, 'utf8')).events : []) {
      if (e.do === 'click' && SOUND.clicks?.length && inside(e.start)) {
        fx.push({ file: lib(SOUND.clicks[k++ % SOUND.clicks.length]).file, at: m.f(e.start), gain: MIX.click_peak_db + 1 });   // clicks peak at −1 dBFS
      }
      if (e.do === 'type' && SOUND.typing && inside(e.start) && m.f(e.end) - m.f(e.start) > 0.15) {
        const t = lib(SOUND.typing);
        fx.push({ file: t.file, at: m.f(e.start), len: m.f(e.end) - m.f(e.start), gain: MIX.typing_lufs - t.lufs });
      }
    }
  }
  for (const x of fx) { x.idx = inputs.filter(v => v === '-i').length; inputs.push(...(x.len ? ['-stream_loop', '-1'] : []), '-i', x.file); }
  const maps = ['-map', '[vout]'];
  if (aIn.length || fx.length) {
    aIn.forEach((a, i) => {
      const trim = a.a0 !== undefined ? `atrim=start=${a.a0}:end=${a.a1},asetpts=PTS-STARTPTS,` : '';
      fc.push(`[${a.idx}:a]${trim}aresample=48000,adelay=${Math.round(a.at * 1000)}:all=1[a${i}]`);
    });
    fx.forEach((x, i) => {
      const shape = x.len ? `atrim=0:${r3(x.len)},asetpts=PTS-STARTPTS,afade=t=in:d=0.04,afade=t=out:st=${r3(Math.max(0, x.len - 0.08))}:d=0.08,` : '';
      fc.push(`[${x.idx}:a]${shape}aresample=48000,aformat=channel_layouts=mono,volume=${r3(x.gain)}dB,adelay=${Math.round(x.at * 1000)}:all=1[x${i}]`);
    });
    const ins = [...aIn.map((_, i) => `[a${i}]`), ...fx.map((_, i) => `[x${i}]`)];
    fc.push(`${ins.join('')}amix=inputs=${ins.length}:normalize=0,pan=stereo|c0=0.7071*c0|c1=0.7071*c0,apad,atrim=duration=${r3(total)}[aout]`);
    maps.push('-map', '[aout]', '-c:a', 'aac', '-b:a', '192k');
  }
  const script = path.join(OUT, `${s.id}.filter.txt`);
  fs.writeFileSync(script, fc.join(';\n'));
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...inputs, '-/filter_complex', script, ...maps,
    '-c:v', 'libx264', '-preset', 'medium', '-crf', '17', '-pix_fmt', 'yuv420p', '-r', String(FPS), '-movflags', '+faststart', outFile], { stdio: 'inherit' });
  const p = probe(outFile);
  return { id: s.id, file: outFile, seconds: r3(p.d), size: `${p.w}×${p.h}`, map: m.segs.map(g => ({ src: [g.a, g.b], speed: g.sp, out: r3(g.o) })) };
}

// ── main ───────────────────────────────────────────────────────────────────────────────────────
if (EDIT) {
  const labels = await drawLabels();
  const done = [];
  for (const s of edit.segments) {
    if (ONLY && !ONLY.includes(s.id)) continue;
    const t0 = Date.now();
    const r = renderSegment(s, labels);
    log(`✓ ${s.id}.mp4  ${r.seconds} s  ${r.size}  (${((Date.now() - t0) / 1000).toFixed(0)} s to render)`);
    done.push(r);
  }
  fs.writeFileSync(path.join(OUT, 'segments.json'), JSON.stringify(done, null, 2) + '\n');
  if (!ONLY) {
    const list = path.join(OUT, 'concat.txt');
    fs.writeFileSync(list, edit.segments.map(s => `file '${s.id}.mp4'`).join('\n') + '\n');
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', list, '-c', 'copy', '-movflags', '+faststart', path.join(OUT, 'picture.mp4')], { stdio: 'inherit' });
    const total = done.reduce((a, r) => a + r.seconds, 0);
    log(`✓ picture.mp4  ${r3(total)} s (all segments, stitched)`);
  }
}

// ── reference master: joined + captions burned in ─────────────────────────────────────────────────
if (MASTER) {
  const srt = path.join(REC, 'captions.srt');
  const blocks = fs.readFileSync(srt, 'utf8').trim().split(/\n\s*\n/);
  const sec = (t) => { const [h, m, r] = t.split(':'); const [s2, ms] = r.split(','); return +h * 3600 + +m * 60 + +s2 + +ms / 1000; };
  const ass = (t) => { const h = Math.floor(t / 3600), m = Math.floor(t / 60) % 60, s2 = (t % 60).toFixed(2).padStart(5, '0'); return `${h}:${String(m).padStart(2, '0')}:${s2}`; };
  // segment windows on the joined timeline, for "captionsTop"
  let t = 0; const top = [];
  for (const s of edit.segments) { const d = probe(path.join(OUT, `${s.id}.mp4`)).d; if (s.captionsTop) top.push([t, t + d]); t += d; }
  const ev = blocks.map(b => {
    const p = b.split('\n'); const [a, z] = p[1].split(' --> ').map(sec);
    let txt = p.slice(2).join(' ').replace(/<b>/g, '{\\c&H07C1FF&}').replace(/<\/b>/g, '{\\c&HFFFFFF&}');
    if (top.some(([x, y]) => a >= x && a < y)) txt = '{\\an8}' + txt;
    return `Dialogue: 0,${ass(a)},${ass(z)},Cap,,0,0,0,,${txt}`;
  });
  fs.writeFileSync(path.join(OUT, 'captions.ass'), `[Script Info]
ScriptType: v4.00+
PlayResX: ${W}
PlayResY: ${H}
WrapStyle: 0
ScaledBorderAndShadow: yes

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Cap,Helvetica Neue,50,&H00FFFFFF,&H00FFFFFF,&H00111111,&H28111111,1,0,0,0,100,100,0,0,3,14,0,2,360,360,86,1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
${ev.join('\n')}
`);
  const ins = edit.segments.flatMap(s => ['-i', path.join(OUT, `${s.id}.mp4`)]);
  const n = edit.segments.length;
  let fc = `${edit.segments.map((_, i) => `[${i}:v][${i}:a]`).join('')}concat=n=${n}:v=1:a=1[v][a];[v]subtitles=captions.ass[vs]`;
  let aOut = '[a]';
  if (SOUND?.music) {                                   // one bed under the whole master, ducked under the voice
    const mu = lib(SOUND.music);
    ins.push('-stream_loop', '-1', '-i', mu.file);
    fc += `;[a]asplit=2[av][key];[${n}:a]aresample=48000,aformat=channel_layouts=stereo,atrim=0:${r3(t)},asetpts=PTS-STARTPTS,` +
      `volume=${r3(MIX.music_lufs - mu.lufs)}dB,afade=t=in:d=${MIX.music_fade_in},afade=t=out:st=${r3(t - MIX.music_fade_out)}:d=${MIX.music_fade_out}[mu];` +
      `[mu][key]${MIX.duck}[mud];[av][mud]amix=inputs=2:normalize=0:duration=first[am]`;
    aOut = '[am]';
  }
  const master = path.join(OUT, `${VIDEO.toUpperCase()}_reference_master.mp4`);
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...ins, '-filter_complex', fc, '-map', '[vs]', '-map', aOut, '-c:v', 'libx264', '-preset', 'medium', '-crf', '17',
    '-pix_fmt', 'yuv420p', '-r', String(FPS), '-c:a', 'aac', '-b:a', '192k', '-ar', '48000', '-movflags', '+faststart', '-f', 'mp4', master + '.tmp'], { stdio: 'inherit', cwd: OUT });
  fs.renameSync(master + '.tmp', master);                       // a failed render never truncates the last good master
  log(`✓ ${path.basename(master)}  ${r3(probe(master).d)} s`);
  if (!qc(master, { fix: true, inPlace: true }).pass) process.exitCode = 1;
}

// ── the 20–30 s cut: pieces of the master, crossfaded ────────────────────────────────────────────
if (CUT) {
  if (!edit.cut) throw new Error('edit.json has no "cut" block');
  const master = path.join(OUT, `${VIDEO.toUpperCase()}_reference_master.mp4`);
  if (!fs.existsSync(master)) throw new Error(`${path.basename(master)} missing — render it first with --master`);
  const start = {}; let t = 0;
  for (const s of edit.segments) { const d = probe(path.join(OUT, `${s.id}.mp4`)).d; start[s.id] = [t, d]; t += d; }
  const pieces = edit.cut.pieces.map(([id, from, to]) => {
    if (!start[id]) throw new Error(`cut: no segment ${id}`);
    const [s0, d] = start[id];
    const a = s0 + (from < 0 ? d + from : from), b = s0 + (to === null || to === undefined ? d : Math.min(to, d));
    return { a: r3(a), len: r3(b - a) };
  });
  const xf = edit.cut.xfade || [];
  const fc = pieces.map((p, i) => `[${i}:v]settb=AVTB,setpts=PTS-STARTPTS,fps=${FPS},format=yuv420p[v${i}];[${i}:a]asetpts=PTS-STARTPTS[a${i}]`);
  let len = pieces[0].len, v = 'v0', a = 'a0';
  for (let i = 1; i < pieces.length; i++) {
    const d = xf[i - 1] ?? 0.3;
    fc.push(`[${v}][v${i}]xfade=transition=fade:duration=${d}:offset=${r3(len - d)}[vx${i}]`, `[${a}][a${i}]acrossfade=d=${d}[ax${i}]`);
    v = `vx${i}`; a = `ax${i}`; len = len + pieces[i].len - d;
  }
  const out = path.join(OUT, `${VIDEO.toUpperCase()}_cut_${Math.round(len)}s.mp4`);
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...pieces.flatMap(p => ['-ss', String(p.a), '-t', String(p.len), '-i', master]),
    '-filter_complex', fc.join(';'), '-map', `[${v}]`, '-map', `[${a}]`, '-c:v', 'libx264', '-preset', 'medium', '-crf', '17', '-pix_fmt', 'yuv420p',
    '-r', String(FPS), '-c:a', 'aac', '-b:a', '192k', '-ar', '48000', '-movflags', '+faststart', '-f', 'mp4', out + '.tmp'], { stdio: 'inherit' });
  fs.renameSync(out + '.tmp', out);
  log(`✓ ${path.basename(out)}  ${r3(probe(out).d)} s  (${pieces.map(p => `${p.a}–${r3(p.a + p.len)}`).join(' + ')} of the master)`);
  if (!qc(out, { cut: true, fix: true, inPlace: true }).pass) process.exitCode = 1;
}

