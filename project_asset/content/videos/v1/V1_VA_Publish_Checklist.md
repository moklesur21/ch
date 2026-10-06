# V1 — publish checklist for the VA

**Video:** "How much of my WooCommerce revenue comes from returning customers?" · 1:40 · Pro
**Version 1.0 · 6 Oct 2026** · from `PureDevs_V1_Placement_Kit_v1_3.md` and `PureDevs_V1_Script_v1_2.md`
**Who:** the VA does everything below unless a step says **Yeasir** or **Developer**. Tick each box as you go and send Yeasir the "Report back" list at the end.

---

## Before you start: the files

Yeasir shares these with you (Drive). They come from `ch/project_asset/content/videos/v1/recordings/`:

| File | For |
|---|---|
| `pd v1 final edit -14LUFS.mp4` | **The video to upload.** Do **not** upload `pd v1 final edit.mp4`: it is the louder copy |
| `V1-poster.png` (1280 × 720) | YouTube thumbnail — **not made yet; wait for Yeasir** |
| `V1-shot-1-path.png`, `V1-shot-2-split.png`, `V1-shot-3-free-rate.png` | Capterra / G2 screenshots |
| `V1-shot-1-path.webp`, `V1-shot-2-split.webp`, `V1-shot-3-free-rate.webp` | The question page on puredevs.com |
| `V1-split.gif` | Optional, question page |
| `V1_cut_24s.mp4` | The 24-second ad version — **later** (step 7) |

---

## 1. Upload to YouTube — the same day the question page goes live

- [ ] **Upload** `pd v1 final edit -14LUFS.mp4`.
- [ ] **Title** (copy exactly):
  ```
  How much of my WooCommerce revenue comes from returning customers?
  ```
- [ ] **Description** (copy exactly). If the question page is not live yet when you upload, use `https://puredevs.com/pricing` in line 1 and change it back to the question page the day the page goes live.
  ```
  Customer History (Pro) puts new, returning and guest revenue side by side for the same dates, right in your wp-admin → https://puredevs.com/questions/woocommerce-new-vs-returning-customers/

  Where to click: Customer History → Dashboard → Revenue Analytics → Revenue by Customer Type.
  Three cards — New Customers, Returning Customers, Guest Orders — each with revenue and order count for one date range. New and Returning count customers with an account; guest orders have their own card. Self-hosted: it reads your store's own orders; nothing leaves your server.
  The free version shows your repeat purchase rate; this split is Pro. Recorded on a demo store with synthetic data.

  00:00 How much revenue comes from returning customers — and where to click
  00:21 New, returning and guest revenue, side by side
  01:04 The decision it gives you
  01:28 Free vs Pro

  Free plugin on WordPress.org: https://wordpress.org/plugins/puredevs-customer-history-for-woocommerce/
  Start the 30-day free trial (no card needed): https://puredevs.com/pricing
  ```
  *Tracked links:* if Yeasir wants UTM tags on the two puredevs.com links, build them in the team UTM sheet with `utm_source=youtube`, `utm_medium=video` and the campaign value Yeasir approves (suggested: `returning_revenue`). Don't invent other values.
- [ ] **Thumbnail:** upload `V1-poster.png` (when Yeasir sends it).
- [ ] **Tags:**
  ```
  woocommerce new vs returning customers, woocommerce new vs returning customers report, woocommerce returning customers, woocommerce revenue breakdown new returning, woocommerce new customer report, woocommerce repeat purchase rate, woocommerce reporting plugin, woocommerce orders report customer type filter
  ```
- [ ] **Audience:** No, it's not made for kids.
- [ ] **Altered or synthetic content:** **No.** The narration is Yeasir's own voice, cloned with ElevenLabs. YouTube says creators don't need to disclose that. The picture is a real screen recording.
- [ ] **Captions:** don't upload `captions.srt` (its timings are drafts). The captions are already burned into the video.
- [ ] **Visibility:** Public, after Yeasir confirms the question page is ready (the page and the video go live the same day).
- [ ] **Pinned comment** — post it from the PureDevs channel, then pin it:
  ```
  See this split on your own store's orders: start the 30-day free trial, no card needed → https://puredevs.com/pricing
  ```
- [ ] **Write down:** the YouTube URL, the **video ID** (the 11 characters after `watch?v=`) and the publish date.

## 2. Question page on puredevs.com

**Developer** builds the "Questions" section first (spec: `ch/project_asset/content/questions/PureDevs_Question_Pages_Dev_Spec_v1.md`). The V1 page will already exist as a **draft** with its text filled in. Then you:

