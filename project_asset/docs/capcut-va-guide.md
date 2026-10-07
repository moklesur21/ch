# CapCut guide for the VA — stitching a PureDevs demo video

**Version 1.1 · 7 Oct 2026** · for CapCut desktop (Mac or Windows). Menu names can differ slightly between CapCut versions; if a button isn't where this guide says, look for the same word in the panel on the right.

## What you get, and what you do

Claude delivers finished picture clips: every speed-up, zoom, label and the end card is already done. You:
1. put the clips in order;
2. lay the voice files on them (unless the clips already carry the voice: then skip step 2);
3. add the captions;
4. check;
5. export.

**You don't record anything and you don't change the picture.** If something looks wrong, stop and tell Yeasir; don't fix it in CapCut.

| File | What it is |
|---|---|
| `V8_b1.mp4`, `V8_b2.mp4` … (`recordings/edit/`) | The picture clips, in order. 1920 × 1080, 30 fps. Named after the voice chunks they carry |
| `picture.mp4` | All clips already joined, for reference: watch it once before you start |
| `voice/V8_b1.wav` … | Yeasir's voice, one file per chunk. `_t2` = a replacement take: always use the newest |
| `captions.srt` | The captions: exact words and spelling, timed to the finished video |
| `voice-over.md` | The same words as text. Your reference when you check captions |

## 1. New project

1. Open CapCut → **Create project**.
2. Drag every file listed above into the **Media** panel (top-left).
3. **Project settings:** click **Modify** on the right panel (or the gear by the project name) and set:
   - **Ratio:** 16:9;
   - **Resolution:** 1080p;
   - **Frame rate:** 30 fps.
4. **Turn off** anything automatic: Auto reframe, Enhance, any filter or effect. The picture is finished.

## 2. Put the clips in order

1. Drag the clips onto the **main track** one after another, **in the order of their names** (`V8_b1`, `V8_b2`, `V8_b3a`, `V8_b3b`, `V8_b3c`, `V8_b4`, `V8_b5`).
2. Leave no gaps and add no transitions: the end card's fade is already inside the last clip.
3. Check the total length against the table at the end of this guide (± 1 s).

## 3. Lay the voice (skip if the clips already have sound)

1. Drag each `V8_….wav` onto the **audio track** under the picture.
2. Put each one at the **start time** in the V8 table below. Move the playhead to the time, then drag the clip until its left edge snaps to the playhead.
3. **Splitting one file into pieces** (only where the table says so):
   - Play the file and stop the playhead in the silent gap after the sentence.
   - Press **Split**: the scissors icon, or Cmd+B (Mac) / Ctrl+B (Windows).
   - Drag the piece to its time.
   - Never cut inside a word, and never delete or re-order words.
4. **Volume:** select all the voice clips → **Audio** panel on the right → turn on **Normalize loudness**. If your version asks for a target, use −14 LUFS.
5. No music and no sound effects.

**If the voice runs longer than its picture,** don't speed the voice up and don't cut words. Tell Yeasir: Claude re-times the picture to the voice. For a gap under 1 s at the end of a still clip (`V8_b1`, `V8_b4`, `V8_b5`), you may stretch it instead: select the clip, drag its right edge out.

## 4. Captions

