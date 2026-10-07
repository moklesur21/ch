# Demo video pipeline — the complete guide

**Version 1.0 · 7 Oct 2026** · proven end to end on V8 ("Where does my store data go when I install this?").

How a PureDevs demo video is made from now on, from script to finished file, for this plugin or any
new product. Everything is made locally, on one Mac. Yeasir writes nothing by hand after the script is
approved. He watches the finished video and sends notes. The only things that leave the machine are
the voice script's text (to ElevenLabs, to make the voice) and the finished files (to YouTube, by the VA).

- **The voice:** Yeasir's own ElevenLabs voice clone, generated through the API.
- **The edit:** rendered with ffmpeg from an edit list: speed-ups, zooms, labels, captions and the end card.
- **The sound:** a calm music bed plus mouse clicks and typing, from a shared library.
- **The check:** every file is measured against the spec before anyone watches it.

`docs/video-production.md` is the PureDevs runbook: who decides what, the gates, the status of each
video. This guide is the how-to behind its steps 4–6, written so it can be reused for another product.

---

## 1. The pipeline at a glance

```
 1 Research ─► 2 Brief + script ─► 3 Demo data ─► 4 Recording ─► 5 Voice ─► 6 Edit + sound ─► 7 QC ─► 8 Review ─► 9 Kit + publish
   (Yeasir)      (Claude)            (Claude)       (Claude)        (Claude)     (Claude)           (auto)    (Yeasir)     (Claude, VA)
                 GATE A: Yeasir                     GATE B:                                                 GATE D: Yeasir  GATE C: audit
                 approves script                    first scene                                             watches the     PASS before
                                                    checked                                                 video, notes    publishing
```

