# PureDevs Customer History — Proof-Led Marketing & Ad Strategy

**Version:** v1.0 · **Date:** 18 Sep 2026 · **Status:** Draft for sign-off · **Owner:** Yeasir
**Plan window:** Mon 21 Sep → Sun 15 Nov 2026 (8 weeks) · **Verdict date:** Mon 16 Nov 2026
**Reads with:** `PureDevs_30_Day_Ad_Launch_Plan_v1.md` · `PureDevs_Sales_Strategy_v1.md` · `PureDevs_Lifecycle_Emails_v1.1.md` · `PureDevs_Brevo_Automation_Setup_v1.md` · `PureDevs_Trial_Reengagement_Master_Brief.md`

**Evidence tiers used in this file**
- **[Cited]** — external source, listed in §14.
- **[Observed]** — from PureDevs' own documents, research or operations.
- **[Uncertain]** — a planning assumption. Replace with real numbers as soon as they exist.

**Precedence:** this file governs creative, landing pages, channel roles, budget gates and kill criteria. Where it disagrees with the 30-Day Ad Launch Plan v1 on campaign build details, reconcile before launch and log the change in §15.

---

## 1. The decision in one paragraph

PureDevs stops producing researched educational content. Every marketing asset from here is a **screen recording of the working plugin answering one question a store owner already has**, narrated by the founder, ending on the decision that number lets the owner make. Those videos are placed first where warm audiences already are (WordPress.org listing, inside the plugin, trial emails, pricing page, review sites), and only then amplified with Search ads and a small retargeting layer. The plan is frozen for eight weeks and judged on trial starts and cost per trial, not views.

**Why this is the right bet**
- Software buyers name free trials (77%) and product demos (73%) as the resources that most influence their decision. **[Cited: TrustRadius 2026]**
- 80% of people say they have bought or downloaded an app after watching a demo video; 63% prefer a short video when learning about a product. **[Cited: Wyzowl 2026 — vendor survey, treat as directional]**
- Generic informational content is losing its distribution: users click a regular result in 8% of searches when an AI summary appears, against 15% without one. Transactional queries trigger AI Overviews only about 5% of the time, so buying-intent search is where clicks still exist. **[Cited: Pew via Contently; Cognizo]**

**What the insight does not change:** this model only reaches people who are already in-market. That group is small, so placement and targeting do more work than volume.

---

## 2. What this replaces, keeps and adds

| | Item |
|---|---|
| **Replaces** | Researched educational posts, topic-led social content, generic "how to grow retention" material |
| **Keeps (unchanged)** | Lifecycle + playbook drip emails (v1.1), Brevo automations, Capterra/G2 programme, trial re-engagement campaign, Phase 2 cold outreach gating, all email rules (no dollar amounts, single sender, CTAs on puredevs.com) |
| **Adds** | Demo Video system (§4), question pages (§5.4), placement ladder (§5), Search + retargeting with spend gates (§6, §10) |

---

## 3. Who this reaches, in order of cost

| # | Buyer state | Where they are | Asset that meets them | Cost |
|---|---|---|---|---|
| 1 | Installed the free version | Inside WP admin | Poster image → demo video next to each Pro capability; Freemius trial notice | Free |
| 2 | Evaluating on WordPress.org | Plugin listing | Readme video + screenshots in order of the questions | Free |
| 3 | In trial, lapsed trial, churned | Inbox | One matching demo per lifecycle / re-engagement email | Free |
| 4 | Comparing tools | Capterra, G2, AI assistants | Listing media + reviews | Free |
| 5 | Searching Google with buying intent | Search results | Search ad → question page with the matching demo | Paid |
| 6 | Visited pricing, no trial | YouTube / feeds | 20–30 s retargeting cut | Paid |

**Not targeted:** anyone without an existing WooCommerce store and an existing question about their customers or revenue. No cold video, no display prospecting.

---

