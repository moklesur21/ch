# V1 Recording & Edit Brief — How much of my WooCommerce revenue comes from returning customers?

**Version 1.4 · 28 Sep 2026** · Demo Video Prompt Pack v3, FILE 4 · supersedes v1.3
From `PureDevs_V1_Script_v1_2.md` and `PureDevs_V1_Demo_Brief_v1_2.md`. Recording is done by the scene recorder (`project_asset/docs/video-production.md` §5) from `scenes.json` in this folder; the narration is generated with ElevenLabs (`PureDevs_ElevenLabs_Voiceover_Workflow_v1_1.md`); the editor receives finished clips and audio and edits. Screens confirmed on the live store, not from the PDF.

> **STATUS: RECORDED.** Scene-01 passed gate B on 28 Sep; all eight clips are in `recordings/` with the stills, `delivery-note.md`, `voice-over.md` (caption script), `voice-over-elevenlabs.md` (voice script) and draft `captions.srt`. Next: Yeasir generates the audio chunks into `recordings/voice/`; then the editor's part starts at §D.

---

## For Yeasir — the editor can skip this part

### What changed since v1.2

1. **No human recording, no second site.** v1.2 had the editor record on a Pro site and a Free site. The recorder now plays `scenes.json` on the local store: same scene table, same holds, same rest points, at 1920 × 1080 CSS px captured at 2× (3840 × 2160). `[PRO SITE]`, `[FREE SITE]` and the editor's logins are gone.
2. **Scene 08 is recorded on the Pro build.** The Repeat Purchase Rate card (REV-02) is the same template in both builds; the beat-5 zoom stays on the card and its caption, so the build is not visible. Switching builds bought nothing and risked a Freemius prompt.
3. **The A/B and C/D options are resolved by the recorder** from what the screen shows and written to `delivery-note.md`; `voice-over.md` carries the script with the options chosen (A: Returning is the biggest card · C: Guest is the smallest).
4. **Every `[DEV SETUP]` item is done and checked by machine** before a clip is recorded (below).
5. **The voice is generated, not recorded** (v1.4): an ElevenLabs clone of Yeasir's own voice, from `voice-over-elevenlabs.md` — the approved words with `[PAUSE]` as break tags, one chunk per beat — following the ElevenLabs workflow. The audio is made after the clips and before the edit, and the picture is cut to it.

### `[DEV SETUP]` — done on 28 Sep 2026

