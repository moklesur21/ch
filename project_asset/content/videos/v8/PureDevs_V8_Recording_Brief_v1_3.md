# V8 Recording & Edit Brief — Where does my store data go when I install this?

**Version 1.3 · 7 Oct 2026** · Demo Video Prompt Pack v3, FILE 4 · supersedes v1.2
From `PureDevs_V8_Script_v1_2.md` and `PureDevs_V8_Demo_Brief_v1_1.md`. The scene recorder (`project_asset/docs/video-production.md` §5) plays `scenes.json` in this folder on the throw-away store **ch-v8** (`http://localhost/ch-v8`), never on `ch`. The narration is generated with ElevenLabs from `recordings/voice-over-elevenlabs.md`. **The whole video is made here** (owner, 7 Oct): the voice through the ElevenLabs API (`voice-gen.mjs`), the edit, captions, music bed and click sounds by `render-edit.mjs` from `edit.json` (`project_asset/docs/video-pipeline-guide.md`). The VA only uploads the approved master.

> **STATUS: EDITED WITH VOICE, 7 Oct 2026.** Seven segments in `recordings/edit/`, each carrying its voice; `V8_reference_master.mp4` = all of them joined with the captions burned in.
> - **Length:** 1:56.8, 1080p30.
> - **Loudness:** −14.0 LUFS, peaks −4.6 dBTP.
> - **API pilot, 7 Oct night:** re-voiced through the API (one take per chunk, all word-perfect), music bed, clicks and typing added: `V8_reference_master.mp4` 1:56.3 (−14.4 LUFS) and `V8_cut_27s.mp4` 26.7 s, both QC PASS. The web-UI-voice version is kept as `V8_reference_master_webui.mp4` / `V8_cut_27s_webui.mp4`.
> - **Next:** gate D — Yeasir picks a version. Then the Placement Kit timecodes and the FILE 6 audit on that master, then the VA uploads it.
---

## For Yeasir — the VA can skip this part

### How V8 differs from V1

1. **A throw-away store.**
   - ch-v8 is the same Northline store (products, customers, orders), but the plugin and Freemius have never been on it. That's the only way to film a real first install.
   - `scripts/v8-site.sh` builds it, checks it (`check`), returns it to the pre-install snapshot (`reset`, about 8 s) and updates it (`update`).
2. **One take, real time, a clock on screen.**
   - Scene-02 runs Plugins → Add Plugin → search → Install Now (a real WordPress.org download) → Activate → Freemius opt-in → **Skip** → Dashboard, unedited.
   - The mm:ss clock bottom-right runs across every page load.
3. **The shopper is a second, logged-out browser** (scene-03). Admins are never recorded on Free.
4. **Order matters:** 02 → 03 → 04 → 05 → 01. Scenes 03–05 and 01 show data that scene-02 and scene-03 create, so a retake of the install means `reset` and the whole chain again.
5. **The latest WordPress, always** (owner rule, 7 Oct). The store runs WordPress 7.1.3. If WordPress or WooCommerce ships an update before the take, run `scripts/v8-site.sh update` first.

### `[DEV SETUP]` — checked 7 Oct 2026