| Step | Tool | Input | Output |
|---|---|---|---|
| 4 Recording | `record-scenes.mjs` | `scenes.json`, the local demo site | `recordings/scene-NN.mp4` (4K, silent), `scene-NN.steps.json` (every click's time), stills, delivery note |
| 4 End card | `make-end-card.mjs` | `scenes.json` `endCard` block | `recordings/end-card.png` + `end-card.mp4` |
| 5 Voice | `voice-gen.mjs` | `recordings/voice-over-elevenlabs.md`, `voice.json` | `audio/<chunk>.mp3`, `audio/takes.json` |
| 5 Loudness | `voice-gen.mjs --process` | `audio/<chunk>.mp3` | `recordings/voice/VN_<chunk>.wav` at −14 LUFS |
| 6 Fit | `conform-voice.py` (per video) + `voice_phrases.py` | voice takes, step logs | `edit.json` timings, `recordings/captions.srt` |
| 6 Render | `render-edit.mjs` | `edit.json`, clips, voice, captions, audio library | `recordings/edit/VN_reference_master.mp4`, `VN_cut_NNs.mp4` |
| 6 Sound | `audio-lib.mjs` (once, not per video) | `audio-lib.json` | `~/Music/PureDevs-Audio-Library/` |
| 7 QC | `qc-video.mjs` (runs inside render-edit too) | any .mp4 | PASS / FAIL per spec; loudness fixed in place |

All tools live in `project_asset/marketing/tooling/`. Every video lives in `project_asset/content/videos/vN/`.

---

## 2. One-time setup

### 2.1 Software on the Mac

| Need | Check | Install |
|---|---|---|
| Node 18 or newer (23 in use) | `node --version` | `brew install node` |
| ffmpeg 7 or newer with libass, rubberband, loudnorm | `ffmpeg -hide_banner -filters \| grep -E 'loudnorm\|subtitles\|rubberband'` | `brew install ffmpeg` |
| Python 3 | `python3 --version` | comes with macOS / `brew install python` |
| Google Chrome | — | the normal installer (the tools drive it headless; no browser download) |
| playwright-core | — | `cd project_asset/marketing/tooling && npm install` |
| A local WordPress demo site | `http://localhost/<site>` loads | MAMP; see `docs/storefront-setup.md` for the Northline store |

### 2.2 ElevenLabs

- **Plan:** a paid plan (Starter or higher). A paid plan gives the voice clone, API access and the
  commercial licence for music and sound effects. The current plan has no 192 kbps audio. The tools
  fall back to 128 kbps automatically, the same quality as the web-UI downloads.
- **Voice:** "Yeasir Voice Quick", id `WtNDtptM8xXyadpKyK8M`, an Instant Voice Clone of Yeasir's own voice.
- **API key permissions:**

| Permission | Used for |
|---|---|
| Text to Speech | the voice |
| Speech to Text | checking every take word by word, phrase timing |
| Voices: read | `voice-gen.mjs --setup` finds the clone and its settings |
| Music | the music bed (`audio-lib.mjs`) |
| Sound Effects | clicks, typing (`audio-lib.mjs`) |
| Models: read | optional — setup checks the model id with it, and works without it |

Set a monthly credit cap on the key.

- **Store the key once, in the macOS Keychain.** The command asks for the key, so it never lands in a
  file or in shell history. The tools read it at run time; Claude never sees it.

```bash
security add-generic-password -a "$USER" -s elevenlabs-api -w
```

To replace a key, delete the old entry first: `security delete-generic-password -s elevenlabs-api`.
For a one-off run, `ELEVENLABS_API_KEY` in the environment overrides the Keychain.

- **Lock the voice:**

```bash
cd project_asset/marketing/tooling
node voice-gen.mjs --setup --voice WtNDtptM8xXyadpKyK8M
```

This fills `voice.json` and prints the voice, model and settings. It generates no audio.

### 2.3 The locked voice choices — `voice.json`

| Field | Value | Why |
|---|---|---|
| `voice_id` / `voice_name` | `WtNDtptM8xXyadpKyK8M` / Yeasir Voice Quick | Yeasir's own voice |
| `tts_model_id` | `eleven_v4` | Eleven v4, not Turbo. It supports request stitching, so consecutive chunks flow |
| `voice_settings` | stability 0.5, similarity 0.75 | Stability is the owner's choice; Similarity is the clone's default. v4 has only these two |
| `stt_model_id` | `scribe_v2` | transcribes each take for the word check |
| `output_format` | `mp3_44100_128` | the best the plan allows |
| `takes` / `retries` | 1 / 2 | one take per chunk (owner decision). Up to 2 more only when a take fails the word check |
| `min_accuracy` | 0.98 | in practice, every word must be there |
| `keyterms` | product and tech names | help the transcriber spell names, so the check doesn't flag them falsely. **Add a new product's names here** |
| `processing` | gate, compressor, gain, limiter; −14 LUFS target | one loudness chain over the whole voice |

Change a setting only to fix a problem, never in the middle of a video.

### 2.4 The shared audio library — once per Mac, not per video

The music bed and UI sounds live **outside the repo**, at `~/Music/PureDevs-Audio-Library/`, so every
project can use them. They are defined in `marketing/tooling/audio-lib.json` and made by `audio-lib.mjs`.

```bash
node audio-lib.mjs                 # what exists
node audio-lib.mjs --make all      # make whatever is missing (a new Mac, or a new asset in audio-lib.json)
```

| Asset | What it is |
|---|---|
| `music/calm-minimal-01.mp3` | 2:30 instrumental bed, Eleven Music `music_v2_5`: soft electric piano and pads, slow, no drums, no vocals. Checked by speech-to-text for vocals: none |
| `music/alt/calm-minimal-01-music_v1.mp3` | an earlier take on the older model, kept as an alternative |
| `sfx/click-01…03.wav` | three soft mouse clicks, used in turn so repeated clicks don't sound mechanical |
| `sfx/typing-01.wav` | 6 s of soft laptop typing, loopable |
| `library.json`, `README.md` | each file's prompt, model, seed, date, length, loudness, and the licence |

On a new Mac, copy the folder over (Dropbox, Drive or a disk) rather than regenerating it: music made
from a prompt can't be made again identically. Or point `PUREDEVS_AUDIO_LIB` at a shared copy.

**Licence:** ElevenLabs self-serve paid plans allow online and offline commercial use: ads, YouTube,
social media, product demos. The exclusions are film, TV, radio and studio games. No attribution is
required. This was checked against the Eleven Music terms on 7 Oct 2026; re-check if the plan changes.

---

## 3. One video's folder

```
project_asset/content/videos/vN/
  research/                          step 1 — research output, as saved
  PureDevs_VN_Demo_Brief_vX_Y.md     step 2
  PureDevs_VN_Script_vX_Y.md         step 2 — gate A
  PureDevs_VN_Recording_Brief_vX_Y.md step 4
  scenes.json                        step 4 — what the recorder plays
  edit.json                          step 6 — what the renderer plays (segments, voice, sound, cut)
  conform-voice.py                   step 6 — only when phrases must land on clicks (V8 has one)
  audio/                             step 5 — takes/<chunk>-tN.mp3, takes.json, <chunk>.mp3 (the pick)
  recordings/
    scene-NN.mp4 + scene-NN.steps.json   step 4 — clips (4K, silent) and the exact time of every step
    answer-frame.png, …-crop.png         step 4 — stills (the poster and thumbnail come from these)
    delivery-note.md                     step 4 — what the screen showed (never amounts)
    voice-over.md                        step 4 — the CAPTION script (correct spelling)
    voice-over-elevenlabs.md             step 4 — the VOICE script (what ElevenLabs reads)
    captions.srt                         step 4 draft → step 6 re-timed to the voice
    end-card.png / .mp4                  step 4
    voice/VN_<chunk>.wav                 step 5 — the voice after the loudness chain
    edit/                                step 6 — segments, VN_reference_master.mp4, VN_cut_NNs.mp4
  PureDevs_VN_Placement_Kit_vX_Y.md  step 9
```

Video and audio files are gitignored; JSON, Markdown and scripts are tracked.

---

## 4. Step by step

### Step 1 — Research (Yeasir)

Run the research prompt for the video (PureDevs: `strategy/PureDevs_Video_Docs/gemini.txt` plus the
video's field block) and save the output as-is into `vN/research/`. Then tell Claude which video is ready.

### Step 2 — Demo brief and script (Claude) → gate A

- Written from the product's strategy documents (PureDevs: Topic Filter v4.2, Prompt Pack v3, the
  Marketing Brief) and the installed product code. Every UI name and menu path is checked in the code.
- The script is written in beats (1 question → 2 where to click → 3 the proof → 4 the decision → 5 the
  CTA), 60–120 s in total. No spoken figures.
- **Gate A:** Yeasir approves the script. Nothing is recorded before that.

### Step 3 — Demo data (Claude)

- The video's prerequisites become checks in the demo-data verifier (PureDevs: `wp pdwchs-demo verify`).
  `scripts/demo-refresh.sh` must say record (0 FAIL).
- A video that needs a clean install (like V8) gets a throw-away site with a reset script
  (`scripts/v8-site.sh` is the pattern: create, snapshot, reset before every take, check, destroy).

### Step 4 — Recording (Claude) → gate B

1. **`scenes.json`:** one entry per scene. Each entry has a start URL, a ready check, and steps:
   - steps: `move`, `click`, `type`, `press`, `scroll`, `scroll-to`, `hold`, `goto`, `back`,
     `wait-for`, `timer-start` / `timer-stop`, `note`;
   - cursor rest points, and `"context": "visitor"` for a logged-out shopper;
   - top level: `base` (the site URL), `wpPath`, `user` (the throw-away admin's email), `ready`
     profiles, `stills`, `endCard`.

   V8's file is the most complete example.
2. **Probe, then record the answer-frame scene first (gate B), then the rest:**
   ```bash
   node record-scenes.mjs --video vN --probe
   node record-scenes.mjs --video vN --only scene-01
   node record-scenes.mjs --video vN
   ```
   - **Capture:** 1920 × 1080 CSS px at 2× (3840 × 2160 frames), 30 fps, with a drawn, highlighted cursor.
   - **Readiness audit:** the recorder refuses to record a page with a spinner, a notice, an error or
     empty data (`--force` overrides).
   - **Step log:** it writes `scene-NN.steps.json`, the exact time of every click and keystroke. The
     edit and the click sounds use it.
3. **End card:** `node make-end-card.mjs --video vN --seconds 5`. It renders the `endCard` block: the
   product name, one CTA, "Demo store".
4. **The two scripts and the captions:**
   - `voice-over.md`, the **caption script**: the approved words, correct spelling, emphasis words marked.
     Captions, the YouTube transcript and the question page come from it.
   - `voice-over-elevenlabs.md`, the **voice script**, by the ElevenLabs guide's rules
     (`strategy/PureDevs_Video_Docs/PureDevs_ElevenLabs_v4_Voice_Script_Guide_v1_1.md`):
     - the same words, word for word;
     - `[thoughtful]` at the start of every chunk;
     - `...` at every pause (v4 has no break tags);
     - at most one reveal pause (`... ` before the key words) per chunk, about three per video;
     - no exclamation marks, figures or URLs;
     - one chunk per beat, at most about 60 words per chunk.

     **Format the tools read:**

     ````markdown
     ## V13_b1 — the question

     ```
     [thoughtful] Which searches on your store... find nothing at all?
     ```
     ````

     Pronunciation fallbacks go in a table under `## Pronunciation fallbacks`, with the columns
     `| Term | Chunk | Replace with |` and the replacement in backticks. They are applied only with
     `--fix`.
   - `captions.srt`: a draft, with emphasis words in `<b>…</b>` (they render amber). Step 6 re-times it.

### Step 5 — Voice (Claude)

```bash
cd project_asset/marketing/tooling
node voice-gen.mjs --video vN --dry       # the chunks and the characters a run will send — no API call
node voice-gen.mjs --video vN             # one take per chunk → speech-to-text → word check → audio/<chunk>.mp3
node voice-gen.mjs --video vN --process   # one loudness chain over all chunks → recordings/voice/VN_<chunk>.wav
```

What a run does:
- **One take per chunk, in script order.** Each take is sent with the previous chunk's request id
  (request stitching), so the read sounds continuous. Each take gets a fixed seed, so it can be
  re-made exactly.
- **Every take is transcribed and compared with the script, word by word.** Tags and ellipses are
  ignored. Numbers and spelled-out names are normalised, so "90" matches "ninety" and "W P admin"
  matches "wp-admin".
- **Retries only for broken takes.** A take with a missing, extra or changed word, or a clipped last
  word, gets up to two more takes. A take is never retried for taste: tone is judged at gate D.
- **Logging:** everything goes to `audio/takes.json`: seed, request id, cost, length, accuracy,
  differences, word timings.
- **Processing:** `--process` joins all chunks, runs one gate → compressor → gain → limiter chain
  (gain trimmed until the whole voice measures −14 LUFS), and splits them back at the exact same
  samples.

V8 result: 9 chunks, all word-perfect on the first take, 1,325 characters sent (the API reported 145
credits), −14.2 LUFS.

### Step 6 — Edit and sound (Claude)

**`edit.json`** is the edit list. Times are **source seconds** in the recorder's clips; the step logs
give them. Zoom rectangles are CSS px of the 1920 × 1080 page.

```json
{
  "video": "v13",
  "size": [1920, 1080],
  "fps": 30,
  "labels": {
    "demo-store": { "text": "Demo store · sample data", "style": "pill", "at": "bottom-left" },
    "product":    { "text": "PureDevs Customer History", "style": "lower-third", "at": "lower-third" }
  },
  "segments": [
    { "id": "V13_b1", "chunks": ["V13_b1"], "still": "recordings/answer-frame.png", "duration": 5,
      "zoom": [[0, 170, 70, 1730]],
      "labels": [["product", 0, 4.9, "lower-third-right"], ["demo-store", 0, null]],
      "audio": [["recordings/voice/V13_b1.wav", 0.25]] },
    { "id": "V13_b2", "chunks": ["V13_b2"], "source": "scene-02",
      "ranges": [[0, 12.4, 1], [12.4, 20.0, 3]],
      "labels": [["demo-store", 0, null]],
      "audio": [["recordings/voice/V13_b2.wav", 0.2]] },
    { "id": "V13_b5", "chunks": ["V13_b5"], "still": "recordings/answer-frame.png", "duration": 7,
      "endCard": { "file": "recordings/end-card.mp4", "xfade": 0.5 },
      "audio": [["recordings/voice/V13_b5.wav", 0.3]] }
  ],
  "sound": {
    "music": "music/calm-minimal-01",
    "clicks": ["sfx/click-01", "sfx/click-02", "sfx/click-03"],
    "typing": "sfx/typing-01"
  },
  "cut": { "pieces": [["V13_b1", 0, 5.0], ["V13_b4", 0, null], ["V13_b5", -4.5, null]], "xfade": [0.3, 0.4] }
}
```

| Field | Meaning |
|---|---|
| `still` + `duration` | a still image as the picture (the answer frame) |
| `source` | a recorded clip (`scene-02`) |
| `ranges` | `[srcStart, srcEnd, speed]`. A speed over 1 shows an "N× speed" badge; `"badge": false` hides it |
| `zoom` | `[srcTime, x, y, width]` keyframes, smoothly eased; height follows 16:9 |
| `labels` | `[labelId, from, to or null, position?]`. Positions: `bottom-left`, `lower-third`, `lower-third-right`, `bottom-right-above-timer`, `top-right`. Styles: `pill`, `lower-third`, `badge` |
| `endCard` | appended with a crossfade |
| `audio` | `[file, startInSegment, inStart?, inEnd?]`: the voice, or a trimmed piece of it |
| `captionsTop` | `true` moves that segment's captions to the top, when the subject is at the bottom |
| `sfx: false` | no clicks or typing in this segment |
| `sound` | the shared library's music bed, clicks and typing. Leave it out for a silent edit |
| `cut` | the 20–30 s cut: `[segment, from, to]` in that segment's seconds. A negative `from` counts back from the segment's end. `xfade` sets the crossfade at each join |

**Fitting the picture to the voice:**
- **Simple video** (one chunk per segment, the voice just plays over it): set each segment's
  `duration` or `ranges` so it is a little longer than its chunk, then lay the chunk at about 0.2 s.
- **Phrases that must land on clicks** (V8's install: "Plugins," on the Plugins click, "We skip it."
  on the Skip click): write a `conform-voice.py` for the video. Copy V8's as the template.
  - It lists each chunk's `PHRASES` (the voice script's words, split where a piece or caption starts).
  - It gets their timings from `voice_phrases.phrase_spans()`: cuts land in the take's pauses, and the
    transcript's word times decide which pause is which boundary.
  - It reads click times from `scene-NN.steps.json`, sets the speed ranges so each phrase starts on its
    click, and writes `edit.json` and `captions.srt`.
  - It stops with a clear message if the phrases no longer match the voice script.

**Render:**

```bash
python3 ../../content/videos/vN/conform-voice.py      # only if the video has one
node render-edit.mjs --video vN --master --cut          # about 1 minute for a 2-minute video
```

| Output | What's in it |
|---|---|
| `recordings/edit/VN_b*.mp4` | finished 1080p segments: picture, labels, badge, end card, voice, clicks and typing |
| `recordings/edit/VN_reference_master.mp4` | all segments joined, captions burned in, music bed mixed under the voice |
| `recordings/edit/VN_cut_NNs.mp4` | the short cut, taken from the master |

**How the sound is mixed** (levels in `audio-lib.json` → `mix`):
- **The voice** sits at −14 LUFS.
- **The music** is set to −30 LUFS, then ducked by the voice (sidechain compression). It lands about
  23 dB under the voice while it speaks and about 17 dB under in the pauses. It fades in over 1.5 s and
  out over the last 3 s, under the end card.
- **Clicks** play exactly on the recorder's click times, peaking at −20 dBFS. **Typing** loops under
  each typed stretch at −36 LUFS. Both follow the speed ranges.
- **Nothing else:** no whooshes, pops or dings. They make a B2B demo sound cheap and fight the voice.

### Step 7 — QC (automatic)

`render-edit.mjs` checks both files with `qc-video.mjs` and prints PASS or FAIL per line. It can also be
run on any file, for example a delivery from an editor:

```bash
node qc-video.mjs path/to/file.mp4            # a master: 60–120 s
node qc-video.mjs path/to/cut.mp4 --cut       # the cut: 20–30 s
node qc-video.mjs path/to/file.mp4 --fix      # loudness off → "<name> -14LUFS.mp4", picture copied untouched
```

| Check | Pass |
|---|---|
| Frame | 1920 × 1080, 30 fps |
| Codecs | H.264 yuv420p video, AAC 48 kHz audio |
| Loudness | −14 ±0.5 LUFS integrated |
| True peak | ≤ −1 dBTP |
| Length | master 60–120 s; cut 20–30 s |

Then Claude spot-checks frames at caption times (captions on the right screen, clear of labels, clock
and badge) before sending the files.

### Step 8 — Review (Yeasir) → gate D

Yeasir gets the master and the cut, watches them and writes notes in plain words. Each kind of note
has one fix and a re-render of a few minutes:

| Note | Fix |
|---|---|
| "b3a sounds flat / rushed / odd" | `node voice-gen.mjs --video vN --redo b3a` (a new take; old ones are kept, and `--pick b3a=t1` brings one back) → `--process` → conform → render |
| "it says Freemius wrong" | add the term to the voice script's pronunciation table → `--fix b2b` → `--process` → conform → render |
| "the voice doesn't sound like me / uneven" | `voice.json` Stability / Similarity, then every chunk again (`--redo` all) |
| "change this sentence" | back to the script (gate A): caption script and voice script together, then that chunk again |
| "hold longer here / zoom / label" | `edit.json` (or the conform script) → render |
| "caption wrong" | the conform script or `captions.srt` → render |
| "music too loud / quiet" | `audio-lib.json` `mix.music_lufs` (and `duck`) → render. This applies to every video |
| "different music" | a new asset in `audio-lib.json` → `audio-lib.mjs --make music/<id>` → name it in `edit.json` `sound.music` |
| "no click there" | `"sfx": false` on that segment, or adjust `mix.click_peak_db` |

### Step 9 — Placement kit, audit, publish

- **Claude:** with the final length, fill the Placement Kit (chapters, the cut's table, screenshots,
  GIF, poster 1280 × 720 under 2 MB, transcript), then run the final audit on the exported files.
  **Gate C:** PASS before anything is published.
- **The VA:** uploads and publishes per the video's VA Publish Checklist.

---

## 5. Making videos for a new plugin or product

The pipeline is generic. What changes is the demo site, the product's names and its strategy
documents. The voice, the music and the sounds are reused as they are.

**Where:** give each product its own repo with the same layout, so video ids stay `v1, v2, …` and
chunks stay `V1_b1, …` (the tools expect `vN` / `VN_`). Copy:
- `project_asset/marketing/tooling/`: then run `npm install` there;
- `project_asset/docs/video-pipeline-guide.md` (this file) and `video-production.md` as the runbook
  template;
- `wp-content/mu-plugins/auto_login_local.php` into the new local site: passwordless login for the
  recorder. Local only, and **never deployed**.

**Then adapt:**

| Where | What to change |
|---|---|
| `record-scenes.mjs` → `BUILDS` | the product's plugin folder slugs, pro and free (used to switch builds). Leave it unused if there's one build |
| `record-scenes.mjs` → `PHP`, `WP`, `SOCK` | MAMP's PHP, wp-cli and MySQL socket paths, if they differ |
| `record-scenes.mjs` → V1-specific helpers | the revenue-card readiness checks and measurements (`pdwchs-*` selectors) belong to V1's ready profile. A new product uses the generic checks in `scenes.json` → `ready`: `no-spinner`, `no-notice`, `no-error`, `text:…`, `no-text:…` |
| `scenes.json` (per video) | `base` (the site URL), `wpPath`, `user` (a throw-away admin), `ready` profiles, scenes, `endCard` (title, CTA) |
| `edit.json` (per video) | the label texts (product name), segments, cut |
| `voice.json` → `keyterms` | add the product's names, so the transcription check spells them right |
| Voice script → pronunciation table | any product name the voice gets wrong |
| Strategy documents | the product's own topic list, script rules and feature brief (PureDevs' are in `strategy/PureDevs_Video_Docs/` and `marketing/MARKETING-BRIEF-3.1.2.md`) |

