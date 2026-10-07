/**
 * The shared audio library: a music bed and a few UI sounds (mouse clicks, typing), made once with ElevenLabs
 * Music and Sound Effects and reused by every video — and by other projects, so the files live outside this repo.
 *
 *   node audio-lib.mjs                              # list the assets in audio-lib.json and which exist
 *   node audio-lib.mjs --make all                   # make every missing asset
 *   node audio-lib.mjs --make music/calm-minimal-01 # one asset; --force makes it again (sounds: same seed; music: a new track)
 *
 * Definitions (prompt, length, seed, shape) and the mix levels: audio-lib.json, tracked. Output: the "library"
 * folder (default ~/Music/PureDevs-Audio-Library, or $PUREDEVS_AUDIO_LIB): music/<id>.mp3, sfx/<id>.wav (shaped:
 * a click trimmed to its onset and peak-normalised; a bed loudness-normalised), sfx/raw/<id>.mp3 (as generated),
 * library.json (prompt, model, seed, date, length, loudness, licence) and README.md. Music is checked for vocals by
 * speech-to-text. Licence: ElevenLabs self-serve paid plans allow online and offline commercial use (ads, YouTube,
 * social, product demos) except film, TV, radio and studio games; no attribution (Eleven Music terms, Oct 2026).
 * The API key: macOS Keychain (service "elevenlabs-api"), as voice-gen.mjs. Needs: Node 18+, ffmpeg 7+.
 */
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync, spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { api, stt } from './voice-gen.mjs';
import { loudness } from './qc-video.mjs';

const here = path.dirname(new URL(import.meta.url).pathname);
export const LIBCFG = JSON.parse(fs.readFileSync(path.join(here, 'audio-lib.json'), 'utf8'));
export const LIB = (process.env.PUREDEVS_AUDIO_LIB || LIBCFG.library).replace(/^~(?=\/)/, os.homedir());
const MANIFEST = path.join(LIB, 'library.json');
const log = (...a) => console.log(...a);
const r2 = (n) => Math.round(n * 100) / 100;

export const assetFile = (id) => path.join(LIB, LIBCFG.assets[id]?.kind === 'music' ? `${id}.mp3` : `${id}.wav`);
export function manifest() { return fs.existsSync(MANIFEST) ? JSON.parse(fs.readFileSync(MANIFEST, 'utf8')) : { assets: {} }; }

function peak(file) {
  const r = spawnSync('ffmpeg', ['-hide_banner', '-nostats', '-i', file, '-af', 'volumedetect', '-f', 'null', '-'], { encoding: 'utf8' });
  return +(r.stderr.match(/max_volume: (-?[\d.]+) dB/) || [, 0])[1];
}
const seconds = (f) => +execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', f], { encoding: 'utf8' });

async function makeMusic(id, a) {
  // the API takes no seed with a text prompt (only with a composition plan), so a re-make is a new track
  const body = { prompt: a.prompt, music_length_ms: a.length_s * 1000, force_instrumental: true, model_id: a.model_id };
  const r = await api('POST', '/v1/music', { json: body, query: { output_format: 'mp3_44100_128' } });
  const out = assetFile(id);
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, Buffer.from(await r.arrayBuffer()));
  const t = await stt(out);                                    // an instrumental should transcribe to (almost) nothing
  const sung = (t.words || []).filter(w => w.type === 'word');
  return { model_id: body.model_id, seed: null, vocals_check: sung.length <= 3 ? 'none heard' : `${sung.length} words heard: "${sung.slice(0, 12).map(w => w.text).join(' ')}"` };
}

