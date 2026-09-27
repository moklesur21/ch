# PureDevs — Section D Guide: Landing Pages and Site

**Version:** v1.4 · **Date:** 24 Sep 2026 · **Owner:** Yeasir
**Supersedes:** v1.1 and v1.0
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.1.md` (items D1–D5)
**Reconciled with:** `PureDevs_Video_Topic_Filter_v4.1.md` (20 Sep), the Proof-Led strategy, and the 3.1.2 Feature Reference (§D do-not-claim).
**Your total time in D:** about 2 hours approving copy. Recording time is counted in section E.

**What changed in v1.2, in three lines**
1. **Nothing is recorded.** Every fallback that pointed to a recorded video is gone. The fallback is now a static image of the answer frame.
2. v4.1 restores the strategy's video IDs and puts **V1 back as the AG1 hero**. V8 fronts the Alternatives page. V13 fills the WordPress.org readme slot in week 2.
3. Names still double as `video_id` in tracking. The table below maps each name to its v4.1 ID and recording week.

---

## Video names used everywhere (pages, tracking, hand-offs)

| Name = `video_id` | ID | The question | Tier | Records |
|---|---|---|---|---|
| `returning_revenue` | V1 | How much of my WooCommerce revenue comes from returning customers? (covers guests too; V2 is merged in) | Pro | **Week 1 · final Fri 25 Sep** |
| `no_saas` | V8 | Where does my store data go when I install this? (V10 and V11 folded in) | Free | **Week 1 · by Mon 28 Sep** |
| `zero_result_searches` | V13 | What are shoppers searching for in my store and not finding? | Free | Week 2 |
| `drop_off_funnel` | V4 | Where do shoppers drop off before they pay? | Pro | Week 3 |
| `free_to_pro` | V16 | What changes when I switch from Free to Pro? | Free → Pro | Week 4 |
| `viewed_not_bought` | V14 | Which products get viewed but not bought? | Free → Pro | Week 5 |
| `buying_times` | V7 | When do my customers actually buy? | Pro | Week 6 |
| `bought_together` | V3 | Which products do my customers actually buy together? | Pro | Week 7 |

Next window: `customers_by_product` (V15) · `order_screen_customer` (V17) · `refund_rate` (V18) · `cart_value` (V5). The name `guest_share` is retired.

**Slot rule:** every video slot below names its target video. Until that video is published, the slot shows the **answer frame as a still image** (same file as the poster, captioned "Demo store, sample data"). Swapping in the video is a two-minute edit.

---

## D0. Rules for every page in this section

1. **One action.** The only button is `pd-trial`: **Start free 30-day trial**. It opens the checkout on the page itself (section C). A no-card trial then drops to the free version, so it is the better path for the visitor and the only one ads can measure.
2. **Hero, H2s and meta never lead with** RFM, churn scores, health scores, behavioural tags or the ready-made segments (decision A2). They may appear inside a feature list.
3. **Approved wording only:**
   - Data: "Your customer and order data stays in your own WordPress database." Avoid the absolute "nothing leaves your site": licensing and updates run through Freemius, with opt-in.
   - Never: "GDPR-compliant", "cookie-less", "real-time scoring", "recover carts", automation, coupons, loyalty.
   - Never say "WooCommerce has no analytics". Shape: "WooCommerce shows you ___. Customer History adds ___, so you can decide ___."
   - The free limit is a read limit: "Free shows the most recent 90 days." Never "Free only tracks 90 days." There is **no 100-customer cap** (decision A7), so no page mentions one.
   - Search counts run "from the day you install 3.1.2", never retroactively. Abandoned-cart tracking starts when Pro is switched on.
   - Safe: "HPOS-compatible" · WordPress 6.0+ · WooCommerce 7.0+ · PHP 7.4+.
4. **No numbers from the demo store as results.** Caption every screenshot and video "Demo store, sample data". No unverified statistics.
5. **No review stars, no `aggregateRating`** until real reviews exist. The reviews block stays hidden until 3 are published.
6. **Speed:** the poster is the largest element. WebP, under 80 KB, preloaded. No sliders. Check mobile first.

---

## Build kit — DEV · 1.5 days · due Fri 25 Sep · build this before any page

| Piece | What it is |
|---|---|
| Template **Landing** | Header = logo + the trial button only. Footer = Privacy · Terms · Contact. No main menu. |
| Pattern `pd/video-poster` | Click-to-load YouTube (code below). Pushes `video_play`. |
| Pattern `pd/cta-trial` | Button with class `pd-trial` + the one-line microcopy. |
| Pattern `pd/proof-strip` | Four short facts in a row. |
| Pattern `pd/question-tiles` | Three tiles: question, screenshot, two lines, tier label, link. |
| Pattern `pd/faq` | Accordion, plain HTML `<details>`. |
| Pattern `pd/reviews` | Hidden by default. |

```html
<div class="pd-video" data-youtube="YOUTUBE_ID" data-video="returning_revenue" data-page="lp_category">
  <button type="button" aria-label="Play the demo">
    <img src="/wp-content/uploads/returning-revenue-poster.webp" width="1280" height="720"
         alt="Revenue by customer type on a demo store" fetchpriority="high">
  </button>
