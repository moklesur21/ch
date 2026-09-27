# PureDevs Demo Video Prompt Pack v3.0

**Prepared for:** PureDevs Team
**Date:** 21 September 2026
**Supersedes:** `PureDevs_Content_Prompt_Pack_v2.md` (24 Jul 2026) entirely
**Works with:** `PureDevs_Video_Topic_Filter_v4.x.md` (latest) · `MARKETING-BRIEF-3.1.2` + `MARKETING-BRIEF-3.1.2-PDF-INDEX.md` · `PureDevs_Proof_Marketing_Ad_Strategy_v1.md`
**Paste texts:** `claude.txt` (the step-by-step messages) · `gemini.txt` (File 1, ready to paste)

**How this file works.** Keep it in the project. Each numbered FILE below is one prompt. In Claude you call it by name, exactly as before ("run section FILE 3 of 6"). The UNIVERSAL BLOCK appears once; every file tells Claude to load it. If you ever paste a file outside this project, paste the Universal Block with it. File 1 is the only one that runs outside Claude, so it is fully self-contained.

---

## 0. The pipeline (per video ID from the Video Topic Filter)

| Step | File | Runs in | Needs | Produces |
|---|---|---|---|---|
| 1 | research-prompt.md | Gemini (and Perplexity, if you still run both) | Three fields from the video's row | Research output |
| 2 | demo-brief-prompt.md | Claude | Video ID + research output | **Demo Brief** — the single source of truth for this video |
| 3 | script-prompt.md | Claude | Approved Demo Brief | Script + YouTube package + poster |
| 4 | recording-brief-prompt.md | Claude | Approved script + current screenshots | The editor's recording and edit brief |
| 5 | placement-kit-prompt.md | Claude | Approved script + Demo Brief | 20–30 s cut, question page, email line, listing captions, screenshot and GIF list, ad match |
| 6 | claim-audit-prompt.md | Claude | Any asset | False-claim audit + corrected version |

One video therefore yields: 1 research doc → 1 Demo Brief → 1 script → 1 recording brief → 1 placement kit.

**Retired from v2, by strategy §2:** the Final Content Doc (hook menu, contradiction, damage, derived angles), the 4–6 minute script, 3–4 shorts per topic, social sets, newsletter, tweets, blog outline and draft. The **question page** replaces the blog. The **one 20–30 s cut** replaces the shorts.

---

## UNIVERSAL BLOCK (load this before running any file)

**U1 — Sources, and who decides what**

| Question | Source that decides |
|---|---|
| What the product does, Free or Pro, exact UI wording, definitions, do-not-claim | `MARKETING-BRIEF-3.1.2`. Find every feature by its entry ID (for example SA-04) through `MARKETING-BRIEF-3.1.2-PDF-INDEX.md`, then read that entry. **Read §D before writing anything.** |
| Format, placements, exclusions, ad rules | `PureDevs_Proof_Marketing_Ad_Strategy_v1.md` |
| Which video: its ID, title, tier, screens, decision, guardrails, week | `PureDevs_Video_Topic_Filter_v4.x.md`, latest version in the project |
| Any of the above against an owner rule (U2) | The owner rule |

Superseded, never used where they conflict: Content Prompt Pack v2, Video Topic Filter v3, any "Final Content Doc", the video and social sections of Marketing Strategy v2. Invent nothing. If the sources do not settle a point, write `[OPEN — ask Yeasir]`.

**U2 — Owner rules (they beat every file)**

