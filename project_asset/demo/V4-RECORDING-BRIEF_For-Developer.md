# V4 SCREEN RECORDING BRIEF — for the developer

**Video:** V4 / Week 7 — *"Your WooCommerce Revenue Is Two Numbers — You Only See One"*
**Covers:** the long-form demo segment (2:15–4:30) **and** all three derived shorts
**Demo store:** Northline Supply Co. · admin user *Sarah M.*
**Scripts:** `V4-SCRIPT_Flat-Revenue_Traffic-Up-Revenue-Flat.md` · `V4-SHORTS_Flat-Revenue.md`

> **One recording session produces everything.** The long-form demo and all three shorts reuse the same captures. Read §1 and §2 before booking the session — there is a data blocker that has to clear first.

---

# 1. READ THIS FIRST — the demo store cannot tell this story yet

The screenshots confirm the blocker flagged in the Final Content Doc, and add a second one.

### Blocker A — there is no five-month history, and revenue isn't flat

Every screen shows **Jun 24 – Jul 23, 2026**: a 30-day window. The script's spine is *"revenue hasn't moved in five months."* Worse, the current data shows the opposite of flat:

| Card (Dashboard) | Value | Trend |
|---|---|---|
| Net Revenue | $2,883.00 | ▲ 22.5% |
| Total Orders | 38 | ▲ 11.8% |
| Avg Order Value | $75.87 | ▲ 9.6% |
| Total Customers | 35 | ▲ 25.0% |

And the split currently reads **NEW $336 (5 orders) · RETURNING $2,298 (30 orders) · GUEST $249 (3 orders)** — returning revenue is 80% of the total and healthy. That's a *thriving* store, which is the exact opposite of the video's argument. WooCommerce → Analytics → Revenue confirms the store was seeded recently: month-to-date $2,298 against $74 for the same window last year.

**Nothing can be recorded until the dataset is reseeded.** Seeding spec in §4.

### Blocker B — the split is three cards, not two diverging lines

This is a script problem, not just a data one.

The script says *"look what happens to a flat line when you split it"* and describes **two diverging lines** with an annotation held on screen. The actual UI (`Dashboard-_-Demo.png`) shows **Revenue by Customer Type** as a static three-card strip — New / Returning / Guest, each with a dollar total and an order count. There is no toggle, and no time-series broken out by customer type.

You cannot record diverging lines because the product does not draw them. Two ways forward:

| | Approach | Verdict |
|---|---|---|
| **A** | Reseed for five months, then record the three-card strip **five times — once per month** — stepping the date range back. The editor steps or cross-fades through them: returning descends, new climbs, total holds. | ✅ **Recommended.** The divergence is real, told through the UI exactly as it exists. Nothing fabricated. |
| **B** | Rewrite the script's demo beats to describe a snapshot rather than a trend. | Weaker — loses the resolution moment the whole video is built on. |

**Go with A.** §7 is written for it. Script deltas in §3.

---

# 2. CONFIRMED MENU PATHS

The screenshots resolve every blank in `PureDevs_Feature_Reference_Card.md`. Worth copying back into that file — it unblocks other videos too.

**Top-level:** `Customer History` sits in the main wp-admin sidebar between *Payments* and *Analytics*.

**Sub-menu:** Dashboard · Customers & Users · Abandoned Carts · Segments · Order History · Revenue · Churn Prediction · Sessions · Statistics · Settings · Account · Contact Us

**In-page tabs (top of every Customer History screen):** Dashboard | Customers & Users | Order History | Revenue | Segments | Sessions | Statistics | Settings

**Statistics sub-nav:** Site Pages | Keywords | Viewed vs Purchased Ratio | Top Selling Products by This Month | Product Revenue | Conversion Funnel | Frequently Bought Together | Brand View | Category View | Revenue Patterns | Total Spent

| Feature | Confirmed path |
|---|---|
| **Revenue by Customer Type (new/returning/guest)** | **Customer History → Dashboard →** scroll to *Revenue Analytics* → strip directly below *Revenue Over Time* |
| Revenue Analytics (KPIs + trend, standalone) | Customer History → Revenue |
| Avg Order Value card | Both of the above |
| Data integrity surfacing | *"Data Verified ✓"* badge, top-right of the Dashboard's Revenue Analytics header |
| Churn Prediction | Customer History → Churn Prediction (tabs: At-Risk Customers / Alerts / Analytics / Settings) |
| Customer Health Distribution | Customer History → Dashboard |
| Customer Health Score (per customer) | Customers & Users → [customer] → Customer Details, right column |
| RFM Segments (donut) | Customer History → Dashboard → *Customer Segments (RFM Analysis)* |
| Top Customers widget | Customer History → Dashboard |
| Abandoned Cart Tracking | Customer History → Abandoned Carts |
| Saved Segments | Customer History → Segments |
| Enhanced Product Reports | Statistics → Product Revenue · Frequently Bought Together |
| Revenue Time Patterns / heatmap | Statistics → Revenue Patterns (Day of Week / Hour of Day / Heatmap) |
| Aggregate Conversion Funnel | Statistics → Conversion Funnel |
| Behavioral tags | Customer Details → *Behavioral Profile* · Dashboard → *Behavioral Segments* |
| Per-customer session / search history | Customer Details → *Sessions History* / *Search History* |
| Customer financial summary | Customer Details → *Financial Summary* |

