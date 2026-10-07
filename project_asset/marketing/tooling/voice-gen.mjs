/**
 * Voice generator for the demo videos: turns a video's voice script (recordings/voice-over-elevenlabs.md)
 * into the narration through the ElevenLabs API — Yeasir's own-voice clone, the locked choices in voice.json.
 *
 *   node voice-gen.mjs --setup [--voice "Name"]   # find the clone + the v4 model, fill voice.json; makes no audio
 *   node voice-gen.mjs --video v8 --dry           # the chunks and the credits a run would spend; no API call
 *   node voice-gen.mjs --video v8                 # every chunk: a take → transcribed → audio/<chunk>.mp3
 *   node voice-gen.mjs --video v8 --only b3a,b4   # some chunks
 *   node voice-gen.mjs --video v8 --redo b3a      # a new take for a chunk (new seed; the old takes are kept)
 *   node voice-gen.mjs --video v8 --fix b2b       # apply the script's pronunciation fallbacks to b2b, then new takes
 *   node voice-gen.mjs --video v8 --pick b3a=t2   # use another stored take (no API call)
 *   node voice-gen.mjs --video v8 --process [--out dir]   # one loudness chain over every pick →
 *                                                 # recordings/voice/<VIDEO>_<chunk>.wav (48 kHz mono 16-bit)
 *
 * Chunks are the script's "## V8_b1 — …" headings, each followed by a code block (the text sent, tags and
 * ellipses included); files use the short name (b1). Pronunciation fallbacks come from the script's
 * "Pronunciation fallbacks" table (Term | Chunk | Replace with `…`) and, once applied with --fix, stay applied.
 *
 * Takes: audio/takes/<chunk>-tN.mp3, each with a fixed seed (any take can be made again) and joined to the
 * previous chunk's pick (request stitching, so the read flows across chunks). Every take is transcribed
 * (speech-to-text) and compared word for word with the script — tags and ellipses ignored, numbers and
 * letter-by-letter names normalised. voice.json "takes" (1) are made per chunk; only a take that fails the
 * check — below min_accuracy, or a clipped last word — gets up to "retries" (2) more. The best take wins
 * (highest word accuracy; tie → the length closest to the median); anything still off, or a sound event, is
 * flagged in the run summary. A --pick is pinned until that chunk's --redo. All of it is in audio/takes.json.
 *
 * The API key never touches a file: macOS Keychain (service "elevenlabs-api") or ELEVENLABS_API_KEY.
 * Audio is gitignored. Needs: Node 18+, ffmpeg 7+.
 */
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { loudness } from './qc-video.mjs';

const here = path.dirname(new URL(import.meta.url).pathname);
const ROOT = path.resolve(here, '../../..');
const CFG = path.join(here, 'voice.json');
const API = 'https://api.elevenlabs.io';
const args = process.argv.slice(2);
const opt = (n, d = null) => { const i = args.indexOf(n); return i >= 0 ? args[i + 1] : d; };
const list = (n) => (opt(n) ? opt(n).split(',').map(s => s.trim()).filter(Boolean) : []);
const VIDEO = opt('--video', 'v8');
const vdir = path.join(ROOT, 'project_asset/content/videos', VIDEO);
const REC = path.join(vdir, 'recordings');
const AUD = path.join(vdir, 'audio');
const TAKES = path.join(AUD, 'takes');
const STATE = path.join(AUD, 'takes.json');
const cfg = JSON.parse(fs.readFileSync(CFG, 'utf8'));
const log = (...a) => console.log(...a);
const die = (m) => { console.error(m); process.exit(1); };
const r3 = (n) => Math.round(n * 1000) / 1000;
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const STITCH_MS = 2 * 3600e3 - 10 * 60e3;          // a request id can be stitched to for about two hours

