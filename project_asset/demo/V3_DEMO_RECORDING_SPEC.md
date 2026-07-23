# V3 DEMO RECORDING SPEC — For the Developer

**Store:** Northline Supply Co. (demo) · **Plugin version on screen:** 7.0.2 · **Screenshots reviewed:** 9
**Serves:** `V3_SCRIPT_Revenue_Numbers_Dashboard.md` (long-form) + `V3_DERIVED_SHORTS.md` (4 shorts)

> ## READ THIS FIRST
> Three findings from the screenshots change what can be recorded. **Sections A–C must be resolved before the batch session.** Section D is the shot list, and roughly half of it is recordable today.

---

# A. WHAT THE SCREENSHOTS CONFIRM — menu paths, finally

These fill the blank column in `PureDevs_Feature_Reference_Card.md`. Copy them back into that doc.

| Feature | Confirmed menu path | Used in |
|---|---|---|
| **Revenue Analytics** | `Customer History → Revenue` (top tabs: Dashboard · Customers & Users · Order History · **Revenue** · Segments · Sessions · Statistics · Settings) | V3, V4, V12 |
| **Revenue Analytics panel** (second location) | `Customer History → Dashboard` → scroll to "Revenue Analytics" | V3, V12 |
| **"Data Verified ✓" badge** | `Customer History → Dashboard` → Revenue Analytics panel → **top-right corner of that panel** | V3 |
| Churn Prediction | `Customer History → Churn Prediction` (tabs: At-Risk Customers · Alerts · Analytics · Settings) | V1, V9, V12 |
| Customer Health Distribution | `Customer History → Dashboard` → "Customer Health Distribution" panel | V9, V12 |
| RFM Segments | `Customer History → Dashboard` → "Customer Segments (RFM Analysis)" · also `Customer History → Segments` | V2, V5, V7, V10 |
| Top Customers widget | `Customer History → Dashboard` → top-left panel | V2, V12 |
| Abandoned Cart Tracking | `Customer History → Abandoned Carts` | V6, V12 |
| Customer profile | `Customer History → Customers & Users` → a customer → "Customer Details" | V5, V11 |
| Enhanced Product Reports | `Customer History → Statistics → Product Revenue` | V8 |
| Revenue Time Patterns | `Customer History → Statistics → Revenue Patterns` (Day of Week / Hour of Day / **Heatmap**) | V8 |
| Sessions / Search history | `Customer History → Sessions` · per-customer on Customer Details | V11 |
| **WooCommerce's own revenue report** | `Analytics → Revenue` (a different top-level menu — not under Customer History) | V3 comparison |

**Naming resolved:** the on-screen label is **"Data Verified"** on a green badge. Not "Data Integrity Checker," not "Revenue Data Integrity." All scripts must be updated to match.

---

# B. THREE BLOCKING FINDINGS

## B1 — There are no refunds on this store. None.

**Evidence:** WooCommerce Analytics table footer reads `$0.00 Returns` across the whole period. Customer Details shows `Refunded Orders: 0` and `Refunded Orders Amount: $0`.

**What this breaks:**

| Piece | Depends on refunds | Status |
|---|---|---|
| Long-form demo beat 1 — "net revenue with refunds subtracted" | Yes | **Nothing to subtract. Line is meaningless on this data.** |
| Long-form demo beat 4 — refund posted on return date as a named cause | Yes | **Unrecordable** |
| **Short 2 — "Your refunds are in the wrong month"** | Entirely | **Cannot be shot at all** |
| Short 1 — closes on "partial refund, posted on the return date" | Yes | **Rewrite or rebuild data** |

