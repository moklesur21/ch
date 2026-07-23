# Demo Store Runbook — build, record, and keep ch.puredevs.com fresh

**Companion to:** [`PureDevs_V1_Demo_Recording_Guide.md`](PureDevs_V1_Demo_Recording_Guide.md) (the shot-list/acceptance authority — this runbook is the *how*, that guide is the *contract*).
**Tooling:** `docs/demo/pdwchs-demo-tools/` — an internal ops plugin that never ships to customers (`.distignore` excludes `docs/` from both builds; verified against the built zips).

---

## 1. The model

- **Two twin stores, built by generation — never by cloning.** The local MAMP store is where the dataset is built, verified, and **all video is recorded** (F11 fullscreen wp-admin → the domain is never on camera). ch.puredevs.com is what prospects see live.
- **Everything is anchored.** The seeder computes every date relative to an **anchor** (default: today) under a fixed RNG seed. Same anchor + seed → identical dataset. Run it any day → same *shape*, fresh dates: newest orders are from today/yesterday and the hero customer is *always* exactly **51 days** silent.
- **Data is on-spec on refresh day.** All plugin analytics are windows relative to "now", so the dataset decays: the hero drops out of the High churn band within ~10–14 days, the Declining-Health widget empties after the product's next 3 AM health cron, and the revenue chart eventually flatlines. **Refresh before every demo and every recording session.** The `verify` command tells you when you forgot.

```
refresh = teardown (only seeded data, ledger-driven)
        → re-seed anchored today → recalc everything → fixups → alerts
        → verify (Phase-1E acceptance as machine checks)
```

## 2. One-time local setup (MAMP)

Uses the checked-in wrapper `scripts/wpc` (injects MAMP's MySQL socket +
`--path`; adds `--socket` for `db export`/`db import`). Run `scripts/wpc <command>`
from the repo root, or `alias wpc='/Applications/MAMP/htdocs/ch/scripts/wpc'` —
every `wpc …` command below then works verbatim.

1. **wp-config.php** — add above `/* That's all */`:
   ```php
   define( 'PDWCHS_DEMO_ALLOW', true );   // enables seed/refresh/wipe on THIS site
   ```
2. **Install the tooling** (symlink from the repo — already done on this machine):
   ```bash
   ln -s "$PDWCHS_SITE/wp-content/plugins/puredevs-customer-history-for-woocommerce/docs/demo/pdwchs-demo-tools" \
         "$PDWCHS_SITE/wp-content/plugins/pdwchs-demo-tools"
   wpc plugin activate pdwchs-demo-tools
   ```
3. **Pro license** — `wpc pdwchs-demo verify` currently reports `Pro build active + licensed: false` on this machine. Activate the Freemius license (product 7511) in `Customer History → Account` first — **seeding refuses to recalc without it** (guide Phase 0.5 checks the same thing).
4. **Backup before destroying anything** (guide 0.9): UpdraftPlus backup **and** `wpc db export pre-demo-seed.sql`.
5. **One-time junk purge** — deletes ALL orders (51), all customer/subscriber users, sessions, carts, and every analytics table on the local store. Products and settings stay:
   ```bash
   wpc pdwchs-demo wipe-legacy --yes-really
   ```
6. **First seed + acceptance check:**
   ```bash
   wpc pdwchs-demo seed          # ~2-4 minutes: identity, catalog, 51 customers, ~230 orders, sessions, carts, full recalc
   wpc pdwchs-demo verify        # every Phase-1E line as PASS/FAIL
   ```
7. Founder picks (optional, before seeding — defaults in parentheses):
   ```bash
   wpc option update pdwchs_demo_store_name "Northline Supply Co."
   wpc option update pdwchs_demo_admin_name "Sarah M."
   wpc option update pdwchs_demo_hero_name  "Daniel Mercer"
   ```
