# V1 Voice Script — Eleven v4 · FINAL

**Version 3.1 · 29 Sep 2026** · replaces v3 (which offered two versions of four chunks)
**From:** `voice-over.md` — options **A** (Returning is the biggest card) + **C** (Guest is the smallest)
**Written to:** `PureDevs_ElevenLabs_v4_Voice_Script_Guide` v1.2

**Before you paste:**
- **Model:** Eleven v4 (not Turbo).
- **Voice:** your clone, with the settings from the guide's locked choices (§2.6).
- **Paste one chunk at a time**, in order, in one session. Copy only what's inside each grey box.
- **Words are exactly the approved script's.** The only changes are:
  - `[thoughtful]` at the start of each chunk;
  - `...` at every `[PAUSE]`;
  - three reveal pauses (b1, b3b, b4b);
  - `RETURNING` in capitals in b1.
- **Captions never change.** They keep the approved spelling and punctuation.

---

## Chunk plan

| File | Beat · slot | Spoken words | Reveal pause | Picture (Recording Brief) |
|---|---|---|---|---|
| `V1_b1` | 1 · 0:00–0:05 | 10 | "revenue... comes from" — the question | scene-01 |
| `V1_b2` | 2 · 0:05–0:20 | 36 | — | scene-02, scene-03 |
| `V1_b3a` | 3 · 0:20–0:35 | 33 | — | scene-04 |
| `V1_b3b` | 3 · 0:35–0:54 | 46 | "revenue... side by side" — the proof | scene-05 |
| `V1_b3c` | 3 · 0:54–1:06 | 20 | — (the question mark makes the beat) | scene-06, then scene-07 |
| `V1_b4a` | 4 · 1:06–1:18 | 29 | — | scene-07 |
| `V1_b4b` | 4 · 1:18–1:33 | 33 | "belongs to... the customers" — the decision | scene-06 |
| `V1_b5` | 5 · 1:33–1:46 | 32 | — (keeps the ending brisk) | scene-01 → scene-08 → end card |

**239 spoken words.** The ellipses add a little time; check the finished video still runs under 2:00.

---

## V1_b1 — the question

```
[thoughtful] How much of your WooCommerce revenue... comes from RETURNING customers?
```

## V1_b2 — where to click

```
[thoughtful] This is a WooCommerce demo store running PureDevs Customer History. Open Customer History, Dashboard, and scroll to Revenue Analytics.
This is a Pro report... It's self-hosted — nothing leaves your server. It reads your store's own orders.
```

## V1_b3a — the three cards

```
[thoughtful] Here's the split. New Customers. Returning Customers. Guest Orders... Each card shows the revenue, and the orders behind it. New and Returning count customers with an account; guest orders are placed without one.
```

## V1_b3b — side by side

```
[thoughtful] One date range, three cards... WooCommerce's own Orders report can show orders from new or returning customers, one filter at a time. Customer History adds new, returning and guest revenue... side by side for the same dates, so you can decide where your next hour goes.
```

## V1_b3c — read the proportion

```
[thoughtful] Now read the proportion, not the number... Returning Customers is the biggest of the three... And guests? The smallest card.
```

## V1_b4a — the decision: guests

```
[thoughtful] So here is the decision this gives you... Guests are small here. If yours is big, decide whether to encourage accounts at checkout — repeat guests never show as returning.
```

## V1_b4b — the decision: your next hour

```
[thoughtful] And look at the biggest card. Returning is the biggest — so your next hour belongs to... the customers who already buy from you. That's where the biggest share of this revenue comes from.
```

## V1_b5 — next step

```
[thoughtful] The plugin doesn't decide for you. It shows you where the money comes from.
The free version shows your repeat purchase rate.
This split is Pro. The link below starts the trial.
```

Only if the call to action sounds hesitant when played right after b4b: regenerate this chunk without `[thoughtful]`.

---

## For the editor

- **Captions:** unchanged. `captions.srt` keeps the approved words and punctuation (no ellipses, "returning" in lower case); re-time it to these audio files.
- **Longer pauses:** where a picture needs more time (after "So here is the decision this gives you...", or between b5's three lines), widen the gap in the edit. Don't ask for a new take.
- **The 20–30 s cut** uses the same audio:
  - all of `V1_b1`;
  - from `V1_b4a`, only "So here is the decision this gives you..." — cut in the pause after it;
  - from `V1_b4b`, everything after "And look at the biggest card.";
  - from `V1_b5`, only "This split is Pro. The link below starts the trial."

---

## Sanity check

| Check | Result |
|---|---|
| Every word matches `voice-over.md` (ignoring capitals) | PASS — checked by script |
| An ellipsis at each of the 6 `[PAUSE]` spots | PASS |
| Three reveal pauses, at most one per chunk | PASS |
| Only `[thoughtful]`, once at the start of each chunk | PASS |
| Capitals only on "RETURNING" in b1 | PASS |
| No figures, trial terms, URLs or exclamation marks | PASS |
| One CTA line, in b5 | PASS |
