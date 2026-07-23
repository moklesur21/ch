# Live demo migration — ch.puredevs.com

How demo data gets from this local MAMP site onto the live demo, and how to
keep it fresh. Consolidates the deploy steps from
[`DEMO-STORE-RUNBOOK.md`](../demo/DEMO-STORE-RUNBOOK.md) §4–§5,
[`storefront-setup.md`](storefront-setup.md) and
[`demo-site-lockdown.md`](demo-site-lockdown.md) into one ordered checklist.
The runbook stays the local recording bible.

---

## 0. The model: regenerate, never copy

**Demo data is never migrated as data.** The same `pdwchs-demo-tools` plugin is
deployed to the live box, and its seeder *regenerates* the dataset there via
token-authenticated REST (`/wp-json/pdwchs-demo/v1/*`). The seeder is
deterministic:

> same **anchor** + same **RNG seed (20260722)** + same **plugin version**
> ⇒ byte-identical dataset — same names, same order counts, same totals,
> same scores — on both stores.

So "migrating" the demo data = triggering a ~3-minute refresh on live with the
anchor you verified locally. One command: `scripts/live-refresh.sh`.

**Why we don't copy the database:**

- A local dump bakes in `http://localhost/ch` URLs and the `pp09_` table
  prefix; fixing that needs serialized-PHP-safe search-replace — a classic
  breakage source.