</div>
```
```js
document.addEventListener('click', function (e) {
  var box = e.target.closest('.pd-video');
  if (!box || box.dataset.loaded) return;
  box.dataset.loaded = '1';
  (window.dataLayer = window.dataLayer || []).push({
    event: 'video_play', video_id: box.dataset.video, page_type: box.dataset.page
  });
  box.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + box.dataset.youtube +
    '?autoplay=1&rel=0" title="Demo video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
});
```

---

## D1. LP-Category (for ad group AG1) — VA assembles, Y approves · due Mon 28 Sep

**URL:** `/lp/customer-analytics/` · template Landing · **noindex** (retire once D5 rewrites the homepage)
**Title:** WooCommerce Customer Analytics | PureDevs Customer History
**Hero video slot:** `returning_revenue` (V1) · fallback: the answer frame as a still image

| Block | Copy |
|---|---|
| Eyebrow | PureDevs Customer History · WooCommerce plugin |
| H1 | WooCommerce customer analytics that runs inside WordPress |
| Sub | See how much of your revenue comes from new, returning and guest customers, what shoppers search for and can't find, and where they drop off before paying. Your data stays in your own WordPress database. |
| Video | Hero slot · caption: "Demo store, sample data" |
| CTA | **Start free 30-day trial** · under it: "No card needed. When the trial ends you keep the free version and all your data." |
| Proof strip | Self-hosted, no SaaS account · HPOS-compatible · WordPress 6.0+ / WooCommerce 7.0+ / PHP 7.4+ · 30-day money-back guarantee |
| H2 | Three questions it answers on your own store |
| Tile 1 · Pro | **How much of my revenue comes from returning customers?** New, returning and guest revenue side by side, each with its order count. |
| Tile 2 · Free | **What are shoppers searching for in my store and not finding?** The exact words that returned nothing, how often, and when. |
| Tile 3 · Pro | **Where do shoppers drop off before they pay?** Visitors, add to cart, checkout started, purchased: four numbers and the step that leaks. |
| Tile links | Each tile links to its question page once that page is live. Until then, no link. |
| H2 | And the customers behind the orders |
| Body | A customer list you can filter and save as segments, and a profile for every customer with order history and notes. |
| H2 | Your data stays where your store is |
| Body | Everything is stored in your own WordPress database. There is no external service, no account and no API key. It works with WordPress's personal-data export and erase tools. |
| H2 | What it does not do |
| Body | It observes and reports. It does not send marketing emails, create coupons or run automations, and it never changes your orders or products. You decide what to do with what you see. |
| H2 | Free and Pro |
| Table | Revenue: 5 KPI cards and a trend → adds the new / returning / guest split · Searches: top and zero-result searches, most recent 90 days → full history, sortable and searchable · Product views: most viewed products → views vs purchases for every product · Funnel and abandoned carts: not included → included (observation only) · History you can read: most recent 90 days → full history · Saved segments: 1 → unlimited |
| Line under table | The free version records searches, views, add-to-carts and orders from day one. Pro unlocks the full view in place. Nothing is migrated. |
| FAQ | The seven answers below |
| Final CTA | Same button and microcopy |

**FAQ (reused on D2 and D3)**
1. **How does the trial work?** Click Start free trial and enter your email. Freemius emails you the Pro download and a licence key. Upload it under Plugins → Add New, activate the key, and Pro runs on your own store for 30 days. If you already use the free version, the data it collected is still there.
2. **Do I need a card?** No.
3. **What happens when the trial ends?** Pro features switch off and the plugin returns to the free view. Everything it collected stays in your database.
4. **Will it slow my store?** Heavy reports are cached, and scores are calculated overnight, not while shoppers browse.
5. **Does any data leave my site?** Your customer and order data does not. Licensing and updates run through Freemius, which asks for your permission first.
6. **Which searches does it record?** Searches made through WordPress and WooCommerce's own search, from the day you install. *(Hold this answer until the DEV has tested FiboSearch, Advanced Woo Search and the Woo Product Search block. Then state the result plainly.)*
7. **Is there a free version?** Yes, on WordPress.org. *(plain text link, existing UTM pattern with `utm_campaign=lp_category`)*

GA4 already logs outbound clicks. Watch wordpress.org clicks from this page as a second signal, because those installs never show up as trials.

---

## D2. LP-Alternatives (for ad group AG3) — VA assembles, Y approves · due Mon 28 Sep

**URL:** `/compare/self-hosted-vs-saas/` · template Landing · **indexable**
**Title:** Self-Hosted WooCommerce Analytics vs SaaS | PureDevs
**Meta:** Two ways to understand your WooCommerce customers: a hosted dashboard that holds your data, or a plugin that keeps it in your own WordPress database.
**Hero video slot:** `no_saas` (V8) · fallback: **no video**, the comparison table moves up under the CTA

**Calls that keep this task small**
- It compares **models, not vendors**, and carries **no competitor prices**, so nothing goes stale. v4.1 agrees: the model comparison lives in this page's text, not in the video's narration.
- "Self-hosted" separates you from hosted tools only. The $79 Marketplace extension is self-hosted too, so this page never claims self-hosting is unique among plugins.

| Block | Copy |
|---|---|
| Eyebrow | PureDevs Customer History · WooCommerce plugin |
| H1 | A self-hosted alternative to SaaS WooCommerce analytics |
| Sub | Tools like Metorik and Metrilo are hosted services: your store connects to them and your customer data lives on their servers. PureDevs Customer History is a plugin. The reports open inside wp-admin and your customer data stays in your own database. |
| Video + CTA | Hero slot (`data-page="lp_alternatives"`) + the D1 CTA. Because this video ends on "install free", use this microcopy here: "The trial gives you the full plugin for 30 days. After that it drops to the free version and you keep your data." |
| H2 | The two models, side by side *(the comparison table below goes here)* |
| H2 | Which one fits your store |
| Body A | **Choose a hosted dashboard if** you want built-in email automation, one login across many stores, or a team that works outside WordPress. |
| Body B | **Choose a plugin inside WordPress if** you want customer data to stay on your own site, one yearly licence instead of a monthly bill, and no second dashboard to check. |
| Then | Question tiles · "What it does not do" · FAQ · final CTA (all from D1) |

| | Hosted (SaaS) dashboard | PureDevs Customer History |
|---|---|---|
| Where customer data is stored | On the vendor's servers | In your own WordPress database |
| Where you open reports | A separate web app | Inside wp-admin |
| Account or API key | Required | None |
| Setup | Connect the store to the service | Activate the plugin |
| Billing | Subscription, usually monthly | One yearly licence per site |
| If you stop paying | Access to the dashboard ends | Returns to the free version; collected data stays with you |
| Sends emails or runs automations | Some do | No. It observes and reports |

**VA, on build day:** open each named vendor's own site and confirm the left column is still true for it. Record the date in the page's revision note. No statement about a competitor's quality, ever.

---

## D3. Pricing page edits — VA edits, DEV wires buttons, Y approves · due Mon 28 Sep

**Title:** Pricing | PureDevs Customer History for WooCommerce
**Meta:** Free on WordPress.org. Pro adds full history, the conversion funnel and the full revenue and product reports. Try Pro free for 30 days, no card needed.
**Video slot above the plans:** `returning_revenue` (V1) · fallback: the answer frame as a still image · `free_to_pro` (V16) goes **below** the plans when it ships in week 4

| # | Change |
|---|---|
| 1 | Replace title and meta with the two lines above (today's meta leads with churn and RFM). |
| 2 | H1: **Try Pro free for 30 days. No card needed.** Sub: "Pro runs on your own orders. If you do nothing, you drop back to the free version and keep all your data." |
| 3 | Add the video slot **above** the plan cards (`data-page="pricing"`). |
| 4 | Pro single-site card: primary button `pd-trial` **Start free 30-day trial** · second button `pd-buy-1` **Buy Pro** · below them the Founding 50 line with `pd-founding` and the live counter. |
| 5 | Multi-site card: two buttons, `pd-buy-5` and `pd-buy-unlimited`. Prices stay as they are (decision A4). |
| 6 | Reorder Pro bullets so the first five are things the demos show: revenue by new / returning / guest · full-history searches and zero-result searches · views vs purchases for every product · conversion funnel · full history beyond 90 days. Then abandoned carts (observed, never "recovered"), Frequently Bought Together and the rest. RFM, health and churn stay lowest. |
| 7 | Three-tile row, "See it answer your question", filled in ship order: `drop_off_funnel` (week 3), `viewed_not_bought` (week 5), `buying_times` (week 6); `bought_together` joins in week 7. **Hidden until the first tile video is live.** |
| 8 | FAQ: move the trial question to the top and add "What happens when the trial ends?" (answers from D1). |
| 9 | Money-back line next to the buttons. Reviews block hidden. |

---

## D4. Question pages — DEV template, VA fills · does not block launch

**Template (site header stays, indexable):** H1 = the question → answer in two lines → video → "How to check this on your own store" (5–8 lines from the transcript) → 3 captioned screenshots → "What this lets you decide" → tier note → `pd-trial` CTA → links to two other questions.

**Rule:** a question page goes live the day its video publishes, never before. Order follows the recording weeks.

**URL pattern (approved 21 Sep):** `puredevs.com/questions/<slug>/`. V1's slug is fixed as `woocommerce-new-vs-returning-customers`. The other slugs below are proposals; the video chat sets the final slug when it publishes each video, and **a slug is never changed after publishing.**

### First three · due Sun 4 Oct (strategy gate G1)

| | `returning_revenue` (V1) | `no_saas` (V8) | `zero_result_searches` (V13) |
|---|---|---|---|
| URL | `/questions/woocommerce-new-vs-returning-customers/` **(approved slug)** | `/questions/where-your-woocommerce-customer-data-is-stored/` | `/questions/woocommerce-zero-result-searches/` |
| H1 | How much of my WooCommerce revenue comes from returning customers? | Where does my store data go when I install PureDevs Customer History? | What are shoppers searching for in my WooCommerce store and not finding? |
| Title | Returning Customer Revenue in WooCommerce \| PureDevs | Where Your Store Data Goes \| PureDevs Customer History | Zero-Result Searches in WooCommerce \| PureDevs |
| Answer in two lines | Open Customer History → Dashboard → Revenue Analytics. "Revenue by Customer Type" shows New Customers, Returning Customers and Guest Orders, each with revenue and order count for the dates you pick. | It stays in your own WordPress database. The plugin records searches, views, add-to-carts and orders on your server and makes no outbound requests of its own. There is no account and no API key. | On the free version: Customer History → Search Analytics → Zero-Result Searches. On Pro: Statistics → Zero-Result Searches. The table lists each term that returned no products, how many times it was searched, and when. |
| What this lets you decide | Whether your next hour goes to existing buyers or first-time buyers, and whether to encourage accounts at checkout. | Whether customer analytics has to mean handing customer data to a third party. | What to stock, rename or add a synonym for. |
| Must state | WooCommerce lets you filter the orders report by new or returning customers; this puts new, returning and guest side by side. Completed and processing orders only. Figures can lag up to 30 minutes. | Licensing and updates run through Freemius, with an opt-in prompt you can skip. IP addresses and emails are stored, so never "anonymised". Works with WordPress's personal-data export and erase tools. | Counts start the day you install 3.1.2. Your own admin searches are not recorded. Report only: it does not redirect or suggest. Search-plugin coverage per FAQ 6. |
| Tier note | Pro screen. The free Revenue page shows Repeat Purchase Rate, for registered customers only. | Free. | **Free:** up to 20 terms, most recent 90 days. Pro: full history, sortable, searchable. |

Meta descriptions:
- `returning_revenue`: See how much WooCommerce revenue comes from new, returning and guest customers. A short demo on a working store, plus the steps to check your own.
- `no_saas`: See where PureDevs Customer History keeps your WooCommerce customer data: in your own WordPress database, with no account and no API key. A one-take demo.
- `zero_result_searches`: See the exact words shoppers typed into your WooCommerce search that returned nothing, how often, and when. A short demo, and it is free to check your own.

### Then, one per week as each video ships

| | `drop_off_funnel` (V4) | `viewed_not_bought` (V14) | `buying_times` (V7) | `bought_together` (V3) |
|---|---|---|---|---|
| URL | `/questions/woocommerce-where-shoppers-drop-off/` | `/questions/woocommerce-products-viewed-not-bought/` | `/questions/woocommerce-sales-by-day-and-hour/` | `/questions/woocommerce-products-bought-together/` |
| H1 | Where do shoppers drop off before they pay in my WooCommerce store? | Which WooCommerce products get viewed but not bought? | When do my WooCommerce customers actually buy? | Which products do my customers actually buy together? |
| Title | Where Shoppers Drop Off in WooCommerce \| PureDevs | Products Viewed but Not Bought in WooCommerce \| PureDevs | WooCommerce Sales by Day and Hour \| PureDevs | Products Bought Together in WooCommerce \| PureDevs |
| Answer in two lines | Open Customer History → Statistics → Conversion Funnel. Four bars: Visitors, Add to Cart, Checkout Started, Purchased, with the rate between each step. | The free Dashboard shows Most viewed products. On Pro, Statistics → Viewed vs Purchased Ratio puts View Count and Purchase Count in one table for every product. | Open Customer History → Statistics → Revenue Patterns. Best and Worst cards, then revenue by day of week, by hour of day, and a day-by-hour heat-map. | Open Customer History → Statistics → Frequently Bought Together. Pick a product and the table lists what is bought in the same order, with co-occurrences and confidence. |
| What this lets you decide | Which step to fix first. | Which page or price to fix, and which product deserves more traffic. | When to schedule emails, posts and ad budget. | Which two products to bundle or place side by side. |
| Must state | Counts sessions, not people. Store-wide, never one visitor's path. | Views are page loads, repeats included, not unique visitors. Never call the gap "lost revenue". | Your site's timezone, not the shopper's. Completed and processing orders. | Confidence = the share of the chosen product's orders that also contained the other. Windows: 30, 60, 90 or 180 days. **A report in wp-admin, not a storefront widget.** |
| Tier note | Pro screen. | Free + Pro, as above. | Pro screen. | Pro screen. |

Meta descriptions:
- `drop_off_funnel`: See how many store visits add to cart, start checkout and buy, and which step leaks. A short demo of the conversion funnel on a working WooCommerce store.
- `viewed_not_bought`: See product views next to purchases in WooCommerce and find the products people look at but do not buy. A short demo on a working store.
- `buying_times`: See which days and hours your WooCommerce store earns the most, as charts and a day-by-hour heat-map. A short demo on a working store.
- `bought_together`: Pick a product and see what customers buy in the same order, with a confidence figure. A short demo of the report on a working WooCommerce store.

`free_to_pro` (V16) gets no question page. It lives below the plans on /pricing, in trial emails and in the plugin's upgrade screen.

**Schema — one per question page (fill the four placeholders)**
```json
{ "@context": "https://schema.org", "@type": "VideoObject",
  "name": "THE QUESTION (same as H1)",
  "description": "A short screen recording on a demo store showing ... in PureDevs Customer History.",
  "thumbnailUrl": "https://puredevs.com/wp-content/uploads/VIDEO-NAME-poster.webp",
  "uploadDate": "2026-09-26T08:00:00+00:00",
  "duration": "PT1M25S",
  "embedUrl": "https://www.youtube-nocookie.com/embed/YOUTUBE_ID" }