> **Segment count — confirmed.** The RFM donut lists exactly ten named segments (Champions, Loyal, Potential Loyalist, New Customer, Promising, Needs Attention, At Risk, Cannot Lose, Hibernating, Lost) plus an *Undefined* bucket. Ten named, as the Marketing Brief says. **Undefined is a null bucket, not an eleventh segment** — don't let it get counted in any copy.

---

# 3. SCRIPT DELTAS

Give these to whoever owns the script before the session. The recording can't match the current wording.

| Script location | Current line | Change to |
|---|---|---|
| Long-form 2:20 | *"I'm opening **Revenue Analytics**"* | *"I'm on the Customer History dashboard"* — the split lives here, not on the Revenue page |
| Long-form 3:15 | *"here's the click that changes the picture"* | *"here's the panel most people scroll straight past"* — there is no click; it's a scroll |
| Long-form 3:20 | *"Look what happens to a **flat line** when you split it"* | *"Look what happens to a flat total when you split it"* |
| Long-form 3:30–3:50 | *"that **line**'s fine… returning-customer revenue **is falling**, month after month, **underneath the total**"* | Rewrite for the month-by-month step-through: *"Here's March. Here's April. May. June. July. New-customer revenue climbing every month. Returning revenue falling every month. The total never moves."* |
| Long-form 4:00 | *"the two diverging **lines**"* | *"the two numbers moving apart"* |
| Short 1 | *"I split it by customer type"* | *"Here's the same revenue, by customer type"* |
| Short 1 / 2 | annotation on *"two diverging lines"* | annotation across the New and Returning **cards** |
| Short 3 | *"the revenue split"* | unchanged — Short 3 only needs the snapshot, so it works as written |

Everything else in both scripts survives intact, including all §10 clauses.

---

# 4. DATA SEEDING SPEC

Target: **five months, flat total, returning declining, new climbing.** Set the store's window to roughly **Mar 1 – Jul 23, 2026**.

The shape the seeded data must produce when the Dashboard date range is set to each single month:

| Month | New customers | Returning customers | Guest | Total |
|---|---|---|---|---|
| March | ~$420 | ~$2,300 | ~$180 | ~$2,900 |
| April | ~$560 | ~$2,150 | ~$190 | ~$2,900 |
| May | ~$700 | ~$2,010 | ~$185 | ~$2,895 |
| June | ~$850 | ~$1,870 | ~$180 | ~$2,900 |
| July | ~$980 | ~$1,730 | ~$190 | ~$2,900 |

**These are shape targets, not required figures.** What matters: the total varies by under ~2% across all five months, returning falls by roughly 25%, new roughly doubles, guest stays boring. Any numbers hitting that shape work.

Also required:

- **The five-month topline must read flat.** With the range set to Mar 1 – Jul 23, the Net Revenue trend arrow should be near zero — not ▲22.5%. If the KPI card shows strong growth, the opening of the demo contradicts the script.
- **Order counts should move with the money** — returning-customer order count declining alongside returning revenue. A falling total with a flat order count implies an AOV story, which is a different video.
- Keep AOV roughly stable or slightly rising, so Short 3's beat still works.
- Seeded customers only. All current demo emails are `@example.com` — keep it that way, and confirm no real customer data is present before recording.

---

# 5. PRE-FLIGHT — clean the environment

Everything here shows up on camera and reads as sloppy if left.

**Browser**

- Chrome, clean profile, **no extensions**, no bookmarks bar, no other tabs.
- **Hide the address bar** — full-screen/kiosk mode (`F11`). The store currently runs on `http://localhost/ch/…` and a visible localhost URL undercuts "a real WooCommerce install." If you'd rather keep the bar visible, set up a local domain like `northline.test` first.
- Browser zoom **125%** for the long-form pass. WordPress admin at 100% renders text too small to read on mobile YouTube.
- Window/canvas **1920 × 1080**.

**WordPress admin**

