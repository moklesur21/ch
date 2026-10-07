# V1 Placement Kit — How much of my WooCommerce revenue comes from returning customers?

**Version 1.4 · 7 Oct 2026** · Demo Video Prompt Pack v3, FILE 5 · supersedes v1.3
From `PureDevs_V1_Script_v1_2.md`, `PureDevs_V1_Demo_Brief_v1_2.md` and `PureDevs_V1_Recording_Brief_v1_4.md`, filled from the final master.
**Final master:** `recordings/pd v1 final edit -14LUFS.mp4` — **1:40** (99.8 s), 2560 × 1440, −14.0 LUFS. Step-6 audit: **PASS** (6 Oct 2026).
**Question page URL (approved):** https://puredevs.com/questions/woocommerce-new-vs-returning-customers/

**Where V1 is embedded:** /pricing (above the plans) · the question page · the LP-Category variant (AG1). In each, the first link under the video must be "Start the trial" → /pricing. The main homepage hero takes V8 instead: every homepage button goes to WordPress.org, which would make V1's "the link below starts the trial" untrue. Embeds are YouTube embeds. If a page ever self-hosts the file instead, use an H.264 copy: the master is HEVC, which Firefox doesn't play.

## 0. Publish-day values (from the final master)

| Field | Value |
|---|---|
| Length | **1:40** (filled in §3 and §6) |
| JSON-LD `duration` | `PT1M40S` |
| Upload file | `recordings/pd v1 final edit -14LUFS.mp4` (not the editor's `pd v1 final edit.mp4`, which is 3 dB louder and peaks above 0 dBTP) |
| Thumbnail | Built from `recordings/answer-frame-crop.png` to Script v1.2 §C: 1280 × 720, under 2 MB, the question in three lines, a thin outline on Returning, "Demo store" |

**YouTube chapters** — these replace the placeholders in Script v1.2 §B, timed to where each beat's voice starts in the master. Every chapter is at least 10 s.

```
00:00 How much revenue comes from returning customers — and where to click
00:21 New, returning and guest revenue, side by side
01:04 The decision it gives you
01:28 Free vs Pro
```

**Beat starts in the master** (voice onsets; picture cuts land within a few frames):

| Beat | Starts | First words |
|---|---|---|
| 1 | 0:00.0 | "How much of your WooCommerce revenue…" |
| 2 | 0:04.9 | "This is a WooCommerce demo store…" |
| 3a | 0:21.0 | "Here's the split." |
| 3b | 0:36.0 | "One date range, three cards." |
| 3c | 0:55.1 | "Now read the proportion, not the number." |
| 4a | 1:04.3 | "So here is the decision this gives you." |
| 4b | 1:16.2 | "And look at the biggest card." |
| 5 | 1:28.2 | "The plugin doesn't decide for you." — "The free version…" 1:32.7 · "This split is Pro." 1:35.9 · end card from 1:37.6 |

---

## 1. The 20–30 s cut

**Made:** `recordings/V1_cut_24s.mp4` — 23.5 s, 2560 × 1440 H.264, −14.0 LUFS, built with ffmpeg from the master and `end-card.mp4` exactly as the table below says (short audio fades at each join; the end card holds about 2 s after the last word, with silence). Video files stay out of git.

Beat 1 + the answer frame + the beat-4 decision + the end card. Beat 2 is skipped. About **24 s**. Take picture and voice together from the master (it already carries the zooms and captions); put a 0.2 s audio crossfade at each join so the music bed doesn't jump.

| Cut time | Master source (final) | Picture | Voice (from the master) | Caption |
|---|---|---|---|---|
| 0:00–0:04.9 | 0:00.0–0:04.9, beat 1 | The master's opening: answer frame, then the Returning card highlighted. Lower third **PureDevs Customer History**; **Demo Store** label | How much of your WooCommerce revenue comes from returning customers? | As in the master |
| 0:05–0:07.5 | 1:04.3–1:06.8, beat 4a, first sentence only | The master's picture at 1:04 (the dashboard) | So here is the decision this gives you. | As in the master |
| 0:07.7–0:17.7 | 1:18.2–1:28.2, beat 4b from "Returning is the biggest…" (skip "And look at the biggest card.") | The master's Returning-card zoom | Returning is the biggest — so your next hour belongs to the customers who already buy from you. That's where the biggest share of this revenue comes from. | As in the master |
| 0:18–0:22 | 1:35.9–1:39.8, beat 5's last two sentences | `recordings/end-card.mp4` (the tool's card) | This split is Pro. The link below starts the trial. | **Start the trial** |
| 0:22–0:24 | — | End card held; music fades out | — | — |

