# PureDevs — Ad Launch Pre-Requisites Checklist

**Version:** v1.3 · **Date:** Thu 24 Sep 2026 · **Owner:** Yeasir
**QA gate:** Tue 29 Sep · **Launch:** Thu 1 Oct, 18:00 Dhaka · **Hard deadline:** Sat 3 Oct · next slot Tue 6 Oct
**Scope:** Google Search only (`category` AG1 · `alternatives` AG3 · Brand AG4). AG2 and retargeting come after launch.
**Governed by:** `PureDevs_Proof_Marketing_Ad_Strategy_v1.md` · videos from `PureDevs_Video_Topic_Filter_v4.1.md` · claims from the 3.1.2 Feature Reference §D.
**Guides:** B v1.2 · C v1.2 · D v1.4 · F v1 · G v1.1 · H v1 · I v1.1 · J v1 · Lifecycle Emails v1.4 · Video hand-off note v1.1.
**Legend:** Y = Yeasir (2 h/day) · DEV · VA · **M** blocks launch · **S** in the window, does not block · **L** after launch · ✔ column: mark done / partial / not started and send me the numbers.
**Names:** V1 `returning_revenue` · V8 `no_saas` · V13 `zero_result_searches`. Names are also the `video_id` in tracking. Question pages: `/questions/<slug>/`.
**Screenshots, wherever they appear below:** take them from the 3.1.2 brief PDFs first (§F screenshot index, part 2 p.78, filed by entry ID such as `DASH-10.png`); retake only where a filter must be applied or an RFM / health widget is in frame.
**Anything due before today and not done: do it Fri 25 Sep. The QA gate does not move.**

