# PureDevs — ElevenLabs Voiceover Workflow

**Version 1.1 · 28 Sep 2026** · supersedes v1.0 · Applies to every demo video (V1, V8, V13 …)
**Works with:** `PureDevs_Demo_Video_Prompt_Pack_v3.md` · each video's Script (FILE 3) and Recording Brief (FILE 4)
**Owner decision, 28 Sep 2026:** narration uses an **ElevenLabs Instant Voice Clone of Yeasir's own voice**, generated from the founder-written script. Pronunciation test passed (owner, 28 Sep 2026). The script style stays the same: second person, short plain sentences.

---

## 0. The whole flow on one page

| # | Step | Who | When | Output |
|---|---|---|---|---|
| 1 | One-time setup (§1). **Done:** plan, clone, pronunciation test. **Left:** lock the settings in the voice log, store the sample files, turn on two-factor login | Yeasir | Before V1's voice-over | Filled voice log (§1.6), saved to the project |
| 2 | Script approved | Claude + Yeasir | FILE 3 | `PureDevs_Vx_Script_vN.md` |
| 3 | Screen recording | Editor | FILE 4 | Clips + delivery note (which box is biggest, where Guest sits) |
| 4 | Resolve the script options (`[A]`/`[B]`, `[C]`/`[D]`) and build the **voice script** and the **caption script** (§2) | Yeasir (or ask Claude) | After the delivery note | Two text files |
| 5 | Generate the audio, one chunk per beat (§3) | Yeasir | Same day | One audio file per chunk |
| 6 | Listen and check every chunk (§4) | Yeasir | Same day | Approved takes |
| 7 | Download and name the files (§5) | Yeasir | Same day | `Vx/voice/` folder |
| 8 | Edit: audio first, then cut picture to the audio; captions from the caption script (§6) | Editor | After step 7 | Master video |
| 9 | Publish. No AI disclosure needed for an own-voice clone (§8) | Yeasir | Upload day | Live video |
| 10 | Any later fix: re-render only the affected chunk with the logged voice and settings (§7) | Yeasir | When needed | Replacement chunk |

**The one rule that matters most:** the audio is made **after** the recording and **before** the picture edit. The script's timings were estimates, so the real audio sets the cut.

---

## 1. One-time setup (once for the series)

### 1.1 Plan

| Plan | Price | Credits / month | Commercial licence | Use it? |
|---|---|---|---|---|
| Free | $0 | 10k | **No** | **Never** for these videos |
| **Starter** | $6 / month | 30k | Yes | **Yes** — enough for this series (§9) |
| Creator | $22 / month | 121k | Yes | Only if a month needs heavy re-renders |

Starter includes Text to Speech, **instant voice cloning** (used for this series), 20 Studio projects, and 128 kbps / 44.1 kHz audio. Prices exclude tax. Plans change; check the pricing page when renewing.

### 1.2 Model: **Eleven Multilingual v2**

| Model | Credits per character | Pauses | Speed slider | Best for | Choice |
|---|---|---|---|---|---|
| **Multilingual v2** | 1 | **Exact break tags, up to 3 s** | Yes, 0.7–1.2 | Steady, long-form narration | **Use this** |
| Eleven v3 | 1 | No break tags; punctuation and audio tags only | No | The most expressive delivery | Only if v2 sounds flat |
| Flash v2.5 | about 0.5 | Break tags | Yes | Speed and low cost | No — lower quality |

**Why Multilingual v2:**
- Exact, repeatable pauses.
- A speed control.
- More consistent from take to take, which matters when one beat is re-rendered weeks later and must match the others.

It works with Instant Voice Clones. Eleven v3 has no break tags, no Similarity and no Speed setting, and varies more between takes.

### 1.3 The voice: your own clone

| Item | Rule |
|---|---|
| **Type** | Instant Voice Clone of Yeasir's voice, made 28 Sep 2026 from his own recordings |
| **Where** | *My Voices* in ElevenLabs. Keep it **private**; never share it to the Voice Library |
| **Voice ID** | Copy it into the voice log (§1.6). Re-renders need the ID, not the name |
| **Sample recordings** | Keep the exact files used to make the clone, backed up in two places, with their file names in the voice log. If the clone ever has to be rebuilt, rebuild it from **these same files** |
| **Never re-create mid-series** | A new clone from new recordings sounds different, so re-rendered chunks wouldn't match the rest. If a rebuild is unavoidable, re-run the pronunciation test and re-render whole videos (§7) |
| **Security** | Turn on two-factor login. The account now holds a copy of your voice |
| **Accent** | The clone keeps your accent; the words and pacing come from the script. Accepted by the owner after the test |