1. **No spoken figures anywhere in the demo.** The narration says "read the proportion, not the number". It points at which card, bar or row is bigger and never recites a demo-store amount, count, percentage or date. This works on any demo store and is stronger than reciting totals. Demo values can differ on recording day, so every line must stay true if the numbers change. Where a relationship could flip, write both options in `[brackets]`. Search terms and product names may be read aloud; they are words, not figures. This rule replaces the strategy's beat-3 wording "read the numbers out loud".
2. **Freshness.** [MOST IMPORTANT: For any WooCommerce or other plugin's feature you want to criticise, please check the latest version — perhaps they already fixed it. Double-check and confirm.] Every sentence about what WooCommerce core or another tool does or does not do needs a check made today, with URL and version. No check, no sentence.
3. **No 100-customer cap on Free.** Never mention one in any script, page, email or ad. Brief D.2, FREE-A, CUST-01 and B3 still word it that way; ignore that wording. Say "Free shows the last 90 days".
4. **Links.** Free plugin: https://wordpress.org/plugins/puredevs-customer-history-for-woocommerce/ · Site: https://puredevs.com/ · Every trial CTA goes to https://puredevs.com/pricing, never to a raw Freemius URL.

**U3 — The plugin observes and reports. The owner acts.** Brief §D.1 and §D.2 bind every sentence. The slips that happen most:

- No automated, bulk or marketing email to customers. The only customer-facing email is a manual one-to-one message from a profile.
- Carts are observed, never recovered. No "recover", "win back", "reminder". Do not voice or promise the Automation Add-on.
- No coupons, campaigns, automations, workflows, loyalty, referrals.
- Klaviyo and Mailchimp are CSV column layouts for a file you download. Never "integration", "sync" or "connect".
- Zero-Result Searches and Frequently Bought Together are admin reports. No redirects, no storefront widget, no recommendations.
- Scores are nightly. "Live Sessions" means not trashed, not real-time. Free widgets are near-live (30-minute cache).
- Zero-result data accrues from the day the site runs 3.1.2, never retroactively.
- Retention, repeat purchase rate and refund rate cover registered customers only. Guests have no profile.
- Privacy: say "self-hosted, nothing leaves your server, works with WordPress's personal-data export and erase tools". Never "GDPR-compliant", "cookie-less", "consent-managed" or "anonymised".
- Compatibility: "HPOS-compatible" is safe. Never claim Blocks-checkout compatibility or a WooCommerce 11 test pass.
- Only features documented in Brief 3.1.2 exist. Nothing from a future release is shown or promised.

**U4 — Never-pitch and banned hooks** (strategy §4.5, Brief D.3). Never lead with or build a piece around RFM, churn scores, health scores, behavioral tags, or the five ready-made segments. Never use recovery language, automation claims, coupons, loyalty mechanics or "per-customer session history" as a hook. A ⚑-flagged entry may be visible in passing; it is never the subject and never narrated. No "only", "first" or "nobody else" claims. Competitors: model and data location only, never quality. No competitor names in narration or ad text.

**U5 — Claims discipline.** No sentence in a video without the screen that proves it. No external statistic in narration, ever. A statistic in page text needs the primary source and URL. Brief D.4 is embargoed (the zero-result percentage). Demo numbers are synthetic and never presented as a customer result.

**U6 — Positioning sentence.** Never "WooCommerce has no analytics". The shape: **"WooCommerce shows you ___. Customer History adds ___, so you can decide ___."** Use it only with a core fact verified under U2.2. If it cannot be verified, the beat runs without mentioning core.

**U7 — Free, Pro, price, CTA.** State the tier from the Brief for every capability. Say "Free shows…", never "Free only tracks…". On upgrade, never "imported", "synced" or "migrated"; the data was already collected. No price anywhere. One CTA per asset: a free-answered question → install free; a Pro-answered question → start the trial. Say "30-day" or "no card" only if the task confirms it.

**U8 — Format** (strategy §4.2–4.3, Video Topic Filter §3). 60–120 seconds. One question. Founder voice. Screen recording of the seeded demo store only. "PureDevs Customer History" in the first frame and the end card. No greeting, no intro animation over 2 seconds, no slides, no stock footage, no stat cards.

| Beat | Time | Content |
|---|---|---|
| 1. The question | 0–5 s | The owner's question in their words, spoken over the **answer frame** |
| 2. Where to click | 5–20 s | The path. Say once that the data never leaves the site. Say "this is a Pro report" when it is |
| 3. What it shows | 20–60 s | Show the numbers, zoomed. Describe the proportion, never the figure |
| 4. The decision | 60–90 s | "So here is the decision this gives you…" End on the decision and the money, never on the dashboard |
| 5. Next step | last 10 s | One sentence on Free vs Pro. One CTA |

---
---

# ═══ FILE 1 of 6 — research-prompt.md ═══
*(run in Gemini; run the same text in Perplexity if you still use both · self-contained · `gemini.txt` is the paste-ready copy)*

You are a research assistant gathering VERIFIABLE evidence for a short product-demo video aimed at WooCommerce store owners. The video answers ONE question with a screen recording of a WordPress plugin. It contains no statistics and no news hooks, so do not collect them unless category 5 asks.

THE OWNER'S QUESTION (the video title): "[paste from the Video Topic Filter]"
WHAT THE PLUGIN SHOWS: "[capability + FREE or PRO, from the same row]"
PLANNED SENTENCE ABOUT WOOCOMMERCE CORE OR OTHER TOOLS (unverified): "[paste, or write NONE]"

Gather these categories:

1. COMPETITIVE AND CORE CHECK (3–8 items) — the load-bearing category.
   For WooCommerce core AND every product that touches this capability: the product · its CURRENT version and release date · exactly what it does TODAY on this capability, and what it does not · where in the product (menu path or feature name) · URL (official docs, changelog, product page or plugin listing) · date checked.
   Always cover: WooCommerce core (Analytics reports and any relevant core feature) · the official Marketplace extension "Customer Analytics for WooCommerce" · Metorik · Metrilo · any free WordPress.org plugin built for this one job.
   Separate what a marketing page claims from what documentation or a changelog confirms.

2. OWNER LANGUAGE (5–8 items).
   How store owners ask this question in their own words: publicly indexed Reddit threads, WordPress.org support forums, WordPress StackExchange, plugin reviews, public group posts. For each: the question or complaint, close to their wording and short · where · URL · date. Not for publication. It is used to word the title and the first five seconds.

3. SEARCH PHRASING (5–10 items).
   How this question is typed into Google or YouTube: autocomplete variants, "People also ask" questions, titles of videos and articles that rank for it. For each: the phrase · where you saw it · URL. Do NOT invent search volumes.

4. BUYER DOUBTS (3–6 items).
   What a sceptical store owner asks before trusting an answer like this: accuracy, guests versus registered customers, site speed, privacy, "does it work with my search plugin or checkout". For each: the doubt · where it appears · URL. Used for the FAQ on the video's page.

5. ONE STATISTIC FOR PAGE TEXT (0–1 item, optional).
   Only if a primary source exists: the organisation that produced the number, year, URL. Never used in narration. If none, write NONE.

STRICT RULES:
- Every item MUST have a real, working URL. No URL → omit it.
- Do NOT fabricate or approximate versions, dates, features, quotes or statistics. Fewer real items is correct; invented items are a failure.
- Version numbers and dates must come from the page you link.
- Flag anything uncertain: [UNCERTAIN — double-check].
- Prefer 2026 sources, then the last 2–3 years.
- [MOST IMPORTANT: For any WooCommerce or other plugin's feature you want to criticise, please check the latest version — perhaps they already fixed it. Double-check and confirm.]

OUTPUT: a clean list per category with the fields above. Then VERDICT ON THE PLANNED SENTENCE: accurate / needs rewording / false, with the evidence and a corrected wording. Then a SOURCE LINK LIST of every URL used.

---
---

# ═══ FILE 2 of 6 — demo-brief-prompt.md ═══
*(run in Claude with the video ID and the research output attached · replaces synthesis-prompt.md)*

Load the UNIVERSAL BLOCK and apply it in full.

You are preparing ONE **Demo Brief**: the single source of truth for one demo video. The script, the recording brief and the placement kit are all written from it. Use only the sources in U1 and the research I attach. Invent nothing.

INPUTS:
- VIDEO ID (for example V13). Pull its row and its brief from the latest Video Topic Filter yourself.
- Every Brief entry the row names (by entry ID, through the INDEX). Read each entry fully, including its accuracy notes and demo prerequisites. Read §D.
- RESEARCH A (Gemini) and, if attached, RESEARCH B (Perplexity). Where they disagree, prefer the official documentation or changelog; if unresolvable, flag `[CONFLICT]` and keep it out of the script.
- If I write "Research: none" (for example V16, which is about our own tiers), skip section 5 and keep WooCommerce core and other tools out of the script.

PRODUCE THESE SECTIONS:

1. HEADER — ID · the title (the owner's question, verbatim from the row) · tier(s) · CTA (install free, or start the trial) · week and placements from the Video Topic Filter. Propose an alternate title only if OWNER LANGUAGE clearly supports different wording; say that a title change also touches the question page, the ads and the IDs.

2. SCREENS — one row per screen, in order: exact UI title · Brief entry ID · FREE or PRO · menu path from Brief §3 · what it shows · **which build to record on** (some screens exist only on Free, some only on Pro) · demo prerequisites · the entry's ⛔ and ⚑ notes, copied.

3. FIVE BEATS — for each beat: the screen · what the narration must establish, in words with no figures · each claim paired with the screen that proves it.

4. THE DECISION (beat 4) — one or two sentences starting from the row's decision. It ends on the decision and the money. The plugin never acts in it.

5. THE HONEST SENTENCE — "WooCommerce shows you ___. Customer History adds ___, so you can decide ___." Cite the research item that verifies the core fact (URL, version, date checked). If the research cannot confirm it, write `[UNVERIFIED — cut or re-check]` and give a version of the beat that does not mention core at all.

6. THE FREE / PRO SENTENCE (beat 5) — the exact boundary for this capability, in the Brief's wording, under U2.3 and U7.

7. CLAIMS LEDGER — every factual sentence the script may make | the screen that proves it | Brief entry | status: PROVEN ON SCREEN · VERIFIED EXTERNAL (URL + date) · CUT.

8. LIMITS A BUYER WILL HIT — honest limits from the Brief entries (for example: registered customers only; data starts on install day; native search only; nightly scores). These feed the question-page FAQ together with the research's BUYER DOUBTS.

9. OPEN QUESTIONS FOR YEASIR — only what the sources cannot settle.

10. SANITY CHECK — run the FILE 6 audit list on the row, the research and your own draft. List every false, stale or unprovable claim you found and what you did about it. Print the table even if it is empty.

FORMAT: one clean md file, scannable, one page where possible.

---
---

# ═══ FILE 3 of 6 — script-prompt.md ═══
*(run in Claude with the approved Demo Brief · replaces video-script-prompt.md)*

Load the UNIVERSAL BLOCK and apply it in full.

Using the approved Demo Brief, write the demo script and its publishing package. The video's one job: a store owner who already has this question sees it answered on a working store and takes the next step.

A) THE SCRIPT
- 60–120 seconds. About 150–260 spoken words; never over 280. Five beats with the timings in U8.
- One table row per beat or sub-beat: time · `[SCREEN: exact UI title — entry ID]` · `[ZOOM: the area the viewer must read]` · NARRATION · CAPTION EMPHASIS (the few words to bold in the burned-in captions).
- Beat 1: the question in the owner's words, over the answer frame. No greeting. Start inside the question.
- Beat 2: the click path. Say once that the data never leaves the site. Say "this is a Pro report" when it is.
- Beat 3: show and describe. **No spoken figures.** Use the line "read the proportion, not the number" once where it fits. Point at shapes, gaps, ranks and which one is bigger. Where the relationship could flip on recording day, write both options in `[brackets]`.
- Beat 4: open with "So here is the decision this gives you…". End on the decision and the money.
- Beat 5: the Demo Brief's Free / Pro sentence, then one CTA. Optional closing line in the strategy's voice: "The plugin doesn't decide for you. It shows you where the money comes from."
- If the Demo Brief's honest sentence is verified, speak it once, in beat 2 or beat 3. If it is not, leave core out.
- Voice: founder, second person, short plain sentences that are easy to read aloud. `[PAUSE]` where a beat needs air. No "subscribe", no email-list ask, no price.
- Only sentences that appear in the Claims Ledger as PROVEN ON SCREEN or VERIFIED EXTERNAL.

B) YOUTUBE PACKAGE
- Title = the question, exactly.
- Description: line 1 = the one-sentence answer plus the question-page URL (propose the slug). Then 2–4 plain lines naming only what the video shows. Chapters for the five beats. Links: the free plugin on WordPress.org and https://puredevs.com/pricing.
- Pinned comment = the trial link (https://puredevs.com/pricing).
- 5–8 tags from the research's SEARCH PHRASING.

C) POSTER AND THUMBNAIL
- The answer frame: which screen, which zoom, which row or card is highlighted. The question as text; shorten only if it cannot fit, and keep the owner's words. Real UI only. No arrows, no shock faces, no invented numbers.
- The same image serves the pricing page, the question page and the in-plugin poster.

D) SANITY CHECK — run the FILE 6 audit list on the script and the package. Print the table, then a word count and a spoken-time estimate per beat.

