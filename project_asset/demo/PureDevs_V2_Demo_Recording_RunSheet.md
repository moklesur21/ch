# PureDevs — V2 Demo Screen-Recording Run-Sheet
## For the developer producing the demo footage (long-form video + 4 shorts)

**Topic:** Find the Top 20% of Customers Driving 80% of Your Revenue
**Demo store:** ch.puredevs.com · Plugin build 7.0.2 (marketed as 3.0.0)
**Source scripts:** `PureDevs_V2_YouTube_Script_Top20.md` (long-form) + `PureDevs_V2_Shorts_Scripts_Top20.md` (shorts)
**Guardrail (applies to every frame):** the plugin **observes and reports** — it scores, sorts, ranks, shows. It never emails, recovers carts, or automates. Never record anything that implies otherwise.

---

# ⛔ READ FIRST — 3 blockers that make current recording impossible

The screenshots show the demo store is not yet in a state that supports this video. **Fix all three before recording**, or the footage won't match the script.

### Blocker 1 — There are ZERO Champions
Image 4 (Dashboard → "Customer Segments (RFM Analysis)") shows **Champions: 0**, Hibernating: 13, Lost: 0, most others 0–3. The entire V2 demo is "open the Champions segment → there's your top 20%." **An empty Champions list = no video.** You must seed Champions (details in Part A).

### Blocker 2 — No "5,5,5 Champion" profile exists
The profile you sent (John Doe, Image 3) is **RFM R 0/5 · F 3/5 · M 4/5**, badge **"At Risk"**, Health **10 / Critical**. The script says *"five, five, five — top marks on all three… a person worth protecting."* You need at least one customer whose profile actually shows a high RFM (ideally 5/5/5), a healthy Health Score, Loyal/Champion lifecycle, and Low churn risk. (Part A gives the exact spec.)

### Blocker 3 — Currency is Taka (৳), scripts are in USD
Every amount on screen shows **৳** (e.g., Total Spent ৳328). The video targets US/UK/AU $10–50k/month stores and the narration/overlays use **$**. Switch the demo store to **US Dollar** (WooCommerce → Settings → General → Currency) before recording, or the on-screen numbers will contradict the voiceover.

**Bonus cleanup:** John Doe's profile is internally contradictory — RFM/Health say "At Risk / Critical," but the Churn Risk Analysis panel says "0 / Low Risk / healthy and engaged." Any customer that appears on camera must be internally consistent. Fix or avoid this record.

---

# PART A — Demo-store data prep (do this first)

**Goal:** a believable store where the Champions segment is populated and at least one Champion is a clean 5/5/5 hero.