- **Nothing to re-record.** All voice comes from the master.
- **Never under 11 s.** The cut is about 24 s.
- The guest clause is left out to stay under 30 s.
- The "Demo store" label replaces beat 2's "demo store", which the cut skips.
- No spoken figures. One CTA.
- Retargeting ads carry their own CTA button to /pricing, so "the link below" holds.

**Optional vertical 9:16.** Frame 1080 × 1920. Question text in the top third, 72 px. Place the three-box strip across the middle at full width; if the amounts aren't readable, use scene-06 (the biggest box) for the decision line. Captions in the bottom third, at least 60 px. Short title: "How much of my WooCommerce revenue comes from returning customers?" (66 characters).

---

## 2. Question page

| Field | Content |
|---|---|
| Slug | `/questions/woocommerce-new-vs-returning-customers/` |
| H1 | How much of my WooCommerce revenue comes from returning customers? |
| Meta title (56 / 60) | WooCommerce New vs Returning Customer Revenue \| PureDevs |
| Meta description (145 / 155) | See new, returning and guest revenue side by side for the same dates, inside your own WordPress admin. A short demo of PureDevs Customer History. |
| Video | YouTube embed of the master; poster = the Script v1.2 §C still |
| CTA button (one) | **Start the free trial** → https://puredevs.com/pricing · small text under it: "30 days, no card needed." |

**Written walkthrough** (from the transcript; no demo figures):

1. Open **Customer History → Dashboard** and scroll to **Revenue Analytics**. This is a Pro report, and it runs inside your own WordPress admin: self-hosted, nothing leaves your server.
2. Under **Revenue by Customer Type** you get three cards — New Customers, Returning Customers and Guest Orders — each with its revenue and the number of orders behind it. New and Returning count customers with an account; guest orders are placed without one.
3. One date range at the top drives all three, so you're always comparing the same dates.
4. WooCommerce's own Orders report can show orders from new or returning customers, one filter at a time. Customer History adds new, returning and guest revenue side by side for the same dates.
5. Read the proportion, not the number: which card is biggest, and where the guest card sits.
6. If returning customers bring the biggest share, your next hour goes to the customers who already buy from you. If new customers do, bringing them back is the gap. If the guest card is big, decide whether to encourage accounts at checkout — repeat guests never show as returning.
7. The plugin doesn't decide for you. It shows you where the money comes from.
8. The free version shows your repeat purchase rate; this split is Pro.

**Screenshots.** Take frames from the recording, never the Brief's screenshots, which carry red callouts. Label each "Demo store data".

| # | File (see §7) | Caption | Alt text |
|---|---|---|---|
| 1 | V1-shot-1-path.png | Customer History → Dashboard, inside your own wp-admin. | WordPress admin with the Customer History menu open and the plugin's Dashboard tab selected (demo store). |
| 2 | V1-shot-2-split.png | New, returning and guest revenue, side by side for the same dates (Pro). | Revenue by Customer Type section with three cards — New Customers, Returning Customers, Guest Orders — each showing revenue and order count (demo store). |
| 3 | V1-shot-3-free-rate.png | The free version shows your repeat purchase rate (registered customers only). | Revenue page with five summary cards; the last card is Repeat Purchase Rate, captioned "Registered customers only" (demo store). |