| # | v1.2 item | How it is met now |
|---|---|---|
| 1 | Pro site, licensed, active a day before | Pro build 3.1.2 licensed on the local store; `scripts/wpc pdwchs-demo verify` → `env_pro` PASS |
| 2 | Data window: orders on most days from new, returning and guest buyers; Returning the biggest box; Guest not the biggest; nothing at zero; the free repeat rate not 0 % | `v1_*` checks in `verify` (three cards non-zero, Returning largest, Guest not largest, ≥ 1.25× margin, orders on ≥ 20 of 30 days, badge verified, repeat rate > 0). `scripts/demo-refresh.sh` on the morning of the shoot: 31 pass / 0 fail |
| 3 | 30-minute cache | `verify` warms the same transients the screens read; the recorder waits for populated cards, never for a fixed time |
| 4 | Free site trial notice | Not applicable: scene 08 is recorded on Pro (see above) |
| 5 | No update badges or notices | WordPress 7.1.2, WooCommerce 11.1.2, all plugins and themes updated; WooCommerce notices cleared; the WooPayments incentive badge and UpdraftPlus's "thank you" notice dismissed through the plugins' own mechanisms; welcome panel off; the WordPress home screen (scene 02's first frame) tidied through the account's Screen Options — four neutral widgets, one per column (WooCommerce Status, At a Glance, Recent Reviews, Quick Draft), Email Log's widget off through its own setting. The recorder refuses to record while any notice, bubble or spinner is on screen |
| 6 | Badge reads "Data Verified ✓", no "Loading…", no "Failed to load…" | Part of the recorder's readiness audit for every scene and again after the click in scene 02 |

Recording account: the throw-away admin `pdwchs-shot` (display name "Sarah M.", default avatar). Delete it when the series is done.

### Re-shooting

Any clip can be re-made: `node project_asset/marketing/tooling/record-scenes.mjs --video v1 --only scene-04`. Re-shoot on a day the store was refreshed (`demo-refresh.sh`), and if the answer frame is re-shot on another day, re-read `delivery-note.md` before the voice is recorded: a different biggest card means script options B / D.

---

## For the editor

Hi! You receive eight finished screen-recording clips of one WordPress screen, the narration as audio files (one per beat), two stills and four text files. Nothing needs recording; you lay the audio, cut the picture to it, zoom and caption. If a clip or an audio file looks or sounds different from what this brief describes, stop and tell Yeasir — don't work around it.

**About the clips.** 3840 × 2160, 30 fps, H.264 (MP4). They were made from a 1920 × 1080 screen at double resolution so your zooms stay sharp: a 2× zoom in a 1080p timeline is still pixel-perfect. The cursor is drawn with a soft amber ring; where a clip is a still hold, the picture does not change and that is intended. Clips start 3 s before the first movement and hold at least 5 s on every box the viewer must read.

### A) What is in the folder

| File | What it is |
|---|---|
| `scene-01.mp4` … `scene-08.mp4` | The clips, one per scene (table below) |
| `answer-frame.png` | The full frame at the home position, 3840 × 2160 — the picture behind beat 1 and the end card |
| `answer-frame-crop.png` | The same frame cropped to the heading "Revenue by Customer Type" plus the three boxes — the beat-1 zoom and the poster |
| `delivery-note.md` | Which box is biggest, where the guest box sits, the clip table. No amounts, by rule |
| `voice-over.md` | The **caption script**: the exact words of the narration with the two on-screen choices already made, correct spelling, emphasis words listed. Captions come from this file |
| `voice-over-elevenlabs.md` | The **voice script** the audio was generated from — same words, with pause tags and the chunk plan. Not for captions |
| `voice/V1_b1.wav` … `V1_b5.wav` (`b3a`, `b3b`, `b3c`, `b4a`, `b4b` split beats) | The narration, one file per chunk, generated with ElevenLabs. `_t2` means a replacement take: use the newest |
| `captions.srt` | Draft burned-in captions at the script's timings, emphasis words in `<b>`; re-time to the audio |

### B) Scene table (as recorded)

| Clip | Beat | Screen, in plain words | What happens in the clip | Planned length | Hold on the subject |
|---|---|---|---|---|---|
| **scene-01 ★** | 1 (reused in 5 and the end card) | **Answer frame:** the Revenue Analytics box with the three boxes NEW CUSTOMERS, RETURNING CUSTOMERS, GUEST ORDERS near the bottom | Nothing moves. Cursor parked in the empty header space between the title and the date box, outside the beat-1 crop | 12 s | 12 s |
| scene-02 | 2 | From the WordPress home screen to the three boxes | 3 s still, the cursor moves to **Customer History** in the dark left menu and clicks, the page loads (a short "Loading…" you will trim), then one slow scroll down to the home position, then 5 s still | about 28 s (the load varies) | 5 s at the end |
| scene-03 | 2 | The title "Revenue Analytics" | Cursor moves to the words Revenue Analytics and rests just to their right | 11 s | 6 s |
| scene-04 | 3 | The three boxes, one by one | Cursor rests in the middle of the NEW CUSTOMERS box, on the amount's row beside the number (2 s), then RETURNING CUSTOMERS (2 s), then GUEST ORDERS (2 s), then parks below the boxes | 24 s | 2 s each, then 10 s |
| scene-05 | 3 | The date box, then back to the boxes | Cursor rests beside the small date box (two dates and a calendar icon) — it is never clicked — then moves down and rests below the three boxes | 24 s | 6 s, then 12 s |
| scene-06 | 3 and 4 | **The biggest box** — RETURNING CUSTOMERS on this recording | Cursor rests in the middle of that box, on the amount's row beside the number, never over it | 24 s | 20 s |
| scene-07 | 3 and 4 | The GUEST ORDERS box | Cursor rests in the middle of the box, beside the number | 19 s | 15 s |
| scene-08 | 5 | The "Repeat Purchase Rate" box on the Revenue page | Cursor rests beside the small grey caption "Registered customers only", on its row | 11 s | 6 s |

The exact length of each clip is in `delivery-note.md`.

### C) How the clips were made (so you know what to expect)

- One clip per scene, named to match the table. Every clip was recorded only after the screen was checked: no spinner, no notice, no update bubble, every box showing an amount and an "orders" line, the badge reading "Data Verified ✓".
- The scroll in scene-02 is a single slow scroll in one direction. No clip opens Settings, Plugins or any other tab, and no clip scrolls down to the boxes below "Revenue Analytics" (Retention, Lifetime value, Customer Segments, Health, Churn and others). Never zoom to those areas.
- The numbers, names and dates on screen are synthetic demo data; the voice-over never reads a number out, so give the viewer time to read them.

### D) Edit notes

**Audio first.** Lay the chunks from `voice/` on the timeline in script order with a short gap between them (about 0.3–0.5 s, more where the picture needs a hold), then cut the picture to the audio: the times below were estimates, the audio is the truth. Captions come from `voice-over.md` / `captions.srt`, never from the voice script.

| Script beat · time | Use | Zoom |
|---|---|---|
| 1 · 0:00–0:05 | scene-01 | `answer-frame-crop.png` framing: the heading "Revenue by Customer Type" plus the three boxes; the four top boxes and the "Data Verified" badge are out. Lower third: **PureDevs Customer History** |
| 2 · 0:05–0:12 | scene-02 | Follow the click on Customer History, then the scroll. Trim the load wait |
| 2 · 0:12–0:20 | scene-03 | The "Revenue Analytics" title |
| 3 · 0:20–0:35 | scene-04 | The three boxes, left to right |
| 3 · 0:35–0:54 | scene-05 | The date box, then pan down to the three boxes |
| 3 · 0:54–1:06 | scene-06, then scene-07 | The biggest box (hold at least 5 s), then the guest box |
| 4 · 1:06–1:18 | scene-07 | The guest box |
| 4 · 1:18–1:33 | scene-06 | The biggest box. A thin brand-colour outline is fine; no arrows |
| 5 · 1:33–1:39 | scene-01 | Zoom out to all three boxes |
| 5 · 1:39–1:42 | scene-08 | The "Repeat Purchase Rate" box and its caption — tight on the box, never wide enough to show the row of tabs above |
| 5 · 1:42–1:46 | End card | Still from scene-01, cropped as in beat 1 + **PureDevs Customer History** + **Start the trial · puredevs.com/pricing** |

- Burned-in captions from `captions.srt` (the script's lines, emphasis words in bold), re-timed to the audio.
- Normalise the voice track once, not chunk by chunk (about −14 LUFS integrated, peaks at or below −1 dBTP).
- The master must stay within 60–120 s. If it passes 2:00, tell Yeasir before exporting.
- No intro longer than 2 s. One CTA, on the end card only.
- **Poster and thumbnail:** built from `answer-frame-crop.png` to Script v1.2 §C: 1280 × 720, under 2 MB, the question as text in three lines, a thin outline on the biggest box (Returning), a small "Demo store" label, no arrows.
- Export the master at 1080p. Cut the derivatives listed in the Placement Kit (§1, the 20–30 s cut).

### E) Delivery

Send back: the 1080p master, the 20–30 s cut, the poster (1280 × 720), and a note with the final length of the master (it fills the Placement Kit's `[LENGTH]`) plus anything you had to change from this brief.

---

## F) Sanity check

| # | Check | Result |
|---|---|---|
| 1 | Every scene maps to a script line and a Brief entry | 01 → beats 1, 5a, end card (DASH-10) · 02 → beat 2a (A1, §3.1, NAV-01) · 03 → beat 2b · 04 → beat 3a · 05 → beat 3b · 06 → beats 3c, 4b · 07 → beats 3c, 4a · 08 → beat 5b (REV-02). **PASS** |
| 2 | Every path comes from Brief §3, not memory | Customer History → Dashboard (`pd-woo-dashboard`) · Revenue (`pd-woo-revenue`). **PASS** |
| 3 | Every screen confirmed | On the live store by the recorder's probe on 28 Sep (geometry and readiness), not from the PDF. **PASS** |
| 4 | No scene shows a future feature | DASH-10 and REV-02 only, both in 3.1.2. **PASS** |
| 5 | No expected values written into a scene | Scenes and clips are described by structure; the delivery note names boxes, never amounts. **PASS** |
| 6 | Demo values differ on another day | The recorder resolves A/B and C/D from the screen and writes them to the note; `voice-over.md` says to re-read it before a re-shoot. **Handled** |
| 7 | Home position keeps Retention off screen | Computed and checked on every scene that uses it. **PASS** |
| 8 | Scene 08 on Pro is honest | Same template file as the free build; the zoom excludes the tab bar (edit note). **PASS** |
| 9 | Answer frame ≥ 8 s; every box hold ≥ 5 s | 12 s; every hold ≥ 5 s except the 2 s glances in scene-04, which are followed by a 10 s hold. **PASS** |
| 10 | Product name in the first frame and end card; one CTA | Edit notes. **PASS** |
| 11 | Update badges, notices, cursor over numbers | Machine-checked before every clip (the UpdraftPlus "thank you" notice and the WooPayments badge were both caught and dismissed through the plugins' own mechanisms). Rest points checked at full resolution: on each card the cursor sits at the card's centre on the amount's row, beside the left-aligned number, never over it. **PASS** |
| 12 | The narration speaks only the approved words | `voice-over-elevenlabs.md` is checked word for word against `voice-over.md` (239 words, identical; only `[PAUSE]` → break tags); options A and C only; no figures, trial terms, cap, competitor names or "only / first"; one CTA. **PASS** |

**Verdict:** **PASS.** Ready for the audio, then the editor.

---

## Changelog

- **v1.4 — 28 Sep 2026.** Narration generated with an ElevenLabs clone of Yeasir's voice instead of recorded (owner decision, ElevenLabs workflow v1.1): `voice-over.md` becomes the caption script, `voice-over-elevenlabs.md` the voice script, audio chunks in `voice/`; edit notes now say audio first, cut picture to it, captions from the caption script, loudness and length rules; sanity row 12.
- **v1.3 — 28 Sep 2026.** Recording moved from a human editor on two sites to the scene recorder on the local store; scene 08 recorded on Pro; `[DEV SETUP]` replaced by what `verify` and the recorder check; delivery reversed (the editor receives clips and returns the master, cut and poster); edit notes carry the file names.
- **v1.2 — 21 Sep 2026.** Follows Script v1.2 (about 1:46): edit-note times moved; scene 04 and 05 holds lengthened to cover the longer beat 3.
- **v1.1 — 21 Sep 2026.** Screens confirmed against the Brief's own screenshots; trial-notice setup corrected; capture-width and callout notes added.
- **v1.0 — 21 Sep 2026.** First version.