| # | Item | Owner | Pri | Due | ✔ |
|---|---|---|---|---|---|
| | **A. Decisions** | | | | |
| A1–A5 | ✔ Decided 19–21 Sep: nothing recorded, videos in v4.1 order · never lead with RFM / churn / health / tags / ready-made segments · trial first, Founding 50 as the close · multi-site tiers stay · $10/day, $450 cap to 15 Nov, six countries | Y | — | done | ✔ |
| A6 | v4.1 §2 amendments: V2 → V1 merge signed off 21 Sep. Confirm the rest (drop V6 and V9, fold V10 and V11 into V8, V5 later, V12 → V15, add V13–V18) | Y | M | 25 Sep | |
| A7 | ✔ No 100-customer cap (21 Sep, reconfirmed 24 Sep). Removed from pages, emails and the readme edits. Closed | Y | — | done | ✔ |
| A8 | ✔ Trial terms confirmed 21 Sep: 30 days, no card. Unlocks the ★ lines in F | Y | — | done | ✔ |
| | **B. Tracking and consent** | | | | |
| B1 | New Google Ads account under yeasir@puredevs.com, Expert mode, billing, advertiser verification started; pervej86@ as backup Admin; old accounts left alone | Y | M | 21 Sep | |
| B2 | GTM + GA4 on puredevs.com, internal-traffic flag by cookie | DEV | M | 23 Sep | |
| B3 | Privacy Policy + Terms pages and footer links (moved up for H) · CookieYes web app + GTM template, denied by default in UK / EEA only · video and checkout must work without consent | DEV | M | 22 Sep pages · 25 Sep banner | |
| B4 | `trial_start`, `purchase`, `checkout_open`, `video_play` → GA4 · `trial_start` the only Primary conversion in Ads · GA4 ↔ Ads linked | DEV + Y | M | 25 Sep | |
| B5 | Search Console + sitemap · UTM sheet · Clarity | VA | S | 25 Sep | |
| | **C. Freemius and checkout** | | | | |
| C1 | Overlay checkout via button classes (`pd-trial`, `pd-buy-1`, `pd-buy-5`, `pd-buy-unlimited`, `pd-founding`); events from `purchaseCompleted` | DEV | M | 25 Sep | |
| C2 | Trial button on /pricing and both landing pages · `/trial-started/` page (S) | DEV | M | 25 Sep | |
| C3 | Dashboard check: trial ✔ · prices · FOUNDING50 lock and counter · refund policy · Freemius emails · keys and sandbox token to DEV · DEV added to the Freemius team | Y | M | 21 Sep | |
| C4 | **Audit and extend** the existing webhook at webhook.puredevs.com: signature check · add `user.trial.started` (website trials) · new *Uninstalled* list · attributes `TRIAL_START`, `TRIAL_SOURCE`, `FS_MARKETING` | DEV | M | 28 Sep | |
| | **D. Landing pages and site — VA assembles, DEV builds the kit, Y approves** | | | | |
| D0 | Build kit: Landing template + 6 block patterns incl. click-to-load video | DEV | M | 25 Sep | |
| D1 | LP-Category `/lp/customer-analytics/` (noindex) · hero V1 or still image · one CTA = trial, opens on the page | VA + Y | M | 28 Sep | |
| D2 | LP-Alternatives `/compare/self-hosted-vs-saas/` · hero V8 or table-first · models only, no competitor prices | VA + Y | M | 28 Sep | |
| D3 | Pricing: new title, meta, H1 · V1 above the plans · trial button · two multi-site buttons · bullets reordered · title/meta free of churn and RFM | VA + DEV + Y | M | 28 Sep | |
| D4 | Question pages under `/questions/<slug>/`, live the day each video publishes. V1 slug fixed: `woocommerce-new-vs-returning-customers` · `VideoObject` + `SoftwareApplication` schema | DEV + VA | S | 4 Oct | |
| D5 | Homepage hero, three old compare pages, remaining churn-led copy, demo-store link | Y | L | — | |
| | **E. Videos and YouTube — format and briefs per v4.1** | | | | |
| E0 | Demo store on 3.1.2, seeded so new, returning and guest orders appear on most of the last 30 days | VA + DEV | M | 21 Sep | |
| E1 | **V1**: script → record → VA edit → final. If it slips past 28 Sep, launch with the still image | Y + VA | M | 25 Sep | |
| E2 | Publish V1: title = the question · line 1 = answer + /pricing link with UTMs · chapters · pinned trial link · hand back YouTube ID, poster, 3 screenshots, transcript | VA | M | 26 Sep | |
| E3 | **V8** recorded in one take on the free WordPress.org build, published | Y + VA | S | 29 Sep | |
| E4 | **V13** after the DEV's search-plugin test (FiboSearch, Advanced Woo Search, Woo Product Search block) · per video: 20–30 s cut, 3–5 screenshots, GIF under 2 MB, transcript | Y + DEV + VA | S | 4 Oct | |
| | **F. Google Ads build — everything stays paused until J3** | | | | |
| F0 | Account: auto-apply recommendations off · dynamic sitelinks, callouts and snippets off · three cost rules ($150 and $300 email, pause at $445) | VA | M | 25 Sep | |
| F1 | Two campaigns: Non-brand $9/day (cap $2.50) and Brand $1/day (cap $1.00) · exact and phrase only · partners and Display off · Presence · six countries · AI Max, broad match and auto-created assets off · end 15 Nov | VA | M | 25 Sep | |
| F2 | One RSA per ad group with the pinned headline · ★ trial-terms lines now allowed · demo lines only when V1 is on the page · negatives · sitelinks and callouts · no competitor name or price in ad text · **created by 28 Sep so Google reviews them while paused** | VA + Y | M | 28 Sep | |
| F3 | GA4 audiences (`pricing_no_trial_30d`, `lp_no_trial_30d`, `trial_no_purchase_60d`, `video_players_30d`) as Observation | VA | S | 28 Sep | |
| F4 | AG2 first ad group when the V1 page is live · Demand Gen at ~300 users · +$5/day steps only after a gate passes | VA | L | — | |
| | **G. Brevo** | | | | |
| G4 | Test the live Playbook form; if the PDF does not arrive, build the one-step delivery automation today | VA | M | 21 Sep | |
| G1 | Domain Authenticated · DMARC record · sender "Yeasir" <yeasir@puredevs.com> · mail-tester 9+ · inbox on Gmail and Outlook | DEV + VA | M | 23 Sep | |
| G2 | ✔ Decided in the G guide: Trial 5 lapse variant · sender Yeasir · trial sequence to every trial user · newsletter to consented only · emails may link to question pages · Playbook drip unchanged · Freemius reminder off at go-live | Y | — | done | ✔ |
| G3 | Automation A (trial sequence) live from Lifecycle Emails v1.4, exit guard before every email, entry = added to *Trial — Active* · **launch minimum: Trial 1 final**; Trial 2 by 2 Oct, Trial 3 by 15 Oct, Trial 4 by 23 Oct, Trial 5 by 29 Oct · one-minute-wait test passed | VA + Y | M | 28 Sep | |
| G5 | Automation D (Welcome) · Playbook drip 2–6 · newsletter segment · Automation B (Pro cleanup) | VA | S | 2 Oct | |
| | **H. Capterra and G2 — not a launch gate; approval is the long pole** | | | | |
| H1 | Capterra submitted with the H-guide copy, form answers and feature ticks; category = where Metorik and Metrilo sit | VA | S | 23 Sep | |
| H2 | G2 profile submitted with the same assets; company details, logo and LinkedIn page from Y | VA + Y | S | 24 Sep | |
| H3 | Listing screenshots (six of the eight in I2) · V13 added to both listings when published | VA | S | 25 Sep | |
| H4 | Reviews: target 5 Capterra by 15 Nov (3 realistic) · Pro users → Capterra, free users → WordPress.org · never with an offer · 1:1 from yeasir@ · add the person to *Suppress — Personal* that week | Y | L | — | |
| | **I. WordPress.org — no plugin release needed this week** | | | | |
| I1 | Readme in trunk **and** tags/3.1.2: five tags · title "– Customer Analytics" · new short description · stale 3.0.0 opener replaced · data wording · **External services** section (Freemius) · performance answer · Pro section regrouped, scoring last · pricing link with UTMs · validator clean | DEV + Y | S | 2 Oct | |
| I2 | Eight captioned screenshots, free screens first; delete screenshots 9–12; check the 2021 banner text | VA | S | 25 Sep | |
| I3 | V13 URL on its own line near the top of the readme; walkthrough moves to the FAQ | DEV | S | week 2 | |
| I4 | Reviews: 3 WordPress.org by 15 Nov · one dismissible in-plugin prompt after 14 days in the next release · outbound plugin links `utm_source=plugin` | Y + DEV | L | — | |
| | **J. QA and launch** | | | | |
| J0 | Readiness snapshot: fill this ✔ column, apply the go / fallback / slip rules in the J guide | Y | M | 25 Sep | |
| J1 | Baseline table (Freemius, wp.org, Brevo) · real trial→paid rate → allowable cost per trial (formula in J1) | VA | M | 25 Sep | |
| J2 | QA run in dependency order: tracking → consent → pages → checkout → real end-to-end trial → Ads → listings → publish GTM and freeze | DEV + Y + VA | M | 29 Sep | |
| J3 | Launch runbook: 18:00 Dhaka · Brand first, then Non-brand · policy check at +1 h · pause triggers · daily log | Y | M | 1 Oct | |
| J4 | Daily 15 min · Monday scorecard (strategy §9) · gates: G3a ~17 Oct, G3b ~3 Nov, G3c at 15 Nov · verdict Mon 16 Nov | Y + VA | — | ongoing | |

