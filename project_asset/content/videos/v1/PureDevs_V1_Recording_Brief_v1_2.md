# V1 Recording & Edit Brief — How much of my WooCommerce revenue comes from returning customers?

**Version 1.2 · 21 Sep 2026** · Demo Video Prompt Pack v3, FILE 4 · supersedes v1.1
From `PureDevs_V1_Script_v1_2.md` and `PureDevs_V1_Demo_Brief_v1_2.md`. Screens confirmed against the screenshots in `MARKETING-BRIEF-3.1.2-part1.pdf` and `-part2.pdf`, found through `MARKETING-BRIEF-3.1.2-PDF-INDEX.md`.

> **STATUS: CONFIRMED.** Ready for the editor once `[DEV SETUP]` is done and the two placeholders are filled in: the site addresses and the editor's logins.

---

## For Yeasir — the editor can skip this part

### Screens confirmed

| Script screen | Screenshot | PDF, page | What it confirms |
|---|---|---|---|
| Menu and tab bar (scene 02) | NAV-01 | Part 2, p. 68 | "Customer History" in the left menu; tab bar Dashboard · Customers & Users · Order History · Revenue · Segments · Sessions · Statistics · Settings |
| Dashboard, top to "Revenue Analytics" (scene 02) | DASH-00, section 1 | Part 1, p. 8 | Four rows of small boxes above; "Revenue Analytics" is the first wide box below them |
| Below the three boxes (home position) | DASH-00, section 2 | Part 1, p. 9 | "Retention · 30-day window", with a Churn rate card, sits directly under the three boxes |
| Answer frame (scenes 01, 03–07) | DASH-10 | Part 1, p. 22 | Title left, date box centre, badge right, four boxes, "Revenue Over Time" with Daily / Weekly / Monthly, then "Revenue by Customer Type" with three boxes, each an amount plus an "orders" line |
| Data-integrity badge (in frame only) | Visible on DASH-10 | Part 1, p. 22 | Reads "Data Verified ✓". DASH-10b has no image of its own because its panel opens only on a mismatch |
| Repeat Purchase Rate (scene 08) | FREE-08 (Free) · REV-01 (Pro) | Part 1, p. 69 · p. 68 | Title "Revenue", From / To / Apply, five boxes; the last reads "Repeat Purchase Rate" with "Registered customers only" |

**Scene 02 starts on the WordPress home screen.** That's standard WordPress, so the Brief has no picture of it and none is needed. The editor only needs the left menu, which NAV-01 shows.

### Differences between the screenshots and recording day

1. **Width.** The Brief captured at 1440 px wide; recording is 1920 × 1080, so boxes look wider. Every scene is described by structure, so it holds at either width.
2. **Callouts.** The screenshots carry red boxes and numbered circles drawn by the capture script. They're not on the real screen.
3. **The trial notice in FREE-08.** The Brief's Free shots were taken on the Pro build with no licence. That build shows "Start your free 30-day trial…"; the WordPress.org free version never shows that notice.
4. **Update badges.** DASH-00, NAV-01 and FREE-08 show a red bubble on **Plugins** and an update counter in the top bar. The real screen must have neither.
5. **Values.** All names, amounts and dates are synthetic and will differ on recording day.

### Still needed from you

