# ch — PureDevs Customer History demo site

Local WordPress site (MAMP) that is a clone of the live demo at ch.puredevs.com.
Never touch the live site — everything is built and tested here, then synced to
the live demo manually.

The product being demoed is:
`wp-content/plugins/puredevs-customer-history-for-woocommerce-pro`

## Purpose of this site

1. Test each new plugin feature with realistic data right after building it.
2. Stage demo content for promotional screen recordings, then migrate it to the
   live demo site.

## What is tracked in git

Whitelist `.gitignore` — WordPress core, third-party plugins/themes, uploads,
backups and `wp-config.php` are all ignored. Tracked:

- `wp-content/plugins/puredevs-customer-history-for-woocommerce-pro` — the main (pro) plugin
- `wp-content/plugins/puredevs-customer-history-for-woocommerce` — the free version
- `wp-content/plugins/pdwchs-demo-tools` — demo data/tooling plugin (needs optimisation)
- `wp-content/mu-plugins/auto_login_local.php` — local-only auto-login helper
- `project_asset/` — project docs and demo assets

## Local environment

- Site URL: `http://localhost/ch` (MAMP Apache). DB `ch`, prefix `pp09_`.
- wp-cli needs MAMP's MySQL socket:
  `php -d mysqli.default_socket=/Applications/MAMP/tmp/mysql/mysql.sock /opt/homebrew/bin/wp <command>`
- The **free** plugin is the active one; pro is installed but inactive
  (realistic upgrade flow for demos).
- Auto-login helper (mu-plugin): `http://localhost/ch/?local_login=1&user_email=<email>`
  — gated to `WP_ENVIRONMENT_TYPE=local` and disabled when `PDWCHS_DEMO_LOCKDOWN`
  is defined. Never deploy this file to the live VPS.

## Roadmap / notes

- Freemius sandbox checkout: wired up via the `WP_FS__<slug>_SECRET_KEY`
  constant in `wp-config.php` — paste the product secret key there to enable.
  Full guide: `project_asset/docs/freemius-sandbox.md`
- Demo-site lockdown: read-only public viewer (`demo`/`demo1234`) for the live
  site, enforced by `pdwchs-demo-tools` →
  `includes/class-pdwchs-demo-lockdown.php`. Inert unless
  `PDWCHS_DEMO_LOCKDOWN` is defined true in `wp-config.php` (live only).
  Full guide + deploy checklist: `project_asset/docs/demo-site-lockdown.md`
- Storefront branding: the shop is styled as **Northline Supply Co.** (apparel)
  via the git-tracked child theme `wp-content/themes/northline/` (child of
  Twenty Twenty-Five) plus `wp pdwchs-demo setup-storefront` (idempotent; sets
  currency, product copy, categories, About/Contact, shipping). Guide:
  `project_asset/docs/storefront-setup.md`
- Build a migration plugin that pushes demo data to the live site (REST API or
  similar).
- Next planned task: `project_asset/docs/update-01.txt`.
- Git remote: https://github.com/moklesur21/ch.git