OUTPUT: one md file: script, YouTube package, poster spec, sanity check.

---
---

# ═══ FILE 4 of 6 — recording-brief-prompt.md ═══
*(run in Claude with the approved script and the current demo-store screenshots · for the NON-TECHNICAL editor)*

Load the UNIVERSAL BLOCK and apply it in full.

You are producing a SCREEN-RECORDING AND EDIT BRIEF for a non-technical video editor. He records exactly what already exists on the demo store. He configures nothing, fixes nothing and improvises nothing. Assume he has never seen WordPress. Plain, friendly language; every instruction is click-by-click.

INPUTS: the approved script, the Demo Brief's SCREENS table, Brief §3 menu paths, and the screenshots I attach. If the script needs a screen the screenshots do not confirm, flag it at the top as `[CONFIRM SCREEN EXISTS BEFORE RECORDING DAY]` and write no scene for it. If anything needs setup beyond clicking and the exact typing you list (installing a plugin, switching a licence, changing a setting, generating store activity), list it at the top under `[DEV SETUP — before recording day]`. The editor never does setup.

**The demo values will be different on recording day.** Never write an expected number, name or date into a scene. Describe what must be visible by structure: "three cards, none showing zero", "a table with at least eight rows".

PRODUCE THESE SECTIONS:

