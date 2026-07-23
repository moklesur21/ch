# V2–V4 demo-store coverage report

Answers to every open question in `PureDevs_V2_Demo_Recording_RunSheet.md`,
`V3_DEMO_RECORDING_SPEC.md` and `V4-RECORDING-BRIEF_For-Developer.md`, plus
what was built into the seeder to support them. Dataset verified
**48 pass / 0 warn / 0 fail** on anchor 2026-07-23.

The morning routine is unchanged: `scripts/demo-refresh.sh` before any shoot.
The verify output now machine-checks all three videos' data requirements and
the `--report` sheet prints the V2 hero block, the V3 refund cases and the V4
month table below (numbers identical on every refresh; only dates slide).

---

## V2 — "Top 20% of Customers" (run-sheet answers)

**All three blockers are cleared** (they were symptoms of the old junk-data
store, plus two real gaps now seeded):

| Blocker | Status |
|---|---|
| 1 — zero Champions | **Champions = 8** (list shot has substance) |
| 2 — no 5/5/5 hero | **Marcus Hale** below |
| 3 — currency ৳ | Store is USD everywhere (verify enforces it) |

**The hero Champion (Part A3) — actual values on screen:**

| Field | Value |
|---|---|
| Name | **Marcus Hale** (marcus.hale@example.com) |
| RFM | **R 5/5 · F 5/5 · M 5/5**, badge **Champions** |
| Health | **86 — Excellent**, trend Stable |
| Churn panel | **Low Risk 7%**, confidence 100% (fully consistent profile) |
| Financial | **$1,768 lifetime — #1 in store**, 21 orders |
| Behavioral tags (J3) | **Brand Loyal · Category Specialist · Desktop Shopper · Researcher · Gift Shopper** |

- **J3 / narration fix:** the product has no "Weekend Shopper" on this
  profile — swap the placeholder tags for the five above. ("Brand Loyal" is
  real: the catalog now carries `product_brand` terms — North Line /
  Camber Studio / Field & Forge — and Marcus concentrates in North Line.)
- **A1 volume:** store is now **86 customers / ~348 orders / ~$3.2-3.3k per
  month**. The run-sheet's "few hundred customers" ideal was traded off
  deliberately: growing further would destabilise the churn At-Risk list
  that V1's footage depends on (exactly 7 rows). 86 customers with a full
  10-segment donut reads as a real store on camera.
- **A2 donut:** Champions 8, Loyal 3, Potential Loyalist ~35, New ~21,
  Needs Attention 3, At Risk 2, Cannot Lose 1, Hibernating ~10, Lost 1,
  Undefined ~4 (null bucket, minimal as required).
- **A4 / J2 (Segments page) — layout differs from the donut, exactly the
  case the run-sheet anticipated:** the dedicated `Customer History →
  Segments` page is a **Saved Segments manager** (custom saved lists +
  New Segment), NOT a 10-segment RFM browser. The 10-segment view lives on
  the **Dashboard donut**, and clicking a segment opens **Customers & Users
  pre-filtered by that segment** (`admin.php?page=pd-woo-customers&rfm_segment=champions`)
  — a filterable customer table with RFM badges, health, lifecycle, tags and
  spend per row. So CAP-2 = the donut, CAP-3 = the filtered Customers list.
  Screenshots: `project_asset/demo/screenshots/` (02, 04, 05).
- **J1:** seeded and machine-verified (`v2_*` checks in `verify`).

## V3 — Revenue / Data Verified (spec answers)

### E1 — refunds: built, deterministic, rebuilt on every refresh

| Case | Shape (dates slide with the anchor) |
|---|---|
| **Cross-month refund** | $140 order placed **June 26** → **$90 refunded July 8** (order month ≠ refund month) |
| Partial refund, same month | $95 order July 9 → $28 refunded July 17 |
| On-hold order | $180, July 17, never paid |

(Order IDs change on each reseed — `verify --report` prints the current ones
under `v3_refunds` / `v3_on_hold_orders`.)

