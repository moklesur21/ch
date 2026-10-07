# V8 Placement Kit — Where does my store data go when I install this?

**Version 1.1 · 7 Oct 2026** · Demo Video Prompt Pack v3, FILE 5
From `PureDevs_V8_Script_v1_2.md`, `PureDevs_V8_Demo_Brief_v1_1.md` and `PureDevs_V8_Recording_Brief_v1_3.md`, filled from the final master.

> **Gate D open (7 Oct night):** the times and loudness below are the web-UI-voice master's (now `V8_reference_master_webui.mp4`). If Yeasir picks the API-voice master (1:56.3, music and clicks), §0, the chapters and the cut table are re-filled from it before upload.
**Final master:** `recordings/edit/V8_reference_master.mp4`:
- **1:57** (116.8 s), 1920 × 1080, 30 fps, H.264;
- −14.0 LUFS, peaks −4.6 dBTP;
- captions burned in.

Step-6 audit: **PASS** with two copy fixes applied (`PureDevs_V8_Claim_Audit_v1_0.md`).
**Question page URL** (D4 proposal; fixed on publish and never changed after): https://puredevs.com/questions/where-your-woocommerce-customer-data-is-stored/

**Where V8 is embedded:**
- **LP-Alternatives hero** (`/compare/self-hosted-vs-saas/`, AG3). The only button there is the trial; the page microcopy bridges "install free" (hand-off note §5).
- **The question page.**
- **The homepage hero:** its buttons go to WordPress.org, which matches "Install it free".
- **YouTube.**

**Not on /pricing:** V1 is there, and its trial CTA fits that page. The embeds are YouTube embeds. The master is H.264, so it can also be self-hosted as it is.

## 0. Publish-day values

| Field | Value |
|---|---|
| Length | **1:57** (filled in §3) |
| JSON-LD `duration` | `PT1M57S` |
| Upload file | the master Yeasir approves at gate D: `recordings/edit/V8_reference_master.mp4` (API voice, music, clicks) or `V8_reference_master_webui.mp4` |
| Thumbnail | `recordings/V8-poster.png`: 1280 × 720, 65 KB. Source `poster.html`: the question, the "hoodie" row outlined, the two products, "PureDevs Customer History", "Demo store" |
| Page poster | `recordings/V8-poster.webp`, 24 KB (hand-off §4: under 80 KB) |
| Transcript | `recordings/transcript.txt`: the 200 spoken words, cleaned (hand-off §4) |
| Recorded on | WordPress 7.1.3 · WooCommerce 11.1.2 · PureDevs Customer History 3.1.2 from WordPress.org (all three still the latest on 7 Oct 2026) |

**YouTube chapters.** These replace Script v1.1 §B's placeholders and are already in Script v1.2. Each starts where its beat's voice starts, and every chapter is at least 10 s.

```
00:00 Where your store data goes — the install, in one take
00:47 A shopper's search, in your own wp-admin
01:26 The decision it gives you, and Free vs Pro
```

**Beat starts in the master** (voice onsets, from `recordings/captions.srt`):

| Beat | Starts | First words |
|---|---|---|
| 1 | 0:00.3 | "Where does your store data go…" |
| 2 | 0:05.2 | "Here's the whole install, in one take." — "Plugins," 0:07.9 · "Search." 0:13.5 · "Install." 0:19.1 · "Activate." 0:23.9 · Freemius 0:26.2 · "We skip it." 0:32.3 · "No account…" 0:33.3 |
| 3 | 0:47.7 | "Now a shopper — not logged in…" — Search Analytics 0:58.9 · Site Health 1:15.5 |
| 4 | 1:26.8 | "So here is the decision this gives you." |
| 5 | 1:44.9 | "Everything you just saw is the free version." — end card from 1:52.2 |

---

## 1. The 20–30 s cut

**Made:** `recordings/edit/V8_cut_27s.mp4`.
- 27.0 s, 1920 × 1080 H.264, −13.8 LUFS, captions burned in.
- Built with ffmpeg from the master: a 0.3 s crossfade into the decision, a 0.4 s crossfade into the end card, and the audio crossfaded at the same joins.

Beat 1 + the answer frame + the beat-4 decision + the end card. Beat 2 (the install) is skipped.

| Cut time | Master source | Picture | Voice (from the master) | Caption |
|---|---|---|---|---|
| 0:00–0:05.0 | 0:00.0–0:05.0, beat 1 | The answer frame, framed on the page. Title **PureDevs Customer History** from frame one; "Demo store · sample data" | Where does your store data go when you install this plugin? | As in the master |
| 0:04.7–0:22.8 | 1:26.5–1:44.6, beat 4 entire | Slow pull-out from the "hoodie" table to the whole page | So here is the decision this gives you. Whether customer analytics has to mean sending your customer data to someone else's servers. With a plugin like this, it doesn't — no SaaS account, no API key, and the data sits in the same database as your orders. | As in the master |
| 0:22.4–0:27.0 | 1:52.3–1:56.8 | The end card **Install free · WordPress.org** | Install it free — the link is below. | **Install it free** |

