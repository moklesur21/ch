# V1 DEMO RECORDING GUIDE — Developer Handbook

**Produces:** all screen-recording footage for the V1 long-form demo segment (script 2:00–5:00) **and** the demo clips for the 4 derived shorts, plus a spare/b-roll library.
**Companion docs:** `PureDevs_V1_Script_Silent_Churn.md` (the narration this footage must match) · Topic Filter v2.1 (V1 row) · Video Production Strategy v3.
**Built from:** the 4 demo-store screenshots provided July 2026 (Dashboard, Churn Prediction → At-Risk, Churn Prediction → Alerts, Customer Details).

**The one rule above all (v3, non-negotiable):** real screen recording of the real demo store. No mockups, no CapCut-faked UI. If the data doesn't look right, we fix the data — never the pixels.

**Order of work:** Phase 0 → 1 → 2 are data work. **Recording does not start until the Phase 1 acceptance checklist passes and the Phase 2 report-back sheet is returned** — the voiceover gets patched to match your real numbers before you record, so narration and screen always agree.

---

## PHASE 0 — Store identity & cleanup (est. 1–2 hrs)

These are one-time fixes. Several were visible in the screenshots.

| # | Fix | Where | Detail |
|---|---|---|---|
| 0.1 | **Currency → USD** | WooCommerce → Settings → General | Screenshots show ৳ (Taka) throughout. The video narrates in dollars for an EN-market ICP — switch to `$` **before** seeding so all seeded values are dollar-realistic. |
| 0.2 | **Site title → store name** | Settings → General | Admin bar currently reads "WordPress Blog." Rename to a believable store, e.g. `Northline Supply Co.` or `Harbor & Oak` (founder's pick — just not "WordPress Blog"). |
| 0.3 | **Admin display name → human** | Users → your profile | Top bar reads "Howdy, wpadmin." Set display name to a person (e.g. `Sarah M.`). This name also appears in the **Assigned To** dropdown — it's on camera in the demo's final click. |
| 0.4 | **Deactivate Freemius Debug** | Plugins | `Freemius Debug [v.2.13.2]` menu is visible in the sidebar (Customer Details screenshot). Must not appear in any take. |
| 0.5 | **License state** | Customer History → Account | Confirm Pro license properly activated. Sidebar currently shows both `Account` and a green `Upgrade ➤`. If `Upgrade` still shows for licensed Pro by design, fine — we just won't feature it. If it's a state bug, fix it. |
| 0.6 | **Dismiss all admin notices** | wp-admin everywhere | Zero nags, zero update banners, zero WooCommerce setup prompts on any screen we record. Update WP/Woo/plugins first so no update badges show (the sidebar currently shows "Plugins ①"). |
| 0.7 | **Customer name realism pass** | Seeder (Phase 1) | Current names break the illusion: `JohnWalton` (no space), lowercase `everett`, `John Doe`. All customers get real-looking full names (`John Walton`, `Everett Cole`, …). Keep `@example.com` emails — safe and standard for demo data. |
| 0.8 | **Timezone + date format** | Settings → General | Confirm store timezone is set (order timestamps will be visible). Default date format is fine. |
| 0.9 | **DB snapshot (before)** | Your tooling | Take a backup before seeding so you can iterate destructively. |

**Version-string note:** the footer reads "Version 7.0.2" while public messaging is 3.0.0. Confirm with the founder that the demo store runs the build that ships publicly, and don't scroll the footer into held shots.

---

## PHASE 1 — Data seeding spec (est. 0.5–1.5 days incl. script)

**Why:** the story is a *healthy-looking store with an invisible leak*. The current data reads as a dead store: returning revenue **$0 / 0 orders**, average health **16.3** with **73.68% of customers Critical**, "Declining Health Alert" **empty**, "At Risk Customers (30+ Days)" widget **empty**, a 2-row at-risk list, revenue chart flat at zero for 3 weeks. Every one of those contradicts the narration and will be visible on camera.

Seed via script/WP-CLI with **backdated order timestamps** (orders spread over the last 12–15 months, not created "today"). Implementation is your call — the acceptance checklist below is the contract. Seed once, seed well: **this same dataset serves all 12 videos** (Topic Filter rule), so don't tune it narrowly to V1.

### 1A — The hero customer (the star of the demo)

| Attribute | Seed target |
|---|---|
| Name | Real-looking full name, e.g. `Daniel Mercer` (founder may swap) |
| History | **12–14 orders over ~13 months** at a steady **28–32 day cadence** |
| AOV | $60–$90 → lifetime total roughly **$850–$1,200** (top-5 customer in the store) |
| Last order | **51 days ago** (the script's line: "used to order about every thirty days — it's now been fifty-one") |
| Sessions/searches | A few sessions 6–8 weeks ago, then nothing recent — silence, not conflict. No refunds, no support-looking activity |
| Expected computed state (verify, don't force) | Churn risk **High** (roughly 60–80%, NOT maxed-out Critical — he's *drifting*, not lost), ~20 days overdue, primary indicator along the lines of extended absence / declining frequency; health score in the declining band; appears in the Dashboard **Top Customers** widget and (ideally) the **Declining Health Alert** widget |
| Alert | A churn alert must exist for him (check Churn Prediction → Settings thresholds so High-risk generates alerts). His alert row is the demo's final click |

### 1B — Supporting at-risk cast

The at-risk list currently has 2 rows. Seed so it has **6–8 rows spanning at least two risk levels** — e.g. 2 Critical (the existing two, renamed, are fine), 2–3 High (hero among them), 2–3 Medium. Vary customer values ($150–$900) and last-order recency (60–170 days for the others). Mixed primary indicators.

### 1C — Store-wide believability

| Surface | Acceptance target |
|---|---|
| Customers total | 45–70, with real-looking names |
| Orders | ~200–350 spread across 12–15 months; **no gap longer than 3 days in the last 60** (kills the flatline in the revenue chart) |
| Revenue by customer type (Dashboard) | Returning revenue **> $0** and **≥ 30–40%** of the period — this widget is the thesis of the whole video series |
| Revenue KPIs | Period-over-period deltas in sane ranges (no "+2159%" artifacts) |
| Health distribution | Average roughly **60–70**; Critical band **≤ 15%** of customers (currently 73.68% — must change); a healthy Good/Excellent majority |
| Declining Health Alert widget | **2–4 customers listed, hero included** (currently empty) |
| RFM donut | Champions and Loyal **> 0** (currently 0/0 with 13 Hibernating); a plausible spread |
| Lifecycle funnel | All stages populated |
| At Risk Customers (30+ Days) widget | Non-empty, consistent with the churn cards (currently empty while cards say 2 Critical — see 1D) |
| Top Searches / Most-Viewed | Already fine — keep |

### 1D — Coherence pass (the bug-or-stale-data hunt)

The screenshots show internal contradictions that must be gone before recording — a verification prospect **will** pause the video:

1. John Doe: Financial Summary says **5 orders (latest July 2)** while Health components say "**No orders yet** / No orders in last 12 months," and Churn Risk says **0 — Low Risk, healthy and engaged** directly above "**16 days overdue** / Primary concern: **Extended absence**." Lifecycle simultaneously says **Loyal Customer**.
2. Everett: **83%** risk on the At-Risk list vs **81%** on his alert row.
3. Dashboard "At Risk (30+ Days)" empty while Churn Risk Overview shows 2 Critical.

After seeding, run **every** Recalculate/Refresh (RFM, Health, Lifecycle, Behavioral, Churn — plus any cron the plugin uses), then re-check all three. If contradictions persist after recalculation, that's a product bug — **fix before recording**, because the hero's profile must be internally consistent across all five right-column cards.

### 1E — Acceptance checklist (gate to Phase 2)

Walk these four screens and tick every box:

- [ ] **Dashboard:** returning revenue ≥30%, health avg 60–70, Critical ≤15%, Declining Health widget shows 2–4 names incl. hero, RFM has Champions/Loyal, revenue chart continuous, hero visible in Top Customers
- [ ] **Churn Prediction → At-Risk:** 6–8 rows, ≥2 risk levels, hero at High with a "51 days ago" last order, summary cards match the table
- [ ] **Churn Prediction → Alerts:** hero's alert exists, Status=New, Assigned To=Unassigned, risk % matches the list exactly
- [ ] **Hero profile:** all five right-column cards agree with each other and with his order history; "Last calculated" timestamps fresh
- [ ] **DB snapshot (after):** export taken and labeled `V1-approved-dataset` — this exact state must be restorable for videos 2–12

---

## PHASE 2 — Report-back sheet (15 min, blocks recording)

The narration will be patched to your **actual computed numbers** — never record against a script whose numbers differ from the screen. Fill and return:

| Field | Your value |
|---|---|
| Hero display name | |
| Hero health score + band label + trend indicator (does a trend arrow/label render? exact wording) | |
| Hero churn risk % + risk level label | |
| "Days since last order" (exact on-screen text) | |
| "Days overdue for purchase" (exact text) | |
| "Predicted next purchase" — does this field render on his profile? Exact text | |
| Primary indicator (exact text, list + profile) | |
| Hero total spent + order count | |
| Hero's rank in Top Customers widget | |
| Assigned To dropdown — full option list | |
| Status dropdown — full option list (confirm "In Progress" exists) | |
| Anything on these screens that differs from this doc | |

---

## PHASE 3 — Recording setup

| Item | Spec |
|---|---|
| Recorder | OBS (per production stack). Display/window capture of the browser |
| Canvas / output | Native display res, minimum **2560×1440** output; 4K if the machine allows (protects the 9:16 crops) |
| FPS | 60 preferred, 30 acceptable |
| Format | MP4 or MOV, high bitrate (≥ 40 Mbps at 1440p) — editor recompresses |
| Browser | Chrome, fresh profile, zero extensions, bookmarks bar hidden, single tab, **F11 fullscreen** (no browser chrome on camera) |
| Browser zoom | **100%** for all long-form takes · **150%** for all shorts takes |
| OS | Do Not Disturb ON, notifications off, no other apps audible/visible |
| Cursor | System default. Move in straight, unhurried lines. **Hover 1s before every click; hands off the mouse 2–3s after every page settles.** No circling, no jiggling — the editor adds highlights |
| Scrolling | Slow, even, one direction per pass. Never scroll while a card you're featuring is mid-frame |
| Takes | Minimum **2 clean takes** of every shot |
| Audio | None — record silent; VO is recorded separately |

**Never click the envelope icon** on at-risk rows during any take — it opens the OS mail client on top of the recording, and the narration doesn't cover it. (It's the "Email this customer" mail-client shortcut — spares only, if at all.)

**RFM "Recommended Actions" card:** its copy ("Execute win-back email campaign" etc.) is advice *to the owner* — acceptable if incidentally on screen, but never hover, highlight, or center it. The narration's §10 line ("the plugin didn't email anyone") must not be visually contradicted by lingering on that card.

---

## PHASE 4 — Shot list: LONG-FORM demo (script 2:00–5:00)

Every shot: 100% zoom, fullscreen, 2 takes. "Hold" = static, hands off. Record generously — the editor cuts to VO pacing, so raw length ≈ 1.5–2× the script beat.

| Shot | Start state | Action — exactly | Hold / duration | Must be visible | Script beat it serves |
|---|---|---|---|---|---|
| **LF-01 — Nav in** | Any wp-admin page with sidebar visible (use Customer History → Dashboard) | Cursor travels to sidebar **Customer History** (flyout renders) → hover 1s → click **Churn Prediction** | Hold 5s after load. Take ≈ 20s | Sidebar with full submenu; page transition | "Left menu — Customer History, then Churn Prediction" + [GRAPHIC: menu-path highlight] |
| **LF-02 — Page survey** | Churn Prediction, At-Risk tab, top of page | No clicks. Cursor rests bottom-right, off-content | Hold 8s | All four summary cards (Critical / High / Medium / Active Alerts) + the table below | "One click, and this loads. The at-risk list." |
| **LF-03 — List read** | Same | Slow single scroll so all 6–8 rows pass; cursor may run *once* slowly down the Risk Level column, then off | 10–12s total | Columns: Customer, Email, Risk Score, Risk Level, Primary Indicator, Customer Value, Last Order. Sort control reading "Highest Risk First" | "Every customer on this page has been flagged… churn score… risk level…" + [GRAPHIC: risk-column highlight]. **Note:** days-overdue/prediction are NOT list columns — they land in LF-06; VO is being patched to match |
| **LF-04 — Open the hero** | Same | Hover hero's **name link** 1s → click → profile loads | Hold 5s on top of profile | Hero name, Financial Summary card (order count + total spent), RFM badge | "Let's open one." |
| **LF-05 — Health card** | Hero profile, top | One slow scroll of the right column until **Customer Health Score** card is centered; stop | Hold 8s; then micro-scroll the 4 breakdown rows, 4s | Gauge + score + band; Recent Activity / Order Frequency / Spending Trend / Engagement rows; interpretation box | "Health score [X] and falling… click into the components" + [GRAPHIC: score + trend callout] |
| **LF-06 — Churn card** | Same, mid-column | Continue slow scroll to **Churn Risk Analysis** card; center | Hold 10s | Risk gauge + level; **Key Indicators: days since last order · days overdue · primary concern**; predicted next purchase if it renders; confidence + trend | "Recency… frequency… the missed pattern… days-overdue count. That's the *why* in one line" + [GRAPHIC: recency callout] |
| **LF-07 — To the alert** | Hero profile | Sidebar → **Churn Prediction** → click **Alerts** tab (badge count visible) | Hold 5s on the alerts table | Columns: Priority, Customer, Alert, Risk, Value, Status, Assigned To, Created; hero's row | "Last click. Flag the customer, and assign the follow-up" |
| **LF-08 — The assignment** | Alerts tab | On hero's row: click **Assigned To** → dropdown open, hold 2s → select the owner (0.3's human name) → click **Status** → select **In Progress** → cursor off | Hold 5s on the updated row | Both dropdowns' open states; the row's new values | The demo's action moment + [GRAPHIC: assignment highlight]. §10: this assigns follow-up to a *person* — the plugin contacts no one |
| **LF-09 — The result** | Alerts tab | Click **At-Risk Customers** tab → cursor off-content | Hold **10s minimum**, dead still | The full ranked list, top of page | Closing beat: "Not on a settings page — on a result… a named, ranked list" |

