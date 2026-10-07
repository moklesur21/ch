# Video production — the per-video workflow

**Scope:** every demo video in the Topic Filter v4.2 backlog (V1, V8, V13, V4,
V16, V14, V7, V3, then the next window).
**Written:** 28 Sep 2026, agreed in the V1 kick-off session.
**Replaces**, for work done in this repo, the paste-and-upload flow in
`project_asset/strategy/PureDevs_Video_Docs/claude.txt` (research in Gemini →
new Claude chat → upload the docs → run FILE 2–6 one message at a time). The
Prompt Pack's FILE 2–6 remain the spec for each document; this file says who
runs them, where everything lives, what is machine-checked and where the
reviews are.

Read this first when a video starts. Update §8 (status) when a step completes,
and §5 (the recorder) with what actually worked on V1.

---

## 1. Sources — what decides what

| Question | File |
|---|---|
| Which video: title, tier, screens, decision, guardrails, demo prerequisites, week | `project_asset/strategy/PureDevs_Video_Docs/PureDevs_Video_Topic_Filter_v4.2.md` — §4 briefs, §6 week plan |
| How each document is written: Universal Block, owner rules, FILE 1–6 | `project_asset/strategy/PureDevs_Video_Docs/PureDevs_Demo_Video_Prompt_Pack_v3.md` |
| The research prompt (FILE 1, paste-ready, with a FIELD BLOCK per video) | `project_asset/strategy/PureDevs_Video_Docs/gemini.txt` |
| What the product does, Free / Pro, exact UI wording, ⛔ / ⚑ notes | `project_asset/marketing/MARKETING-BRIEF-3.1.2.md` (+ `-PDF-INDEX.md`) — and, in this repo, the installed plugin code itself |
| Format, placements, exclusions, ad rules | `project_asset/marketing/PureDevs_Proof_Marketing_Ad_Strategy_v1.md` |
| The voice: ElevenLabs clone of Yeasir's own voice — plan, model, locked settings, voice log, script conversion, chunking, listening check, file naming, editing to the audio, re-renders, YouTube disclosure | `project_asset/strategy/PureDevs_Video_Docs/PureDevs_ElevenLabs_v4_Voice_Script_Guide_v1_1.md` (owner decision 28 Sep 2026; applies to every video; earlier docs call it `…_Voiceover_Workflow_v1_1.md`). Since 7 Oct the voice is made through the API: the locked choices live in `project_asset/marketing/tooling/voice.json` (step 5) |
| Launch dates | `project_asset/strategy/assets/PureDevs_Execution_List_v1.md` — ad launch Tue 6 Oct 2026. The Topic Filter's §6 week plan still shows the older dates |
| Demo data: refresh, verify, seeder | `scripts/demo-refresh.sh` · `wp-content/plugins/pdwchs-demo-tools/` · `project_asset/demo/DEMO-STORE-RUNBOOK.md` |
| Browser tooling: login, tier override, Chrome via playwright-core | `project_asset/marketing/tooling/` and its `README.md` |
| How steps 4–7 are done, command by command — voice (ElevenLabs API), edit, music and clicks, QC, review fixes — and how to reuse it for a new product | `project_asset/docs/video-pipeline-guide.md` |

`claude.txt` is kept only for running the pack outside this repo.
**Owner rules (Pack U2) beat every file:** no spoken figures · a freshness
check on the day any sentence about WooCommerce core or another tool is
written · no 100-customer cap on Free, ever · trial CTAs go to
https://puredevs.com/pricing.

---

## 2. Where everything lives

```
project_asset/content/videos/vN/
  research/                            step 1 — Gemini / Perplexity output, one file per source (Yeasir saves it)
  PureDevs_VN_Demo_Brief_vX_Y.md       step 2 — FILE 2
  PureDevs_VN_Script_vX_Y.md           step 2 — FILE 3: script + YouTube package + poster spec
  PureDevs_VN_Recording_Brief_vX_Y.md  step 4 — FILE 4: the scene table is the spec for scenes.json; §C–§E are the edit notes
  PureDevs_VN_Placement_Kit_vX_Y.md    step 6 — FILE 5
  scenes.json                          step 4 — the scene file the recorder plays (tracked, so any clip can be re-made)
  edit.json                            step 5 — the edit list render-edit.mjs plays: segments, speed ranges, zooms, labels, voice, the cut
  audio/                               step 5 — voice-gen.mjs: takes/<chunk>-tN.mp3, takes.json (seeds, word checks, picks), <chunk>.mp3 = the pick
  recordings/                          step 4 output — media stays on this Mac, never in git
    scene-01.mp4 … scene-NN.mp4        video and audio files are gitignored (*.mp4 *.mov *.webm *.mkv *.wav *.mp3 *.m4a)
    answer-frame.png · answer-frame-crop.png · poster.png
    delivery-note.md                   which card / bar / row is biggest, when recorded, anything skipped — never an amount
    voice-over.md                      the CAPTION script: the approved words with every [A]/[B] option resolved, correct spelling
    voice-over-elevenlabs.md           the VOICE script for ElevenLabs: same words, [PAUSE] → ellipsis, [thoughtful] per chunk, one chunk per beat
    captions.srt                       draft burned-in captions with the emphasis words marked
    end-card.png · end-card.mp4        step 4 — from make-end-card.mjs and the endCard block in scenes.json
    voice/                             step 5 — voice-gen.mjs --process: the picks after one loudness chain (V8_b1.wav …); audio is gitignored
    edit/                              step 5 — render-edit.mjs: segments, VN_reference_master.mp4, VN_cut_NNs.mp4
```

