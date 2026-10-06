# Marketing screenshot tooling

Helpers that produce `../screenshots/` for `../MARKETING-BRIEF-3.1.2.md`. Moved here from the
plugin repo's `docs/marketing/` on 2026-09-26; this `ch` copy is now the maintained one.
Run everything on the **local** `ch` store only — never against ch.puredevs.com.

The 102 shots in the 3.1.2 brief were captured on the dev store with `seed-demo-data.php`.
On `ch` the data comes from `pdwchs-demo-tools` (Northline dataset) instead, so re-shot
screens show different customers and numbers than the committed ones.

## Files

| File | Purpose |
|---|---|
| `seed-demo-data.php` | **Dev store only — never run on `ch`** (it would mix a second dataset into the Northline one; use `scripts/demo-refresh.sh`). Seeds synthetic customers, orders, sessions, carts, tags, notes, segments and settings, then runs every nightly intelligence job once. Tags what it creates (`_pdwchs_demo_seed` meta, `10.42.*` IPs). Refuses to run twice. |
| `unseed-demo-data.php` | **Dev store only.** Removes everything the seeder created. |
| `pdwchs-capture-tier.mu-plugin.php` | Temporary override that forces the Pro or Free tier from the `pdwchs_capture_tier` option (`pro` / `free`). Needed because Pro is licence-gated in two places: the `pdwchs_is_pro_active` filter and direct `pchfwv_fs()->can_use_premium_code()` calls in six Pro templates. **Never deploy it.** |
| `build-shots.mjs` → `shots.json` | The shot manifest: URL, actions, clip region and numbered callouts per screenshot ID. Edit the `.mjs`, then run it. |
| `capture-screens.mjs` | Logs in, captures each shot with the installed Google Chrome (playwright-core, no browser download) and draws the callouts in the page. `{HERO}` resolves to the dev seeder's hero, else the `ch` demo-tools ledger hero. |
| `build-index.mjs` | Regenerates `../screenshots/INDEX.md`. |
| `record-scenes.mjs` | Demo-video scene recorder: plays `content/videos/<vN>/scenes.json` on the local store and writes the clips, stills and delivery note to `<vN>/recordings/`. See `project_asset/docs/video-production.md` §5. |
| `make-end-card.mjs` | Demo-video end card: `node make-end-card.mjs --video v8` renders the `endCard` block of that video's `scenes.json` (answer-frame crop, optional outline, product name, one CTA, "Demo Store") to `recordings/end-card.png` + a 4 s `end-card.mp4`. |

## Steps on `ch` (macOS + MAMP)

```bash
CH=/Applications/MAMP/htdocs/ch
TOOLS=$CH/project_asset/marketing/tooling
alias wpc="$CH/scripts/wpc"

# 1. fresh data, anchored today (0 FAIL = good to shoot)
$CH/scripts/demo-refresh.sh

# 2. tier override (only needed for shots of both tiers)
cp $TOOLS/pdwchs-capture-tier.mu-plugin.php $CH/wp-content/mu-plugins/pdwchs-capture-tier.php
wpc option update pdwchs_capture_tier pro

# 3. a throw-away admin for the browser, stored outside git (.auth/ is gitignored)
wpc user create pdwchs-shot shot@example.invalid --role=administrator --user_pass='CHANGE-ME'
mkdir -p $TOOLS/.auth
echo '{"user":"pdwchs-shot","pass":"CHANGE-ME","base":"http://localhost/ch"}' > $TOOLS/.auth/creds.json

# 4. capture
cd $TOOLS && npm install
node build-shots.mjs
node capture-screens.mjs            # everything (about 25 minutes)
node capture-screens.mjs --only DASH-14,PROF-07
node capture-screens.mjs --tier free
node capture-screens.mjs --outline "/wp-admin/admin.php?page=pd-woo-dashboard"   # selector helper
node build-index.mjs

# 5. clean up — always
wpc user delete pdwchs-shot --reassign=1
wpc option delete pdwchs_capture_tier
rm -f $CH/wp-content/mu-plugins/pdwchs-capture-tier.php $TOOLS/.auth/creds.json
```

`creds.json` also accepts `site`, `php` and `wp` to point at a different store (defaults: the
`ch` root, MAMP PHP 8.2, MAMP's `wp`).

## Notes

- Selector syntax in the manifest: plain CSS, or `panel:Heading text`, `heading:Heading text`,
  `panel:Heading text >> css` (resolved in the page by heading text).
- A clean Free view of the Segments page (teaser + cap modal) needs a store with no Default segment
  rows and at most one custom segment; Pro seeds the five defaults the first time it boots.
- "At Risk Customers (30+ Days)" needs `lifecycle_days_in_stage >= 30`; the seeder back-dates stage
  dates for that reason.
- The data-integrity details panel only renders on a revenue mismatch, so it has no shot.
- `/private/tmp` is wiped on reboot. Keep anything you want to survive inside the repo.
