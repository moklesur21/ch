# V8 Claim Audit — Where does my store data go when I install this?

**Version 1.0 · 7 Oct 2026** · Demo Video Prompt Pack v3, FILE 6 · step 6 of `project_asset/docs/video-production.md`

**Audited:**
- the final export `recordings/edit/V8_reference_master.mp4` (1:57): frames sampled every 3 s, plus at each word-on-action moment; the burned-in captions read off the frames and checked against `recordings/captions.srt`;
- the 27 s cut `V8_cut_27s.mp4`;
- the YouTube package (Script v1.2 §B);
- the poster `V8-poster.png`;
- the three screenshots;
- Placement Kit v1.0: the question page, the email line, the readme FAQ text, the ad match.

**Freshness checks made today (U2.2):**

| Product | Latest today | Source |
|---|---|---|
| WordPress | **7.1.3** | https://api.wordpress.org/core/version-check/1.7/ |
| WooCommerce | **11.1.2**, 22 Sep 2026 | wp.org API |
| PureDevs Customer History | **3.1.2**, `tested` 7.0.7, 24 Aug 2026 | wp.org API |
| Freemius SDK | 2.13.4 in that build | the SDK's `start.php` |

The recording runs exactly these versions.

---

## Findings

| # | Line or section | Problem | Rule | Fix |
|---|---|---|---|---|
| 1 | Master, first frame | The "PureDevs Customer History" title faded in over 0.35 s, so frame one had no product name | U8 | **Fixed:** a label that starts a segment is on from frame one (`render-edit.mjs`); the master and the cut were re-rendered |
| 2 | YouTube description | The video ends "Install it free — the link is below", but the WordPress.org link was the last line of the description, under the chapters | U7 (one CTA, and it must be findable) | **Fixed in Script v1.2 §B:** WordPress.org is line 2, right under the answer line |
| 3 | 27 s cut, retargeting | "The link is below" is false where the ad lands on LP-Alternatives: its only button is the trial | U7 | **Fixed in Kit §1:** the retargeting final URL is the question page, whose walkthrough ends with the WordPress.org link |
| 4 | Ad copy, AG3 (strategy §6.2 Description 2; Headline 4) | "Your store data never leaves your site" / "Your Data Stays On Your Site" are absolutes. The free build emails a weekly order digest to the admin by default (dev request 4), and Section D's rule D0 already bans the absolute on pages | U3, U5 | **Fixed in Kit §8:** "Data Stays In Your Database" (27) and "Customer analytics that runs inside WordPress. Your store data stays in your own database." (90). The old lines can return once dev request 4 ships |
| 5 | V1 question page (outside V8) | FAQ 5 says the plugin "works with WordPress's personal-data export and erase tools" with no limit; the free build's exporter covers Customer Notes only | U3, U5 | **Flagged:** use the V8 Kit §2 wording in V1's page and the Question Pages dev spec |

No other false, stale, unprovable or forbidden claim was found. The checks below came back clean.

## Checks that passed (the eight-point list)

1. **Owner rules (U2).**
   - No spoken figure. The only number spoken is "90 days", the Free read window that rule 3 asks for.
   - No sentence about another tool except Freemius ("an optional opt-in from Freemius, our licensing provider"). It is proven on screen (Skip, then the Dashboard) and by today's code read: Skip stores a local flag and sends nothing.
   - No customer cap anywhere.
   - Links: the free plugin URL and `/pricing` only.
2. **The plugin observes and reports (U3).**
   - "What it records stays in your own WordPress database": B1 (one table) and B7; S5 reads it back.
   - "The dashboard is already reading the store's own orders": Latest Orders filled at first load.
   - "Your own admin browsing isn't recorded": SA-01, the notice is on screen.
   - "Nothing leaves your server" is not used (Demo Brief Q1).
   - The privacy wording on the page follows D0 / D4 v1.5: Freemius named, the digest stated, export/erase limited to notes.
3. **Never-pitch (U4).**
   - The Dashboard's upsell banner (a count and a churn pitch) is on screen for about 2 s before the zoom, never narrated or held.
   - No competitor named in the narration, captions or ad text; "a plugin like this" makes no "only" claim.
4. **Claims discipline (U5).**
   - Every spoken line has its screen: Demo Brief v1.1 ledger L1–L16, the word-on-action frames in Recording Brief v1.2 row 15.
   - No external statistic.
   - The Site Health line is scoped to "the plugin's own usage counters", matching the on-screen text.
