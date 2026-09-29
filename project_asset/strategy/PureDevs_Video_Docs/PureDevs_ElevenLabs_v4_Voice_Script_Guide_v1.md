# PureDevs — ElevenLabs v4 Voice Script Guide

**Version 1.0 · 29 Sep 2026** · How to turn an approved video script into text for **Eleven v4**, and how to generate the voice from it.

---

## Part 1 — Converting a script

### 1.1 What you start with and what you make

- **Start with** the approved script, after the editor's delivery note has decided the options (`[A]`/`[B]`, `[C]`/`[D]`).
- **Keep that text as it is.** It's the caption text.
- **Make a copy for ElevenLabs** — the *voice script*. Every change in this guide happens only in that copy.

### 1.2 The one rule

**Never change, add or remove a word.** The audio must match the approved, claim-checked script and the captions, word for word.

Only five things may change in the voice script:

| Allowed change | When |
|---|---|
| `[PAUSE]` → `[short pause]` | Wherever the script has `[PAUSE]` (§1.4) |
| One delivery tag at the start of each chunk | If the first-time test keeps it (§1.5, §2.3) |
| A new line between script rows | In any chunk with more than one row |
| One word in CAPITALS | Only to fix a take that stresses the wrong word (§1.6) |
| A pronunciation fix for a name | Only for names the voice gets wrong (§1.7) |

### 1.3 Conversion table

| In the script | In the voice script |
|---|---|
| Beat headings, time codes, `[SCREEN: …]`, `[ZOOM: …]`, caption-emphasis notes | Delete |
| `[A: …]` / `[B: …]`, `[C: …]` / `[D: …]` | Keep only the chosen option's words; delete the brackets and the other option |
| `[New / Returning]` inside an option | Keep only the word the delivery note gives |
| `[PAUSE]` | `[short pause]` |
| Full stops, commas, question marks, semicolons, em-dashes `—` | Keep exactly as written |
| A new script row inside one chunk | Start a new line |
| Product and tech names | As written, unless the voice gets one wrong |
| Numbers, prices, percentages, dates, version numbers | Should not exist: no figure is spoken in these videos. If one appears, the script is wrong — send it back; don't spell it out |
| URLs | Never spoken ("the link below") |

### 1.4 Pauses

Eleven v4 doesn't support break tags such as `<break time="0.7s" />`. ElevenLabs says to control pauses with audio tags, punctuation and text structure.

- **Default:** `[short pause]`, written after the full stop, exactly where the script had `[PAUSE]`:
  `This is a Pro report. [short pause] It's self-hosted — nothing leaves your server.`
- **Fallback:** if the voice says "short pause" aloud, or makes a sound instead of pausing, replace the full stop before the pause with an ellipsis:
  `This is a Pro report… It's self-hosted — nothing leaves your server.`
  ElevenLabs notes ellipses add a pause *and weight*, so they can sound hesitant. That's why they're second choice.
- **Pick one method in the first-time test (§2.3) and use it everywhere.** Never mix the two in one video.
- **One pause strength only.** Don't use `[long pause]`; its length is unpredictable. If a pause needs to be longer, the editor widens it.
- **Never add a pause the script doesn't have.** Commas, full stops and new lines already give natural breaths.

### 1.5 Delivery tag

One tag at the very start of every chunk keeps the tone steady:

```
[calm, clear, conversational tone]
```

- **Same tag, same wording, in every chunk of every video.** Changing it between chunks changes the voice.
- **Nowhere else.** No tags mid-chunk, and no mood change per sentence (`[confident]`, `[thoughtful]`, `[direct]` …). That gives an uneven, performed read.
- **It must describe the voice.** ElevenLabs warns that v4 can take a vague tag as a request for a sound effect. Describing the voice quality avoids that.
- **Never** sound-effect tags (`[applause]`), action tags (`[smiling]`, `[standing]`) or playful tags (`[laughs]`, `[giggles]`). ElevenLabs notes a serious voice doesn't respond well to playful tags.
- **The first-time test decides whether to keep it at all** (§2.3). If kept, use it always; if dropped, never.

### 1.6 Emphasis

In v4, capital letters increase emphasis (ElevenLabs).

- **Default: no capitals.** Write every word as the script does.
- **Fix only:** if every take stresses the wrong word, capitalise the one word that should carry the stress, then regenerate. Example:
  `Now read the PROPORTION, not the number.`
- **At most one capitalised word per chunk.**
- **Never capitalise a short word that could be read as letters** (`PRO`, `NEW`) or a product name. It may get spelled out.
- **Never add exclamation marks or extra ellipses.** They push the voice toward an announcer read.

### 1.7 Pronunciation

Only when the voice gets a name wrong. Two ways:

- **Respelling:** best for letter-by-letter acronyms and names that split into ordinary words.
- **IPA:** Eleven v4 reads International Phonetic Alphabet symbols wrapped in slashes. Copy ElevenLabs' format exactly, double quotes included: `"/ˈfriːmiəs/"`. Best for made-up names. ElevenLabs says IPA results can still vary by voice, so generate twice and keep the better take.

| Term | Should sound like | Respelling | IPA |
|---|---|---|---|
| WooCommerce | woo-KOM-ers | `Woo Commerce` | — |
| PureDevs | pure devs | `Pure Devs` | — |
| wp-admin | W-P admin | `W P admin` | — |
| HPOS | H-P-O-S | `H P O S` | — |
| CSV | C-S-V | `C S V` | — |
| WordPress.org | WordPress dot org | `WordPress dot org` | — |
| Freemius | FREE-mee-us | — | `"/ˈfriːmiəs/"` |
| Klaviyo | KLAY-vee-oh | — | `"/ˈkleɪvioʊ/"` |
| SaaS | sass | `sass` | `"/sæs/"` |