## 4. The Demo Video system

### 4.1 The proof standard
Every asset must answer the four things a buyer checks, in this order:

| Buyer's silent question | What answers it | Where it lives |
|---|---|---|
| Is this my problem? | The title **is** their question | YouTube title, page H1, ad headline |
| Is it real? | Unedited screen recording on a seeded demo store | The video itself |
| Did it work for someone like me? | Capterra / G2 reviews, later named customer quotes | End card, question page, pricing page |
| What do I do next? | One CTA only | Start trial (→ `/pricing`) or install free |

### 4.2 Format spec
- **Length:** 60–120 seconds. One question per video. (Most marketers find 30 s–2 min the effective range; demos can run longer only when the question needs it. **[Cited: Wyzowl via Blondish]**)
- **Picture:** screen recording at 1080p, cursor highlighted, zoom on the number being discussed. No slides, no stock footage, no animated intro longer than 2 seconds.
- **Voice:** founder narration, own voice, conversational. Burned-in captions.
- **Data:** a seeded demo store only. Never a customer's store, never real customer names. This matters twice over for a product whose promise is "your data stays yours".
- **Name on screen:** "PureDevs Customer History" appears in the first frame and the end card (this also closes the known gap where the product name is missing from site copy). **[Observed]**

### 4.3 Five-beat anatomy
| Beat | Time | Content |
|---|---|---|
| 1. The question | 0–5 s | Say the owner's question in their words |
| 2. Where to click | 5–20 s | Open the plugin, show the path. State once that the data never leaves the site |
| 3. What it shows | 20–60 s | Read the numbers out loud on the demo store |
| 4. The decision it enables | 60–90 s | "So here is the decision this gives you…" |
| 5. Next step | last 10 s | One CTA |

**Rule for beat 4:** end on the decision and the money, never on the dashboard. PureDevs' own competitor research says analytics without action may not convert **[Observed]**, and the product philosophy is that the software observes, reports and predicts while the owner decides and acts **[Observed]**. Beat 4 is where those two meet: the plugin never acts, but the video always shows what the owner would do next.

### 4.4 Title rule and backlog
**Title = the store owner's question, not the feature name.** This keeps search discovery on YouTube and Google without writing educational content: the demo is the education.

| # | Title (the owner's question) | Capability shown | Decision it enables (beat 4) | Tier |
|---|---|---|---|---|
| V1 | How much of my WooCommerce revenue comes from returning customers? | New / returning / guest revenue split | Whether the next hour goes to existing buyers or first-time buyers | [FREE/PRO] |
| V2 | How much am I selling to guests I can't recognise next time? | Guest share inside the same split | Whether to encourage accounts at checkout | [FREE/PRO] |
| V3 | Which products do my customers actually buy together? | Frequently-bought-together | Which two products to bundle or place side by side | [FREE/PRO] |
| V4 | Where do shoppers drop off before they pay? | Store-wide funnel | Which step to fix first | [FREE/PRO] |
| V5 | How much money is sitting in abandoned carts right now? | Abandoned-cart value | Whether the leak is big enough to act on | [FREE/PRO] |
| V6 | Can I trust the numbers in my WooCommerce reports? | Data integrity check | Fix the data before deciding anything on it | [FREE/PRO] |
| V7 | When do my customers actually buy? | Day / hour heatmap | When to schedule emails, posts and ad budget | [FREE/PRO] |
| V8 | Where does my store data go when I install this? | Self-hosted: data in your own WordPress database, no SaaS account | Choosing tools that keep customer data on your own site | Free |
| V9 | What changed in Customer History 3.0? | Customer identity → revenue attribution | For existing and lapsed users: the reason to come back | Pro |
| V10 | Install to first insight in five minutes | Onboarding | Removes the "will this become a project?" objection | Free |
| V11 | A SaaS analytics dashboard or a plugin inside WordPress — what's the difference? | Setup and data location, side by side, factual only | Which model fits the store | — |
| V12 | Which group of customers deserves my attention this month? | Segments **[CONFIRM which segment types may be shown]** | Where to spend this month's effort | [FREE/PRO] |