1. **Import the file:** open **Captions** (or **Text → Captions**) → **Import captions** / **Local captions** → choose `captions.srt`. The lines land on a captions track, already timed.
2. **If your CapCut has no import:** use **Auto captions** (language English), then correct **every** line against `voice-over.md`. The words must match it exactly: product names, "wp-admin", "SaaS", "Freemius", the quotation marks around "hoodie".
3. **Style:** select all captions → **Text** panel:
   - font bold sans-serif (CapCut's default is fine), white text, a black box or outline behind it;
   - size large enough to read on a phone (about 7–8 % of the frame height);
   - position: bottom centre, but **above** the "Demo store · sample data" label in the bottom-left corner. Never cover it.
4. **Emphasis words:** where the caption script marks words in bold (`<b>…</b>` in the .srt), make those words bold or amber (#FFC107) if your CapCut supports per-word styling. If it doesn't, skip it. Never change the words.
5. Captions must never cover the clock (bottom-right in `V8_b2`) or the speed badge above it. If they do, move the captions up slightly for that clip only.

## 5. Check before you export

- [ ] Play the whole video once with sound.
- [ ] Every voice line is on the picture it describes. In `V8_b2`, "We skip it." lands as the cursor clicks **Skip**.
- [ ] The captions match the voice word for word (`voice-over.md`).
- [ ] The total length is within ± 1 s of the table, and under 2:00.
- [ ] The first frame shows "PureDevs Customer History" (it's in `V8_b1`), and the video ends on the end card.
- [ ] Nothing was added: no music, stickers, transitions, CapCut watermark or CapCut outro.

## 6. Export

- **Export** (top-right). Name: `V8_master`.
- **Resolution:** 1080p · **Frame rate:** 30 fps · **Format:** MP4 · **Codec:** H.264 · **Bit rate:** Recommended or Higher.
- **Audio:** AAC, 48 kHz if offered. Leave "Export audio only" off.
- **Captions:** export with the captions burned in (on the video). If CapCut offers "Export captions" as a separate file too, tick it and send that SRT as well.
- **Also export the 20–30 s cut** when the Placement Kit lists it: a separate project made from the same clips, built exactly as the kit says.

**Send back:**
- `V8_master.mp4`;
- the cut;
- one line with the final length, plus anything you had to change.

## 7. Only if Claude asks you to touch the picture

| Task | How |
|---|---|
| Speed up a part of a clip | Split at the start and end of the part → select the middle piece → **Speed → Normal** → set the value (e.g. 2.0x). Add a text "2× speed" on that piece, top-right of the clock, if the clip has a clock |
| Zoom in on an area | Select the clip → **Video → Basic → Scale** and **Position**. To move smoothly, set a **keyframe** (diamond icon) at the start and another where the zoom should end |
| Hold a frame longer | Playhead on the frame → right-click → **Freeze** (adds a still of about 3 s; drag its edge to the length you need) |
| Cut a wait out | Never in a clip with a clock (the one-take clip). Elsewhere: split before and after, delete the middle, close the gap |

---

## V8 — "Where does my store data go when I install this?"

**Status, 7 Oct 2026: the clips carry the voice.** Skip step 3 of this guide. Everything is in `project_asset/content/videos/v8/recordings/edit/`.

**Two ways to finish. Yeasir decides which:**
- **A — the reference master, as it is.** `V8_reference_master.mp4` is complete:
  - every clip joined;
  - the voice laid in at −14 LUFS;
  - the captions burned in;
  - 1:56.8, 1080p, 30 fps.

  If it passes Yeasir's review, it is the master. Your job: upload, plus the checks in §5.
- **Already made, beside it:**
  - `V8_cut_27s.mp4`: the 20–30 s cut, for the retargeting ads;
  - in `../`: `V8-poster.png` (the YouTube thumbnail), `V8-poster.webp` (the web pages), the three `V8-shot-*` screenshots (PNG and WebP), `V8-search-analytics.gif`, `transcript.txt`.

  Upload texts (title, description, chapters, pinned comment, tags): `PureDevs_V8_Script_v1_2.md` §B. Where each piece goes: `PureDevs_V8_Placement_Kit_v1_0.md`.
- **B — rebuild it in CapCut** (for CapCut's caption styling, or a change Yeasir asks for): follow steps 1, 2, 4, 5 and 6 with the clips below. Captions come from `../captions.srt`.

| # | Clip | Starts at | Length | What it shows (voice inside) |
|---|---|---|---|---|
| 1 | `V8_b1.mp4` | 0:00.0 | 5.0 s | The question over the answer: Search Analytics with "hoodie" and two products. Title "PureDevs Customer History" |
| 2 | `V8_b2.mp4` | 0:05.0 | 42.6 s | **The one-take install**, clock bottom-right: Plugins → Add Plugin → search → Install → Activate → Freemius → Skip → Dashboard. Waits at 1.5× / 3× with a badge. Each word lands on its click. Ends zoomed on Latest Orders |
| 3 | `V8_b3a.mp4` | 0:47.7 | 11.1 s | A logged-out shopper searches "hoodie" and opens two products |
| 4 | `V8_b3b.mp4` | 0:58.8 | 16.8 s | wp-admin → Search Analytics: "hoodie", the two products, the "Heads up" notice |
| 5 | `V8_b3c.mp4` | 1:15.5 | 11.0 s | Tools → Site Health → Info → PureDevs Customer History. **Captions at the top of the frame for this clip only**: the section fills the bottom |
| 6 | `V8_b4.mp4` | 1:26.5 | 18.1 s | The answer frame, slow pull-out: the decision |
| 7 | `V8_b5.mp4` | 1:44.6 | 12.1 s | The answer frame, then the end card "Install free · WordPress.org" |
| | **Total** | | **1:56.7** | |

**Checks specific to V8 (on top of §5):**
- [ ] "We skip it." lands on the click on Skip (about 0:32).
- [ ] The clock bottom-right is visible from 0:05 until the Dashboard (about 0:34).
- [ ] The speed badge shows whenever the install runs fast.
- [ ] No caption covers the clock or the badge.
- [ ] In the Site Health clip (1:15–1:27) the captions are at the top, and "None reached yet" stays readable.

## Changelog

- **v1.1 — 7 Oct 2026.** V8 section: the clips carry the voice; the reference master is option A; final clip times.
- **v1.0 — 7 Oct 2026.** First version, with the V8 section.
