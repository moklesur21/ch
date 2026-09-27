# PureDevs — Section I Guide: WordPress.org

**Version:** v1.1 · **Date:** 24 Sep 2026 · **Owner:** Yeasir
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.1.md` (items I1–I2)
**Governed by:** strategy §5.1 · decision A2 · the 3.1.2 do-not-claim list
**Time:** DEV 2 h · VA 3 h · Y 30 min. **Not a launch gate.** Steps that do not need the V13 video can ship this week; the video swap follows in week 2.
**Why it matters more than its priority suggests:** people who click an ad often look the plugin up on WordPress.org before paying, and this page is also your main free channel.

---

## I0. What the live listing says today (read on 21 Sep)

| Finding | Detail |
|---|---|
| Basics | Version 3.1.2 · 40+ active installs · **0 reviews** · tested up to 7.0.5 · **2 tags** of 5 |
| First words | "Customer History 3.0.0 is a complete revamp". Stale, and it spends the most-read line on a version number |
| **The 100-customer cap is stated four times** | "Free version scope" section · the FAQ "What does the free version include?" · screenshot 2's caption ("the free-tier scope banner") · the Pro bullet "No limits — unlimited customers". The 3.0.0 changelog says the same |
| Pro section order | Opens with RFM Segments, Health Score, Churn, Behavioral Tags: the exact list A2 says never to lead with |
| Absolutes | "nothing sent to any third-party service or API" · "No data leaves your server" · "Will this plugin slow down my store? No." · "GDPR" |
| External service | The Freemius SDK is bundled, and the readme has no section disclosing it |
| Video | The full walkthrough. The strategy wants a short demo that a **free** feature answers |
| Screenshots | 12. Numbers 1–5 are recent and captioned. **6–12 have no captions and date from older versions** |
| Good, keep | The short, honest tone · the live demo · "what it does not do" · the zero-result FAQ |

---

## Decisions

| # | Question | Decision |
|---|---|---|
| a | The 100-customer cap | **Closed: no cap** (Yeasir, 21 and 24 Sep). Edit 11 applies without a check |
| b | Add a descriptor to the listing title? | **Yes:** `PureDevs Customer History for WooCommerce – Customer Analytics`. The title is the strongest signal in the plugin directory's search, and nobody finds a 40-install plugin by brand name. Change the readme title now; the PHP header follows in the next release. The Capterra and G2 name stays "PureDevs Customer History" |
| c | Where do review requests go? | **Free users → WordPress.org. Pro users → Capterra.** One person, one request, one platform. Until the Capterra listing is live, every request goes to WordPress.org. This refines the order in the Section H guide |

---

## I1. Readme edits — DEV · 2 h · text below, Y approves

**Shipping without a release:** edit `readme.txt` in **both** `trunk/` and `tags/3.1.2/` (the directory reads the stable tag's copy). Screenshots, banner and icon live in `/assets` and need no version bump. Run the official readme validator before committing.

| # | Edit |
|---|---|
| 1 | **Tags (use all five):** `customer analytics, woocommerce analytics, woocommerce reports, search analytics, customer history` |
| 2 | **Tested up to:** the current WordPress release, after a quick smoke test. An old value shows visitors a warning |
| 3 | **Title:** per decision b |
| 4 | **Short description:** text A below |
| 5 | **Opening paragraph:** delete the "3.0.0 is a complete revamp" paragraph. Replace with text B |
| 6 | **Video:** keep the current walkthrough for now. The day V13 (`zero_result_searches`) is published, put its YouTube URL **on its own line near the top** and move the walkthrough link into the FAQ. Never make a video URL the last line of the FAQ section |
| 7 | **Data wording:** apply text C in the three places listed |
| 8 | **New section `== External services ==`:** text D |
| 9 | **"Will this plugin slow down my store?"** Replace "No." with text E |
| 10 | **GDPR wording:** "supports the WordPress GDPR export/erase workflow" → "works with WordPress's personal-data export and erase tools". Rename the FAQ to "What customer data is stored, and how do I export or erase it?" |
| 11 | **Cap statements:** remove the cap from "Free version scope", the free-version FAQ answer, screenshot 2's caption, and change "No limits — unlimited customers, full historical data…" to "No limits — full historical data and unlimited saved segments" |
| 12 | **Pro section:** keep every bullet's wording; regroup under the four sub-headings in text F, scoring last. Change the closing sentence to "…browse every Pro screen: revenue analytics, the conversion funnel, abandoned carts, and the rest" |
| 13 | **Pricing link:** `https://puredevs.com/pricing/?utm_source=wporg&utm_medium=referral&utm_campaign=proof2026_readme` |
| 14 | **One new FAQ**, after the DEV's search-plugin test: "Which searches does it record?" with the same answer as the landing-page FAQ |

**Text A — short description (138 characters, limit 150)**
> Customer analytics for WooCommerce, inside WordPress: customer profiles, search and zero-result search reports, product views and revenue.

**Text B — opening paragraph**
> WooCommerce tells you what your store did. Customer History shows you who your customers are and what they do before they order: what they search for, which searches find nothing, and which products they view. It observes and reports. It runs on your own server and never contacts your customers automatically.

**Text C — data wording**
- "Private by design" paragraph: *"Your store's customer and order data stays in your own WordPress database. Licensing and updates use Freemius, which asks for your permission first (see External services)."*
- FAQ "Where is my customer data stored?": *"In your own WordPress database. The plugin never sends your customers' data to any external service. The only outside connection is Freemius, for licensing and updates, and only after you opt in."*
- FAQ on stored data: replace "No data leaves your server" with *"Your customers' data stays on your server."*

