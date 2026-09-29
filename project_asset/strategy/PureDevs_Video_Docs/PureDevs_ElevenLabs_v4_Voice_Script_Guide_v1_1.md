# PureDevs — ElevenLabs v4 Voice Script Guide

**Version 1.2 · 29 Sep 2026** · How to turn an approved video script into text for **Eleven v4**, and how to generate the voice from it.
*1.2 — after the owner's ear tests: `[thoughtful]` on every chunk; ellipses for pauses; about three reveal pauses per video.*

---

## Part 1 — Converting a script

### 1.1 What you start with and what you make

- **Start with** the approved script, after the editor's delivery note has decided the options (`[A]`/`[B]`, `[C]`/`[D]`).
- **Keep that text as it is.** It's the caption text.
- **Make a copy for ElevenLabs** — the *voice script*. Every change in this guide happens only in that copy.

### 1.2 The one rule

**Never change, add or remove a word.** The audio must match the approved, claim-checked script and the captions, word for word.

Only these things may change in the voice script:

| Allowed change | When |
|---|---|
| `[thoughtful]` at the start of each chunk | Every chunk (§1.5) |
| `[PAUSE]` → `...` | Wherever the script has `[PAUSE]` (§1.4) |
| A reveal pause (`...`) before the key words | Your choice, on lines that carry the point (§1.6) |
| One long key word in CAPITALS | Optional (§1.6) |
| A new line between script rows | In any chunk with more than one row |
| A pronunciation fix for a name | Only for names the voice gets wrong (§1.7) |

### 1.3 Conversion table

| In the script | In the voice script |
|---|---|
| Beat headings, time codes, `[SCREEN: …]`, `[ZOOM: …]`, caption-emphasis notes | Delete |
| `[A: …]` / `[B: …]`, `[C: …]` / `[D: …]` | Keep only the chosen option's words; delete the brackets and the other option |
| `[New / Returning]` inside an option | Keep only the word the delivery note gives |
| `[PAUSE]` | `...` in place of the full stop before it (§1.4) |
| Full stops, commas, question marks, semicolons, em-dashes `—` | Keep as written, apart from the ellipses this guide adds |
| A new script row inside one chunk | Start a new line |
| Product and tech names | As written, unless the voice gets one wrong |
| Numbers, prices, percentages, dates, version numbers | Should not exist: no figure is spoken in these videos. If one appears, the script is wrong — send it back; don't spell it out |
| URLs | Never spoken ("the link below") |

### 1.4 Pauses

Eleven v4 doesn't support break tags such as `<break time="0.7s" />`. ElevenLabs says to control pauses with audio tags, punctuation and text structure.

- **Default:** where the script has `[PAUSE]`, replace the full stop before it with an ellipsis:
  `This is a Pro report... It's self-hosted — nothing leaves your server.`
  An ellipsis gives a short, natural beat. ElevenLabs says ellipses add "pauses and weight".
- **If the voice runs straight through an ellipsis in every take,** write `[short pause]` after the full stop at that spot instead:
  `This is a Pro report. [short pause] It's self-hosted — nothing leaves your server.`
- **One pause strength only.** Don't use `[long pause]`; its length is unpredictable. If a pause needs to be longer, the editor widens it.
- **The only other extra pause is the reveal pause (§1.6).** Commas, full stops and new lines already give natural breaths.

### 1.5 Delivery tag

Start every chunk with:

```
[thoughtful]
```

- **Tested on 29 Sep 2026:**
  - no tag sounded generic and AI-like;
  - `[calm, clear, conversational tone]` sounded forced;
  - `[thoughtful]` sounded like a person asking.
  `[thoughtful]` is also one of the direction tags ElevenLabs' own Enhance feature uses.
- **Same tag at the start of every chunk, and nowhere else.** No mood change per line (`[confident]`, `[direct]` …): that gives an uneven read between chunks.
- **Exception, by ear:** if one chunk sounds too slow or pensive with the tag (most likely the click path or the call to action), generate it without the tag. Keep whichever matches the chunk before it.
- **Never** sound-effect tags (`[applause]`), action tags (`[smiling]`, `[standing]`) or playful tags (`[laughs]`, `[giggles]`). ElevenLabs notes a serious voice doesn't respond well to playful tags, and warns that vague tags can be taken as sound effects.

### 1.6 Emphasis — the beat before the key words

**First choice: a reveal pause.** Put an ellipsis right before the words that carry the point of the line:

`How much of your WooCommerce revenue... comes from returning customers?`

A short beat before the important words sounds like a person choosing them. Capitals only make a word louder.

- **When — your call, by importance.** Use it on the lines that carry the point of the beat: the opening question, the answer, the decision, the closing line. Skip it on click-path and navigation lines and on plain facts. The same line may work better with or without it; when unsure, generate both and keep the more natural.
- **At most one reveal pause per chunk.** More turns it into a mannerism.
- **Rule of thumb: about three per video**, one each at the opening question, the product's proof and the decision (V1 uses b1, b3b, b4b).
  - **Skip it** where a question mark already makes the beat ("And guests? The smallest card.").
  - **Skip it** in a chunk that already has two `[PAUSE]` ellipses.
  - **Skip it** next to the call to action, so the ending stays brisk.
- **Never inside a name**, and never right next to a `[PAUSE]` ellipsis.
- **Each one adds a moment.** Check the finished video still runs under 2:00.