| # | Item | How it is met | State |
|---|---|---|---|
| 1 | Plugin never installed; Freemius never seen | ch-v8 + `check` → `pre_files`, `pre_options`, `pre_tables`, `pre_usermeta` | PASS |
| 2 | Latest WordPress, no update bubbles | WordPress 7.1.3, WooCommerce 11.1.2; `check` → `env_wp`, `cam_updates` | PASS |
| 3 | The wp.org card reads "Compatible" | `check` → `org_tested`: wp.org says tested to 7.0.7. **Owner decision, 7 Oct: not blocking.** The card shows "Untested…" in the full-frame take; the narration makes no compatibility claim. Dev request 1 stays open | WARN |
| 4 | The first Dashboard has orders to read | `data_orders`: completed / processing orders, some in the last 30 days | PASS |
| 5 | A shopper can search | Product search added to the Northline header (git-tracked theme, 7 Oct); "hoodie" returns several products | Done |
| 6 | Camera hygiene | Shot user "Sarah M." (first name for Freemius's "Hey Sarah,"), welcome panel off, four neutral WordPress home widgets, no WooCommerce notices, store live (not "coming soon") | Done |
| 7 | Search counted once | The shopper reaches the second product through the "Hoodies" breadcrumb, not Back: the plugin's PHP session sends no-cache headers, so Back reloads the results and records the search again | Done |
| 8 | 30-minute caches | Search Analytics' Top Keyword, Last Searches and Most Viewed are live queries; scene-04 reaches them from the WordPress home screen, never via the plugin Dashboard (cached at install) | Done |

### Running the real take (Claude asks Yeasir before each run: Install Now downloads the plugin from wp.org)

```bash
scripts/v8-site.sh update
```
```bash
scripts/v8-site.sh reset
```
```bash
scripts/v8-site.sh check
```
```bash
node project_asset/marketing/tooling/record-scenes.mjs --video v8
```
```bash
node project_asset/marketing/tooling/make-end-card.mjs --video v8
```
```bash
scripts/v8-site.sh reset
```

- `check` must show **0 FAIL** (warnings are allowed). The recorder runs it again before scene-02 and refuses the take on any FAIL.
- `--rehearsal` overrides that refusal and writes to `recordings/rehearsal/` instead.
- The last `reset` puts the store back for a retake.

### Rehearsal, 7 Oct — what it showed

| Run | Result | Fix |
|---|---|---|
| 1 | Take OK (65 s); shopper scene timed out; recorder crashed | Product titles sat below the fold, so the click landed off screen → explicit scrolls, and the recorder now refuses any off-screen target. A failed scene left the screencast running → it now always stops |
| 2 | All five scenes; "hoodie" counted twice | Back reloads the search → breadcrumb instead (setup item 7). Cursor rest points moved off the far edge and off the Site Health values |
| 3 | All five scenes clean: "hoodie" once, both products, "None reached yet" | Dashboard hold extended to 9 s afterwards, so the "No account…" line has picture |
| **Real take** | Pre-take check PASS; all five scenes; the screens showed the expected facts (`delivery-note.md`) | Scene-05 re-shot the same day (admin-only, no reinstall): the cursor now starts above Tools, so it never crosses "Export / Erase Personal Data" in the expanded Tools menu. Scene-01 re-shot to measure the "hoodie" row for the end card |

---

## The edit — done here, not by the editor

`node project_asset/marketing/tooling/render-edit.mjs --video v8` renders every segment from `edit.json`. Every time in it comes from the recorder's step log (`recordings/scene-NN.steps.json`), so the speed ranges and zooms start on the exact click, not by eye.

| Segment | From | What is baked in | Length now |
|---|---|---|---|
| `V8_b1` | answer-frame still | Framed on the page content; title **PureDevs Customer History** (bottom-right, clear of the product rows); "Demo store · sample data" | 5.0 s |
| `V8_b2` | scene-02, **the one take** | Never cut. **Full frame while the install runs, so the clock is always visible.** Only the waits run faster, each with a **"1.5× speed" / "3× speed" badge** above the clock: page loads, the search spinner, "Installing…", the two redirects. The Freemius screen and every click play at 1×. **Once the Dashboard has loaded (the take is complete), the picture zooms onto Latest Orders.** That keeps the upsell banner out (Demo Brief Q3), and the clock leaves the frame only after the take has ended | 40.1 s |
| `V8_b3a` | scene-03 | "Logged-out visitor" title; zoom on the header search while "hoodie" is typed; the browsing after the search at 4× (no clock in this scene, so no badge) | 12.3 s |
| `V8_b3b` | scene-04 | Zooms: tables with "hoodie" → Most Viewed Products → the Heads-up notice | 16.9 s |
| `V8_b3c` | scene-05 | Zoom onto the PureDevs Customer History section once it opens | 11.8 s |
| `V8_b4` | answer-frame still | Slow pull-out from the "hoodie" table to the whole page | 19.5 s |
| `V8_b5` | answer-frame still + `end-card.mp4` | Held, then a 0.5 s crossfade into the end card **Install free · WordPress.org** | 10.0 s |

- **Speeds are up to 3×, not 2× as v1.0 planned.** At 2× the master ran over 2:00. The clock and the badge show every speed-up on screen, and nothing inside the take is cut.
- **"Demo store · sample data"** sits bottom-left on every segment except the end card, which has its own label.
### The voice, conformed (7 Oct)

1. **Loudness, the same for every chunk.** Yeasir's nine ElevenLabs takes (`audio/*.mp3`, untouched) were joined and run through one fixed chain, then split back at exact sample boundaries into `recordings/voice/V8_*.wav`. The chain is in `recordings/voice/PROCESSING.txt`: a gentle gate for the pauses, light compression, +20 dB, a limiter at −2 dBFS. Result: −14.0 LUFS integrated, pauses kept at about −64 dB (as in the originals; a dynamic normaliser had lifted them to −37 dB), and the stereo upmix at −3 dB per channel, so the file still reads −14 LUFS.
2. **Phrases on their moments.** `conform-voice.py` finds each phrase from the pauses and maps the recorder's click times through the speed ranges. It then writes the placements into `edit.json` and re-times `recordings/captions.srt`:
   - `V8_b2a` is split into six pieces: "Here's the whole install, in one take" · "Plugins," on the Plugins click · "Add Plugin." on its click · "Search." as typing starts · "Install." on Install Now · "Activate." on Activate.
   - `V8_b2b` is placed so "We skip it." starts on the Skip click; `V8_b2c` follows it.
   - `V8_b3b` is split across the three tables; `V8_b3c` across Tools → Info → the opened section.
   - The end card fades in just before "Install it free".
3. **The captions** are burned into the reference master: white on a dark box, emphasis words bold amber, clear of the "Demo store" label, the clock and the badge. In the Site Health clip they sit at the top (`captionsTop`): that section fills the bottom of a page too short to scroll, so no zoom can lift it clear of a bottom caption.

To redo after a re-generated chunk:

```bash
python3 project_asset/content/videos/v8/conform-voice.py
```
```bash
node project_asset/marketing/tooling/render-edit.mjs --video v8 --master
```

## For the VA

Nothing to edit. Upload the master Yeasir approves at gate D (`recordings/edit/V8_reference_master.mp4`, or the `_webui` version if he picks that one) with the texts from `PureDevs_V8_Script_v1_2.md` §B, per the Placement Kit.

**Poster and thumbnail:** built from `recordings/answer-frame-crop.png` to Script v1.2 §C (Claude can render it from `poster.html`, as for V1).

---

## F) Sanity check

| # | Check | Result |
|---|---|---|
| 1 | Every scene maps to a script line and a Brief entry | 01 → beats 1, 4, 5 (A8, SA-01–05) · 02 → beat 2 (core S1, Freemius S2, A1) · 03 → beat 3a (B1) · 04 → beat 3b (A8, SA-01–03, SA-05) · 05 → beat 3c (SYS-01). **PASS** |
| 2 | Every path from Brief §3 or WordPress core, not memory | Plugins → Add Plugin (WordPress 7.1.3 `menu.php`) · Customer History → Search Analytics (`pd-woo-search-analytics`) · Tools → Site Health → Info. **PASS** |
| 3 | Every screen confirmed on the store | Rehearsal 3, 7 Oct, ch-v8, free build 3.1.2 from wp.org. **PASS** |
| 4 | No future feature; Free screens only | Search Analytics is Free-only; Site Health reads "Active tier: Free". **PASS** |
| 5 | Cut lines never on screen as a subject | Export / Erase Personal Data, Settings, Customers page, phpMyAdmin: not in any clip. Zero-Result Searches is visible on the page but never zoomed or narrated. **PASS** |
| 6 | Owner rule: latest WordPress | 7.1.3, `env_wp` PASS; `update` before any take. **PASS** |
| 7 | One take is honest | One clip, never cut; the clock is visible for the whole install. Waits only at 1.5×–3×, with a badge on screen. The zoom starts only after the Dashboard has loaded. **PASS** |
| 8 | Freemius: never Allow & Continue | The recorder clicks only `#skip_activation`. The SDK code shows Skip stores a local flag and sends nothing. **PASS** |
| 9 | The card's "Untested…" line | Visible in the full-frame take. Owner decision 7 Oct: not blocking. The narration makes no compatibility claim; dev request 1 stays open. **Accepted** |
| 10 | Answer frame ≥ 8 s; every hold ≥ 5 s | 12 s; reading holds 5–9 s. The 3 s product holds in scene-03 are glances, not reads. **PASS** |
| 11 | Product name in the first frame and end card; one CTA | Edit notes; end card "Install free · WordPress.org". **PASS** |
| 12 | Narration = approved words | Script v1.2, `voice-over.md` and `voice-over-elevenlabs.md` all checked word for word by script: 200 words, identical. **PASS** |
| 13 | Length and loudness | 1:56.8 with the voice (under 2:00); −14.0 LUFS, peaks −4.6 dBTP. **PASS** |
| 15 | Words on their moments | Checked on frames from the master: "Plugins, Add Plugin." over the Plugins menu, "Install." on Install Now, "We skip it." on the Skip click, the Dashboard line over Latest Orders, "hoodie" and "two products" on their rows, the quote over the opened section, "Install it free" on the end card. **PASS** |
| 16 | Captions = the approved words | `captions.srt` is generated from the same 200 words (Script v1.2 = `voice-over.md`); 27 lines; two script lines split at sentence ends ("There's "hoodie"." / "And there are…"; "Tools, Site Health, Info:" / the quote) so each lands on its moment. No word changed. **PASS** |
| 14 | Things that must not be on screen | Export / Erase Personal Data: the cursor no longer crosses it (scene-05 re-shot); the menu item text itself shows in the expanded Tools menu, never highlighted. The upsell banner: zoomed out after the take. **PASS** |

**Verdict:** **PASS.** Recorded, edited, voiced and captioned. Next: Yeasir's review of the reference master, then the Placement Kit (FILE 5) and the FILE 6 audit on the final export.

---

## Changelog

- **v1.3 — 7 Oct 2026.** No CapCut step (the CapCut guide was removed): the VA only uploads. Status: the API-voice pilot with music and clicks, awaiting gate D.
- **v1.2 — 7 Oct 2026.** Voice conformed: one loudness chain for all chunks (−14 LUFS, quiet pauses kept); phrases placed on their clicks by `conform-voice.py`; captions re-timed and burned into `V8_reference_master.mp4` (1:56.8); the title moved clear of the captions; Site Health captions at the top. Sanity rows 13, 15, 16.
- **v1.1 — 7 Oct 2026.** Real take recorded (owner: the "Tested up to" warning does not block). The edit moves here: `render-edit.mjs` + `edit.json` make finished segments, and the VA stitches in CapCut (`capcut-va-guide.md`). Speeds up to 3× with a badge; Dashboard zoom after the take; scene-05 re-shot so the cursor avoids Export / Erase Personal Data.
- **v1.0 — 7 Oct 2026.** First Recording Brief for V8, written from rehearsal 3 on ch-v8 (WordPress 7.1.3, free build 3.1.2 from wp.org).
