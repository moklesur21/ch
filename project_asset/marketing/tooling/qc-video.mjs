/**
 * Delivery check for a finished demo video: PASS / FAIL per spec (docs/video-production.md §3, step 5).
 *
 *   node qc-video.mjs <file.mp4>               # a master: 60–120 s
 *   node qc-video.mjs <file.mp4> --cut         # the 20–30 s cut
 *   node qc-video.mjs <file.mp4> --fix         # loudness off → writes "<name> -14LUFS.mp4" next to it
 *   node qc-video.mjs <file.mp4> --fix --in-place
 *
 * Checks: 1920×1080 · 30 fps · H.264 yuv420p · AAC 48 kHz · −14 ±0.5 LUFS integrated · true peak ≤ −1 dBTP ·
 * length. --fix only touches the audio: two-pass loudnorm (linear) to −14 LUFS / −1.5 dBTP, the picture
 * stream-copied untouched — the fix V1's −11.1 LUFS delivery needed. Works on any delivery, not only on
 * render-edit.mjs output (which calls qc() itself). Exit code 1 on any FAIL.
 * Needs: ffmpeg 7+.
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync, spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';

export const SPEC = { w: 1920, h: 1080, fps: 30, lufs: -14, lufsTol: 0.5, tp: -1, master: [60, 120], cut: [20, 30] };

export function probe(file) {
  const j = JSON.parse(execFileSync('ffprobe', ['-v', 'error', '-show_entries',
    'stream=codec_type,codec_name,pix_fmt,width,height,r_frame_rate,sample_rate,channels:format=duration', '-of', 'json', file], { encoding: 'utf8' }));
  const v = j.streams.find(s => s.codec_type === 'video');
  const a = j.streams.find(s => s.codec_type === 'audio');
  const [n, d] = (v?.r_frame_rate || '0/1').split('/').map(Number);
  return { d: +j.format.duration, v, a, fps: d ? n / d : 0 };
}

/** Integrated loudness (LUFS), loudness range (LU) and true peak (dBTP) of a file's audio. */
export function loudness(file) {
  const r = spawnSync('ffmpeg', ['-hide_banner', '-nostats', '-i', file, '-map', '0:a:0', '-af', 'ebur128=peak=true:framelog=quiet', '-f', 'null', '-'], { encoding: 'utf8' });
  const s = r.stderr.split('Summary:').pop();
  const num = (re) => { const m = s.match(re); return m ? +m[1] : NaN; };
  return { I: num(/I:\s+(-?[\d.]+|-inf) LUFS/), LRA: num(/LRA:\s+(-?[\d.]+) LU/), TP: num(/Peak:\s+(-?[\d.]+|-inf) dBFS/) };
}

function check(file, cut) {
  const p = probe(file);
  const L = p.a ? loudness(file) : { I: NaN, TP: NaN };
  const [lo, hi] = cut ? SPEC.cut : SPEC.master;
  const rows = [
    ['Frame', `${p.v?.width}×${p.v?.height}`, p.v?.width === SPEC.w && p.v?.height === SPEC.h],
    ['Frame rate', `${+p.fps.toFixed(3)} fps`, Math.abs(p.fps - SPEC.fps) < 0.01],
    ['Video codec', `${p.v?.codec_name} ${p.v?.pix_fmt}`, p.v?.codec_name === 'h264' && p.v?.pix_fmt === 'yuv420p'],
    ['Audio codec', p.a ? `${p.a.codec_name} ${p.a.sample_rate / 1000} kHz` : 'none', p.a?.codec_name === 'aac' && +p.a?.sample_rate === 48000],
    ['Loudness', `${L.I} LUFS`, Math.abs(L.I - SPEC.lufs) <= SPEC.lufsTol, 'loud'],
    ['True peak', `${L.TP} dBTP`, L.TP <= SPEC.tp, 'loud'],
    ['Length', `${p.d.toFixed(1)} s (${lo}–${hi})`, p.d >= lo && p.d <= hi],
  ];
  return { rows, pass: rows.every(r => r[2]), loudOnly: rows.every(r => r[2] || r[3] === 'loud') };
}

/** Two-pass loudnorm to −14 LUFS / −1.5 dBTP; the video stream is copied, never re-encoded. */
export function fixLoudness(file, out) {
  const target = `I=${SPEC.lufs}:TP=-1.5:LRA=11`;
  const r = spawnSync('ffmpeg', ['-hide_banner', '-nostats', '-i', file, '-map', '0:a:0', '-af', `loudnorm=${target}:print_format=json`, '-f', 'null', '-'], { encoding: 'utf8' });
  const m = JSON.parse(r.stderr.slice(r.stderr.lastIndexOf('{'), r.stderr.lastIndexOf('}') + 1));
  const tmp = out + '.tmp.mp4';
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', file, '-map', '0:v:0', '-map', '0:a:0', '-c:v', 'copy',
    '-af', `loudnorm=${target}:measured_I=${m.input_i}:measured_TP=${m.input_tp}:measured_LRA=${m.input_lra}:measured_thresh=${m.input_thresh}:offset=${m.target_offset}:linear=true,aresample=48000`,
    '-c:a', 'aac', '-b:a', '192k', '-ar', '48000', '-movflags', '+faststart', tmp], { stdio: 'inherit' });
  fs.renameSync(tmp, out);
  return out;
}

/** Check a file; with fix, repair the loudness (in place, or into "<name> -14LUFS.mp4") and check again. */
export function qc(file, { cut = false, fix = false, inPlace = false, log = console.log } = {}) {
  let res = check(file, cut);
  let target = file;
  if (!res.pass && fix && res.loudOnly) {
    target = inPlace ? file : file.replace(/\.mp4$/i, ' -14LUFS.mp4');
    if (!inPlace && fs.existsSync(target)) throw new Error(`${path.basename(target)} already exists — move it aside first`);
    log(`  loudness off → two-pass loudnorm${inPlace ? ' (in place)' : ` → ${path.basename(target)}`}, picture copied untouched`);
    fixLoudness(file, target);
    res = check(target, cut);
  }
  log(`QC ${path.basename(target)}: ${res.pass ? 'PASS' : 'FAIL'}`);
  for (const [k, v, ok] of res.rows) log(`  ${ok ? '✓' : '✗'} ${k.padEnd(12)} ${v}`);
  return { ...res, file: target };
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const args = process.argv.slice(2);
  const file = args.find(a => !a.startsWith('--'));
  if (!file || !fs.existsSync(file)) { console.error('usage: node qc-video.mjs <file.mp4> [--cut] [--fix [--in-place]]'); process.exit(2); }
  const r = qc(path.resolve(file), { cut: args.includes('--cut'), fix: args.includes('--fix'), inPlace: args.includes('--in-place') });
  process.exit(r.pass ? 0 : 1);
}