Rules: file names follow the V1 pattern (`PureDevs_V1_Script_v1_2.md`); every
change bumps the version, adds a changelog line and deletes the superseded
file. The recorder itself lives in `project_asset/marketing/tooling/` (§5).

---

## 3. The six steps

| # | Step | Who | Needs | Produces | Gate |
|---|---|---|---|---|---|
| 1 | Research | Yeasir | `gemini.txt` + the video's FIELD BLOCK | `vN/research/*` | — |
| 2 | Demo Brief, then Script + YouTube package + poster spec | Claude | Topic Filter row · Brief entries · research · plugin code · freshness checks made today | Demo Brief, Script | **A — Yeasir approves the script.** Nothing is recorded before this |
| 3 | Demo data | Claude | the row's demo prerequisites + the script's stop-and-report conditions | `verify` checks for this video, seeder fixes if needed, a green `demo-refresh.sh` | verify: 0 FAIL |
| 4 | Recording brief, scene file, recording, the two scripts | Claude | approved script, green data | Recording Brief, `scenes.json`, `recordings/*` incl. `voice-over.md` (caption script) and `voice-over-elevenlabs.md` (voice script) | **B — scene-01, the answer frame, goes to Yeasir first** as the quality check, before the rest is recorded |
| 5 | Voice (ElevenLabs API) and edit | Claude | the voice script, `voice.json`; then `recordings/`, `edit.json`, Recording Brief §D–§E, poster spec | `audio/` takes → `recordings/voice/*.wav`, then the 1080p master + the 20–30 s cut, both QC PASS | **D — Yeasir watches the master and the cut**; every note has a fixed fix (step 5) |
| 6 | Placement kit and final audit | Claude | the final export | Placement Kit; FILE 6 audit → **PASS / FIX FIRST** | **C — PASS before anything is published** |

### Step 1 — Research (Yeasir)

- Paste the block between the two lines in `gemini.txt` into Gemini (and
  Perplexity, if still used), with the three fields from the video's FIELD
  BLOCK at the bottom of that file.
- Save each output as-is into `vN/research/` (any name, for example
  `gemini-2026-09-28.md`). Then say which video is ready.
- V16 has no research step ("Research: none").

### Step 2 — Demo Brief and Script (Claude)

- Run FILE 2, then FILE 3, exactly as the pack specifies, with the Universal
  Block loaded.
- What differs from the old chat flow: every UI title, menu path, tier gate and
  definition is checked in the installed plugin code (the `-pro` folder and the
  free folder) and on the local store, not only in the Brief. Every sentence
  about WooCommerce core or another tool gets its freshness check (U2.2) on the
  day it is written, with URL and version, recorded in the Demo Brief (V1's is
  §12 "Checks run today").
- Where the relationship on screen could flip, the script carries both options
  in `[brackets]`; step 4 resolves them.
- Deliver both files, then stop for gate A.

### Step 3 — Demo data (Claude)

- The row's **Demo prerequisites** and the script's **stop-and-report** list
  become checks in `wp pdwchs-demo verify`
  (`pdwchs-demo-tools/includes/class-pdwchs-demo-verifier.php`), named
  `vN_*`, so `scripts/demo-refresh.sh` prints a record / don't-record verdict
  that covers this video. V1, for example: all three Revenue by Customer Type
  cards non-zero for the last 30 days, Returning the biggest, Guest not the
  biggest, no near tie.
- If the seeder cannot meet a prerequisite, fix the seeder
  (`class-pdwchs-demo-seeder.php`). V13's zero-result searches are missing
  because search rows are written without `results_count`.
- Done 28 Sep 2026: the verifier is scoped to the v4.2 list — `env_*`,
  `data_*`, `dash_*` (Pro dashboard widgets alive, warn only, for V16's
  scroll-past), `v1_*`, `v4_funnel`. The July hero / V2 / V3 / V4-shape checks
  are gone (git history has them); the seeder still builds that data. The
  `v1_*` checks call the plugin's own methods for the same window the screens
  send (today and the 29 days before it), and `verify --report` prints the
  answer-frame sheet: the three cards, which is biggest, the guest position,
  the badge and the repeat rate — the delivery-note facts, never narration.