1. The Pro site address, the Free site address and the editor's demo logins. Placeholders `[PRO SITE]`, `[FREE SITE]`.
2. The recording date, so dev can check the 30-day window (`[DEV SETUP]` #2).

No new screenshots needed.

### `[DEV SETUP — before recording day]`

The editor does none of this.

1. **Pro site.** Customer History 3.1.2 with an active licence or trial, active at least a day before. If dev instead forces the Pro tier with the Brief's capture override (`pdwchs-capture-tier.mu-plugin.php`), check that no trial notice appears, and delete the override afterwards.
2. **Data window.** The Dashboard box shows the 30 days before recording day, so the window moves with the calendar. That window needs:
   - completed orders on most days from new, returning and guest buyers;
   - one box clearly largest — target Returning Customers;
   - Guest Orders not the largest, and no box at zero.
   On the Free site, the same window needs registered customers with two or more orders, so Repeat Purchase Rate isn't 0 %, plus orders in the 30 days before that. The Brief's seed script (`docs/marketing/tooling/seed-demo-data.php`) can refresh a store; confirm its order dates reach recording day.
3. **Cache.** The boxes are cached for 30 minutes. Finish any seeding at least 30 minutes before recording.
4. **Free site = the WordPress.org 3.1.2 build.** It has no plugin trial notice. Freemius's own trial offer can still appear 24 hours after install; dismiss it while logged in as the editor's account. If you use one site with the capture override instead, the "30-day" notice from FREE-08 appears there; dismiss it the same way. Either dismissal lasts per user and per plugin version, so repeat it after any update.
5. **Badges.** Clear every update (plugins, themes, WordPress) and every admin notice on both sites. No red bubbles anywhere.
6. **Health check.** The badge beside "Revenue Analytics" reads "Data Verified ✓", and no box reads "Loading…" or "Failed to load…".

---

## For the editor

Hi! You're recording one WordPress screen on one site and one small box on a second site. You only click what this sheet names. If anything looks different, stop and tell Yeasir. Never fix it yourself.

**Reference pictures.** Yeasir will send three pictures from the Feature Reference: **NAV-01**, **DASH-10** and **FREE-08**. Things that will differ on your screen, and that's fine:
- the numbers, names and dates;
- the width (your screen is wider);
- the red boxes and numbered circles — they're labels drawn on the pictures, not on the real screen.

One thing must differ: FREE-08 shows a blue notice box at the top ("Start your free 30-day trial…"). Your screen must **not** show it.

### A) One-time setup checklist

- [ ] Log in with the demo account Yeasir gives you, never a personal or developer account.
- [ ] Browser window 1920 × 1080, zoom 100 %, bookmarks bar hidden, all other tabs closed, computer notifications off.
- [ ] Cursor highlight on in your screen recorder.
- [ ] Check the dark menu on the left and the black bar across the top. **No red bubbles with numbers and no coloured notice boxes.** If you see any, stop and report. Don't click them.

### Route P — Pro site, to the "home position"

1. Open `[PRO SITE]/wp-admin` and log in. You land on the WordPress home screen.
2. In the dark menu on the left, click **Customer History**. Careful: not "Dashboard" at the very top of that menu — that's WordPress's own page.
3. The page has a row of tabs across the top: Dashboard · Customers & Users · Order History · Revenue · Segments · Sessions · Statistics · Settings. The first tab is highlighted and the big title says **Dashboard** (like NAV-01).
4. Wait until every box on the page has finished loading.
5. Scroll down slowly with the mouse wheel, once, in one direction. Pass the small boxes until you reach the wide box titled **Revenue Analytics** (like DASH-10).
6. **Stop when the three boxes NEW CUSTOMERS, RETURNING CUSTOMERS and GUEST ORDERS sit fully visible near the bottom of the screen.** The title "Revenue Analytics" and the small date box should still be on screen above them. The words "Retention" and "Churn rate" must **not** be on screen.
7. Wait until all three boxes show a big amount and a small "orders" line underneath.

This is the **home position**. Scenes 01 and 03–07 all start here.

### Route F — Free site, to the Revenue page

1. Log in to `[FREE SITE]/wp-admin` with its demo account.
2. In the address bar, type `[FREE SITE]` followed by exactly this, then press Enter: `/wp-admin/admin.php?page=pd-woo-revenue`
   This skips the Dashboard on purpose.
3. The big title says **Revenue** (like FREE-08, but with no blue notice at the top). Near the top is a row of five boxes; the last is **Repeat Purchase Rate**, with small grey text "Registered customers only".
4. Don't touch the date boxes or the blue **Apply** button. The page is already filled in.

### B) Scene table (script order)

| Scene # | Beat | Site | Screen, in plain words | How to get there | What to do | Hold | Must be visible before you stop | Stop and report if… |
|---|---|---|---|---|---|---|---|---|
| **01 ★** | 1 (reused in 5) | Pro | **Answer frame:** the three boxes New / Returning / Guest | Route P to the home position, before you press record | Park the cursor in empty grey space away from the boxes. Don't move | **12 s** | All three boxes show an amount and an orders line; nothing loading; "Retention" not on screen | See the list below the table |
| 02 | 2 | Pro | From the WordPress home screen to the three boxes | Press record on the WordPress home screen (Route P step 1 done) | Do Route P steps 2–7 on camera: click **Customer History**, wait, one slow scroll, stop at the home position | 5 s after stopping | Home position reached | You scrolled too far and "Retention" shows → start the scene again |
| 03 | 2 | Pro | The title "Revenue Analytics" | Home position | Move the cursor slowly to the words **Revenue Analytics** and rest it just to their right | 6 s | Title and the three boxes | — |
| 04 | 3 | Pro | The three boxes, one by one | Home position | Move slowly left to right. Rest just **under the label** of NEW CUSTOMERS (2 s), then RETURNING CUSTOMERS (2 s), then GUEST ORDERS (2 s). Then park the cursor below the boxes | 6 s + 10 s | All three amounts readable; the cursor never covers an amount | — |
| 05 | 3 | Pro | The date box, then back to the boxes | Home position | Move the cursor to the small date box at the top of the Revenue Analytics area (two dates and a calendar icon). Rest **beside** it — **don't click**. Then move slowly down and rest below the three boxes | 6 s + 12 s | The date box, then the boxes | The date box opens because it was clicked → start the scene again |
| 06 | 3 and 4 | Pro | **The biggest box** | Home position | Find the box with the largest big amount. Rest the cursor just under that box's label, never over the number | **20 s** | That box readable | — |
| 07 | 3 and 4 | Pro | The Guest Orders box | Home position | Rest the cursor just under the GUEST ORDERS label | **15 s** | That box readable | — |
| 08 | 5 | **Free (WordPress.org build)** | The "Repeat Purchase Rate" box | Route F, before you press record | Move the cursor slowly to the **Repeat Purchase Rate** box and rest it below "Registered customers only" | 6 s | Label, the value and "Registered customers only" readable; no notice box at the top | A blue notice box appears at the top — don't click it. Or the box reads 0 |

**Stop and report on any Pro scene if:**
- "Loading revenue data…" doesn't finish within 30 s, or you see "Failed to load revenue data";
- any of the three boxes reads zero;
- **Guest Orders shows the largest amount**;
- the two largest amounts are too close to tell which is bigger at a glance;
- the badge near "Revenue Analytics" says "Data Mismatch" instead of "Data Verified";
- the layout differs from the reference pictures — different numbers, dates and width are fine.

**No storefront scenes in V1**, so there is nothing to record in a private window.

### C) Recording rules

- One clip per scene, named **scene-01 … scene-08**, matching the table. Start 3 s before the first action; stop 3 s after the last.
- Scroll slowly, once, in one direction. Click nothing the table doesn't name. Never open Settings, the Plugins screen, or any other tab.
- The dashboard has more boxes below "Revenue Analytics" (Retention, Lifetime value, Customer Segments, Health, Churn and others). **Never scroll down to them** and never stop or zoom on them.
- Hold still at least 5 s on every box you're showing. The voice-over never reads numbers out, so viewers need time to read them.

### D) Edit notes

The voice-over is recorded after the footage. Yeasir picks the spoken options from your delivery note, so match captions to the lines he records.

| Script beat · time | Use | Zoom |
|---|---|---|
| 1 · 0:00–0:05 | scene-01 | Crop to the heading "Revenue by Customer Type" plus the three boxes; the four top boxes and the "Data Verified" badge are out. Lower third: **PureDevs Customer History** |
| 2 · 0:05–0:12 | scene-02 | Follow the click on Customer History, then the scroll. Trim load waits |
| 2 · 0:12–0:20 | scene-03 | The "Revenue Analytics" title |
| 3 · 0:20–0:35 | scene-04 | The three boxes, left to right |
| 3 · 0:35–0:54 | scene-05 | The date box, then pan down to the three boxes |
| 3 · 0:54–1:06 | scene-06, then scene-07 | The biggest box (hold at least 5 s), then the guest box |
| 4 · 1:06–1:18 | scene-07 | The guest box |
| 4 · 1:18–1:33 | scene-06 | The biggest box. A thin brand-colour outline is fine; no arrows |
| 5 · 1:33–1:39 | scene-01 | Zoom out to all three boxes |
| 5 · 1:39–1:42 | scene-08 | The "Repeat Purchase Rate" box and its caption |
| 5 · 1:42–1:46 | End card | Still from scene-01, cropped as in beat 1 + **PureDevs Customer History** + **Start the trial · puredevs.com/pricing** |

- Burned-in captions from the approved script, with the **caption emphasis** words in bold. Only the options Yeasir actually speaks.
- No intro longer than 2 s. One CTA, on the end card only.
- **Poster and thumbnail:** a still from scene-01, built to Script v1.2 §C. Key points: 1280 × 720, under 2 MB, the question as text, a thin outline on the biggest box, a small "Demo store" label, no arrows.
- Export the master at 1080p. Cut the derivatives in the placement kit (FILE 5, coming next).

### E) Delivery

One folder named **V1** containing:
- scene-01 … scene-08;
- the answer-frame still as PNG (full frame, plus the crop from beat 1);
- a short note with:
  1. **Which box shows the largest amount:** New Customers or Returning Customers.
  2. **Where the Guest Orders box sits:** the smallest of the three, or bigger than New or Returning (say which).
  3. The date and time you recorded.
  4. Any scene you couldn't record, and why, plus anything that made you stop and report.

Don't write the amounts in the note, just which box is bigger.

---

## F) Sanity check

| # | Check | Result |
|---|---|---|
| 1 | Every scene maps to a script line and a Brief entry | 01 → beats 1, 5a and end card (DASH-10) · 02 → beat 2a (A1, §3.1, NAV-01) · 03 → beat 2b (DASH-10) · 04 → beat 3a (DASH-10) · 05 → beat 3b (DASH-10 ②) · 06 → beats 3c and 4b · 07 → beats 3c and 4a · 08 → beat 5b (REV-02). **PASS** |
| 2 | Every path comes from Brief §3, not memory | Customer History → Dashboard (`pd-woo-dashboard`, §3.1) · Revenue (`pd-woo-revenue`, §3.1) · tab bar (§3.3, NAV-01). **PASS** |
| 3 | Every screen is confirmed by a screenshot | NAV-01, DASH-00 §1–2, DASH-10, FREE-08 / REV-01 (see "Screens confirmed"). The WordPress home screen is standard WordPress. **PASS** (v1.0 wrongly treated these as unconfirmed) |
| 4 | No scene shows a future feature | Only DASH-10 and REV-02, both shipped in 3.1.2. **PASS** |
| 5 | No expected values written anywhere | Scenes are described by structure only. No amount, count, date or percentage from the screenshots is copied into a scene. **PASS** |
| 6 | Demo values will differ on recording day | Nothing depends on a figure. The editor reports which box is biggest and where Guest sits, so Yeasir picks script options A/B and C/D. The moving 30-day window is `[DEV SETUP]` #2. **Handled** |
| 7 | Callouts on the screenshots could confuse the editor | Explained under "Reference pictures". **Handled** |
| 8 | Capture width 1440 px vs recording 1920 px | Positions are structural ("boxes near the bottom, Retention off screen"). **Handled** |
| 9 | Trial notice | Corrected from v1.0: it came from the Pro build with no licence (Brief §F); the WordPress.org build doesn't show it. Freemius's own trial offer can still appear → `[DEV SETUP]` #4 plus a stop trigger. **Handled** |
| 10 | Update badges | Present in DASH-00, NAV-01 and FREE-08 → `[DEV SETUP]` #5 plus a checklist item. **Handled** |
| 11 | ⚑ widgets and Retention | DASH-00 §2 shows Retention (Churn rate card) directly below the boxes. The home position keeps it off screen; never scroll further. **PASS** |
| 12 | Apply clicks | Neither screen needs Apply: DASH-10 loads by itself and the Revenue page opens filled in. **PASS** |
| 13 | 30-minute cache; no storefront scenes | `[DEV SETUP]` #3. **PASS** |
| 14 | Answer frame ≥ 8 s; every box hold ≥ 5 s | Scene 01 is 12 s; every other hold is 5 s or more. **PASS** |
| 15 | Beat 5 insert on the Free build | Scene 08, WordPress.org build. **PASS** |
| 16 | Product name in the first frame and end card; one CTA | Edit notes. **PASS** |
| 17 | Guest Orders largest, or a near tie | Stop-and-report triggers, because the script has no option for either. **Handled** |
| 18 | WordPress "Dashboard" vs the plugin's "Dashboard"; the cursor over numbers | Route P step 2 warns; every rest point is "under the label". **Handled** |
| 19 | Upstream files | Demo Brief v1.1 Q4 and Script v1.1 sanity row 21 say the trial notice "sits on the Free build". Imprecise per #9; the instructions they give are unaffected. | Correct at their next revision |

**Verdict:** **PASS.** Ready for the editor once `[DEV SETUP]` is done and `[PRO SITE]`, `[FREE SITE]` and the logins are filled in.

---

## Changelog

- **v1.2 — 21 Sep 2026.** Follows Script v1.2 (about 1:46): edit-note times moved; scene 04 and 05 holds lengthened to cover the longer beat 3. Trial terms are now confirmed, but dismissing trial notices stays, for a clean screen.
- **v1.1 — 21 Sep 2026.** Screens confirmed against the Brief's own screenshots (NAV-01, DASH-00, DASH-10, FREE-08, REV-01) via the PDF index; provisional status removed. Trial-notice setup corrected (Brief §F: the Free shots came from the Pro build with no licence). Capture-width and callout notes added for the editor. "Two sites or one" answered: Free scene on the WordPress.org build. New screenshots no longer requested.
- **v1.0 — 21 Sep 2026.** First version; provisional because the Brief's screenshots were wrongly treated as unconfirmed.
