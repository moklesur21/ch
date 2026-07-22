# Storefront setup — Northline Supply Co.

Turns the bare WooCommerce install into a branded, believable shop for on-camera
browsing (`update-01.txt`). Two reproducible layers:

1. A git-tracked child theme — the look.
2. A `setup-storefront` demo-tools command — the config + content.

Run once per site (local or live), after WooCommerce and the demo-tools plugin are
active:

```
wp pdwchs-demo setup-storefront
```

Idempotent — safe to re-run. Mutating, so it needs `PDWCHS_DEMO_ALLOW` in wp-config
like the seeder.

## The brand

**Northline Supply Co.** — a field-and-supply everyday-apparel shop (hoodies, tees,
caps, the small stuff). The name is also the seeder's default store name
(`pdwchs_demo_store_name`), so `blogname` stays consistent across every seed/refresh.
To rename the store, change that one option and re-run both seed and setup-storefront.

Design system (in `wp-content/themes/northline/theme.json`):

- **Palette** — Paper `#ECEDE8`, Ink `#16191C`, Spruce `#24544A` (accent), Kraft
  `#CBB79C`, Steel `#5A636B`.
- **Type** — Roboto Slab (rugged slab display), Fira Sans (body), Fira Code (mono
  labels / the "48°N" coordinate motif). Fonts are bundled locally in
  `assets/fonts/` (copied from Twenty Twenty-Five) — no external font calls.
- **Signature** — a monospace field-coordinate / spec-tag motif (`48°N — FIELD
  SUPPLY · EST. MMXIX`) instead of decorative numbering.

## The child theme — `wp-content/themes/northline/`

Child of Twenty Twenty-Five (git-tracked; whitelisted in `.gitignore`). Provides:

- `theme.json` — the whole brand system (palette, fonts, buttons, spacing) applied
  globally, so shop/product/cart pages inherit the brand, not just the homepage.
- `templates/front-page.html` — the homepage (hero → value strip → featured products
  → shop-by-category → editorial band). Block themes use this for the site front
  page automatically; no DB "Home" page needed.
- `parts/header.html` — top announcement bar, wordmark, nav (Home/Shop/About/Contact
  by URL), Woo mini-cart.
- `parts/footer.html` — branded multi-column footer.
- `style.css` — child header + a couple of hover rules the editor can't express.

## What `setup-storefront` does

`includes/class-pdwchs-demo-storefront.php` (`PDWCHS_Demo_Storefront`):

- **Activates** the Northline theme.
- **Store identity** — name, tagline, USD currency, US base, tax off, 4-col shop.
- **Product copy** — reads `data/catalog.json` and writes each product's name,
  description, short description, category, featured flag, price (simple products),
  and forces catalog visibility to `visible`. Backfills onto the products the seeder
  already created — no reseed needed. Clears stray sample sale prices.
- **Retires oddities** — Album, Single, Logo Collection, WordPress Pennant → draft
  (kept, not deleted, so historical order lines stay intact).
- **Categories** — consolidates to **Hoodies · Tees · Accessories**; prunes the
  leftover sample categories (Clothing, Music, Decor, Tshirts).
- **Brands** — clears the sample `product_brand` terms (Hype, Luxe Leather, Urban
  Threads) — this is a single-brand store.
- **Pages** — creates/updates **About** and **Contact** (block content).
- **Shipping** — flat rate $6 + free over $50 on the catch-all zone.
- **Cleanup** — trashes the default Sample Page.

The homepage "Best of the line" row shows only products flagged `featured` in
`catalog.json` — currently the clean-photo products (the sketch-logo variants are
excluded on purpose; see below).

## Known limitation — product imagery

The product photos are the WooCommerce **sample images** (hand-drawn illustrations),
kept per the decision not to generate new imagery. They're stylistically consistent,
but four "logo" variants (Everyday Tee — Logo, Northline Hoodie — Logo, Watch Cap —
Logo, V-Neck Tee) carry the WooCommerce smiley graphic. They're excluded from the
homepage featured row but still appear in the shop grid and Woo's "New in store"
widget. To upgrade: drop real product photos into the media library (or
`pdwchs-demo-tools/data/images/` + re-run `wp pdwchs-demo export-catalog`).

## Live deploy (ch.puredevs.com)

1. Deploy the theme (`wp-content/themes/northline/`) and the demo-tools plugin via git.
2. `wp pdwchs-demo setup-storefront` once (needs `PDWCHS_DEMO_ALLOW`).
3. `front-page.html` renders the homepage automatically — no manual page building.
4. If the store isn't seeded yet, run the seeder first (`wp pdwchs-demo seed`), then
   setup-storefront to apply copy to the freshly created products.

## Verify

`wp pdwchs-demo verify` now passes the currency (USD) and store-name checks. The
storefront changes don't touch the seeded data model — products are referenced by
position and order totals are plan-driven, so renaming/re-describing/re-pricing
products moves no acceptance band.
