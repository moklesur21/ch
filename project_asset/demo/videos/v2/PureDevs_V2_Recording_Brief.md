# SCREEN-RECORDING BRIEF — V2 (FINAL)

## "What Your WooCommerce Customers Search For Before They Buy"

**For:** the video editor
**From:** PureDevs
**Folder to create:** `v02-search-before-they-buy/`
**Every screen below verified against demo-store screenshots dated 25 July 2026.** No scene is written blind.

---

# PART 0 — FOR THE FOUNDER ONLY

> **Delete Part 0 before sending this to the editor.** Two blockers below need your decision first.

## 0.1 Two corrections I owe you

Both came from reading a low-resolution dashboard screenshot. The high-resolution Statistics pages corrected them.

| What I said last round | What's actually true |
|---|---|
| *"'watch' is the #1 search term and there is no watch in the catalogue."* | **Wrong.** You sell **Watch Cap — Logo** and **Watch Cap Beanie**. "watch" maps to real products. |
| *"Customers search 'pique', the product is called 'Piqua Polo' — a spelling mismatch. Don't fix it."* | **Wrong.** The product is **Pique Polo**. The spelling matches exactly. There is no typo and nothing to preserve. |

The replacements are stronger — see 0.6.

## 0.2 RESOLVED — Statistics is free, so the ratio table is the demo

Confirmed by the founder: **`Statistics` is in the free tier.** That settles it — scene-09 (`Viewed vs Purchased Ratio`) becomes the centrepiece of the demo rather than an upgrade coda, and the script's closing line survives intact:

> *"That's now two clicks and a list — and it was free before you upgraded anything."*

It also retires the time-window problem completely. Views, purchases and a computed ratio, one table, one window, sortable. No apples-to-oranges comparison, no date-range fiddling.

**Recommended demo spine:** Dashboard widgets as the establishing shot (what the store already recorded, no setup) → `Statistics → Viewed vs Purchased Ratio` as the payoff. Scene-04 and scene-06 stay in the recording set as the vertical-stack material for the shorts.

**One check worth sixty seconds before publish.** Marketing Brief §2 and §4 put "product-view stats" in the **Pro** column. Your answer contradicts your own authoritative feature doc, which means one of them is wrong and it isn't safe to guess which. Open the free build, confirm `Statistics → Viewed vs Purchased Ratio` is there, then **correct the Marketing Brief** — otherwise this contradiction propagates into every downstream format that reads it. If it turns out the doc was right, the video's "free" claim is the thing that breaks, and it breaks publicly.

**Scope note:** only the two named tabs get recorded. Other `Statistics` tabs — Conversion Funnel especially — are marketed as Pro, so the editor stays on Keywords and Viewed vs Purchased Ratio and nothing else. `Sessions` stays off-limits regardless of tier (0.8).

## 0.3 CLOSED — don't intercut the two search screens

The dashboard's Top Searches and `Statistics → Keywords` report different counts for the same keywords. Cause not investigated, by decision — and that's fine, there are innocent explanations and none of them need chasing.

It reduces to a single edit-side rule, which costs nothing:

> **Pick one search screen and stay on it. Never cut from Top Searches to Keywords, or back, inside the same video.**

Both are recorded; the choice happens in the edit. Given 0.2, `Statistics → Keywords` is the stronger pick — it's the full list rather than a top-five widget, and it sits beside the ratio table in the same menu.

Also on the Keywords page: **"the"** appears as a keyword with 2 searches. A stopword in a demo list looks unfinished — worth filtering before recording day.

## 0.4 Menu paths — RESOLVED, final

There is **no page called "Search Analytics."** Your wp.org readme claims one; the product has no such screen. Fix the readme at the next release.

| Data | Path | Tier |
|---|---|---|
| Top Searches, Latest Searches, Most viewed products, Most viewed Product categories | `Customer History → Dashboard` | **Free** |
| Full keyword list with counts | `Customer History → Statistics → Keywords` | **Free** (founder-confirmed; Marketing Brief disagrees — see 0.2) |
| Views, purchases and ratio per product | `Customer History → Statistics → Viewed vs Purchased Ratio` *(page heading reads "Product View Count")* | **Free** (same caveat) |
| Individual searches with customer names | `Customer History → Sessions → Live Searches` | Pro — **and off-limits, see 0.8** |

## 0.5 Label corrections the script still needs

