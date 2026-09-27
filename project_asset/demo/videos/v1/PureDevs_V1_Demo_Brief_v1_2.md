# V1 Demo Brief — How much of my WooCommerce revenue comes from returning customers?

**Version 1.2 · 21 Sep 2026** · Demo Video Prompt Pack v3, FILE 2 · supersedes v1.1
**Sources:** Video Topic Filter v4.2 (V1 row, §2, §4, §6, §8) · Brief 3.1.2 (§1, §3, A1, DASH-10, DASH-10b, REV-01, REV-02, A18, B1, B3, B6, B7, B8, §D, §E) · Proof strategy §4.3–§4.8, §5 · Research RA, RB, RC · checks run today (§12)
**Status:** Script v1.2 matches this brief. Recording and voice-over are unblocked. Q1, Q4, Q5 and Q7 are closed. Still open: Q2 (dev ticket, not a blocker), Q3 (custom range, FAQ wording only), Q6 (landing-page fixes).

---

## 1. Header

| | |
|---|---|
| **ID** | V1 |
| **Title** | How much of my WooCommerce revenue comes from returning customers? |
| **Tier** | PRO — DASH-10 "Revenue by Customer Type". Free bridge: REV-02 "Repeat Purchase Rate" (FREE) |
| **CTA** | Start the trial → https://puredevs.com/pricing (one CTA only). Terms (30 days, no card, confirmed 21 Sep) go in changeable text, not in the video |
| **Week** | Week 1 (21–27 Sep): script, record, edit. Final by Fri 25 Sep (checklist E1) |
| **Placements** | Week 2: YouTube · LP-Category hero · /pricing above the plans · homepage hero (V1 or V8) · question page. Week 5: retargeting 20–30 s cut, if the list is ready. Week 6: AG2 question page "new vs returning". Week 7: pre-BFCM email. In-plugin: Freemius trial/pricing screen poster until V16 replaces it. **Not the readme** (Pro-answered) |
| **Alternate title** | None. Owner language supports the current title (wp.org thread asks how much revenue comes from "first time buyers" vs "repeat buyers"). Search phrasing and AG2 keywords use "new vs returning". Keep the title |

---

## 2. Screens

| # | Exact UI title | Entry | Tier | Menu path (Brief §3) | Record on | What it shows |
|---|---|---|---|---|---|---|
| S1 | "Dashboard" (transit only) | A1 | FREE page | Customer History → Dashboard (`admin.php?page=pd-woo-dashboard`) | Pro build | Free grid of six widgets, then full-width Pro widgets below |
| **S2 ★ answer frame** | "Revenue Analytics" → section "Revenue by Customer Type" | DASH-10 | PRO | Customer History → Dashboard → first full-width Pro widget, below the free grid | Pro build, demo store on 3.1.2 | Three cards: New Customers · Returning Customers · Guest Orders, each with an amount and an order count. Above them: date-range picker (Today · Last 7 Days · Last 30 Days default · This Month · Last Month), four KPI cards, "Revenue Over Time" chart, badge (S3). Completed + processing orders only; cached 30 min per range |
| S3 | Data-integrity badge ("Data Verified ✓") | DASH-10b | PRO | DASH-10 header, callout ③ | Pro build | In frame only. Never narrated |
| S4 | "Repeat Purchase Rate" card | REV-02 | FREE | Customer History → Revenue (`admin.php?page=pd-woo-revenue`) | **Free build (wp.org 3.1.2)** | Share of registered customers who ordered in the window and placed more than one order in it. Caption "Registered customers only" |

| # | Demo prerequisites | ⛔ / ⚑ notes (copied from the entry) |
|---|---|---|
| S1 | Pro active at least a day before. Freemius trial notice and any upsell banner dismissed `[DEV SETUP]` | The ⚑ widgets (DASH-14 … DASH-21) sit below Revenue Analytics: never scroll past it. Keep Retention (DASH-11, has a churn-rate card) out of frame |
| S2 | Brief: completed orders on most days of the selected range, from new, returning and guest buyers, so all three split cards are non-zero. Add: one card clearly larger, so the proportion reads at a glance | ⛔ Pending, on-hold, cancelled, refunded and failed orders are excluded from revenue. This is the Pro revenue view; there is no separate "Revenue Analytics" page. The free Revenue page is A6. Filter guardrail: do not show Cohorts in this video; cohort totals will not match this split. Crop the answer frame to the split cards (see Q2) |
| S3 | Demo store verifies clean | ⛔ It is a diagnostic on the plugin's own revenue figures. Do not describe it as an accounting audit or a reconciliation with a payment gateway |
| S4 | Brief: orders in the selected period and the period before it; a few registered customers with two or more orders in the window so the rate is not 0 %. On Free: orders inside the last 90 days | ⛔ Guests are excluded from Repeat Purchase Rate (the caption says so). Pending, on-hold, cancelled, refunded and failed orders are excluded from every card. Figures can lag by up to 30 minutes because of the cache |