**Fallback, only if the clone can't be used:**
- Pick one of ElevenLabs' **default voices**: native English, calm, clear, mid-pace. Not a Voice Library voice, because Library voices can be withdrawn by their owners after a notice period.
- Log its ID, and re-render whole videos, never single chunks.

### 1.4 Settings (lock the values from your passing test)

Write the values you used in the passing test into the voice log. That's the locked set. The table only helps if something sounds off later.

| Setting | Typical value | Adjust if … |
|---|---|---|
| **Model** | Multilingual v2 | — |
| **Stability** | 50–60 | Two takes of the same chunk sound different → raise toward 70 (ElevenLabs' own narration preset). Sounds flat or robotic → lower toward 50 |
| **Similarity** | 75 (default) | Doesn't sound enough like you → try 80–85. You hear room noise, hiss or artifacts from your sample → lower toward 60. ElevenLabs notes that higher similarity "may amplify audio artifacts or background noise from the original recording" |
| **Style exaggeration** | 0 | Keep at 0. ElevenLabs recommends it |
| **Speaker boost** | On | ElevenLabs says it "enhances voice clarity and similarity to the original" — leave it on unless it causes artifacts |
| **Speed** | 1.0 | Rushed against the screen → 0.95. Extreme values reduce quality |
| **Standard pause** | `<break time="0.7s" />` | Before a decision or CTA line: `1.0s`. Never above 3 s |

**Consistency check** (once, when locking): generate the same chunk twice with the locked settings. The two takes should sound like the same person on the same day. If not, raise Stability and check again.

Settings may be changed between generations, but **never mid-video**. Every chunk of every video uses the locked values.

### 1.5 Pronunciation test — passed 28 Sep 2026

**Test paragraph** (re-run it if the model or the clone ever changes):

> This is a WooCommerce demo store running PureDevs Customer History. Open wp-admin, then Customer History. It's HPOS-compatible and self-hosted, with no SaaS account. Licensing runs through Freemius. You can export a CSV in a Klaviyo or Mailchimp layout. The free version is on WordPress dot org.

**Terms, and respellings to use if a word ever comes out wrong:**

| Term | Should sound like | Respelling to try |
|---|---|---|
| WooCommerce | woo-KOM-ers | `Woo Commerce` |
| PureDevs | pure devs | `Pure Devs` |
| wp-admin | W-P admin | `W P admin` |
| HPOS | H-P-O-S | `H P O S` |
| Freemius | FREE-mee-us | `Free-mee-us` |
| SaaS | sass | `sass` |
| CSV | C-S-V | `C S V` |
| Klaviyo | KLAY-vee-oh | `Clay-vee-oh` |
| WordPress.org | WordPress dot org | Always write `WordPress dot org` |

**How respelling works:**
- Multilingual v2 doesn't support phoneme (IPA / CMU) tags, so pronunciation is fixed by respelling the word in the **voice script only**. Captions keep the correct spelling (§2.1).
- Write every respelling you needed into the voice log, and reuse it in every video.
- **Never read a URL aloud.** Point to "the link below" instead.

**Optional — Studio instead of respelling by hand.** ElevenLabs Studio supports pronunciation dictionaries. Alias rules work with every model. One Studio project per video, one paragraph per chunk, applies the respellings automatically. Starter includes 20 Studio projects. Use it only if hand-respelling becomes a chore.

### 1.6 Voice log

Fill this in now and save it in the project. Every re-render reads from it.

| Field | Value |
|---|---|
| Date locked | |
| Model | Eleven Multilingual v2 |
| Voice type | Instant Voice Clone — own voice |
| Voice name (in *My Voices*) | |
| Voice ID | |
| Clone created | 28 Sep 2026 |
| Sample files (names + where stored) | |
| Pronunciation test | Passed 28 Sep 2026 |
| Stability | |
| Similarity | |
| Style exaggeration | 0 |
| Speaker boost | On / Off |
| Speed | |
| Standard pause | 0.7 s (1.0 s before decision / CTA) |
| Respellings needed | e.g. `PureDevs → Pure Devs` (or "none") |
| Two-factor login | On |
| Plan | Starter |

---

## 2. Formatting a script for ElevenLabs (every video)

### 2.1 Make two versions of the resolved script

| Version | Used for | Spelling | Cues |
|---|---|---|---|
| **Caption script** | Burned-in captions, question-page walkthrough, YouTube transcript | Correct (WooCommerce, PureDevs) | None; bold words kept for the editor |
| **Voice script** | Pasting into ElevenLabs | Respelled as the voice log says | Break tags only |

Both come from the approved Script (FILE 3) **after** the options are resolved from the editor's delivery note.

### 2.2 Conversion rules

| In the script | In the voice script |
|---|---|
| `[SCREEN: …]`, `[ZOOM: …]`, time codes, table columns, caption-emphasis notes | Delete |
| `[PAUSE]` | `<break time="0.7s" />` (use `1.0s` before a decision or CTA line) |
| `[A: …]` / `[B: …]`, `[C: …]` / `[D: …]` | Keep **only** the chosen option's words; delete the brackets and the other option |
| `[New / Returning]` inside an option | Keep only the word the delivery note gives |
| Sentence end | Keep the period. It gives a natural full stop |
| Comma | Keep. It gives a short breath |
| Em-dash `—` | Keep. It gives a short break mid-sentence. Don't add extra dashes or ellipses for pacing; ElevenLabs calls those less consistent than break tags |
| A new script row (sub-beat) inside one chunk | Start a new line |
| Product and tech names | Respell per the voice log |
| Numbers, prices, percentages, dates | Should not exist (owner rule: no spoken figures). If one appears, the script is wrong — stop and fix the script, not the audio |
| URLs | Never spoken ("the link below") |

**Limits per chunk:**
- At most **3 break tags**. Too many in one generation can make the voice speed up or add noise.
- About **60 words**. Split a longer beat at a sub-beat boundary (V1's beat 3 becomes chunks 3a and 3b).

### 2.3 Worked example (V1, beat 2)

**Script (FILE 3):**
> This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics. / This is a Pro report. [PAUSE] It's self-hosted — nothing leaves your server. It reads your store's own orders.

**Caption script:**
```
This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report. It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

**Voice script** (showing a respelling, in case the voice log lists one for "PureDevs"):
```
This is a WooCommerce demo store running Pure Devs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report. <break time="0.7s" /> It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

### 2.4 Check before pasting

- [ ] Only one option from each `[A]`/`[B]` and `[C]`/`[D]` pair is left.
- [ ] No spoken figures anywhere.
- [ ] No trial terms in the narration ("30-day", "no card"). They live in the description and on the page, because a published video can't be edited.
- [ ] No customer cap, no competitor names, no "only / first".
- [ ] One CTA line, in beat 5.
- [ ] Every sentence matches the approved script word for word, apart from the respellings.

---

## 3. Generating (Text to Speech page)

1. Log in and open **Text to Speech**.
2. Select the **model** from the voice log (Multilingual v2).
3. Select **your clone** from *My Voices*. Check the voice ID matches the voice log.
4. Open the settings and set every value from the voice log. Check them every session; the page can remember other values.
5. Paste **chunk 1** of the voice script.
6. Check the credit cost shown before generating.
7. **Generate.**
8. Listen (§4).
9. If the take isn't right and the text hasn't changed, **regenerate**. ElevenLabs gives two free regenerations within 2 hours if the voice, text and model stay the same. Keep the settings locked between them.
10. If a word is wrong in every take, fix its respelling, add it to the voice log and generate again. A changed text costs credits again.
11. Keep the best take. Move to the next chunk.
12. Repeat for every chunk, in script order, in **one session**, so the takes sit close together in tone.

**Chunk plan per video:** one chunk per beat; split beat 3 (and any beat over about 60 words or 3 breaks). A 5-beat script usually becomes 6 chunks.

**Generation limit:** paid plans allow up to 5,000 characters per generation on the website. Chunks are far below that.

---

## 4. Listening check (every chunk, on headphones)

- [ ] Every word matches the voice script: nothing skipped, repeated or added.
- [ ] Names sound as the voice log says.
- [ ] Pauses land where the `[PAUSE]` marks were.
- [ ] Pace is unhurried. A viewer can read the screen while listening.
- [ ] Emphasis falls on the caption-emphasis words, or at least not against them.
- [ ] It sounds like you all the way through: no drift in accent or tone between takes.
- [ ] No clicks, breaths in odd places, metallic artifacts, hiss or trailing noise.
- [ ] Energy and tone match the chunk before it. Play the two back to back.
- [ ] The last word isn't clipped.

**Take-selection rule:** clarity first, then natural pacing, then tone match with neighbouring chunks. Expressiveness last.

**Optional:** before publishing the first video, have one other person listen to it once.

---

## 5. Downloading and naming

- **Format:**
  - WAV, if the download menu offers it on your plan.
  - Otherwise MP3 at the highest bitrate offered. Starter lists 128 kbps.
  - Past generations stay in **History**, where the advanced options may offer more formats.
- **Names:** `V1_b1.wav`, `V1_b2.wav`, `V1_b3a.wav`, `V1_b3b.wav`, `V1_b4.wav`, `V1_b5.wav`. Add `_t2` for a later replacement take.
- **Folder:** `V1/voice/`, next to the editor's `V1/` scene clips. Include:
  - the voice script;
  - the caption script;
  - a short note: date generated and "voice log unchanged" (or what changed).

---

## 6. Editing (for the editor)

1. **Audio first.** Lay the chunks on the timeline in order, with a short gap between chunks (about 0.3–0.5 s, more where the picture needs a hold).
2. **Cut the picture to the audio.**
   - Stretch holds to cover the narration.
   - Never cut a hold on a card, chart or table below **5 seconds**; the Recording Brief's minimums still apply.
   - The answer frame stays at least 8 s.
3. **Captions** come from the **caption script**, never the voice script, with the caption-emphasis words in bold.
4. **Loudness:** a common target for YouTube is about −14 LUFS integrated, with peaks at or below −1 dBTP. Normalise the whole voice track once, not chunk by chunk.
5. **Length check:**
   - The master must stay within **60–120 s** (pack U8).
   - If it's over **2:00**, tell Yeasir: the "2-minute demo" ad lines then can't run.
6. **The 20–30 s cut** reuses the same chunks. No new audio is needed.

---

## 7. Re-renders (when something changes later)

Typical triggers:
- the demo store flips from option A to B;
- a claim audit changes a word;
- WooCommerce changes and the honest sentence needs rewording.

1. Change only the affected sentence in the caption script and the voice script.
2. Open the voice log. Use the **same model, voice ID and settings**.
3. Re-render **only the affected chunk**. Play it next to the chunk before and after, and check the tone matches.
4. Replace it in the timeline. Update the captions.
5. Re-check the total length (§6.5).
6. Note the change in the video's folder note.

**If ElevenLabs retires the model, or the clone has to be rebuilt:**
- Don't patch one chunk.
- Rebuild the clone from the **same sample files**, re-run the pronunciation test (§1.5), re-render whole videos, and update the voice log.

---

## 8. Publishing — YouTube AI disclosure

**Not needed for these videos.** YouTube's help page lists "Cloning one's own voice to create voice overs or dubs" under content creators **don't need to disclose**. The picture is a real screen recording, labelled "Demo store".

- In YouTube Studio's upload details, answer the AI-use / altered-content question (the help page places it under **Attributes**) for what the video contains: an own-voice clone and a real recording → **No**.
- Re-read the help page's two lists before each upload; YouTube changes this policy.
- If a viewer or customer asks, answer plainly: "The narration is my own voice, generated from my script with ElevenLabs."

---

## 9. Budget

Per video, for a 1:40-ish script of about 240 words:

| Item | Credits |
|---|---|
| First pass (about 1,400 characters + break tags) | ≈ 1,500 |
| Two free regenerations per chunk (same text) | 0 |
| Respelling fixes and extra takes | ≈ 500–1,500 |
| **Per video** | **≈ 2,000–3,000** |
| Clone and pronunciation test | Done |

At one video a week, that's about 10–15k credits a month. Starter's 30k covers it with room for re-renders. The counter on the page is the source of truth.

---

## 10. How this differs from the generic guide

| Generic guide | This workflow | Why |
|---|---|---|
| Chunks of 200–500 characters | One chunk per beat; split beats over about 60 words or 3 breaks | Matches the scene table; one beat can be re-rendered alone |
| Strip all cues | Delete `[SCREEN]`/`[ZOOM]`; **convert** `[PAUSE]` to break tags; **resolve** `[A]`/`[B]` | Pauses are deliberate; stripping options leaves both in |
| Line breaks and ellipses for pacing | `<break time="0.7s" />` | Documented and exact on Multilingual v2; ElevenLabs calls dashes and ellipses less consistent |
| Spell out prices, percentages, versions | No figures are spoken at all | Owner rule: no spoken figures |
| Pick Adam, Antony or Rachel | Your own voice clone; log its **voice ID**; keep the sample files | Founder voice for the series; re-renders need the ID; a rebuild needs the same samples |
| Stability 40–50 | Lock the passing-test values (usually 50–60; up to 70 for steadier takes) | Series consistency over expressiveness |
| "Similarity Boost" | "Similarity", 75; lower if your sample's noise shows through | Current name; ElevenLabs' own caution for clones |
| "3x variation button" | Two free regenerations within 2 hours (same voice, text and model) | Current documented behaviour |
| Respell mispronounced words | Respell in the **voice script only** | Captions must stay correctly spelled |
| Download and sync in the editor | Audio first, then cut picture to the audio | Script timings were estimates |
| — | Voice log, pronunciation test, clone care, re-render rules | A series, not a one-off |

---

## 11. Rule changes to log

The owner decision changes wording in these files. Update them at their next revision:

| File | Current wording | Change to |
|---|---|---|
| `PureDevs_Proof_Marketing_Ad_Strategy_v1.md` §4.2 | "Voice: founder narration, own voice, conversational" | "Voice: founder narration, own voice — generated from the founder's script with an ElevenLabs clone of his voice — conversational." Log in §15 as an owner decision, 28 Sep 2026 |
| `PureDevs_Demo_Video_Prompt_Pack_v3.md` U8 | "Founder voice" | "Founder voice (own-voice clone; see the ElevenLabs voiceover workflow)" |
| Prompt Pack FILE 3 A) | "Voice: founder, second person …" | Keep the writing style; add "Also output the caption script and the voice script (ElevenLabs workflow §2)" |
| Prompt Pack FILE 4 D) | Voice-over recorded after the footage | "Voice generated after the footage, before the picture edit; cut picture to the audio" |
| `PureDevs_V1_Script_v1_2.md` | Voice line in A) | As the Prompt Pack FILE 3 row |
| `PureDevs_V1_Recording_Brief_v1_2.md` D) | "Yeasir picks the spoken options … match captions to the lines he records" | "… match captions to the caption script" |