- [ ] Open **Questions → "How much of my WooCommerce revenue comes from returning customers?"** in wp-admin.
- [ ] **Featured image:** upload `V1-poster.webp` (from Yeasir; under 80 KB). Alt text: `Revenue by Customer Type on a demo store: New Customers, Returning Customers and Guest Orders cards`
- [ ] In the body, upload the three **WebP** screenshots into the three image blocks. Copy each caption and alt text from the spec's Appendix A, "Body". Every caption ends with "Demo store, sample data."
- [ ] In the **Question page** box, fill:
  - YouTube ID: *(from step 1)*
  - Upload date: *(the YouTube publish date)*
  - Duration: `PT1M40S`
- [ ] **Preview** on your phone and on a laptop. Check:
  - the poster plays the video when clicked;
  - there is one kind of button only, **Start free 30-day trial**;
  - every image has its caption.
- [ ] **Publish** on the same day the YouTube video goes public. **Never change the URL** after publishing.
- [ ] Send Yeasir the live link.

## 3. Put the video on /pricing and the AG1 landing page

Both pages already have a video slot showing a still image. Swap in the video:

- [ ] **/pricing** — the slot **above** the plan cards: set the YouTube ID (video name `returning_revenue`).
- [ ] **/lp/customer-analytics/** (AG1 landing page) — the hero slot: same YouTube ID.
- [ ] On both pages, check that **the first link under the video is the trial button** ("Start free 30-day trial"). The video ends with "The link below starts the trial", so that must be true.
- [ ] Do **not** put V1 on the homepage hero. The homepage's buttons go to WordPress.org, which would make the video's last line untrue; the homepage gets V8 later.
- [ ] If the slot can't be changed in wp-admin, ask the **Developer** (it's a two-minute change for them).

## 4. Capterra and G2

In each listing's media section:

- [ ] **Add the video** by its YouTube URL. Caption:
  ```
  How much of my WooCommerce revenue comes from returning customers? A 1:40 demo of Customer History's Revenue by Customer Type, recorded on a demo store.
  ```
- [ ] **Add the screenshots, in this order, with these captions:**
  1. `V1-shot-1-path.png` — `Customer History → Dashboard, inside your own wp-admin.`
  2. `V1-shot-2-split.png` — `Revenue by Customer Type: new, returning and guest revenue side by side for the same dates (Pro). New and Returning count customers with an account.`
  3. `V1-shot-3-free-rate.png` — `The free version shows your repeat purchase rate (registered customers only).`
- [ ] No star ratings, review quotes or prices in any caption.

## 5. Google Ads — **Yeasir** (or whoever runs the ads)

- [ ] In ad group AG2 (Questions), set the final URL of `[woocommerce new vs returning customers]` and `[woocommerce returning customer report]` to the question page.
- [ ] The headlines and descriptions are already written in Placement Kit v1.3 §8. "Watch The 2-Minute Demo" is fine: the video is 1:40.

## 6. Emails — **wait for Yeasir**

- [ ] **Trial 3 email:** Yeasir fixes Trial 3 first (its menu path and the abandoned-carts paragraph). Then add this line after its first paragraph:
  ```
  1:40 video: how much of my WooCommerce revenue comes from returning customers? https://puredevs.com/questions/woocommerce-new-vs-returning-customers/
  ```
- [ ] The same line goes into the **pre-Black-Friday email** (Week 7) to free users and trials.
- [ ] Always link the question page, never YouTube. No dollar amounts.

## 7. Later — the 24-second ad version (Week 5, when Yeasir says)

- [ ] Upload `V1_cut_24s.mp4` to YouTube as **Unlisted**, titled `How much of my WooCommerce revenue comes from returning customers? (24 s)`. Ads need a YouTube-hosted video; Unlisted keeps it off the channel page.
- [ ] Send Yeasir its YouTube URL for the retargeting campaign.

---

## Report back to Yeasir

Copy, fill and send:

```
V1 published
- YouTube URL:
- YouTube video ID:
- Publish date (YYYY-MM-DD):
- Question page live at:            https://puredevs.com/questions/woocommerce-new-vs-returning-customers/
- /pricing video swapped:            yes / no
- AG1 landing page video swapped:    yes / no
- Capterra media added:              yes / no
- G2 media added:                    yes / no
- Problems or anything unclear:
```

Yeasir passes the YouTube ID, publish date and poster URL to Claude, who fills the last open values in the Placement Kit.