| Script says | Product says |
|---|---|
| "Recent Searches" | **Latest Searches** |
| "Most-Viewed Products" | **Most viewed products** |
| "Top-Selling Products" | **Top Selling Products by This Month** |
| — | The ratio page's tab says *Viewed vs Purchased Ratio*; its heading says *Product View Count*. Name the tab, not the heading. |

## 0.6 The demo moment that actually works

From the ratio table, three products with almost identical view counts:

| Product | Views | Purchases | Ratio |
|---|---|---|---|
| Northline Hoodie — Logo | 99 | 72 | **72.73%** |
| Zip-Pocket Hoodie | 94 | 11 | **11.70%** |
| Pique Polo | 87 | 4 | **4.60%** |

Nearly the same number of people looked at each. One converts brilliantly, one poorly, one barely at all. That is the whole video in three rows — and it works perfectly under the no-spoken-figures rule, because the narration never reads a number. It says *"three products, almost the same number of people looked at each one — now look at the last column,"* and the viewer does the rest.

Two supporting notes:

- **Zip-Pocket Hoodie is also the joint-top keyword** on the Keywords page (26 searches). Most-searched, second-most-viewed, converts at under one in eight. That is the row to land on.
- **Watch Cap — Logo shows 8 views, 0 purchases, 0%.** Resist it. Eight views proves nothing, and using it would model exactly the over-reading the script warns against. If anything, say so out loud — it's a good thirty seconds on reading data honestly.

## 0.7 The free/Pro cut line on the Dashboard

The Dashboard is one long page: free widgets on top, Pro intelligence below.

> **Everything above the "Revenue Analytics" heading is free-tier. Everything from "Revenue by Customer Type" down is Pro.**

Below that line sit RFM segments, Health Distribution, Lifecycle Funnel, At-Risk Customers, Behavioral Segments, Churn Risk Overview and Abandoned Carts. None of it may appear in this video. The editor's instruction is mechanical — never scroll past Revenue Analytics — so he can follow it without knowing what Pro means.

## 0.8 Screens the editor must never open

`Customer History → Sessions → Live Searches` lists individual searches **against named customers**, with a **delete (trash) button on every row**.

Three reasons it is off-limits: it is Pro, it is V11's material, and one stray click destroys demo data permanently. It is written into Part 5 as a named prohibition.

Still outstanding from last round, unchanged: the customer profile carries **"Send Win-Back Email"** and **"Create Coupon"** buttons and recommends launching win-back campaigns and 20% discounts. U1 says the plugin does none of that. V2 never opens a profile, so it isn't blocking here — but it blocks V6 and V11, and it needs the guardrail wording and the product brought into agreement.

## 0.9 Housekeeping before recording day

- The **Plugins badge now reads ④** (it was ② last week) and the admin bar shows 4 updates. Clear them or the editor stops on scene-01, exactly as instructed.
- **Most viewed Brands is empty** — headers, no rows. Cut. Already absent from the wp.org 3.0.0 feature list.
- The RFM legend lists ten named segments plus an **"Undefined"** row. Ten named is correct; make sure nobody downstream counts eleven.
- Set the dashboard **date range** before recording, so the establishing shot and the ratio table don't visibly disagree on period.

## 0.10 The storefront has no search box — typing scene cut

Confirmed: the demo store's theme has no search field, and the search data was generated by requesting search URLs directly. That's mechanically the same thing a theme's search form submits — a search form and a direct search-URL request produce an identical request — so the recorded data is legitimate. Nothing to disclose, nothing to work around.

Two consequences:

**Scene-10 becomes plain background footage** of the shop page. No typing. That removes the only rule-exception in the brief, so the editor's instruction is now absolute: he never types anything, anywhere. Simpler and safer.

**The script needs a one-line patch.** Its damage section currently reads `[B-ROLL: a shopper types into a store search bar, pauses, closes the tab]` — unrecordable here. Swap it for the scene-10 shop-page footage, or a generic shot that doesn't claim to be Northline.

**One product question this surfaces** — not a video problem, but worth an hour: because no search form has ever been exercised on this store, capture through a live theme search form is untested, and capture through an **AJAX search plugin** is more untested still. FiboSearch and similar tools answer keystrokes with background requests rather than a search-page load, so terms typed into their autocomplete may never be recorded. Final Content Doc §10.6 already earmarked this for the blog FAQ; it's also the first question a technical reviewer will ask.

---

# PART 1 — SETUP, ONCE, BEFORE YOU RECORD ANYTHING

Hello — thanks for doing this. You don't need to know anything about WordPress. Everything below is click-by-click. **You are only recording what is already on screen. You never fix, configure, type, or change anything.**