**Nothing to change:**
- `voice.json` voice and settings: the same clone and model.
- The audio library: same path, same files.
- `voice-gen.mjs`, `render-edit.mjs`, `qc-video.mjs`, `voice_phrases.py`, `audio-lib.mjs`.
- The ElevenLabs key: the same Keychain entry works for every repo on the Mac.

**First video for a new product, in order:**
1. Set up the local demo site with realistic data; add the auto-login mu-plugin and a throw-away admin.
2. Copy the tooling, `npm install`, adapt the table above.
3. `node voice-gen.mjs --setup --voice WtNDtptM8xXyadpKyK8M`, then `node audio-lib.mjs`: every asset ✓.
4. Script → gate A → `scenes.json` → `record-scenes.mjs --probe` → scene-01 → gate B → all scenes.
5. `make-end-card.mjs`, the two scripts, the draft captions.
6. `voice-gen.mjs --dry`, then the real run, then `--process`.
7. `edit.json` (+ a conform script if phrases must land on clicks) → `render-edit.mjs --master --cut`.
8. Send master + cut → gate D → fixes → Placement Kit → audit → publish.

---

## 6. Commands, all in one place

```bash
cd project_asset/marketing/tooling

# once
security add-generic-password -a "$USER" -s elevenlabs-api -w
npm install
node voice-gen.mjs --setup --voice WtNDtptM8xXyadpKyK8M
node audio-lib.mjs --make all

# per video
node record-scenes.mjs --video vN --probe
node record-scenes.mjs --video vN [--only scene-01]
node make-end-card.mjs --video vN --seconds 5
node voice-gen.mjs --video vN --dry
node voice-gen.mjs --video vN
node voice-gen.mjs --video vN --process
python3 ../../content/videos/vN/conform-voice.py          # if the video has one
node render-edit.mjs --video vN --master --cut

# fixes
node voice-gen.mjs --video vN --redo b3a
node voice-gen.mjs --video vN --pick b3a=t1
node voice-gen.mjs --video vN --fix b2b
node render-edit.mjs --video vN --cut                       # re-cut from the existing master only
node qc-video.mjs <file.mp4> [--cut] [--fix]
```