| Clean up | Why |
|---|---|
| Plugins update badge (currently **2**) | Reads as a neglected install |
| Comments count / notification bubbles in the admin bar | Distracting, and dates the footage |
| The *UpdraftPlus* item in the admin bar | Third-party branding in frame |
| Any admin notices, "Got it" popups, review nags | One is visible in the WooCommerce Analytics screenshot — dismiss all before recording |
| **Collapse the left sidebar menu?** | **No — leave it expanded.** The `Customer History` menu item being visible is part of the proof it's a real plugin in wp-admin |

**Consistency across the whole session**

Same browser window size, same zoom, same logged-in user (*Sarah M.*), same date range unless the shot calls for changing it. Record everything in one sitting — v3 §6 batch rule.

---

# 6. 🚨 §10 DANGER ZONES — must never appear on camera

The scripts promise, repeatedly, that the plugin **observes and reports — it never emails customers, never recovers carts, never automates anything.** Several screens contradict that on their face. If any of these land in frame, the video argues against itself.

| Screen | What's on it | Rule |
|---|---|---|
| **Customer Details** (any customer) | Buttons labelled **"Send Win-Back Email"** and **"Create Coupon"**. Also *"Execute win-back email campaign"*, *"Send personalized discount (20%)"*, *"URGENT: Launch win-back campaign"* under Recommended Actions | **Do not navigate to this page at all during the V4 session.** A viewer who sees "Send Win-Back Email" concludes the plugin sends email — directly contradicting the narration |
| **Dashboard → At Risk Customers** panel | *"View Profile"* buttons leading to the above | Don't click. Don't let it sit in frame during a hold |
| **Dashboard → Abandoned Carts (Last 7 Days)** | *"Observation only — see the **Automation Add-on** for recovery actions"* | Sits **immediately below** Revenue by Customer Type. **Stop every scroll before this panel enters frame.** It raises a second product the video never explains |
| **Churn Prediction** | *Assigned To* dropdowns, alert workflow | Not needed for V4. Skip entirely |

**Framing consequence:** the Revenue by Customer Type hold in §7 has a hard bottom edge. Scroll so the three cards sit in the upper two-thirds of frame and the Abandoned Carts panel stays below the fold.

> Worth raising separately with the founder: the "Send Win-Back Email" and "Create Coupon" buttons need checking against Marketing Brief §10. If they open the store owner's mail client and WooCommerce's own coupon screen, that's consistent with §10 and just needs documenting. If they do more than that, §10 is out of date and every piece of copy built on it needs revisiting. Not a blocker for this recording — the fix here is simply to stay off that page.

---

# 7. SHOT LIST — long-form demo (2:15–4:30)

Record in this order. Cursor moves slowly and deliberately; no hunting, no overshoot. Pause **2 full seconds** before and after every click so the editor has clean cut points.

### SHOT 1 — arrival
- **Path:** wp-admin sidebar → click **Customer History** → lands on Dashboard
- **Action:** start with the sidebar visible, click the menu item, let the page load fully
- **Hold:** 4s on the loaded dashboard top
- **Serves:** *"This is PureDevs Customer History on a real WooCommerce install"*
- **Note:** don't scroll yet. The widget grid at the top establishes it's a real product.

### SHOT 2 — the topline
- **Action:** scroll down to the **Revenue Analytics** section header. Stop with the four KPI cards and the *Data Verified ✓* badge in frame
- **Hold:** 6s
- **Serves:** *"Here's the topline. Net revenue, orders, average order value, customer count."*
- **Note:** the four cards must show the five-month range and a near-flat trend. If the arrows show strong growth, stop — the data isn't ready (§4).

### SHOT 3 — widening the window
- **Action:** click the date-range control, set **Mar 1 – Jul 23, 2026**, apply. Let the numbers and chart repaint
- **Hold:** 5s after repaint
- **Serves:** *"Now I'll widen the window to five months"* + the free-tier honesty beat
- **Note:** capture the whole interaction in one unbroken take, including the picker opening. The editor may speed-ramp the middle, but needs the real motion.

### SHOT 4 — flat, at length
- **Action:** no interaction. Sit on the Revenue Over Time chart with **Monthly** granularity selected
- **Hold:** 8s
- **Serves:** *"Still flat. Still nothing."*
- **Note:** the longest static hold in the video, and deliberately so. The viewer needs to feel the flatness before the reveal.

### SHOT 5 — the panel below
- **Action:** slow scroll (~1.5s) down to **Revenue by Customer Type**. Stop with all three cards in the upper two-thirds of frame
- **Hold:** 8s
- **Serves:** *"here's the panel most people scroll straight past"*
- **⚠️ Framing:** Abandoned Carts must stay below the fold (§6)

### SHOT 6 — the month-by-month step (the resolution)
This is the shot the whole video exists for. Five sub-takes, identical framing.