A) ONE-TIME SETUP CHECKLIST
- Log in with the demo account Yeasir gives you, never a personal or developer account.
- Browser 1920×1080, zoom 100 %, bookmarks bar hidden, other tabs closed, notifications off, cursor highlight on.
- No red update badges or notice bubbles on screen. If any show, stop and report; do not click them.

B) SCENE TABLE — one row per scene, in script order:
| Scene # | Beat | Which site or build (Free / Pro / storefront in a private window) | Screen name in plain words | How to get there, click by click | What to do (hover / click / scroll slowly / type exactly this: "…") | Hold time in seconds | What must be visible before you stop | Stop-and-report if… |
- **Hold at least 5 seconds on every chart, card row or table.** The narration never reads numbers aloud, so the viewer needs still time to read the proportion.
- Record the ANSWER FRAME as its own held scene of 8 seconds or more. It opens the video and becomes the poster.
- Where a report draws nothing until "Apply" is pressed, the Apply click is part of the scene.
- Storefront actions (searching, viewing products) happen in a private window, logged out. Activity made while logged in as an administrator is not recorded by the plugin.
- Free screens can lag up to 30 minutes behind new activity. Put the storefront scenes first and say how long to wait, or mark it `[DEV SETUP]`.
- Stop-and-report triggers: a spinner that never finishes, an error, an empty card or "No data" message, a zero where the script expects a comparison, anything that looks different from the attached screenshot.