This is the biggest single gap. The refund-dating mechanism (DP-13, WooCommerce's own documentation) is the most defensible evidence in the entire research package, and the demo store can't demonstrate it.

**What I need you to build — see Section E1.**

## B2 — "Data Verified" looks like a passive badge, not a check you run

**Evidence:** the green `✓ Data Verified` badge sits in the corner of the Dashboard's Revenue Analytics panel. On the dedicated `Customer History → Revenue` page there's no equivalent control at all — just From/To/Apply and the four KPI cards. There's no "Data Integrity" item anywhere in the Customer History submenu (Dashboard · Customers & Users · Abandoned Carts · Segments · Order History · Revenue · Churn Prediction · Sessions · Statistics · Settings · Account · Contact Us).

**What this breaks:** every script says *"run the integrity check → it names the cause."* Across the long-form and all four shorts, that's the resolution moment — the thing the entire video builds to. If the feature is a passive pass/fail badge with no failure state and no cause displayed, **the resolution moment doesn't exist as written.**

**Questions I need answered — Section E2.** Depending on the answers, the demo either stays as scripted, gets rewritten around the badge, or the video gets re-planned.

## B3 — The two date ranges don't match, and there's a dangerous coincidence

| Screen | Range shown | Net revenue | Orders |
|---|---|---|---|
| `Customer History → Revenue` | **Jun 24 – Jul 23, 2026** (30 days) | **$2,883.00** | **38** |
| `Analytics → Revenue` (WooCommerce) | **Jul 1 – 23, 2026** (23 days) | **$2,298.00** | **30** |

If these two screens are recorded as-is and the editor cuts them together, the video shows a $585 discrepancy that is **entirely an artifact of two different date pickers**. That is a fabricated discrepancy in a video whose entire premise is honest verification. It would be the single worst thing this channel could publish.

**It gets worse.** On the plugin Dashboard, "Revenue by Customer Type" shows `RETURNING CUSTOMERS $2,298.00 — 30 orders`. That is *the same number and the same order count* as WooCommerce's net sales for a completely different period. An editor scrubbing footage could very easily grab that number believing the two systems agree. They don't — it's a coincidence in the demo data.

**Non-negotiable:** before recording anything, set **both** screens to the identical range and tell me what the numbers actually are. Instructions in E3.

---

# C. TWO GENUINE MECHANISMS ALREADY VISIBLE — record these regardless

Both of these are real, on-screen, need nothing built, and are honest. **They may rescue the video if B1 and B2 can't be resolved.**

## C1 — WooCommerce's own popover admits the counting rule changed

On `Analytics → Revenue` there's a dismissible popover: *"Revenue is now reported from paid orders ✅ — We now collect orders in this table based on when the payment went through, rather than when they were placed. You can change this in settings."*

That is **WooCommerce telling the store owner, on their own screen, that the rule producing the number changed.** Order date versus payment date. It is exactly the video's thesis, stated by the platform.

**Record this before dismissing it.** If it's already dismissed on your admin user, create a fresh admin user or clear the dismissal so it renders again.

## C2 — The staleness window is displayed with timestamps

Top-right of `Analytics → Revenue`: `Data status — Last updated Jul 22 10:29 · Next update Jul 23 at 09:11 · Update now`.

WooCommerce Analytics runs off a scheduled import. **For up to ~23 hours it is legitimately behind the live order data**, and it says so. Two screens can disagree purely because one hasn't re-imported. That's a real, demonstrable, non-bug cause of disagreement — and it's got a clock on it.

**Record:** the Data status block, then click **Update now**, then the refreshed state and timestamp.

---

# D. SHOT LIST

## Pre-flight — do all of this before the first take

| # | Item | Why |
|---|---|---|
| 1 | Record at **1920×1080**, browser at 100% zoom, no OS scaling | Matches your screenshots; keeps text legible after the shorts crop |
| 2 | **Hide the admin sidebar** where a shot doesn't need it — use *Collapse Menu* at the bottom | The sidebar eats ~160px and is dead weight in a 9:16 crop |
| 3 | **Clear the `Plugins 2` update badge** | An update-pending badge on camera reads as a neglected install |
| 4 | **Remove UpdraftPlus from the admin bar** and hide the **Email Log** sidebar item if possible | Email Log especially — a viewer seeing "Email Log" next to a churn feature will assume the plugin emails customers, which Brief §10 forbids claiming |
| 5 | Browser in **fullscreen / kiosk** — no URL bar | The store runs on `localhost`; a visible URL bar destroys the illusion of a real store |
| 6 | Confirm the greeting reads a plausible store-owner name | Currently "Howdy, Sarah M." — fine, just be consistent across all 12 videos |
| 7 | **Check the duplicated Customer History submenu.** In two screenshots the submenu renders twice in the sidebar | Looks like a bug on camera. Confirm whether it's a stitching artifact or real |
| 8 | Cursor: **slow, deliberate, no circling.** Pause ~1s before each click | Editors can't fix jittery cursor movement |
| 9 | **Hold every end state 12–15 seconds** with no mouse movement | Gives the editor room to breathe and zoom. You will always want more than you think |
| 10 | One continuous take per shot. Don't stitch mid-shot | |

## Long-form shots — `V3_SCRIPT_Revenue_Numbers_Dashboard.md`, 2:00–5:00

### SHOT 1 — The two totals, same period
**Serves:** long-form demo beat 1 · Short 1 · Short 3
**Status:** ⚠️ Recordable only after B3 is resolved

| Step | Action | Hold |
|---|---|---|
| 1.1 | Open `Customer History → Revenue`. Sidebar collapsed. | 3s |
| 1.2 | Set **From 06/24/2026 → To 07/23/2026**, click **Apply** | 2s |
| 1.3 | Rest on the four KPI cards — Net Revenue, Orders, Avg Order Value, Customers | **15s, no movement** |
| 1.4 | New browser tab → `Analytics → Revenue` | 3s |
| 1.5 | Open the date-range dropdown, choose **Custom**, set **Jun 24 – Jul 23 2026**, apply | 5s |
| 1.6 | Scroll to the table footer showing the totals row | **15s, no movement** |
| 1.7 | Alt-tab between the two tabs three times, ~4s on each | 25s |

**Must be in frame:** both net revenue figures, both order counts, and **both date ranges legible**. If the ranges aren't readable on screen, the shot is unusable — the audience has to be able to verify we're comparing like with like.

**Report back:** the two figures once the ranges match. The script's opening beat depends on whether they agree.

### SHOT 2 — The Data Verified badge
**Serves:** long-form beat 2–3 · Short 3
**Status:** ✅ Recordable today

| Step | Action | Hold |
|---|---|---|
| 2.1 | `Customer History → Dashboard` | 2s |
| 2.2 | Scroll to the **Revenue Analytics** panel. Stop scrolling with the panel header at the top of frame | 3s |
| 2.3 | Rest on the panel — four KPI cards visible, green **✓ Data Verified** badge top-right | **15s** |
| 2.4 | Move cursor to the badge and hover. **If a tooltip or detail appears, hold 10s.** If nothing appears, hold 5s and note it | 10s |
| 2.5 | Slow scroll through "Revenue Over Time" and "Revenue by Customer Type" | 12s |

**Report back:** does hovering or clicking the badge do anything at all?

### SHOT 3 — The failure state
**Serves:** long-form beat 4 · Short 1 · Short 4
**Status:** 🚫 **Blocked on B1 + B2.** This is the video's resolution moment and currently cannot be recorded.

Once E1 and E2 are resolved, record: the same Revenue Analytics panel in its **failed** state, whatever the badge becomes, and whatever text names the cause. Hold the named cause **20 seconds** — it's the single most important frame in the video.

### SHOT 4 — WooCommerce's own rule change *(C1)*
**Serves:** honesty beat, 1:30–2:00 · possible Short 4 replacement
**Status:** ✅ Recordable today, but the popover must not be dismissed first

| Step | Action | Hold |
|---|---|---|
| 4.1 | `Analytics → Revenue` with the "Revenue is now reported from paid orders" popover visible | **15s** |
| 4.2 | Slow zoom-friendly hold on the popover body text | 10s |
| 4.3 | Cursor hovers the word **settings** in the popover — don't click | 5s |
| 4.4 | Click **Got it** | 3s |

### SHOT 5 — The staleness clock *(C2)*
**Serves:** honesty beat · strong Short 4 alternative
**Status:** ✅ Recordable today

| Step | Action | Hold |
|---|---|---|
| 5.1 | `Analytics → Revenue`, cursor to the **Data status** block, top-right | 3s |
| 5.2 | Rest on `Last updated` / `Next update` timestamps | **12s** |
| 5.3 | Click **Update now** | — |
| 5.4 | Capture the refresh, then the new timestamp | **12s** |

### SHOT 6 — B-roll bank
**Status:** ✅ Recordable today. 8–10s each, slow scroll, no clicks.

`Customer History → Order History` · `Customer History → Segments` · `Customer History → Statistics → Product Revenue` · the Dashboard's "Revenue by Customer Type" strip.

## Shorts shots — vertical

**The framing problem:** wp-admin is 1920 wide; shorts are 9:16. Cropping loses ~70% of the frame. **Do not reframe while recording.** Record everything at full 1920×1080 and let the editor punch in. For each shorts beat below I've named the region to punch into — the developer's job is to make sure that region is uncluttered and legible.

| Short | Uses shot | Punch-in region | Status |
|---|---|---|---|
| **1 — "Which number is real?"** | 1 + 3 | The four KPI cards, then the named cause | 🚫 blocked on B1/B2/B3 |
| **2 — "Refunds are in the wrong month"** | needs new footage | Net revenue card + refund line | 🚫 **blocked on B1 — no refunds exist** |
| **3 — "What a verified number looks like"** | 2 | Revenue Analytics panel header + Data Verified badge | ✅ **recordable today** |
| **4 — "Your dashboard won't tell you why"** | 3 | The named cause | 🚫 blocked on B2 · **see substitution below** |

**Substitution for Short 4 if B2 can't be resolved:** rebuild it on Shot 4 (WooCommerce's own popover). The hook still works — *"Every dashboard shows you a number. Almost none tell you why it's that number"* — and the demo becomes WooCommerce admitting the counting rule changed. Honest, already on screen, needs nothing built. Say the word and I'll rewrite that short.

