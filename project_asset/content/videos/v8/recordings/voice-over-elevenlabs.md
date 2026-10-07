# V8 Voice Script — Eleven v4

**Version 1.0 · 7 Oct 2026**
**From:** `voice-over.md`. V8 has no on-screen options, so these words are final.
**Written to:** `PureDevs_ElevenLabs_v4_Voice_Script_Guide_v1_1.md` (v1.2 rules)

**Before you paste:**
- **Model:** Eleven v4 (not Turbo).
- **Voice:** your clone, with the guide's locked settings (§2.6).
- **Paste one chunk at a time**, in order, in one session. Copy only what is inside each grey box.
- **The words are exactly the approved script's.** The only changes are:
  - `[thoughtful]` at the start of each chunk;
  - `...` at every `[PAUSE]`;
  - three reveal pauses: b1 (the question), b3b (the proof), b4 (the decision).
- **No capitals for emphasis** in this video. The candidates are names or short words that could be spelled out.
- **Captions never change.** They keep the approved spelling and punctuation.

---

## Chunk plan

| File | Beat · slot (after the cut) | Spoken words | Reveal pause | Picture (Recording Brief §D) |
|---|---|---|---|---|
| `V8_b1` | 1 · 0:00–0:05 | 11 | "store data... go" — the question | scene-01 |
| `V8_b2a` | 2 · 0:05–0:22 | 13 | — (click path; the editor spreads the words over the clicks) | scene-02, Plugins → Install |
| `V8_b2b` | 2 · 0:22–0:35 | 15 | — | scene-02, the opt-in → Skip |
| `V8_b2c` | 2 · 0:35–0:50 | 30 | — | scene-02, the Dashboard |
| `V8_b3a` | 3 · 0:50–1:02 | 16 | — | scene-03 |
| `V8_b3b` | 3 · 1:02–1:15 | 30 | "came from... the shopper" — the proof | scene-04 |
| `V8_b3c` | 3 · 1:15–1:25 | 18 | — | scene-05 |
| `V8_b4` | 4 · 1:25–1:40 | 46 | "sits in... the same database" — the decision | scene-01 |
| `V8_b5` | 5 · 1:40–1:50 | 26 | — (keeps the ending brisk) | scene-01 → end card |

**200 spoken words.** The ellipses add a little time; check the finished video still runs under 2:00.

---

## V8_b1 — the question

```
[thoughtful] Where does your store data... go when you install this plugin?
```

## V8_b2a — the install

```
[thoughtful] Here's the whole install, in one take... Plugins, Add Plugin. Search. Install. Activate.
```

## V8_b2b — the one prompt

```
[thoughtful] There's one prompt: an optional opt-in from Freemius, our licensing provider... We skip it.
```

## V8_b2c — no account, your database

```
[thoughtful] No account. No API key. No setup wizard... The dashboard is already reading the store's own orders — and it's self-hosted: what it records stays in your own WordPress database.
```

## V8_b3a — the shopper

```
[thoughtful] Now a shopper — not logged in — searches the store for "hoodie", and opens two products.
```

## V8_b3b — it's here

```
[thoughtful] Back in wp-admin: Customer History, Search Analytics... There's "hoodie". And there are the two products they opened. Your own admin browsing isn't recorded — this came from... the shopper.
```

## V8_b3c — Site Health

```
[thoughtful] Even the plugin's own usage counters stay here. Tools, Site Health, Info: "This information stays on your server."
```

## V8_b4 — the decision

```
[thoughtful] So here is the decision this gives you... Whether customer analytics has to mean sending your customer data to someone else's servers. With a plugin like this, it doesn't — no SaaS account, no API key, and the data sits in... the same database as your orders.
```

## V8_b5 — next step

```
[thoughtful] Everything you just saw is the free version. Free shows the last 90 days; Pro shows your full history... Install it free — the link is below.
```

If the call to action sounds hesitant when played right after b4, regenerate this chunk without `[thoughtful]`.

---

## Pronunciation fallbacks: only if a take gets the name wrong

These go in the **voice script only**. Captions keep the real spelling. Once a fix works, add it to the guide's locked choices (§2.6).

| Term | Chunk | Replace with |
|---|---|---|
| Freemius | b2b | `"/ˈfriːmiəs/"` (IPA, guide §1.7) |
| SaaS | b4 | `sass` |
| wp-admin | b3b | `W P admin` |
| WooCommerce | (not spoken in V8) | — |

---

## For the editor

- **Captions:** unchanged. `captions.srt` keeps the approved words and punctuation (no ellipses); re-time it to these audio files.
- **Done here, 7 Oct:** the chunks are placed by `conform-voice.py` (Recording Brief v1.2); the notes below are for a hand edit only.
- **Beat 2 runs over a real-time take.**
  - Place `V8_b2a`'s words on the clicks they name. Cut the audio at its sentence boundaries ("Plugins, Add Plugin." / "Search." / "Install." / "Activate.") and lay each piece on its moment. This changes no words.
  - `V8_b2b` sits on the opt-in screen and must end on "We skip it." as Skip is clicked.
- **Longer pauses:** where a picture needs more time, widen the gap in the edit. Don't ask for a new take.
- **The 20–30 s cut** (Placement Kit §1) uses the same audio. Proposal, to confirm in the kit:
  - all of `V8_b1`;
  - from `V8_b2c`, "No account. No API key. No setup wizard.";
  - from `V8_b4`, from "So here is the decision this gives you..." to the end;
  - from `V8_b5`, only "Install it free — the link is below."

---

## Sanity check

| Check | Result |
|---|---|
| Every word matches `voice-over.md` (ignoring ellipses and the tag) | PASS — checked by script, 200 words |
| An ellipsis at each of the 6 `[PAUSE]` spots | PASS |
| Three reveal pauses, at most one per chunk, none next to a `[PAUSE]` ellipsis | PASS (b1, b3b, b4) |
| Only `[thoughtful]`, once at the start of each chunk | PASS |
| No capitals for emphasis, no exclamation marks | PASS |
| No trial terms, URLs or demo-store figures | PASS. "90 days" is the Free read window that owner rule 3 asks for. The guide's no-figures line targets demo-store numbers, and owner rules beat every file |
| One CTA line, in b5 | PASS |
| Every chunk ≤ 60 words | PASS (largest: b4, 46) |