**Never on screen:** Cohorts / Cohort Retention (STAT-08, A14) · any ⚑ widget · the Plugins screen · Settings · any Free customer-cap banner.

---

## 3. Five beats

| Beat | Time | Screen | Narration must establish (no figures) | Claims → ledger |
|---|---|---|---|---|
| 1. The question | 0–5 s | S2 answer frame, split cards zoomed. "PureDevs Customer History" on screen | The title question in the owner's words. No greeting | — |
| 2. Where to click | 5–20 s | S1 → S2: Customer History → Dashboard → scroll to Revenue Analytics → Revenue by Customer Type | The path. "This is a Pro report." Once: self-hosted, nothing leaves your server, it reads the store's own orders | L1–L4 |
| 3. What it shows | 20–60 s | S2: zoom on the three cards → the date picker → back to the largest card | Name the three cards. New and Returning count customers with an account; guest orders have none (L12). One date range drives all three. "Read the proportion, not the number." Which card is biggest `[Returning / New]`. Where the guest card sits `[smallest of the three / bigger than New or Returning — name the card it beats]`. The honest sentence (§5) once, while all three cards are on screen | L5–L12 |
| 4. The decision | 60–90 s | S2 held; largest card highlighted | §4 | L8–L9, L17 |
| 5. Next step | last 10 s | S4 insert, 2–3 s (Free build) → end card | §6, then the one CTA. Optional close: "The plugin doesn't decide for you. It shows you where the money comes from." | L13–L16 |

Target 90–100 s, inside the 60–120 s rule.

---

## 4. The decision (beat 4)

> So here is the decision this gives you. `[Guests are small here. If yours is big, decide whether to encourage accounts at checkout — repeat guests never show as returning. / The guest card is big here — and repeat guests never show as returning. So decide whether to encourage accounts at checkout.]` And look at the biggest card. `[Returning is the biggest — so your next hour belongs to the customers who already buy from you. That's where the biggest share of this revenue comes from. / New is the biggest — so your next hour goes to bringing those new customers back. That's the gap in this revenue.]`

The plugin never acts. The guest clause comes first so the beat ends on the money. The guest clause is V2's point; the merge is signed off (Q5, 21 Sep 2026). v1.2: the guest clause now carries its reason (repeat guests never show as returning, Q1). "First-time buyers into a second order" is replaced, because New revenue can already include a second or third order in the same period.

---

## 5. The honest sentence

> WooCommerce's own Orders report can show orders from new or returning customers, one filter at a time. Customer History adds new, returning and guest revenue side by side for the same dates, so you can decide where your next hour goes.

**Status: VERIFIED EXTERNAL**, scoped to core. Owner confirmed 21 Sep 2026. Re-checked against the full WooCommerce changelog (11.0.0 → 11.1.1): no Orders-report comparison added. v1.2 wording: the report lists orders, not customers (audit finding 1).

| Fact | Evidence | Version / date |
|---|---|---|
| Orders report has a "Customer Type" advanced filter, All / Any matching; no comparison view documented | https://woocommerce.com/document/woocommerce-analytics/orders-report/ | page last modified 2 Nov 2022 · checked 21 Sep 2026 |
| Current core is 11.1.1; security and Mini-Cart fixes only | https://developer.woocommerce.com/2026/09/18/woocommerce-11-1-1/ | released 18 Sep 2026 |
| 11.1.0 adds no customer-type comparison | https://developer.woocommerce.com/2026/09/03/wc-11-1-release-notes/ | released 1 Sep 2026 |
| Customers report includes guests and joins a guest's repeat orders by email; no customer-type split | https://woocommerce.com/document/woocommerce-analytics/customers-report/ | updated 9 Aug 2026 |
| Woo's own "WooCommerce Analytics" plugin has no new-vs-returning view; a 28 Oct 2025 review asks for one | https://wordpress.org/plugins/woocommerce-analytics/ | v0.9.16, 14 Aug 2026 |
| Both research files agree | RC §1 + verdict · RB category 1 + verdict | 21 Sep 2026 |