C) RECORDING RULES
- One clip per scene, named scene-01, scene-02, … matching the table. Start 3 seconds before the first action; stop 3 seconds after the last.
- Scroll slowly, once, in one direction. Type only what the table spells out. Click nothing the table does not name. Never open Settings unless a scene says so.
- If a ⚑ widget (RFM, health, churn, behavioral tags) is on screen, scroll past it at normal speed. Never stop or zoom on it.

D) EDIT NOTES
- Order: answer frame under beat 1, then scenes in beat order. Zoom on the area the script names in `[ZOOM]`.
- Burned-in captions from the script, with the CAPTION EMPHASIS words in bold. "PureDevs Customer History" in the first frame and on the end card. No intro over 2 seconds. One CTA on the end card.
- Export the master at 1080p. Then cut the derivatives listed in the placement kit's shot list.

E) DELIVERY — one folder named for the video ID, all clips, the answer-frame still as PNG, and a note listing any scene that could not be recorded and why.

F) SANITY CHECK — every scene maps to a script line and a Brief entry; every path comes from Brief §3, not memory; no scene shows a future feature; no expected values written anywhere. Print the result.

---
---

# ═══ FILE 5 of 6 — placement-kit-prompt.md ═══
*(run in Claude with the approved script and the Demo Brief · replaces shorts-prompt.md, social-prompt.md and blog-prompt.md)*

