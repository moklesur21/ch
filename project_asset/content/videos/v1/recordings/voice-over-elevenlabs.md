# V1 Voice Script — ElevenLabs

**Version 1.0 · 28 Sep 2026** · from `voice-over.md` (options **A** + **C**: Returning is the biggest card, Guest is the smallest) · follows `PureDevs_ElevenLabs_Voiceover_Workflow` v1.1 §2–§3

**Before you paste:**
- **Model:** Eleven Multilingual v2. Break tags don't work on v3.
- **Voice:** your clone, from *My Voices*.
- **Settings:** the locked values from the voice log.
- **Paste one chunk at a time**, in order, in one session. Copy only what's inside each grey box.
- **Words** are exactly the approved script's. The only change is `[PAUSE]` → `<break time="0.7s" />`; one pause becomes `1.0s` before the decision (workflow §2.2).
- **No respellings**, because your pronunciation test passed. If any name comes out wrong, respell it here only (for example `Pure Devs`), never in the captions, and add it to the voice log.

---

## Chunk plan

| File | Beat · slot | Words | Breaks | Picture (Recording Brief) |
|---|---|---|---|---|
| `V1_b1` | 1 · 0:00–0:05 | 10 | 0 | scene-01 |
| `V1_b2` | 2 · 0:05–0:20 | 36 | 1 | scene-02, scene-03 |
| `V1_b3a` | 3 · 0:20–0:35 | 33 | 1 | scene-04 |
| `V1_b3b` | 3 · 0:35–0:54 | 46 | 1 | scene-05 |
| `V1_b3c` | 3 · 0:54–1:06 | 20 | 2 | scene-06, then scene-07 |
| `V1_b4a` | 4 · 1:06–1:18 | 29 | 1 | scene-07 |
| `V1_b4b` | 4 · 1:18–1:33 | 33 | 0 | scene-06 |
| `V1_b5` | 5 · 1:33–1:46 | 32 | 0 | scene-01 → scene-08 → end card |

**239 words · 7 breaks · about 1,600 credits for one full pass.** The two free regenerations per chunk cost nothing, as long as the text stays the same.

---

## V1_b1 — the question

```
How much of your WooCommerce revenue comes from returning customers?
```

## V1_b2 — where to click

```
This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report. <break time="0.7s" /> It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

## V1_b3a — the three cards

```
Here's the split. New Customers. Returning Customers. Guest Orders. <break time="0.7s" /> Each card shows the revenue, and the orders behind it. New and Returning count customers with an account; guest orders are placed without one.
```

## V1_b3b — side by side

```
One date range, three cards. <break time="0.7s" /> WooCommerce's own Orders report can show orders from new or returning customers, one filter at a time. Customer History adds new, returning and guest revenue side by side for the same dates, so you can decide where your next hour goes.
```

## V1_b3c — read the proportion

```
Now read the proportion, not the number. <break time="0.7s" /> Returning Customers is the biggest of the three. <break time="0.7s" /> And guests? The smallest card.
```

## V1_b4a — the decision: guests

```
So here is the decision this gives you. <break time="1.0s" /> Guests are small here. If yours is big, decide whether to encourage accounts at checkout — repeat guests never show as returning.
```

## V1_b4b — the decision: your next hour

```
And look at the biggest card. Returning is the biggest — so your next hour belongs to the customers who already buy from you. That's where the biggest share of this revenue comes from.
```

## V1_b5 — next step

```
The plugin doesn't decide for you. It shows you where the money comes from.
The free version shows your repeat purchase rate.
This split is Pro. The link below starts the trial.
```

---

## For the editor

- **Captions:** unchanged. There are no respellings, so `captions.srt` already has the right words. Re-time it to these audio files.
- **Beat 5 gaps:** b5's three lines go under three different pictures. If a picture needs more time, widen the gap at the full stop between lines. Don't speed up the voice.
- **The 20–30 s cut** uses the same audio:
  - all of `V1_b1`;
  - from `V1_b4a`, only "So here is the decision this gives you." — the 1.0 s pause after it is a clean cut point;
  - from `V1_b4b`, everything after "And look at the biggest card.";
  - from `V1_b5`, only "This split is Pro. The link below starts the trial."

---

## Why this doesn't use the Gemini style

- **No added ellipses (`...`).** ElevenLabs calls them less consistent than break tags, and says they can add hesitation or nervousness. Pauses go only where the script has `[PAUSE]`. Periods and commas give the rest of the natural rhythm.
- **No exclamation marks.** "This is a Pro report!" and "Here's the split!" push the voice toward an announcer tone. The series is a calm founder explaining.
- **No changed words.** Gemini's "scroll **down** to" isn't the approved line. The audio must match the claims-checked script and the captions word for word.
- **Split into chunks.** Each paragraph is its own file, so one beat can be re-rendered later without touching the rest.

---

## Sanity check

| Check | Result |
|---|---|
| Words match `voice-over.md` exactly (with `[PAUSE]` removed) | PASS — checked by script: 239 words, identical |
| Only options A and C present | PASS |
| No spoken figures, trial terms, cap, competitor names or "only / first" | PASS |
| One CTA line, in b5 | PASS |
| At most 3 breaks and about 60 words per chunk | PASS (max 2 breaks, 46 words) |
| Break tags only on Multilingual v2 | Set the model before pasting |

---

## Changelog

- **v1.0 — 28 Sep 2026.** First voice script for V1 (options A + C), from `voice-over.md`.