V1–V7 map to the capabilities already cleared as safe for outbound copy. **[Observed]**

### 4.5 Hard exclusions (apply to titles, narration, captions, ads, pages)
- **Never pitch:** RFM, churn scores, health scores, behavioural tags. **[Observed — never-pitch list]**
- **Never say:** recovery language ("recover carts"), automation claims, coupons, loyalty mechanics, per-customer session history. **[Observed]**
- **Claims discipline:** no sentence in a video without the screen that proves it on camera.
- **Competitors:** factual comparison of model and data location only. No claims about a competitor's quality.

> **Correction logged:** the example title used in the 18 Sep verdict ("which customers are about to stop buying?") leans on churn prediction and is therefore **not** to be used. Use V1–V7.

### 4.6 Production workflow (one video per week, ~3.5 h)
| Step | Time | Note |
|---|---|---|
| Pick the next title from §4.4 | 5 min | Never invent a new topic mid-week |
| Script the five beats | 40 min | AI may draft from your bullet notes; the screen and the numbers must be real |
| Record screen + voice | 45 min | Two takes maximum |
| Edit, captions, end card | 75 min | Template once, reuse |
| Publish + place (§5 checklist) | 45 min | A finished video is not "done" until it is placed |

### 4.7 Derivatives from every video
1. Full video on YouTube (title = question).
2. **20–30 s cut** for retargeting and in-plugin use. Keep it above 11 seconds: shorter videos do not build a remarketing list when viewed as ads. **[Cited: Google]**
3. 3–5 screenshots in sequence (WordPress.org, question page, emails).
4. One GIF under 2 MB (emails, in-plugin).
5. Transcript → the written walkthrough on the question page.
6. Optional: vertical Short of the same cut.

### 4.8 Sample script — V1 (≈ 85 seconds)
> **[0–5 s]** How much of your revenue comes from people who've bought from you before? Most store owners guess. Here's how to see it in about a minute.
> **[5–20 s]** This is a WooCommerce store running PureDevs Customer History. I open **[UI-PATH]**. Nothing leaves the site — this is reading the store's own order data.
> **[20–55 s]** Here's the split: new customers, returning customers, and guests. On this demo store, returning customers are **[X]%** of buyers but **[Y]%** of revenue. Guests — people who checked out without an account — are **[Z]%**. That guest number matters, because you can't recognise them next time they buy.
> **[55–75 s]** So here's the decision this gives you. If returning customers are carrying the store, your next hour belongs to the people you already have. If it's mostly one-time buyers, the second purchase is the gap.
> **[75–85 s]** The plugin doesn't decide for you. It shows you where the money comes from. The free version is on WordPress.org, and the link below starts the Pro trial.

---

## 5. Placement ladder — free first, in this order