**Text D — External services**
> == External services ==
> This plugin bundles the Freemius SDK, which handles licensing and updates and, only if you opt in, basic diagnostics. When you opt in on activation, or activate a Pro licence or trial, the plugin sends Freemius **[DEV: list exactly what your opt-in screen lists, for example site URL, WordPress and plugin versions, admin name and email]**. It never sends your customers' data, orders or tracked activity. You can skip the opt-in and the free plugin works the same. Freemius terms: https://freemius.com/terms/ · privacy policy: https://freemius.com/privacy/

**Text E — performance answer**
> Tracking writes are small and happen during normal page loads. Heavy reports run on demand and are cached, and Pro's scores are calculated overnight, not while shoppers browse.

**Text F — Pro sub-headings, in this order**
1. **See more of what already happened:** Full Session History · Customer Activity Timeline · Guest-to-Customer Merge · Stats pages · Aggregate Conversion Funnel · Abandoned Cart Tracking
2. **Revenue and retention:** Full Revenue Analytics · Cohort Retention · Retention Metrics & Expected CLV · LTV Distribution · Geographic Performance · Enhanced Product Reports · Refund-Rate Signal · Revenue Data Integrity
3. **Working your customer list:** Advanced customer table · Advanced order-history filters · Manual Tags · Marketing-ready CSV export presets · Segment attached to your digest · Session data tools · No limits
4. **Scoring:** RFM Segments · RFM Matrix Heat-Map · Customer Health Score · Churn Risk & Prediction · Behavioral Tags · Lifecycle Stages · Order-Screen Intelligence Panel · Ready-made segments

---

## I2. Screenshots — VA · 3 h · due Fri 25 Sep (same shoot as the Capterra and G2 set)

The video does **not** show in the "View details" window inside wp-admin, so **screenshot 1 has to carry the message on its own.** Free screens come first, because this audience installs the free version.

| # | Screen | Caption |
|---|---|---|
| 1 | Search Analytics → Zero-Result Searches | What are shoppers searching for and not finding? |
| 2 | Customer profile: orders, searches, viewed products, private notes | Who is this customer? |
| 3 | Dashboard | Who are my best customers right now? |
| 4 | Revenue → Repeat Purchase Rate | What share of my customers buy again? (registered customers) |
| 5 | Customers & Users with a Product Purchased filter, saved as a segment | Who bought this product? |
| 6 | Revenue Analytics → Revenue by Customer Type | (Pro) How much of my revenue comes from returning customers? |
| 7 | Statistics → Conversion Funnel | (Pro) Where do shoppers drop off before they pay? |
| 8 | Statistics → Viewed vs Purchased Ratio | (Pro) Which products get viewed but not bought? |

- Taken on the seeded 3.1.2 demo store · no RFM, health or churn widget in frame · every caption ends "(demo data)".
- **Delete `screenshot-9` to `screenshot-12`** from `/assets` and their lines from the readme.
- The Capterra and G2 set in the Section H guide is six of these eight.
- Banner and icon date from 2021. Read the banner's words against today's positioning. Replace it (772×250 and 1544×500) only if it carries an old tagline.

---

## After the commit — VA · 20 min

1. View the listing logged out: five tags, captions lined up with images, video renders, no validator warnings.
2. Open two translated listings (en-GB, en-CA). Some still show descriptions from years ago. If they are still stale a day later, leave it: that is a translation-cache matter and not worth your hours this month.
3. Answer any open thread in the support forum. The "issues resolved" count is public.
4. Next plugin release: outbound links inside the plugin carry `utm_source=plugin`; the PHP header takes the new title.

---

## Reviews on WordPress.org — after launch

Zero reviews means no stars in directory search, which is the biggest gap on this page.

- **Target: 3 WordPress.org reviews by 15 Nov.**
- Routing per decision c. Link: `https://wordpress.org/support/plugin/puredevs-customer-history-for-woocommerce/reviews/#new-post`
- The rules in the Section H guide apply unchanged: nothing offered, honest review asked for in those words, 1:1 from yeasir@, one nudge, the person added to *Suppress — Personal* that week. Use the same request email with the WordPress.org link.
- Next release: **one** dismissible in-plugin prompt after 14 days of use. Never a repeating notice.

---

## I sign-off

- [ ] Five tags · new title · new short description · stale opener gone
- [ ] Absolutes replaced · External services section present · performance answer replaced
- [ ] Pro section regrouped with scoring last
- [ ] Eight captioned screenshots, free screens first, nothing uncaptioned left
- [ ] Readme validator clean · listing checked logged out
- [ ] V13 embedded near the top once published

## Hand-offs to other sections

- **H3:** review routing now splits by user type (decision c).
- **E:** V13 is recorded on the free WordPress.org build, which suits this page exactly.
- **C / D:** the External services wording matches the landing-page FAQ answer on data.

## Changelog
- **v1.1 — 24 Sep 2026.** Decision a closed as no cap; the cap check is dropped and edit 11 is unconditional.
- **v1.0 — 21 Sep 2026.** First version, written from the live listing. Flags four public statements of the 100-customer cap, supplies paste-ready readme text, regroups the Pro section, adds an External services section, defines an eight-image screenshot set that also feeds Capterra and G2, and routes review requests by user type.
