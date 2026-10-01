# PureDevs — Section B Guide: Tracking and Consent

**Version:** v1.3 · **Date:** 1 Oct 2026 · **Owner:** Yeasir
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.md` (items B1–B5)
**Your total time in B:** about 1.5 hours. Everything else is DEV or VA.
**Note:** Google renames menus often. If a label is not where this guide says, use the search box inside that product. Plugin labels can also shift between versions.

---

## B0. Who owns what

| Asset | Owner login | Access for others |
|---|---|---|
| Google Ads (new account), GTM, GA4, Search Console, Clarity | yeasir@puredevs.com | pervej86@gmail.com = backup Admin on Ads · DEV = GTM Publish + GA4 Editor · VA = Ads Standard + GA4 Viewer |

- The cookie banner is a WordPress plugin (B3), so it needs no separate login.
- Turn on 2-step verification for both logins.
- Work day to day from yeasir@puredevs.com only.
- Order: B1 → B2 → B3 → B4 → B5. B1 and B2 steps 1–3 fit in one sitting.

---

## B1. Google Ads account — Y · 40 min · due Mon 21 Sep

**Decision:** open a new account under yeasir@puredevs.com. The old login holds one Closed account and one setup that was never finished, so there is no history worth keeping. One login then owns Ads, GTM, GA4 and Search Console.

1. Use a separate browser profile, or sign out of pervej86@gmail.com. Sign in as yeasir@puredevs.com → ads.google.com → start a new account.
2. On the first wizard screen choose **Switch to Expert Mode**, then **Create an account without a campaign** (the label may read "Skip campaign creation").
3. Confirm **billing country**, **time zone** and **currency**. You cannot freely change these later.
   - USD is preferred, because Freemius revenue and every gate in the strategy are in USD. A local-currency account is workable if your card only supports that.
4. Name the account `PureDevs`. The payments profile name and address must match the documents you will use for verification.
5. Billing → Payment methods → add a card that allows international online payments. Choose automatic payments. Expect a small temporary authorisation.
6. Billing → **Advertiser verification**: if a task is shown, start it today. Review takes days. If nothing is shown, there is nothing to do yet.
7. Admin → Account settings → **Auto-tagging: ON**.
8. Admin → Access and security → add pervej86@gmail.com as a backup **Admin** and the VA as **Standard**.
9. Old login: leave the Closed account closed. Do not finish the "PureDevs (Setup in progress)" account and never add billing to it, so only one live account advertises puredevs.com.
10. If the wizard forces you to create a campaign, create it and **pause it immediately**.

**Done when:** account is active under yeasir@puredevs.com · payment method accepted · backup Admin added · auto-tagging is on.

---

## B2. GTM + GA4 — Y 15 min, then DEV 2 h · due Wed 23 Sep

**Y — create the accounts (as yeasir@puredevs.com)**
1. tagmanager.google.com → Create account `PureDevs` → container `puredevs.com` → **Web**. Note the `GTM-XXXXXXX` ID. Admin → User management → add DEV with Publish.
2. analytics.google.com → Admin → Create account `PureDevs` → property `puredevs.com`, currency **USD** → Web data stream `https://puredevs.com` → Enhanced measurement **ON**. Note the `G-XXXXXXX` ID. Add DEV as Editor.
3. Admin → Data collection and modification → Data retention → **14 months**.

**DEV — install and configure**
4. Load GTM through the GDPR Cookie Compliance plugin (B3 steps 1–3), **not** the theme. If GTM snippets are already in the `puredevs-site` theme, remove them. Do **not** also install gtag.js or Site Kit (double counting). Purge cache.
5. In GTM: new tag → **Google tag** → Tag ID = `G-XXXXXXX` → trigger Initialization – All Pages.
6. Internal-traffic flag (no IP lists):
   - Variable: 1st-party cookie `pd_internal`.
   - Tag: Custom HTML, fires on Page View where Page URL contains `internal=1`:
     `<script>document.cookie="pd_internal=1; max-age=31536000; path=/";</script>`
   - Variable: Lookup table on the cookie: `1` → `internal`, default empty.
   - In the Google tag → Configuration settings → add parameter `traffic_type` = that lookup variable.
   - GA4 → Admin → Data collection and modification → Data filters → **Internal Traffic** → keep on Testing until step 7 passes, then set **Active**.
   - Everyone on the team opens `https://puredevs.com/?internal=1` once per browser and device.
7. Test: GTM Preview shows the Google tag firing · GA4 DebugView shows `page_view` · your own visit carries `traffic_type = internal`.

**Done when:** live `page_view` data in GA4 and the internal filter is Active.

---

## B3. Consent banner + privacy policy — DEV 1.5 h, Y 20 min · due Fri 25 Sep

