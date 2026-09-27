# PureDevs — Hand-off Note: Ad-Launch Chat → Video Chat

**Version:** v1.1 · **Date:** 21 Sep 2026 · **Supersedes:** v1.0 (written against v4.0, now out of date)
**From:** the ad-prerequisites chat · **For:** the chat that owns `PureDevs_Video_Topic_Filter_v4.1.md`
**Read v4.1 in full.** The ad-launch docs now follow it: Checklist v1.1 · Section D guide v1.2 · Section B v1.2 · Section C v1.1.
**Launch target:** Google Search ads live **Thu 1 Oct** (earlier than the strategy's week-3 date) · $10/day · Yeasir has 2 h/day.

## 1. Where the two chats now agree

- Nothing is recorded. V1 and V8 are made in week 1, V13 in week 2, then V4, V16, V14, V7, V3.
- V1 is the LP-Category hero and sits above the plans on /pricing. V8 is the LP-Alternatives hero. V13 is the WordPress.org readme video and the Capterra / G2 master.
- The model comparison (hosted vs plugin) lives in the LP-Alternatives **page text**, with no competitor prices and nothing in the narration.
- The strategy's RSAs stand. The two search-led headlines go in only when the V13 question page is live.
- First three question pages: V1, V8, V13, each live the day its video publishes.
- From the ad side there is **no objection to any amendment in v4.1 §2**. The sign-off is Yeasir's (checklist item A6, due 22 Sep).

## 2. Names in tracking, IDs in documents

v4.1 keeps the strategy's V-IDs. The site's tracking uses a name as `video_id`, so reports stay readable if IDs ever move again. Please carry the name column into v4.2.

| ID | Name | ID | Name |
|---|---|---|---|
| V1 | `returning_revenue` | V14 | `viewed_not_bought` |
| V8 | `no_saas` | V7 | `buying_times` |
| V13 | `zero_result_searches` | V3 | `bought_together` |
| V4 | `drop_off_funnel` | V15 · V17 | `customers_by_product` · `order_screen_customer` |
| V16 | `free_to_pro` | V18 · V5 | `refund_rate` · `cart_value` |

## 3. Dates the pages need

| Video | Needed | If it is late |
|---|---|---|
| V1 | Script Mon 21 · record Tue 22 · VA edit Wed 23 · **final Fri 25** · published Sat 26 Sep | D1 and /pricing launch with the answer frame as a still image; the video swaps in on publish. Launch does not move. |
| V8 | Recorded Sat 26 – Mon 28 · published Tue 29 Sep | LP-Alternatives runs table-first, no video. |
| V13 | Week 2 (by Sun 4 Oct), **after** the DEV's search-plugin test | Readme refresh and its question page wait for it. |

**Scripts needed from the video chat, in this order:** V1 today, V8 by Thu 24 Sep, V13 by Tue 29 Sep.

## 4. Hand back for every published video

YouTube ID · the answer frame as a poster (1280×720, WebP under 80 KB) · 3 captioned screenshots ("Demo store, sample data") · cleaned transcript · duration · publish date. Pages and their `VideoObject` schema are filled from these.

## 5. Three things the scripts should know

- **On ad landing pages the only button is the trial**, including under V8 and V13, whose beat 5 says "install free". No script change needed: the page microcopy bridges it ("The trial gives you the full plugin for 30 days. After that it drops to the free version and you keep your data.").
- **Links:** description line 1 and the pinned comment go to `/pricing` now; line 1 switches to the `/answers/...` URL when that page is live. UTMs: `utm_source=youtube&utm_medium=video&utm_campaign=proof2026_<name>`.
- **Data wording:** narration may say "nothing leaves your server" (the Feature Reference approves it). Pages use "your customer and order data stays in your own WordPress database", because Freemius licensing runs with an opt-in. V8 already shows that prompt honestly, which is the right call.

## 6. Shared open items and owners

| Item | Owner | When |
|---|---|---|
| Demo store on 3.1.2, seeded for V1 (new, returning and guest orders on most of the last 30 days) | VA + DEV | Mon 21 Sep |
| Trial terms in Freemius (30 days, no card) before any end card says so | Yeasir (C3) | Mon 21 Sep |
| 100-customer cap: live or removed? | Yeasir (A7) | Tue 22 Sep |
| Search-plugin test: FiboSearch, Advanced Woo Search, Woo Product Search block | DEV | By Mon 28 Sep |
| Zero-result searches generated logged out for V13 | VA | Week 2 |
| Which STAT-04 columns sort (V14) | DEV | Before week 5 |

One fact for the V13 page and script: the free path is Search Analytics → Zero-Result Searches; on Pro the same report is Statistics → Zero-Result Searches.

## Changelog
- **v1.1 — 21 Sep 2026.** Rewritten after reading v4.1. Dropped v1.0's renumbering advice, launch-hero proposal and answers that v4.1 has since resolved. Added the agreed slot map, recording dates against the 1 Oct launch, the script order, and the shared open items with owners.
- **v1.0 — 20 Sep 2026.** First version, written against v4.0.
