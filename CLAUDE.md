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

## Roadmap / notes

- Set up a Freemius sandbox checkout for purchase testing:
  https://freemius.com/help/documentation/checkout/integration/testing/
- Build a migration plugin that pushes demo data to the live site (REST API or
  similar).
- Next planned task: `project_asset/docs/update-01.txt`.
- Git remote: https://github.com/moklesur21/ch.git
