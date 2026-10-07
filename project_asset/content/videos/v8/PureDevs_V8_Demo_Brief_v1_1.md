# V8 Demo Brief — Where does my store data go when I install this?

**Version 1.1 · 7 Oct 2026** · Demo Video Prompt Pack v3, FILE 2 · supersedes v1.0
**Sources:** Video Topic Filter v4.2 (V8 row, §1.2, §2, §3, §8.4, §8.7) · Brief 3.1.2 (§1, §3, A1, DASH-01–03, DASH-B, A8, SA-01–SA-05, SYS-01–03, FREE-A, FREE-C, FREE-D, B1, B7, B8, §D) · Proof strategy §4.3, §6 (AG3) · Section D guide v1.5 (D0, D2, D4) · Video hand-off note v1.1 §5 · research `research/gemini-2026-10-07.md` (RG), `research/perplexity-2026-10-07.md` (RP) · code read of the installed 3.1.2 free build · the V8 store `ch-v8` · checks run today (§12)
**Owner decisions, 7 Oct 2026:**
1. No "export and erase" claim, because Free's exporter covers Customer Notes only. The default-on digest is noted, not narrated. Product fixes go to dev (`V8_Dev_Requests.md`).
2. "Tested up to" is bumped on wp.org before the take, and the editor zooms on Install Now.
3. The recorder drives the one take and clicks **Skip** on the Freemius opt-in.
4. **The video always runs the latest WordPress, whatever the readme's "Tested up to" says: 7.1.3 today.** If a newer release ships before recording, update first with `scripts/v8-site.sh update`.
5. Q1–Q6 are closed with the recommendations (§9).

**Status:** **Gate A passed, 7 Oct 2026.** Recording waits only for dev request 1.

---

## 1. Header

| | |
|---|---|
| **ID** | V8 · tracking name `no_saas` |
| **Title** | Where does my store data go when I install this? |
| **Tier** | FREE. Every screen is the wp.org 3.1.2 free build |
| **CTA** | Install free → https://wordpress.org/plugins/puredevs-customer-history-for-woocommerce/ (one CTA). On ad landing pages the button is still the trial; the page microcopy bridges it (hand-off note §5) |
| **Week** | Filter §6: Week 1 (now overdue). Launch was Tue 6 Oct; until V8 is live, LP-Alternatives runs table-first |
| **Placements** | LP-Alternatives hero (`/compare/self-hosted-vs-saas/`, AG3) · question page `/questions/where-your-woocommerce-customer-data-is-stored/` (D4 proposal) · homepage hero (V1 brief §6: install CTA matches the homepage buttons) · YouTube · Week 5 retargeting cut (V1, V4, V8). **Not** the readme video (V13) and **not** an in-plugin poster (Pack FILE 5 §5: Pro-answered only) |
| **YouTube title** | **Decided (Q4):** "Where does my store data go when I install PureDevs Customer History?" This is D4's page H1, so the page and the video match; "this" means nothing in search. Beat 1 still speaks the row's words ("…when you install this plugin?") over the answer frame |
| **Recorded on** | **WordPress 7.1.3** (the latest) · WooCommerce 11.1.2 · PureDevs Customer History 3.1.2, the wp.org free build, installed on camera |

---

## 2. Screens

All screens are recorded on the throw-away store `ch-v8` (`http://localhost/ch-v8`). It is the same Northline store as `ch`, but the plugin has never been installed on it. `scripts/v8-site.sh check` gives the record / don't-record verdict, and `reset` returns it to the pre-install snapshot for every retake.