**FAQ** (from Demo Brief v1.2 §8 and the research's buyer doubts):

- **Does the free version show this split?**
  No, the new, returning and guest split is Pro. The free version shows your repeat purchase rate for registered customers, for the most recent 90 days.
- **How does it decide who is new or returning?**
  It looks at customers with an account, for the dates you pick.
  - **New:** their first paid order falls inside those dates. Every order they place in that period counts as New, even a second or third.
  - **Returning:** they ordered in that period, and their first paid order came before it.

  Only completed and processing orders count, and revenue is net of refunds. Days follow GMT, not your site's timezone. Results can be up to 30 minutes behind.
- **How are guest orders handled?**
  Any order placed without an account goes in Guest Orders. The plugin doesn't match guests by email, so a guest who buys twice stays in Guest Orders and never shows as returning. If that shopper later creates an account, their old guest orders stay in Guest and their first order with the account counts as New. The exception: if WooCommerce links their old guest orders to the new account, those orders count toward that customer. The plugin never changes your orders.
- **Does it only count orders placed after I install it?**
  No. It reads your existing WooCommerce orders, so earlier orders are included once Pro is active.
- **Does my data leave my site, or slow my store down?**
  It's self-hosted: your customer and order data stays in your own WordPress database, and the plugin makes no outbound requests of its own. The only outside contact is Freemius, for licensing and its optional opt-in. The weekly digest email to you is on by default; switch it off in Customer History → Settings. Customer notes work with WordPress's personal-data export and erase tools; tracked visit rows are not part of them yet. The report runs in your WordPress admin and caches its results for 30 minutes. On your storefront the plugin records activity server-side, with no JavaScript tracker.

**VideoObject JSON-LD** (alongside the planned SoftwareApplication JSON-LD):

```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "How much of my WooCommerce revenue comes from returning customers?",
  "description": "A demo of PureDevs Customer History: new, returning and guest revenue side by side for the same dates, inside WordPress. Recorded on a demo store with synthetic data.",
  "thumbnailUrl": ["[THUMBNAIL URL]"],
  "uploadDate": "[YYYY-MM-DD]",
  "duration": "PT1M40S",
  "embedUrl": "https://www.youtube.com/embed/[VIDEO ID]",
  "publisher": { "@type": "Organization", "name": "PureDevs", "url": "https://puredevs.com/" }
}
```

---

## 3. Email line

**Line** (plain text):

> 1:40 video: how much of my WooCommerce revenue comes from returning customers? https://puredevs.com/questions/woocommerce-new-vs-returning-customers/

**Where it goes:**
- **Trial 3** ("the split your revenue chart hides"), after the first paragraph, once Trial 3 is fixed:
  - path → "Customer History → Dashboard → Revenue Analytics";
  - cut the abandoned-carts paragraph.
- **The Week 7 pre-BFCM email** to free users and trials (strategy §6.4).

No dollar amounts. It links to the question page, never to YouTube.

**Trial terms (30 days, no card) settle one lifecycle choice:** Trial 5 → **Variant A** (the trial lapses to Free). Delete Variant B.

---

## 4. WordPress.org

**NOT FOR README.** V1 is Pro-answered. The readme video is V13.

---

## 5. In-plugin poster

| Field | Content |
|---|---|
| Poster text (7 words) | How much revenue comes from returning customers? |
| Link text | Watch the demo on puredevs.com |
| Image | The Script v1.2 §C still, **bundled inside the plugin**. No remote image, no iframe |
| Opens | The question page, in a new tab |
| Where | 1st choice: the Freemius trial / pricing screen (strategy §5.2) — `[OPEN — dev: can the plugin add a block to that screen?]`. Otherwise: the free Revenue page, beside the Repeat Purchase Rate card. That's a product change for the next release |
| Behaviour | One small, dismissible card. Never on every page. V16 replaces it on the trial screen once V16 exists |

---

## 6. Capterra and G2 media

**Video caption:**
> How much of my WooCommerce revenue comes from returning customers? A 1:40 demo of Customer History's Revenue by Customer Type, recorded on a demo store.

**Screenshot captions, in order:**
1. Customer History → Dashboard, inside your own wp-admin.
2. Revenue by Customer Type: new, returning and guest revenue side by side for the same dates (Pro). New and Returning count customers with an account.
3. The free version shows your repeat purchase rate (registered customers only).

---

## 7. Shot list for the editor

**Screenshots**, exported as PNG at 1920 px wide from the raw clips in `recordings/` (3840 × 2160, no captions or zooms), never from the edited master:

| File | Source frame | Crop |
|---|---|---|
| V1-shot-1-path.png | scene-02: Customer History highlighted in the left menu, Dashboard tab active | Left menu + tab bar + page title "Dashboard" |
| V1-shot-2-split.png | scene-01, or `answer-frame-crop.png` directly | Heading "Revenue by Customer Type" + the three boxes. Four top boxes and badge out (Q2) |
| V1-shot-3-free-rate.png | scene-08 (recorded on Pro; the card row is the same template in both builds) | Page title "Revenue" + the row of five boxes, including "Registered customers only". The crop starts below the tab bar, so nothing Pro-only is in the picture |

**Made (6 Oct 2026), in `recordings/`:** `V1-shot-1-path.png` (1920 × 620, scene-02 at 13 s, before the cursor reappears), `V1-shot-2-split.png` (1920 × 192, from `answer-frame-crop.png`), `V1-shot-3-free-rate.png` (1920 × 254, scene-08 at 1 s, crop starts below the tab bar). Each carries a small "Demo store data" label placed off the cards. GIFs: `V1-split.gif` (1200 × 81, 56 KB) and `V1-split-800.gif` (800 × 54, 29 KB) — scene-04 from 4.2 s for 7.4 s, 12 fps, looping. **Use the 1200 px GIF:** the three cards span the whole widget, so at 800 px the card labels are about 6 px tall and only the amounts read; at 1200 px both read. No three-card strip is legible displayed at 600 px — show it at full column width.

**GIF** (under 2 MB) for the question page and in-plugin use:
- **Source:** scene-04, from the cursor arriving under NEW CUSTOMERS to the cursor resting under GUEST ORDERS. About 5 s.
- **Crop:** the three boxes only, 800 px wide, 10–12 fps.
- **Loop:** the last frame jumps back to the first.
- **Legibility:** labels and amounts must read at 600 px wide.
- **Not in the plain-text emails.**

---

## 8. Ad match (strategy §6.2, AG2 Questions)

The existing copy matches this page, so no new headlines or descriptions.

| Use | Copy | Characters |
|---|---|---|
| Headline, pin to position 1 | New vs Returning Revenue | 24 / 30 |
| Headline | PureDevs Customer History | 25 / 30 |
| Headline (replaces "Your Data Stays On Your Site": the default-on digest email makes that absolute untrue; V8 Claim Audit finding 4) | Data Stays In Your Database | 27 / 30 |
| Headline, unlocked (Q4: 30 days, no card) | Start Your Free Trial | 21 / 30 |
| Headline (final length 1:40 — under 2:00, so it holds) | Watch The 2-Minute Demo | 23 / 30 |
| Description | You decide what to do. The plugin shows you where the money comes from. | 71 / 90 |
| Description (final length 1:40 — holds) | Watch a 2-minute demo on a working WooCommerce store, then try it on your own orders. | 85 / 90 |

- **Don't use Description 1:** it names features this page doesn't show.
- **Keywords:** in Google Ads, give `[woocommerce new vs returning customers]` and `[woocommerce returning customer report]` this page as their keyword-level final URL.
- **Optional seeds**, marked "check in Keyword Planner": `[woocommerce new vs returning customers report]` · `[woocommerce returning customers]` · `[woocommerce revenue breakdown new returning]`.
- No competitor names, price, recovery language, or "identifies repeat buyers".

---

## 9. Sanity check (FILE 6 audit list, every section)

| # | Section | Finding | Rule | Status |
|---|---|---|---|---|
| 1 | §1 cut | No spoken figures; one CTA; about 24 s, timed from the final master; "Demo Store" label; option A (the recorded store) | U2.1, U5, U7 | PASS |
| 2 | Embeds | V1 appears only where the first link under it is the trial; homepage hero → V8 (audit finding 5). Self-hosted embeds need an H.264 copy | U7 | PASS |
| 3 | §2 walkthrough | Honest sentence says "orders" (finding 1). Account qualifier and guest reason come from dev's code read (Q1). **Re-checked 6 Oct 2026:** WooCommerce 11.1.2 is still the latest release, and its Orders report still has the new / returning customer-type filter (core `Admin/API/Reports/Orders`) | U2.2, U5 | PASS — re-check on publish day if a newer WooCommerce has shipped |
| 4 | §2 FAQ | Definitions, GMT days, net-of-refunds and guest handling all come from dev's code read. The FAQ 5 speed line is corrected (finding 3); the Freemius qualifier is added (finding 4) | U5, U3 | PASS |
| 5 | §2 FAQ | Dev's copy rule: never "identifies repeat buyers" without the qualifier. The FAQ says who is counted and who isn't | Q1 | PASS |
| 6 | §2 CTA | "30 days, no card needed" in button text: confirmed (Q4); changeable page text | U7 | PASS |
| 7 | §3 | Email line correct. Trial 3 needs its two fixes first. Trial 5 → Variant A | Standing decisions | Open (lifecycle file) |
| 8 | §4 | NOT FOR README | Pack §4 | PASS |
| 9 | §5 | 7 words; bundled; new tab; modest | Pack §5 | PASS. The slot needs a dev answer |
| 10 | §6–§7 | Tier and demo store stated; KPI row and badge cropped; GIF under 2 MB. Shot 3's alt text no longer says "Free Revenue page" (the frame comes from the Pro build; the crop is build-neutral) | U5, Q2 | PASS |
| 11 | §8 | Within 30 / 90. Headline 10 unlocked by Q4. "2-minute" copy holds: final length 1:40 | U7 | PASS |
| 12a | §0 | The beat-5 free-version shot in the master shows the full Pro page; kept by the owner's decision (6 Oct). The narration's claim itself ("The free version shows your repeat purchase rate") is true | Owner call | Noted |
| 12 | Whole kit | No cap, ⚑, "only / first", competitor names, recovery language or price | U2.3, U3, U4 | PASS |
| 13 | Lifecycle file (outside V1) | Welcome 1 and Trial 4 state the 100-customer cap; Trial 1 and 2 lead with churn and RFM; Trial 2's "opens your mail app" contradicts Brief A4; labels are 3.0.0-era | U2.3, U4, product facts | Flag for the lifecycle revision |

**Verdict:** **PASS.** Length, duration, chapters and cut timecodes are filled from the final master. Still open:
- After the upload: upload date, YouTube video ID and thumbnail URL in the JSON-LD; the chapters from §0 in the YouTube description.
- Fix Trial 3 before the email line goes in.
- Dev answers whether the Freemius-screen poster slot is possible.

---

## Changelog

- **v1.4 — 7 Oct 2026.** §2 FAQ 5: "nothing leaves your server" and the unqualified export/erase line replaced — export/erase covers customer notes only, the weekly digest is on by default (V8 Claim Audit finding 5; same wording as the developer spec v1.1). §8: headline "Your Data Stays On Your Site" → "Data Stays In Your Database" (finding 4).
- **v1.3 — 6 Oct 2026.** Assets made from the recordings: the three screenshots, two GIFs (1200 px recommended for legibility) and the 23.5 s cut; §1 and §7 say where they are.
- **v1.2 — 6 Oct 2026.** Filled from the final master (`pd v1 final edit -14LUFS.mp4`, 1:40, step-6 audit PASS): new §0 with the length, JSON-LD duration, upload file, thumbnail source, YouTube chapters (replacing Script v1.2 §B's placeholders) and the beat starts; §1 cut re-timed to the master (about 24 s, option A resolved, the tool's end card); `[LENGTH]` → 1:40 in §3 and §6; shot list sources the raw clips; shot 3's alt text made build-neutral; honest sentence re-checked (WooCommerce 11.1.2); "2-minute" copy confirmed.
- **v1.1 — 21 Sep 2026.** Q1 answers applied: FAQ rewritten; account qualifier and guest reason in the walkthrough; option B corrected. Q4 applied: button microcopy, headline 10 unlocked, Trial 5 → Variant A. Audit findings 1, 3, 4 and 5 applied; homepage hero → V8. Timecodes follow Script v1.2 (planned 1:46).
- **v1.0 — 21 Sep 2026.** First placement kit.
