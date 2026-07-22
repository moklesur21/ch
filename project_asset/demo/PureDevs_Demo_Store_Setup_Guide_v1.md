# PureDevs — Demo Store Setup Guide v1

## Building the Recording Environment for the 12-Video Sequence

**Prepared for:** PureDevs developer
**Date:** July 2026
**Version:** 1.0
**Companion to:** PureDevs_Video_Topic_Filter_v2.1.md, PureDevs_Video_Production_Strategy_v3.md, RELEASE-3.0.0-MARKETING-BRIEF.md, PureDevs_Feature_Reference_Card.md

---

## 1. What This Store Is For (Read This First)

Every one of the 12 planned videos ends with a 2–3 minute screen recording of Customer History 3.0.0 solving that video's problem inside a real wp-admin. The production rule (Video Strategy v3 §3) is absolute: **real screen recording, real data, no mockups.** If the store can't show the topic's scenario, we delay the video — we never fake it in editing.

This store is that recording environment. It has four jobs:

1. **Look like a real ICP store.** A believable niche shop doing ~$20k/month with repeat-purchase economics — because the scripts quote those numbers ("a store doing $20k a month", "average customer worth $300", "70% of carts vanish").
2. **Contain the exact scenarios each demo flow needs.** An at-risk list that looks earned. A Champions segment. Two customers with identical $60 orders and wildly different lifetime values. Abandoned carts from yesterday. Three years of seasonal history.
3. **Stay alive for ~6 months.** Videos record from Week 1 through Week 23. Churn scores, health trends, "this month" revenue, and abandoned carts all decay with time — the store needs a weekly data top-up, not a one-time seed.
4. **Stay the same store throughout.** Same brand, same customers, same dataset evolving forward — channel coherence requires that a viewer who watches V3 and V9 recognizes the same shop. Never reset between videos.

**Definition of done:** every row in the per-video acceptance checklist (Section 10) passes, and the keep-alive routine (Section 9) is documented and handed over.

**Two things to verify before investing in the full build** (they de-risk everything downstream — see Section 8):

- Confirm the plugin correctly computes RFM / health / churn / lifecycle from **backdated** orders (orders inserted with past dates). Seed a small test batch first.
- Confirm the Pro **session data import (CSV)** works for backfilling browsing/search history, by exporting a few real sessions and mirroring the format.

---

## 2. Store Concept & Brand

### 2.1 The concept: a specialty coffee roaster

Recommended store type: **direct-to-consumer specialty coffee roaster.** This is not arbitrary — it's the single best fit for what the 12 demos must show:

| Requirement from the videos | Why coffee delivers it |
|---|---|
| Churn prediction must look meaningful (V1, V9, V12) | Coffee reorders on a natural 2–6 week cycle. "Customer who ordered every 3 weeks went silent 5 weeks ago" is instantly legible — a missed reorder *is* the churn signal. |
| Predicted next purchase (V1, V11) | Regular cadence makes "predicted next purchase date" and "days overdue" credible numbers. |
| 3 years of seasonal data (V8) | Q4 gifting is a real, dramatic coffee peak. Gift sets and samplers spike Nov–Dec; the seasonal chart writes itself. |
| Behavioral variety — 15 auto-detected patterns (V11) | Gift boxes (Gift Shopper), 5lb wholesale bags (Bulk Buyer), sale-day buyers (Bargain Hunter), Saturday browsers (Weekend Shopper), single-blend devotees (Brand Loyal), cart abandoners — all natural coffee behaviors. |
| Believable cart values (V6) | Carts of $40–$90 make abandoned-cart value-at-risk numbers feel real. |
| ICP recognition | Specialty coffee with repeat purchases is literally Tier 1 in the Sales Strategy ICP list. The verifying prospect sees their own store type on screen. |

### 2.2 Brand kit

Build a lightweight but complete brand so nothing on screen says "test site":

| Element | Spec |
|---|---|
| **Store name** | Pick a fictional name and **verify no real coffee store uses it** (Google it, check the .com, check Instagram). Candidates to check: *Driftline Roasters*, *Ember & Oak Coffee*, *Northbound Roasting Co.* Founder makes the final call. |
| **Domain** | Buy the matching domain (~$10/yr). The browser address bar appears in every recording — `localhost:8888` or `demo.puredevs.com` breaks the illusion instantly. |
| **Logo** | Simple text wordmark (Canva, 30 minutes). No elaborate design needed — it appears small in the site header. |
| **Colors** | Warm, credible palette (e.g., deep brown / cream / one accent). Applied to theme header, buttons, and product badges. Consistency matters more than beauty. |
| **Voice** | Product descriptions written like a real roaster: origin notes, tasting notes, roast level. 2–3 sentences each is enough. |

### 2.3 Two rules for this build