// ── API ─────────────────────────────────────────────────────────────────────────────────────────
let KEY = null;
function apiKey() {
  if (KEY) return KEY;
  if (process.env.ELEVENLABS_API_KEY) return (KEY = process.env.ELEVENLABS_API_KEY.trim());
  try {
    KEY = execFileSync('security', ['find-generic-password', '-s', 'elevenlabs-api', '-w'], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
  } catch {
    die('No ElevenLabs API key. Store it once (the command asks for it, so it stays out of shell history):\n' +
        '  security add-generic-password -a "$USER" -s elevenlabs-api -w');
  }
  return KEY;
}

export async function api(method, p, { json, form, query } = {}) {
  const url = new URL(API + p);
  for (const [k, v] of Object.entries(query || {})) url.searchParams.set(k, v);
  const headers = { 'xi-api-key': apiKey() };
  let body;
  if (json) { headers['content-type'] = 'application/json'; body = JSON.stringify(json); }
  if (form) body = form;
  for (let attempt = 1; ; attempt++) {
    const r = await fetch(url, { method, headers, body });
    if (r.ok) return r;
    const text = await r.text();
    if ((r.status === 429 || r.status >= 500) && attempt < 4) { await sleep(3000 * attempt); continue; }
    const e = new Error(`${method} ${p} → ${r.status}: ${text.slice(0, 500)}`);
    e.status = r.status; e.body = text;
    throw e;
  }
}

// ── the voice script ────────────────────────────────────────────────────────────────────────────
export function readScript() {
  const f = path.join(REC, 'voice-over-elevenlabs.md');
  if (!fs.existsSync(f)) die(`No voice script: ${path.relative(ROOT, f)}`);
  const md = fs.readFileSync(f, 'utf8');
  const chunks = [...md.matchAll(/^## (V\d+_[a-z0-9]+)\b[^\n]*\n+```[^\n]*\n([\s\S]*?)\n```/gim)]
    .map(m => ({ id: m[1], c: m[1].replace(/^V\d+_/i, ''), text: m[2].trim() }));
  if (!chunks.length) die(`No "## ${VIDEO.toUpperCase()}_b1 — …" chunks with a code block in ${path.basename(f)}`);
  const fixes = [];
  const sec = md.split(/^## Pronunciation[^\n]*\n/m)[1];
  if (sec) {
    for (const line of sec.split(/^## /m)[0].split('\n')) {
      const cell = line.split('|').map(s => s.trim());
      if (cell.length < 5) continue;
      const rep = cell[3].match(/`([^`]+)`/);
      if (!rep || cell[1] === 'Term') continue;
      for (const c of cell[2].split(/[,\s]+/).filter(x => /^b\d/i.test(x))) fixes.push({ term: cell[1], c, with: rep[1] });
    }
  }
  return { chunks, fixes };
}
const applyFixes = (text, fixes) => fixes.reduce((t, f) => t.split(f.term).join(f.with), text);
const plain = (t) => t.replace(/\[[^\]]*\]\s*/g, '').replace(/\s+/g, ' ').trim();

// ── word check ──────────────────────────────────────────────────────────────────────────────────
const ONES = 'zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen'.split(' ');
const TENS = ' ten twenty thirty forty fifty sixty seventy eighty ninety'.split(' ');
const say = (n) => n < 20 ? ONES[n] : n < 100 ? TENS[Math.floor(n / 10)] + (n % 10 ? ' ' + ONES[n % 10] : '')
  : ONES[Math.floor(n / 100)] + ' hundred' + (n % 100 ? ' ' + say(n % 100) : '');
export function words(s) {
  const w = s.replace(/\[[^\]]*\]/g, ' ').toLowerCase().replace(/[’']/g, '').replace(/(\d+)\s*%/g, '$1 percent')
    .replace(/[^a-z0-9]+/g, ' ').trim().split(' ').filter(Boolean)
    .flatMap(x => /^\d+$/.test(x) && +x < 1000 ? say(+x).split(' ') : [x]);
  const out = [];                                                    // "w p admin" → "wp admin"
  for (let i = 0; i < w.length; i++) {
    let j = i; while (j < w.length && w[j].length === 1) j++;
    if (j - i >= 2) { out.push(w.slice(i, j).join('')); i = j - 1; } else out.push(w[i]);
  }
  return out;
}
function lev(a, b) {
  const d = Array.from({ length: a.length + 1 }, (_, i) => [i]);
  for (let j = 1; j <= b.length; j++) d[0][j] = j;
  for (let i = 1; i <= a.length; i++) for (let j = 1; j <= b.length; j++)
    d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
  return d[a.length][b.length];
}
const same = (a, b) => a === b || (Math.min(a.length, b.length) >= 4 && 1 - lev(a, b) / Math.max(a.length, b.length) >= 0.75);
/** Word accuracy of a transcript against the script, and the differences. Joined/split words ("set up") are free. */
export function compare(refText, hypText) {
  const R = words(refText), H = words(hypText);
  const D = Array.from({ length: R.length + 1 }, () => new Array(H.length + 1).fill(Infinity));
  const B = Array.from({ length: R.length + 1 }, () => new Array(H.length + 1).fill(null));
  D[0][0] = 0;
  for (let i = 0; i <= R.length; i++) for (let j = 0; j <= H.length; j++) {
    const v = D[i][j]; if (v === Infinity) continue;
    const go = (ii, jj, cost, op) => { if (ii <= R.length && jj <= H.length && v + cost < D[ii][jj]) { D[ii][jj] = v + cost; B[ii][jj] = [i, j, op]; } };
    if (i < R.length && j < H.length) go(i + 1, j + 1, same(R[i], H[j]) ? 0 : 1, 'sub');
    if (i < R.length) go(i + 1, j, 1, 'missing');
    if (j < H.length) go(i, j + 1, 1, 'extra');
    if (i < R.length && j + 1 < H.length && same(R[i], H[j] + H[j + 1])) go(i + 1, j + 2, 0, 'join');
    if (i + 1 < R.length && j < H.length && same(R[i] + R[i + 1], H[j])) go(i + 2, j + 1, 0, 'join');
  }
  const diff = [];
  for (let i = R.length, j = H.length; i || j;) {
    const [pi, pj, op] = B[i][j];
    if (op === 'sub' && !same(R[pi], H[pj])) diff.unshift(`"${R[pi]}" → "${H[pj]}"`);
    if (op === 'missing') diff.unshift(`missing "${R[pi]}"`);
    if (op === 'extra') diff.unshift(`extra "${H[pj]}"`);
    i = pi; j = pj;
  }
  return { accuracy: r3(Math.max(0, 1 - D[R.length][H.length] / R.length)), diff };
}

// ── takes ───────────────────────────────────────────────────────────────────────────────────────
function fnv(s) { let h = 0x811c9dc5; for (const ch of s) { h ^= ch.codePointAt(0); h = Math.imul(h, 0x01000193) >>> 0; } return h; }
const seconds = (f) => +execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', f], { encoding: 'utf8' });
const loadState = () => fs.existsSync(STATE) ? JSON.parse(fs.readFileSync(STATE, 'utf8')) : { video: VIDEO, chunks: {} };
const saveState = (s) => fs.writeFileSync(STATE, JSON.stringify(s, null, 2) + '\n');

let FORMAT = cfg.output_format;
async function tts(text, seed, ctx) {
  const vs = Object.fromEntries(Object.entries(cfg.voice_settings || {}).filter(([, v]) => v !== null));
  const body = { text, model_id: cfg.tts_model_id, seed, ...(Object.keys(vs).length ? { voice_settings: vs } : {}) };
  if (ctx.prevIds?.length) body.previous_request_ids = ctx.prevIds; else if (ctx.prevText) body.previous_text = ctx.prevText;
  if (ctx.nextText) body.next_text = ctx.nextText;
  try {
    const r = await api('POST', `/v1/text-to-speech/${cfg.voice_id}`, { json: body, query: { output_format: FORMAT } });
    return { buf: Buffer.from(await r.arrayBuffer()), requestId: r.headers.get('request-id'), cost: +r.headers.get('character-cost') || null, format: FORMAT };
  } catch (e) {
    if (FORMAT !== 'mp3_44100_128' && [400, 401, 403, 422].includes(e.status) && /format|tier|subscription/i.test(e.body || '')) {
      log(`  ${FORMAT} isn't available on this plan → mp3_44100_128 from here on`);
      FORMAT = 'mp3_44100_128';
      return tts(text, seed, ctx);
    }
    throw e;
  }
}

let KEYTERMS = true;
export async function stt(file) {
  const form = new FormData();
  form.append('model_id', cfg.stt_model_id);
  form.append('language_code', 'en');
  form.append('tag_audio_events', 'true');
  form.append('timestamps_granularity', 'word');
  if (KEYTERMS) for (const k of cfg.keyterms || []) form.append('keyterms', k);
  form.append('file', new Blob([fs.readFileSync(file)], { type: 'audio/mpeg' }), path.basename(file));
  try {
    return await (await api('POST', '/v1/speech-to-text', { form })).json();
  } catch (e) {
    if (KEYTERMS && e.status === 422 && /keyterm/i.test(e.body || '')) { KEYTERMS = false; return stt(file); }
    throw e;
  }
}

function judge(take, refText) {
  const w = (take.stt.words || []);
  const spoken = w.filter(x => x.type === 'word');
  const { accuracy, diff } = compare(refText, spoken.map(x => x.text).join(' '));
  const flags = [];
  if (accuracy < cfg.min_accuracy) flags.push(`words ${Math.round(accuracy * 1000) / 10} %: ${diff.join(', ')}`);
  const last = spoken[spoken.length - 1];
  if (last && take.seconds - last.end < 0.03) flags.push('last word may be clipped');
  const events = w.filter(x => x.type === 'audio_event').map(x => x.text);
  if (events.length) flags.push(`sound: ${events.join(' ')}`);
  return { accuracy, diff, flags, words: spoken.map(x => [x.text, r3(x.start), r3(x.end)]) };
}

function autoPick(st) {
  const cur = Object.entries(st.takes).filter(([, t]) => t.sent === st.sent && t.accuracy !== undefined && !t.rejected);
  if (!cur.length) return null;
  const best = Math.max(...cur.map(([, t]) => t.accuracy));
  const top = cur.filter(([, t]) => t.accuracy >= best - 1e-9);
  const lens = cur.map(([, t]) => t.seconds).sort((a, b) => a - b);
  const median = lens[Math.floor(lens.length / 2)];
  top.sort((a, b) => Math.abs(a[1].seconds - median) - Math.abs(b[1].seconds - median));
  return top[0][0];
}

function usePick(st, c) {
  fs.copyFileSync(path.join(TAKES, `${c}-${st.picked}.mp3`), path.join(AUD, `${c}.mp3`));
}

// ── --setup ─────────────────────────────────────────────────────────────────────────────────────
async function setup() {
  const { voices } = await (await api('GET', '/v1/voices')).json();
  const want = opt('--voice');
  const hit = want ? voices.filter(v => v.voice_id === want || v.name.toLowerCase() === want.toLowerCase())
                   : voices.filter(v => ['cloned', 'professional'].includes(v.category));
  if (hit.length !== 1) {
    log('Voices on this account:');
    for (const v of voices) log(`  ${v.category.padEnd(12)} ${v.name}`);
    die(hit.length ? 'More than one clone — choose with --voice "<name>"' : 'No clone found — choose with --voice "<name>"');
  }
  const v = hit[0];
  const saved = await (await api('GET', `/v1/voices/${v.voice_id}/settings`)).json();
  let m;
  try {
    const models = await (await api('GET', '/v1/models')).json();
    m = models.find(x => x.model_id === cfg.tts_model_id) || models.find(x => /^eleven v4$/i.test(x.name));
    if (!m) die(`Model ${cfg.tts_model_id} not offered. Models: ${models.map(x => x.model_id).join(', ')}`);
  } catch (e) {
    if (e.status !== 401) throw e;                        // a key without "Models: read" — keep voice.json's model id
    m = { model_id: cfg.tts_model_id, name: `${cfg.tts_model_id} (not verified: the key can't read models)` };
  }
  cfg.voice_id = v.voice_id;
  cfg.voice_name = v.name;
  cfg.tts_model_id = m.model_id;
  cfg.voice_settings ||= {};
  for (const k of ['stability', 'similarity_boost']) if (cfg.voice_settings[k] == null && saved[k] != null) cfg.voice_settings[k] = saved[k];
  fs.writeFileSync(CFG, JSON.stringify(cfg, null, 2).replace(/\[\s+([^\[\]{}]*?)\s+\]/g, (_, s) => '[' + s.replace(/\s*\n\s*/g, ' ') + ']') + '\n');
  log(`Voice    ${v.name} (${v.category}, ${v.voice_id})`);
  log(`Model    ${m.name} (${m.model_id})`);
  log(`Settings stability ${cfg.voice_settings.stability} · similarity ${cfg.voice_settings.similarity_boost}  (the clone's saved settings: ${saved.stability} · ${saved.similarity_boost})`);
  try {
    const s = await (await api('GET', '/v1/user/subscription')).json();
    log(`Plan     ${s.tier} · ${s.character_count} of ${s.character_limit} credits used this period`);
  } catch { log('Plan     (this key may not read the subscription — fine)'); }
  log(`Wrote ${path.relative(ROOT, CFG)}. No audio was generated.`);
}

// ── --process: one loudness chain over all the picks, split back per chunk ─────────────────────
function processVoice() {
  const { chunks } = readScript();
  const P = cfg.processing;
  const out = opt('--out') ? path.resolve(opt('--out')) : path.join(REC, 'voice');
  fs.mkdirSync(out, { recursive: true });
  const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'voice-'));
  const parts = chunks.map(ch => {
    const src = path.join(AUD, `${ch.c}.mp3`);
    if (!fs.existsSync(src)) die(`Missing ${path.relative(vdir, src)} — run voice-gen first`);
    const w = path.join(tmp, `${ch.c}.wav`);
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', src, '-ar', '48000', '-ac', '1', '-c:a', 'pcm_s16le', w]);
    const n = +execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'stream=duration_ts', '-of', 'csv=p=0', w], { encoding: 'utf8' });
    return { ch, w, n };
  });
  const joined = path.join(tmp, 'joined.wav');
  let gain = P.gain_db, L;
  for (let i = 0; i < 4; i++) {
    const chain = `${parts.map((_, k) => `[${k}:a]`).join('')}concat=n=${parts.length}:v=0:a=1,${P.pre},volume=${gain}dB,${P.post}`;
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...parts.flatMap(p => ['-i', p.w]), '-filter_complex', chain, '-ar', '48000', '-c:a', 'pcm_s16le', joined]);
    L = loudness(joined);
    if (Math.abs(L.I - P.target_lufs) <= P.tolerance_lu) break;
    gain = Math.round((gain + P.target_lufs - L.I) * 100) / 100;
  }
  let s = 0;
  for (const p of parts) {
    const f = path.join(out, `${VIDEO.toUpperCase()}_${p.ch.c}.wav`);
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', joined, '-af', `atrim=start_sample=${s}:end_sample=${s + p.n},asetpts=PTS-STARTPTS`, '-c:a', 'pcm_s16le', f]);
    s += p.n;
  }
  const chain = `${P.pre},volume=${gain}dB,${P.post}`;
  fs.writeFileSync(path.join(out, 'PROCESSING.txt'), `${chain}\n\n` +
    `One chain over all ${parts.length} chunks joined in script order (48 kHz mono), then split back at the same samples.\n` +
    `Made by voice-gen.mjs --process on ${new Date().toISOString().slice(0, 16).replace('T', ' ')} UTC from audio/<chunk>.mp3: ` +
    `${L.I} LUFS integrated, true peak ${L.TP} dBTP.\n`);
  fs.rmSync(tmp, { recursive: true, force: true });
  log(`✓ ${parts.length} chunks → ${path.relative(ROOT, out)}/  ${L.I} LUFS, true peak ${L.TP} dBTP (gain ${gain} dB)`);
  if (Math.abs(L.I - P.target_lufs) > P.tolerance_lu) log(`  ⚠ ${L.I} LUFS is off the ${P.target_lufs} target — check the chain`);
}

