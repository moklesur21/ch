# V1 Voice Script — Eleven v4

**Version 2.0 · 29 Sep 2026** · replaces v1.0 (Multilingual v2 break tags — don't use)
**From:** `voice-over.md` — options **A** (Returning is the biggest card) + **C** (Guest is the smallest)
**Written to:** `PureDevs_ElevenLabs_v4_Voice_Script_Guide_v1.md`

**Before you paste:**
- **Model:** Eleven v4 (not Turbo).
- **Voice:** your clone, with the settings from the guide's locked choices (§2.6).
- **Paste one chunk at a time**, in order, in one session. Copy only what's inside each grey box.
- **Words are exactly the approved script's.** The only changes are:
  - `[PAUSE]` → `[short pause]`;
  - one delivery tag at the start of each chunk;
  - new lines between script rows.

---

## First: the one-time test (guide §2.3)

Run it on **`V1_b2`** before generating anything else. Then apply the results to **every** chunk below:

| Test result | Change in every chunk |
|---|---|
| The tone tag is dropped | Delete `[calm, clear, conversational tone] ` from the start of each chunk |
| `[short pause]` is spoken or makes a sound | Replace `. [short pause]` with `…` (e.g. `This is a Pro report… It's self-hosted`) |
| "WooCommerce" comes out wrong | Write `Woo Commerce` (in b1, b2, b3b) |
| "PureDevs" comes out wrong | Write `Pure Devs` (in b2) |

Captions never change: they keep the approved spelling and punctuation.

---

## Chunk plan

| File | Beat · slot | Spoken words | Pauses | Picture (Recording Brief) |
|---|---|---|---|---|
| `V1_b1` | 1 · 0:00–0:05 | 10 | 0 | scene-01 |
| `V1_b2` | 2 · 0:05–0:20 | 36 | 1 | scene-02, scene-03 |
| `V1_b3a` | 3 · 0:20–0:35 | 33 | 1 | scene-04 |
| `V1_b3b` | 3 · 0:35–0:54 | 46 | 1 | scene-05 |
| `V1_b3c` | 3 · 0:54–1:06 | 20 | 2 | scene-06, then scene-07 |
| `V1_b4a` | 4 · 1:06–1:18 | 29 | 1 | scene-07 |
| `V1_b4b` | 4 · 1:18–1:33 | 33 | 0 | scene-06 |
| `V1_b5` | 5 · 1:33–1:46 | 32 | 0 | scene-01 → scene-08 → end card |

**239 spoken words · 6 pauses · 8 chunks.** The page shows the credit cost before each generation. Two free regenerations per chunk, as long as the text stays the same.

---

## V1_b1 — the question

```
[calm, clear, conversational tone] How much of your WooCommerce revenue comes from returning customers?
```

## V1_b2 — where to click *(test chunk)*

```
[calm, clear, conversational tone] This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report. [short pause] It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

## V1_b3a — the three cards

```
[calm, clear, conversational tone] Here's the split. New Customers. Returning Customers. Guest Orders. [short pause] Each card shows the revenue, and the orders behind it. New and Returning count customers with an account; guest orders are placed without one.
```

## V1_b3b — side by side

```
[calm, clear, conversational tone] One date range, three cards. [short pause] WooCommerce's own Orders report can show orders from new or returning customers, one filter at a time. Customer History adds new, returning and guest revenue side by side for the same dates, so you can decide where your next hour goes.
```

## V1_b3c — read the proportion

```
[calm, clear, conversational tone] Now read the proportion, not the number. [short pause] Returning Customers is the biggest of the three. [short pause] And guests? The smallest card.
```

## V1_b4a — the decision: guests

```
[calm, clear, conversational tone] So here is the decision this gives you. [short pause] Guests are small here. If yours is big, decide whether to encourage accounts at checkout — repeat guests never show as returning.
```

## V1_b4b — the decision: your next hour

```
[calm, clear, conversational tone] And look at the biggest card. Returning is the biggest — so your next hour belongs to the customers who already buy from you. That's where the biggest share of this revenue comes from.
```

## V1_b5 — next step

```
[calm, clear, conversational tone] The plugin doesn't decide for you. It shows you where the money comes from.
The free version shows your repeat purchase rate.
This split is Pro. The link below starts the trial.
```

---

## If a take stresses the wrong word

Capitalise only that one word in that chunk and regenerate (guide §1.6). For example, in b3c: `Now read the PROPORTION, not the number.`

Don't do this by default, and never on "Pro".

---

## For the editor

- **Captions:** unchanged. `captions.srt` already has the right words; re-time it to these audio files.
- **Longer pauses:** v4 has one pause strength. Where a picture needs more time (after "So here is the decision this gives you.", or between b5's three lines), widen the gap in the edit. Don't ask for a new take.
- **The 20–30 s cut** uses the same audio:
  - all of `V1_b1`;
  - from `V1_b4a`, only "So here is the decision this gives you." — cut in the pause after it;
  - from `V1_b4b`, everything after "And look at the biggest card.";
  - from `V1_b5`, only "This split is Pro. The link below starts the trial."

---

## Sanity check

| Check | Result |
|---|---|
| Words and punctuation match `voice-over.md` exactly, once the tag and `[short pause]` are removed | PASS — checked by script |
| `[short pause]` sits exactly where each `[PAUSE]` was, and nowhere else | PASS — 6 of 6 |
| Only options A and C present | PASS |
| No figures, trial terms, URLs, exclamation marks, added ellipses or extra tags | PASS |
| One CTA line, in b5 | PASS |
| At most about 60 words per chunk | PASS (max 46) |
