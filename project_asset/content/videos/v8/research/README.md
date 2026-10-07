# V8 research — drop the Gemini / Perplexity output here

**Video:** V8 — "Where does my store data go when I install this?" · FREE · `no_saas`
**Step 1 of the runbook** (`project_asset/docs/video-production.md` §3).

1. Open `project_asset/strategy/PureDevs_Video_Docs/gemini.txt`, copy the block between the two "PASTE" lines into Gemini (and Perplexity, if you use it), and fill the three fields with:

   ```
   QUESTION: Where does my store data go when I install this?
   PLUGIN SHOWS: Self-hosted analytics. Tracking runs inside WordPress and everything is stored in the site's own database. No external account, API key or tracking script (FREE).
   PLANNED SENTENCE: Hosted WooCommerce analytics services need an account and copy store data to their own servers; a self-hosted plugin keeps it in the site's own database. For category 1, check how Metorik, Metrilo and Putler connect to a store and where each says the data is stored.
   ```

2. Save each answer here as-is, one file per source, named with the date: `gemini-2026-10-07.md`, `perplexity-2026-10-07.md`.
3. Tell Claude "V8 research is in". Claude then writes the Demo Brief and the Script (gate A: you approve them).
