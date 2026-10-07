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
- `project_asset/` — project docs and demo assets (`marketing/` — see below;
  `content/videos/` — per-video docs and recordings, see "Demo videos" below)
- `scripts/` — helper scripts: `wpc` (wp-cli wrapper), `demo-refresh.sh`
  (shoot-morning data refresh), `live-refresh.sh` (live-site refresh via REST),
  `install-plugin.sh` (plugin builds from the dev repo)

## Plugin updates

The two plugin folders are **build output**, never source. Update them only with
`scripts/install-plugin.sh [git-ref]` (default `release`): it builds the ref in a
throw-away worktree of the dev repo
(`/Applications/MAMP/htdocs/puredevs-customer-history/wp-content/plugins/puredevs-customer-history-for-woocommerce`)
with its `build.sh`, and swaps in the free and pro builds. Never copy, symlink or
check out the dev source tree here — the free folder would carry `pro/` and turn
premium. `project_asset/plugin-build.txt` records the installed version and commit.
After an install: load wp-admin once, run `scripts/demo-refresh.sh`, commit, and
upgrade the live site to the same version before the next `live-refresh.sh`
(parity needs the same plugin version — `project_asset/docs/live-migration.md` §5).

## Marketing assets

`project_asset/marketing/` is the home of all marketing material (moved from the
dev repo's `docs/marketing/` on 2026-09-26). All marketing work — brief updates,
screenshots, strategy, videos — happens here; the dev repo is for development
only. Its `marketing` branch still holds the 2026-09-26 copy, frozen and
unmaintained: never sync from it or edit it.
- `MARKETING-BRIEF-3.1.2.md` — screen-by-screen feature reference with entry IDs.
  Its source `file:line` references resolve inside the installed plugin folders
  (`pro/…` → the `-pro` folder) while `plugin-build.txt` says 3.1.2.
- `MARKETING-BRIEF-3.1.2*.pdf` + `…-PDF-INDEX.md` — PDF export and page index.
  The PDF generator scripts were lost; regenerating needs new ones.
- `screenshots/` — 102 callout shots (captured on the dev store's seed data).
- `PureDevs_Proof_Marketing_Ad_Strategy_v1.md` — ad strategy.
- `tooling/` — Playwright capture scripts; see `tooling/README.md`. Never run its
  `seed-demo-data.php` here (use `scripts/demo-refresh.sh`).

## Local environment

- Site URL: `http://localhost/ch` (MAMP Apache). DB `ch`, prefix `pp09_`.
- wp-cli needs MAMP's MySQL socket:
  `php -d mysqli.default_socket=/Applications/MAMP/tmp/mysql/mysql.sock /opt/homebrew/bin/wp <command>`
- Shorthand for the above: `scripts/wpc <command>` (injects socket + `--path`;
  adds `--socket` for `db export`/`db import`). Shoot-morning routine:
  `scripts/demo-refresh.sh` — refresh + verify + record/don't-record verdict
  in one command.
- MAMP's MySQL 5.7 needs `/Applications/MAMP/conf/my.cnf` with
  `innodb_open_files = 300` and `table_open_cache = 400` (added 2026-09-28,
  outside the repo — re-create after a MAMP reinstall). Without it mysqld
  passes 1024 open files and drops concurrent connections (select() /
  FD_SETSIZE), so the plugin dashboard's parallel AJAX shows "—".
- Both builds are installed side by side; activate one at a time — free for the
  realistic upgrade-flow demo, pro for Pro features (pro is active as of 2026-09-26).
- V8 throw-away store: `/Applications/MAMP/htdocs/ch-v8` (outside the repo, DB
  `ch_v8`, `http://localhost/ch-v8`) — the Northline store with the PureDevs
  plugin never installed, for V8's one-take wp.org install. Built, reset to its
  pre-install snapshot and checked only with `scripts/v8-site.sh`
  (`create | update | reset | check | snapshot | wp … | destroy --yes`); delete it
  once V8 is published. Videos always run the latest WordPress (`update`).
- Auto-login helper (mu-plugin): `http://localhost/ch/?local_login=1&user_email=<email>`
  — gated to `WP_ENVIRONMENT_TYPE=local` and disabled when `PDWCHS_DEMO_LOCKDOWN`
  is defined. Never deploy this file to the live VPS — nor the screenshot
  tier override `wp-content/mu-plugins/pdwchs-capture-tier.php` (capture only).

## Roadmap / notes

- Freemius sandbox checkout: wired up via the `WP_FS__<slug>_SECRET_KEY`
  constant in `wp-config.php` — paste the product secret key there to enable.
  Full guide: `project_asset/docs/freemius-sandbox.md`
- Demo-site lockdown: read-only public viewer (`demo`/`demo1234`) for the live
  site, enforced by `pdwchs-demo-tools` →
  `includes/class-pdwchs-demo-lockdown.php`. Inert unless
  `PDWCHS_DEMO_LOCKDOWN` is defined true in `wp-config.php` (live only).
  Full guide + deploy checklist: `project_asset/docs/demo-site-lockdown.md`
- Demo videos: the July V2–V4 set (Champions/5-5-5 hero, refund + on-hold
  cases, five-month flat-total revenue shape) was retired on 2026-09-27; the
  seeder still builds that data, but `wp pdwchs-demo verify` was re-scoped on
  2026-09-28 to the v4.2 videos (`v1_*`, `v4_*`; `dash_*` warn only). Video
  work now follows
  `project_asset/strategy/PureDevs_Video_Docs/` (Topic Filter v4.2 + Prompt
  Pack v3). Per-video docs, scene files and recordings live in
  `project_asset/content/videos/vN/` (video/audio files are gitignored). The
  workflow — who does what, gates, the recorder, status per video — is
  `project_asset/docs/video-production.md`; the step-by-step how-to (voice via
  the ElevenLabs API, edit, music bed and clicks from the shared library at
  `~/Music/PureDevs-Audio-Library`, QC, reuse for a new product) is
  `project_asset/docs/video-pipeline-guide.md`. The old V2–V4 coverage report was
  removed on 2026-07-30 with the superseded docs; read it from git history:
  `git show a5a0bb4^:project_asset/docs/v2-v4-coverage-report.md`.
- Storefront branding: the shop is styled as **Northline Supply Co.** (apparel)
  via the git-tracked child theme `wp-content/themes/northline/` (child of
  Twenty Twenty-Five) plus `wp pdwchs-demo setup-storefront` (idempotent; sets
  currency, product copy, categories, About/Contact, shipping). Guide:
  `project_asset/docs/storefront-setup.md`
- Live migration: solved — demo data is *regenerated* on the live box via the
  demo-tools REST API (deterministic seeder), never copied. Guide:
  `project_asset/docs/live-migration.md`; day-to-day: `scripts/live-refresh.sh`.
- **Live deploy completed 2026-07-23**: ch.puredevs.com seeded + verified
  (48 pass / 0 fail, twin of local), lockdown viewer (`demo`/`demo1234`)
  confirmed read-only. Known delta: Marcus's *Brand Loyal* tag is absent on
  live (older WooCommerce lacks the `product_brand` taxonomy) — appears
  after a Woo update + refresh. Recordings are always local, so no impact.
- Git remote: https://github.com/moklesur21/ch.git