async function makeSfx(id, a) {
  const body = { text: a.prompt, duration_seconds: a.duration_s, prompt_influence: a.prompt_influence, ...(a.loop ? { loop: true } : {}) };
  if (a.seed !== undefined) body.seed = a.seed;
  let r;
  try { r = await api('POST', '/v1/sound-generation', { json: body, query: { output_format: 'mp3_44100_128' } }); }
  catch (e) { if (e.status !== 422 || body.seed === undefined) throw e; delete body.seed; r = await api('POST', '/v1/sound-generation', { json: body, query: { output_format: 'mp3_44100_128' } }); }
  const raw = path.join(LIB, 'sfx', 'raw', `${path.basename(id)}.mp3`);
  fs.mkdirSync(path.dirname(raw), { recursive: true });
  fs.writeFileSync(raw, Buffer.from(await r.arrayBuffer()));
  const out = assetFile(id);
  const tmp = path.join(os.tmpdir(), `sfx-${process.pid}.wav`);
  if (a.shape === 'click') {
    // from the first sound, 0.15 s, short fade, peak −1 dBFS
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', raw, '-af', 'silenceremove=start_periods=1:start_threshold=-45dB:start_silence=0.005,atrim=0:0.15,afade=t=out:st=0.1:d=0.05',
      '-ar', '48000', '-ac', '1', '-c:a', 'pcm_s16le', tmp]);
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', tmp, '-af', `volume=${r2(-1 - peak(tmp))}dB`, '-c:a', 'pcm_s16le', out]);
  } else {
    // a bed: −20 LUFS, the level the mix starts from
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', raw, '-ar', '48000', '-ac', '1', '-c:a', 'pcm_s16le', tmp]);
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', tmp, '-af', `volume=${r2(-20 - loudness(tmp).I)}dB,alimiter=limit=0.89:level=false`, '-c:a', 'pcm_s16le', out]);
  }
  fs.rmSync(tmp, { force: true });
  return { model_id: 'eleven_text_to_sound_v2', seed: body.seed ?? null, raw: path.relative(LIB, raw) };
}

function readme(m) {
  const rows = Object.entries(m.assets).map(([id, x]) => `| \`${x.file}\` | ${x.kind} | ${x.seconds} s | ${x.lufs} LUFS · peak ${x.peak_db} dB | ${x.created.slice(0, 10)} | ${x.prompt} |`);
  return `# PureDevs audio library

Shared music and UI sounds for PureDevs videos (any project). Made with ElevenLabs Music and Sound Effects by
\`audio-lib.mjs\` in the \`ch\` repo (\`project_asset/marketing/tooling/\`); the definitions live in its
\`audio-lib.json\`, the full record in \`library.json\` here. Don't edit files in place: change the definition and
re-make, so every file can be traced to a prompt and seed.

**Licence:** ${m.licence}

| File | Kind | Length | Level | Made | Prompt |
|---|---|---|---|---|---|
${rows.join('\n')}

- \`music/\` — beds, instrumental, long enough for a 2-minute video without looping. Mix them about 17 dB under the
  voice and duck them under speech (\`render-edit.mjs\` does both).
- \`sfx/\` — UI sounds, shaped: clicks start on the click and peak at −1 dBFS; beds (typing) sit at −20 LUFS.
  \`sfx/raw/\` keeps each sound as generated.
`;
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const args = process.argv.slice(2);
  const want = args.includes('--make') ? args[args.indexOf('--make') + 1] : null;
  const force = args.includes('--force');
  const m = manifest();
  m.licence = 'ElevenLabs self-serve paid plan: online and offline commercial use allowed (ads, YouTube, social media, product demos), except film, TV, radio and studio games; no attribution required (Eleven Music model-specific terms, checked 7 Oct 2026). Generated on PureDevs\' ElevenLabs account.';
  const ids = Object.keys(LIBCFG.assets);
  log(`Library: ${LIB}`);
  if (!want) { for (const id of ids) log(`  ${fs.existsSync(assetFile(id)) ? '✓' : '·'} ${id}`); process.exit(0); }
  const todo = want === 'all' ? ids : want.split(',');
  for (const id of todo) {
    const a = LIBCFG.assets[id];
    if (!a) { console.error(`No asset ${id} in audio-lib.json`); process.exit(1); }
    if (fs.existsSync(assetFile(id)) && !force) { log(`  ${id}: exists (--force to make again)`); continue; }
    const t0 = Date.now();
    const extra = a.kind === 'music' ? await makeMusic(id, a) : await makeSfx(id, a);
    const f = assetFile(id);
    const L = loudness(f);
    m.assets[id] = { file: path.relative(LIB, f), kind: a.kind, prompt: a.prompt, ...extra, created: new Date().toISOString(),
      seconds: r2(seconds(f)), lufs: L.I, peak_db: r2(peak(f)) };
    log(`✓ ${id}  ${m.assets[id].seconds} s  ${L.I} LUFS  (${((Date.now() - t0) / 1000).toFixed(0)} s)${extra.vocals_check ? `  vocals: ${extra.vocals_check}` : ''}`);
    fs.mkdirSync(LIB, { recursive: true });
    fs.writeFileSync(MANIFEST, JSON.stringify(m, null, 2) + '\n');
  }
  fs.writeFileSync(path.join(LIB, 'README.md'), readme(m));
}
