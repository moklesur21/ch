# Video 1 — Final Script

## How to Find WooCommerce Customers Who Are Quietly Slipping Away

**Source doc:** `FCD_V1_Quietly_Slipping_Away.md` (Final Content Doc, 25 July 2026)
**Topic Filter:** v3 §4 · Video 1 · Week 1, launch day (PILOT) · Stage **3.0.0 shipped** · Gate: **demo store only**
**Format:** verification asset — the demo is the centrepiece
**Runtime:** ≈5:35 · **Spoken words:** 777 (target 600–900)
**Hook used:** A — EVENT (Albertsons), as recommended by the doc's Video Script Brief
**Evidence used (at cap):** 2 stat cards · 2 pattern voices
**Written:** 25 July 2026 · **Freshness re-verified:** 25 July 2026 (see FLAGS)

---

## Title

**Default (recommended — Topic Filter v3 wording, unchanged):**
> How to Find WooCommerce Customers Who Are Quietly Slipping Away

**Variant 1:**
> The WooCommerce Report That Shows *Who* Stopped Buying, Not Just How Much

**Variant 2:**
> Your WooCommerce Revenue Looks Fine. Your Best Customers Might Not Be.

*All three avoid automation, certainty, fixed-window and total-replacement claims. Founder chooses.*

---

## SCRIPT

### ▸ HOOK + PROBLEM — 0:00–0:35

**[ON CAMERA, founder — brand bumper, then straight in. No greeting.]**

On the twenty-third of July, Albertsons cut its forecast for the year.

Management said the biggest leakage was lower-income shoppers moving over to Walmart and Amazon.

**[PAUSE]**

Nobody complained. Nobody cancelled anything. They just started buying somewhere else — and the company found out from a forecast revision, long after the behaviour changed.

**[GRAPHIC: lower-third — "Albertsons cut annual forecast · Reuters, 23 July 2026"]**

Your store does the same thing at a smaller scale. Traffic's fine. Orders still arrive. Nothing's broken.

And your best repeat customers are quietly buying less.

Your revenue chart can tell you *how much* changed. It can't tell you *who*.

---

### ▸ DAMAGE / CONTEXT — 0:35–2:00

**[VOICEOVER + B-ROLL: slow scroll over a healthy-looking revenue chart, then over an order list]**

Here's why that costs more than it looks like it should.

**[GRAPHIC: stat card — Gorgias, 2023 — "21% of customers. 44% of revenue."]**

Repeat customers are usually a minority of a store's customer list and a majority of its revenue.

So silent churn doesn't take a random slice out of your base. It takes it out of the part that was paying for everything.

**[PAUSE]**

And there's rarely a warning shot. One late delivery. One unanswered email. One out-of-stock at the wrong moment — and someone decides, quietly, without telling you. There's no ticket to close. Nothing fires.

So why does almost nobody catch this early?

**[GRAPHIC: stat card — Klaviyo, 2025 — "73% say acquisition costs more. 14% prioritise retention."]**

Klaviyo asked B2C marketers about this last year. Most said acquisition keeps getting more expensive. Far fewer said retention is where they're actually putting the effort.

Acquisition has dashboards, alerts, daily attention. The quiet exit has none of that.

**[VOICEOVER + B-ROLL: forum-style text on screen, blurred, non-specific — no real screenshots]**

A version of this keeps showing up in WooCommerce groups. Someone can quote this month's revenue to the dollar — but has never once looked at how long it's been since each repeat customer last ordered. Because no screen shows it.

And when they finally do notice, the list gets built by hand, out of an order export, months after the fact.

Which raises the obvious question. What would you actually have to be looking at to catch it earlier?

---

### ▸ REMEDY / DEMO — 2:00–5:00

**[SCREEN RECORDING — real demo store, Northline Supply Co. Full frame. Real data, no mockups.]**

Two things up front, so this is a fair comparison.

Churn scoring isn't rare — other WooCommerce tools do it, and some of them are free. And WooCommerce's own analytics are genuinely useful; they're just built to report transactions.

**WooCommerce shows you** that revenue moved and which orders made it up. **PureDevs adds** a per-customer health score, churn risk and recency signals on one screen — **so you can decide** which relationships get your next hour.

**[GRAPHIC: menu path callout — "Customer History → Dashboard"]**

This is the Customer History dashboard. Start with Health Distribution — every customer scored, grouped by how strong the relationship is right now.

**[PAUSE — hold on the chart]**

Don't read the numbers. Read the proportion. How much of this base sits in the healthy band, and how much has drifted the other way.

**[GRAPHIC: annotation ring on the declining band]**

Underneath it is the Declining Health watchlist. These aren't customers who complained. These are customers whose *own* pattern changed.

Let's open one. Everett Cole.