- **Action:** with the three cards framed exactly as in Shot 5, set the date range to **March only** → apply → **hold 4s**. Then April → hold 4s. May. June. July.
- **Critical:** the page must not shift a single pixel between sub-takes. Don't scroll, don't resize, don't move the window. If the layout jumps, the step-through won't cut together
- **Total:** ~20s of usable footage plus the range-change motion between each
- **Serves:** *"Here's March. Here's April… New climbing. Returning falling. The total never moves."*
- **Note:** if the date picker is visible in frame, that's fine and good — it proves the months are real. If it's not, capture one separate take showing the range being changed so the editor can cut it in.

### SHOT 7 — the resting frame
- **Action:** return to the full five-month range. Nothing else
- **Hold:** 10s, dead still
- **Serves:** the resolution and §10 beat — *"it showed you which half of your revenue is falling. It didn't email anyone."*
- **Note:** the editor annotates over this. Give clean, motionless footage with no cursor in frame — move the mouse off-screen.

---

# 8. SHOT LIST — shorts

Same session, same store, same data. **Shorts are vertical**, so these need their own pass.

### Vertical capture setup
Narrow the browser window to roughly **900px wide** before this pass. WordPress admin is responsive — at that width the three customer-type cards should stack vertically, which fills a 9:16 frame far better than cropping a landscape capture. **Test this first**: if they don't stack, fall back to landscape at 150% zoom and record each card individually for the editor to compose.

Everything below is re-recorded in the vertical setup. Don't try to crop the long-form footage — the text ends up too small.

| Asset | Content | Hold | Used by |
|---|---|---|---|
| **V-A** | Revenue Analytics KPI cards, five-month range, flat | 6s | Shorts 1, 2 |
| **V-B** | Revenue by Customer Type, three cards, five-month range | 8s | Shorts 1, 2, 3 |
| **V-C** | The month-by-month step-through, vertical framing, 5 × 3s | 15s | Shorts 1, 2 |
| **V-D** | Tight on the **Avg Order Value** card alone | 5s | Short 3 |
| **V-E** | Tight on the **Returning Customers** card alone | 5s | Short 2 |

Same §6 framing rules apply — Abandoned Carts stays out of frame in V-B and V-C.

---

# 9. RECORDING SETTINGS

| Setting | Value |
|---|---|
| Resolution | 1920 × 1080 (landscape pass) · 1080 × 1920 or 900-wide window (vertical pass) |
| Frame rate | 60 fps — smooth scrolls matter more here than anywhere else |
| Format | MP4, H.264, high bitrate. No compression before handoff |
| Audio | **None.** Narration is recorded separately |
| Cursor | Visible, default size, no click-highlight effects or ripples |
| Tool | OBS or Loom per the v3 stack. OBS preferred for the vertical pass — easier canvas control |

**File naming:** `V4_LF_shot01_arrival.mp4` … `V4_LF_shot07_resting.mp4`, `V4_SH_VA_kpis.mp4` … `V4_SH_VE_returning-card.mp4`

Deliver raw takes. Don't trim, don't colour-correct, don't add zooms — the editor handles all of it and needs the headroom.

---

# 10. QA CHECKLIST — before handing off

- [ ] Dataset reseeded; five-month total flat within ~2%; returning down ~25%; new roughly doubled
- [ ] Net Revenue KPI shows a near-flat trend on the five-month range, not ▲22.5%
- [ ] All demo emails still `@example.com`; no real customer data anywhere in frame
- [ ] Address bar hidden, or a real local domain in use — **no `localhost` visible**
- [ ] Plugins update badge, comment count, admin notices, UpdraftPlus all cleared
- [ ] **Customer Details page never opened during the session**
- [ ] Abandoned Carts / Automation Add-on panel never in frame
- [ ] Shot 6's five sub-takes are pixel-identical in framing
- [ ] Shot 7 has no cursor in frame
- [ ] Vertical pass recorded separately, not cropped from landscape
- [ ] Every take has 2s of dead air at both ends
- [ ] Files named per §9

---

# 11. OPEN QUESTIONS

1. **"Send Win-Back Email" / "Create Coupon"** — what do these actually do? Determines whether Marketing Brief §10 is accurate or needs updating. Doesn't block this recording (§6 routes around them) but affects a lot of published copy.
2. **Version 7.0.2** in the admin footer — that's the standard WordPress version slot, so presumably WP 7.0.2 rather than the plugin. Worth confirming, since the plugin version on the demo store should match what's public before anything records.
3. **Does the free tier actually cap at 90 days on this install?** The script's honesty beat depends on it. If the demo store runs Pro, confirm the free limit separately rather than claiming it on camera.
4. **Guest orders** — the three-card strip includes guest revenue, and the script now mentions three numbers. Confirm guest behaviour stays boring across the five months so it doesn't pull focus from the New/Returning story.