**Spares (one take each, editor insurance):** LF-02 alt with a slow cursor pass over the four summary cards · full-profile slow scroll top-to-bottom · Alerts tab Status dropdown full option list · sort dropdown opened to show options · 8s static of the Dashboard **Churn Risk Overview** widget.

---

## PHASE 5 — Shot list: SHORTS demo clips (4 clips)

Separate takes, **150% browser zoom**, action centered in the middle ~40% of the frame (survives the 9:16 crop). Max 2–3 clicks each. Each clip 30–45s raw for a 20–30s edited demo segment.

| Clip | For short | Flow | Center-frame requirement |
|---|---|---|---|
| **SH-A** | "The one metric that predicts churn ~30 days early" | Churn Prediction page (cards + list, 5s) → click hero → hold his **Churn Risk Analysis** card, indicators centered | Risk %, days overdue |
| **SH-B** | "Your best customer just disappeared — why you didn't notice" | Dashboard **Top Customers** widget (hero visible in top 5, 5s) → hero profile → hold on **days since last order** | Hero's name in Top Customers, then the "51 days" line |
| **SH-C** | "The alert most stores miss" | Churn Prediction → **Alerts** tab (3s) → hero row: Assigned To open → select owner → Status → In Progress | The two dropdowns |
| **SH-D** | "Customer Health Score in 60 seconds" | Hero profile **Health card**: gauge (3s) → slow pass down the 4 component rows → cut to Dashboard **Customer Health Distribution** widget, 5s | Gauge, then component rows |