Load the UNIVERSAL BLOCK and apply it in full.

A finished video is not done until it is placed (strategy §5). Produce ONE md file with the sections below. Write a section only if the strategy gives this video that placement; otherwise write "NOT FOR THIS VIDEO" and the reason.

1. THE 20–30 S CUT — beat 1 + the answer frame + the beat-4 decision sentence + the end card. It skips beat 2. **Never under 11 seconds.** Give master timecodes to reuse, any line to re-record, and caption text. Same rules: no spoken figures, one CTA. Optional vertical 9:16 note: what to crop to, caption size, and a Short title.

2. QUESTION PAGE — slug · H1 = the question · meta title (≤ 60 characters) · meta description (≤ 155) · the 5–8 line written walkthrough from the transcript (what the viewer sees and the decision; no demo figures) · 3 screenshots with caption and alt text · FAQ of 3–5 honest answers built from the Demo Brief's LIMITS and the research's BUYER DOUBTS · **one CTA button → https://puredevs.com/pricing**. For a free-answered question, the walkthrough's last line may say that the free version on WordPress.org shows this, as a plain text link; no second button. Add `VideoObject` JSON-LD with placeholders for URLs, upload date and duration.

3. EMAIL LINE — for the lifecycle or re-engagement email that pitches this capability: one plain-text line with the real length ("90-second video: …") linking to the question page, never to YouTube. No dollar amounts. Say which email it belongs in.

4. WORDPRESS.ORG — only if the FREE version answers the question: the readme placement (YouTube URL on its own line near the top of the description, never the last line of an FAQ) and the caption for screenshot 1, which must carry the message alone because the embed does not show inside wp-admin. The readme shows what the plugin does; it does not pitch Pro. If the question is Pro-answered, write "NOT FOR README".

5. IN-PLUGIN POSTER — only for a Pro-answered capability: poster text of 8 words or fewer and the link text. A bundled image that opens the question page in a new tab. No iframe, no remote image. Modest, never nagging.

6. CAPTERRA AND G2 MEDIA — a one-line video caption and 3–5 screenshot captions in sequence.

7. SHOT LIST FOR THE EDITOR — the 3–5 screenshots (which frame, which crop, file name) and one GIF under 2 MB (which 3–5 seconds, loop point, nothing too small to read).

8. AD MATCH — name the existing headline and description in strategy §6.2 that match this page. If none does, propose at most 2 headlines (≤ 30 characters) and 1 description (≤ 90), each with its character count, plus exact-match keyword seeds from the research's SEARCH PHRASING marked "check in Keyword Planner". No competitor names, no price, no recovery language.