- **Nothing to re-record.** All the voice comes from the master.
- **Never under 11 s:** the cut is 27.0 s.
- **No spoken figures. One CTA.**
- **Retargeting (Week 5: V1, V4, V8):** point the ad's final URL at the **question page**, where the walkthrough's last line is the WordPress.org link, so "the link is below" holds. Don't point it at LP-Alternatives: its only button is the trial.

**Optional vertical 9:16:**
- **Frame:** 1080 × 1920.
- **Question text:** top third, 72 px, white on #1d2327, as on the poster.
- **Middle:** the Search Analytics strip at full width. Use the poster's two strips ("hoodie" outlined, then the two products); a full-width table is unreadable at 1080 px wide.
- **Captions:** bottom third, at least 60 px.
- **Short title:** "Where does my store data go when I install this?" (48 characters).

---

## 2. Question page

| Field | Content |
|---|---|
| Slug | `/questions/where-your-woocommerce-customer-data-is-stored/` (D4) |
| H1 | Where does my store data go when I install PureDevs Customer History? |
| Meta title (54 / 60) | Where Your Store Data Goes \| PureDevs Customer History |
| Meta description (154 / 155) | See where PureDevs Customer History keeps your WooCommerce customer data: in your own WordPress database, with no account and no API key. A one-take demo. |
| Answer in two lines (D4) | It stays in your own WordPress database. The plugin records searches, views, add-to-carts and orders on your server and makes no outbound requests of its own. There is no account and no API key. |
| Video | YouTube embed of the master; poster `V8-poster.webp` |
| CTA button (one) | **Start free 30-day trial** → https://puredevs.com/pricing · microcopy (hand-off §5): "The trial gives you the full plugin for 30 days. After that it drops to the free version and you keep your data." |

**Written walkthrough** (from the transcript; no demo figures):

1. Install it like any plugin: **Plugins → Add Plugin**, search "PureDevs Customer History", **Install Now**, **Activate**.
2. There's one prompt: an optional opt-in from Freemius, the licensing provider. You can skip it, and the plugin works the same.
3. No account, no API key, no setup wizard. **Customer History → Dashboard** is already reading your store's own orders.
4. It's self-hosted: what it records stays in your own WordPress database.
5. To see it work, visit your store logged out, search for something and open a product. Then open **Customer History → Search Analytics**: the search and the product views are there. Your own admin browsing isn't recorded.
6. **Tools → Site Health → Info → PureDevs Customer History** shows the plugin's own usage counters, which stay on your server too.
7. The decision: customer analytics doesn't have to mean sending your customer data to someone else's servers.
8. Everything here is the free version; it shows the last 90 days, and Pro shows your full history. Install it free from WordPress.org: https://wordpress.org/plugins/puredevs-customer-history-for-woocommerce/ (a plain text link, not a button).

**Screenshots** (frames from the recording, each labelled "Demo store · sample data"; PNG and WebP in `recordings/`):

| # | File | Caption | Alt text |
|---|---|---|---|
| 1 | `V8-shot-1-optin` | The one prompt on activation: an optional opt-in from Freemius, the licensing provider. Skip it, and the plugin works the same. | The Freemius opt-in box "Never miss an important update" with an "Allow & Continue" button and a "Skip" button (demo store). |
| 2 | `V8-shot-2-search-analytics` | A logged-out shopper's search and product views, recorded in your own WordPress admin (free version). | The Search Analytics page: Top Keyword Searches and Last Searches list "hoodie"; Most Viewed Products lists The Northline Hoodie and Full-Zip Hoodie (demo store). |
| 3 | `V8-shot-3-site-health` | Tools → Site Health → Info: the plugin's own usage counters stay on your server. | The Site Health Info section "PureDevs Customer History": "Local, non-identifying counters… This information stays on your server", with Plugin version, Active tier Free, First activated and First free-tier limit reached "None reached yet" (demo store). |

