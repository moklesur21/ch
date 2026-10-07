# V8 voice-over — the caption script

**From:** `PureDevs_V8_Script_v1_2.md` §A (gate A passed 7 Oct 2026), data line per Demo Brief Q1. V8 has no recording-day options: the words do not depend on what the screen shows, so this file is final before the real take.
**Role:** the narration's words with correct spelling. It is the source for the captions, the question-page walkthrough and the YouTube transcript. The audio is generated with ElevenLabs from `voice-over-elevenlabs.md`, which carries these same words with ellipses at the pauses, one chunk per beat or sub-beat (`PureDevs_ElevenLabs_v4_Voice_Script_Guide_v1_1.md`).
**Length:** 1:57 in the final master · 200 spoken words · founder voice, second person, short sentences · `[PAUSE]` = a beat of air.
**Rule:** no figure is spoken. "90 days" is the Free read window, worded as owner rule 3 asks; it is not a demo-store number.

---

## Beat 1 · 0:00–0:05 · the question

Where does your store data go when you install this plugin?

## Beat 2 · 0:05–0:50 · the install, one take

Here's the whole install, in one take. `[PAUSE]` Plugins, Add Plugin. Search. Install. Activate.

There's one prompt: an optional opt-in from Freemius, our licensing provider. `[PAUSE]` We skip it.

No account. No API key. No setup wizard. `[PAUSE]` The dashboard is already reading the store's own orders — and it's self-hosted: what it records stays in your own WordPress database.

## Beat 3 · 0:50–1:25 · what it shows

Now a shopper — not logged in — searches the store for "hoodie", and opens two products.

Back in wp-admin: Customer History, Search Analytics. `[PAUSE]` There's "hoodie". And there are the two products they opened. Your own admin browsing isn't recorded — this came from the shopper.

Even the plugin's own usage counters stay here. Tools, Site Health, Info: "This information stays on your server."

## Beat 4 · 1:25–1:40 · the decision

So here is the decision this gives you. `[PAUSE]` Whether customer analytics has to mean sending your customer data to someone else's servers. With a plugin like this, it doesn't — no SaaS account, no API key, and the data sits in the same database as your orders.

## Beat 5 · 1:40–1:50 · next step

Everything you just saw is the free version. Free shows the last 90 days; Pro shows your full history. `[PAUSE]` Install it free — the link is below.

---

## For the editor

- **Caption emphasis** (bold in the burned-in captions), in order:
  **your store data** · **one take** · **optional** · **skip it** · **No account · No API key** · **your own WordPress database** · **not logged in** · **"hoodie"** · **this came from the shopper** · **stays on your server** · **the decision** · **same database as your orders** · **free version** · **Install it free**.
- `captions.srt` next to this file carries these lines timed to the final master (made by `conform-voice.py`), emphasis words in `<b>`.
- **Spelling in captions:** WooCommerce, PureDevs Customer History, Freemius, wp-admin, Site Health, SaaS, API, WordPress. Never a respelling from the voice script.
- "The link is below" is true on YouTube and the question page, where the first link is WordPress.org. On ad landing pages the page microcopy bridges it (Script v1.2 §A).
