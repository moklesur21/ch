#!/bin/bash
# demo-refresh.sh — shoot-morning demo-data refresh for the LOCAL MAMP site.
#
# Runs   wp pdwchs-demo refresh --anchor=<date>   (teardown → seed → recalc → alerts)
# then   wp pdwchs-demo verify --report           (report sheet + 0-FAIL gate)
# and prints a clear record / don't-record verdict.
#
# Shoot-day rules (project_asset/demo/DEMO-STORE-RUNBOOK.md §3):
#   - Same-day retakes: nothing to do — keep recording.
#   - New shoot day:    run this with no args that morning; record the same day.
#   - Pinned anchor:    byte-identical numbers, but "days since" reads 51+N.

set -u   # NO set -e / pipefail — run_wp's grep pipeline depends on it (see below).

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO="$(cd "$SCRIPT_DIR/.." && pwd)"
WPC="$SCRIPT_DIR/wpc"
SOCK="/Applications/MAMP/tmp/mysql/mysql.sock"

if [ -t 1 ]; then
  BOLD="$(printf '\033[1m')"; GREEN="$(printf '\033[32m')"; RED="$(printf '\033[31m')"; RESET="$(printf '\033[0m')"
else
  BOLD=""; GREEN=""; RED=""; RESET=""
fi
say() { printf '%s\n' "${BOLD}$*${RESET}"; }
die() { printf '%s\n' "${RED}${BOLD}$*${RESET}" >&2; exit 1; }

usage() {
  cat <<'EOF'
Usage: scripts/demo-refresh.sh [YYYY-MM-DD] [--snapshot]

  (no args)     New shoot day — refresh anchored to today (UTC), then verify.
  YYYY-MM-DD    Pin the anchor: rebuild the byte-identical dataset of that
                shoot day (for retakes). "Days since" fields read 51+N.
  --snapshot    Timestamped `wp db export` into backups/ BEFORE the teardown.
  -h, --help    This help.

Exit 0 + green "0 FAIL" verdict = record; exit 1 = do NOT record.
Rules: same-day retakes need nothing; record the same day you refresh.
See project_asset/demo/DEMO-STORE-RUNBOOK.md §3 and, per video,
project_asset/docs/video-production.md.
EOF
}

ANCHOR=""
SNAPSHOT=0
while [ $# -gt 0 ]; do
  case "$1" in
    --snapshot) SNAPSHOT=1 ;;
    -h|--help)  usage; exit 0 ;;
    -*)         usage >&2; die "Unknown option: $1" ;;
    *)
      [ -n "$ANCHOR" ] && { usage >&2; die "Only one anchor date allowed (got '$ANCHOR' and '$1')."; }
      ANCHOR="$1"
      ;;
  esac
  shift
done

# Site timezone is UTC (verify enforces it), so "today" must be UTC-today.
TODAY="$(date -u +%F)"
[ -n "$ANCHOR" ] || ANCHOR="$TODAY"

case "$ANCHOR" in
  [0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]) : ;;
  *) die "Anchor must be YYYY-MM-DD (got: $ANCHOR)" ;;
esac

[ -x "$WPC" ] || die "Missing helper: $WPC"
[ -S "$SOCK" ] || die "MAMP MySQL socket not found ($SOCK) — start the MAMP servers first."

# PHP 8.2 spews "Deprecated:" lines over wp-cli output; strip them from BOTH
# streams (2>&1 merges) while keeping wp's exit code via PIPESTATUS[0].
# DO NOT add set -e/pipefail (grep exits 1 when it filters every line) and
# DO NOT insert anything between the pipeline and the `return`.
run_wp() {
  "$WPC" "$@" 2>&1 | grep -Ev '^(PHP +)?Deprecated:'
  return "${PIPESTATUS[0]}"
}

if [ "$ANCHOR" != "$TODAY" ]; then
  say ""
  say "Retake mode: pinned anchor $ANCHOR (today is $TODAY)."
  say "Numbers will be byte-identical to that shoot; live-computed \"days since\""
  say "fields read 51+N and verify will WARN about staleness — expected."
fi

START=$SECONDS

if [ "$SNAPSHOT" -eq 1 ]; then
  mkdir -p "$REPO/backups"
  SNAP="$REPO/backups/pre-refresh-$(date -u +%Y%m%d-%H%M%S).sql"
  say ""
  say "== Snapshot -> $SNAP"
  run_wp db export "$SNAP" || die "Snapshot failed — aborting BEFORE teardown (nothing was destroyed)."
fi

say ""
say "== Refresh (anchor $ANCHOR, ~3 min) =="
run_wp pdwchs-demo refresh --anchor="$ANCHOR" \
  || die "Refresh FAILED — data may be partially rebuilt. Fix and re-run; see runbook §7 (project_asset/demo/DEMO-STORE-RUNBOOK.md)."

say ""
say "== Verify =="
if run_wp pdwchs-demo verify --report; then VERIFY_OK=1; else VERIFY_OK=0; fi

ELAPSED=$((SECONDS - START)); MIN=$((ELAPSED / 60)); SEC=$((ELAPSED % 60))

echo ""
if [ "$VERIFY_OK" -eq 1 ]; then
  if [ "$ANCHOR" = "$TODAY" ]; then
    printf '%s\n' "${GREEN}${BOLD}0 FAIL — record today.  (anchor $ANCHOR, ${MIN}m${SEC}s)${RESET}"
  else
    printf '%s\n' "${GREEN}${BOLD}0 FAIL — retake dataset for $ANCHOR rebuilt.  (${MIN}m${SEC}s; staleness warnings expected)${RESET}"
  fi
  printf '%s\n' "If there are WARN lines above: read them, decide. The report sheet must match your narration."
  exit 0
else
  printf '%s\n' "${RED}${BOLD}VERIFY FAILED — do NOT record.${RESET}"
  printf '%s\n' "Fix and re-run. Troubleshooting: runbook §7 (project_asset/demo/DEMO-STORE-RUNBOOK.md)."
  exit 1
fi