If anything doesn't match this brief, **stop and message me.** Never guess. Stopping is always the right call and never causes a problem.

1. Log in with the **Sarah M. / Northline Supply Co.** account — never your own, never a developer account. Top right should read *"Howdy, Sarah M."*
2. Browser window **1920 × 1080**. Zoom exactly **100%** (`Ctrl/Cmd + 0` resets it).
3. Hide the bookmarks bar. Close every other tab and every other app.
4. Turn off all notifications — Slack, Mail, calendar, everything.
5. Look at the black bar at the top and the dark menu on the left. **If you see a red circle with a number in it** — beside "Plugins," or next to the circular arrows at the top — **stop and message me.** Do not click it.
6. Normal mouse speed. Move slowly and deliberately. No circling, no wiggling, no hovering over anything the brief doesn't mention.
7. Record one throwaway practice clip. Watch it back. Check the text is sharp and the pointer moves calmly.

---

# PART 2 — THE MOST IMPORTANT RULE

**The numbers on screen will not match anything anyone told you. That is normal and expected.**

This is a live demo store. Counts, products and dates change daily. The voiceover is recorded afterwards and **never reads a number out loud**, so what the numbers say does not matter.

| What you see | What you do |
|---|---|
| Different numbers from any example | **Keep recording.** Normal. |
| Different products listed | **Keep recording.** Normal. |
| Different search words | **Keep recording.** Normal. |
| Different dates | **Keep recording.** Normal. |
| A table or box with **no rows at all** | **Stop. Message me.** |
| A spinner that never finishes | **Stop. Message me.** |
| A red or yellow error message | **Stop. Message me.** |
| A box or page named something completely different from this brief | **Stop. Message me.** |

Short version: **different numbers are fine. Missing things are not.**

Because nobody reads the numbers aloud, viewers read the shapes themselves — so every list must sit still long enough to be read. That is why the hold times feel long. Please honour them. When in doubt, hold longer.

---

# PART 3 — SCENE TABLE

Record all of these, in order. One clip per scene, named exactly as shown. Some clips may not make the final cut — record them anyway.

| Scene | Screen (plain words) | How to get there | What to do | Hold | Must be visible before you stop | Stop and report if… |
|---|---|---|---|---|---|---|
| **scene-01** | Customer History dashboard, top of page | Dark left menu → **Customer History** → **Dashboard** | Land on the page. Don't scroll, don't click. | **6s** | Four boxes: Top Customers, Latest Orders, Top Selling Products by This Month, Top Searches | Page blank, still loading after 10s, or an error |
| **scene-02** | The **Top Searches** box | Same page | Move the pointer slowly to just outside the box and hold completely still | **8s** | Box title, column headings, every row of words and counts | Box has no rows |
| **scene-03** | **Top Selling Products by This Month** + **Top Searches** together | Same page | Frame both boxes in one shot — they sit side by side. Hold still. | **8s** | Both titles, all rows in both | Either box empty |
| **scene-04** | **Top Selling Products by This Month** alone, full width | Same page | Frame this box on its own, filling the width. Hold still. | **8s** | Title and all rows | Box empty |
| **scene-05** | The **Latest Searches** box | Same page. Scroll down **slowly, once**, until Latest Searches is centred | Scroll slowly, stop, hold still | **8s** | Title, the KEYWORD and DATE columns, every row | Box has no rows |
| **scene-06** | **Most viewed products** alone, full width | Same scroll position — it sits to the right of Latest Searches | Frame this box on its own, filling the width. Hold still. | **8s** | Title, PRODUCT NAME and VIEW COUNT columns, every row | Box has no rows |
| **scene-07** | Dashboard, slow scroll down to the money figures — **then stop** | Return to the top of the dashboard | Scroll down slowly and evenly. **Stop the moment you see the large heading "Revenue Analytics."** Hold. | **5s** at the stop | The heading "Revenue Analytics" at the bottom of frame | You scroll past it — discard and retake |
| **scene-08** | The full keyword list | Left menu → **Customer History** → **Statistics**. In the blue link row near the top, click **Keywords** | Let it load. Hold still. Then scroll slowly, once, to the bottom of the list and hold. | **8s** top, **6s** bottom | A list of words with numbers beside them | Page empty, errors, or there is no "Keywords" link |
| **scene-09** | Views compared with purchases | Left menu → **Customer History** → **Statistics**. Click **Viewed vs Purchased Ratio** | Let it load. Hold still on the top of the table. Then scroll slowly, once, to the bottom and hold. | **10s** top, **6s** bottom | Four columns: Product Name, View Count, Purchase Count, View vs Purchase Ratio — with rows in all four | Page empty, errors, or there is no such link |
| **scene-10** | The public shop page — background footage | Open a **new tab** and go to the demo store's public shop page, the normal storefront a customer sees | Let it load and hold still. Then scroll down slowly, once, through the products and hold at the bottom. Don't click anything. Close the tab. | **5s** top, **6s** bottom | The shop page with product pictures and names | Page won't load, or shows an error |

