# Question pages on puredevs.com — developer spec

**Version 1.1 · 7 Oct 2026** · for the developer of the `puredevs-site` theme
**Builds:** Section D4 of `PureDevs_Section_D_Landing_Pages_Guide_v1.5.md` (question pages: "DEV template, VA fills")
**First page:** `/questions/woocommerce-new-vs-returning-customers/` — its full content is in **Appendix A**.
**Where the source material lives:** the `ch` repo (`/Applications/MAMP/htdocs/ch`), not the website repo. Every file this spec names is under `ch/project_asset/content/videos/v1/recordings/` unless it says otherwise.

---

## 1. What a question page is

One page per demo video. Each answers one store owner's question with the video, a short "how to check this on your own store", three screenshots, the decision it helps with, and one trial button. The ad strategy sends Google Ads keywords, YouTube descriptions, emails and the Capterra / G2 listings to these pages, so they must be indexable, fast and stable.

- **URL:** `https://puredevs.com/questions/<slug>/`. A slug is **never changed after publishing** (ads, emails and YouTube descriptions point at it).
- **Go-live rule:** a question page is published the **day its video goes live on YouTube**, never before. Until then it stays a draft.
- **About eight pages over the next two months**, one a week (V1, V8, V13, V4, V14, V7, V3, then the next window). Same layout every time.

## 2. Decision: a custom post type `question`

**Use a CPT.** The pages share one template, the VA adds a new one each week without a developer, and each needs structured values (YouTube ID, video name, duration, tier) that feed tracking and schema. A page template per question would need a developer every week; plain Pages would lose the structure.

This follows the `docs` CPT precedent (`inc/docs-cpt.php`, seeded by `bin/seed-docs.php`). It is the **second deliberate place where copy lives in the database**, so add one line to the theme's `CLAUDE.md` §2 recording the founder's decision (6 Oct 2026), next to the docs exception.

No new plugin is needed: no ACF, no SEO plugin. The theme's `inc/seo.php` already handles title, description, canonical, OG and JSON-LD.

## 3. Registration — `inc/questions-cpt.php`

Require it from `functions.php` after `inc/docs-cpt.php`.

| Setting | Value | Why |
|---|---|---|
| Post type | `question` | |
| Labels | Questions / Question | |
| `public` | `true` | indexable, in the core sitemap |
| `hierarchical` | `false` | flat URLs |
| `has_archive` | `false` | no `/questions/` index yet (see §10) |
| `rewrite` | `array( 'slug' => 'questions', 'with_front' => false )` | `/questions/<slug>/` |
| `show_in_rest` | `true` | block editor for the body |
| `supports` | `title, editor, excerpt, thumbnail, revisions, custom-fields` | title = H1, excerpt = meta description, thumbnail = video poster |
| `menu_icon` | `dashicons-format-video` | |

Flush rewrite rules **once** after it ships, with the same stored-version pattern as docs (`pd_questions_rewrite_v`).

### Fields (post meta)

Register each with `register_post_meta( 'question', …, array( 'show_in_rest' => true, 'single' => true, 'auth_callback' => … ) )` and edit them in **one classic meta box** ("Question page") under the editor. Keep it plain: inputs, selects and textareas.

| Meta key | Input | Required | Used for |
|---|---|---|---|
| *(post title)* | title | yes | the H1, word for word the video's question |
| *(excerpt)* | excerpt | yes | the meta description (`inc/seo.php` already reads it) |
| *(featured image)* | thumbnail | yes | the video poster: 1280 × 720 WebP, **under 80 KB**; also `og:image` and the VideoObject `thumbnailUrl` |
| `_pdq_seo_title` | text | yes | `<title>` via `pd_set_seo()` |
| `_pdq_answer` | textarea, 2 lines | yes | "the answer in two lines" under the H1 |
| `_pdq_video_name` | select (list below) | yes | `video_id` in the `video_play` push |
| `_pdq_youtube_id` | text, 11 chars | at publish | the click-to-load embed and VideoObject `embedUrl` |
| `_pdq_duration` | text, ISO 8601 (`PT1M40S`) | at publish | VideoObject `duration` |
| `_pdq_upload_date` | date | at publish | VideoObject `uploadDate` |
| `_pdq_decide` | textarea | yes | the "What this lets you decide" block |
| `_pdq_tier` | select: `pro` · `free` · `free_pro` | yes | the tier pill ("Pro", "Free", "Free + Pro") |
| `_pdq_tier_note` | textarea | yes | one or two lines under the pill |
| `_pdq_related` | two selects listing other `question` posts | no | "More questions" links; **only published** ones render |
| `_pdq_faq` | textarea, format below | no | the FAQ accordion and FAQPage JSON-LD |
| `_pd_noindex` | checkbox | no | already honoured by `inc/seo.php` |