- The fix goes in the **voice script only**. Captions keep the real spelling.
- Once a fix works, write it into the locked choices (§2.6) and use it in every video.

### 1.8 Never add

- **Words.** No "down", "your", "exact", "click here".
- **Exclamation marks.**
- **Ellipses**, except as the pause fallback.
- **Any tag** other than the one delivery tag and `[short pause]`.
- **Spelled-out numbers, trial terms** ("thirty-day", "no card") **or URLs.**
- **The Enhance button's output.** Enhance keeps the words, but adds tags, capitals, exclamation marks and ellipses.

### 1.9 Chunks

- **One chunk per script paragraph** (usually one per beat), so one part can be regenerated alone.
- **About 60 words at most.** Split a longer paragraph at a sentence boundary.
- **Every chunk starts with the delivery tag**, if the test kept it.
- **Name each chunk** after the video and beat: `V1_b1`, `V1_b2`, `V1_b3a` …

### 1.10 Worked example (V1, beat 2)

**Approved script (also the captions):**
> This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
> This is a Pro report. `[PAUSE]` It's self-hosted — nothing leaves your server. It reads your store's own orders.

**Voice script — `V1_b2`:**
```
[calm, clear, conversational tone] This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report. [short pause] It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

**The same chunk with the ellipsis fallback and a respelling fix:**
```
[calm, clear, conversational tone] This is a WooCommerce demo store running Pure Devs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report… It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

### 1.11 Check before pasting

- [ ] Only the chosen option of each pair is left.
- [ ] Every word matches the approved script.
- [ ] `[short pause]` (or the ellipsis) appears exactly where `[PAUSE]` was, and nowhere else.
- [ ] One delivery tag, at the very start — or none, if the test dropped it.
- [ ] No exclamation marks, no extra ellipses, no other tags.
- [ ] No figures, trial terms or URLs.
- [ ] Capitals and pronunciation fixes only where a take needed them.

---

## Part 2 — Generating the voice

### 2.1 Model

**Eleven v4.** Not Eleven v4 Turbo, which ElevenLabs built for real-time voice agents; v4 is the one for content creation.

### 2.2 Voice and settings

- **Voice:** your clone, from *My Voices*.
- **v4 has only two settings: Stability and Similarity.** There is no Style or Speed slider; ignore any guide that mentions them.

| Setting | What it does (ElevenLabs) | Start | Adjust if … |
|---|---|---|---|
| **Stability** | Lower: more expressive, varied delivery. Higher: closer to a fixed baseline | The page's default | Two takes of the same chunk differ in energy or pace, or the read sounds dramatic → higher. Flat or robotic → lower. A very high setting may follow the delivery tag less |
| **Similarity** | Higher: closer to your reference voice, at some cost to naturalness | The page's default | Sounds stiff, or picks up flaws from your original recording (hiss, harsh "s", pops) → one step lower. Doesn't sound enough like you → one step higher |

Change settings only in the first-time test or to fix a problem, never mid-video.

### 2.3 First-time test

Run once, before the first video. Repeat after any model update: ElevenLabs says v4 is still being trained and its behaviour may shift. Also repeat if takes start sounding different from earlier videos.

1. Take one chunk that has a `[PAUSE]` (for V1: `V1_b2`).
2. Generate it **with** the delivery tag, then **without** it. Keep whichever sounds steadier and more like you.
3. Check the pause. If `[short pause]` is spoken aloud or makes a sound, switch to the ellipsis fallback (§1.4).
4. Listen to every product name in the script. Add fixes (§1.7) only for names that come out wrong.
5. Generate the same chunk twice more with your chosen settings. The takes should sound like the same person on the same day. If not, raise Stability and repeat.
6. Write your choices into §2.6.

### 2.4 Generating a video

1. Open **Text to Speech**. Select **Eleven v4** and your clone. Check both settings against §2.6.
2. Paste the **first chunk** only.
3. **Generate** and listen (§2.5).
4. If the take isn't right, **regenerate** without editing the text. ElevenLabs gives two free regenerations within 2 hours when the text, voice and model stay the same.
5. If a problem repeats in every take, fix the text as §2.5 says, then generate again. Changed text costs credits.
6. Keep the best take. Download it as **WAV**, named after the chunk (`V1_b2.wav`).
7. Repeat for every chunk, in order, in **one session**, so all takes match in tone.

### 2.5 Listen to every take

| Check | If it fails |
|---|---|
| Every word is there, as written — nothing skipped, repeated or added | Regenerate |
| The tag isn't spoken and makes no sound | Regenerate. If it repeats, drop the tag for the whole video and redo from the first chunk |
| Pauses land where `[PAUSE]` was | Switch to the ellipsis fallback for the whole video |
| Names sound right | Add a pronunciation fix (§1.7) |
| Stress falls on the right words | Capitalise the one word that should carry it (§1.6) |
| Calm and conversational, not dramatic | Regenerate. If it repeats, raise Stability |
| Same energy and pace as the chunk before | Regenerate. If it repeats, raise Stability |
| No hiss, clicks, harsh "s" or pops | Regenerate. If it repeats, lower Similarity one step |
| The last word isn't clipped | Regenerate |
| Every take is too fast | Pace comes mostly from the recording your clone was made from (ElevenLabs); a calmer, slower sample fixes it. Don't add pauses to compensate |

### 2.6 Locked choices

Fill this in after the first-time test. Every chunk of every video uses it.

| Choice | Value |
|---|---|
| Model | Eleven v4 |
| Voice | *(your clone's name)* |
| Stability | |
| Similarity | |
| Delivery tag | `[calm, clear, conversational tone]` / none |
| Pause method | `[short pause]` / ellipsis |
| Pronunciation fixes | *(term → fix)*, or "none" |
| Date tested | |