---

# E. WHAT I NEED YOU TO BUILD OR CONFIRM

## E1 — Build refunds on the demo store *(highest priority)*

Nothing about the refund mechanism can be shown until this exists. Please create:

1. **A cross-month refund.** An order placed in **June 2026**, refunded in **July 2026**. This is the one that matters most — it's what makes a June report and a July report legitimately disagree, and it's the mechanism WooCommerce documents.
2. **A partial refund.** One order refunded in part, not in full. Brief §4 lists partial refunds as something the integrity feature flags.
3. **An On-Hold order** inside the reporting period, ideally with a value large enough to move the total visibly.
4. **A mixed-currency order**, if the store supports it. Optional — the other three carry the video.

**Please don't wipe the existing dataset.** Per v3, the same demo store has to carry all twelve videos. Add to it; don't rebuild it.

## E2 — Answer these about "Data Verified"

1. Is it interactive, or purely a status indicator?
2. **Is there a failure state?** What does the badge become — red, a warning, different text?
3. **Does it name a cause** when it fails, or just report pass/fail?
4. What conditions actually trigger a failure? Brief §4 says unusual statuses, partial refunds and currency mismatches — is that accurate to the shipped code?
5. Can it be forced into a failure state on demand for recording?
6. Is it Pro-only? Every script tags it `PRO` on screen — confirm that's right.
7. Does it appear anywhere besides the Dashboard's Revenue Analytics panel?

