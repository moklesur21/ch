# PureDevs — Section J Guide: QA and Launch

**Version:** v1.0 · **Date:** 24 Sep 2026 · **Owner:** Yeasir
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.2.md` (items J0–J4)
**Governed by:** strategy §9 (scorecard) and §10 (gates) · running rules from the Section F guide
**Time:** Y about 3 h between Fri 25 Sep and Thu 1 Oct, then 15 min a day · DEV 3 h · VA 3 h

| Day | What happens |
|---|---|
| Fri 25 Sep | J0 snapshot · J1 baseline · V1 final · last M builds |
| Sat 26 – Sun 27 | Fixes · V1 published |
| Mon 28 | Ads and assets created (Google reviews them while paused) · D1–D3 final · Automation A built |
| **Tue 29** | **J2 QA run** |
| Wed 30 | Fix, re-test only what failed, publish GTM, freeze the site |
| **Thu 1 Oct** | **J3 launch, 18:00 Dhaka** |
| Sat 3 Oct | Hard deadline. Missed → Tue 6 Oct (launch Tue–Thu so the first 48 h fall on working days) |

---

## J0. Readiness snapshot — Y · 20 min · Fri 25 Sep

Fill the ✔ column of checklist v1.2 and send me the numbers that are done. Then apply these rules.

**Launch on 1 Oct if all eight are true on Tue 29 Sep**
1. Ads account active, payment method accepted, no verification hold that stops serving.
2. GTM published; GA4 shows `page_view` from a real visit; internal filter Active.
3. A test trial from the landing page pushes `trial_start` once; it is a GA4 key event; both conversion actions exist in Ads.
4. Every buy and trial button opens the overlay; the plain links still work with JavaScript off.
5. D1 and /pricing live, V1 poster (or its still image) and one trial button above the fold on a phone.
6. Automation A live with **Trial 1 final**; Freemius trial-started email on.
7. Privacy Policy and Terms linked in the footer.
8. Both campaigns built and paused; every ad shows **Eligible**.

**Fallbacks that keep 1 Oct (use them; do not slip for them)**
- V1 not published → the answer frame as a still image on D1 and /pricing.
- V8 not published → D2 runs table-first.
- D2 not live at all → launch `category` and Brand only; enable `alternatives` the day D2 is live.
- Consent banner fails the UK test → remove United Kingdom and Ireland from both campaigns until it passes. The site still needs the banner for its own visitors; this only keeps ads out of it. Not legal advice.
- Trial 2–5 not final → fine. They do not send until Day 2 / 15 / 23 / 29. Finalise each before its first send: **Trial 2 by Fri 2 Oct · Trial 3 by Thu 15 Oct · Trial 4 by Fri 23 Oct · Trial 5 by Thu 29 Oct.**
- Capterra, G2, the readme, question pages, audiences → no effect on launch.

**Slip to 6 Oct if any of these is false on Wed 30 Sep**
- `trial_start` cannot be seen end to end (page → GA4 → Ads action).
- The trial overlay fails on iPhone Safari or Android Chrome.
- The Ads account is under a verification hold.
- D1 takes more than about 3 s to load on a phone.

A launch without measurement is spend without a test. That is the one thing the date never overrides.

---

## J1. Baseline — VA · 1 h · Fri 25 Sep (was 21 Sep)

Last 28 days, and the 28 before that if the report allows it. One table, kept in the scorecard sheet.

| Metric | Where |
|---|---|
| Downloads per day · active installs | wordpress.org listing → Advanced view |
| Trials started (total; web vs plugin cannot be separated historically) | Freemius Developer Dashboard, trials report *(confirm menu name)* |
| Trial → paid: count and % · sales · net revenue · refunds | Freemius |
| Founding 50: redeemed to date, spots left | Freemius coupon page |
| Renewals: how many year-1 licences renewed | Freemius. If there are none yet, say so |
| Top 5 buyer countries | Freemius customers or payments |
| The four Brevo list sizes | Brevo |
| Site sessions | None yet. GA4 starts on 30 Sep; write that date down |
| Capterra · G2 · YouTube | Not live / zero. Record as zero |

**Allowable cost per trial (replaces the provisional $12 in A5 and the F guide)**
= trial→paid rate × net revenue per paying customer over three years ÷ 3
= rate × ($79 × 0.90 × 3) ÷ 3 = rate × $71.
At 17.5 % that is $12.4; at 10 % it is $7. Use the real rate from Freemius. If J1 shows no renewals yet, keep the three-year figure but write "assumed" next to it. Founding 50 sales use $39 in place of $79.

Exclude the QA test trial (J2 check 5) from every count.

---

## J2. QA run — Tue 29 Sep · DEV 2 h · Y 1 h · VA 1 h · on production

**Setup.** Three browser profiles: **team** (has the `pd_internal` cookie), **clean** (no cookie, private window), and a **phone**. Test address `yeasir+qa01@puredevs.com`. A UK check needs a VPN or a contact in the UK. If DebugView shows nothing from the team profile, that is the internal filter working; use the clean profile for anything that must appear in GA4.

Run in this order. Each line is pass or fail; a fail goes on the fix list for Wed 30.

**1 · Tracking — DEV, team profile, GTM Preview**
- Google tag fires on every page; Tag Assistant shows one `G-` tag only (no gtag.js or Site Kit duplicate).
- GA4 DebugView (clean profile) shows `page_view`; team profile carries `traffic_type = internal`.
- CookieYes tag fires on Consent Initialization; the GA4 event tag fires on a console-pushed `checkout_open`.

**2 · Consent — DEV plus the UK contact**
- UK: banner shows; default Denied; Accept → Granted; Reject → stays Denied.
- US: no default denial.
- Banner untouched: the video plays and the checkout opens.
- Privacy Policy and Terms in both footers; the policy line under each Playbook form.

**3 · Pages — VA, phone first**
- D1, D2, /pricing: poster or still image plus one `pd-trial` button above the fold on iPhone Safari and Android Chrome.
- Poster click plays the video and pushes `video_play` with the right `video_id` and `page_type`.
- Every link and every sitelink URL loads; no 404s; no link to the homepage or the old compare pages from ad pages.
- D1 carries `noindex`; D2 is in the sitemap.
- No RFM, churn, health score or behavioural tag in any hero, H2 or meta; every screenshot and video captioned as demo data.
- PageSpeed Insights, mobile: LCP under 2.5 s on D1 (the poster is the LCP element).
- Rich Results Test: `SoftwareApplication` valid; schema price equals page price.

**4 · Checkout — DEV, then Y repeats on the phone**
- Each class opens the overlay with the right mode: `pd-trial` (trial, no card step) · `pd-buy-1` · `pd-buy-5` · `pd-buy-unlimited` · `pd-founding` (FOUNDING50 applied, spots permitting).
- JavaScript off: every button still links to checkout.freemius.com.
- `checkout_open` fires per click with `cta_location`.
- Sandbox: trial → `trial_start` once (double-click it); purchase → `purchase` once with value, currency and transaction ID.
- `/trial-started/` loads and is `noindex`.
- Sandbox keys removed from production afterwards.

**5 · End to end — Y, clean profile**
- Start a **real** trial from D1 with the test address.
- Expect within minutes: `trial_start` in DebugView · Freemius email with download and key · the contact in *Trial — Active* with `TRIAL_SOURCE = web` · Trial 1 from "Yeasir" in the inbox, not spam · unsubscribe link works.
- Clean up: delete the test user in the Freemius dashboard and the contact in Brevo; note the time so J1 and the scorecard exclude it.

**6 · Google Ads — Y**
- Both campaigns Paused; budgets $9 and $1; end date 15 Nov; six countries; Presence.
- Every ad and asset Eligible. "Under review" past 24 h → wait. Disapproved → fix per the F guide.
- Every keyword Exact or Phrase; the negative list attached; brand negatives on Non-brand.
- Conversion actions: `trial_start` Primary, `purchase` Secondary, neither Inactive. "No recent conversions" is normal.
- The three cost rules enabled. Audiences present in Audience manager, even if "Too small".
- Billing: no red banner.

**7 · Listings — VA (not gates)**
- Demo store login in the readme works. Capterra and G2 submitted. Readme edits committed or dated.

**8 · Publish and freeze — DEV**
- Publish the GTM container, version note "launch 2026-10-01". Back up the site.
- Change freeze from Wed 30 Sep 18:00 to Sat 3 Oct, fixes excepted.

---

## J3. Launch day — Thu 1 Oct

**Timing.** Enable at **18:00 Dhaka** = 08:00 New York = 13:00 London. US morning and UK afternoon start together, and you are awake for the first six hours.

**Wed 30 Sep (T-1):** fixes from J2; re-run only the failed checks; ads Eligible; budgets, end date and rules confirmed; Freemius trial-expiring reminder **off** now that Automation A is live.

**Steps — Y, 30 min**
1. Open D1 from the team profile; GA4 Realtime shows the visit. Tracking is alive.
2. Enable **Brand**. Wait 30 minutes. Check it serves with Ads → Ad Preview and Diagnosis. Never search for and click your own ad.
3. Enable **Non-brand**. Write the time in the log.
4. +1 h: policy status of every ad and asset. Disapproved → pause that ad group, fix, resubmit; the rest keeps running.
5. +6 h: impressions and clicks above zero; spend within reason. Search terms may not show until tomorrow.
6. **Fri 2 Oct morning:** the full day-1 read. Impressions, clicks, CPC, search terms → negatives. Trial starts: GA4 first (Ads lags up to a day). Brevo *Trial — Active* growing?

**Pause both campaigns within the hour if**
- D1, /pricing or the checkout is down.
- `trial_start` stops firing (test it from the team profile).
- The UK banner breaks while the UK is targeted.
- Spend passes twice the daily budget in one day (Google's own ceiling; beyond it something is wrong).
- After 30 clicks, more than 80 % of search terms are off-intent → pause `category` only, rebuild its keywords, keep the rest running.

**Log, one row a day:** date · spend · impressions · clicks · CTR · CPC · trial starts (GA4) · trial starts (Freemius, web) · purchases · notes. This sheet feeds J4.

---

## J4. From 2 Oct to 16 Nov

**Daily, 15 min (Y):** the F guide's routine (search terms → negatives · keyword status · CPC against the cap · no ad edits in week 1 · never accept a recommendation), plus reply to every trial-email reply the same day. Those replies are where reviews and the next video questions come from.

**Monday scorecard — VA fills, Y reads, 20 min.** Strategy §9, one row per week, with baseline and last week beside it:

| Area | Metrics |
|---|---|
| Outcome | Trial starts (total · web · plugin) · trial → paid, rolling 30 days · cost per trial |
| Site | Pricing sessions · video play rate · checkout-open rate |
| WordPress.org | Downloads per day · active installs |
| Search (Non-brand only) | Spend · clicks · CTR · CPC · trial starts · search terms reviewed Y/N |
| Proof | WordPress.org reviews · Capterra reviews · G2 reviews |
| Production | Videos shipped · placements completed per video · email replies received |

Views, likes and subscribers stay off it.

**Gates, read on Non-brand only (at $9/day)**

| Gate | Expected date | Pass | If missed |
|---|---|---|---|
| G3a · $150 | about Sat 17 Oct | CTR ≥ 2 % on `category` plus the two phrase keywords in `alternatives` | Exact match only; rewrite H1–H3 |
| G3b · $300 or 100 clicks | about Tue 3 Nov | ≥ 2 trial starts | Swap D1's first screen and video once; rerun $150 |
| G3c · $450 | Non-brand reaches about $414 by 15 Nov, so read it at the end date | Cost per trial ≤ the J1 allowable | Pause Search; keep every free placement |
| G1 · production | Sun 4 Oct | V1 live, readme video, pricing page updated | One video per fortnight; add no channels |
| G2 · free placements | week of 2 Nov | Pricing video play rate ≥ 10 % of sessions; trial starts per week vs baseline | Play rate low → placement or thumbnail. Plays fine, no lift → re-rank the questions from trial replies |

**Post-launch, by week** (recording per the Video Topic Filter v4.1; pages per the D guide)

| Week | Ship |
|---|---|
| 5–11 Oct | Trial 2 final · V13 recorded and published · readme embed · question pages for V1, V8, V13 · Automation D (Welcome) · Playbook drip emails 2–6 |
| 12–18 Oct | V4 · AG2's first ad group (`returning_revenue`) paused-built, enabled when its page is live · Trial 3 final · G3a read · Capterra live → complete profile |
| 19–25 Oct | V16 (below the plans on /pricing) · Trial 4 final · first review requests to users with 30+ days · D5 homepage rewrite |
| 26 Oct – 1 Nov | V14 · Trial 5 final · G2 read |
| 2–8 Nov | V7 · G3b read · old compare pages rebuilt |
| 9–15 Nov | V3 · Demand Gen retargeting only if an audience passes 300 |
| **Mon 16 Nov** | **Verdict** (strategy §10): scale if trial starts per week are up ≥ 30 % on baseline or Search is inside the allowable cost per trial · free-only if placements lifted trials but paid did not · escalate, don't iterate, if neither moved |

**Your weekly hours (14):** 1.75 h ads and replies · 0.5 h scorecard · 8 h script, record and review one video · 1 h reviews and support · the rest for approvals.

---

## Hand-offs and corrections to other docs

- **D guide → v1.4, emails → v1.4:** question pages now use the approved `/questions/<slug>/` pattern; V1's slug is `woocommerce-new-vs-returning-customers` and never changes.
- **F guide:** the ★ C3 lines (H10, H12, two callouts, one sitelink description) are unlocked; trial terms were confirmed on 21 Sep as 30 days, no card.
- **F guide:** the Non-brand campaign will not reach $450 by 15 Nov at $9/day; G3c is read at the end date.
- **I guide, decision a:** the cap check on a real install is still open and must happen before the readme commit.
- **A5:** the allowable cost per trial comes from J1 on Fri 25 Sep.

## Changelog
- **v1.0 — 24 Sep 2026.** First version. Adds go/no-go rules with fallbacks, the baseline table and cost-per-trial formula, an eight-part QA script in dependency order, a launch-day runbook timed to US and UK mornings with pause triggers, and the operating rhythm with dated gates through the 16 Nov verdict.