- The demo-tools plugin is also what the live site regenerates from
  (`scripts/live-refresh.sh`): after any seeder or verifier change here,
  upload the same plugin version to ch.puredevs.com before the next live
  refresh, or the live verdict will differ.
- **V8 is the exception: it records on its own throw-away store,** `ch-v8`
  (`/Applications/MAMP/htdocs/ch-v8`, outside the repo, DB `ch_v8`), because
  its beat 2 is a one-take real install from WordPress.org and `ch` already
  has the plugin and a Freemius registration. `scripts/v8-site.sh` builds it
  (`create`: WordPress and WooCommerce copied from `ch`, the Northline store
  from `wp pdwchs-demo seed --woo-only` + `setup-storefront`, demo-tools then
  removed), snapshots the pre-install state, and `reset` restores it before
  every take; `update` brings WordPress, plugins and themes to the latest
  release and re-snapshots (owner rule, 7 Oct 2026: the video always shows
  the latest WordPress, whatever the plugin readme says). `scripts/v8-site.sh
  check` replaces `demo-refresh.sh` for V8:
  pre-install state, versions, update bubbles, and the wp.org card
  ("Tested up to" must cover the site's WordPress, or the card says
  "Untested"). Delete the site with `destroy --yes` once V8 is published.
- Timing: widgets cache up to 30 minutes, so finish seeding at least 30 minutes
  before recording. Record on the day you refresh (`demo-refresh.sh --help`
  covers pinned anchors and retakes).
- Environment (found 28 Sep 2026, fixed the same day): MAMP's MySQL 5.7
  dropped most concurrent connections once mysqld held more than 1024 open
  files — `File Descriptor N exceeded FD_SETSIZE=1024` in
  `/Applications/MAMP/logs/mysql_error.log`. The dashboard fires a dozen
  admin-ajax calls at once, so its cards showed "—" and the badge "Could not
  load integrity check" while single requests (and WP-CLI) worked. Fix:
  `/Applications/MAMP/conf/my.cnf` with `innodb_open_files = 300` and
  `table_open_cache = 400`, then restart MySQL in MAMP. The file lives
  outside the repo — re-create it after a MAMP reinstall. Before a recording
  session: `SHOW GLOBAL STATUS LIKE 'Connection_errors_internal'` must be 0
  and the error log must have no FD_SETSIZE lines; a burst of 12 fetches to
  the split endpoint from the dashboard page must all return JSON.

### Step 4 — Recording brief, scene file, recording (Claude)