### A1 — Size & currency (recommended, serves all 12 videos)
- **Currency → USD ($).** Re-check that amounts render as `$` everywhere (Dashboard, profiles, segments).
- **Volume:** the current store shows ~6–19 customers and ~$723 total revenue — that reads as a sandbox. For an ICP of $10–50k/month stores, seed enough data that the numbers look real: a few hundred customers, a believable RFM spread, revenue in the thousands+. This is the "realistic dataset, same across all 12 videos" the pilot gate already requires.
- **Minimum viable (if full seeding isn't feasible for this video):** at least **8–20 customers in Champions** so the "list of names" reveal has substance, plus the hero below.

### A2 — Segment distribution (so the donut/Segments page looks real)
Target a plausible spread, e.g. Champions ~12, Loyal ~20, Potential Loyalist ~15, New ~10, Promising ~8, Needs Attention ~10, At Risk ~8, Cannot Lose ~3, Hibernating ~15, Lost ~6. Exact numbers don't matter — **Champions must be non-zero and visually meaningful**, and "Undefined" should be minimal.

### A3 — The hero Champion (this is the profile Click 3 opens)
Create/seed **one clear standout** with these properties:

| Field | Required value |
|---|---|
| Segment | **Champions** |
| RFM | **R 5/5 · F 5/5 · M 5/5** (recent order within days; 6+ orders; top spender, clearly above store average) |
| RFM badge on profile | **Champion** (not "At Risk") |
| Health Score | **80–95 / Healthy or Excellent**, trend Stable or Improving |
| Lifecycle | **Loyal Customer** (or Champion) |
| Churn Risk panel | **Low (0–20)**, high confidence — consistent with the healthy Health Score (no contradiction) |
| Financial Summary | Total Orders 6+, **Total Spent = highest in store**, sensible AOV |
| Behavioral Profile | 3–5 tags incl. **Brand Loyal**; note the ACTUAL tags shown so narration matches (don't promise tags that aren't there) |
| Name / email | realistic (e.g., a normal first+last name); `@example.com` is fine for a demo |
| Currency | **USD** |

Make **2–3 Champions** this clean if you can, so the Champions list doesn't have one hero and eleven blanks.

### A4 — Confirm the Segments page
I have the **Dashboard donut** ("Customer Segments (RFM Analysis)") but not the dedicated **Customer History → Segments** page. Please confirm that page shows the 10 named segments and lets you open **Champions** to a member list. If its layout differs from the donut, send me a screenshot and I'll tighten the run-sheet. Either way, you can record from whichever view shows the Champions list cleanest.

---

# PART B — Technical recording setup

| Setting | Long-form (16:9) | Shorts (9:16) |
|---|---|---|
| Capture resolution | 1920×1080, 60 fps | Record 1920×1080, **editor crops/zooms to 1080×1920** — OR do a focused re-record (see note) |
| Browser | Clean profile / incognito; **hide bookmarks bar**; window maximized | Same, but **zoom to ~125–150%** so panel text stays legible when cropped vertical |
| Zoom level | 100% (or 110% if text feels small at 1080p) | 125–150% on the target panel |
| Cursor | Visible; **enable click-highlight** (a subtle ring/spotlight on click) | Same |
| Motion | Deliberate — move, pause ~1 s on the target, click, let it settle ~1.5 s | Same, slightly snappier |
| Head/tail | Record **3–5 s extra** before the first action and after the last | Same |

**Hide before recording (all takes):**
- The **Freemius Debug** menu item (visible in Image 3) and any dev/debug notices.
- WP admin-bar clutter: update counts, comment/notification bubbles if possible.
- The green **"Upgrade ➤"** nag in the Customer History submenu if it's distracting (or crop it out in framing).
- Any browser extensions / OS notifications. Do-Not-Disturb on.

**Shorts legibility note:** the admin UI is dense; cropping a full 1080p desktop shot to vertical makes text tiny. For each shorts capture, **zoom the browser and frame the single panel** that matters (the Champions list, or the RFM bars), so it reads on a phone. If a cropped clip is unreadable, re-record that one screen zoomed-in.

**No audio needed from you** — narration is recorded separately by the founder and synced by the editor. Just capture clean screen video. (Mute system sounds.)

---

# PART C — Menu-path reference (from your screenshots — now the source of truth)

All navigation is in the left sidebar under the top-level **Customer History** menu, and mirrored in the horizontal tab bar on each Customer History page.

| Target screen | Exact path | Seen in |
|---|---|---|
| **Dashboard** (RFM donut, Top Customers, Revenue, etc.) | Customer History → **Dashboard** | Image 4 |
| **RFM Segments view** | Customer History → **Segments** (left submenu) *or* top tab **Segments** *or* the Dashboard "Customer Segments (RFM Analysis)" donut | Image 4 (donut) |
| **Champions list** | On the donut: **click the Champions slice** ("Click any segment to view customers") · or open **Champions** inside the Segments page | Image 4 |
| **Champion profile** | Customer History → **Customers & Users** → click the customer name → **Customer Details** page | Image 3 |
| **Top Customers widget** (Free) | Customer History → **Dashboard** (top-left card) | Image 4 |
| Churn Prediction (NOT used in V2) | Customer History → Churn Prediction | Images 1–2 |

*Note: Churn Prediction (Images 1 & 2) belongs to the V1 churn video, not V2. Don't include it here.*

---

# PART D — Master capture list (record once, reuse across video + shorts)

Record each of these as its own clean clip. The video and all four shorts are assembled from them.

### CAP-1 — Establishing: "inside WordPress, on your own server"
- **Path:** Customer History → Dashboard.
- **Action:** Land on the Dashboard. Slow, smooth **scroll down ~1 screen and back up**, or just hold on the top with the WP admin chrome visible (so it's obviously inside wp-admin).
- **Must be visible:** the WordPress left sidebar + "Customer History" active + the dashboard header.
- **Hold:** 6–8 s of usable footage.
- **Purpose:** video opening of the demo ("running right inside WordPress… your data never leaves your store").

### CAP-2 — RFM Segments distribution (the 10 named segments)
- **Path:** open the **Segments** view (or park on the Dashboard "Customer Segments (RFM Analysis)" donut).
- **Action:** Land on the segment distribution. Slowly move the cursor across the segment legend (Champions, Loyal, Potential Loyalist, New Customer, Promising, Needs Attention, At Risk, Cannot Lose, Hibernating, Lost). **Hover over "Champions"** and pause.
- **Must be visible:** all **10 named segments** with counts; **Champions count > 0**.
- **Hold:** 8–10 s (long enough for the "scores every customer on three things… ten named segments" line).
- **Used by:** Video Click 1 · Shorts S1, S2, S4.

### CAP-3 — Champions list (THE reveal)
- **Path:** from CAP-2, **click Champions** (donut slice or segment row).
- **Action:** The Champions member list loads. Let it settle. Slowly move the cursor down the list of names (don't click yet). This is the money shot — the "list of names."
- **Must be visible:** a **populated, ranked list of Champion customers** (names + spend/orders). Ideally 8–20 rows.
- **Hold:** 8–12 s.
- **Used by:** Video Click 2 · Shorts S1, S2, S4.
- **Editor note:** this is where the "Remember the twenty people you couldn't name? This is the list." overlay lands.

### CAP-4 — Champion profile (RFM 5,5,5 + financial + tags)
- **Path:** from the Champions list, **click the hero Champion's name** → Customer Details page (Image 3 layout).
- **Action, in order (this is the on-camera scroll path):**
  1. Land at top — **Financial Summary** panel visible (Total Orders, Orders average, **Total Spent** = highest). Hold ~3 s.
  2. Scroll to the **RFM Analysis** panel. Pause on the three bars — **R 5/5, F 5/5, M 5/5**, badge **Champion**. Hold ~4 s. (This is "five, five, five — top marks on all three.")
  3. Scroll to the **Behavioral Profile** panel — the tag chips (e.g., Brand Loyal, etc.). Hold ~3 s.
  4. (Optional) brief pause on **Customer Health Score** (healthy) and **Customer Lifecycle** (Loyal) if the editor wants them.
- **Must be visible:** high RFM (5/5/5), highest Total Spent, real behavioral tags, healthy status. **No "At Risk"/"Critical" badges on this customer.**
- **Hold:** capture ~20–25 s of scrolling/pausing so the editor can pick.
- **Used by:** Video Click 3 · Shorts S2, S3.

### CAP-5 — Top Customers widget (Free) — *optional cutaway*
- **Path:** Customer History → Dashboard → **Top Customers** card (top-left).
- **Action:** Hold on the card (ranked by Total Amount / Total Orders).
- **Hold:** 4–5 s.
- **Used by:** optional in video/S1 to show "even the free view ranks top spenders." Skip if not needed.

### CAP-6 — Spreadsheet B-roll (the "old way")
- **Not a plugin screen.** Export the demo store's orders to CSV (WooCommerce → Orders → Export, or the plugin's CSV export), open in Excel/Google Sheets.
- **Action:** Show a **messy orders CSV** — scroll it, then show a manual SUM of a "spend" column being typed. Look slightly tedious on purpose.
- **Hold:** 6–8 s (scroll) + 4–5 s (the SUM).
- **Used by:** Video damage B-roll · **Short 4 hook** (this is the opening frame of S4).

### CAP-7 — "Revenue is one click" B-roll
- **Path:** Customer History → **Revenue** (or the Dashboard **Revenue Analytics** card with the big **Net Revenue** number + "Data Verified ✓").
- **Action:** Land on the revenue number instantly (one click feel). Hold on the headline figure.
- **Must be visible:** a clean **Net Revenue** headline in **USD**.
- **Hold:** 4–5 s.
- **Used by:** Video contradiction/damage ("revenue is one click; the people are a spreadsheet project").

---

# PART E — Long-form video demo run-sheet (script window 2:00–5:00)

Pair each clip with the narration it plays under (founder records narration separately). Capture generous holds; the editor trims to the voiceover.

| # | Video time | Clip | On-screen action | Narration it underlays | Overlay |
|---|---|---|---|---|---|
| 1 | 2:00–2:12 | **CAP-1** | Dashboard establishing, gentle scroll | "This is PureDevs Customer History, running right inside WordPress… your customer data never leaves your store." | — |
| 2 | 2:12–2:35 | **CAP-2** | Open Segments; hover the 10 segments; pause on Champions | "Click one. I open the RFM Segments view… scores every customer on recency, frequency, and spend… sorts your entire base into ten named segments." | `RFM = Recency · Frequency · Monetary` then `10 named segments — scored automatically` |
| 3 | 2:35–3:05 | **CAP-3** | Click Champions; the named list loads; cursor drifts down the names | "Click two. I open the segment called Champions. …Remember the twenty people you couldn't name without a spreadsheet? This is the list." | `The Champions list = the names you couldn't produce` |
| 4 | 3:05–4:05 | **CAP-4** | Click the hero → profile; scroll Financial Summary → RFM 5,5,5 → Behavioral tags | "Click three. …their financial summary… behavioral tags… their RFM score: five, five, five. Top marks on all three. This is a person worth protecting." | `RFM 5,5,5 — recent, frequent, high-value` on the RFM pause |
| 5 | 4:05–4:35 | hold **CAP-4** (or wide) | Stay on the profile / pull back | Guardrail line: "the plugin doesn't email this person for you… It shows you who they are… what you do next is your call." | *(no overlay — let the line breathe)* |
| 6 | 4:35–5:00 | **CAP-3** again (or CAP-6 flash) | Back to the Champions list; optional 1 s flash of the spreadsheet for contrast | "No export. No CSV. …visible in the time it took me to say this sentence." | — |

**Hard rules for this segment:** real screen recording only (no mockups); no new stat cards inside the demo (the screen carries it); the demo must visibly **resolve the opening contradiction** at step 3.

---

# PART F — Shorts demo run-sheets (the 0:20–0:50 window each)

Each short is 45–60 s; the demo is the middle 30 s. Hook and outcome frames are separate (see the shorts script for VO). Below is exactly what to record for the demo window. **Zoom in / frame the panel** for legibility.

### Short 1 — "The Top 20% in 60 Seconds" — 2 clicks
| Time | Clip | Action | Overlay |
|---|---|---|---|
| 0:20–0:30 | **CAP-2** | Open Segments; pause on the distribution | `10 segments, scored automatically` |
| 0:30–0:45 | **CAP-3** | Click **Champions**; the named list loads | `= your top customers, ranked` |
| 0:45–0:50 | hold CAP-3 | Rest on the list | — |
*Hook frame (0:00–0:05) uses a still of CAP-2 or CAP-3 with the overlay "YOUR TOP 20%."*

### Short 2 — "What 'Champions' Actually Means" — 3 clicks
| Time | Clip | Action | Overlay |
|---|---|---|---|
| 0:20–0:27 | **CAP-2** | Open Segments | — |
| 0:27–0:34 | **CAP-3** | Click **Champions** | — |
| 0:34–0:50 | **CAP-4** | Open hero profile; **hold on the RFM bars (5/5/5)** | `R · F · M` then `5,5,5` |
*Emphasis is the RFM panel — frame it tightly so R/F/M and the /5 scores read on a phone.*

### Short 3 — "Your Most Valuable Customer Isn't Who You'd Guess" — 2 clicks
| Time | Clip | Action | Overlay |
|---|---|---|---|
| 0:20–0:28 | **CAP-3** | Open **Champions** list | — |
| 0:28–0:50 | **CAP-4** | Open the hero profile; scroll **Total Spent → order history → RFM 5,5,5 → tags** | `lifetime spend, not one order` |
*Point: value is built from many orders over time, not one big purchase. **Do NOT invent a dollar contrast** — use exactly what the profile shows.*

### Short 4 — "Stop Rebuilding That Spreadsheet" (bonus) — 2 clicks
| Time | Clip | Action | Overlay |
|---|---|---|---|
| 0:00–0:05 | **CAP-6** | (hook) messy CSV / manual SUM | *(hook text: "if this is how you find your best customers…")* |
| 0:20–0:30 | **CAP-2** | Open Segments | — |
| 0:30–0:50 | **CAP-3** | Click **Champions**; rest on the list | `no export, always current` |

---

# PART G — On-screen overlay / annotation master list

Give these to the editor; they're added in post (Canva/CapCut), not recorded.

| Overlay text | Where it appears |
|---|---|
| `RFM = Recency · Frequency · Monetary` | Video step 2; Short 2 |
| `10 named segments — scored automatically` | Video step 2; Short 1 |
| `The Champions list = the names you couldn't produce` | Video step 3 |
| `= your top customers, ranked` | Short 1 |
| `RFM 5,5,5 — recent, frequent, high-value` / `5,5,5` | Video step 4; Short 2 |
| `lifetime spend, not one order` | Short 3 |
| `no export, always current` | Short 4 |
| Thumbnails (no founder face): `YOUR TOP 20%` · `WHAT MAKES A 'CHAMPION'?` · `NOT WHO YOU THINK` · `DITCH THE SPREADSHEET` | S1–S4 thumbnails |

---

# PART H — Guardrail & QA checklist (before you hand off footage)

- [ ] **Champions segment is populated** and the hero profile shows **RFM 5/5/5 + healthy + highest spend** (blockers 1 & 2 cleared).
- [ ] **All amounts are in USD** (blocker 3 cleared); no ৳ anywhere on screen.
- [ ] Any customer shown on camera is **internally consistent** (no "At Risk" + "healthy" clash).
- [ ] **Nothing implies automation:** don't open/record the churn "Email this customer" action, don't show the Abandoned-Carts "Automation Add-on" line, don't click any send/recover control. Observe-and-report surfaces only.
- [ ] **Real screen recording only** — no mockups, no fabricated numbers, no invented dollar contrast in Short 3.
- [ ] **Freemius Debug / upgrade nags / notification bubbles hidden**; clean browser chrome.
- [ ] Menu paths match Part C exactly (Segments; Customers & Users → Customer Details).
- [ ] Each clip has 3–5 s head/tail and the hold times in Part D.
- [ ] Shorts captures are **zoomed/framed for phone legibility**.
- [ ] Demo store dataset is the **same one you'll use for all 12 videos** (channel coherence).

---

# PART I — Deliverables & file naming

Deliver raw clips (not edited) named by capture ID so the editor can match them to the scripts:

```
CAP-1_dashboard-establishing_16x9.mp4
CAP-2_rfm-segments_16x9.mp4
CAP-3_champions-list_16x9.mp4
CAP-4_champion-profile_16x9.mp4
CAP-5_top-customers_16x9.mp4        (optional)
CAP-6_spreadsheet-broll.mp4
CAP-7_revenue-oneclick_16x9.mp4
```
- If you record zoomed re-takes for shorts, suffix `_vertical` or `_zoom`.
- One folder per video: `/V2_TopdCustomers/…`. Keep the raw exports; the editor trims.

---

# PART J — What I still need from you

1. **Confirm the data is seeded** (Champions populated + one 5/5/5 hero + USD). Ideally send a fresh screenshot of the **Dashboard RFM donut** (Champions > 0) and the **hero Champion's profile** so I can sanity-check before you record.
2. **Segments page screenshot** — the dedicated Customer History → Segments view (and the Champions list opened). I only have the Dashboard donut; a screenshot lets me pin exact click targets in Parts D–F.
3. Tell me the **hero Champion's actual behavioral tags** once seeded, so I can lock the narration wording (the script currently says "Brand Loyal / Weekend Shopper" as placeholders — we'll swap to what's really on screen).

Everything else (paths, timings, overlays) is set. Once 1–3 are in, this run-sheet records start to finish with no guesswork.

---

*V2 demo run-sheet — July 2026. Built from the four demo-store screenshots. Fix the three blockers, prep the data per Part A, then record the seven captures in Part D and assemble per Parts E–F. Observe-and-report guardrail holds in every frame.*