| # | Exact UI title | Entry | Tier | Path | Record on | What it shows |
|---|---|---|---|---|---|---|
| S1 | "Add Plugins" (WordPress core) | — (core) | — | Plugins → **Add Plugin** (`plugin-install.php`; WordPress 7.1.3's menu label) → search "PureDevs Customer History" | ch-v8, pre-install | The wp.org card: name, author, **Install Now**, then **Activate**. Also: active-installs count, star rating, "Last Updated", compatibility line |
| S2 | Freemius opt-in (SDK 2.13.4) | B7 note; SYS-03 | — | Shown at `admin.php?page=pd-woo-dashboard` straight after activation | ch-v8, take | "Hey Sarah," · heading "Never miss an important update" · opt-in body text · **Allow & Continue** · **Skip** · "This will allow PureDevs Customer History for WooCommerce to" (expandable permissions list) · footer "Powered by Freemius – Privacy Policy – Terms of Service" |
| S3 | "Dashboard" | A1, DASH-01–03, DASH-B | FREE | Customer History → Dashboard (lands here after Skip) | ch-v8, take | Six free widgets. Top Customers, Latest Orders and Top Selling Products by This Month are already filled from the store's existing orders. Top Searches, Latest Searches and Most viewed products are still empty. Likely also one upsell banner (Q3) |
| S4 | Northline storefront (logged out) | B1 | — | Shop → search box → "hoodie" → results → two product pages | ch-v8, a separate logged-out browser | A shopper's search and two product views. Admins are never recorded on Free, so this must not be an admin browser |
| **S5 ★ answer frame** | "Search Analytics" | A8, SA-01, SA-02, SA-03, SA-05 | **FREE only** (hidden when Pro is active) | Customer History → Search Analytics (`admin.php?page=pd-woo-search-analytics`) | ch-v8, after S4 | The "Heads up" notice (SA-01), then "Top Keyword Searches" (Keyword / Count) and "Last Searches" (Keyword / Date) with "hoodie", and "Most Viewed Products" (Product / View Count) with the two products |
| S6 | Site Health → Info → "PureDevs Customer History" | SYS-01 | FREE | Tools → Site Health → **Info** tab → last accordion section | ch-v8, after S5 | "Local, non-identifying counters used to understand free-tier usage. This information stays on your server." Four fields: Plugin version · Active tier (Free) · First activated · First free-tier limit reached ("None reached yet") |

| # | Demo prerequisites | ⛔ / ⚑ notes (copied from the entry, or found today) |
|---|---|---|
| S1 | Plugin absent and Freemius never seen (`check`: `pre_*`). wp.org card "Compatible" (`org_tested`, dev request 1). No update bubbles (`cam_updates`): the site runs WordPress 7.1.3, the latest | The card shows active installs, rating and "Last Updated". These are figures; never read them, and zoom on Install Now (owner decision 2) |
| S2 | Freemius state clean; a shot user with first name "Sarah" | ⛔ **Never click Allow & Continue** (it registers the site with Freemius; runbook §7). Skip is local only: it sets anonymous mode and sends nothing. On localhost the SDK may fetch the plugin icon while drawing this screen; that is Freemius, not the plugin's analytics |
| S3 | Orders in the last 90 days, some this month (`data_orders`) | DASH-B: the `dashboard-attention` banner ("%s customers haven't ordered in over 30 days… at risk of churning") shows when any registered customer has been quiet for 30 days. ⚑ It is a churn pitch with a count: never narrate it or stop on it (Q3). Free widgets cache for 30 minutes, so **do not come back to this page** after S4 |
| S4 | The term returns several products; a single result redirects straight to the product. "hoodie" gives four on ch-v8 | ⛔ SA-01/B1: an admin browser is never recorded on Free. Native WordPress/WooCommerce search only (SA-02) |
| S5 | S4 done, then this page opened for the first time | Top Keyword Searches, Last Searches and Most Viewed Products are live queries (`search-stats-data.php:41, 123-174`). **Zero-Result Searches is cached for 30 minutes** (`:298-326`) and stays out of V8 (it is V13's subject). Views are page loads, not unique visitors |
| S6 | Customers page never opened (so the limit field stays "None reached yet") | ⛔ SYS-01: do not call it "usage analytics" or "telemetry we collect". Its sentence describes **these counters**, not every row the plugin stores; never present it as proof about all store data |

**Never on screen:** Tools → Export / Erase Personal Data (owner decision 1) · phpMyAdmin (Q2) · Settings · the Customers page (customer-limit wording) · Zero-Result Searches · any Pro screen · the Freemius "Allow & Continue" result.

---

## 3. Five beats

| Beat | Time | Screen | Narration must establish (no figures) | Claims → ledger |
|---|---|---|---|---|
| 1. The question | 0:00–0:05 | S5 answer frame. "PureDevs Customer History" lower third, "Demo store" label | The question in the owner's words. No greeting | — |
| 2. Where to click | 0:05–0:37 | S1 → S2 → S3, **one take, timer on screen** | The whole install, unedited: Plugins → Add Plugin → search → Install Now → Activate. The one prompt is an optional opt-in from Freemius, the licensing provider; it is skipped. No account, no API key, no setup wizard. The Dashboard already reads the store's own orders. Once: where the data lives, in L8's words (Q1) | L1–L8 |
| 3. What it shows | 0:37–1:13 | S4 → S5 → S6 | A shopper who is not logged in searches for "hoodie" and opens two products. In wp-admin, Search Analytics lists the search and the two products. Your own admin browsing isn't recorded, so this came from the shopper. Then Site Health: even the plugin's own usage counters stay on your server | L9–L13 |
| 4. The decision | 1:13–1:28 | S5 held (or the answer frame) | §4 | L14 |
| 5. Next step | 1:28–1:38 | S5 → end card | §6, then the one CTA | L15–L16 |

Target about 1:35–1:40, inside 60–120 s. Beat 2 is long because the take runs in real time, including the wp.org download. Do not speed it up. If the editor shortens a wait, the timer must stay visible so the jump shows.

---

## 4. The decision (beat 4)

> So here is the decision this gives you. [PAUSE] Whether customer analytics has to mean sending your customer data to someone else's servers. With a plugin like this, it doesn't — no SaaS account, no API key, and the data sits in the same database as your orders.

- The row's decision is "choosing tools that keep customer data on your own site". The plugin never acts in it.
- "A plugin like this" is deliberate. The $79 Marketplace extension is self-hosted too (D2), so there is no "only" (U4).
- **No money clause.** U8 asks beat 4 to end on the money. V8's money is the SaaS subscription, but U7 bans prices, and D2's "one yearly licence instead of a monthly bill" is a pricing-model claim that belongs on the page. So the beat ends on where the data sits. Logged in §10 #17.
- The row's exit line ("Tools → Export / Erase Personal Data") is cut by owner decision 1.

---

## 5. The honest sentence

**Not used — core stays out of V8 by design.**

- Gemini says WooCommerce core "guarantees" the data stays in the site's database (RG §1). That is incomplete. Today WooCommerce.com documents a separate hosted **WooCommerce Analytics** service: "you must connect your store to our service", and it syncs "all of your store's orders, customers, products…" through Jetpack Sync. Source: https://woocommerce.com/woocommerce-analytics-data-syncing/, checked 7 Oct 2026, undated.
- The same name now covers core's local Analytics menu, the wp.org "WooCommerce Analytics" plugin (0.9.16, 14 Aug 2026) and that hosted service. No sentence about "WooCommerce analytics" survives a 5-second read without a paragraph of qualification.
- U6 fallback, used in beat 2: "No account. No API key." It is proven by the take itself.
- The model comparison (hosted vs plugin) lives in D2's page text, never in the narration (Filter §1.2, hand-off §1).

---

## 6. The Free / Pro sentence (beat 5)

> Everything you just saw is the free version. Free shows the last 90 days; Pro shows your full history. [PAUSE] Install it free — the link is below.

End card: "PureDevs Customer History · Install free · WordPress.org".

- Brief wording: "Free shows…", never "Free only tracks…" (U7). Collection is never windowed (FREE-A).
- Never a customer cap (U2.3).
- No trial terms and no price (U7).

---

## 7. Claims ledger

| # | Sentence the script may make | Screen | Entry | Status |
|---|---|---|---|---|
| L1 | This is a WooCommerce demo store | S1, "Demo store" label | — | PROVEN ON SCREEN |
| L2 | The install path: Plugins → Add Plugin → search → Install Now → Activate | S1, one take with timer | core | PROVEN ON SCREEN |
| L3 | There is one prompt: an optional opt-in from Freemius, our licensing provider | S2 ("Skip", "Powered by Freemius") | B7, SYS-03 | PROVEN ON SCREEN |
| L4 | We skip it, and the plugin works | S2 → S3 | code: Skip sets anonymous mode | PROVEN ON SCREEN |
| L5 | No account. No API key | S1–S3: no sign-up or key step anywhere in the take | §1, B7 | PROVEN ON SCREEN + Brief |
| L6 | No setup wizard | S2 → S3 lands straight on the Dashboard | §1 ("There is no wizard") | PROVEN ON SCREEN |
| L7 | The dashboard already reads the store's own orders | S3: Latest Orders, Top Customers and Top Selling filled at first load | A1, DASH-01–03 | PROVEN ON SCREEN |
| L8 | It's self-hosted: what it records stays in your own WordPress database | S5 read back inside the store's own wp-admin | §1, B1, B7 | PROVEN ON SCREEN + Brief (V1 L4 standard) |
| L9 | A shopper who isn't logged in searches the store for "hoodie" and opens two products | S4, "logged-out visitor" lower third | B1 | PROVEN ON SCREEN |
| L10 | The search is here, and so are the two products they opened | S5: Top Keyword Searches, Last Searches, Most Viewed Products | SA-02, SA-03, SA-05 | PROVEN ON SCREEN |
| L11 | Your own admin browsing isn't recorded; this came from the shopper | S5, SA-01 notice | SA-01, B1 | PROVEN ON SCREEN |
| L12 | It starts recording as soon as it's activated | S4 → S5: data made after the install | §1 ("Tracking starts on activation") | PROVEN ON SCREEN (optional line) |
| L13 | Even the plugin's own usage counters stay on your server (reading the on-screen sentence) | S6 | SYS-01 | PROVEN ON SCREEN — counters only |
| L14 | Decision (§4) | S5 | Filter row | Owner's decision line |
| L15 | Everything you just saw is the free version. Free shows the last 90 days; Pro shows your full history | S5 is a Free-only screen | FREE-A, FREE-D, A8 | PROVEN (Brief tier) |
| L16 | Install it free; the link is below | end card | U2.4 | PROVEN — one CTA |
| C1 | "Nothing leaves your server" | — | U3, hand-off §5 | **CUT (Q1)** until dev request 4 ships. Approved wording, but the free build emails a weekly order CSV to the admin by default |
| C2 | "Works with WordPress's personal-data export and erase tools" | — | SYS-02 | **CUT** — owner decision 1. On Free it covers Customer Notes only |
| C3 | "Charts load from the plugin folder" | — | B8 | **CUT** — no screen in the video proves it, and Search Analytics loads no charts |
| C4 | "GDPR-compliant", "cookie-less", "anonymised", "consent-managed", "no cookies" | — | D.2, B1 | **CUT** — IP addresses are stored; PHP starts a session cookie (`PHPSESSID`) for every visitor |
| C5 | Any time promise ("in a minute", "five minutes") | — | Filter row | **CUT** — the timer shows it |
| C6 | Competitor names, or hosted vs plugin in narration | — | U4, Filter §1.2 | **CUT** — D2 page text only |
| C7 | Anything about WooCommerce core or WooCommerce.com's Analytics service | — | U6 | **CUT** — §5 |
| C8 | "Nothing is sent anywhere" / "no network requests at all" | — | B7 | **CUT** — Freemius, WordPress update checks and the digest email exist; B7's true version is "the plugin makes no outbound requests of its own" (page text) |
| C9 | SYS-01's sentence as proof that all store data stays | — | SYS-01 | **CUT** — it describes the counters |
| C10 | Any Free customer cap | — | U2.3 | **CUT** |

---

## 8. Limits a buyer will hit

For the question-page FAQ, together with the research's buyer doubts (RP §4).

| # | Limit | Source | Doubt it answers |
|---|---|---|---|
| 1 | What is stored: searches (with the result count), the path of every non-404 page (product views are derived from it), add-to-cart and new-order events. Each row carries the user ID (0 for guests), IP address, HTTP referer, date and PHP session ID. One table: `{prefix}pd_woo_customer_history_sessions` | B1; `class-pdwchs-sessions-data.php:181-225` | "What exactly does it collect?" (RP owner language 4) |
| 2 | IP addresses and emails are stored, so it is not anonymised. Tracking is server-side PHP with no JavaScript tracker; PHP starts a session (cookie `PHPSESSID`). It does not settle your privacy-policy obligations | B1, D.2; `class-pd-customer-history.php:126-129` | Privacy (RP doubt 4) |
| 3 | Administrators and shop managers are never recorded on Free | SA-01, B1 | "Why is my report empty?" |
| 4 | Native WordPress/WooCommerce search only; AJAX search plugins: see the search-plugin test (Filter §8.2) | SA-02 | Search-plugin coverage (RP doubt 5) |
| 5 | The plugin makes no outbound HTTP requests of its own; Chart.js is bundled. Licensing and updates run through Freemius, with an opt-in you can skip | B7, B8; code read today | "Does it send my data anywhere?" (RP phrasing 6) |
| 6 | **The weekly email digest is on by default**: an order CSV goes to the admin email, and a copy stays in the uploads folder. Switch it off in Customer History → Settings | `activator.php:43-45`; `class-pd-customer-history.php:313, 500, 550`; dev request 4 | "Does anything leave by email?" |
| 7 | WordPress's export and erase tools cover Customer Notes on Free (Tags on Pro). Tracked visit rows are not part of those tools yet | SYS-02; dev request 2 | GDPR requests |
| 8 | Free shows the last 90 days; collection is never windowed, and Pro shows the full history at once | FREE-A, FREE-D | "Do I lose older data?" |
| 9 | Nothing is deleted automatically. On Free there is no in-plugin delete; deleting the plugin drops its three tables and options. Pro adds delete-by-date-range | FREE-C, B7; `puredevs-customer-history-for-woocommerce.php:112-156` | Retention, database growth (RP doubt 3) |
| 10 | Bots are recorded as guests on Free; views are page loads, not unique visitors | B1, SA-05 | Accuracy |

---

## 9. Decisions (closed 7 Oct 2026: owner, "proceed with what you recommend")

| # | Question | Decision |
|---|---|---|
| Q1 | Beat 2's data line: "nothing leaves your server" (U3, hand-off §5) while the digest emails an order CSV by default | **L8: "what it records stays in your own WordPress database."** "Nothing leaves your server" comes back once dev request 4 ships |
| Q2 | Answer frame: Search Analytics, or the plugin's table in phpMyAdmin | **Search Analytics (S5).** No phpMyAdmin |
| Q3 | The `dashboard-attention` banner on the first Dashboard | **No pre-dismissal**: the take stays honest. The editor's zoom on S3 frames the widget grid, with the banner outside it. Dev request 5 asks to delay it on new installs |
| Q4 | Title | **YouTube title = D4's H1**: "Where does my store data go when I install PureDevs Customer History?" Beat 1 speaks "…when you install this plugin?" |
| Q5 | WordPress 7.1.3 | **Done 7 Oct.** `ch` and ch-v8 updated to 7.1.3; ch-v8 re-snapshotted. Owner rule: always the latest WordPress (`scripts/v8-site.sh update`) |
| Q6 | D4's V8 "Must state" says "Works with WordPress's personal-data export and erase tools" | **Narrowed** in Section D guide v1.5 (§11) |

---

## 10. Sanity check (FILE 6 list, run on the row, the research and this draft)

| # | Where | Claim | Problem | Rule | Action |
|---|---|---|---|---|---|
| 1 | Filter row, beat 3 | SYS-01 "This information stays on your server" as the "where it lives" proof | The sentence describes four local counters, not the stored rows | U5, SYS-01 ⛔ | Kept, narrowed to the counters (L13); C9 |
| 2 | Filter row, beat 3 | "Charts load from the plugin folder" | True (B8), but no screen in V8 proves it; Search Analytics loads no charts | U5 | Cut (C3) |
| 3 | Filter row, beat 4 | "Your exits: Tools → Export / Erase Personal Data" | Free's exporter covers Customer Notes only; session rows are not covered; readme.txt:146 overclaims | U3, U5, owner decision 1 | Cut (C2); dev requests 2–3 |
| 4 | Filter row, guardrail | "Open no plugin page before the incognito search" | The Freemius redirect always lands on the Dashboard first | Row | The S5 tables are live queries (only Zero-Result is cached, and it stays out). Never return to the Dashboard |
| 5 | U3 / hand-off §5 | "Nothing leaves your server" | The default-on digest emails an order CSV to the admin | U3, U5 | Cut until dev request 4 (C1, Q1) |
| 6 | RG §1 | WooCommerce core "guarantees" data stays local | Incomplete: WooCommerce.com's hosted WooCommerce Analytics service syncs store data via Jetpack Sync (official page, checked today) | U2.2, `[CONFLICT]` | Resolved against the official page; core kept out (§5) |
| 7 | RP verdict | Suggested narration names Metorik, Metrilo, Putler | Competitor names in narration | U4 | Not used; D2 page text only |
| 8 | RG verdict | Refined line: "Metorik and Putler… securely locked inside your site's own database" | Competitor names; "securely locked" is unprovable | U4, U5 | Not used |
| 9 | RP proof sequence | Browser Network panel; table names | Other requests exist (Freemius icon fetch on localhost, theme fonts); a Network panel invites "zero requests" readings | U5 | Not used (Q2: no phpMyAdmin either) |
| 10 | RP PureDevs entry | "Up to 100 customers" | Owner rule | U2.3 | Not used anywhere |
| 11 | RP §1 core | "Since WooCommerce 10.5, analytics imports may update every 12 hours" | Cites the 2018 wc-admin 0.4.0 notes | U2.2 | Not used |
| 12 | RG vs RP | Metorik Helper date (Sep vs 18 Aug 2026); Customer Analytics 2.7.4 date | `[CONFLICT]` | — | Not used; D2's VA checks vendors on build day |
| 13 | RG §2, §4 | "Database bloat", PHP worker exhaustion, 503s | Doubts about other products; nothing on screen in V8 answers them | U5 | FAQ only (limit 9), no performance claim |
| 14 | This draft | "No setup wizard" right after showing a prompt | Could read as contradictory | U5 | Order fixed: the prompt is named first, then "no wizard" (L3 → L6) |
| 15 | S1 | Card shows "Untested with your version of WordPress" today (wp.org `tested` 7.0.7, site 7.1.3) | Undermines a trust video | Owner decisions 2, 4 | The site stays on the latest WordPress; the take is blocked until dev request 1; `check` → `org_tested` |
| 16 | S3 | Upsell banner with a count and a churn pitch | ⚑ churn as a visible pitch; an on-screen figure | U4, U2.1 | Never narrated or held; zoom excludes it (Q3) |
| 17 | §4 | Beat 4 does not end on money | U8 asks for the money | U7 (no price) wins | Ends on where the data sits; D2 carries the billing-model row |
| 18 | S6 | "First free-tier limit reached" field | Could show `customer_cap` if the Customers page were opened | U2.3 | Customers page never opened; post-take check: "None reached yet" |
| 19 | Whole draft | Spoken figures, ⚑ subjects, "only/first", price, trial terms | None present | U2.1, U4, U7 | Clear |

**Verdict (v1.1):** PASS for recording once dev request 1 lands. Script v1.2 matches this brief.

---

## 11. Outside this brief — claims on V8's pages to fix

| Page | Claim | Problem | Fix |
|---|---|---|---|
| wp.org readme FAQ (`readme.txt:146`) | Export/erase "fully supports the WordPress privacy request workflow" for session data; "for GDPR" | False for session rows; "GDPR" framing | Dev requests 2–3 |
| wp.org readme (`readme.txt:63`) | Digest "optional, opt-in" | On by default | Dev request 4 |
| wp.org readme header | "Tested up to: 7.0" | The card warns on WordPress 7.1 | Dev request 1 |
| wp.org listing | "Up to 100 customers" (still listed, RP) | Owner rule 3 | Remove (already in V1 brief §11) |
| D4 `no_saas`, Must state | "Works with WordPress's personal-data export and erase tools" | Customer Notes only on Free | **Done, Section D guide v1.5:** "Customer notes work with WordPress's export and erase tools; tracked visit rows are not part of them yet" |
| D4 `no_saas`, answer | "…makes no outbound requests of its own" | True for HTTP; the default digest still emails a CSV to the admin | **Done, Section D guide v1.5:** Must state adds "The weekly digest email to you is on by default; switch it off in Settings" |
| D2 Sub | Names Metorik and Metrilo | Allowed in page text (U4: model and data location only) | VA re-checks the vendor column on build day, as D2 says |

---

## 12. Checks run today (7 Oct 2026)

- PureDevs Customer History on wp.org: **3.1.2**, `tested` **7.0.7**, last updated 24 Aug 2026 — https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=puredevs-customer-history-for-woocommerce
- WordPress latest: **7.1.3** — https://api.wordpress.org/core/version-check/1.7/ (`ch` and ch-v8 updated to it the same day)
- WooCommerce: **11.1.2**, last updated 22 Sep 2026, tested 7.1.3 — https://wordpress.org/plugins/woocommerce/
- WooCommerce Analytics plugin: 0.9.16, 14 Aug 2026 — https://wordpress.org/plugins/woocommerce-analytics/
- WooCommerce Analytics data syncing (connect + Jetpack Sync of orders, customers, products…; undated) — https://woocommerce.com/woocommerce-analytics-data-syncing/
- Core "Untested" rule: `wp-admin/includes/class-wp-plugin-install-list-table.php:566` (WordPress 7.1.3)
- Code read, free 3.1.2 build installed in `ch`:
  - Freemius SDK 2.13.4: on localhost the opt-in is still shown (`class-freemius.php:3815-3819`); Skip is local; the connect screen strings are in `templates/connect.php`
  - Site Health section: `includes/class-pdwchs-telemetry.php:41, 108-131`
  - Exporter/eraser: Customer Notes only — `includes/crm/class-pdwchs-notes.php:21-22`
  - Digest on by default: `includes/class-pd-customer-history-activator.php:43-45`
  - No outbound requests in plugin code; Chart.js bundled at `admin/js/chart.umd.js`
  - Search Analytics: live queries except Zero-Result (30-minute transient) — `includes/data/class-pdwchs-search-stats-data.php:41, 123-174, 298-326`
  - Admins skipped: `public/class-pd-customer-history-public.php:65`
- ch-v8 on WordPress 7.1.3: `scripts/v8-site.sh check` → every check PASS except `org_tested` (dev request 1)

---

## Changelog

- **v1.1 — 7 Oct 2026.** Gate A passed: Q1–Q6 closed with the recommendations (§9). Owner rule: always the latest WordPress (7.1.3 today); `ch` and ch-v8 updated. "Recorded on" row added; phpMyAdmin (S7) dropped; C1 cut until dev request 4; YouTube title set to D4's H1; D4 fixes applied in Section D guide v1.5.
- **v1.0 — 7 Oct 2026.** First Demo Brief for V8. Applies the three owner decisions of 7 Oct, the ch-v8 site, and today's code read and checks.
