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
| `voice-gen.mjs` + `voice.json` | Demo-video voice through the ElevenLabs API (Yeasir's own-voice clone, Eleven v4): `node voice-gen.mjs --setup` once, then `--video v8 [--dry]` (one take per chunk, transcribed and checked word for word against `recordings/voice-over-elevenlabs.md`; up to two retries only on a failed check → `audio/<chunk>.mp3`), `--redo` / `--pick` / `--fix <chunk>`, and `--process` (one loudness chain → `recordings/voice/*.wav`). Locked choices in `voice.json`; the API key only in the macOS Keychain (service `elevenlabs-api`). |
| `render-edit.mjs` | Demo-video edit renderer: plays `content/videos/<vN>/edit.json` (speed ranges, zooms, labels, voice, end card, and the `"sound"` block: music bed, clicks, typing from the shared library) into 1080p segments, `--master` (joined, captions burned in) and `--cut` (the 20–30 s cut). Both outputs go through `qc-video.mjs`. |
| `audio-lib.mjs` + `audio-lib.json` | The shared audio library, kept **outside the repo** (`~/Music/PureDevs-Audio-Library`, or `$PUREDEVS_AUDIO_LIB`) so other projects can use it: a music bed (ElevenLabs Music) and UI sounds (mouse clicks, typing; ElevenLabs Sound Effects). `node audio-lib.mjs` lists, `--make all` makes what's missing; prompts, seeds, loudness and licence go to the library's `library.json` and `README.md`. `audio-lib.json` also holds the mix levels `render-edit.mjs` uses. |
| `voice_phrases.py` | For the per-video conform scripts (`content/videos/vN/conform-voice.py`): each phrase's start and end in a voice take — cuts in the pauses, phrase boundaries from voice-gen's speech-to-text word times. |
| `qc-video.mjs` | Delivery check for any video file: 1920 × 1080, 30 fps, H.264, AAC 48 kHz, −14 ±0.5 LUFS, true peak ≤ −1 dBTP, length (`--cut` for 20–30 s). `--fix` repairs only the loudness, picture stream-copied. |

The demo-video tools (`record-scenes`, `make-end-card`, `voice-gen`, `audio-lib`, `render-edit`, `qc-video`,
`voice_phrases.py`) are explained step by step in `project_asset/docs/video-pipeline-guide.md`.

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