---

## 7. Deliverable spec

| Item | Spec |
|---|---|
| Master | 1920 × 1080, 30 fps, H.264 yuv420p, AAC 48 kHz, −14 LUFS (±0.5), true peak ≤ −1 dBTP, 60–120 s (tell Yeasir if it passes 2:00) |
| Cut | same frame, codecs and loudness; 20–30 s, never under 11 s |
| Captions | burned in, from the caption script (never the voice script); white on a dark box; emphasis amber |
| Holds | at least 5 s on anything the viewer must read, at least 8 s on the answer frame |
| Branding | product name in the first frame and on the end card; one CTA; "Demo store" label |
| Sound | the voice; the music bed about 23 dB under it; clicks and typing only |
| Poster | 1280 × 720 PNG under 2 MB; WebP for the page |

---

## 8. Troubleshooting and known quirks

| Symptom | Cause / fix |
|---|---|
| `No ElevenLabs API key` | store it in the Keychain (§2.2) |
| `401 … missing the permission X` | add that permission to the key in ElevenLabs. `models_read` is optional |
| "mp3_44100_192 isn't available on this plan" | expected on the current plan; the tools use 128 kbps |
| Music: "`seed` cannot be used with `prompt`" | the API rule; music from a prompt has no seed, so a re-make is a new track. Keep good tracks |
| A chunk is flagged after retries | read the differences in the run summary: a transcription spelling (harmless; add the name to `keyterms`) or a real dropped word (`--redo`) |
| Conform stops: "phrase not found" / "PHRASES no longer match" | the voice script changed, or the take dropped words; update `PHRASES` or redo the chunk |
| Conform note: "no pause between phrases" | the voice ran two phrases together. The cut falls between their words; fine unless they land on different clicks |
| QC fails loudness only | `render-edit` fixes it in place (two-pass loudnorm, picture untouched) |
| QC fails frame or codec | an editor's export: re-export to spec, or re-render here |
| A failed render | the last good master and cut are never overwritten (renders go to a temp file first) |
| Measuring the music under the voice | rebuild the music stem with the same filter and measure it. Subtracting two AAC files leaves voice residue and gives nonsense |
| Labels look wrong | labels are drawn with the installed Chrome; check Chrome is installed |

---

## 9. Costs (Oct 2026)

| Item | Cost |
|---|---|
| Voice | the voice script's length per run: V8 sent 1,325 characters (the API reported 145 credits) |
| Retakes | one chunk's characters each (`--redo`) |
| Speech-to-text | seconds of audio per take; small |
| Music bed | once per track, reused by every video |
| Clicks, typing | once, reused |

---

## Changelog

- **1.0 — 7 Oct 2026.** First version, after V8 was made end to end here: API voice, music bed, clicks, QC.