// ── main ────────────────────────────────────────────────────────────────────────────────────────
async function main() {
  if (args.includes('--setup')) { await setup(); return; }
  if (args.includes('--process')) { processVoice(); return; }

  const { chunks, fixes } = readScript();
  const state = loadState();
  fs.mkdirSync(TAKES, { recursive: true });

  const PICK = list('--pick');
  if (PICK.length) {
    for (const p of PICK) {
      const [c, t] = p.split('=');
      const st = state.chunks[c];
      if (!st?.takes?.[t]) die(`No take ${t} for ${c}. Stored: ${Object.keys(st?.takes || {}).join(', ') || 'none'}`);
      if (st.takes[t].sent !== st.sent) log(`  note: ${c}-${t} was made from older text`);
      Object.assign(st, { picked: t, pinned: true });
      usePick(st, c);
      log(`✓ ${c} → ${t} (pinned)`);
    }
    saveState(state);
    return;
  }

  const ONLY = list('--only'), REDO = list('--redo'), FIX = list('--fix');
  for (const c of [...ONLY, ...REDO, ...FIX]) if (!chunks.some(ch => ch.c === c)) die(`No chunk ${c}. Chunks: ${chunks.map(ch => ch.c).join(', ')}`);
  for (const c of FIX) if (!fixes.some(f => f.c === c)) die(`The script's pronunciation table has no fallback for ${c}`);
  const N = +opt('--takes', cfg.takes);
  const DRY = args.includes('--dry');
  if (!DRY && !cfg.voice_id) die('voice.json has no voice yet — run: node voice-gen.mjs --setup');

  // what each chunk needs
  const plan = chunks.map((ch, i) => {
    const st = state.chunks[ch.c] || { takes: {} };
    const fixed = FIX.includes(ch.c) || !!st.fixed;
    const sent = fixed ? applyFixes(ch.text, fixes.filter(f => f.c === ch.c)) : ch.text;
    const current = Object.values(st.takes || {}).filter(t => t.sent === sent).length;
    const selected = (!ONLY.length || ONLY.includes(ch.c)) || REDO.includes(ch.c) || FIX.includes(ch.c);
    const need = !selected ? 0 : REDO.includes(ch.c) ? N : Math.max(0, N - current);
    return { ch, i, fixed, sent, need, selected };
  });
  const credits = plan.reduce((a, p) => a + p.need * p.sent.length, 0);
  log(`${VIDEO.toUpperCase()} · ${cfg.voice_name || '(voice not set up)'} · ${cfg.tts_model_id} · ${N} takes per chunk`);
  for (const p of plan) if (p.selected) log(`  ${p.ch.c.padEnd(5)} ${String(p.sent.length).padStart(4)} chars  ${p.need ? `${p.need} new take${p.need > 1 ? 's' : ''}` : 'takes exist'}${p.fixed ? '  (pronunciation fix)' : ''}`);
  log(`About ${credits} characters of text-to-speech${credits ? ' (credits: the character-cost of each take is logged in takes.json) plus speech-to-text' : ''}.`);
  if (DRY) return;

  for (const p of plan) {
    const { ch, i } = p;
    if (!p.selected) continue;
    const st = (state.chunks[ch.c] ||= { takes: {} });
    Object.assign(st, { id: ch.id, text: ch.text, sent: p.sent, fixed: p.fixed });
    if (REDO.includes(ch.c)) {                          // a redo is a verdict on what exists: only new takes compete
      st.pinned = false;
      for (const t of Object.values(st.takes)) t.rejected = true;
    }
    const prev = i > 0 ? state.chunks[chunks[i - 1].c] : null;
    const make = async (count) => {
      const prevTake = prev?.picked ? prev.takes[prev.picked] : null;
      const ctx = {
        prevIds: prevTake?.request_id && Date.now() - Date.parse(prevTake.created) < STITCH_MS ? [prevTake.request_id] : null,
        prevText: i > 0 ? plain(chunks[i - 1].text) : null,
        nextText: i < chunks.length - 1 ? plain(chunks[i + 1].text) : null,
      };
      const base = Math.max(0, ...Object.keys(st.takes).map(t => +t.slice(1)));
      const made = await Promise.all(Array.from({ length: count }, async (_, k) => {
        const t = `t${base + k + 1}`;
        const seed = fnv(`${VIDEO}:${ch.c}:${t}:${p.sent}`);
        const r = await tts(p.sent, seed, ctx);
        const file = path.join(TAKES, `${ch.c}-${t}.mp3`);
        fs.writeFileSync(file, r.buf);
        return [t, { sent: p.sent, seed, request_id: r.requestId, stitched: ctx.prevIds ? 'request' : ctx.prevText ? 'text' : null,
          format: r.format, cost: r.cost, created: new Date().toISOString(), seconds: r3(seconds(file)) }];
      }));
      for (const [t, take] of made) st.takes[t] = take;
      saveState(state);                                   // paid for: keep them even if the check below fails
    };
    const check = () => Promise.all(Object.entries(st.takes).filter(([, t]) => t.sent === p.sent && t.accuracy === undefined).map(async ([t, take]) => {
      take.stt = await stt(path.join(TAKES, `${ch.c}-${t}.mp3`));
      Object.assign(take, judge(take, ch.text));
      delete take.stt;
    }));
    const failed = (t) => t.accuracy < cfg.min_accuracy || t.flags.includes('last word may be clipped');
    if (p.need) await make(p.need);
    await check();
    if (!(st.pinned && st.takes[st.picked]?.sent === p.sent)) {
      st.picked = autoPick(st); st.pinned = false;
      for (let r = 0; r < (cfg.retries ?? 0) && failed(st.takes[st.picked]); r++) {
        log(`  ${ch.c}: ${st.takes[st.picked].flags.join(' · ')} → another take`);
        await make(1); await check();
        st.picked = autoPick(st);
      }
    }
    usePick(st, ch.c);
    saveState(state);
    const t = st.takes[st.picked];
    log(`✓ ${ch.c.padEnd(5)} ${st.picked.padEnd(4)} ${(Math.round(t.accuracy * 1000) / 10 + ' %').padStart(7)}  ${t.seconds.toFixed(2).padStart(6)} s` +
        `${st.pinned ? '  (pinned)' : ''}${t.flags.length ? '  ⚠ ' + t.flags.join(' · ') : ''}`);
  }
  const flagged = chunks.filter(ch => state.chunks[ch.c]?.picked && state.chunks[ch.c].takes[state.chunks[ch.c].picked].flags.length).map(ch => ch.c);
  log(flagged.length ? `Listen to: ${flagged.join(', ')} — other takes: --pick <chunk>=tN · new takes: --redo <chunk>` : 'Every pick reads the script word for word.');
  log(`Next: node voice-gen.mjs --video ${VIDEO} --process`);
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) await main();