**Decision (v1.3):** free **GDPR Cookie Compliance** plugin by Moove Agency, installed in WordPress. Tracking is **on by default** for every visitor. The banner offers Reject, and Reject really switches tracking off. No region rules, no VPN tests.

**Known risk, accepted:** UK and EEA visitors are tracked before they consent. That does not meet UK/EEA cookie rules, and Google can limit remarketing and personalised ads for that traffic. Revisit once the business is earning: the premium add-on (£59/year) adds Geo-location, which shows an opt-in banner to UK/EEA visitors only.

**DEV — plugin setup**
1. WP Admin → Plugins → Add New → search "GDPR Cookie Compliance" (by Moove Agency) → Install → Activate. Do not install CookieYes or any other consent plugin.
2. GDPR Cookie Compliance → cookie categories:
   - Strictly Necessary: always on.
   - 3rd Party Cookies (analytics): ON · default **Enabled**.
   - Additional Cookies (ads): ON · default **Enabled**.
3. GDPR Cookie Compliance → Integrations:
   - **Google Tag Manager** → `GTM-XXXXXXX`. The plugin now loads GTM. Remove any GTM snippet from the theme so it does not load twice.
   - **Google Consent Mode v2** → ON. This is what makes Reject actually stop tracking. If asked to map categories: `analytics_storage` → 3rd Party · `ad_storage`, `ad_user_data`, `ad_personalization` → Additional.
   - Microsoft Clarity integration → leave **OFF**. Clarity runs from GTM (B5).
4. Banner: small box, bottom-left, so it never covers the video or the CTA · buttons Accept, Reject, Settings · suggested text: "We use cookies for analytics and ads. You can switch them off at any time." · Privacy Policy link ON.
5. Add a **Cookie settings** link in the footer pointing to `/#gdpr_cookie_modal`, so visitors can change their choice after the banner closes.
6. Do not add YouTube or any other script to the plugin's script boxes. Everything runs through GTM.

**DEV — GTM**
7. GTM → Admin → Container settings → enable **consent overview**. Google tag and GA4 tags use built-in consent checks. For Clarity (B5) add a required check on `analytics_storage`.

**DEV — cookie list for the policy** (the free plugin has no scanner)
8. Open the site, accept, then DevTools → Application → Cookies → list every cookie with name, provider, purpose and expiry. Expect at least: `_ga`, `_ga_*` (GA4) · `_gcl_au` (Google Ads) · `_clck`, `_clsk` (Clarity) · the plugin's own consent cookie.

**Privacy policy**
9. WP Admin → Settings → Privacy → create or select the Privacy Policy page.
10. It must name: GA4 · Google Ads (conversion tracking and remarketing) · YouTube embeds · Clarity · Brevo (email) · Freemius (checkout and payment) · the cookie list from step 8 · that analytics and ad cookies are on by default and how to switch them off (the Cookie settings link) · contact email · how to request access or deletion.
11. Add **Privacy Policy**, **Terms** and **Cookie settings** links to the footer (none are there today), and one line under every Playbook form linking to the policy.
12. Y reads and approves the text.

**Test** (incognito window, no VPN needed)
- New visitor, no click: GTM Preview → Consent tab shows `analytics_storage` and `ad_storage` **Granted** · GA4 DebugView shows `page_view`.
- Click **Reject**: Consent tab shows **Denied** · `_ga` cookies are removed or stop updating (DevTools → Cookies).
- Footer **Cookie settings** reopens the panel.
- Without clicking the banner: the demo video plays and the checkout opens.

**Done when:** all four tests pass and the Privacy Policy, Terms and Cookie settings links are in the footer.

---

## B4. Events → GA4 → Google Ads — DEV 2 h, Y 10 min · due Fri 25 Sep

**The data contract.** The site pushes these. The checkout code that fires them comes in the section C dev spec.

```js
dataLayer.push({ event: 'checkout_open', plan_id: '12279', cta_location: 'pricing_trial' });
dataLayer.push({ event: 'trial_start',   plan_id: '12279', trial_days: 30 });
dataLayer.push({ event: 'purchase', transaction_id: '...', value: 79.00, currency: 'USD', plan_id: '12279', coupon: 'FOUNDING50' });
dataLayer.push({ event: 'video_play', video_id: 'zero_result_searches', page_type: 'lp_category' });
```

`video_id` is the video's **name**, never its number (`zero_result_searches`, `no_saas`, `returning_revenue` ...). The video list was renumbered once already; names keep the reports stable. The full name table is in the Section D guide v1.1.

`video_play` fires when a visitor clicks the video poster. Landing pages load YouTube only on click (faster page, and the banner tool cannot block it). Use `youtube-nocookie.com` for the embed.