### E2 — "Data Verified": all seven answers (from the shipped code)

1. **Interactive?** Verified state: static green pill; hover shows a native
   tooltip with the deltas (`Δ x% / y% (threshold 1%)`). Failure state adds a
   **"View Details" button**.
2. **Failure state?** **Yes** — `Data Mismatch ⚠️`, amber (`--low`) or red
   (`--high`).
3. **Does it name a cause?** The badge itself: no — pass/fail + severity.
   **View Details opens a panel with three named buckets**: *Orders in
   unusual statuses*, *Partially refunded orders*, *Currency mismatch*, each
   row linking to the order. **Nuance the script must respect:** those
   buckets are *diagnostics*, not the trigger.
4. **What actually triggers failure:** the plugin computes the range's
   revenue twice (its cached SQL vs a fresh WooCommerce loop) and fails when
   they diverge ≥ 1% (5% = high). Real divergence sources: its 30-minute
   cache going stale after order edits, and timezone-edge orders. The
   Brief-§4 list (statuses/partials/currency) is what the details panel
   *inspects*, not what flips the badge.
5. **Forcible on demand?** Yes, honestly: load the Dashboard (warms the
   cache), edit/refund an order inside the visible range worth >1% of net,
   re-run within the cache window → badge flips to Mismatch and the details
   panel shows the flagged orders. (A dev-only alternative: the
   `pdwchs_data_integrity_threshold` filter.)
6. **Pro-only?** Yes — license-gated; keep the `PRO` tag in the scripts.
7. **Anywhere else?** No. Dashboard's Revenue Analytics panel only; the
   standalone Revenue page has no badge.

**Consequence for SHOT 3:** the resolution moment is recordable — badge →
Mismatch → View Details → the named bucket rows (our seeded partial refund
and on-hold order appear there permanently, even in the green state). Script
wording should be "the check names the orders to look at", not "names the
cause of the discrepancy".

### E3 — matched-range comparison (Jun 24 – Jul 23) — verified on both screens

| | Net revenue | Orders |
|---|---|---|
| Customer History → Revenue | **$4,021** | 55 |
| Analytics → Revenue (WooCommerce) | **$4,021** (gross $4,139 − returns $118) | 55 |

**They agree on matched ranges** — screenshots `09` and `10` in
`project_asset/demo/screenshots/` show both screens on this exact range.

**The real, honest mechanism (better than the fabricated-discrepancy risk in
B3):** the plugin attributes refunds to the **parent order's date**;
WooCommerce Analytics posts them on the **refund date**. Full matched ranges
containing both dates agree; **single-month views legitimately disagree by
exactly the $90 cross-month refund** — June: plugin $3,295 vs Woo $3,385;
July: plugin $3,249 vs Woo $3,159. Woo's daily table even shows the red
negative-revenue days (Jul 8: −$12 after the $90 return; Jul 17: −$28) —
DP-13 on camera with nothing faked. Never cut together the two screens on
different ranges (B3 stands).

### E4 / §11.1 — the win-back buttons (⚠️ flag for the founder)

Marketing Brief §10 is **contradicted by shipped code**:

- **"Send Win-Back Email"** opens the plugin's own compose screen and on
  submit **actually sends** via `wp_mail()`. Not a mailto link.
- **"Create Coupon"** **really creates** a WooCommerce coupon
  (`WINBACK-XXXXXXXX`, 20%, single-use, 30-day expiry, email-restricted)
  **and emails it** to the customer in one click.
- **"Automation Add-on"** (abandoned-carts panel) is positioning copy only —
  no such product exists anywhere in the codebase.

Until §10 vs product is reconciled, the standing rule holds: **keep the
Customer Details right-hand column and the abandoned-carts panel out of
frame** (already mandated by V4 §6). The buttons only render on high/critical
churn profiles — another reason V2/V4 never open one.

### Other V3 items