### 5.1 WordPress.org listing
- Paste the YouTube URL **on its own line** near the top of the readme description; it auto-embeds. Do not make it the last line of an FAQ section. **[Cited: WordPress.org]**
- The embed shows on wordpress.org but **not** in the "View details" window inside WP admin, so the first screenshot must carry the message for people searching from their dashboard. **[Cited: Core Trac #45853]**
- The readme should show what the plugin does, not pitch Pro. Use a video whose question the **free** version answers. **[Cited: WordPress.org Plugins team] [CONFIRM free vs Pro per capability]**
- Reorder screenshots to follow the question sequence V1 → V7.

### 5.2 Inside the plugin
- Next to each Pro capability: a **bundled** poster image or GIF that links out to the demo video in a new tab. Do not iframe YouTube or load remote images in the free version — the review team disallows calling files remotely and prefers a link over an iframe. **[Cited: WordPress.org common issues]**
- Freemius shows a dismissible trial offer 24 hours after the free version is installed; the trial/pricing screen it leads to is the highest-value spot for the V1 poster. **[Cited: Freemius docs]**
- Upsells stay modest: not prominent, not nagging. **[Cited: WordPress.org guidelines]**

### 5.3 Emails (Brevo lifecycle + re-engagement)
- One matching demo per email, as a plain text link ("2-minute video: …") to keep the plain-text style. **[Observed]**
- Links point to the **question page on puredevs.com**, never to YouTube. This extends, not breaks, the "all CTAs on puredevs.com" rule: attribution stays in GA4 and each question page carries a single CTA to `/pricing`. **[CONFIRM this rule extension]**
- No dollar amounts, one sender, exit rules unchanged. **[Observed]**
- Re-engagement follow-ups: add the demo that matches the capability pitched in that email. Review asks stay in separate emails, weeks apart from any offer. **[Observed — WordPress.org policy]**

### 5.4 puredevs.com
- **Pricing page:** V1 above the plans, then a three-tile "See it answer your question" row (V3, V4, V7).
- **Homepage hero:** V1 or V8. Product name in the H1 or the eyebrow line.
- **Question pages** (one per video): H1 = the question · video · 5–8 line written walkthrough from the transcript · 3 screenshots · one CTA to `/pricing`. Add `VideoObject` schema alongside the planned `SoftwareApplication` JSON-LD.
- Conversion rates fell in 13 of 14 industries in 2026 while ad CTR rose: the landing page, not the ad, is where paid search is won or lost. **[Cited: Foundry CRO]**

### 5.5 Capterra and G2
Upload the demo videos and sequenced screenshots as listing media. 51% of software buyers now start vendor research in AI tools, and those tools lean heavily on review sites — so reviews and listing content are also how PureDevs shows up in AI answers. **[Cited: G2 via MarketScale; Electric B2B]**

### 5.6 YouTube channel
Title = question. First description line = the one-sentence answer plus the question-page link. Pinned comment = trial link. Chapters for the five beats. The channel is a host and a search surface, not an audience-building project.

---

## 6. Paid

### 6.1 Channel roles
| Channel | Role | When |
|---|---|---|
| Google Search | Capture people already searching with buying intent | From Week 3 |
| Demand Gen (YouTube + feeds), **retargeting only** | Bring back pricing-page visitors and lapsed trials | From Week 5, once the list is big enough |
| Cold YouTube, Display prospecting, Performance Max | **Not used** | — |

Traditional Search outperforms Performance Max on efficiency for B2B lead generation, and exact match delivered roughly half the cost per qualified lead of phrase match in one agency's real-campaign data. **[Cited: 42 Agency]**

### 6.2 Search campaign
**Structure**

| Ad group | Intent | Seed keywords (exact first, phrase second) | Landing page |
|---|---|---|---|
| AG1 Category | Looking for the tool | `[woocommerce customer analytics]` `[woocommerce customer insights plugin]` `[woocommerce customer reports]` `[woocommerce customer history]` `"woocommerce customer history plugin"` | Homepage variant with V1 |
| AG2 Questions | Looking for the answer | `[woocommerce new vs returning customers]` `[woocommerce returning customer report]` `[woocommerce sales funnel report]` `[woocommerce sales by hour]` | The matching question page |
| AG3 Alternatives | Comparing | `[metorik alternative]` `[metrilo alternative]` `"self hosted woocommerce analytics"` `"woocommerce analytics without saas"` | V11 / V8 page |
| AG4 Brand | Protect | `[puredevs]` `[puredevs customer history]` | Pricing |

- **Held back for now:** "woocommerce crm" (intent is contact management and email), "frequently bought together plugin" (intent is an upsell widget), "abandoned cart" terms (intent is recovery, which PureDevs does not claim). Test later in a separate low-bid ad group only if AG1–AG3 are profitable.
- **Negatives at launch:** free, nulled, crack, gpl download, tutorial, course, jobs, salary, developer, hook, snippet, code, github, shopify, magento, login, google analytics, ga4.
- **Volume will be low.** That is expected for a niche plugin. Check Keyword Planner, but do not wait for volume data to launch. **[Uncertain]**
- **Geo / language:** English; start with US, UK, CA, AU, NZ, IE. Add markets where Freemius shows paying customers. **[CONFIRM against Freemius sales geography]**
- **Competitor names:** bid on them, but keep them **out of ad text**.

**Responsive search ad copy (all within limits — 30 / 90 characters)**

Headlines:
1. `WooCommerce Customer Analytics`
2. `See What Sells Together`
3. `New vs Returning Revenue`
4. `Your Data Stays On Your Site`
5. `Self-Hosted. No SaaS Account.`
6. `Runs Inside WordPress`
7. `A Self-Hosted Alternative`
8. `Watch The 2-Minute Demo`
9. `PureDevs Customer History`
10. `Start Your Free Trial` **[CONFIRM trial terms]**

Descriptions:
1. `See new vs returning revenue, what sells together and where shoppers drop off.`
2. `Customer analytics that runs inside WordPress. Your store data never leaves your site.`
3. `Watch a 2-minute demo on a working WooCommerce store, then try it on your own orders.`
4. `You decide what to do. The plugin shows you where the money comes from.`

**Tracking**
- Primary conversion: `trial_start`. Secondary (observation only): `checkout_open`, `video_play`.
- Fire GA4 events from the Freemius Checkout JavaScript callbacks on `/pricing`, then import into Google Ads. **[CONFIRM callback names against Freemius docs]**
- WordPress.org installs cannot be attributed to ads. Accept it; judge Search on trial starts only.

**Bidding and budget**
- **$10/day** to start (~$300/month). **[Uncertain — your call]**
- Maximise clicks with a max-CPC cap of $[MAX-CPC] until there are 15+ conversions in 30 days, then switch to maximise conversions.
- Raise budget only in +$5/day steps, only after a gate in §10 is passed.

### 6.3 Retargeting (Demand Gen)
- **Audiences:** pricing-page visitors without `trial_start` (30 days); trial starters without purchase (60 days); YouTube channel viewers.
- Google's minimum is now 100 active users on every network. **[Cited: MediaPost]** Wait for ~300+ before launching so delivery is not starved. **[Uncertain]**
- **Creative:** the 20–30 s cuts, rotating V1, V4, V8.
- **Settings:** audience expansion / optimised targeting **off**, frequency capped at 3 per week, $3–5/day.
- Judged on assisted trial starts. Pause if $100 produces none.

### 6.4 Seasonal note
Black Friday 2026 is 27 Nov, after this window closes. V1 ("returning vs new revenue") is the natural pre-BFCM message for the Week 7 email to free users and trials. "Who were my BFCM buyers?" is the first video of the next window.

---

## 7. Unit economics — do this before scaling

```
cost per trial  = CPC ÷ (click → trial rate)
CAC             = cost per trial ÷ (trial → paid rate)
LTV (net)       = [PRO-PRICE-PER-YEAR] × (1 − [FREEMIUS-FEE]) × [EXPECTED-YEARS]
Rule            = CAC ≤ one third of LTV (net)
```

| Scenario | CPC | Click → trial | Cost / trial | Trial → paid | CAC |
|---|---|---|---|---|---|
| A — good | $1.50 | 5% | $30 | 25% | **$120** |
| B — middle | $3.00 | 4% | $75 | 20% | **$375** |
| C — category average | $5.34 **[Cited]** | 3% **[Cited]** | $178 | 15% | **$1,187** |

Scenarios A and B use planning numbers **[Uncertain]**; C uses the B2B SaaS averages ($5.34 CPC, 3–5% click-to-trial). **[Cited: Kampaio / Involve Digital]** The lesson is in the spread: the landing page and the trial-to-paid rate move CAC far more than the click price does. Fill in your Freemius trial-to-paid figure and the Pro price, then set **[ALLOWABLE-COST-PER-TRIAL]** = allowable CAC × trial-to-paid rate.

---

## 8. Third-party proof (the leg this plan cannot supply itself)

Demo videos are first-party proof. The "did it work for someone like me?" question is answered only by other people.
- Capterra stays the primary review target; G2 secondary. **[Observed]**
- Review asks are never tied to value and never share an email with an offer. **[Observed]**
- When reviews exist: one quote on each question page, one on the pricing page, one line in the video end card.
- Target for this window: **[REVIEW-TARGET]** Capterra reviews by 15 Nov.

---

## 9. Weekly scorecard (10 minutes, every Monday)

| Area | Metric |
|---|---|
| Outcome | Trial starts (total and by source) · trial → paid (rolling 30 days) |
| Site | Pricing-page sessions · video play rate · checkout-open rate |
| WordPress.org | Downloads per day · active installs |
| Search | Spend · clicks · CTR · CPC · trial starts · cost per trial · search terms reviewed (Y/N) |
| Proof | Capterra reviews · G2 reviews |
| Production | Videos shipped (cumulative) · placements completed per video |

Views, likes and subscribers are not on the scorecard.

---

## 10. Gates and kill criteria (set now, not renegotiated mid-window)

| Gate | Check | If missed |
|---|---|---|
| **G1 Production** — end of Week 2 | V1–V3 live on YouTube, readme video in place, pricing page updated | The constraint is capacity, not strategy. Drop to one video per fortnight. Add no channels. |
| **G2 Free placements** — Week 5 | Four weeks after vs four weeks before: pricing → checkout-open rate, trial starts per week. Video play rate on pricing ≥ 10% of sessions | Play rate under 10% = placement or thumbnail problem, fix that first. Plays fine but no lift = wrong questions; re-rank §4.4 using trial-user replies before recording more |
| **G3a Search** — at $150 spend | CTR ≥ 2% (B2B SaaS non-brand averages 2.5–3.5% **[Cited: Foundry CRO]**) | Keyword/ad mismatch. Tighten to exact match, rewrite headlines 1–3 |
| **G3b Search** — at $300 or 100 clicks | ≥ 2 trial starts | Landing-page problem. Swap the first screen and video once, rerun $150 |
| **G3c Search** — at $450 total | Cost per trial ≤ **[ALLOWABLE-COST-PER-TRIAL]** | Pause Search. Keep every free placement. Revisit when reviews ≥ **[REVIEW-TARGET]** |
| **G4 Retargeting** — at $100 | ≥ 1 assisted trial start | Pause |

**Verdict on 16 Nov**
- **Continue and scale** if trial starts per week are up ≥ 30% on baseline at similar traffic, **or** Search cost per trial is inside the allowable number.
- **Continue free-only** if placements lifted trials but paid did not.
- **Escalate, don't iterate** if neither moved: the videos are a sunk asset that stays in place, paid stops, and the open question becomes the known one — whether analytics without an action layer converts, i.e. the Automation Plugin timing decision. **[Observed]**

---

## 11. Eight-week plan

| Week | Dates | Work |
|---|---|---|
| 1 | 21–27 Sep | Seed the demo store · confirm §4.4 tiers and exclusions · record V1, V2 · snapshot the last 4 weeks as baseline |
| 2 | 28 Sep–4 Oct | Record V3 · publish V1–V3 · readme video + screenshot reorder · pricing-page video block · question-page template + 3 pages · GA4 events + Google Ads link · **G1** |
| 3 | 5–11 Oct | V4 · add demo links to trial-conversion and re-engagement emails · **launch Search** (AG1, AG3, AG4) |
| 4 | 12–18 Oct | V5 · in-plugin poster links in the next release · Capterra/G2 media · first search-terms review · **G3a** |
| 5 | 19–25 Oct | V6 · launch retargeting if the list is ready · **G2** |
| 6 | 26 Oct–1 Nov | V7 · add AG2 with matching question pages · **G3b** |
| 7 | 2–8 Nov | V8 · pre-BFCM email to free users and trials (V1) · **G3c / G4** as spend allows |
| 8 | 9–15 Nov | No new production · close placement gaps · pull the scorecard · **verdict 16 Nov** |

**Floor budget for the window [Uncertain]:** Search ≈ $420 · retargeting ≈ $110 · total ≈ **$530**.

---

## 12. Capacity rule
One PureDevs video per week, no more. Two rules settle every conflict: **placing a finished video beats recording a new one**, and **replying to a trial user beats both**.

---

## 13. Open items to confirm
1. Free vs Pro tier for each capability in §4.4 (decides the WordPress.org video).
2. Whether the never-pitch list written for re-engagement emails also binds video — this file assumes **yes**.
3. Which segment types may be shown (V12).
4. Whether V9 may show a per-customer view without breaching "no per-customer session history".
5. Rule extension in §5.3: email links may point to puredevs.com question pages, not only `/pricing`.
6. Narrator name on videos must match the email sender identity (sender-name reconciliation is already an open item).
7. Pro price, Freemius fee, trial-to-paid rate → §7.
8. Trial terms wording for ad headline 10.
9. Reconcile campaign build details with `PureDevs_30_Day_Ad_Launch_Plan_v1.md`.

---

## 14. Sources
| Claim | Source |
|---|---|
| Trials 77% / demos 73% influence | TrustRadius 2026 B2B Buying Disconnect, via MarketingProfs — marketingprofs.com/charts/2026/55339 |
| Demo video stats; 63% prefer short video | Wyzowl, State of Video Marketing 2026 — wyzowl.com/video-marketing-statistics |
| 30 s–2 min effective length | Wyzowl 2026 via Blondish — blondish.net/video-marketing-statistics |
| 8% vs 15% clicks with AI summary | Pew Research, via Contently — contently.com/2026/04/27/ai-overview-traffic-impact |
| ~5% AI Overview rate on transactional queries | Cognizo — cognizo.ai/blog/google-ai-overviews-statistics |
| Readme video embed; readme is not a Pro pitch | make.wordpress.org/plugins/2018/03/06/understanding-readme-txt |
| Video missing in WP-admin details window | core.trac.wordpress.org/ticket/45853 |
| No remote files; link preferred over iframe | developer.wordpress.org/plugins/wordpress-org/common-issues |
| Freemius trial notice 24 h after install | freemius.com/help/documentation/selling-with-freemius/free-trials |
| 51% start vendor research in AI tools | G2 2026 Buyer Behavior Report, via MarketScale |
| AI tools draw on review sites | Electric B2B (Substack) summary of buyer research |
| CPC $5.34; click-to-trial 3–5% | Kampaio — kampaio.com/blog/b2b-saas-google-ads-benchmarks-2026 |
| CTR up, conversion down in 13 of 14 industries; non-brand CTR 2.5–3.5% | Foundry CRO — foundrycro.com/blog/google-ads-benchmarks-by-industry-2026 |
| Search beats PMax; exact vs phrase | 42 Agency — intel.42agency.com/b2b-benchmarks/google-ads-benchmarks |
| Remarketing minimum now 100 users | MediaPost — mediapost.com/publications/article/411616 |
| Videos under 11 s do not build lists | Google — support.google.com/displayvideo/answer/9000233 |

---

## 15. Changelog
- **v1.0 — 18 Sep 2026.** First version. Adopts proof-led content in place of educational content; defines the Demo Video system, placement ladder, Search + retargeting roles, unit-economics model, gates and the eight-week freeze. Logs the correction to the churn-based example title.