1. **Do NOT install WooCommerce Subscriptions.** Repeat behavior is simulated with normal, individually placed orders on a regular cadence. The analytics look identical, and it avoids an entire layer of extension complexity. The "subscription-like" customers are just customers whose seeded orders repeat every 2–6 weeks.
2. **All customer data is invented — and safely invented.** Names come from a generator (no real people, no celebrities). Emails use plausible-looking but verified-unregistered fictional domains (e.g., `d.okafor@harborpost-mail.com` style) — never real providers like gmail.com, because a real person's address could coincidentally match on camera. The plugin never sends email to customers (Brief §10), so the risk is display-only, but keep it clean anyway.

---

## 3. Technical Foundation (Step-by-Step)

### 3.1 Environment

1. **Host on real hosting, not localhost.** Any decent managed WP or small VPS. Recordings need real page-load behavior and a real URL. Nothing kills verification trust like a dev environment leaking into frame.
2. **Point the purchased domain** at it, install SSL. The padlock should show in the address bar.
3. **Block search engines:** enable Settings → Reading → "Discourage search engines," and add an `X-Robots-Tag: noindex` header at the server level. The store must never rank, never confuse a real shopper, never appear in brand searches for the fictional name.
4. **No password protection / coming-soon plugins.** They interrupt recording flow and add popups. Noindex is sufficient protection because nobody is ever sent here.
5. **Payments in test mode only.** Stripe test mode or Cash on Delivery. Checkout must be completable (we need it to create abandoned and recovered carts through real browsing) but must never be able to take real money.
6. **PHP 8.2+, recent MySQL/MariaDB**, object caching optional. The store should feel fast on camera.
7. **Daily automated backups** (UpdraftPlus or host-level). The seeded dataset represents days of work — losing it mid-sequence would stall the whole video calendar.
8. **Server timezone + WordPress timezone: US-based** (e.g., America/New_York). All "days overdue" and "abandoned 3 hours ago" math shows in this timezone on camera.

### 3.2 WordPress + WooCommerce configuration

1. Install latest WordPress + latest WooCommerce.
2. **Enable HPOS** (WooCommerce → Settings → Advanced → Features → High-Performance Order Storage). The plugin is HPOS-compatible and the demo should reflect a modern store setup.
3. Store address: a US city. Currency: **USD**. All script damage frames are in dollars.
4. Shipping: one flat-rate zone ($6 flat, free over $50). Enough to make order totals look real; no complexity beyond that.
5. Taxes: off (or a single simple rate). Tax rows add visual noise to order screens without adding realism.
6. Complete the Woo onboarding fully so no setup wizard banners linger in wp-admin.

### 3.3 Theme & front-end shell

1. Use a lightweight theme — **Storefront** or a clean block theme. Speed and clarity beat beauty; the front end appears on camera only briefly (product pages, and during cart-abandonment browsing).
2. Apply the brand: logo, colors, typography, a simple hero on the homepage ("Freshly roasted, shipped weekly" type message with a product image).
3. Menus: Shop, About, Contact. Footer with the standard links. No dead links, no "Sample Page."

### 3.4 Customer History 3.0.0 Pro