**scene-09 gets the longest hold in the brief.** It's the most important clip. Ten full seconds, motionless, before you touch anything.

**scene-10 is background footage.** It doesn't need to show anything in particular — just the shop as a customer sees it. Don't click a product, don't add anything to a basket, don't type.

---

# PART 4 — THE THREE SCENES THAT NEED EXTRA CARE

### scenes 04 and 06 — frame these full-width, as a matched pair

These two get stacked one above the other for a vertical, phone-shaped video. That only works if each list is recorded **on its own, filling the width** — not squeezed beside its neighbour.

One clip of **Top Selling Products by This Month** alone. One clip of **Most viewed products** alone. Same framing, same hold, same scroll speed. They need to look like a matched pair.

Scene-03, where two boxes share a frame, is a different shot for the long video. Record all three.

### scene-07 — the wall you must not cross

Partway down the dashboard is a large heading, **Revenue Analytics**, with big money figures under it.

**That heading is your floor. Never scroll below it — in this scene or any other.** Everything under it belongs to other videos and must not appear here, even for a fraction of a second during a fast scroll.

Scene-07 is deliberately a clip of you stopping there. Scroll down at a calm, even speed, stop as the heading reaches the bottom of the screen, and hold. If you overshoot, discard the clip and start again. No harm done.

### scene-09 — the most important clip in the set

A table with four columns. Some rows show a high number of views and a very low number of purchases. **That contrast is the entire video** — it needs to be readable and completely still.

Frame the whole table. Don't zoom, don't highlight, don't move the pointer across it. Ten seconds motionless, then one slow scroll to the bottom, then hold again.

---

# PART 5 — RECORDING RULES

- **One clip per scene**, named exactly `scene-01`, `scene-02`, and so on.
- **Start recording ~3 seconds before** your first move and **stop ~3 seconds after** your last. That padding gets trimmed later; without it the clip is unusable.
- **Scroll slowly, once, in one direction.** Never scroll up and down hunting.
- **Never open these**, for any reason:
  - **Sessions** (either "Live Sessions" or "Live Searches") — these pages have **delete buttons on every row** and one stray click destroys data permanently
  - **Settings**, **Account**, **Contact Us**
  - Any customer's name or profile page
- **Never click a link the scene table doesn't name** — not customer names, not product names, not blue links, however tempting.
- **Never type anything, anywhere, at any point.** There are no exceptions in this brief.
- **Never click a trash or bin icon.** Ever. If you see one, that's a page you shouldn't be on — back out and message me.
- **If a page looks different from this brief, stop and report.** Different *numbers* are fine (Part 2). A different *page* is not.
- If you redo a scene, keep the good take and delete the rest.

---

# PART 6 — DELIVERY

Put every clip in one folder:

```
v02-search-before-they-buy/
```

Add a plain-text file called `notes.txt` listing:

1. Any scene you couldn't record, and one sentence on why.
2. Anything that looked odd, even if you recorded it anyway.
3. The date and rough time you recorded.

Don't edit, trim, colour-correct or rename beyond the scene numbers.

**Thank you.** The hold times are the part people skip and the part that matters most. If every list got its full time, this will cut together well.

---

# PART 7 — FALLBACK NARRATION *(edit-side, not the editor)*

The data shifts, so the narration line gets chosen **after** watching the clip, never before.

### For scene-02 / scene-08 (the search lists)

| Recorded shape | Line that works |
|---|---|
| One term clearly ahead | "The top term is running well ahead of everything under it." |
| Top two close, then a drop *(dashboard on 25 July: 28 · 26 · 19 · 15 · 7)* | "The top two are neck and neck — then look how fast it falls away." |
| A cluster at the top, then a cliff *(Keywords on 25 July: 38 · 26 · 26 · 22 · 20 · then single digits)* | "Five words carry almost all of it. Everything after that is noise." |
| Flat, no clear leader | "No single winner. That flatness is the finding — demand is spread wider than the catalogue assumes." |

### For scene-09 (views vs purchases) — the strongest available line

