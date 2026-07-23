#!/bin/bash
# live-refresh.sh — regenerate the LIVE demo dataset on ch.puredevs.com via
# the pdwchs-demo-tools REST API (no SSH needed). Only works after the
# one-time deploy in project_asset/docs/live-migration.md.
#
#   POST /refresh          -> 202 {run_id}; background ~18s WP-Cron slices
#   GET  /status           -> poll until run.status = done (auto-resume one stall)
#   GET  /verify?report=1  -> check counts + Phase-2 report sheet
#
# Usage:
#   scripts/live-refresh.sh              # anchor = today (UTC)
#   scripts/live-refresh.sh 2026-07-25   # pinned anchor
#
# Token = the PDWCHS_DEMO_TOKEN constant from the LIVE wp-config.php, read from
# env PDWCHS_DEMO_TOKEN or the file ~/.config/pdwchs/demo-token.
# Target override: env PDWCHS_LIVE_URL (default https://ch.puredevs.com).
# Note: the token appears in `ps` args while curl runs — fine on a single-user
# Mac; use a shared box's secret store otherwise.

set -u

BASE="${PDWCHS_LIVE_URL:-https://ch.puredevs.com}"
API="$BASE/wp-json/pdwchs-demo/v1"
TOKEN_FILE="$HOME/.config/pdwchs/demo-token"
POLL_EVERY=10
STALL_AFTER=180   # mirrors the server's resume threshold (Runner::STALL_SECONDS)
MAX_WAIT=900

if [ -t 1 ]; then
  BOLD="$(printf '\033[1m')"; GREEN="$(printf '\033[32m')"; RED="$(printf '\033[31m')"; RESET="$(printf '\033[0m')"
else
  BOLD=""; GREEN=""; RED=""; RESET=""
fi
say() { printf '%s\n' "${BOLD}$*${RESET}"; }
die() { printf '%s\n' "${RED}${BOLD}$*${RESET}" >&2; exit 1; }

usage() {
  cat <<'EOF'
Usage: scripts/live-refresh.sh [YYYY-MM-DD]

Triggers a demo-data refresh on the live site via REST, waits for it, then
verifies. Same anchor + RNG seed as local => the live demo shows the exact
same numbers as your local recordings.

  (no args)     Anchor = today (UTC). Run the morning of any prospect demo.
  YYYY-MM-DD    Pinned anchor (match a local dataset for side-by-side parity).
  -h, --help    This help.

Setup + troubleshooting: project_asset/docs/live-migration.md
EOF
}

ANCHOR=""
while [ $# -gt 0 ]; do
  case "$1" in
    -h|--help) usage; exit 0 ;;
    -*)        usage >&2; die "Unknown option: $1" ;;
    *)
      [ -n "$ANCHOR" ] && { usage >&2; die "Only one anchor date allowed (got '$ANCHOR' and '$1')."; }
      ANCHOR="$1"
      ;;
  esac
  shift
done

# Live site timezone is UTC too (verify enforces it).
TODAY="$(date -u +%F)"
[ -n "$ANCHOR" ] || ANCHOR="$TODAY"
case "$ANCHOR" in
  [0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]) : ;;
  *) die "Anchor must be YYYY-MM-DD (got: $ANCHOR)" ;;
esac

# ── config checks (exit 2 = not configured; no network calls made) ──────────
TOKEN="${PDWCHS_DEMO_TOKEN:-}"
if [ -z "$TOKEN" ] && [ -f "$TOKEN_FILE" ]; then
  TOKEN="$(tr -d ' \t\r\n' < "$TOKEN_FILE")"
fi
if [ -z "$TOKEN" ]; then
  cat >&2 <<EOF
No API token found. The token is the PDWCHS_DEMO_TOKEN constant defined in the
LIVE site's wp-config.php during the one-time deploy
(see project_asset/docs/live-migration.md §2).

Provide it either way:
  export PDWCHS_DEMO_TOKEN='<token>'
or store it once:
  mkdir -p ~/.config/pdwchs
  printf '%s' '<token>' > ~/.config/pdwchs/demo-token
  chmod 600 ~/.config/pdwchs/demo-token
EOF
  exit 2
fi
command -v python3 >/dev/null 2>&1 || { printf 'python3 is required for JSON parsing (xcode-select --install).\n' >&2; exit 2; }

# ── helpers ─────────────────────────────────────────────────────────────────
# jget <dot.path> <json> — print the scalar at the path, empty if missing/null.
# (Python booleans print as True/False.)
jget() {
  printf '%s' "$2" | python3 -c '
import json, sys
try:
    d = json.load(sys.stdin)
except Exception:
    sys.exit(0)
for k in sys.argv[1].split("."):
    d = d.get(k) if isinstance(d, dict) else None
    if d is None:
        sys.exit(0)
print(d)' "$1"
}

# api <curl args…> — call the API; sets globals CODE (http status) and BODY.
api() {
  local out
  out="$(curl -sS -m 60 -H "X-PDWCHS-Demo-Token: $TOKEN" -w '\n%{http_code}' "$@")" || return 1
  CODE="$(printf '%s\n' "$out" | tail -n 1)"   # no `head` — shadowed on this Mac
  BODY="$(printf '%s\n' "$out" | sed '$d')"
}