**FAQ** (Demo Brief v1.1 §8 + the research's buyer doubts; wording per Section D guide v1.5, D0 and D4):

- **What exactly does it store?**
  - **What:** the searches shoppers make (with how many results each got), the pages they view (that's how product views are counted), add-to-carts and new orders.
  - **With each row:** the user ID (0 for guests), the IP address, the referring page, the date and the PHP session ID.
  - **Where:** one table in your WordPress database. IP addresses and emails are stored, so it isn't anonymised, and it doesn't settle your privacy-policy obligations for you.
- **Does anything leave my site?**
  - **Licensing and updates:** they run through Freemius, with an opt-in prompt you can skip.
  - **No outside requests:** the plugin makes no outbound requests of its own, and its charts are bundled.
  - **One thing to know:** the weekly digest email to you is on by default. It sends an order summary to your admin email; switch it off in Customer History → Settings.
- **Do I need an account or an API key?** No. Install, activate, skip the opt-in if you like, and it works.
- **Can I export or erase a customer's data?** Customer notes work with WordPress's export and erase tools (Tools → Export / Erase Personal Data). Tracked visit rows are not part of those tools yet.
- **Why is my Search Analytics page empty?** Administrators and shop managers are never recorded on the free version. Visit your store logged out, or wait for real shoppers. Only searches made through WordPress/WooCommerce search are recorded.

**VideoObject JSON-LD:**

```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Where does my store data go when I install PureDevs Customer History?",
  "description": "A one-take install of PureDevs Customer History from WordPress.org, then a shopper's search showing up in wp-admin: the data stays in your own WordPress database, with no account and no API key. Recorded on a demo store with sample data.",
  "thumbnailUrl": ["[THUMBNAIL URL]"],
  "uploadDate": "[YYYY-MM-DD]",
  "duration": "PT1M57S",
  "embedUrl": "https://www.youtube.com/embed/[VIDEO ID]",
  "publisher": { "@type": "Organization", "name": "PureDevs", "url": "https://puredevs.com/" }
}
```

---

## 3. Email line

**Line** (plain text):

> 1:57 video: where does my store data go when I install this plugin? https://puredevs.com/questions/where-your-woocommerce-customer-data-is-stored/

**Where it goes:** **Welcome 1** ("on sync"), straight after its sentence "And your customer and order data stays in your own database." The email already makes the claim, and the video proves it. The same email sends people to Search Analytics, which the video shows filling up.

No dollar amounts. It links to the question page, never to YouTube.

---

## 4. WordPress.org

**Readme video: not V8.** The slot near the top of the description belongs to V13 (Video Topic Filter §4; Section I guide item 6). One video, one slot.

**Readme FAQ, the answer V8 supports.** It replaces the answer at `readme.txt:146` and ships with dev request 3:

> **Q. What customer data does the plugin store, and where?**
> A. Searches (with their result count), page and product views, add-to-carts and new orders, each with the user ID (0 for guests), IP address, referring page, date and PHP session ID. Everything is stored in one table in your own WordPress database; the plugin makes no outbound requests of its own. Customer notes work with WordPress's personal-data export and erase tools; tracked visit rows are not part of them yet. A short demo: https://puredevs.com/questions/where-your-woocommerce-customer-data-is-stored/

**Screenshot caption,** if the readme's screenshot set takes the Search Analytics frame (`V8-shot-2-search-analytics`):

> Search Analytics: what logged-out shoppers searched for and which products they viewed, stored in your own WordPress database.

The readme shows what the plugin does; it doesn't pitch Pro.

---

## 5. In-plugin poster

**NOT FOR THIS VIDEO.** The Pack allows an in-plugin poster for a Pro-answered capability only. V8 is answered by the free version, and the user has already installed the plugin.

---

## 6. Capterra and G2 media

**NOT FOR THIS VIDEO.** The Section H guide makes V13 the listing video for both sites. If Yeasir wants a second video there later:

- **Video caption:** "Where does my store data go when I install this? A 1:57 one-take install of the free plugin, recorded on a demo store."
- **Screenshot captions:** shots 1–3 above, in order.

---

## 7. Shot list (made, 7 Oct 2026)

| File (`recordings/`) | Source | Size |
|---|---|---|
| `V8-shot-1-optin.png` / `.webp` | scene-02 at 45.0 s: the Freemius box with Allow & Continue and Skip | 1920 × 1040 · 317 / 55 KB |
| `V8-shot-2-search-analytics.png` / `.webp` | `answer-frame-crop.png` scaled to 1920 wide | 1920 × 874 · 109 / 32 KB |
| `V8-shot-3-site-health.png` / `.webp` | scene-05, last frame: the opened section | 1920 × 700 · 179 / 33 KB |
| `V8-search-analytics.gif` | `edit/V8_b3b.mp4` 5.3–10.9 s: the zoom from "hoodie" to the two products; 960 px, 12 fps, looping | 391 KB (limit 2 MB) |
| `V8-poster.png` / `.webp` | `poster.html` rendered in Chrome | 1280 × 720 · 65 / 24 KB |

- The screenshots are cropped from the raw 4K clips, never the captioned master. Each carries "Demo store · sample data".
- The cursor ring is part of the recording. It sits beside the subject, never on a value.
- **GIF:** for the question page only, never in the plain-text emails.

---

## 8. Ad match (strategy §6.2, AG3 Alternatives)

AG3's keywords land on the V8 page (`[metorik alternative]`, `[metrilo alternative]`, `"self hosted woocommerce analytics"`, `"woocommerce analytics without saas"`). The competitor names stay in keywords only, never in ad text.

| Use | Copy | Characters |
|---|---|---|
| Headline | Self-Hosted. No SaaS Account. | 29 / 30 |
| Headline | A Self-Hosted Alternative | 25 / 30 |
| Headline | Runs Inside WordPress | 21 / 30 |
| Headline | PureDevs Customer History | 25 / 30 |
| Headline (length 1:57 — under 2:00, so it holds) | Watch The 2-Minute Demo | 23 / 30 |
| **Headline — replaces "Your Data Stays On Your Site"** (audit finding 4) | Data Stays In Your Database | 27 / 30 |
| **Description — replaces Description 2** (audit finding 4) | Customer analytics that runs inside WordPress. Your store data stays in your own database. | 90 / 90 |
| Description | Watch a 2-minute demo on a working WooCommerce store, then try it on your own orders. | 85 / 90 |

- **Why the two replacements:** Description 2 says "Your store data never leaves your site". Section D's rule D0 already bans that absolute on pages, and the free plugin emails a weekly order digest to the admin by default (dev request 4). "Stays in your own database" is true today. Once dev request 4 ships, the old lines can return.
- **Keyword seeds** (check in Keyword Planner): `[where does woocommerce store data]` · `[self hosted woocommerce analytics]` · `[woocommerce analytics without saas]` · `[does this plugin send my data anywhere]`.
- No competitor names in ad text, no price, no recovery language.

---

## 9. Sanity check (FILE 6 list, every section)

| # | Section | Finding | Rule | Status |
|---|---|---|---|---|
| 1 | §1 cut | 27.0 s; no spoken figures; one CTA; the title from frame one; "Demo store · sample data" throughout | U2.1, U7, U8 | PASS |
| 2 | §1 retargeting | "The link is below" holds only where a WordPress.org link is below → final URL = the question page | U7 | PASS (instruction given) |
| 3 | §2 page | One button (trial), WordPress.org as a plain text link in the last walkthrough line (Pack FILE 5 §2) | U7 | PASS |
| 4 | §2 FAQ | Privacy wording per D0 / D4 v1.5: no "nothing leaves your site", "GDPR" or "anonymised"; digest and export/erase limits stated | U3, D.2 | PASS |
| 5 | §2 FAQ | "No outbound requests of its own": true for the plugin's code (code read, Brief B7); Freemius named separately | U5 | PASS |
| 6 | §2 meta | 54 / 60 and 154 / 155 characters | Pack §2 | PASS |
| 7 | §3 | Real length 1:57, question page, no amounts; Welcome 1 already carries the data claim | Pack §3 | PASS |
| 8 | §4 | Readme video slot stays V13; FAQ text ships with dev request 3 (it corrects `readme.txt:146`) | Pack §4, Filter §4 | PASS |
| 9 | §5, §6 | Not for this video, with reasons | Pack | PASS |
| 10 | §7 | Frames from the recording; demo label; GIF under 2 MB; poster WebP under 80 KB | Pack §7, hand-off §4 | PASS |
| 11 | §8 | Two lines replaced for the default digest; "2-minute" holds at 1:57; within 30 / 90 | U3, U7 | PASS |
| 12 | Whole kit | No cap, ⚑ subject, "only / first", competitor names in copy, price (trial terms only in changeable page text) | U2.3, U4, U7 | PASS |
| 13 | Freshness | WordPress 7.1.3, WooCommerce 11.1.2 and the plugin 3.1.2 re-checked on wp.org, 7 Oct 2026 | U2.2 | PASS — re-check on publish day |

**Verdict:** **PASS.** Still open:
- **After the upload:** the YouTube ID, the thumbnail URL and the upload date in the JSON-LD.
- **Ads:** the AG3 headline and description swap (§8).
- **Dev requests 3–4:** the readme FAQ, and the digest made opt-in. Once those ship, the "nothing leaves" lines can return.
- **V1's question page** still says the plugin "works with WordPress's personal-data export and erase tools" (Question Pages dev spec, V1 FAQ 5). Fix it to the §2 wording.

---

## Changelog

- **v1.1 — 7 Oct 2026.** Upload file: the gate-D master, no CapCut rebuild (the CapCut guide was removed); note that the times are the web-UI master's until gate D.
- **v1.0 — 7 Oct 2026.** First Placement Kit, from the final master (1:57): the 27 s cut, the poster, three screenshots, the GIF and the transcript made; question page, email line (Welcome 1), readme FAQ text, ad match with two copy replacements.