**`_pdq_video_name` options** (the stable names from Section D; never the V-numbers): `returning_revenue` · `no_saas` · `zero_result_searches` · `drop_off_funnel` · `viewed_not_bought` · `buying_times` · `bought_together` · `customers_by_product` · `order_screen_customer` · `refund_rate` · `cart_value`.

**`_pdq_faq` format** — simple for the VA, easy to parse. Each item starts with a line beginning `Q: `; the lines after it, up to the next `Q: `, are the answer. Blank lines split answer paragraphs; lines starting `- ` become a list. Run answers through `wp_kses_post()`; allow `<strong>`, `<em>`, `<a>`.

```
Q: Does the free version show this split?
No. The new, returning and guest split is Pro. …

Q: How does it decide who is new or returning?
It looks at customers with an account, for the dates you pick.
- New: …
- Returning: …
```

### The body (post content, block editor)

The VA writes only two parts in the editor, in this order:
1. An H2 **"How to check this on your own store"** and an ordered list of steps.
2. **Three Image blocks**, each with a caption and alt text.

Everything else on the page comes from the fields, so the layout can't drift.

## 4. Template — `single-question.php`

A classic PHP template like the others: design tokens only, mobile-first, one `<h1>`. Page CSS in `assets/css/question.css`, enqueued only on `is_singular( 'question' )`. Section order follows Section D4, plus the FAQ:

| # | Section | Source |
|---|---|---|
| 1 | Small eyebrow: "PureDevs Customer History · WooCommerce plugin" | hard-coded |
| 2 | **H1** = the question | post title |
| 3 | The answer in two lines | `_pdq_answer` |
| 4 | **Video**: click-to-load poster (§5), caption "Demo store, sample data" | featured image + `_pdq_youtube_id` + `_pdq_video_name` |
| 5 | **Trial button** + microcopy (§6) | component |
| 6 | "How to check this on your own store" + the three captioned screenshots | post content |
| 7 | H2 "What this lets you decide" + text | `_pdq_decide` |
| 8 | Tier pill + tier note | `_pdq_tier`, `_pdq_tier_note` |
| 9 | H2 "Questions store owners ask" + `<details>` accordion | `_pdq_faq` (hidden when empty) |
| 10 | **Trial button** again + microcopy | component |
| 11 | H2 "More questions" + up to two links | `_pdq_related`, published only (hidden when none) |