1. Install the plugin; activate the **Pro license** (founder grants this via the Freemius dashboard — ask him, don't create a checkout).
2. In plugin settings, **enable "exclude bot/admin sessions"** (Pro session tool) *before* any session seeding begins — otherwise your own admin browsing pollutes the analytics forever.
3. Weekly email digest: off (nobody needs it, and it keeps the inbox clean).
4. All customer-simulation browsing (for carts and sessions) happens **logged out, in incognito windows or scripted headless sessions** — never while logged in as admin.

### 3.5 Admin hygiene for recording

The wp-admin itself is on camera in every video. Set it up once, keep it clean:

1. **Admin account display name = the founder's real name.** The "Howdy, …" corner and any user references in recordings should show him, not "admin" or the developer.
2. Default WordPress admin color scheme. No admin-theme plugins.
3. **Only these plugins active:** WooCommerce, Customer History Pro, the theme's requirements, backup plugin. No SEO plugins, no Jetpack, no page builders — every extra plugin adds menu items and notices to the frame.
4. Dismiss every admin notice; disable WordPress/plugin auto-update nags where possible. Before each shoot, check for new update badges (see the pre-recording checklist in Section 9).
5. Recording defaults (for the founder, but the store should be verified against them): 1920×1080, browser at 100% zoom, bookmarks bar hidden, a dedicated clean browser profile.

---

## 4. Catalog & Front-End Build

### 4.1 Category structure

```
Shop
├── Single Origin
├── Blends
├── Gifts & Samplers
├── Equipment
└── Wholesale (5lb)
```

### 4.2 Product list (~20 SKUs)

Prices are chosen deliberately — they make the engineered scenarios work (the $60 twin carts in V5, the seasonal gift spike in V8, the bulk-buyer pattern). Keep them as specified unless there's a good reason.

| # | Product | Category | Price | Role in the data |
|---|---|---|---|---|
| 1 | Ethiopia Yirgacheffe (12oz) | Single Origin | $22 | Core repeater SKU; part of the $60 twin cart |
| 2 | Colombia Huila (12oz) | Single Origin | $20 | Core repeater SKU |
| 3 | Guatemala Antigua (12oz) | Single Origin | $21 | — |
| 4 | Kenya AA (12oz) | Single Origin | $24 | Premium; Champions buy it |
| 5 | Sumatra Mandheling (12oz) | Single Origin | $22 | — |
| 6 | Brazil Cerrado (12oz) | Single Origin | $18 | Entry price point |
| 7 | House Blend (12oz) | Blends | $18 | Highest volume SKU; part of the $60 twin cart |
| 8 | Breakfast Blend (12oz) | Blends | $17 | — |
| 9 | Espresso Blend (12oz) | Blends | $19 | Used in the V7 live segment-build |
| 10 | Dark Roast Blend (12oz) | Blends | $18 | Nina Petrov's Brand-Loyal SKU (V11) |
| 11 | Decaf Colombia (12oz) | Blends | $20 | Part of the $60 twin cart |
| 12 | 3-Bag Sampler | Gifts & Samplers | $45 | Seasonal spike + Gift Shopper |
| 13 | Coffee Lover's Gift Box | Gifts & Samplers | $65 | Q4 spike driver (V8) |
| 14 | Holiday Gift Set | Gifts & Samplers | $75 | **Only sells Nov–Dec** each year (V8) |
| 15 | Pour-Over Dripper | Equipment | $32 | — |
| 16 | Paper Filters (100pk) | Equipment | $9 | Attach-rate item |
| 17 | Ceramic Mug | Equipment | $15 | Gift attach |
| 18 | Digital Scale | Equipment | $49 | — |
| 19 | Burr Grinder | Equipment | $129 | Nina Petrov's predicted next purchase (V11) |
| 20 | House Blend 5lb Bag | Wholesale | $79 | Bulk Buyer pattern |
| 21 | Espresso Blend 5lb Bag | Wholesale | $85 | Bulk Buyer / Cannot-Lose customer |

**The $60 twin cart (V5):** House Blend $18 + Ethiopia Yirgacheffe $22 + Decaf Colombia $20 = **$60.00**. Both twin customers order exactly this cart on the same morning (details in Section 6).

### 4.3 Product content

- Each product: 2–3 sentence description (origin, tasting notes, roast level), one good photo.
- **Photos:** consistent stock photography (Storyblocks per the production stack, or similarly licensed sources). Same style across the catalog — mixed photography styles read as fake faster than anything else. Coffee bags can share one bag mockup with different label colors.
- 2–3 short product reviews on the top 5 SKUs (optional, adds front-end realism; invented reviewer names follow the same safety rules as customers).

### 4.4 Pages

- **Home:** hero + featured products + one "our roastery" strip.
- **About:** 3–4 paragraphs of invented roaster story. Nobody reads it, but the nav link must not 404.
- **Contact:** simple form.
- **Cart + Checkout:** fully functional in test mode — verified end-to-end, because abandoned/recovered cart creation depends on real checkout sessions (Section 7.6).

---

## 5. The Data Model — Numbers the Store Must Hit

These targets come straight out of the video scripts' damage frames. If the store's numbers contradict what the founder says on camera, the verification asset breaks. Match them.

### 5.1 Headline targets

| Metric | Target | Where the scripts reference it |
|---|---|---|
| Order history depth | **36 months** (backdated to ~Aug 2023) | V8: "three years of sales data" |
| Current monthly revenue | **~$19–21k/month** | V6 + V7: "a store doing $20k a month" |
| Average order value | **~$58–62** | Keeps order counts in ICP range |
| Orders per month (current) | **~330** | ICP: 50–500 orders/month |
| Total unique customers (36 mo) | **~1,900–2,100** | Average LTV then lands ~$270–300 → V1: "average customer worth $300" |
| Repeat purchase rate | **~45%** of customers have 2+ orders | Relationship-driven store profile |
| Active regular repeaters | **~140–170 customers** on a 2–6 week cadence | The churn/health/prediction engine's raw material |
| Cart abandonment rate | **~70%** (cart → purchase) | V6: "7 out of 10 carts vanish" (matches Baymard) |

### 5.2 The 36-month revenue curve

Shape the backfill like a real growing store, not a flat line:

| Period | Monthly revenue shape | Notes |
|---|---|---|
| Year 1 (Aug 2023 – Jul 2024) | Ramp $6k → $13k | Small store finding its feet |
| Nov–Dec 2023 | Spike to ~1.6× trend | First Q4; Holiday Gift Set + Gift Box drive it |
| Year 2 (Aug 2024 – Jul 2025) | Ramp $13k → $19k | Growth year |
| Nov–Dec 2024 | Spike to ~$29–31k | Q4 again, bigger |
| Year 3 (Aug 2025 – Jan 2026) | Plateau ~$20k | Maturity |
| Nov–Dec 2025 | Spike to ~$32–34k | Third Q4 — three visible peaks total (V8's chart) |
| Feb – Jul 2026 | The engineered flat/divergence period | See 5.3 — this is V4's story |
| Every May | +10–15% bump | Mother's Day gifting — a second, smaller seasonal signal |

**Seasonal SKU behavior (V8 depends on this):** Holiday Gift Set sells *only* in Nov–Dec. Gift Box and 3-Bag Sampler sell year-round but 3–4× volume in Nov–Dec. When the founder opens product-level time patterns in V8, these products must visibly own the peaks.

### 5.3 The last six months — V4's engineered divergence

V4's contradiction: traffic at all-time high, revenue stuck for five months, and the leak is invisible until you split new vs returning. Seed the last six months to tell exactly that story:

| Month | New-customer revenue | Returning revenue | Total | Sessions (traffic) |
|---|---|---|---|---|
| Feb 2026 | $7.0k | $14.2k | $21.2k | 9,600 |
| Mar 2026 | $7.1k | $13.9k | $21.0k | 10,100 |
| Apr 2026 | $7.2k | $13.5k | $20.7k | 10,800 |
| May 2026 | $7.3k | $13.1k | $20.4k | 11,400 |
| Jun 2026 | $7.2k | $12.8k | $20.0k | 12,000 |
| Jul 2026 | $7.3k | $12.5k | $19.8k | 12,500 |

Read: traffic +30%, new-customer revenue flat, **returning revenue in clean visible decline**, total drifting sideways-to-slightly-down. That's the exact chart the V4 demo zooms into. The keep-alive routine (Section 9) preserves this pattern until V4 is recorded.

### 5.4 Monthly funnel ratios

Sessions are backfilled via the Pro session import (Section 7.7) and must produce a believable store-wide funnel (Pro's aggregate conversion funnel, used in V6's context and available everywhere):

| Funnel stage | Monthly volume (current) | Rate |
|---|---|---|
| Sessions | ~12,500 | — |
| Product views | ~5,600 sessions with views | ~45% |
| Add to cart | ~1,150 | ~9% of sessions |
| Checkout started | ~470 | — |
| Purchase | ~330 | **~29% of carts → ~71% abandonment** |

### 5.5 Order status mix

Real stores aren't 100% "Completed." Across the 36 months:

| Status | Share | Purpose |
|---|---|---|
| Completed | ~92% | Normal |
| Processing | ~3% (recent orders only) | Normal |
| Refunded (full) | ~1.5% | V3 net-revenue math |
| Partially refunded | ~0.5% (a dozen orders, some recent) | Data Integrity Checker fodder (V3) |
| Cancelled | ~1.5% | Normal |
| Failed | ~0.7% | Normal |
| On-hold (aging, months old) | ~0.3% | Deliberate "unusual status" for the Integrity Checker |

Also seed **3–4 historical coupon codes** (e.g., WELCOME10, HOLIDAY15, SPRING10) applied to ~8% of orders, clustered in bursts. Coupons are native WooCommerce (no guardrail issue) and they're what the Bargain Hunter behavioral pattern detects.

---

## 6. The Hero Customers — the Engineered Cast

Bulk-generated customers give the store texture. **Heroes give the founder someone specific to click during the demo.** Each hero is hand-authored: fixed name, fixed order history, fixed behavior, engineered to land in a specific plugin state. These are the people who appear full-screen in the videos.

Author them exactly; the acceptance checklist (Section 10) verifies each one.

| Hero | History to seed | Engineered plugin state | Videos |
|---|---|---|---|
| **Daniel Okafor** — the declining regular | 26 orders over 20 months, every ~3 weeks, LTV ~$1,560. Last order **38 days ago** (usual gap: 21). Sessions stopped ~30 days ago. | Health score dropped 80s → 40s, trend **declining**; churn risk High/Critical; ~17 days overdue; sits at/near top of at-risk list with recency-drop + frequency-drop + missed-pattern components visible. | V1, V12 |
| **Priya Raman** — the Champion | 41 orders over 30 months, LTV ~$2,700, last order 6 days ago, regular sessions, buys Kenya AA + Espresso Blend. | **RFM 5,5,5 — Champions.** Health 90s, improving. The profile V2 opens. | V2, V7 |
| **Emily Hart** — twin A (new) | One $20 order 3 weeks prior + **the $60 twin cart** on recording morning. **LTV = $80.** | New Customer segment, low monetary score. | V5 |
| **Robert Chen** — twin B (loyal) | 63 orders over 34 months + **the same $60 twin cart** the same morning. **LTV ≈ $4,040.** | High monetary, Loyal/Champions, long lifetime-spend chart. | V5 |
| **Marisol Vega** — Cannot Lose | 5lb wholesale buyer, ~monthly for 2 years, LTV ~$4,800. **Silent for 4 months.** | RFM **Cannot Lose**; churn critical; the scariest row on the at-risk list. | V1 (list depth), V10 |
| **Tom Beckett** — the hibernator | 9 orders, LTV ~$520, last purchase **7 months ago**. | RFM **Hibernating**; lifecycle Churned/At Risk. The win-back profile V10 opens. | V10 |
| **Grace Lindqvist** — hibernator #2 | 6 orders, LTV ~$310, last purchase 6 months ago. | Hibernating list depth. | V10 |
| **Aaron Doyle** — mid-tier at-risk | 12 orders, LTV ~$700, drifting: gaps stretching from 4 to 9 weeks. | Medium churn risk — the at-risk list needs mixed severity, not all-critical. | V1 (list) |
| **Nina Petrov** — the predictable browser | 14 orders of Dark Roast Blend, every ~4 weeks, on schedule. Last 3 weeks of sessions: repeated Burr Grinder page views + searches "burr grinder", "grind size pour over". | Behavioral tag **Brand Loyal** (high confidence); rich session + search history; the visible pattern → **predicted next purchase: Burr Grinder**. | V11 |
| **Kofi Mensah** — the bulk buyer | Quarterly 5lb-bag orders, AOV ~$160, LTV ~$1,900. | Behavioral tag **Bulk Buyer**. | Tag coverage, list texture |
| **Hannah Ashe** — the gift shopper | Orders **only in Nov–Dec** of 2023, 2024, 2025 — gift sets and boxes. | Behavioral tag **Gift Shopper**; a human face for V8's seasonality. | V8, tag coverage |
| **Leo Marchetti** — the weekend shopper | 11 orders, every one placed Sat/Sun. | Behavioral tag **Weekend Shopper**. | Tag coverage |
| **Sofia Brandt** — the bargain hunter | 8 orders, all coupon-applied, clustered on the seeded coupon bursts. | Behavioral tag **Bargain Hunter**. | Tag coverage |
| **Jae-won Park** — the cart abandoner | 3 purchases + 6 abandoned carts over time, **including one abandoned Tuesday and recovered Thursday** in the week before V6 records. | Behavioral tag **Cart Abandoner**; supplies V6's per-cart detail AND the recovered-cart example. | V6 |
| **Ruth Adeyemi** — the newcomer | First order 5 days ago. | RFM **New Customer**; lifecycle New. Segment coverage. | List texture |

### 6.1 Cast rules

- **The at-risk list must show 10–15 customers with mixed risk levels** (a few critical, several high, some medium). Daniel, Marisol, and Aaron anchor it; the bulk generator's natural decliners fill the rest. An at-risk list of exactly 3 hand-made rows looks staged.
- **All 10 RFM segments must be populated** (see 7.3) and the five ready-made Pro segments (High Value At Risk, Loyal Champions, Needs Attention, Hibernating, High Health Declining) must each return members.
- **The twins are a scheduled event, not part of the initial seed.** Emily's and Robert's identical $60 orders are created the morning V5 records (Week 9) so both show "today" in the orders list. Emily's $20 prior order goes in ~3 weeks before that.
- **Heroes are protected.** The keep-alive routine must never accidentally give Daniel, Marisol, Tom, or Grace a new order before their videos are shot — a single top-up order destroys weeks of engineered decline. Keep the protected list (Section 9.2) beside the seeding scripts.

---

## 7. Feature-by-Feature Data Requirements

What each demo surface must show, and how the seed produces it. Where plugin internals determine the outcome (score formulas, trend windows, integrity triggers), the instruction is: **seed toward the target state, check the actual plugin output, adjust until it shows.** You have the plugin code — use it to confirm thresholds rather than guessing.

### 7.1 Churn Prediction (V1, V9, V12)

**Must show:** dedicated churn dashboard; an at-risk list of 10–15 mixed-severity customers; per-customer 0–100 churn score, risk level, days-overdue, predicted next-purchase date, primary churn indicator; alert assignment flow.

**How:** the regular-repeater core (~150 customers on fixed cadences) is what makes churn math work. Decliners are created by *stopping* a repeater's orders and sessions — the plugin does the rest. Verify how far "days overdue" and "predicted next purchase" respond to backdated data in the pilot batch.

### 7.2 Customer Health Score + Health Distribution (V1, V9, V12)

**Must show:** 0–100 scores with trend indicators (improving / stable / declining); the component breakdown on a profile (recency drop, frequency drop, missed pattern); a declining-health watchlist; a distribution view that looks like a real store (most customers mid-range, visible declining cohort of ~8–12%).

**How:** distribution emerges from the bulk mix. **Verify how the trend indicator is computed** — if it compares recent time windows, backdated decay produces it immediately; if it snapshots over real elapsed time, the store must be seeded 2–3 weeks before V1 records so trends mature. This is the single biggest reason the store is a Week-0 task. Test early.

### 7.3 RFM Segments (V2, V5, V7, V10)

**Must show:** the 10 named segments — Champions, Loyal, Potential Loyalist, New Customer, Promising, Needs Attention, At Risk, Cannot Lose, Hibernating, Lost — all populated; badges; distribution widget; list column + filter.

⚠️ **Note: 10 segments, not 11.** The Topic Filter still says "11-Segment Classification" in places; the Feature Reference Card corrects this against shipped 3.0.0 code. The store (and any on-screen narration) follows the Brief: **10**.

**Sanity ranges after full seed** (adjust the bulk mix until roughly true): Champions 5–8% · Loyal 8–12% · Potential Loyalist 8–12% · New 5–8% · Promising 5–8% · Needs Attention 8–12% · At Risk 5–8% · Cannot Lose 1–3% · Hibernating 10–15% · Lost 25–35%. Lost being the biggest bucket is correct — every store accumulates one-time buyers.

### 7.4 Lifecycle Stages + Behavioral Tags (V11 + texture everywhere)

**Must show:** all 6 lifecycle stages populated (Prospect → New → Active → Loyal → At Risk → Churned) with days-in-stage; behavioral tags with confidence % on profiles and as list filters. The heroes guarantee at least 6 of the 15 patterns appear prominently (Brand Loyal, Bulk Buyer, Gift Shopper, Weekend Shopper, Bargain Hunter, Cart Abandoner); confirm which of the remaining patterns the bulk data triggers and note any that never fire.

### 7.5 Revenue Analytics + New/Returning Split + Data Integrity (V3, V4, V12)

**Must show (V3):** net revenue with refunds visibly subtracted; the Data Integrity Checker passing with "Data Verified ✓"; then a **deliberate mismatch scenario** that makes the checker flag something.

**The mismatch toggle:** build a small, documented, repeatable procedure — e.g., temporarily desync one order's stored total (or introduce whatever condition the checker's logic actually tests: unusual status, partial-refund edge case) → checker flags it → record → revert to verified state. Work out the exact trigger from the checker's code and write the toggle steps into the handover runbook. The founder will run this live in V3; it must be two minutes, reliable, and reversible.

**Must show (V4):** the new-vs-returning split rendering the Section 5.3 divergence — returning line visibly declining across 6 months while new stays flat.

### 7.6 Abandoned Cart Tracking (V6, V12)

**Must show:** an admin list of ~15–25 carts with mixed statuses; per-cart detail with contents, captured email, abandonment timestamp; at least one **recovered** cart; a value-at-risk widget; freshness — a few carts from the last 24–72 hours, at least one from "today."

**How:** carts come from real front-end sessions. Script or manually run incognito browsing sessions: visit products → add to cart → enter email at checkout → leave. For the recovered example (Jae-won Park), abandon Tuesday, return Thursday with the same session/email and complete the purchase. Because cart timestamps are the whole point, **fresh carts are a pre-shoot task, not a one-time seed** (Section 9.3). Confirm at what point the plugin captures the email and how long until a cart counts as "abandoned," and time the pre-shoot browsing accordingly.

### 7.7 Sessions, Searches & Views (V11 + Search Analytics + funnel)

**Must show:** store-wide top/recent searches and most-viewed products; per-customer full session + search history (Nina Petrov's must tell her story); funnel volumes per 5.4.

**How:** backfill via the Pro **session import (CSV)**. First export a handful of organically created sessions to learn the exact schema, then generate import files that hit the monthly volumes and ratios. Search-term distribution to seed (approximate monthly counts, long tail behind them):

| Search term | Note |
|---|---|
| "ethiopian coffee", "dark roast", "espresso beans", "decaf" | Top terms — match the catalog |
| "gift set", "gifts under 50" | Spike Nov–Dec |
| "burr grinder", "grind size pour over" | Nina's story (V11) |
| "cold brew kit", "coffee subscription" | **Deliberately unmet demand** — no matching product. This is the "what shoppers want that you don't stock" beat for search analytics. |
| "expresso" | One realistic misspelling |

### 7.8 Saved Segments + Filters (V7)

**Must show:** the five ready-made segments returning members; the founder building a complex segment **live on camera** — e.g., *RFM = Champions + Country = US + Total spent > $200 + Purchased product = Espresso Blend* — and getting **8–15 members**, then saving it.

**How:** ensure billing countries across the base skew US/UK/CA/AU (matching ICP geography) and that enough Champions bought Espresso Blend for the live-built segment to return a satisfying, non-zero, non-huge count. Dry-run the exact filter combo before V7's shoot.

⚠️ **On "30+ filters":** the Feature Card flags this count as unverified. Count the filters in the shipped product and tell the founder the real number before V7's script is written — the video says whatever the product actually has.

---

## 8. Seeding — How to Build the Data

No code in this guide — approach and sequence only. You choose the implementation; these are the constraints it must satisfy.

### 8.1 Principles

1. **Bulk noise is generated; heroes are authored.** ~1,900 background customers can come from a generator. The 15 heroes and every engineered pattern (the V4 divergence, the seasonal curve, the twins) are hand-specified — random generation will never produce them.
2. **Test-first, always.** Before generating 36 months, seed **one small pilot batch** (~20 customers, ~60 backdated orders, a few sessions) and confirm: RFM computes, health scores compute, churn scores compute, trend indicators behave, session import works. If backdated data doesn't recompute correctly, everything changes — find out on day one, not day seven.
3. **Dates are the product.** Every engineered pattern is a pattern *in time* — order timestamps, gaps, seasonal clustering, session recency. Whatever tooling you use must let you set order and session dates precisely in the past.
4. **Keep the seed reproducible.** Scripts/CSVs in a small repo with a README. If the store ever has to be rebuilt (host failure, corrupted data), the rebuild should be a day, not a week.

### 8.2 Toolchain options

| Layer | Options | Notes |
|---|---|---|
| Bulk orders/customers | WooCommerce Smooth Generator (WP-CLI) with date ranges; or custom WP-CLI script; or CSV → programmatic order insert | Whatever you're fastest with — but verify it sets *paid/completed dates* in the past, not just post dates |
| Hero orders | Hand-inserted (WP-CLI or small script driven by a per-hero spec file) | Precision matters more than speed here |
| Sessions/searches/views | The plugin's own Pro **session import (CSV)** | Export-first to learn the schema (7.7) |
| Carts | Real incognito/headless browsing sessions against the live front end | Only realistic way; carts must be time-fresh anyway |
| Coupons, refunds, odd statuses | Native WooCommerce admin actions or scripted | Small volumes — manual is fine |

### 8.3 Build order

1. Environment + WordPress + WooCommerce + theme + plugin configured (Section 3), **admin/bot session exclusion ON**.
2. Catalog live (Section 4).
3. **Pilot batch** → verify all intelligence computes on backdated data (8.1.2). Adjust approach if needed.
4. Bulk backfill, oldest → newest: Year 1, Year 2, Year 3, shaping revenue to the 5.2 curve and the 5.3 divergence months. Include the status mix and coupon bursts from 5.5.
5. Check RFM distribution + health distribution against the 7.3 sanity ranges; adjust the bulk mix and re-run if a segment is empty or absurd.
6. Author the heroes (Section 6) — except the twins' event orders, which wait for V5 week.
7. Session/search backfill via CSV import, matching 5.4 volumes and the 5.3 traffic ramp; Nina Petrov's sessions authored individually.
8. First round of real browsing sessions → initial abandoned carts.
9. Build the Integrity Checker mismatch toggle procedure (7.5) and verify both states.
10. Run the full acceptance checklist (Section 10). Fix everything that fails. Then hand over.

---

## 9. Keep-Alive Routine (Weekly, ~30–45 min)

The store must look alive on every recording day across 23 weeks. This is the routine — it goes into the handover runbook and someone owns it every week.

### 9.1 Weekly data top-up

- Add **~75–85 orders** spread across the week (backdate within the week — never a single-day dump): ~55% from the regular-repeater core, ~35% new one-time customers, ~10% second orders from recent newcomers.
- Hold the revenue pattern: total ~$4.6–4.9k/week, returning share continuing the gentle 5.3 decline **until V4 is recorded (~Week 7)**; after V4, keep total flat around $19–20k/month for the rest of the sequence — the store's topline story must stay consistent through V12.
- Add ~2,800–3,000 sessions/week via import, continuing the traffic ramp until V4, then plateau.
- Rotate 1–2 organic-looking refunds/cancellations per month.

### 9.2 The protected list (do not touch)

Until their video is shot, these customers receive **no new orders and no new sessions**:

| Customer | Protected until | Why |
|---|---|---|
| Daniel Okafor | V1 recorded | His decline is the pilot's demo |
| Marisol Vega | V10 recorded | Cannot-Lose silence |
| Aaron Doyle | V1 recorded | Mid-tier at-risk texture |
| Tom Beckett, Grace Lindqvist | V10 recorded | Hibernation must hold ~19 weeks |
| Nina Petrov | V11 recorded | Buys Dark Roast on cadence, yes — but **never the grinder** |

### 9.3 The decliner conveyor belt

Daniel is perfect for V1 — but by V9 (Week 17) he'll read as fully churned, and V9 needs a customer **currently mid-decline** (the 30-days-early signal). Solution: **every ~4 weeks, retire one healthy repeater from the top-up list** (simply stop giving them orders/sessions). This produces a rolling supply of fresh decliners so that on any recording date, someone credible is in the early-warning window. Log who was retired and when.

### 9.4 Scheduled events

| Week (per Topic Filter calendar) | Event |
|---|---|
| Week 9 (V5) | Create the twins' identical $60 orders on recording morning; Emily's $20 prior order ~3 weeks earlier |
| Week before V6 (Wk ~10–11) | Jae-won Park's abandon-Tuesday / recover-Thursday sequence |
| Nov–Dec (if the sequence crosses it) | Ramp gift-SKU volume so the current year's Q4 matches the historical pattern |

### 9.5 Pre-recording checklist (24–48h before every shoot)

1. That video's acceptance row (Section 10) passes right now.
2. 4–6 fresh abandoned carts exist, at least one from today.
3. Current week's orders topped up — "this month" looks alive on every dashboard.
4. No update badges, admin notices, or plugin nags anywhere in wp-admin.
5. Founder's clean browser profile ready: 1920×1080, 100% zoom, bookmarks hidden.
6. Protected-list integrity spot-check (nobody got an accidental order).

---

## 10. Per-Video Acceptance Checklist

The developer runs this once at handover; the founder re-runs the relevant row before each shoot. Every row = open the page → this must be true.

| Video | Open | Must be true |
|---|---|---|
| **V1** Silent churn | Churn Prediction page | 10–15 at-risk customers, mixed risk levels. Daniel Okafor near top: high churn score, ~17 days overdue, health trend declining, components show recency drop + frequency drop + missed pattern. Alert assignment works. |
| **V2** Top 20% | RFM segments view | All 10 segments populated. Champions ≈ 5–8% of base. Priya Raman: RFM 5,5,5, LTV ~$2,700, clean profile. |
| **V3** Numbers wrong | Revenue Analytics + Integrity Checker | Net revenue visibly ≠ gross (refunds subtracted). Checker shows "Data Verified ✓". Mismatch toggle procedure flips it to a flagged state and back in under 2 minutes. |
| **V4** Traffic ≠ revenue | Revenue overview + new/returning toggle | 6-month view: returning line clearly declining, new line flat, total flat-ish — while session counts over the same period rose ~30%. |
| **V5** Customer worth | Customer list sorted by total spent | Emily Hart and Robert Chen both show a $60 order **today**; profiles show $80 vs ~$4,040 lifetime; Robert's lifetime spend chart spans ~3 years. |
| **V6** Abandoned carts | Abandoned Carts page | 15–25 carts, several < 72h old, one from today. A cart detail shows contents + captured email + timestamp. Jae-won Park shows one **recovered** cart. Value-at-risk widget populated. |
| **V7** Metorik replacement | Saved Segments | 5 ready-made segments return members. Live-build test: Champions + US + spent > $200 + bought Espresso Blend → 8–15 members, saves cleanly. Real filter count confirmed and reported to founder. |
| **V8** Seasonal stocking | Product reports + time patterns | 3 Q4 peaks visible across 36 months + May bumps. Product-level view: Holiday Gift Set / Gift Box / Sampler own the peaks. |
| **V9** 30 days early | Health Distribution | Realistic distribution with a declining cohort. At least one conveyor-belt customer (9.3) currently mid-decline: health trending down, **not yet churned**, decline visibly ~3–4 weeks old. |
| **V10** Win-backs | RFM → Hibernating | Hibernating ≈ 10–15% of base, sorted by last-purchase date. Tom Beckett: 9 orders, $520, 7 months quiet, full order + behavior trail on profile. |
| **V11** Predict next buy | Nina Petrov's profile | Brand Loyal tag with confidence %. Session history: repeated Burr Grinder views over ~3 weeks. Search history: "burr grinder" etc. Predicted next purchase reads as the grinder story. |
| **V12** Monday routine | Dashboard → Revenue → Health → Alerts → Carts → Top customers | Every stop on the 15-minute routine is populated with *current* data on the same day — this row is really "is the whole store alive right now." |

---

## 11. Build Plan & Handover

### 11.1 Timeline (~10 working days)

| Days | Work |
|---|---|
| 1–2 | Environment, domain, SSL, noindex, WP + Woo + HPOS, theme shell, plugin + Pro license, settings, admin hygiene |
| 3–4 | Brand pass + full catalog + pages + checkout verified in test mode |
| 5 | **Pilot batch + verification** of backdated computation and session import — the go/no-go on the whole approach |
| 6–7 | Full 36-month bulk backfill; distribution checks and adjustments |
| 8 | Hero customers authored and verified individually |
| 9 | Session/search backfill; first live-browse cart creation; Integrity mismatch toggle built |
| 10 | Full acceptance pass (Section 10); fixes; handover |

Then: keep-alive ~30–45 min/week for the life of the sequence.

**Scheduling note:** the pilot gate in Topic Filter §9 makes this store a hard prerequisite for V1 — and the health-trend question (7.2) may require the seeded store to *age* 2–3 weeks before trends look right. Start this build immediately; set V1's recording date off the store's readiness, not the calendar.

### 11.2 Handover package

1. Store URL + wp-admin credentials (founder-named account) + hosting/registrar access notes.
2. The seed repo: scripts/CSVs + README sufficient to rebuild from zero in a day.
3. **The runbook:** keep-alive routine (Section 9), protected list, decliner-belt log, scheduled events, Integrity mismatch toggle steps, pre-recording checklist.
4. Known quirks discovered during the build (how trends compute, cart-abandonment timing, anything the founder should know before narrating a demo).
5. Confirmed facts for scripts: real filter count (7.8), any feature label that differs from the docs.

### 11.3 Final step — fill the Feature Reference Card

With the store live, the founder (or you, together) spends the one 30-minute session the **Feature Reference Card** calls for: walk wp-admin, write the exact menu path label for every row of its Table 1. That unblocks Prompt C's plugin-feature field for all 12 topics — and it's the natural moment to sanity-check that every feature named in the card actually looks the way the Topic Filter's demo flows assume.

---

*Version 1.0 — July 2026. The demo store is a production asset with the same standard as the videos themselves: real plugin, real computation, engineered-but-honest data, alive for the full 23-week sequence. Build it once, keep it breathing weekly, and every demo from V1 to V12 records from the same believable store.*