5. **Product facts.**
   - Menu labels as on screen: Plugins → Add Plugin (WordPress 7.1.3), Customer History → Search Analytics, Tools → Site Health → Info.
   - Free tier: Search Analytics exists only on Free; Site Health reads "Active tier: Free".
   - "Free shows the last 90 days; Pro shows your full history": FREE-A, FREE-D.
6. **Tier and CTA (U7).**
   - One CTA in the video ("Install it free"); one button on the page (the trial), with WordPress.org as plain text.
   - Trial terms only in changeable text (the description, the pinned comment, the page microcopy); confirmed 30 days, no card (V1 Q4).
   - No price.
7. **Format (U8).**
   - 1:57: inside 60–120 s and under 2:00, so "2-minute demo" holds.
   - Five beats, product name in the first frame and on the end card, no greeting.
   - The cut is 27 s (never under 11 s).
   - −14.0 LUFS; the cut −13.8 LUFS.
8. **The row's own guardrails** (Video Topic Filter §4, V8).
   - **One take:** never cut; the clock is visible through the whole install; the speed-ups are badged.
   - **The shopper:** a logged-out browser.
   - **Order:** no plugin page opened before the shopper (the Freemius redirect's Dashboard aside: its widgets are not revisited).
   - **No time promise** in the title.
   - **Banned wording:** no "GDPR", "cookie-less", "anonymised" or "consent-managed".

**Noted, not findings:**
- **"Untested with your version of WordPress"** shows on the install card. Owner decision, 7 Oct: the video runs the latest WordPress whatever the readme says; the narration makes no compatibility claim. Dev request 1 stays open.
- **The expanded Tools menu** lists Export / Erase Personal Data in the Site Health clip: menu text, never highlighted or narrated.
- **The demo's own orders** carry today's date with times later than the recording. The "Demo store · sample data" label is on screen throughout.

---

## The corrected assets

**The narration and captions: unchanged.** Every sentence passed. The text, as spoken and captioned, is `recordings/transcript.txt` (Script v1.2 = `voice-over.md` = `captions.srt`, 200 words).

**YouTube description** (Script v1.2 §B), in full:

> It stays in your own WordPress database: no account, no API key. One-take install, then a shopper's search showing up in wp-admin → https://puredevs.com/questions/where-your-woocommerce-customer-data-is-stored/?utm_source=youtube&utm_medium=video&utm_campaign=proof2026_no_saas
>
> Install it free from WordPress.org: https://wordpress.org/plugins/puredevs-customer-history-for-woocommerce/
>
> The install: Plugins → Add Plugin → search "PureDevs Customer History" → Install Now → Activate. The one prompt is an optional opt-in from Freemius, our licensing provider; we skip it. No account, no API key, no setup wizard.
> A shopper who isn't logged in searches the store and opens two products. Customer History → Search Analytics lists the search and the products, inside wp-admin; your own admin browsing isn't recorded.
> Free shows the last 90 days; Pro shows your full history. Recorded on WordPress 7.1.3 and WooCommerce 11.1.2, on a demo store with sample data.
>
> 00:00 Where your store data goes — the install, in one take
> 00:47 A shopper's search, in your own wp-admin
> 01:26 The decision it gives you, and Free vs Pro
>
> Try Pro free for 30 days, no card needed: https://puredevs.com/pricing?utm_source=youtube&utm_medium=video&utm_campaign=proof2026_no_saas

Until the question page is live, line 1 links to `/pricing` with the same UTM (hand-off note §5).

**AG3 ad copy** (Placement Kit §8): headline "Data Stays In Your Database" (27/30) in place of "Your Data Stays On Your Site"; description "Customer analytics that runs inside WordPress. Your store data stays in your own database." (90/90) in place of Description 2.

---

## Verdict

**PASS.** The master, the cut, the poster, the screenshots and the corrected YouTube package may be published.

**Needs Yeasir:**
- the AG3 copy swap in Google Ads (finding 4);
- whether V1's question page takes the corrected privacy line (finding 5).

**On publish day:**
- re-check that WordPress, WooCommerce and the plugin are still at the versions above;
- fill the YouTube ID, thumbnail URL and upload date in the JSON-LD.
