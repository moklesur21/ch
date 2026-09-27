# PureDevs — Section F Guide: Google Ads Build

**Version:** v1.0 · **Date:** 21 Sep 2026 · **Owner:** Yeasir
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.1.md` (items F1–F4)
**Governed by:** strategy §6.2 (Search), §6.3 (retargeting), §10 (gates) · decisions A2, A3, A5 · the 3.1.2 do-not-claim list
**Time:** VA about 4 hours to build · Y 40 minutes to review. **Everything stays paused until launch day (J3).**
**Needs first:** B1 (account exists, VA has Standard access) · B4 (`trial_start` imported) · D1–D3 URLs live before any ad is created.
**Note:** Google renames menus often. If a label is not where this guide says, use the search box inside Google Ads.

---

## F0. Six calls made here

1. **Two campaigns, not one.** Non-brand at $9/day and Brand at $1/day. Brand trials are cheap and would flatter every number. **All strategy gates are read on the Non-brand campaign only.**
2. **AI Max off · automatically created assets off · campaign-level broad match off.** Since September 2026 Google upgrades campaigns that use the last two into AI Max, which can rewrite ad text and choose its own landing page. Your homepage still leads with churn and RFM, so that would break decision A2 and the do-not-claim list within a day. *(Source: Google Ads blog, 11 Jun 2026.)*
3. **One responsive search ad per ad group, with one headline pinned to position 1**, so the first thing the searcher reads matches what they typed.
4. **Max CPC cap: $2.50 Non-brand, $1.00 Brand.** That buys roughly 4–6 clicks a day. Review rule in the last section.
5. **Lines that must be true before they run.** The demo lines (H8, D3) go in only when the V1 video is live on the page. The trial-terms lines (H10, H12, two callouts, one sitelink description) go in only after C3 confirms 30 days, no card.
6. **Sitelinks never point to the homepage or the three old compare pages** until D5 rewrites them.

---

## F1. Account settings and the two campaigns — VA · 1.5 h · due Fri 25 Sep

### Once, at account level
1. **Recommendations → Auto-apply:** turn every item **off**. Never click "Apply" on a recommendation during this test, above all: broad match, raise budget, "upgrade to Performance Max", AI Max.
2. **Assets → ⋮ → Account-level automated assets → Advanced settings:** turn **off** dynamic sitelinks, dynamic callouts and dynamic structured snippets. (Otherwise Google links to pages of its choosing.)
3. **Goals → Conversions:** confirm `trial_start` = Primary and `purchase` = Secondary (from the Section B guide).
4. **Tools → Bulk actions → Rules** (safety nets for a 2 h/day owner), all on the Non-brand campaign, data range "All time", checked daily:
   - Send email when Cost > $150 · Send email when Cost > $300
   - **Pause campaign when Cost > $445**

### Campaign settings
Create with: **+ New campaign → Create a campaign without a goal's guidance → Search.** If the wizard insists on an ad, skip it or leave a draft; ads are made in F2.

| Setting | `proof2026_search_nonbrand` | `proof2026_search_brand` |
|---|---|---|
| Daily budget | **$9** | **$1** |
| Bidding | Maximize clicks · max CPC bid limit **$2.50** | Maximize clicks · max CPC bid limit **$1.00** |
| Networks | Search partners **off** · Display Network **off** | Same |
| Locations | United States, United Kingdom, Canada, Australia, New Zealand, Ireland | Same |
| Location options | **Presence:** people in or regularly in these locations | Same |
| Language | English | English |
| AI Max · Broad match keywords · Automatically created assets | **All off** | All off |
| Start / end date | 1 Oct 2026 / **15 Nov 2026** | Same |
| Ad schedule · rotation | All day · Optimize | Same |
| Status | **Paused** | **Paused** |

### Ad groups and keywords
Type the brackets and quotes exactly. After saving, check the Match type column reads Exact or Phrase, **never Broad**. Rows marked + are close additions to the strategy's list, because several seed terms will show "Low search volume" and not serve.

| Campaign → ad group | Keywords | Final URL |
|---|---|---|
| Non-brand → `category` (AG1) | `[woocommerce customer analytics]` · `[woocommerce customer insights plugin]` · `[woocommerce customer reports]` · `[woocommerce customer history]` · `"woocommerce customer history plugin"` · + `[woocommerce customer analytics plugin]` · + `[woocommerce customer insights]` · + `[woocommerce customer report plugin]` | `/lp/customer-analytics/` |
| Non-brand → `alternatives` (AG3) | `[metorik alternative]` · `[metrilo alternative]` · `"self hosted woocommerce analytics"` · `"woocommerce analytics without saas"` | `/compare/self-hosted-vs-saas/` |
| Brand → `brand` (AG4) | `[puredevs]` · `[puredevs customer history]` · + `[pure devs]` | `/pricing/` |

### Negative keywords
**Shared list "PD global negatives"**, applied to both campaigns. Negatives do not match plurals, so both forms are listed.

> free · nulled · crack · gpl · tutorial · tutorials · course · courses · job · jobs · salary · developer · hook · hooks · snippet · snippets · code · github · php · sql · shortcode · template · shopify · magento · bigcommerce · wix · squarespace · prestashop · opencart · etsy · amazon · login · "google analytics" · ga4 · crm · "abandoned cart" · recover · recovery · "frequently bought together" · upsell · "order tracking" · "track order" · "tracking number" · "my account" · "what is" · pdf · hire · freelancer · agency

- The middle of that list enforces the strategy's held-back terms (CRM, abandoned cart, bought-together widget).
- **Non-brand campaign only:** `"puredevs"` · `"pure devs"`, so brand searches go to the Brand campaign.
- **Ad group `category` only:** `metorik` · `metrilo` · `alternative` · `alternatives`, so comparison searches go to `alternatives`.
- Watch `[woocommerce customer history]` closely. Some people typing it want to show a shopper's order history on the storefront, which is a developer question.

---

## F2. Ads and assets — VA · 2 h, Y reviews · due Mon 28 Sep

Create ads only after the three final URLs load, or they are disapproved for "destination not working".

### Line bank
| # | Headline (max 30) | # | Headline |
|---|---|---|---|
| H1 | WooCommerce Customer Analytics | H8 | Watch The 2-Minute Demo **★ video live** |
| H2 | See What Sells Together | H9 | PureDevs Customer History |
| H3 | New vs Returning Revenue | H10 | Start Your Free Trial **★ C3** |
| H4 | Your Data Stays On Your Site | H11 | See Where Shoppers Drop Off *(new)* |
| H5 | Self-Hosted. No SaaS Account. | H12 | 30-Day Free Trial, No Card **★ C3** *(new)* |
| H6 | Runs Inside WordPress | H13 | One Yearly Licence Per Site *(new)* |
| H7 | A Self-Hosted Alternative | H14 | Keep Customer Data On-Site *(new)* |

| # | Description (max 90) |
|---|---|
| D1 | See new vs returning revenue, what sells together and where shoppers drop off. |
| D2 | Customer analytics that runs inside WordPress. Your store data never leaves your site. |
| D3 | Watch a 2-minute demo on a working WooCommerce store, then try it on your own orders. **★ video live** |
| D4 | You decide what to do. The plugin shows you where the money comes from. |
| D5 | No account and no API key. Reports open inside wp-admin. One yearly licence per site. *(new)* |

H1–H10 and D1–D4 are the strategy's, unchanged. Every line was checked against the character limits.
**Later, only when the V13 question page is live:** `See What Shoppers Search For` · `Searches That Found Nothing`.

### One ad per ad group
| Ad group | Pinned to position 1 | Other headlines (unpinned) | Descriptions | Display path |
|---|---|---|---|---|
| `category` | H1 | H3 · H11 · H2 · H4 · H5 · H6 · H9 · H13 · ★H8 · ★H10 · ★H12 | D1 · D2 · D4 · ★D3 | `woocommerce` / `analytics` |
| `alternatives` | H7 | H5 · H4 · H14 · H6 · H1 · H13 · H9 · ★H8 · ★H10 · ★H12 | D5 · D2 · D4 · D1 | `self-hosted` / `analytics` |
| `brand` | H9 | H1 · H4 · H6 · H3 · H13 · ★H8 · ★H10 · ★H12 | D2 · D1 · D4 · ★D3 | `pricing` |

- **No competitor name in any headline, description or path.** They exist only as keywords.
- No price anywhere in ad text.
- "Ad strength" will read Average because of the pin. Leave it. Control matters more than the meter here.
- **If H1 comes back "Approved (limited)" for a trademark in ad text:** replace it with `Customer Analytics Plugin` and re-pin.

### Assets
**Sitelinks — add at ad-group level, at least two per ad group.**

| Sitelink (max 25) | Description lines (max 35 each) | URL | Use in |
|---|---|---|---|
| Pricing & Free Trial | Try Pro free for 30 days. / No card needed. **★ C3** (until then: "See plans and what Pro adds.") | `/pricing/` | `category`, `alternatives` |
| Self-Hosted vs SaaS | Where your customer data lives. / The two models, side by side. | `/compare/self-hosted-vs-saas/` | `category`, `brand` |
| What It Shows | Revenue, searches and drop-off. / All inside WordPress. | `/lp/customer-analytics/` | `alternatives`, `brand` |
| New vs Returning Revenue | See the split on a demo store. / New, returning and guest buyers. | V1 question page, **when live** | all |
| Where Your Data Goes | Stored in your own database. / No account. No API key. | V8 question page, **when live** | all |

**Callouts (account level):** Self-Hosted · No SaaS Account · HPOS-Compatible · Runs Inside wp-admin · One Yearly Licence · ★C3: 30-Day Free Trial · No Card Needed · 30-Day Money-Back
**Structured snippet (account level):** header **Types** → Revenue Reports · Search Reports · Conversion Funnel · Customer Segments · Customer Profiles
**Skip for now:** image assets, business name and logo. They need account history or completed advertiser verification.

---

## F3. Audiences, so they fill from day one — VA · 45 min · due Mon 28 Sep · does not block launch

**In GA4 → Admin → Audiences → New audience → Create a custom audience:**

| Audience name | Include | Permanently exclude | Membership |
|---|---|---|---|
| `pricing_no_trial_30d` | `page_view` where page location contains `/pricing` | event `trial_start` | 30 days |
| `lp_no_trial_30d` | `page_view` where page location contains `/lp/` **or** `/compare/self-hosted-vs-saas` | event `trial_start` | 30 days |
| `trial_no_purchase_60d` | event `trial_start` | event `purchase` | 60 days |
| `video_players_30d` | event `video_play` | — | 30 days |

1. They appear in Google Ads → Tools → Audience manager within a day or two. If one shows as ineligible, check that the GA4 ↔ Ads link has personalised advertising on (Section B guide, B4 step 7).
2. After the first video is published: Tools → Data manager → connect the **YouTube channel** (approve it in YouTube Studio) → Audience manager → + → YouTube users → "Viewed any video from a channel", 365 days.
3. Add all of them to both campaigns under Audiences → **Observation**. Observation collects data without narrowing who sees the ads.
- UK and EEA visitors join only if they accepted the banner. That is correct behaviour.
- `trial_no_purchase_60d` will include a few people who bought inside wp-admin, because the website never sees those purchases. Accept it.

---

## F4. After launch — not in the two-week window

- **AG2 Questions:** one ad group per question, because each has its own landing page. First: `returning_revenue` with `[woocommerce new vs returning customers]` · `[woocommerce returning customer report]`, final URL = the V1 question page, H3 pinned. Build it paused now if time allows; enable the day that page is live. Then the funnel and sales-by-hour groups as their pages ship.
- **Demand Gen retargeting:** only when an audience passes about 300 users. $3–5/day, optimised targeting off, frequency cap 3 per week, the 20–30 s cuts. Separate guide when that day comes.
- **Bidding:** switch Non-brand to Maximize conversions only after 15+ trial starts in 30 days.
- **Budget:** up in +$5/day steps, only after a gate is passed.

---

## Running it: the first 14 days (feeds J4)

**Daily, 15 minutes (Y):**
1. Non-brand → Search terms. Add every off-intent term to "PD global negatives". This is the single most valuable habit in the test.
2. Keywords → Status. "Low search volume" is expected. "Eligible (limited)" or "Disapproved" needs a look the same day.
3. Average CPC against the cap (rule below).
4. Do not edit the ads in the first 7 days unless one is disapproved. Do not accept recommendations.

**CPC cap rule, checked once on day 7:** if average CPC is within 10 % of the cap **and** "Search lost impression share (rank)" on `category` is above 50 %, raise the cap to $3.50. Never above $4.00. If average CPC is under $1.50, lower the cap to $2.00.

**Gates, read on the Non-brand campaign only**

| Gate | When | Pass mark | How to read it | If missed |
|---|---|---|---|---|
| G3a | $150 spent | CTR ≥ 2 % | On `category` plus the two phrase keywords in `alternatives`. Leave out the two competitor-name keywords: people typing a competitor's name click less, and that is normal. | Exact match only · rewrite H1–H3 |
| G3b | $300 spent or 100 clicks | ≥ 2 trial starts | Conversions column | Landing-page problem. Swap the first screen and video once, rerun $150 |
| G3c | $450 spent | Cost per trial ≤ about **$12** (provisional; replace with the J1 figure) | Cost ÷ trial starts | Pause Search. Keep every free placement |

Expect Search to miss G3c: the strategy's own best case is $30 per trial. The $450 buys search-term data, landing-page data and a remarketing list. Judge it as that.

---

## Y's review before launch — 40 min · Tue 29 Sep

- [ ] Two campaigns, both **Paused**, budgets $9 and $1, end date 15 Nov
- [ ] Search partners and Display Network off · location option = Presence · six countries · English
- [ ] AI Max, broad match keywords and automatically created assets all off · auto-apply recommendations all off · dynamic sitelinks, callouts and snippets off
- [ ] Every keyword shows Exact or Phrase
- [ ] Shared negative list attached to both campaigns · brand negatives on Non-brand · competitor negatives on `category`
- [ ] No competitor name and no price in any ad text
- [ ] ★ lines present only if their condition is met (video live, C3 confirmed)
- [ ] Every final URL and sitelink URL loads, and none points to the homepage or an old compare page
- [ ] `trial_start` is the only Primary conversion
- [ ] The three cost rules exist
- [ ] Audiences attached as Observation, not Targeting

## Hand-offs to other sections

- **Checklist:** F1 becomes two campaigns. Folded into checklist v1.2 after the walk-through.
- **B5:** with auto-tagging, GA4 shows the Ads campaign names above. They replace the three manual `utm_campaign` values listed for Search.
- **C3:** confirming the trial terms unlocks H10, H12, two callouts and one sitelink description.
- **D and E:** H8 and D3 wait for the V1 video on the page. Two sitelinks wait for the V1 and V8 question pages.
- **J3, launch-day order:** publish GTM → fire one test `trial_start` and see it in GA4 → enable Brand → enable Non-brand → check policy status after 1 hour and after 24 hours.

## Changelog
- **v1.0 — 21 Sep 2026.** First version. Splits Brand from Non-brand, keeps AI Max and its triggers off, pins one headline per ad group, sets CPC caps and cost rules, adds five lines to the strategy's RSA copy, and makes demo and trial-terms lines conditional on being true.