- **Until the YouTube ID is set**, the video slot shows the poster as a still image with no play button (Section D's slot rule). The page itself stays a draft until publish day anyway.
- **Screenshots:** `loading="lazy"`. The poster is the LCP element: `fetchpriority="high"`, eager, and preloaded with `<link rel="preload" as="image">` in `wp_head` on question pages.
- **No** review stars, testimonials, competitor names, or prices in the body.

## 5. Video slot — click-to-load YouTube

Use Section D's build-kit pattern (`pd/video-poster`). If it already exists in the current branch, reuse it; if not, build it once as a template part (`template-parts/shared/video-poster.php` + a small deferred JS file) and use it here and on the landing pages. YouTube loads only on click, from **`youtube-nocookie.com`**, and the click pushes the tracking event:

```html
<div class="pd-video" data-youtube="<?php echo esc_attr( $yt_id ); ?>" data-video="<?php echo esc_attr( $video_name ); ?>" data-page="question">
  <button type="button" aria-label="<?php esc_attr_e( 'Play the demo', 'puredevs' ); ?>">
    <img src="…poster.webp" width="1280" height="720" alt="…" fetchpriority="high">
  </button>
</div>
```
```js
document.addEventListener('click', function (e) {
  var box = e.target.closest('.pd-video');
  if (!box || box.dataset.loaded) return;
  box.dataset.loaded = '1';
  (window.dataLayer = window.dataLayer || []).push({
    event: 'video_play', video_id: box.dataset.video, page_type: box.dataset.page
  });
  box.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + box.dataset.youtube +
    '?autoplay=1&rel=0" title="Demo video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
});
```

`page_type` is **`question`** on every question page. Add it to the GTM `page_type` values if you keep a list (Section B4).

## 6. The trial button

Every question page has **one action**: the same trial button as /pricing and the landing pages (Section D0.1). Use the existing component: class `pd-trial`, label **"Start free 30-day trial"**, opening the checkout on the page (section C). Under it, this microcopy:

> No card needed. When the trial ends you keep the free version and all your data.

`cta_location` for the `checkout_open` push: **`question_<video_name>`**, for example `question_returning_revenue`.

If the section C overlay isn't live yet when the first page ships, link the button to `/pricing/` and switch it the day the overlay ships. **No second button**, and **no "Get Started Free"** to WordPress.org on Pro pages. Free-answered pages (V8, V13) may add the free plugin as a plain text link in the body, never as a second button.

## 7. SEO and schema

Call `pd_set_seo()` before `get_header()`:

```php
pd_set_seo( array(
	'title'       => get_post_meta( $id, '_pdq_seo_title', true ),
	'description' => get_the_excerpt( $id ),
	'og_image'    => get_the_post_thumbnail_url( $id, 'full' ),
	'schema'      => array_filter( array( $video_object, $faq_schema, pd_schema_software(), $breadcrumbs ) ),
) );
```

- **Title:** if the theme appends the site name to `<title>` automatically, drop any brand suffix from `_pdq_seo_title` so "PureDevs" isn't doubled. Follow `page-pricing.php`.
- **VideoObject** — only when the YouTube ID, duration and upload date are all set:
  ```json
  { "@type": "VideoObject",
    "name": "<post title>",
    "description": "<excerpt>",
    "thumbnailUrl": "<featured image URL>",
    "uploadDate": "<_pdq_upload_date>T08:00:00+00:00",
    "duration": "<_pdq_duration>",
    "embedUrl": "https://www.youtube-nocookie.com/embed/<_pdq_youtube_id>" }
  ```
- **SoftwareApplication:** reuse `pd_schema_software()`. Its price follows the page, as Section D requires.
- **FAQPage:** `pd_schema_faq()` from the `_pdq_faq` items, when present.
- **BreadcrumbList** (optional): Home → the question.
- **No** `aggregateRating` until real reviews exist; `pd_schema_software()` already guards this.
- **Sitemap:** confirm `/wp-sitemap-posts-question-1.xml` lists the page. Core sitemaps include public CPTs; check that nothing in the theme disables them.

## 8. Wording rules the template must not break (Section D0)

These apply to the hard-coded strings in the template. The VA follows them in the fields.

- Data wording on pages: **"Your customer and order data stays in your own WordPress database."** Not the absolute "nothing leaves your site" (licensing and updates run through Freemius, with opt-in).
- Never: "GDPR-compliant", "cookie-less", "real-time", "recover carts", automation, coupons, loyalty. Never "WooCommerce has no analytics".
- Free is a read limit: "Free shows the most recent 90 days". No customer cap, anywhere.
- No H1, H2 or meta leads with RFM, churn, health scores, behavioural tags or ready-made segments.
- Every screenshot and the video are captioned **"Demo store, sample data"**.

## 9. Reproducible from code (theme golden rule #8)

- Add `bin/seed-questions.php`, modelled on `bin/seed-docs.php`. It creates the V1 question post as a **draft** with Appendix A's content, and never overwrites an existing post unless forced (`PD_QUESTIONS_FORCE=1`).
- Call it from `bin/setup-site.sh` after the docs seed.
- The VA uploads the images and fills the publish-day fields in wp-admin. Later pages are created in wp-admin; the seed exists so a fresh install isn't empty.

## 10. Not now

- A `/questions/` index page. Add it when 4+ pages are live: `has_archive` → `questions` and an `archive-question.php` grid. The slugs don't change.
- The landing pages' question tiles (Section D1) link to these pages once each is live. That's a link change in the LP templates, not part of this build.

## 11. Definition of done

- [ ] `/questions/woocommerce-new-vs-returning-customers/` renders from a draft with Appendix A's content (preview), matching §4's order.
- [ ] Theme §6 checks pass: tokens only; renders at 375 / 768 / 1280; WebP images with `alt`; poster eager and preloaded, the rest lazy; title, description, canonical and OG via `inc/seo.php`; `php -l` clean; no notices with `WP_DEBUG`.
- [ ] Clicking the poster loads `youtube-nocookie.com` and pushes `video_play` with `video_id: returning_revenue`, `page_type: question` (check in GTM Preview).
- [ ] One trial button style on the page (shown twice), with the microcopy; it pushes `checkout_open` with `cta_location: question_returning_revenue` once section C is live.
- [ ] View source: one JSON-LD graph containing VideoObject (once the publish-day fields are set), FAQPage and SoftwareApplication; no `aggregateRating`.
- [ ] The page is indexable (`index,follow`) and listed in the question sitemap.
- [ ] Mobile Lighthouse ≥ 90.
- [ ] The VA can create a second question from wp-admin alone, without a developer.

---

## Appendix A — content for `woocommerce-new-vs-returning-customers` (V1)

Sources: Section D4 (title, meta, answer, decide, tier note), V1 Placement Kit v1.3 §2 (walkthrough, screenshots, FAQ), and the final video. Wording follows the D0 rules. Where the Placement Kit's §2 differs (its meta title, meta description and CTA), this appendix is final, because Section D owns the pages.

### Fields

| Field | Value |
|---|---|
| Slug | `woocommerce-new-vs-returning-customers` |
| Title (H1) | How much of my WooCommerce revenue comes from returning customers? |
| `_pdq_seo_title` | Returning Customer Revenue in WooCommerce \| PureDevs |
| Excerpt (meta description) | See how much WooCommerce revenue comes from new, returning and guest customers. A short demo on a working store, plus the steps to check your own. |
| `_pdq_answer` | Open Customer History → Dashboard → Revenue Analytics. "Revenue by Customer Type" shows New Customers, Returning Customers and Guest Orders, each with revenue and order count for the dates you pick. |
| `_pdq_video_name` | `returning_revenue` |
| Featured image (poster) | `V1-poster.webp`, 1280 × 720, 41 KB. Alt: "How much of my WooCommerce revenue comes from returning customers? Revenue by Customer Type on a demo store, with the Returning Customers card outlined" |
| `_pdq_youtube_id` | *(set on publish day, from the YouTube upload)* |
| `_pdq_duration` | `PT1M40S` |
| `_pdq_upload_date` | *(the YouTube publish date)* |
| `_pdq_decide` | Whether your next hour goes to the customers who already buy from you or to bringing first-time buyers back, and whether to encourage accounts at checkout. If returning customers bring the biggest share, your next hour belongs to them. If new customers do, bringing them back is the gap. If the guest card is big, decide whether to encourage accounts at checkout: repeat guests never show as returning. |
| `_pdq_tier` | `pro` |
| `_pdq_tier_note` | This is a Pro report. The free version's Revenue page shows your Repeat Purchase Rate, for registered customers only. |
| `_pdq_related` | none yet. Add `/questions/where-your-woocommerce-customer-data-is-stored/` (V8) and `/questions/woocommerce-zero-result-searches/` (V13) once each is live |

### Body (block editor)

**H2:** How to check this on your own store

1. In wp-admin, open **Customer History → Dashboard** and scroll to **Revenue Analytics**. It's a Pro report, and it runs inside your own WordPress admin: your customer and order data stays in your own WordPress database.
2. Under **Revenue by Customer Type** you get three cards: New Customers, Returning Customers and Guest Orders. Each shows its revenue and the number of orders behind it. New and Returning count customers with an account; guest orders are placed without one.
3. One date range at the top drives all three cards, so you're always comparing the same dates.
4. WooCommerce's own Orders report can show orders from new or returning customers, one filter at a time. Customer History puts new, returning and guest revenue side by side for the same dates.
5. Read the proportion, not the number: which card is biggest, and where the guest card sits.
6. Only completed and processing orders count, and figures can lag up to 30 minutes behind.

**Image 1** — file `V1-shot-1-path.webp` (1920 × 620)
- Caption: Customer History → Dashboard, inside your own wp-admin. Demo store, sample data.
- Alt: WordPress admin with the Customer History menu open and the plugin's Dashboard tab selected (demo store).

**Image 2** — file `V1-shot-2-split.webp` (1920 × 192)
- Caption: New, returning and guest revenue, side by side for the same dates (Pro). Demo store, sample data.
- Alt: Revenue by Customer Type section with three cards — New Customers, Returning Customers, Guest Orders — each showing revenue and order count (demo store).

**Image 3** — file `V1-shot-3-free-rate.webp` (1920 × 254)
- Caption: The free version shows your repeat purchase rate (registered customers only). Demo store, sample data.
- Alt: Revenue page with five summary cards; the last card is Repeat Purchase Rate, captioned "Registered customers only" (demo store).

The images carry a small "Demo store data" label of their own. The captions above are the page's official labels.

### `_pdq_faq` (paste as is)

```
Q: Does the free version show this split?
No. The new, returning and guest split is Pro. The free version shows your repeat purchase rate for registered customers, for the most recent 90 days.

Q: How does it decide who is new or returning?
It looks at customers with an account, for the dates you pick.
- New: their first paid order falls inside those dates. Every order they place in that period counts as New, even a second or third.
- Returning: they ordered in that period, and their first paid order came before it.
Only completed and processing orders count, and revenue is net of refunds. Days follow GMT, not your site's timezone. Results can be up to 30 minutes behind.

Q: How are guest orders handled?
Any order placed without an account goes in Guest Orders. The plugin doesn't match guests by email, so a guest who buys twice stays in Guest Orders and never shows as returning. If that shopper later creates an account, their old guest orders stay in Guest and their first order with the account counts as New. The exception: if WooCommerce links their old guest orders to the new account, those orders count toward that customer. The plugin never changes your orders.

Q: Does it only count orders placed after I install it?
No. It reads your existing WooCommerce orders, so earlier orders are included once Pro is active.

Q: Does my data leave my site, or slow my store down?
Your customer and order data stays in your own WordPress database; the plugin makes no outbound requests of its own. Licensing and updates run through Freemius, which asks for your permission first. The weekly digest email to you is on by default; switch it off in Customer History → Settings. Customer notes work with WordPress's personal-data export and erase tools; tracked visit rows are not part of them yet. The report runs in your WordPress admin and caches its results for 30 minutes. On your storefront the plugin records activity server-side, with no JavaScript tracker.
```

### Trial button (both places)

**Start free 30-day trial** · microcopy: "No card needed. When the trial ends you keep the free version and all your data." · `cta_location: question_returning_revenue`

### Files to copy from the `ch` repo into the media library

`ch/project_asset/content/videos/v1/recordings/`: `V1-shot-1-path.webp`, `V1-shot-2-split.webp`, `V1-shot-3-free-rate.webp`, and the poster `V1-poster.webp`. The 1200 px `V1-split.gif` is optional: if used, put it after Image 2 with the caption "Demo store, sample data" and show it at full column width. At narrow widths the card labels stop being readable.

---

## Changelog

- **v1.1 — 7 Oct 2026.** V1 FAQ "Does my data leave my site…": the export/erase line is limited to customer notes (tracked visit rows are not covered), the default-on weekly digest is stated, and "no outbound requests of its own" added (code read of the 3.1.2 builds; V8 Claim Audit finding 5). If the page is already built, update that one answer.
- **v1.0 — 6 Oct 2026.** First version: `question` CPT, fields, template, video slot, trial button, SEO/schema, seed script, and the V1 page content.