- The dump would carry local-only secrets and users (admin password, Freemius
  *sandbox* key, the auto-login helper's assumptions).
- `*.sql` is gitignored by design; dumps are 19 MB and stale the moment the
  clock moves — the seeder is the source of truth, not any snapshot.
- A REST refresh is faster than dump → upload → import → repair, and resets
  demo-lockdown clutter (e.g. the hero's alert back to New/Unassigned).

This resolves the old roadmap item "build a migration plugin": the demo-tools
REST API **is** the migration mechanism.

## 0.5 What runs where (read this once)

```
YOUR MAC                                LIVE SERVER (ch.puredevs.com)
────────────────────────                ─────────────────────────────
scripts/demo-refresh.sh  ── local ──►   (never used here)
scripts/live-refresh.sh  ── HTTPS ──►   WordPress + the two plugins + theme
curl commands in this doc ─ HTTPS ──►   (files uploaded ONCE, then driven
                                         remotely via the plugin's API)
```

- The **server** only ever receives files: the product plugin,
  `pdwchs-demo-tools`, and the northline theme — uploaded via SFTP or the
  hosting file manager.
- The **scripts stay on your Mac**. `demo-refresh.sh` is for the local MAMP
  site only. `live-refresh.sh` runs on your Mac and drives the live site over
  the internet — you never copy it to the server.
- **No SSH and no WP-CLI are needed on the server at any point.** Every
  server-side action (wipe, seed, refresh, verify, storefront branding) is a
  token-authenticated API call made from your Mac.

## 1. Prerequisites

- SFTP or hosting file-manager access to the live box + the hosting/WAF
  control panel + live wp-admin (SSH optional, never required).
- Local dataset verified: `scripts/demo-refresh.sh` ends **0 FAIL**.
- An API token for the trigger: generate with `openssl rand -hex 24`, store it
  locally once:

  ```bash
  mkdir -p ~/.config/pdwchs
  printf '%s' '<token>' > ~/.config/pdwchs/demo-token
  chmod 600 ~/.config/pdwchs/demo-token
  ```

## 2. One-time deploy (ordered)

1. **Catalog parity first.** If product names/images changed locally, run
   `scripts/wpc pdwchs-demo export-catalog` — it writes `data/catalog.json` +
   images *inside* the `pdwchs-demo-tools` plugin, so step 3 carries the
   catalog to live automatically.
2. **Product plugin.** Build (`./build.sh pro`) and upload the pro zip
   (Plugins → Add New → Upload, or unzip over SFTP). Must be a build containing
   the `Health.php` engagement fix (runbook §7 has the symptom if not).
3. **Demo tooling.** Upload `wp-content/plugins/pdwchs-demo-tools/` and
   activate. Optional deactivation-proofing: copy
   `pdwchs-demo-tools/mu-loader/pdwchs-demo-tools-loader.php` →
   `wp-content/mu-plugins/`.
4. **Theme.** Deploy the `northline` child theme via git per
   [`storefront-setup.md`](storefront-setup.md).
   **Never** upload `wp-content/mu-plugins/auto_login_local.php`, the local
   `wp-config.php`, or anything from `backups/`.
5. **Live wp-config.php** (above `/* That's all */`):

   ```php
   define( 'PDWCHS_DEMO_ALLOW', true );            // enables seed/refresh here
   define( 'PDWCHS_DEMO_TOKEN', '<the token from §1>' );
   define( 'PDWCHS_DEMO_LOCKDOWN', true );         // read-only public viewer
   // Sandbox checkout on the live demo — accepted decision (demo-site-lockdown.md):
   // visitors test-purchase with test cards, never real charges.
   define( 'WP_FS__puredevs-customer-history-for-woocommerce_SECRET_KEY', 'sk_…' );
   // NEVER define WP_FS__DEV_MODE or WP_FS__SKIP_EMAIL_ACTIVATION here —
   // dev-only (Freemius Debug menu / activation shortcut), not for a public site.
   ```

   plus the rest of the lockdown checklist in
   [`demo-site-lockdown.md`](demo-site-lockdown.md) (WP_DEBUG off,
   `WP_ENVIRONMENT_TYPE` production, registration off, `DISALLOW_FILE_EDIT`).
   The token-authenticated refresh runs as user 0, so lockdown never blocks it.
6. **WAF check.** The domain currently 403s automated clients. Confirm the API
   is reachable:

   ```bash
   curl -H "X-PDWCHS-Demo-Token: $(cat ~/.config/pdwchs/demo-token)" \
        https://ch.puredevs.com/wp-json/pdwchs-demo/v1/status
   ```

   Must return JSON, not 403 HTML. If blocked, allowlist
   `/wp-json/pdwchs-demo/v1/*` (or your IP) in the host/WAF panel.
7. **License.** Activate the Freemius **production** license on live
   (product 7511) — recalc refuses to run without it.
8. **Storefront branding.** From your Mac (idempotent — theme, currency,
   product copy, categories, About/Contact, shipping; prints a log):

   ```bash
   curl -X POST -H "X-PDWCHS-Demo-Token: $(cat ~/.config/pdwchs/demo-token)" \
        https://ch.puredevs.com/wp-json/pdwchs-demo/v1/setup-storefront
   ```

   (Equivalent, only if WP-CLI ever gets installed on the box:
   `wp pdwchs-demo setup-storefront`.)
9. **One-time junk purge.** Back up live first, then:

   ```bash
   curl -X POST -H "X-PDWCHS-Demo-Token: $(cat ~/.config/pdwchs/demo-token)" \
        -H "Content-Type: application/json" -d '{"confirm":"yes-really"}' \
        https://ch.puredevs.com/wp-json/pdwchs-demo/v1/wipe-legacy
   ```
10. **First seed:** `scripts/live-refresh.sh` — POSTs `/refresh`, polls
    `/status` until done, then verifies. Must end **LIVE OK — 0 FAIL**.

## 3. Parity check (after any deploy)

Same morning, run both with the same anchor:

```bash
scripts/demo-refresh.sh            # local
scripts/live-refresh.sh            # live (defaults to the same UTC today)
```

Both report sheets must show identical hero numbers (health 60, churn 64 %
High, $931, 11 orders, rank #4, 51 days silent). A mismatch means plugin
version skew (redeploy step 2/3) or catalog drift (redo step 1, re-upload
demo-tools, refresh again).

## 4. Day-to-day

| Situation | Command |
|---|---|
| Local shoot morning | `scripts/demo-refresh.sh` |
| Prospect demo on the live site | `scripts/live-refresh.sh` that morning |
| Side-by-side / parity needed | both, same anchor, same morning |

- A live refresh rebuilds public data for a few minutes mid-run — trigger it
  before demos or off-hours, not during a walkthrough.
- Optional automation (any scheduler that can curl):
  `15 5 * * * curl -s -X POST -H "X-PDWCHS-Demo-Token: …" https://ch.puredevs.com/wp-json/pdwchs-demo/v1/refresh`

## 5. Per plugin release

Rebuild + upload the plugin(s) (steps 2–3) → `scripts/live-refresh.sh` →
parity check (§3). A release that changes scoring logic changes the numbers —
re-verify before pointing prospects at it.

## 6. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| 403 with **HTML** body | Host WAF blocking `/wp-json/pdwchs-demo/v1/*` — allowlist path/IP (§2.6). |
| `pdwchs_demo_forbidden` | Token mismatch — header vs live `PDWCHS_DEMO_TOKEN` (≥16 chars). |
| `pdwchs_demo_blocked` | `PDWCHS_DEMO_ALLOW` missing/false in live wp-config. |
| 404 on the API | demo-tools not deployed/active (mu-loader keeps it alive if deactivated). |
| 409 busy | A run is already in progress — `live-refresh.sh` attaches and polls it. |
| Run stalls | Server resumes stalled runs on re-POST; the script does this once automatically. |
| Finished with the wrong anchor | An older stalled run was resumed — the script flags it; just re-run. |
| verify FAILs | Same meanings as local — runbook §7. |
| Numbers differ from local | §3 parity causes: plugin version skew or catalog drift. |

## 7. Safety rails

- Never `wp db import` a local dump on live; never search-replace a local dump
  onto live. Regenerate instead (§0).
- Files that must never leave this machine: `wp-config.php`,
  `wp-content/mu-plugins/auto_login_local.php`, `backups/*.sql`.
- The Freemius *sandbox* secret key is the accepted exception (see
  demo-site-lockdown.md): it IS defined on the live demo so checkout stays in
  sandbox (test cards, no real charges). What must never be defined on live:
  `WP_FS__DEV_MODE` and `WP_FS__SKIP_EMAIL_ACTIVATION`.
- The live box needs exactly: the two product plugins' builds, demo-tools,
  the northline theme, and the wp-config constants above — nothing else from
  this repo.