**[CLICK → Everett Cole's health detail]**

Watch the trend line, not the score. It drops by roughly half.

**[PAUSE]**

Nothing in the order log flagged that. There was no cancellation to react to. His next order just… didn't come.

Remember the store where nothing looked broken? **This is what "nothing" looked like.**

**[GRAPHIC: menu path callout — "Customer History → Churn Prediction"]**

Now the Churn Prediction screen. Every customer carries a risk score and a risk level, and the at-risk list is ranked.

Ignore the totals again. Look at the *order* of the list. The top of it is a short list — and that's the point. That's where the next hour goes.

**[CLICK → one at-risk customer profile]**

Open a profile and you can see *why* they're ranked there. Recency — how far past their own rhythm they've drifted. Frequency — the gap between orders widening. And a missed pattern — the reorder that should have landed and didn't.

**[GRAPHIC: annotation — "Recency · Frequency · Missed pattern"]**

Let me be straight about what this is. It flags risk. It gives you earlier warning signals. It doesn't promise anyone is leaving, and it won't hand you a date.

**[CLICK → Recommended Actions panel]**

And this panel is guidance to read — not something the plugin goes and does. It never emails your customers. It never runs a win-back. It never creates a coupon.

**[PAUSE]**

Store totals report that revenue changed. This names the customers behind the change — and shows health, recency, frequency and churn signals together, on one screen, on your own server.

**[GRAPHIC: "Customer Health Score · Churn Prediction — Pro"]**

---

### ▸ OUTCOME RESTATED — 5:00–5:35

**[ON CAMERA, founder]**

So — back to where we started.

Albertsons found their leak in a forecast. Your dashboard can hide the same thing just as politely.

The difference is whether anything on your screen names the people slipping away, while noticing still counts for something.

And it does count. Klaviyo's consumer research found that a meaningful share of customers who've had a bad experience will come back — if someone follows up.

**[PAUSE]**

So here's the move. Save a priority segment. Read the customer's context. Then choose a retention action yourself, outside the plugin.

It flags it. You act.

Health Score and Churn Prediction are Pro features in PureDevs Customer History for WooCommerce. Link's below.

**[BRAND OUTRO CARD — no subscribe ask, no email-list ask]**

---

## PRODUCTION NOTES

| Item | Detail |
|---|---|
| **Demo store** | Northline Supply Co., demo persona (Sarah M.) account only. Real data, no mockups — per Production Strategy v3's critical production rule. |
| **Click flow (4)** | Dashboard → Health Distribution · Declining Health alert → Everett Cole · Churn Prediction → at-risk list · one profile → risk components. Recommended Actions panel is the closing beat. |
| **Menu paths** | `Customer History → Dashboard` and `Customer History → Churn Prediction` — **[VERIFY IN WP-ADMIN]** before the recording brief; the Feature Reference Card's menu-path column is still unfilled. |
| **Hold times** | Every chart and list needs a minimum 5-second still hold — the narration deliberately does not read the numbers, so the viewer must have time to read the proportion themselves. |
| **Stat cards** | Exactly 2, both under 15 words on screen. Do not add a third in the edit. |
| **Pattern voices** | Exactly 2, both spoken as patterns. B-roll must not show a real forum post, real username or real screenshot. |
| **Ends on** | The result (the named at-risk customer and their evidence), never a settings screen. |

---

## FLAGS — verification, guardrails, risks

**Freshness re-check (completed 25 July 2026, before this script was written):**

- **WooCommerce core** — current stable is the 10.9 line (**10.9.4**, released 7 July 2026). **11.0 ships Tuesday 28 July 2026.** I read the 11.0 pre-release notes directly: its reporting work is failed-analytics-import surfacing with a retry path, a `refunds` value in each v3 sales-report date bucket, and a stable visitor ID requirement for analytics events. **No RFM, churn, cohort or LTV reporting arrives in 11.0.** The script's core-positioning line is therefore accurate through 11.0.
- **⚠️ Publication timing** — if this video publishes on or after **28 July**, core's **Checkout Recovery** is shipped (beta feature, manual send from an eligible order, under WooCommerce → Settings → Advanced → Features). This script never mentions carts, so nothing needs changing — but any cart-adjacent short or social post derived from it must acknowledge it.
- **AutomateWoo** — current version **6.6.0** (25 June 2026). Its Win Back workflows still key off the customer's last-order-date index; 6.6.0's related addition is a tool to rebuild that index so imported orders are counted. So the "calendar rule, not a customer signal" critique in the damage segment remains true today — **and the script deliberately never names AutomateWoo**, keeping the critique about the practice, not the product.
- **Competitors** — Woo's own Customer Analytics extension and the free plugins CRMlytics and Virtevo Insights all ship RFM, churn and/or 0–100 health scoring. **This is why the demo segment opens by saying churn scoring isn't rare and some tools are free.** That line is a guardrail, not a hedge — do not cut it in the edit.

**Guardrail compliance:**

- ✅ **No spoken figures anywhere in the demo.** Everett Cole's drop is spoken as "roughly half," never as the numbers. Distribution and at-risk totals are never read aloud — the narration points at proportion, rank, gap and trend only.
- ✅ **Observe / report / predict.** The script states outright that the plugin never emails, never wins back, never creates coupons. Recommended Actions is framed as guidance to read.
- ✅ **Accuracy boundary.** "Flags risk," "earlier warning signals," "won't hand you a date." No fixed window, no guarantee.
- ✅ **Competitive claims.** No "only" or "first" anywhere. Self-hosting is stated as a fact ("on your own server"), never as a uniqueness claim.
- ✅ **Positioning shape.** The required "WooCommerce shows you ___ / PureDevs adds ___ / so you can decide ___" sentence is spoken verbatim at the top of the demo. "WooCommerce has no analytics" appears nowhere.
- ✅ **Stage discipline.** Only 3.0.0 features appear. No cohorts, no timeline, no merge, no search-gap, no profitability.
- ✅ **Free/Pro.** Health Score and Churn Prediction stated as Pro. No prices. No filter counts. RFM segments are not referenced (this video doesn't demo them).

**Open items before recording:**

1. Confirm the exact wp-admin menu labels for both paths.
2. Confirm the demo store still shows the Everett Cole declining-health alert with a drop of roughly half — if the demo data has been regenerated, the script's "roughly half" line must be re-checked against what's on screen.
3. Confirm the Recommended Actions panel renders on the at-risk profile in 3.0.0.