> "Three products. Almost the same number of people looked at each one. **[PAUSE]** Now look at the last column."

It survives any data shift, because it points at the shape and lets the viewer read the ratios themselves. If the top three no longer have similar view counts, the fallback is:

> "Sort by that last column and the store rearranges itself. Attention on the left, outcomes on the right — and they don't line up."

### Never

Read a count aloud. Read a percentage aloud. Name a product's exact figures. The rule holds regardless of what the clip shows: **read the proportion, not the number.**

---

# PART 8 — SANITY CHECK

**Screens verified from screenshots, 25 July 2026:**

| Screen | Status |
|---|---|
| Customer History → Dashboard | ✅ Confirmed |
| Top Searches widget | ✅ Confirmed, populated |
| Latest Searches widget | ✅ Confirmed, populated |
| Most viewed products widget | ✅ Confirmed, populated |
| Top Selling Products by This Month | ✅ Confirmed, populated |
| Statistics → Keywords | ✅ Confirmed, populated — **but conflicts with Top Searches (0.3)** |
| Statistics → Viewed vs Purchased Ratio | ✅ Confirmed, populated, 14 items across 2 pages |
| Most viewed Product categories | ✅ Confirmed (not used) |
| Most viewed Brands | ⚠️ Exists but **empty** — cut |
| Sessions → Live Searches | ✅ Confirmed — **deliberately excluded**, Pro + deletion risk + V11's material |
| Public shop page | ✅ Resolved. **The storefront has no search box** (0.10) — scene-10 is background footage only and the typing scene is cut. |

**Dropped from the script's original flow:**

- **"Click into one search term."** Keywords in the Top Searches widget render as plain text; product names on the same page render as blue links. They are almost certainly not clickable. Scene-08 replaces that beat.
- **Most viewed Brands.** Empty, and absent from the shipped 3.0.0 feature list.

**Guardrails checked:**

- No gated feature appears: no zero-result report, no Search Gap. Both remain Release 1.5 / Release 2.
- No customer profile is opened, so the win-back and coupon buttons in 0.8 cannot reach a frame.
- No Sessions page is opened, so no named-customer search data and no delete button reaches a frame.
- The Pro dashboard stack is fenced off by a rule the editor can follow without knowing what Pro means.
- **Resolved this round:** `Statistics` is free (0.2), so the ratio table is a free-tier screen and the script's "free" claim holds. The Top Searches / Keywords count difference is closed as an edit-side rule — never intercut the two (0.3).
- **One open verification, not blocking recording:** Marketing Brief §2/§4 still lists "product-view stats" as Pro and needs correcting to match (0.2). Until it's corrected, two internal docs disagree about a claim this video makes on camera.
- Every stop-and-report trigger is structural — something the editor can see without interpreting it. He is never asked to judge a number.
- Menu paths come from the screenshots, not from the wp.org readme, which was wrong about this.
- **Freshness, 25 July 2026:** WooCommerce core is 10.9.4; 11.0 goes GA 28 July. Neither ships on-site search-term reporting — 11.0's reporting work is Analytics import retry, refund values in v3 sales-report date buckets, and stable visitor IDs. The claim this video rests on is **"not in core,"** and it holds. Re-confirm if recording slips past 28 July.

---

# PART 9 — WHAT'S LEFT

**Nothing blocks recording day. Nothing blocks the script.** Both blockers closed: `Statistics` is free (0.2), and the search-count difference is handled by an edit rule rather than an investigation (0.3).

**Before recording day, small:**

1. Clear the **Plugins ④** update badge.
2. Filter **"the"** out of the keyword list, or accept a stopword on camera.
3. Patch the script's damage-section B-roll marker (0.10) — it currently calls for a shopper typing into a search box, which this store can't produce.
4. Apply the three label corrections to the script (0.5): Latest Searches, Most viewed products, Top Selling Products by This Month.

**Before publish, sixty seconds:**

5. Confirm `Statistics → Viewed vs Purchased Ratio` appears on a **free** install, then **correct Marketing Brief §2/§4**, which still lists product-view stats as Pro (0.2). Two internal docs currently disagree about a claim this video makes on camera; the video is only right if the founder's answer is.

**Worth an hour, not blocking:**

6. Test search capture through a real theme search form and through an AJAX search plugin (0.10). The video name-checks FiboSearch, so this is the first thing a technical viewer will try.

**Still escalated, outside this video:** the customer profile's "Send Win-Back Email" and "Create Coupon" buttons contradict Universal Block U1 (0.8). Not a V2 problem. It blocks V6 and V11.