**DEV — GTM**
1. Data Layer variables: `plan_id`, `cta_location`, `trial_days`, `transaction_id`, `value`, `currency`, `coupon`, `video_id`, `page_type`.
2. One trigger: Custom Event, regex match `^(checkout_open|trial_start|purchase|video_play)$`.
3. One tag: GA4 Event · Measurement ID `G-XXXXXXX` · Event name `{{Event}}` · map the variables as parameters · the trigger above.
4. Test before section C exists: in GTM Preview, paste a push line into the browser console and watch it arrive in GA4 DebugView.
5. GA4 → Admin → Data display → Custom definitions → register `plan_id`, `cta_location`, `video_id`, `page_type` as event-scoped dimensions.
6. GA4 → Admin → Data display → Key events → add `trial_start` and `purchase`.

**Y — link and import (as yeasir@puredevs.com, Admin on both sides)**
7. GA4 → Admin → Product links → **Google Ads links** → Link → pick the Ads account → keep personalised advertising and auto-tagging on.
8. Google Ads → Goals → Conversions → Summary → **New conversion action** → Import → Google Analytics 4 properties → Web → select both events.
9. Settings: `trial_start` = **Primary**, category Sign-up, count One · `purchase` = **Secondary**, category Purchase, count Every, use the GA4 value.
   - Only `trial_start` is Primary, so "cost per conversion" in Ads equals cost per trial, the number every gate uses.
10. Imported conversions can lag up to a day, and show "No recent conversions" until the first real one. That is normal.

**Done when:** a test `trial_start` shows in GA4 DebugView and both conversion actions exist in Google Ads.

---

## B5. Search Console, UTMs, Clarity — VA 1.5 h (DNS step by whoever holds DNS) · due Fri 25 Sep · does not block launch

**Search Console**
1. search.google.com/search-console as yeasir@puredevs.com → Add property → **Domain** → add the TXT record at the DNS host → Verify.
2. Sitemaps → submit the site's sitemap URL (WordPress default is `/wp-sitemap.xml`; an SEO plugin may use `/sitemap_index.xml`).
3. GA4 → Admin → Product links → Search Console links → Link.

**UTM convention** (lowercase, no spaces, one shared builder sheet kept by the VA)

| Parameter | Allowed values |
|---|---|
| `utm_source` | google · youtube · wporg · plugin · email · newsletter · capterra · g2 |
| `utm_medium` | cpc · video · email · referral · listing |
| `utm_campaign` | `proof2026_search_category` · `proof2026_search_alternatives` · `proof2026_search_brand` · `proof2026_trial_seq` · `proof2026_readme` |

- Google Ads relies on auto-tagging. No manual UTMs on ads.
- Existing site links to WordPress.org already use `utm_source=puredevs`. Leave them.

**Clarity**
4. clarity.microsoft.com → new project `puredevs.com` → copy the project ID.
5. GTM → new tag from the gallery: **Microsoft Clarity – Official** → project ID → trigger All Pages → Consent settings → require `analytics_storage`.
6. Confirm Clarity is named in the privacy policy (B3 step 10).

**Done when:** domain verified, sitemap accepted, builder sheet shared, Clarity recording a test visit.

---

## B sign-off (feeds QA item J2)

- [ ] Ads account active, payment accepted, auto-tagging on
- [ ] GA4 receives `page_view`; internal filter Active
- [ ] Tracking Granted by default; Reject sets Denied
- [ ] Video plays and checkout opens without clicking the banner
- [ ] Test `trial_start` reaches GA4 DebugView
- [ ] `trial_start` Primary and `purchase` Secondary exist in Google Ads
- [ ] Privacy Policy, Terms and Cookie settings linked in the footer
- [ ] GTM container published only after every box above is ticked

## Changelog
- **v1.3 — 1 Oct 2026.** B3 rewritten: CookieYes replaced by the free GDPR Cookie Compliance plugin. Tracking is on by default with a working Reject; no region rules. UK/EEA risk accepted, to revisit with the premium Geo-location add-on once the business is earning. DEV time in B3 drops from 3 h to 1.5 h. B2 step 4: GTM now loads through the plugin, not the theme. B0 and sign-off updated to match.
- **v1.2 — 20 Sep 2026.** B4: `video_id` now carries the video's name instead of a V-number, after Video Topic Filter v4 renumbered the list. Nothing else changed.
- **v1.1 — 19 Sep 2026.** B0 and B1 rewritten: new Google Ads account under yeasir@puredevs.com instead of reusing the old login, which holds only a Closed account and an unfinished setup. pervej86@gmail.com becomes backup Admin.
- **v1.0 — 19 Sep 2026.** First version. Reuses the existing Google Ads account, picks CookieYes (web app + GTM template) for region-specific consent defaults, and restores `video_play` as a click event because landing-page videos load on click.