---

## 12. Remaining setup and decisions

**Still to do:**

| # | What | When |
|---|---|---|
| 1 | Fill the voice log (§1.6) with the passing-test settings and the voice ID; save it to the project | Before V1's voice-over |
| 2 | Back up the clone's sample recordings in two places; note their names in the voice log | Now |
| 3 | Turn on two-factor login in ElevenLabs | Now |

**Resolved on 28 Sep 2026:**
- **Voice:** own-voice clone.
- **YouTube disclosure:** not needed (§8).
- **V8 exception:** moot; the whole series uses your voice.

---

## Sources (checked 28 Sep 2026)

- [ElevenLabs — Text to Speech product guide](https://elevenlabs.io/docs/eleven-creative/playground/text-to-speech): models, settings and defaults, limits, free regenerations, download formats
- [ElevenLabs — Voice settings reference (skills repo)](https://github.com/elevenlabs/skills/blob/main/text-to-speech/references/voice-settings.md): similarity and artifacts, stability narration preset, speaker boost
- [ElevenLabs — Best practices](https://elevenlabs.io/docs/overview/capabilities/text-to-speech/best-practices): break tags, pronunciation, normalisation, speed range, v3 differences
- [ElevenLabs — How can I add pauses?](https://elevenlabs.io/docs/help-center/product/core-capabilities/text-to-speech/how-can-i-add-pauses): 3 s maximum, models, too-many-breaks caveat
- [ElevenLabs — Pronunciation dictionaries](https://elevenlabs.io/docs/eleven-api/guides/how-to/text-to-speech/pronunciation-dictionaries): alias vs phoneme, model support
- [ElevenLabs — What is a notice period?](https://elevenlabs.io/docs/help-center/product/voices/voice-library/what-is-a-notice-period): Voice Library withdrawal (fallback voices)
- [ElevenLabs — Pricing](https://elevenlabs.io/pricing): plans, credits, commercial licence, instant voice cloning, audio quality
- [Voxrater — ElevenLabs models compared (June 2026)](https://voxrater.com/insights/elevenlabs-models-compared/): credits per character by model (third-party)
- [YouTube Help — Disclosing use of GenAI content](https://support.google.com/youtube/answer/14328491?hl=en): what to disclose and what not to, including own-voice clones
- [YouTube Blog — Disclosing AI-generated content (Mar 2024)](https://blog.youtube/news-and-events/disclosing-ai-generated-content/)

ElevenLabs changes its models, names and plans often. Re-check the settings table and plan if anything looks different in the app.

---

## Changelog

- **v1.1 — 28 Sep 2026.** Owner decision updated: narration uses an Instant Voice Clone of Yeasir's own voice; pronunciation test passed. §1.3 rewritten for the clone (private, sample files kept, never re-created mid-series, two-factor login; default voices only as fallback). §1.4 settings tuned for a clone using ElevenLabs' own guidance (similarity vs sample noise, speaker boost on, narration stability). §1.6 voice log gains clone fields. §4 adds an accent/tone drift check. §7 adds the clone rebuild rule. §8: YouTube disclosure not needed (own-voice clones are exempt). §11 strategy change reduced to "own voice via clone". §12 now lists remaining setup; disclosure and V8 decisions resolved.
- **v1.0 — 28 Sep 2026.** First version: stock AI voice replacing founder narration.