**If the answer to 2 and 3 is "no failure state, no cause displayed," tell me immediately.** The video's entire resolution moment would need re-planning, and I'd rather rewrite now than have you record something that can't be used.

## E3 — Run the matched-range comparison and report back

Set both to **Jun 24 – Jul 23, 2026** and send me four numbers:

| | Net revenue | Orders |
|---|---|---|
| `Customer History → Revenue` | | |
| `Analytics → Revenue` | | |

If they **disagree**, that's the video — record it exactly as it stands and tell me the gap. If they **agree**, the opening beat needs rewriting around a different comparison, and I'd want to know before you spend a Monday recording.

## E4 — A guardrail conflict, and it isn't about V3

On **Customer Details** the Churn Risk Analysis panel carries buttons labelled **"Send Win-Back Email"** and **"Create Coupon."** The RFM panel recommends *"Execute win-back email campaign"* and *"Offer personalized special discounts."* The Lifecycle panel says *"URGENT: Launch win-back campaign · Send personalized discount (20%)."*

Marketing Brief §10 states the plugin never sends customer emails and never creates coupons or discounts. The Dashboard's abandoned-cart panel points at an **"Automation Add-on"** for recovery actions — which suggests those buttons hand off to a separate product, and that add-on isn't mentioned anywhere in the Brief.

Three things I need to know:

1. What do those two buttons actually do? Open the admin's mail client, or something more?
2. What is the Automation Add-on, and is it shipping?
3. **Until that's settled, keep the Customer Details right-hand column out of frame.** It doesn't affect V3, but V1 and V9 are churn videos built on exactly this screen — a viewer seeing "Send Win-Back Email" concludes the plugin automates outreach, which is the one claim the Brief most explicitly forbids.

---

# F. WHAT CAN BE RECORDED THIS WEEK, REGARDLESS

Don't let E1–E3 stall the session. These need nothing:

- **Shot 2** — Data Verified badge in its passing state
- **Shot 4** — WooCommerce's "reported from paid orders" popover *(record before dismissing)*
- **Shot 5** — the Data status / Update now cycle
- **Shot 6** — the B-roll bank
- **Short 3** in full

That's one of four shorts complete and the long-form's honesty beat covered. The rest waits on refunds and an answer about the badge.