8. Catalog realism (founder's call): the exported catalog is currently the WooCommerce sample set ("WordPress Pennant", "Album"…). Order totals are independent of product prices (line totals are overridden), but product names appear in Top Searches / Most-Viewed / order lines. To upgrade: rename products in wp-admin, then re-run `wpc pdwchs-demo export-catalog` so the live bootstrap uses the same set.

## 3. Recording workflow (local)

**Every recording morning:**

```bash
wpc pdwchs-demo refresh --anchor=$(date +%F)   # or a PINNED anchor, see below
wpc pdwchs-demo verify --report
```

Checked-in shortcut: `scripts/demo-refresh.sh [anchor] [--snapshot]` runs both
commands, strips PHP deprecation noise, and prints a record / don't-record
verdict (exit 0 = record).

- `verify` must be **0 FAIL** before recording (warnings: read them, decide).
- `--report` prints the **Phase-2 report-back sheet** values (hero health score/band/trend, churn % + label, exact days-since/overdue, primary indicator, totals, rank, both dropdown option lists) — fill the guide's Phase-2 table from it. Recording is blocked until that sheet is returned (guide rule).
- **Pin the anchor across the 12-video series**: recording V1 on July 25 → `--anchor=2026-07-25`. Re-running with the same anchor + seed on any later day reproduces the **byte-identical dataset** (same names, same numbers), so retakes and videos 2–12 stay consistent with the patched narration. A pinned anchor N days in the past shifts "days since" by +N — verify accounts for it and warns; for on-camera number consistency always record within the anchor day, or re-pin and re-patch narration.
- **Snapshot the approved state** after the guide's §1D coherence walk passes (label `V1-approved-dataset`): UpdraftPlus backup + `wpc db export V1-approved-dataset.sql` + write down the triple **(git commit, rng-seed 20260722, anchor)**. Restore for pixel-identical retakes; re-seed with the pinned anchor for a clean rebuild.
- Camera hygiene (guide Phase 0): dismiss notices, update everything beforehand, deactivate Freemius Debug, don't scroll the version footer into held shots. The seeder already handles store name / currency / admin display name.

## 4. Live update — ch.puredevs.com

**Deploy is SFTP/SSH once; day-to-day triggering is REST from anywhere.**

One-time (or per plugin release):

1. Build + upload the product plugin: `./build.sh pro` → install `dist/puredevs-customer-history-for-woocommerce-pro.zip` (Plugins → Add New → Upload, or unzip over SFTP). Must be a build containing the `Health.php` engagement fix.
2. Upload `docs/demo/pdwchs-demo-tools/` → `wp-content/plugins/pdwchs-demo-tools/` and activate. Optional deactivation-proofing: copy `pdwchs-demo-tools/mu-loader/pdwchs-demo-tools-loader.php` → `wp-content/mu-plugins/`.
3. `wp-config.php` on the live box:
   ```php
   define( 'PDWCHS_DEMO_ALLOW', true );
   define( 'PDWCHS_DEMO_TOKEN', '<32+ random hex chars — generate with: openssl rand -hex 24>' );
   ```
4. **WAF check** (the domain currently 403s automated clients): confirm `/wp-json/pdwchs-demo/v1/*` is reachable from your machine — `curl -H "X-PDWCHS-Demo-Token: $TOKEN" https://ch.puredevs.com/wp-json/pdwchs-demo/v1/status` must return JSON, not 403 HTML. If blocked, allowlist the path (or your IP) in the host/WAF panel.
5. Freemius license active on the live site (production activation on product 7511).
6. One-time junk purge of the old screenshot-era data (backup first, then either):
   ```bash
   curl -X POST -H "X-PDWCHS-Demo-Token: $TOKEN" -H "Content-Type: application/json" \
        -d '{"confirm":"yes-really"}' https://ch.puredevs.com/wp-json/pdwchs-demo/v1/wipe-legacy
   # or over SSH: wp pdwchs-demo wipe-legacy --yes-really
   ```
7. First seed: `POST /refresh` (below) and poll `/status` until `done`, then `GET /verify`.

## 5. Keeping the demo fresh for prospects (the "time to time" update)

This is checked in as `scripts/live-refresh.sh` (POST /refresh → poll /status
with one auto-resume on stall → GET /verify, single command; token from
`~/.config/pdwchs/demo-token`). Full deploy + day-to-day guide:
[`../docs/live-migration.md`](../docs/live-migration.md). Raw curl equivalent
for reference:

```bash
TOKEN='<the wp-config token>'
curl -X POST -H "X-PDWCHS-Demo-Token: $TOKEN" https://ch.puredevs.com/wp-json/pdwchs-demo/v1/refresh
# → {"run_id":"run_…","message":"Refresh running in background — poll GET /status."}
```

Run it **the morning of any prospect demo** (takes a few minutes in background slices), then:

```bash
curl -H "X-PDWCHS-Demo-Token: $TOKEN" https://ch.puredevs.com/wp-json/pdwchs-demo/v1/status   # until run.status = done
curl -H "X-PDWCHS-Demo-Token: $TOKEN" https://ch.puredevs.com/wp-json/pdwchs-demo/v1/verify | python3 -m json.tool | grep -E '"status"|counts' | head
```

What a refresh guarantees: newest orders are from today/yesterday; revenue chart continuous; hero back to exactly 51 days / High risk; **the alert row is reset to New / Unassigned** (so the LF-08 assignment you performed for the previous prospect is cleared); "Last calculated" timestamps fresh.

**10-minute pre-demo checklist:** refresh done → verify 0 FAIL → walk the four screens (Dashboard, Churn → At-Risk, Churn → Alerts, hero profile) → no admin notices/update badges → logged in as the human-named admin.

**Optional automation later** (zero code changes): any scheduler that can run curl —
```
15 5 * * * curl -s -X POST -H "X-PDWCHS-Demo-Token: …" https://ch.puredevs.com/wp-json/pdwchs-demo/v1/refresh
```

## 6. Command reference

| CLI (`wpc pdwchs-demo …`) | Does |
|---|---|
| `plan [--anchor=…] [--rng-seed=…]` | Dry-run: census + analytic hero pre-check. No writes. |
| `seed [--anchor] [--rng-seed] [--force]` | Full build on a clean store (`--force` = teardown first). |
| `refresh [--anchor]` | Teardown → seed(today) → recalc → fixups → alerts → verify. |
| `recalc` | Re-run intelligence calcs + fixups + alerts on current data. |
| `verify [--report]` | Phase-1E checks; `--report` adds the Phase-2 sheet. Read-only. |
| `teardown [--with-catalog]` | Remove only seeded data (ledger-driven). |
| `wipe-legacy --yes-really` | One-time purge of pre-existing junk. |
| `export-catalog` | Write current products → `data/catalog.json` + images. |
| `status` | Latch/token/ledger/run/verify summary. |

| REST (`https://…/wp-json/pdwchs-demo/v1`) | Auth | Does |
|---|---|---|
| `POST /refresh` | token + latch | 202 + run_id; background self-chaining ~18s slices; safe to re-POST if a run stalls >3 min (it resumes). |
| `GET /status` | token | Run progress, ledger summary, last verify counts. |
| `GET /verify?report=1` | token | Full checks (+ Phase-2 sheet). |
| `POST /wipe-legacy` `{"confirm":"yes-really"}` | token + latch | One-time junk purge, background. |
| `POST /setup-storefront` | token + latch | Northline branding pass (theme, copy, pages, shipping). Synchronous, idempotent — replaces the SSH `wp pdwchs-demo setup-storefront` on boxes without WP-CLI. |

wp-admin: **Tools → PDWCHS Demo** shows the same status read-only (deliberately not under the Customer History menu — that sidebar is on camera).

## 7. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `Pro build active + licensed: FAIL` | Freemius license inactive on this site — activate in Customer History → Account. Recalc steps refuse without it ("Pro intelligence not loaded"). |
| Hero shows Medium, or missing from Declining Health | Dataset is stale (anchor < today) or the product's overnight crons overwrote the two-pass delta — run `refresh`. `verify` prints staleness. |
| `wc_order_stats parity` warn / Woo Analytics off | Action Scheduler backlog: `wpc action-scheduler run` (or just re-run `recalc`, its drain step loops until empty). |
| `Health engagement sub-score alive` warn | The live site runs a pro build WITHOUT the `Health.php:268` table-name fix — deploy a current build. |
| REST returns 403 with valid token | Host WAF blocking `/wp-json/pdwchs-demo/v1/*` — allowlist the path/IP. Token must be ≥16 chars and sent as `X-PDWCHS-Demo-Token`. |
| Run stuck `running` | `GET /status`; if `updated` >3 min old, re-`POST /refresh` — the stalled run resumes from its last completed step. CLI equivalent: re-run the command. |
| `MySQL date == site date` warn | MySQL server timezone differs from site TZ around midnight — "51 days" can read 50/52 between ~00:00–06:00 local. Demo/record during daytime, or align the server TZ to UTC. |
| Deprecation warnings in CLI output | From the product plugin under PHP 8.2 — noise, pipe through `grep -v Deprecated`. |
| Numbers must match an earlier recording exactly | Restore the `V1-approved-dataset` snapshot, or `refresh --anchor=<pinned>` with the recorded (commit, seed, anchor) triple. |

## 8. What the seeder builds (for orientation)

86 customers / ~348 orders over 14 months / ~3,700 session rows / 35 carts / 2 partial refunds + 1 on-hold order, engineered against the product's actual scoring code so that: Churn → At-Risk shows **7 rows** (2 Critical, 2 High incl. the hero at ~64, 3 Medium), 4 alerts (hero's = New/Unassigned, the demo's final click), all 6 lifecycle stages populated, Declining Health = 3 names including the hero, "At Risk 30+ Days" spans medium/high/critical, and the conversion funnel is monotonic. The two at-risk names from the old screenshots live on as the Criticals: **Everett Cole** and **John Walton** (guide 0.7).

Since the V2–V4 extension (see [`../docs/v2-v4-coverage-report.md`](../docs/v2-v4-coverage-report.md)) the same dataset also carries: **Champions = 8** with a clean 5/5/5 hero (**Marcus Hale**, #1 spender, Low churn, Brand Loyal tag), a five-calendar-month revenue shape that is **flat in total (~$3.2-3.3k/mo) with returning −29% and new ×2.6** (the V4 story), steady guest revenue, and the V3 refund/on-hold cases (incl. a cross-month refund that makes the plugin and Woo Analytics disagree honestly in single-month views). `verify` machine-checks all of it (48 checks); `--report` prints the V2 hero block, refund cases and the month table. Everything it creates is tagged and ledger-tracked; `teardown` removes only that (refunds cascade with their parent orders).