post_refresh() {
  api -X POST -d "anchor=$ANCHOR" "$API/refresh" || die "curl failed on POST /refresh — network/DNS problem?"
  case "$CODE" in
    202)
      R_ANCHOR="$(jget anchor "$BODY")"
      RESUMED="$(jget resumed "$BODY")"
      say "Run $(jget run_id "$BODY") accepted (anchor $R_ANCHOR)."
      if [ "$RESUMED" = "True" ] && [ -n "$R_ANCHOR" ] && [ "$R_ANCHOR" != "$ANCHOR" ]; then
        say "${RED}NOTE: the server is resuming an older stalled run (anchor $R_ANCHOR),${RESET}"
        say "${RED}ignoring the new anchor. Letting it finish — re-run this script after.${RESET}"
      fi
      ;;
    409)
      say "A run is already in progress on the server — attaching to it."
      ;;
    401|403)
      case "$BODY" in
        *pdwchs_demo_forbidden*) die "Rejected: token mismatch — X-PDWCHS-Demo-Token does not match the live PDWCHS_DEMO_TOKEN." ;;
        *pdwchs_demo_blocked*)   die "Rejected: PDWCHS_DEMO_ALLOW is not enabled in the live wp-config.php." ;;
        *) die "HTTP $CODE without plugin JSON — likely the host WAF blocking /wp-json/pdwchs-demo/v1/*. See live-migration.md §6." ;;
      esac
      ;;
    404)
      die "404 — pdwchs-demo-tools is not deployed/active on $BASE yet (or permalinks/REST are off). See live-migration.md §2."
      ;;
    *)
      die "Unexpected HTTP $CODE from POST /refresh: $BODY"
      ;;
  esac
}

# ── run ─────────────────────────────────────────────────────────────────────
say "== Live refresh on $BASE (anchor $ANCHOR) =="
START=$SECONDS
post_refresh

WAITED=0; LAST_UPDATED=""; LAST_CHANGE=0; REPOSTED=0; LAST_SHOWN=""
while :; do
  api "$API/status" || die "curl failed on GET /status"
  [ "$CODE" = "200" ] || die "HTTP $CODE from GET /status: $BODY"
  ST="$(jget run.status "$BODY")"
  PROG="$(jget run.progress "$BODY")"
  UPD="$(jget run.updated "$BODY")"

  if [ "$ST $PROG" != "$LAST_SHOWN" ]; then
    say "  status=${ST:-?}  progress=${PROG:-?}"
    LAST_SHOWN="$ST $PROG"
  fi

  case "$ST" in
    done)   break ;;
    failed) die "Run FAILED on the server: $(jget run.error "$BODY")" ;;
    "")     die "No run state on the server — the refresh may not have started. /status body: $BODY" ;;
  esac

  # run.updated is a display string ("… UTC") — track *change*, not timestamps.
  if [ "$UPD" != "$LAST_UPDATED" ]; then
    LAST_UPDATED="$UPD"; LAST_CHANGE=$WAITED
  elif [ $((WAITED - LAST_CHANGE)) -ge "$STALL_AFTER" ]; then
    if [ "$REPOSTED" -eq 0 ]; then
      say "No progress for $((WAITED - LAST_CHANGE))s — re-POSTing /refresh (the server resumes stalled runs)."
      REPOSTED=1
      post_refresh
      LAST_CHANGE=$WAITED
    else
      die "Still stalled after one resume. Inspect manually: curl -H 'X-PDWCHS-Demo-Token: <token>' $API/status"
    fi
  fi

  [ "$WAITED" -ge "$MAX_WAIT" ] && die "Timed out after ${MAX_WAIT}s — check GET /status manually."
  sleep "$POLL_EVERY"
  WAITED=$((WAITED + POLL_EVERY))
done

L_ANCHOR="$(jget ledger.anchor "$BODY")"
say ""
say "Seeded: users=$(jget ledger.users "$BODY") orders=$(jget ledger.orders "$BODY") sessions=$(jget ledger.sessions "$BODY") carts=$(jget ledger.carts "$BODY") (anchor ${L_ANCHOR:-?})"

if [ "$L_ANCHOR" != "$ANCHOR" ]; then
  printf '%s\n' "${RED}${BOLD}Finished run has anchor ${L_ANCHOR:-?}, not $ANCHOR (an older stalled run was resumed).${RESET}"
  printf '%s\n' "Re-run this script now to refresh for $ANCHOR."
  exit 1
fi

say ""
say "== Verify (live) =="
api "$API/verify?report=1" || die "curl failed on GET /verify"
[ "$CODE" = "200" ] || die "HTTP $CODE from GET /verify: $BODY"

printf '%s' "$BODY" | python3 -c '
import json, sys
d = json.load(sys.stdin)
for chk in d.get("checks", []):
    if chk.get("status") != "pass":
        print("[%s] %s — %s" % (str(chk.get("status", "?")).upper(), chk.get("label", ""), chk.get("actual", "")))
rep = d.get("report")
if isinstance(rep, dict):
    print("")
    print("Report sheet:")
    for k, v in rep.items():
        print("  %-28s %s" % (k, v))
c = d.get("counts", {})
print("")
print("pass=%s warn=%s fail=%s" % (c.get("pass"), c.get("warn"), c.get("fail")))
'

FAILS="$(jget counts.fail "$BODY")"
ELAPSED=$((SECONDS - START)); MIN=$((ELAPSED / 60)); SEC=$((ELAPSED % 60))
echo ""
if [ "$FAILS" = "0" ]; then
  printf '%s\n' "${GREEN}${BOLD}LIVE OK — 0 FAIL (anchor $ANCHOR, ${MIN}m${SEC}s).${RESET}"
  printf '%s\n' "Same anchor + seed as local => the live demo shows the exact same numbers."
  exit 0
else
  printf '%s\n' "${RED}${BOLD}LIVE VERIFY FAILED (fail=${FAILS:-?}) — see checks above.${RESET}"
  printf '%s\n' "Troubleshooting: project_asset/docs/live-migration.md §6 and runbook §7."
  exit 1
fi