**Slip order if behind (never J2, E1 or the launch date):** I → H → D4 → E4 → E3 → G5 → F3 → B5.

**Due Fri 25 Sep:** Y: A6 confirm, J0, V1 final, F2 review prep · VA: J1, I2/H3 screenshots, F0–F1 · DEV: D0 kit, B3 banner, C1–C2 sign-off.

## Changelog
- **v1.3 — 24 Sep 2026.** A7 closed on Yeasir's instruction; I0 (cap check) removed. Nothing else changed.
- **v1.2 — 24 Sep 2026.** All section guides folded in. ✔ column added and today's date set. A7 marked decided with the verification step kept; A8 records the confirmed trial terms, which unlock the ★ ad lines. B3 now includes the privacy and terms pages moved up for H. C4 is audit-and-extend, not a new build. D4 and the emails use the approved `/questions/<slug>/` pattern. F split into F0–F4 per the F guide, with ads created by 28 Sep for policy review. G reordered (G4 first) with the launch minimum for the trial sequence. H and I rows rebuilt from their guides, including review routing by user type. J0–J4 replace the old J rows. Screenshot sourcing from the brief PDFs added.
- **v1.1 — 21 Sep 2026.** A1 reversed (nothing recorded); v4.1 video order; C3 moved up; D rows per the D guide.
- **v1.0 — 19 Sep 2026.** First version.