---

## PHASE 6 — Delivery

| Item | Spec |
|---|---|
| Naming | `V1-LF-01_take1.mp4` … `V1-SH-D_take2.mp4` · spares as `V1-SPARE-[desc]_take1.mp4` |
| Structure | One folder `V1-demo-footage/` with `long-form/`, `shorts/`, `spares/` + the filled Phase 2 sheet |
| With delivery | 1-line note per shot if anything deviated from this doc |
| Final self-QA before upload | No notices/badges/Freemius Debug in any frame · currency is `$` everywhere · "Howdy, [human name]" · no contradiction visible on the hero profile · every hold met its minimum · 2 takes per shot |

---

## APPENDIX A — Menu paths (fills the Feature Reference Card, V1 rows)

Confirmed from the screenshots:

| Feature | Path |
|---|---|
| Churn Prediction (at-risk list, alerts, analytics, settings) | `wp-admin → Customer History → Churn Prediction` — tabs: **At-Risk Customers · Alerts · Analytics · Settings** |
| Customer Health Score (per customer) | `Customer History → Customers & Users → [customer] → Customer Details` — right column, **Customer Health Score** card |
| Churn Risk detail (per customer) | Same profile — **Churn Risk Analysis** card (risk score, level, key indicators, confidence, trend) |
| Alert assignment | `Churn Prediction → Alerts tab` — per-row **Status** + **Assigned To** dropdowns |
| Health Distribution / Churn Risk Overview / Declining Health widgets | `Customer History → Dashboard` |
| Full sidebar submenu (for the Reference Card) | Dashboard · Customers & Users · Abandoned Carts · Segments · Order History · Revenue · Churn Prediction · Sessions · Statistics · Settings · Account · Contact Us |

## APPENDIX B — Known narration↔UI sync points (already being handled)

1. The at-risk **list** shows Risk Score / Risk Level / Primary Indicator / Value / Last Order — days-overdue and predicted-next-purchase appear on the **profile**, so the VO's list line is being adjusted to match. Nothing for you to do — just a heads-up on why Phase 2 blocks recording.
2. "Health score X **and falling**" depends on whether a trend indicator renders on the health card after reseeding — that's Phase 2 row 2. If no trend renders there, the VO leans on the Churn card's trend field or the Declining Health widget instead.
3. Script's illustrative numbers (health 71, 30→51 days) get replaced with your reported actuals — except **"51 days ago,"** which you control via seed data, so hit it.