9. SANITY CHECK — run the FILE 6 audit list on every section. Print the table.

---
---

# ═══ FILE 6 of 6 — claim-audit-prompt.md ═══
*(run in Claude on ANY asset: a row, a Demo Brief, a script, a page, an email line, an ad · best in a fresh chat)*

Load the UNIVERSAL BLOCK and apply it in full.

Audit the asset I paste or attach. Find every false, stale, unprovable or forbidden claim. Check each line against this list:

1. **Owner rules (U2):** any spoken demo figure · any sentence about WooCommerce core or another tool without a check made today (do the check now, with URL and version) · any mention of a Free customer cap · any wrong link.
2. **Brief §D.1–D.2 (U3):** anything the plugin is said to do that it does not.
3. **Never-pitch and hooks (U4):** a ⚑ feature leading or narrated · a banned hook · "only / first / nobody else" · a competitor named or judged.
4. **Claims discipline (U5):** a narration sentence with no screen behind it · a statistic without a primary source · the D.4 embargo · a demo number presented as a result.
5. **Product facts:** every capability checked against its Brief entry — tier, exact UI title, menu path, definition, what the screen really displays.
6. **Tier and CTA (U7):** "Free only tracks…" · "imported / synced / migrated" · a price · more than one CTA · trial terms not confirmed in the task.
7. **Format (U8):** length, beats, product name in first frame and end card, a cut under 11 seconds.
8. **The row's own guardrails** in the Video Topic Filter for this ID.

OUTPUT:
- A table: line or section · the problem · the rule it breaks · the fix.
- The corrected asset in full.
- A verdict: **PASS** or **FIX FIRST**, and anything that needs Yeasir's decision.

Never soften a finding. If you cannot verify something, say so; do not assume it is fine.

---
---

## Changelog — v2 → v3

| Change | Where | Why |
|---|---|---|
| Pipeline cut from seven files to six; synthesis, shorts, social and blog retired | §0 | Strategy §2 replaces researched educational content with one demo per question |
| Final Content Doc → **Demo Brief** (screens, five beats, decision, honest sentence, claims ledger) | File 2 | The demo is the proof; there is no hook menu, damage section or derived angle any more |
| 4–6 minute script → 60–120 s five-beat script + YouTube package + poster | File 3 | Strategy §4.2–4.3 and §5.6; the poster feeds gate G2 |
| 3–4 shorts → one 20–30 s cut, never under 11 s | File 5 | Strategy §4.7 |
| Social sets, newsletter, blog → placement kit: question page, email line, wp.org, in-plugin, Capterra/G2, shot list, ad match | File 5 | Strategy §5 placement ladder |
| Research prompt rebuilt: core and competitor check first, owner language, search phrasing, buyer doubts; statistics and news hooks removed; verdict on the planned sentence added | File 1 | Videos carry no statistics; the risky line is the sentence about core |
| Reserved "only / first" list deleted | U4 | Behavioral tags are never-pitch, session history is a banned hook, and the integrity check covers the plugin's own figures |
| "10 RFM segments" and release-stage gates removed; "only what Brief 3.1.2 documents exists" added | U3 | RFM is never-pitch; 3.1.2 shipped Release 1.5 |
| Cart rule changed from "acknowledge core's recovery" to "observation only, say nothing about recovery" | U3 | Claims discipline and the recovery-language ban |
| Owner rules made a named block that beats every file: no spoken figures, freshness, **no Free customer cap**, links | U2 | The cap question kept returning; the Brief's own D.2 wording would otherwise leak into scripts |
| Recording brief: build per scene, answer-frame scene, Apply clicks, private-window storefront, 30-minute lag, `[DEV SETUP]`, no expected values, edit notes | File 4 | Free-only and Pro-only screens; demo values differ on recording day |
| Standalone claim audit added; every file ends by running it | File 6 | "Always do a sanity check" made one list instead of seven variants |
| Universal Block appears once instead of inside every file | top | The pack lives in the project; every file loads it |

*Version 3.0 — 21 September 2026.*
