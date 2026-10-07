# V8 — requests for the dev team

**Video:** V8 "Where does my store data go when I install this?" · FREE · `no_saas`
**Written:** 7 Oct 2026, from a code read of the installed 3.1.2 builds (`ch`) and the wp.org API
**Status:** item 1 blocks V8's recording. Items 2–5 do not; V8 is scripted around them (owner decision, 7 Oct 2026). Once 2–4 ship, the "export and erase" line can come back in a later cut.

---

## 1. Blocking — bump "Tested up to" on WordPress.org (readme only)

V8's beat 2 is a one-take install from **Plugins → Add Plugin**. The search-result card shows WordPress's compatibility line, computed by core as `version_compare( site version, wp.org "tested", '<=' )` (`wp-admin/includes/class-wp-plugin-install-list-table.php:566`).

| | Today (7 Oct) |
|---|---|
| wp.org `tested` | **7.0.7** (readme says `Tested up to: 7.0`) |
| Latest WordPress | **7.1.3**, which is what the V8 store runs. Owner rule: the video always shows the latest WordPress |
| Card on a current site | **"Untested with your version of WordPress"** |

**Ask:** change `Tested up to: 7.1` in the readme of the current stable tag (3.1.2) and trunk. This is a readme-only SVN commit: no version bump and no release. wp.org then reports the latest 7.1.x (7.1.3 today, and later 7.1 point releases automatically), and the card reads "Compatible with your version of WordPress". Real users on 7.1 see the same warning today.

When WordPress 7.2 ships, bump again before any V8 retake: the store is updated to the latest WordPress first.

**How we check it:** `scripts/v8-site.sh check` → `org_tested` must PASS before the take.

## 2. Personal-data exporter and eraser for tracked sessions

- Today only **Customer Notes** is registered with Tools → Export / Erase Personal Data (Free; `includes/crm/class-pdwchs-notes.php:21-22, 305-411`). Pro adds Customer Tags.
- The sessions table (`{prefix}pd_woo_customer_history_sessions`) holds the user ID, IP address, page paths, searches, referrer and PHP session ID. It is covered by neither tool, and nothing hooks `delete_user`.
- **Ask:** register an exporter and an eraser for session rows, looked up by user ID from the request's email. Guest rows have no email, so they are out of scope; say so in the readme.

Separately, Pro's "PureDevs Cart History" exporter hooks `woocommerce_privacy_export_personal_data_exporters` and `woocommerce_privacy_erasure_applies_to_personal_data` (`pro/Carts/Cart-Tracker.php:33-34`). Neither filter exists in WooCommerce 11.1.2, so it never appears under Tools. Hook the core `wp_privacy_personal_data_exporters` / `_erasers` filters, as Notes and Tags do.

## 3. readme.txt overclaims the privacy tools

`readme.txt:146` (FAQ "What customer data is stored, and how do I erase it for GDPR?") says the plugin "fully supports the WordPress privacy request workflow" for session data. After item 2 ships, the line can stay with the guest caveat; until then it is false. Also drop "for GDPR" from the question (Brief D.2: never "GDPR-compliant").

## 4. The weekly digest is on by default and leaves CSVs in public uploads

- Activation sets `pd-woo-generate_weekly_report = ['order']` when the option is empty (`includes/class-pd-customer-history-activator.php:43-45`), so a new install emails an order CSV to `admin_email` every week (`includes/class-pd-customer-history.php:500, 550`).
- `readme.txt:63` calls the digest "optional, opt-in". It is opt-out.
- Each run writes `customers_order_history_from_…_to_….csv` and `top_customers_history_from_…_to_….csv` into the dated public uploads folder (`:313, 353, 427`) and never deletes them. The names are guessable from the dates.
- **Asks:** (a) make the digest opt-in: no default report on activation. (b) Write the CSVs to a non-public location (or a random file name) and delete them after sending.
- Why it matters for V8: with the digest on by default, "nothing leaves your server" isn't strictly true. V8's narration avoids that absolute until this ships.

## 5. Small: the post-install Dashboard opens with a churn upsell

On a store with any registered customer who hasn't ordered in 30 days (every real store), the first Dashboard after "Skip" shows the `dashboard-attention` banner: "%s customers haven't ordered in over 30 days. Customer History Pro shows you who's at risk of churning — and why." (`admin/partials/templates/dashboard.php:26-37`). That puts a churn pitch with a count first on a new user's first screen. Consider holding it back until the plugin has been active for a few days.

V8 handles it in the edit; it is not a blocker.

## 6. Small: pressing Back re-records a search

Found in the V8 rehearsal (7 Oct).
- **What happens:** a shopper searches, opens a product, then presses Back. Chrome reloads the results page instead of restoring it from its back/forward cache, so the plugin records the same search a second time. Top Keyword Searches then reads 2 and Last Searches lists it twice.
- **Likely cause:** `session_start()` on every request (`includes/class-pd-customer-history.php:126-129`) makes PHP send `Cache-Control: no-store, no-cache` (the default `session.cache_limiter`), which keeps the page out of the back/forward cache.
- **Effect:** inflates search counts on real stores whenever shoppers go back to their results.
- **Ask:** skip recording a search when the same session already logged the same term in the last few minutes. Or start the session only when it is actually needed.

V8 avoids it on camera: the shopper reaches the second product through the "Hoodies" breadcrumb. Not a blocker.