- **C1 / C2** (Woo's "paid orders" popover + Data status clock): untouched
  by the reseed — record per the spec. The popover is per-admin-user; if
  dismissed, a fresh admin user re-shows it.
- **Pre-flight 7 (duplicated submenu):** not reproducible in code — the menu
  registers exactly once. Plausible one-offs: a transient free+pro
  dual-activation moment, or a screenshot-stitching artifact. If it
  reappears live, screenshot it with the Plugins page visible.

## V4 — flat revenue / diverging split (brief answers)

**Blocker A cleared — the dataset now tells the story.** The month
step-through (Shot 6) reads, on the plugin Dashboard:

| Month | New | Returning | Guest | Total |
|---|---|---|---|---|
| March | $416 | $2,676 | $183 | **$3,275** |
| April | $551 | $2,478 | $185 | **$3,214** |
| May | $707 | $2,332 | $187 | **$3,226** |
| June | $846 | $2,260 | $189 | **$3,295** |
| July | $1,089 | $1,911 | $249 | **$3,249** |

Total flat within 2.5%; returning **−29%**; new **×2.6**; guest boring.
Order counts move with the money (returning order count declines alongside).
The five-month range (Mar 1 – Jul 23) shows a near-flat topline — the
▲22.5% growth arrow is gone.

- **Blocker B:** agreed — approach A (three-card strip stepped month by
  month). The data above is engineered exactly for it.
- **§11.2:** the footer "7.0.2" is **WordPress core**; the plugin (free and
  pro) is **3.0.0**. (The V2 run-sheet header has this backwards.)
- **§11.3 (free 90-day cap):** real — the free tier clamps every analytics
  read to the last 90 days (`pdwchs_clamp_start()`), collection is never
  limited, and the Dashboard shows the "most recent 90 days" banner when the
  clamp bites. This demo runs licensed Pro, so don't record the claim from
  this screen — it's verified from code.
- **§11.4 (guests):** steady $183–189/month with a small July uptick; never
  $0, never pulls focus.
- **Recording constraint:** shoot V4 in the back half of a month (anchor day
  ≥ 18), or pin the anchor (`scripts/demo-refresh.sh 2026-07-23`) — early in
  a month the current-month card can't yet be "full" and verify flags the
  V4 checks as warnings.

## What changed under the hood (for the developer)

- The seeder grew append-only cohorts on a **separate RNG stream** — every
  original V1 customer, order, date and score is byte-identical. Hero sheet
  unchanged: Daniel Mercer, health 60↓, churn 64% High, 51 days, $931,
  11 orders, rank #4; At-Risk list back to exactly 7 rows; alerts 4.
- New cohorts: Marcus +10 orders (F=5), 3 extra Champions, 2 loyal
  regulars, 4 fading-value "restock" customers (the returning decline,
  engineered churn-Low), 24 new-customer influx across Apr–Jul, 7 guest
  orders, 2 refund-case customers; catalog gained `product_brand` terms.
- `verify` gained 12 checks (`v2_*`, `v3_*`, `v4_*`) and widened three V1
  bands to the new census (customers 70–110, orders 220–400, health avg
  55–70); `--report` prints the V2 hero block, refund cases and month table.
- Teardown needs nothing new: refunds cascade-delete with their parent
  orders; everything is ledger-tracked as before.
- **Product bug found (not fixed in the product):** the churn batch job
  pages with `OFFSET` over a result set that shrinks as it processes, so
  with >50 eligible customers the oldest ones are skipped that run
  (self-heals across daily crons). The demo recalc now sweeps the stragglers
  itself. Worth a product ticket.

## Open items that are yours, not the data's

1. Send the script-writer: fresh screenshots of the RFM donut (Champions 8),
   the Segments page + Champions list, and Marcus's profile (V2 Part J).
2. Read the four E3 numbers off both screens once, matched ranges, and
   report them with the screenshots.
3. Raise the §10 contradiction (win-back email / coupon buttons + phantom
   "Automation Add-on") with the founder before V1/V9 churn footage is cut.