**Optional: capitals on one long key word per chunk**, for example `RETURNING`.
- ElevenLabs' v4 guide says capitals increase emphasis. The claim that v4 turns capitals back into lower case isn't in ElevenLabs' docs. Either way, capitals on a long word are harmless.
- **Never capitalise a short word that could be read as letters** (`PRO`, `NEW`) or a product name. It may get spelled out.

**Never add exclamation marks.** They push the voice toward an announcer read.

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
- **Ellipses anywhere else** — only at `[PAUSE]` spots and one reveal pause per chunk.
- **Any tag** other than `[thoughtful]` (and `[short pause]` where an ellipsis fails).
- **Spelled-out numbers, trial terms** ("thirty-day", "no card") **or URLs.**
- **The Enhance button's output.** Enhance keeps the words, but adds tags, capitals, exclamation marks and ellipses everywhere.

### 1.9 Chunks

- **One chunk per script paragraph** (usually one per beat), so one part can be regenerated alone.
- **About 60 words at most.** Split a longer paragraph at a sentence boundary.
- **Every chunk starts with `[thoughtful]`.**
- **Name each chunk** after the video and beat: `V1_b1`, `V1_b2`, `V1_b3a` …

### 1.10 Worked examples (V1)

**Beat 1 — a reveal pause and one capitalised word**

Approved script (also the captions):
> How much of your WooCommerce revenue comes from returning customers?

Voice script — `V1_b1` (the owner's tested take):
```
[thoughtful] How much of your WooCommerce revenue... comes from RETURNING customers?
```

**Beat 2 — a `[PAUSE]`, no reveal (click path)**

Approved script:
> This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
> This is a Pro report. `[PAUSE]` It's self-hosted — nothing leaves your server. It reads your store's own orders.

Voice script — `V1_b2`:
```
[thoughtful] This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report... It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

### 1.11 Check before pasting

- [ ] Only the chosen option of each pair is left.
- [ ] Every word matches the approved script.
- [ ] `[thoughtful]` at the very start, and no other tag.
- [ ] An ellipsis at every `[PAUSE]` spot.
- [ ] At most one reveal pause, and only on a line that carries the point.
- [ ] Capitals, if any, on one long word — never "Pro" or a name.
- [ ] No exclamation marks, figures, trial terms or URLs.

---

## Part 2 — Generating the voice

### 2.1 Model

**Eleven v4.** Not Eleven v4 Turbo, which ElevenLabs built for real-time voice agents; v4 is the one for content creation.

### 2.2 Voice and settings

- **Voice:** your clone, from *My Voices*.
- **v4 has only two settings: Stability and Similarity.** There is no Style or Speed slider; ignore any guide that mentions them.

| Setting | What it does (ElevenLabs) | Start | Adjust if … |
|---|---|---|---|
| **Stability** | Lower: more expressive, varied delivery. Higher: closer to a fixed baseline | The page's default | Two takes of the same chunk differ in energy or pace, or the read sounds dramatic → higher. Flat or robotic → lower. A very high setting may follow the tag less |
| **Similarity** | Higher: closer to your reference voice, at some cost to naturalness | The page's default | Sounds stiff, or picks up flaws from your original recording (hiss, harsh "s", pops) → one step lower. Doesn't sound enough like you → one step higher |

Change settings only when fixing a problem, never mid-video.

### 2.3 Re-test after model updates

The tag and pause choices were tested on 29 Sep 2026 (§2.6). ElevenLabs says v4 is still being trained and its behaviour may shift. Repeat this after an ElevenLabs model update, or if takes start sounding different from earlier videos:

1. Generate Beat 1 of any script **without** a tag, then **with** `[thoughtful]`. Keep whichever sounds more like a person asking.
2. Generate a chunk with a `[PAUSE]`. The ellipsis should give a clear beat.
3. Listen to every product name. Add fixes (§1.7) only for names that come out wrong.
4. Generate the same chunk twice more. The takes should sound like the same person on the same day. If not, raise Stability.
5. Update §2.6.

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
| The tag isn't spoken and makes no sound | Regenerate |
| Sounds like a person talking, not reading | Check there's only `[thoughtful]`, and no extra ellipses or capitals |
| A clear beat at each `[PAUSE]` spot | Use `[short pause]` at that spot (§1.4) |
| The key words land | Add a reveal pause before them (§1.6), or capitals on one long word |
| Names sound right | Add a pronunciation fix (§1.7) |
| Too slow or pensive for this line | Try this chunk without the tag (§1.5) |
| Calm, not dramatic | Regenerate. If it repeats, raise Stability |
| Same energy and pace as the chunk before | Regenerate. If it repeats, raise Stability |
| No hiss, clicks, harsh "s" or pops | Regenerate. If it repeats, lower Similarity one step |
| The last word isn't clipped | Regenerate |
| Every take is too fast | Pace comes mostly from the recording your clone was made from (ElevenLabs); a calmer, slower sample fixes it |

### 2.6 Locked choices

Every chunk of every video uses these.

| Choice | Value |
|---|---|
| Model | Eleven v4 |
| Voice | *(your clone's name)* |
| Stability | |
| Similarity | |
| Delivery tag | `[thoughtful]` on every chunk — tested 29 Sep 2026 (beat 1: beat no tag and a long tag; click path: equal to `[calm]`, with more weight on key words) |
| Pause method | Ellipsis (`[short pause]` only where an ellipsis fails) |
| Emphasis | Reveal pause: about three per video (question, proof, decision), one per chunk at most; capitals optional on one long word |
| Pronunciation fixes | *(term → fix)*, or "none" |
| Date tested | 29 Sep 2026 |