- Write the Recording Brief per FILE 4. Its scene table is the spec for
  `scenes.json`; its `[DEV SETUP]` list is Claude's to do; its §C–§E
  (recording rules, edit notes, delivery) drive `edit.json`. Screenshots come
  from the local store (the capture script or the recorder's own stills), not
  from the PDF.
- `scenes.json`: one entry per scene — build (`pro` / `free`), start URL,
  actions (click, scroll, hover, type, wait), cursor rest points, hold seconds,
  what must be visible. The recorder refuses to run when the page shows a
  spinner, an error, "No data", an update badge or an admin notice, matching
  the brief's stop-and-report list.
- Record scene-01 first → gate B. Then all scenes. Then:
  - `delivery-note.md` — the delivery note FILE 4 §E asks for, written from what
    the page showed (which card is biggest, where guest sits, date and time,
    anything skipped). Which box, never the amount.
  - `voice-over.md` — the **caption script**: the approved script with the
    `[A]/[B]` and `[C]/[D]` options chosen from the delivery note, correct
    spelling, the emphasis words listed. Captions, the question-page
    walkthrough and the YouTube transcript come from it.
  - `voice-over-elevenlabs.md` — the **voice script**, built from the caption
    script by the ElevenLabs guide's Part 1 rules: the same words, word for
    word; `[thoughtful]` at the start of every chunk; `[PAUSE]` → an ellipsis
    (Eleven v4 has no break tags); about three reveal pauses per video, one
    per chunk at most; one chunk per beat, any beat over about 60 words
    split; pronunciation fallbacks in their own table (Term | Chunk | Replace
    with), never in captions; no figures, no URLs, no trial terms. Each chunk
    is a `## VN_bX — …` heading with the text in a code block — the form
    `voice-gen.mjs` reads. It ends with the chunk plan and a word-for-word
    check against the caption script.
  - `captions.srt` — draft captions with the emphasis words marked, timed to
    the script's beat slots; step 5 re-times them to the voice (the conform script).
- Free scenes are recorded on the free build activated in place of Pro (both
  are installed side by side, one active at a time: `scripts/wpc plugin
  deactivate … && scripts/wpc plugin activate …`), with every trial notice
  dismissed. Confirm on V1 that switching builds keeps the data and the
  widgets. Zero-Result Searches (V13) exists only on the free build — never
  record it on Pro switched down.
- Storefront actions run logged out (a separate browser context), because
  admin browsing is never recorded, and they come before any plugin page in
  the same scene (Free reads cache 30 minutes).

### Step 5 — Voice and edit (Claude; gate D is Yeasir's)

Owner decision 7 Oct 2026: the voice and the edit are made here. Nothing goes
to a VA; the only thing that leaves the Mac is the voice script's text, sent to
ElevenLabs exactly as the web UI sent it. Yeasir watches the result.

- **The voice** is the ElevenLabs Instant Voice Clone of Yeasir's own voice
  (owner decision 28 Sep 2026; pronunciation test passed), Eleven v4, made
  through the API by `project_asset/marketing/tooling/voice-gen.mjs`. The
  locked choices — voice, model, Stability, Similarity, takes per chunk, the
  loudness chain — are in `marketing/tooling/voice.json`. The API key lives
  in the macOS Keychain only (service `elevenlabs-api`); Claude never sees
  it. One-time setup (Yeasir): in ElevenLabs create a key restricted to
  Text to Speech, Speech to Text, Voices (read) and Models (read), with a
  monthly credit cap, and store it with
  `security add-generic-password -a "$USER" -s elevenlabs-api -w` (it asks
  for the key, so it stays out of shell history). Then `node voice-gen.mjs
  --setup` finds the clone and the model and fills `voice.json`.
- `node voice-gen.mjs --video vN --dry` shows the characters a run will
  spend; `node voice-gen.mjs --video vN` makes one take per chunk (owner
  decision 7 Oct 2026; a fixed seed, so any take can be made again; joined
  to the previous chunk's take, so the read flows) and transcribes it — the
  machine version of the guide's §2.5 listening check for words, clipped
  endings and stray sounds. Only a take that fails it (a word missing, added
  or changed, or a clipped ending) gets up to two more. Tone is judged at
  gate D: the video is always finished first, and anything still flagged is
  listed for Yeasir with the master. Everything is logged in
  `audio/takes.json`.
- `node voice-gen.mjs --video vN --process` runs one loudness chain over all
  the picks joined (gain trimmed to −14 LUFS) and splits them back into
  `recordings/voice/VN_<chunk>.wav` — the same files the V8 hand process made,
  bit for bit. Audio is made **after** the recording and **before** the
  picture edit: the real audio sets the cut.
- **The edit:** the video's `edit.json`, and — where phrases must land on
  clicks — a per-video conform script that fits the picture to the voice and
  re-times `captions.srt` (V8: `conform-voice.py`). Conform scripts find
  their phrases with `marketing/tooling/voice_phrases.py`: cuts land in the
  take's pauses, and the take's speech-to-text word times decide which pause
  is which phrase boundary, so a take that breathes mid-phrase still fits.
- **Sound (owner decision 7 Oct 2026):** one calm, minimal music bed under
  every master, and a soft mouse click on every recorded click plus quiet
  typing under every typed stretch — nothing else (no whooshes, pops or
  dings). They come from the shared audio library
  (`~/Music/PureDevs-Audio-Library`, outside the repo so other projects can
  use it; made by `marketing/tooling/audio-lib.mjs` with ElevenLabs Music and
  Sound Effects, definitions and mix levels in `audio-lib.json`, prompts and
  licence in the library's `library.json` / `README.md`). A video opts in
  with the `"sound"` block of its `edit.json`. The bed sits about 23 dB under
  the voice while it speaks and about 17 dB under in the pauses (ducked by
  the voice, faded in and out); clicks follow the recorder's step times
  through the speed ranges. Licence: ElevenLabs paid plans allow online
  commercial use (ads, YouTube, product demos), not film, TV, radio or games;
  no attribution. Then `node
  render-edit.mjs --video vN --master --cut`: the 1080p master with captions
  burned in, and the 20–30 s cut from the `"cut"` block. Each is checked by
  `qc-video.mjs` (1920 × 1080, 30 fps, H.264, AAC 48 kHz, −14 ±0.5 LUFS, true
  peak ≤ −1 dBTP, 60–120 s / 20–30 s); off loudness is fixed with the picture
  stream-copied untouched.
- Rules unchanged: holds never below 5 s on a card, chart or table, the
  answer frame ≥ 8 s; captions from `voice-over.md` / `captions.srt`, never
  from the voice script; the master within 60–120 s (tell Yeasir if
  it passes 2:00 — the "2-minute demo" ad lines depend on it). Poster
  1280 × 720, under 2 MB. No YouTube AI disclosure is needed for an own-voice
  clone (guide §8).
- **Gate D — Yeasir watches the master and the cut** and writes notes in
  plain words. Each kind of note has one fix, then a re-render (minutes):

| Note | Fix |
|---|---|
| A chunk sounds wrong (flat, rushed, odd stress) | `voice-gen --redo <chunk>` (a new take; the old ones are kept and can come back with `--pick <chunk>=tN`), then `--process`, the conform step, `render-edit --master --cut` |
| A name is mispronounced | add the fallback to the voice script's pronunciation table → `voice-gen --fix <chunk>`; once it works, into the guide's §2.6 |
| The voice sounds unlike Yeasir, or uneven between chunks | `voice.json` Stability / Similarity (guide §2.2), then every chunk again |
| A word should change | the script (gate A): caption script and voice script together |
| Timing, a hold, a zoom, a label | `edit.json` or the conform script |
| Music too loud / quiet, or not this track | `audio-lib.json` `mix` (levels, ducking) for every video, or a new library track (`audio-lib.mjs --make`) named in `edit.json` `"sound"` |
| A click or typing sound where it shouldn't be | `"sfx": false` on that segment, or the levels in `audio-lib.json` |
| A caption | the conform script that writes `captions.srt` |

### Step 6 — Placement kit and final audit (Claude)

- Once the final length is known: run FILE 5 (or fill the existing kit's
  `[LENGTH]` and timecodes), then FILE 6 on the final export — frames and
  burned-in captions pulled from the file with ffmpeg, plus the YouTube package
  and the poster. Re-run the freshness checks on publish day (V1's honest
  sentence must be re-checked if WooCommerce 11.2 has shipped).
- Verdict **PASS** or **FIX FIRST**. Then update §8.

---

## 4. Rules that apply to every video (short form; the pack has the full text)

- No spoken figures, ever: "read the proportion, not the number". Search terms
  and product names may be read aloud; they are words, not figures.
- No sentence without the screen that proves it. No external statistic in
  narration.
- One CTA: a free-answered question → install free; a Pro-answered question →
  start the trial → https://puredevs.com/pricing. Say "30-day" or "no card"
  only where the task confirms it.
- Never: a Free customer cap · "only / first / nobody else" · competitor names
  · recovery, automation or coupon language · RFM, churn, health, behavioral
  tags or ready-made segments as a subject (scroll past ⚑ widgets, never stop).
- Privacy wording: "self-hosted, nothing leaves your server, works with
  WordPress's personal-data export and erase tools". Never "GDPR-compliant",
  "cookie-less", "consent-managed" or "anonymised".
- Recording: local store only, never ch.puredevs.com · 1920 × 1080 frame ·
  cursor highlighted · no update badges, red bubbles or notices · hold ≥ 5 s on
  anything the viewer must read and ≥ 8 s on the answer frame · one clip per
  scene with 3 s lead-in and lead-out · "PureDevs Customer History" in the
  first frame and on the end card.
- The answer frame is beat 1's picture, the poster and the thumbnail.

---

## 5. The recorder — built 28 Sep 2026, proven on V1 scene-01

- File: `project_asset/marketing/tooling/record-scenes.mjs`, next to
  `capture-screens.mjs` (same playwright-core, same installed Google Chrome,
  no browser download). Scene file: `content/videos/vN/scenes.json`. Runs:
  - `node record-scenes.mjs --video v1 --probe` — readiness audit, geometry,
    home-position check, park point, the resolved A/B and C/D facts, the
    screencast frame size; `probe.png` goes to the OS temp dir; records nothing.
  - `node record-scenes.mjs --video v1 --only scene-01,scene-02`, or without
    `--only` for every scene plus the stills and `delivery-note.md`.
  - Options: `--fps 30 --crf 16 --keep-frames --force`.
- Login: the local auto-login helper, as the throw-away admin `pdwchs-shot`
  (`shot@example.invalid`, display name "Sarah M.", default avatar). Never
  the real admin: the page carries that account's Gravatar hash. Create it
  with `scripts/wpc user create pdwchs-shot shot@example.invalid
  --role=administrator --display_name="Sarah M." --user_pass=<random>` and
  `scripts/wpc user meta update pdwchs-shot show_welcome_panel 0`; delete it
  when the series is done. No password is stored anywhere.
- Picture: viewport 1920 × 1080 CSS px at device scale 2. Chrome's own
  screencast (CDP `Page.startScreencast`, JPEG q93) delivers 3840 × 2160
  frames whenever something repaints; each frame is saved with Chrome's
  timestamp, ffmpeg's concat demuxer turns the timestamps into per-frame
  durations, then `fps=30`, libx264 preset fast, CRF 16, yuv420p, faststart,
  cut to the scene's exact length. A static scene is one frame; the 12 s
  answer frame is about 750 KB.
- Cursor: headless Chrome draws none, so an init script adds a fixed overlay
  (black-and-white arrow plus a 48 px amber ring) that follows real
  `mousemove` events; the recorder drives `page.mouse.move` in eased 14 ms
  steps, so hover states are real; a click pulses the ring. Smooth scrolls
  run in-page with requestAnimationFrame.
- Readiness audit before every scene, and again after the click in scene 02:
  no visible spinner or "Loading…", no notice, welcome panel or update /
  count bubble, none of "Failed to load", "Could not load", "No data",
  "Error establishing", "Data Mismatch"; the three cards and four KPIs
  populated; badge "Data Verified"; on the free Revenue page the Repeat
  Purchase Rate card present and not 0 %. A failure means the scene is not
  recorded (`--force` overrides). The Pro dashboard needs 10–12 s to settle
  (the plugin's session lock serialises its AJAX), so "ready" is a condition,
  never a fixed wait.
- Positions come from the DOM at run time (`heading:`, `text:` and CSS
  selectors): rest points "below", "right", "center", "below-right" and
  "text-right" (beside the text of a full-width block). Check every new rest
  point once at full resolution, not on a downscaled tile: on the V1 cards
  the label spans the card, so "below" lands at the card's centre on the
  amount's row, beside the left-aligned number — which is what the brief
  wants. Park points: the blank header row between the widget title and the
  date box (inside the frame, outside beat 1's crop), below the cards, or the
  admin-menu gap. The home position is computed (split section bottom 70 px
  above the frame bottom — any more and Retention's heading creeps in) and
  checked (title and date box visible, Retention off screen).
  `{biggest-card}` resolves from what the screen shows. A click that loads a
  new document is declared (`"nav": true`) so the recorder waits for it.
- Builds: each scene names `pro` or `free`; the recorder switches with WP-CLI
  (`plugin deactivate` / `activate`) and restores the original build at the
  end.
- Output: `recordings/scene-NN.mp4`, `answer-frame.png` (3840 × 2160),
  `answer-frame-crop.png` (the split section), `delivery-note.md` (which card
  is biggest, the guest position, the margin, the clip table — no amounts).
  A clip still with `"measure": [selectors]` also writes
  `answer-frame-crop.json`: those elements' rects in the still's pixels.
- End card: `node make-end-card.mjs --video vN` renders the video's
  `endCard` block in `scenes.json` — the answer-frame crop (grey page margin
  trimmed by `trimX`), a green outline on `highlight` (a key from the still's
  `.json`, e.g. `{biggest-card}`, or an explicit rect, or null), the product
  name, one CTA, the "Demo Store" label — to `recordings/end-card.png` and a
  4 s H.264 `end-card.mp4` at the master's size. The CTA follows the tier:
  Pro-answered → "Start the trial · puredevs.com/pricing"; free-answered →
  install free (V8, V13). `render-edit.mjs` appends it to the last segment
  with a short crossfade (`edit.json` `endCard`); the lower-third / "Demo
  Store" overlays end where it starts (the card carries both); hold it ~2 s
  past the last word.
- `[DEV SETUP]` done for V1 on 28 Sep: WordPress 7.1.2, WooCommerce 11.1.2
  and every plugin and theme updated (no update bubbles); WooCommerce
  post-update notices cleared; the WooPayments incentive badge on the
  Payments menu dismissed for the shot user through WooCommerce's own
  `Payments::dismiss_extension_suggestion_incentive(…, do_not_track = true)`;
  UpdraftPlus's "thank you" notice dismissed through its own option
  (`updraftplus_dismisseddashnotice`, a year ahead); welcome panel off; the
  WordPress home screen (scene 02's first frame) tidied through the account's
  own Screen Options: at 1920 px the dashboard is always four columns and an
  empty column shows a dashed "Drag boxes here" box, so the account keeps
  exactly four neutral widgets, one per column (`meta-box-order_dashboard`:
  WooCommerce Status · At a Glance · Recent Reviews · Quick Draft) and hides
  the rest (`metaboxhidden_dashboard`: Site Health, Events & News, Activity,
  WooCommerce Setup); Email Log's widget is hidden through the plugin's own
  `email-log-core[hide_dashboard_widget]` setting. Re-check with `--probe`
  before every session.
- **Added for V8 (7 Oct 2026), all opt-in from `scenes.json`, V1 unchanged
  (its `--probe` re-checked):** `wpPath` (WP-CLI targets another site, e.g.
  ch-v8) · `preflight` (a command run before scenes marked `"preflight":
  true`; V8 runs `scripts/v8-site.sh check`) · `allowNotices` · per-scene
  `"context": "visitor"` (a separate logged-out browser, for storefront
  activity) · steps `type`, `press`, `goto`, `back`, `wait-for`, `scroll-to`,
  `timer-start` / `timer-stop` (an mm:ss clock bottom-right that survives page
  loads — the one-take proof) and `note` (a text fact for the delivery note) ·
  ready checks `text:…` / `no-text:…` · `--rehearsal` (carries on past a failed
  preflight and writes to `recordings/rehearsal/`) · a generic `--probe` that
  screenshots each `--only` scene's start page.
- **The edit can be done here too (V8, 7 Oct 2026):** `render-edit.mjs --video vN`
  reads `content/videos/vN/edit.json` and renders finished 1080p segments into
  `recordings/edit/`: speed ranges (with a speed badge where the clip carries a
  clock), smooth zooms (zoompan on the 4K clips), labels drawn with Chrome, the
  end card with a crossfade, and the voice when its files exist. Times come
  from the recorder's `<scene>.steps.json`, which logs every step's time and
  target rect. Since 7 Oct it also builds the 20–30 s cut (`--cut`) and
  checks both files with `qc-video.mjs`; with `voice-gen.mjs` (step 5) the
  whole video is finished here. No outside editor is used.
- Not the recorder's job: anything that signs in to or accepts something on an
  outside service — the Freemius opt-in prompt in V8, starting the trial in
  V16. Those steps are Yeasir's, on camera, or are filmed on the local builds
  without the sign-in.
- Switch builds only when the screen itself differs. V1's scene 08 (the
  Repeat Purchase Rate card, REV-02) is recorded on Pro: the card is the same
  template in both builds and the zoom never reaches the tab bar. A free-only
  screen (V13's Zero-Result Searches) still needs the free build.

---

## 6. Hand-off

- **To Yeasir (gate D):** `recordings/edit/VN_reference_master.mp4` and
  `VN_cut_NNs.mp4`, with the QC lines and any flagged chunk.
- **To the VA, after gate D and the audit:** the approved master, the cut,
  the poster and the Placement Kit, for publishing (`vN/VN_VA_Publish_Checklist.md`).
  Shared outside git.
- A wrong clip is re-made by re-running its scene; nothing is edited by
  hand outside the repo.

---

## 7. What Claude does not do

- Sign in to, or accept anything on, an outside service (Freemius opt-in,
  trial start, WordPress.org, YouTube).
- See or store the ElevenLabs API key: it stays in the Keychain, and
  `voice-gen.mjs` reads it at run time.
- Judge the voice's tone by ear: the takes are checked by machine for words,
  clipped endings and stray sounds; how it sounds is Yeasir's call at gate D.
- Touch the live site or its data.

---

## 8. Status — update at each milestone

| Video | Week (§6) | 1 Research | 2 Brief + Script | 3 Data verified | 4 Recorded | 5 Edited | 6 Kit + audit |
|---|---|---|---|---|---|---|---|
| V1 returning revenue (PRO) | 1 | used 21 Sep; not archived in `research/` — Demo Brief §12 carries the checks | Brief v1.2 + Script v1.2, approved 21 Sep | **done 28 Sep**: verifier re-scoped to v4.2 (`v1_*`, `v4_funnel`, `dash_*` warn-only); seeder gives 8 potentials a second in-window order so the free Repeat Purchase Rate card is not 0 %; `demo-refresh.sh` → 31 pass / 0 fail | **done 28 Sep**: gate B passed on scene-01; all eight clips recorded (3840 × 2160, 2:33 of footage), stills, `delivery-note.md` (option A / C), `voice-over.md`, `voice-over-elevenlabs.md` (8 chunks, 239 words), `captions.srt`; Recording Brief v1.4 | audio chunks in `recordings/audio/` (29 Sep); `V1_master.mp4` delivered 6 Oct (1:40, 2560 × 1440 HEVC) | **audit 6 Oct: PASS** on `pd v1 final edit -14LUFS.mp4` (1:40): captions fixed, end card in, audio normalised by Claude to −14.0 LUFS / −2.3 dBTP with the picture stream copied untouched (the editor's export was −11.1 LUFS / +0.2 dBTP). Beat-5 free-version shot left wide: owner's call. Honest sentence re-checked: WooCommerce 11.1.2 still latest. **Placement Kit v1.3 done 6 Oct** (1:40, chapters; screenshots, GIFs and the 23.5 s cut made). Hand-offs: `v1/V1_VA_Publish_Checklist.md` (VA) and `content/questions/PureDevs_Question_Pages_Dev_Spec_v1.md` (website developer: `question` CPT + V1 page content). Thumbnail `V1-poster.png` + page poster `V1-poster.webp` made 6 Oct (source `v1/poster.html`). Left: upload, then the YouTube ID / thumbnail URL / upload date in the JSON-LD |
| V8 data location (FREE) | 1 | done: `v8/research/gemini-2026-10-07.md`, `perplexity-2026-10-07.md` | **Brief v1.1 + Script v1.2**, gate A passed 7 Oct. Owner rules: no export/erase claim; always the latest WordPress (7.1.3); the recorder clicks Skip; the readme's "Tested up to" does not block. Dev hand-off `v8/V8_Dev_Requests.md`; D4 fix in Section D guide v1.5 | throw-away store `ch-v8` (`scripts/v8-site.sh`), WordPress 7.1.3, check 0 FAIL / 1 warn. Product search added to the Northline header | **recorded 7 Oct** (real take after 3 rehearsals); Recording Brief v1.3 | **edited here, with voice, 7 Oct**: one loudness chain (−14 LUFS) → `conform-voice.py` → `render-edit.mjs --master` → `V8_reference_master.mp4` (1:57, captions burned in). **API pilot, 7 Oct night:** re-voiced with `voice-gen.mjs` (9 chunks, one take each, all word-perfect), music bed + clicks + typing, `V8_reference_master.mp4` 1:56 and `V8_cut_27s.mp4` 26.7 s, both QC PASS; the web-UI version kept as `…_webui.mp4` (takes in `audio-webui/`, `recordings/voice-webui/`). **Gate D open:** Yeasir picks a version; if the API one, the Placement Kit timecodes and the audit are re-run on it | **Placement Kit v1.0 + audit 7 Oct: PASS** (`v8/PureDevs_V8_Claim_Audit_v1_0.md`): 27 s cut, poster PNG/WebP, 3 screenshots, GIF, transcript made; YouTube description fixed (WordPress.org link on line 2); AG3 copy swap to apply in Google Ads. Left: upload, then the YouTube ID / thumbnail URL / upload date in the JSON-LD |
| V13 zero-result searches (FREE) | 2 | — | — | zero-result rows missing from the seeder; record on the free build | — | — | — |
| V4 funnel (PRO) | 3 | — | — | — | — | — | — |
| V16 Free → Pro | 4 | none, by design | — | the trial start is a manual step | — | — | — |
| V14 viewed, not bought | 5 | — | — | — | — | — | — |
| V7 when they buy (PRO) | 6 | — | — | — | — | — | — |
| V3 bought together (PRO) | 7 | — | — | — | — | — | — |

V1 was due Fri 25 Sep (Topic Filter §6); the ad launch is Tue 6 Oct 2026.

---

## 9. Quick commands

```bash
scripts/demo-refresh.sh                    # fresh data anchored today + verify + verdict
scripts/wpc pdwchs-demo verify --report    # verify only
cd project_asset/marketing/tooling && node record-scenes.mjs --video v1 --only scene-01   # see §5
node voice-gen.mjs --video v8 --dry          # what a voice run will spend
node voice-gen.mjs --video v8                # a take per chunk → word check (audio/)
node voice-gen.mjs --video v8 --process      # recordings/voice/*.wav
node render-edit.mjs --video v8 --master --cut   # master + cut (music, clicks), both QC-checked
node audio-lib.mjs                           # the shared music / UI-sound library: what exists; --make all
node qc-video.mjs <file.mp4> [--cut] [--fix]     # any delivery against the spec
```

---

## Changelog

- **7 Oct 2026 (late night)** — The CapCut VA guide is removed: no outside editor; the VA only uploads. V8 Recording Brief v1.3, Placement Kit v1.1.
- **7 Oct 2026 (night)** — Sound: a shared audio library outside the repo (`audio-lib.mjs`: ElevenLabs Music bed, mouse clicks, typing), mixed by `render-edit.mjs` from the `"sound"` block; `voice_phrases.py` for conform scripts. V8 re-voiced through the API (pilot for gate D).
- **7 Oct 2026 (evening)** — Step 5 moves to Claude (owner decision): `voice-gen.mjs` (ElevenLabs API, one take per chunk, checked by speech-to-text, retried only on a failed check), `voice.json`, `qc-video.mjs`, `render-edit.mjs --cut`; gate D (Yeasir watches the master and the cut); the VA publishes only. Stale lines fixed: Eleven v4 (not Multilingual v2), ellipses (not break tags).
- **7 Oct 2026 (later)** — V8 recorded; the edit renderer (`render-edit.mjs`), the step log and the CapCut VA guide added (§5).
- **7 Oct 2026** — V8: the `ch-v8` throw-away store and `scripts/v8-site.sh` (§3 step 3), status row; ElevenLabs file name corrected in §1. `ch` updated to WordPress 7.1.3; owner rule: videos always run the latest WordPress.
- **28 Sep 2026** — first version, from the V1 kick-off session.