- **Say "WooCommerce's own Orders report", never "WooCommerce" alone.** The official Customer Analytics extension already shows new vs returning revenue on its Overview (doc updated 4 Sep 2026).
- Optional safeguard: the Orders report doc dates from 2022 and release notes do not list every UI change. A 2-minute look on a WooCommerce 11.1.1 store (Analytics → Orders → Show: Advanced filters → Customer type) settles it. Owner's call; the rule (U2.2) is already met.
- **Re-check trigger:** WooCommerce 11.2 is in development (11.2.0-dev tagged 14 Sep 2026) and changes Analytics customer-type handling on refund rows (PR #67713). If 11.2 ships before V1 goes live, read its release notes for the Orders report before publishing.
- Keep "verified against 11.1.1" out of narration; it belongs in page text only.

**Fallback, no core:** "Customer History puts new, returning and guest revenue side by side for the same dates, so you can decide where your next hour goes."

---

## 6. The Free / Pro sentence (beat 5)

> The free version shows your repeat purchase rate. This split is Pro. The link below starts the trial.

End card: "Start the trial · puredevs.com/pricing"

- If the window is mentioned: "for the last 90 days". Never a customer cap.
- Trial terms are confirmed (30 days, no card; Q4 closed). They stay out of the narration, because a published video can't be edited if Freemius settings change. They go in the YouTube description, the pinned comment, the question-page button text and ads.
- Embed V1 only where the first link under it is "Start the trial" → /pricing: the question page, /pricing and the LP-Category variant. The main homepage hero takes V8 (FREE), whose install CTA matches the homepage buttons (audit finding 5; owner delegated the choice).
- No second CTA: the §4.8 line "The free version is on WordPress.org" is cut.

---

## 7. Claims ledger

| # | Sentence the script may make | Screen | Entry | Status |
|---|---|---|---|---|
| L1 | This is a WooCommerce demo store running PureDevs Customer History | S1, first frame | §3.1 | PROVEN ON SCREEN |
| L2 | Customer History → Dashboard → Revenue Analytics → Revenue by Customer Type | S1 → S2 | §3.1, A1, DASH-10 | PROVEN ON SCREEN |
| L3 | This is a Pro report | S2 | DASH-10 | PROVEN (Brief tier) |
| L4 | Self-hosted — nothing leaves your server; it reads your store's own orders | S2, inside the store's own wp-admin | §1, B7, DASH-10 | PROVEN ON SCREEN + Brief |
| L5 | Three cards: New Customers, Returning Customers, Guest Orders | S2 | DASH-10 | PROVEN ON SCREEN |
| L6 | One date range drives all three | S2 picker | DASH-10 ② | PROVEN ON SCREEN |
| L7 | Each card shows the revenue and the orders behind it (never read aloud) | S2 | DASH-10 | PROVEN ON SCREEN |
| L8 | `[Returning Customers / New Customers]` is the biggest card | S2 | DASH-10 | PROVEN ON SCREEN — pick on recording day |
| L9 | `[The guest card is the smallest / The guest card is bigger than New]` | S2 | DASH-10 | PROVEN ON SCREEN — pick on recording day |
| L10 | Read the proportion, not the number | S2 | — | Instruction, not a claim |
| L11 | Honest sentence (§5, v1.2 wording) | S2 | — | VERIFIED EXTERNAL, 21 Sep 2026 |
| L12 | New and Returning count customers with an account; guest orders are placed without one | S2 | DASH-10; dev code read (`Revenue-Pro.php:232`, `:634`) | PROVEN (Q1) |
| L17 | Repeat guests never show as returning | S2 | Dev code read: guests are not matched by email | PROVEN (Q1) |
| L13 | The free version shows your repeat purchase rate | S4, Free build | REV-02 | PROVEN ON SCREEN |
| L14 | The new, returning and guest split is Pro | S2 | DASH-10 | PROVEN (Brief tier) |
| L15 | The plugin doesn't decide for you. It shows you where the money comes from | end card | §1 | PROVEN (observe and report) |
| L16 | The link below starts the Pro trial | end card | §E | PROVEN — no trial terms |
| C1 | "[X]% of buyers but [Y]% of revenue" · "Guests are [Z]%" | — | — | CUT (§10 #1) |
| C2 | "…because you can't recognise them next time they buy" | — | — | CUT — false (§10 #2) |
| C3 | "Most store owners guess" · "in about a minute" | — | — | CUT (§10 #3–4) |
| C4 | "Identifies repeat buyers", or any definition broader than the code (for example counting repeat guests) | — | Q1 | CUT. Only the confirmed rules (Q1) may be stated |
| C5 | The three cards add up to your total | — | — | CUT (Q2) |
| C6 | Any date range · this year | — | — | CUT (Q3) |
| C7 | The badge verifies / audits your revenue | — | DASH-10b | CUT |
| C8 | 30-day trial · no card, in narration | — | Q4 | CUT from narration only (evergreen video). Allowed in changeable text |
| C9 | "Only" / "first" / any extension or competitor comparison | — | — | CUT (§10 #12) |
| C10 | Any Free customer cap | — | — | CUT (owner rule 3) |

---

## 8. Limits a buyer will hit

These feed the question-page FAQ, together with the research's BUYER DOUBTS.

| # | Limit | Source | Doubt it answers |
|---|---|---|---|
| 1 | The split is Pro. Free shows Repeat Purchase Rate — registered customers only, the most recent 90 days | DASH-10, REV-02, FREE-A | "Can I see this free?" |
| 2 | Completed and processing orders only; pending, on-hold, cancelled, refunded and failed are excluded. Revenue is net of refunds. Days follow GMT, not the site timezone | DASH-10; Q1 | Accuracy, refunds (RC 2) |
| 2b | How it decides: per customer, for the selected dates. New = a registered customer whose first paid order falls inside the dates (every order they place in that period counts as New, even a 2nd or 3rd). Returning = a registered customer who ordered in the period and whose first paid order came before it. An earlier cancelled, failed, on-hold or fully refunded order doesn't count as a first order | Q1 (`Revenue-Pro.php:232`, `:634`) | "How is returning calculated?" (RC 6) |
| 3 | Up to 30 minutes behind (cached per date range) | DASH-10 | "Is it live?" |
| 4 | Presets: Today, Last 7 Days, Last 30 Days, This Month, Last Month. No year preset; custom range is Q3 | DASH-10 ② | Owner language asks day / week / month / year |
| 5 | Amounts and order counts, no percentages | DASH-10 | — |
| 6 | Guest Orders = every order with no account (`customer_id = 0`); revenue and order count, no customer count. Guests are not matched by email, so a repeat guest stays in Guest. A guest who registers later: old orders stay in Guest; their first logged-in order counts as New. Exception: if WooCommerce links the old guest orders to the account (WooCommerce 11.0+), they count toward that customer and later orders show as Returning | Q1 · WC 11.0 release notes | Guest vs registered (RC 1, 3; RB) |
| 7 | The Pro guest-to-customer merge links browsing sessions by stored session id; the plugin never modifies orders, so it moves no revenue between cards | B6, D.1 | Guest matching |
| 8 | Cohort Retention totals will not match this split (cohorts join guests by billing email) | D.2, STAT-08 | "Why do two reports disagree?" |
| 9 | It reads existing WooCommerce orders, so orders placed before install are included once Pro is active | DASH-10 (no 90-day limit on Pro), REV-01 note | "Only from install day?" |
| 10 | The report runs in wp-admin when the page loads and caches results for 30 minutes (B8's cron covers Pro scores, not this report). On the storefront the plugin records activity server-side, with no JavaScript tracker. HPOS-compatible | DASH-10, B1, B8 | Speed, HPOS (RC 4, 6) |
| 11 | Self-hosted, nothing leaves your server, works with WordPress's personal-data export and erase tools. Never "GDPR-compliant" | §1, B7, D.2 | Privacy (RC 5; RB) |
| 12 | The badge compares two of the plugin's own revenue calculations; it is not an accounting audit | DASH-10b | Accuracy |

---

## 9. Open questions for Yeasir

| # | Question | Blocks |
|---|---|---|
| Q1 | **Closed 21 Sep** (dev code read, `pro/Analytics/Revenue/Revenue-Pro.php:232`, `pdwchs_get_new_customer_ids()` at `:634`). Rules recorded in §8 limits 2, 2b and 6. Dev's copy rule: never claim the plugin "identifies repeat buyers" without the registered-accounts qualifier | — |
| Q2 | Dev ticket: in the Brief's DASH-10.png the split order counts (8 + 19 + 1 = 28) do not match Total Orders (26), though revenue matches to the cent. Both come from the plugin's own queries on the same statuses, so excluded statuses would move revenue too; synthetic data does not explain it. The guest card also reads "1 orders" | **Decided 21 Sep:** crop kept, so it no longer blocks recording. Still a product-accuracy fix: trial users see the full widget on their own store |
| Q3 | Dev: can the DASH-10 picker take a custom range? The Brief lists presets only | FAQ wording |
| Q4 | **Closed 21 Sep:** trial is 30 days, no card. Still dismiss trial notices before recording, for a clean screen. The plugin's own notice appears only on the Pro build without a licence; the WordPress.org build can still show Freemius's own trial offer. Dismiss either as the recording account | — |
| Q5 | **Closed 21 Sep:** V2 merges into V1; beat 4's guest line stays. Log it in the strategy's §15. The rest of Filter §2 still awaits sign-off | — |
| Q6 | V1 goes live on /pricing and the homepage in Week 2. Those pages and the wp.org listing carry claims that break owner rules and the Brief (§11). Fix before or with V1? | Placement |
| Q7 | **Closed 21 Sep:** `/questions/woocommerce-new-vs-returning-customers/` approved; slugs never change after publishing | — |

---

## 10. Sanity check (FILE 6 list, run on the row, the research and this draft)

| # | Where | Claim | Problem | Rule | Action |
|---|---|---|---|---|---|
| 1 | Strategy §4.8 | "[X]% of buyers but [Y]% of revenue… Guests are [Z]%" | The widget shows amounts and order counts, no percentages; spoken figures | U2.1, product fact | Replaced with the proportion line |
| 2 | Strategy §4.8 | Guests: "you can't recognise them next time they buy" | **False.** WooCommerce's Customers report joins a guest's repeat orders by email (doc 9 Aug 2026); WooCommerce 11.0 lets shoppers link past guest orders to an account | U2.2, U5 | Cut |
| 3 | Strategy §4.8 | "Most store owners guess" | Unprovable | U5 | Cut |
| 4 | Strategy §4.8 | "…in about a minute" | Time promise with no screen proof | U5 | Cut |
| 5 | Strategy §4.8 | "The free version is on WordPress.org, and the link below starts the Pro trial" | Two CTAs | U7 | One CTA: the trial |
| 6 | Strategy §4.8 | `[UI-PATH]` | Gap | — | Filled from Brief §3 and A1 |
| 7 | Filter V1 row | Honest sentence opens "WooCommerce lets you filter…" | True for core, but "WooCommerce" alone reads as the ecosystem, where the official Customer Analytics extension shows new vs returning revenue | U2.2, U6 | Scoped to "WooCommerce's own Orders report" |
| 8 | RC §1 | Customer Analytics "does not show revenue New vs Returning" | **False.** Its Overview has a "New versus returning revenue" section (doc 4 Sep 2026). RB was right | `[CONFLICT]` → official docs win | Resolved; no competitor in the video anyway |
| 9 | RC §1 (core) | Refund rows "historically mis-classified" customer type | Being fixed: PR #67713 resets stale returning markers on refund rows in the 11.2.0 database update | U2.2 | Not used; re-check 11.2 if ever used |
| 10 | RC §2 item 2 | Review dated "2024" | It is 28 Oct 2025 | Accuracy | Corrected; not for publication |
| 11 | RC vs RB | Metorik Helper 2.0.9 vs 2.0.10 | `[CONFLICT]` | — | Not used |
| 12 | RC §1 free-plugin list | Missed one | A free wp.org plugin (BigBad Deep eCommerce Insights) already shows a 30-day new / returning / guest order split | U4 | Confirms: no "only / first"; honest sentence stays core-only |
| 13 | RB intro, cat. 5 | "5 % retention → 25–95 % profit"; $108 vs $99 AOV | Secondary source; competitor-sourced AOV stat that does not answer the question | U5 | Not used. Page statistic: NONE |
| 14 | RA | Adobe 2012, Bain 2002, Klaviyo, Wayfair; news hooks | Old or off-topic; videos carry no statistics | U5, pack §0 | Not used |
| 15 | RB cat. 4 | GDPR-compliance framing | Banned wording | U3 | FAQ uses the safe privacy line |
| 16 | This draft | Card definitions ("returning = bought before", "guest = no account") | The Brief does not document the classification | U5 | Held behind Q1 |
| 17 | This draft | "The cards add up to your total" | Order counts do not reconcile in DASH-10.png | U5 | Cut; Q2 |
| 18 | This draft | "Any date range" / "this year" | Presets only | Product fact | Cut; Q3 |
| 19 | This draft | "The free version shows your repeat purchase rate" | True, but registered customers only | Product fact | Kept; S4 shows the caption; limit #1 |
| 20 | Brief D.2 / FREE-A / B3 | Free "100 customers" wording | Owner rule overrides the Brief | U2.3 | Not used anywhere here |
| 21 | Whole draft | D.4 embargo, ⚑ features, competitor names, price | None present | U4, U5, U7 | Clear |

**Verdict (v1.2):** PASS for scripting, recording and voice-over. Q2 stays open as a product fix.

---

## 11. Outside this brief — false or rule-breaking claims on V1's landing pages

V1 goes onto these pages in Week 2. Checked 21 Sep 2026.

| Page | Claim | Problem | Fix |
|---|---|---|---|
| wp.org listing; homepage table and FAQ | Free shows "up to 100 customers" | Owner rule 3 | Remove; "Free shows the last 90 days" |
| Homepage | Free revenue = "Snapshot (4 KPIs + trend)" | Five cards incl. Repeat Purchase Rate (REV-02) — the card V1's beat 5 names | "5 KPI cards incl. repeat purchase rate + trend" |
| Homepage | "Dashboard widgets: 7 core" | Free shows six (D.2) | "6" |
| Homepage | Churn Prediction marked "FREE & PRO"; hero leads with churn | Churn is Pro; ⚑ never lead with churn (§4.5, D.3) | Mark PRO; hero decision is yours |
| Homepage FAQ | "Email this customer" "just opens your own mail client" | Brief A4 / B7: sent from the site with `wp_mail()` | Correct it |
| Homepage compare table | Feature "No" rows for Metorik and Metrilo | Competitors: model and data location only (U4) | Cut to model and data location |
| wp.org and homepage FAQ | "Will it slow my store down? No." | Absolute and unprovable; the wp.org answer also cites bot/admin exclusion, which is Pro | State the B8 facts instead |
| wp.org, Revenue | Repeat purchase rate = "share of customers… who bought more than once" | Registered customers only (REV-02) | Add "registered" |
| wp.org, Dashboard | "top categories" and total / new / active overview cards on Free | Category widgets are Pro in 3.1.2; Free shows six widgets (D.2) | Correct it |
| wp.org, Pro list | Guest merge fires "when a guest's email later registers" | Brief B6: matched by stored session id | Dev to confirm; align |
| wp.org, Pro list | Data integrity "validates the accuracy of your revenue figures" | DASH-10b: a diagnostic on the plugin's own figures | "checks the plugin's revenue figures against a second calculation" |

---

## 12. Checks run today (21 Sep 2026)

- WooCommerce 11.1.1 — https://developer.woocommerce.com/2026/09/18/woocommerce-11-1-1/
- WooCommerce 11.1.0 — https://developer.woocommerce.com/2026/09/03/wc-11-1-release-notes/
- WooCommerce 11.0 (guest order linking) — https://developer.woocommerce.com/2026/08/04/woocommerce-11-0/
- Orders Report — https://woocommerce.com/document/woocommerce-analytics/orders-report/
- Customers Report — https://woocommerce.com/document/woocommerce-analytics/customers-report/
- WooCommerce Analytics plugin v0.9.16 — https://wordpress.org/plugins/woocommerce-analytics/
- Customer Analytics docs — https://woocommerce.com/document/customer-analytics/
- WooCommerce PR #67713 — https://github.com/woocommerce/woocommerce/pull/67713
- BigBad Deep eCommerce Insights — https://wordpress.org/plugins/bigbad-deep-ecommerce-insights/
- PureDevs Customer History v3.1.2 — https://wordpress.org/plugins/puredevs-customer-history-for-woocommerce/
- puredevs.com — https://puredevs.com/

---

## Changelog

- **v1.2 — 21 Sep 2026.** Q1 closed with dev's code read: limits 2, 2b and 6 rewritten; L12 proven; L17 added; C4 narrowed. Q4 closed (30 days, no card): terms allowed in changeable text only. Honest sentence reworded ("can show orders from"). §4 guest clause carries its reason, and the New option drops "second order". Limit 10 corrected. Homepage hero → V8; V1 only where a trial link sits under it.
- **v1.1 — 21 Sep 2026.** Owner decisions applied: Q5 closed (V2 merged, guest line kept); Q2 reframed as a dev ticket with the crop kept; Q4 notice dismissal confirmed (trial terms still open). §4 rewritten to match Script v1.1 and the guest-defining clause removed. Beat 3 guest option fixed. L1 adds "demo". §5: in-admin check made optional; WooCommerce 11.2 re-check trigger added. Q7 added (URL pattern).
- **v1.0 — 21 Sep 2026.** First Demo Brief for V1.