```
**Schema — once, on home, pricing, D1, D2 and the question pages**
```json
{ "@context": "https://schema.org", "@type": "SoftwareApplication",
  "name": "PureDevs Customer History for WooCommerce",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "WordPress 6.0+, WooCommerce 7.0+",
  "softwareVersion": "3.1.2",
  "offers": [ { "@type": "Offer", "name": "Free", "price": "0", "priceCurrency": "USD" },
              { "@type": "Offer", "name": "Pro, single site, yearly", "price": "79", "priceCurrency": "USD" } ],
  "publisher": { "@type": "Organization", "name": "PureDevs", "url": "https://puredevs.com" } }
```
Keep the price in the schema equal to the price on the page. Update `softwareVersion` with each release.

---

## D5. After launch (not in the two-week window)

- Homepage: hero and meta rewritten around `returning_revenue` or `no_saas`. Then retire `/lp/customer-analytics/` with a 301 to the homepage.
- Three existing compare pages: heroes and tables rebuilt on the D2 model; competitor prices re-verified or removed.
- Pricing bullets and any remaining churn-led copy.
- Demo store `ch.puredevs.com` updated to 3.1.2, then linked from the FAQ ("Can I see it without installing?").

---

## Who does what, and the week

The DEV is the bottleneck, so the **VA assembles the pages** and the DEV only builds the kit. With nothing recorded, **V1 is the one item to protect this week.**

| Day | DEV | VA | Y |
|---|---|---|---|
| Mon 21 | B2 GTM + GA4 · B3 consent start | **Demo store on 3.1.2, seeded for V1:** orders from new, returning and guest buyers on most of the last 30 days · G4 · J1 | B1 · C3 · script V1 (read amounts and order counts, not percentages) |
| Tue 22 | C1 + C2 overlay checkout | H1 Capterra · D2 vendor fact check | **Record V1** |
| Wed 23 | C1 + C2 finish · B4 events · G1 domain auth | Edit V1: captions, product name in first frame and end card | Review the edit · G2 |
| Thu 24 – Fri 25 | **D build kit** · B3 finish | Draft D1, D2, D3 in Gutenberg · V1 poster + 3 screenshots | **V1 final (Fri)** · approve D copy |
| Sat 26 – Mon 28 | C4 webhook · search-plugin test, 1 h (FiboSearch, Advanced Woo Search, Woo Product Search block) | Publish V1 (Sat) · swap in its YouTube ID · D1–D3 ready | **Record V8** on the free WordPress.org build, one take |
| Tue 29 | QA (J2) | Publish V8 · first question pages | QA sign-off |
| Week 2 | — | Make 8+ zero-result searches **logged out**, uneven counts, one matching a stocked product under another name | Record V13 |

**If V1 slips past Mon 28 Sep:** launch anyway with the answer frame as a still image on D1 and /pricing, and swap the video in the day it publishes. Do not move the launch for it.

**If behind, cut in this order:** D4 → V8 before launch (D2 then runs table-first) → FAQ item 7.

## D sign-off (feeds QA item J2)

- [ ] D1 and pricing show the V1 poster (or its still image) and one `pd-trial` button above the fold on a phone; D2 shows the button and either the V8 video or the table
- [ ] Poster click plays the video and pushes `video_play` with the right `video_id` name and `page_type`
- [ ] Trial button opens the overlay on the landing page itself
- [ ] No hero, H2 or meta on these pages mentions RFM, churn, health scores, behavioural tags or ready-made segments
- [ ] Every screenshot and video is captioned as demo data
- [ ] D1 is noindex; D2 and question pages are indexable and in the sitemap
- [ ] Privacy and Terms links present in the Landing footer
- [ ] Mobile speed check passed on D1 and D2

## Hand-offs to other sections

- **F2 (ads):** the strategy's RSAs stand. "New vs Returning Revenue" now matches the D1 hero. Two optional headlines from v4.1, `See What Shoppers Search For` and `Searches That Found Nothing`, go in only once the V13 question page is live. Final URLs: AG1 → D1 · AG3 → D2 · AG4 → /pricing.
- **E:** v4.1 governs the video list and format (five beats, one 20–30 s cut). For every published video hand back: YouTube ID, poster frame, 3 captioned screenshots, transcript, duration, publish date.
- **I1:** `zero_result_searches` (V13) is the WordPress.org readme video, week 2. It must be recorded on the free build, because that tab is hidden when Pro is active.
- **G3:** demo links enter trial and re-engagement emails from week 3; `free_to_pro` serves the trial-conversion emails from week 4.
- **H3:** listing media = the V13 master plus screenshots.
- **A7 settled:** no 100-customer cap. It is removed from D0, the D1 table, the pricing meta and the pricing bullets. The 3.1.2 Feature Reference still describes the cap and quotes its banner text, so the DEV should confirm on a free install with more than 100 customers, and the Reference needs a correction note.

## Changelog
- **v1.4 — 24 Sep 2026.** Question pages move to the approved `/questions/<slug>/` pattern; V1 uses the approved slug. No other change.
- **v1.3 — 21 Sep 2026.** Decision A7: no 100-customer cap on the free version. Removed from rule D0.3, the D1 Free/Pro table, the pricing meta description and pricing bullet 6. Nothing else changed.
- **v1.2 — 21 Sep 2026.** Reconciled with Video Topic Filter v4.1 and the fact that nothing is recorded. D1 hero and /pricing slot back to `returning_revenue` (V1); D2 hero `no_saas` (V8); fallbacks are now still images, not other videos. D1 sub and tiles blend customer revenue with search and funnel. D4 rebuilt in ship order (first three: V1, V8, V13), `guest_share` retired, pages added for V8 and V7. Week plan rebuilt around scripting, recording and editing V1 by Fri 25 Sep. F2 hand-off corrected: the strategy's RSAs stand.
- **v1.1 — 20 Sep 2026.** Reconciled with Video Topic Filter v4. Video numbers replaced by stable names that double as `video_id`. D1 hero, tiles and Free/Pro table now lead with pre-order behaviour. Hero slots: D1 `zero_result_searches`, D2 `no_saas`, pricing `free_to_pro`, each with a fallback. D4 split into recorded pages (now) and v4 pages (as videos publish). Fixed an overclaim: abandoned-cart tracking starts with Pro, so Free does not "collect everything". Added the search-plugin test, a search FAQ, and your recording days to the week plan.
- **v1.0 — 20 Sep 2026.** First version.
