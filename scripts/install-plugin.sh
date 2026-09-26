#!/bin/bash
# install-plugin.sh — install the product plugin into this demo site from a
# git ref of the dev repo, as the two build.sh zips customers actually get.
#
#   1. checks out <ref> into a throw-away git worktree of the dev repo
#      (the dev repo's own branch and uncommitted work are never touched)
#   2. runs the dev repo's build.sh there → free zip + pro zip
#   3. replaces the two plugin folders here with the unzipped builds:
#        free → wp-content/plugins/puredevs-customer-history-for-woocommerce/
#        pro  → wp-content/plugins/puredevs-customer-history-for-woocommerce-pro/
#      Folder names never change, so WordPress keeps each one's active state.
#   4. stamps project_asset/plugin-build.txt with version + ref + commit
#
# Never copy, symlink or check out the dev source tree into wp-content/plugins:
# the free folder would carry pro/ (PDWCHS_IS_PREMIUM = file_exists(pro/pro.php)),
# plus tests/, docs/ and dev vendor/.
#
# Usage:  scripts/install-plugin.sh [git-ref]      (default: release)
#   e.g.  scripts/install-plugin.sh                 # current release
#         scripts/install-plugin.sh origin/release  # after a git fetch in the dev repo
# Then:   load wp-admin once (DB healers), run scripts/demo-refresh.sh, commit.

set -euo pipefail

PLUGIN_REPO="${PDWCHS_PLUGIN_REPO:-/Applications/MAMP/htdocs/puredevs-customer-history/wp-content/plugins/puredevs-customer-history-for-woocommerce}"
SLUG="puredevs-customer-history-for-woocommerce"
REF="${1:-release}"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO="$(cd "$SCRIPT_DIR/.." && pwd)"
PLUGINS="$REPO/wp-content/plugins"
STAMP="$REPO/project_asset/plugin-build.txt"

die() { printf 'install-plugin: %s\n' "$*" >&2; exit 1; }

[ -d "$PLUGIN_REPO/.git" ] || die "dev repo not found at $PLUGIN_REPO (set PDWCHS_PLUGIN_REPO)"
SHA="$(git -C "$PLUGIN_REPO" rev-parse --short "$REF^{commit}" 2>/dev/null)" \
  || die "unknown git ref '$REF' in $PLUGIN_REPO"

TMP="$(mktemp -d)"
cleanup() {
  git -C "$PLUGIN_REPO" worktree remove --force "$TMP/src" >/dev/null 2>&1 || true
  rm -rf "$TMP"
  git -C "$PLUGIN_REPO" worktree prune >/dev/null 2>&1 || true
}
trap cleanup EXIT

echo "-> worktree $REF ($SHA)"
git -C "$PLUGIN_REPO" worktree add --quiet --detach "$TMP/src" "$REF"

echo "-> build.sh"
( cd "$TMP/src" && ./build.sh )

VERSION="$(grep -m1 'Version:' "$TMP/src/$SLUG.php" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"

# rm + mv (not rsync --delete): APFS is case-insensitive, and a case-only
# rename such as README.txt → readme.txt trips rsync's delete pass.
install_variant() {  # <zip> <target folder name>
  local zip="$1" target="$2"
  mkdir -p "$TMP/unzip-$target"
  unzip -q "$TMP/src/dist/$zip" -d "$TMP/unzip-$target"
  [ -f "$TMP/unzip-$target/$SLUG/$SLUG.php" ] || die "$zip has no $SLUG/$SLUG.php"
  rm -rf "${PLUGINS:?}/$target"
  mv "$TMP/unzip-$target/$SLUG" "$PLUGINS/$target"
  echo "   $target ← dist/$zip"
}

echo "-> install"
install_variant "$SLUG.zip"     "$SLUG"
install_variant "$SLUG-pro.zip" "$SLUG-pro"

[ ! -e "$PLUGINS/$SLUG/pro" ] || die "pro/ found in the free folder — build is wrong"
[ -f "$PLUGINS/$SLUG-pro/pro/pro.php" ] || die "pro/pro.php missing from the pro folder"

cat > "$STAMP" <<EOF
# Written by scripts/install-plugin.sh — which dev-repo commit the plugin folders were built from.
version: $VERSION
ref: $REF
commit: $(git -C "$PLUGIN_REPO" rev-parse "$REF^{commit}")
installed: $(date -u +%Y-%m-%dT%H:%M:%SZ)
EOF

echo ""
echo "Installed $SLUG $VERSION ($REF @ $SHA) — free + pro."
echo "Next: load wp-admin once, run scripts/demo-refresh.sh, then commit."
