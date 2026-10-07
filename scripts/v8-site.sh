#!/bin/bash
# v8-site.sh — the throw-away store for V8 ("Where does my store data go when
# I install this?"). V8's beat 2 is a one-take *real* install of the free
# plugin from WordPress.org, which `ch` cannot film: the plugin is already in
# its plugins folder, Freemius already knows the site, and months of seeded
# searches would bury the one the viewer watches being made.
#
# The site lives OUTSIDE this repo at /Applications/MAMP/htdocs/ch-v8
# (DB ch_v8, http://localhost/ch-v8). It is the same Northline store as ch —
# catalog, customers, orders from the demo-tools seeder in --woo-only mode —
# but the PureDevs plugin has never been installed on it.
#
# Usage:
#   scripts/v8-site.sh create    build the site, seed it, snapshot the pre-install state
#   scripts/v8-site.sh reset     back to the pre-install snapshot (every retake starts here)
#   scripts/v8-site.sh check     record / don't-record verdict for the install take
#   scripts/v8-site.sh update    Northline theme from ch, then WordPress, plugins and themes to
#                                the latest release, then a new snapshot. The video always
#                                shows the latest WordPress.
#   scripts/v8-site.sh snapshot  re-take the snapshot after another pre-install fix;
#                                both refuse once the plugin has been installed
#   scripts/v8-site.sh wp ...    any wp-cli command against ch-v8
#   scripts/v8-site.sh destroy --yes   drop the DB and delete the folder (after V8 ships)
#
# Never run against ch, never against the live site. The take itself is
# recorded by project_asset/marketing/tooling/record-scenes.mjs --video v8.

set -euo pipefail

CH="$(cd "$(dirname "$0")/.." && pwd)"
V8="/Applications/MAMP/htdocs/ch-v8"
URL="http://localhost/ch-v8"
DB="ch_v8"
SLUG="puredevs-customer-history-for-woocommerce"
SNAPSHOT="$V8/backups/pre-install.sql"
SHOT_LOGIN="pdwchs-shot"
SHOT_EMAIL="shot@example.invalid"

SOCK="/Applications/MAMP/tmp/mysql/mysql.sock"
WP="/opt/homebrew/bin/wp"
MYSQL="/Applications/MAMP/Library/bin/mysql"

die() { printf 'v8-site: %s\n' "$*" >&2; exit 1; }
step() { printf '\n== %s\n' "$*"; }

[ -S "$SOCK" ] || die "MAMP MySQL socket missing ($SOCK) — start MAMP first."

# wp-cli against ch-v8. db export/import shell out to mysqldump/mysql, which
# ignore the PHP socket — those get an explicit --socket (same as scripts/wpc).
v8wp() {
  local extra=""
  case "${1-} ${2-}" in
    "db export"|"db import") extra="--socket=$SOCK" ;;
  esac
  php -d mysqli.default_socket="$SOCK" "$WP" --path="$V8" "$@" ${extra:+"$extra"}
}
chwp() { "$CH/scripts/wpc" "$@" --skip-plugins --skip-themes; }

db_sql() {
  local user pass
  user="$(chwp config get DB_USER)"
  pass="$(chwp config get DB_PASSWORD)"
  MYSQL_PWD="$pass" "$MYSQL" --socket="$SOCK" -u"$user" -e "$1"
}

ch_wp_version() { php -r 'include $argv[1]."/wp-includes/version.php"; echo $wp_version;' "$CH"; }
ch_free_version() { sed -n 's/^ \* Version: *//p' "$CH/wp-content/plugins/$SLUG/$SLUG.php" | sed -n 1p; }
ch_wc_version() { sed -n 's/^ \* Version: *//p' "$CH/wp-content/plugins/woocommerce/woocommerce.php" | sed -n 1p; }

# ── create ───────────────────────────────────────────────────────────────

cmd_create() {
  [ -e "$V8" ] && die "$V8 already exists — run 'destroy --yes' first, or 'reset' to go back to the snapshot."
  if db_sql "SHOW DATABASES LIKE '$DB'" | grep -q "$DB"; then
    die "database $DB already exists — run 'destroy --yes' first."
  fi

  step "Files: WordPress $(ch_wp_version) core and WooCommerce $(ch_wc_version) copied from ch ('update' brings them to the latest)"
  mkdir -p "$V8/backups" "$V8/wp-content/plugins" "$V8/wp-content/themes" "$V8/wp-content/mu-plugins" "$V8/wp-content/uploads"
  rsync -a "$CH/wp-admin" "$CH/wp-includes" "$V8/"
  for f in index.php license.txt readme.html wp-activate.php wp-blog-header.php wp-comments-post.php \
           wp-config-sample.php wp-cron.php wp-links-opml.php wp-load.php wp-login.php wp-mail.php \
           wp-settings.php wp-signup.php wp-trackback.php xmlrpc.php; do
    cp "$CH/$f" "$V8/$f"
  done
  cp "$CH/wp-content/index.php" "$V8/wp-content/index.php"
  cp "$CH/wp-content/plugins/index.php" "$V8/wp-content/plugins/index.php"
  cp "$CH/wp-content/themes/index.php" "$V8/wp-content/themes/index.php"
  # What a fresh WordPress ships with (Akismet, Hello Dolly, the default
  # themes), plus WooCommerce, the Northline child theme and, for seeding
  # only, demo-tools. Never the PureDevs plugin.
  rsync -a "$CH/wp-content/plugins/akismet" "$CH/wp-content/plugins/woocommerce" \
           "$CH/wp-content/plugins/pdwchs-demo-tools" "$V8/wp-content/plugins/"
  cp "$CH/wp-content/plugins/hello.php" "$V8/wp-content/plugins/hello.php"
  rsync -a "$CH/wp-content/themes/twentytwentythree" "$CH/wp-content/themes/twentytwentyfour" \
           "$CH/wp-content/themes/twentytwentyfive" "$CH/wp-content/themes/northline" "$V8/wp-content/themes/"
  cp "$CH/wp-content/mu-plugins/auto_login_local.php" "$V8/wp-content/mu-plugins/"
  sed 's#/ch/#/ch-v8/#g' "$CH/.htaccess" > "$V8/.htaccess"

  step "Database $DB and wp-config.php (no Freemius key: the site behaves like a stranger's)"
  db_sql "CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci"
  v8wp config create --dbname="$DB" --dbuser="$(chwp config get DB_USER)" --dbpass="$(chwp config get DB_PASSWORD)" \
    --dbhost="$(chwp config get DB_HOST)" --dbprefix=wp_ --skip-check --extra-php <<PHP
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_HOME', '$URL' );
define( 'WP_SITEURL', '$URL' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
// No background updates mid-shoot: versions change only through 'v8-site.sh update'.
define( 'AUTOMATIC_UPDATER_DISABLED', true );
// demo-tools seeding only — removed again by 'create' once seeding is done.
define( 'PDWCHS_DEMO_ALLOW', true );
PHP

  step "WordPress install (admin = the throw-away shot user, no password kept)"
  v8wp core install --url="$URL" --title="Northline Supply Co." --admin_user="$SHOT_LOGIN" \
    --admin_email="$SHOT_EMAIL" --admin_password="$(openssl rand -hex 24)" --skip-email
  v8wp user update "$SHOT_LOGIN" --first_name=Sarah --last_name=M. --display_name="Sarah M." --nickname="Sarah M."
  v8wp option update permalink_structure '/%postname%/'
  v8wp option update gmt_offset 0
  v8wp option update timezone_string ''
  v8wp option update admin_email_lifespan 4102444800

  step "WooCommerce"
  v8wp plugin activate woocommerce
  v8wp wc tool run install_pages --user="$SHOT_LOGIN" || true
  v8wp option update woocommerce_default_country 'US:CA'
  v8wp option update woocommerce_coming_soon no
  v8wp option update woocommerce_allow_tracking no
  v8wp option update woocommerce_show_marketplace_suggestions no
  v8wp option update woocommerce_onboarding_profile \
    '{"business_choice":"im_just_starting_my_business","industry":["other"],"is_agree_marketing":false,"store_email":"'"$SHOT_EMAIL"'","is_store_country_set":true,"completed":true}' \
    --format=json

  step "Northline store: catalog, customers, orders (demo-tools --woo-only), then storefront branding"
  v8wp plugin activate pdwchs-demo-tools
  v8wp pdwchs-demo seed --woo-only
  v8wp pdwchs-demo setup-storefront
  v8wp plugin deactivate pdwchs-demo-tools
  v8wp plugin delete pdwchs-demo-tools
  for opt in $(v8wp option list --search='pdwchs_demo*' --field=option_name); do
    v8wp option delete "$opt"
  done
  v8wp config delete PDWCHS_DEMO_ALLOW

  step "Camera hygiene for the shot user (same choices as V1's [DEV SETUP])"
  v8wp user meta update "$SHOT_LOGIN" show_welcome_panel 0
  v8wp user meta update "$SHOT_LOGIN" meta-box-order_dashboard \
    '{"normal":"woocommerce_dashboard_status","side":"dashboard_right_now","column3":"woocommerce_dashboard_recent_reviews","column4":"dashboard_quick_press"}' --format=json
  v8wp user meta update "$SHOT_LOGIN" metaboxhidden_dashboard \
    '["dashboard_site_health","dashboard_primary","dashboard_activity","wc_admin_dashboard_setup"]' --format=json
  v8wp option update woocommerce_admin_notices '[]' --format=json
  v8wp transient delete --all

  cmd_update
  cmd_check || true
  printf '\nSite: %s   Log in: %s/?local_login=1&user_email=%s\n' "$URL" "$URL" "$SHOT_EMAIL"
}

snapshot() {
  step "Snapshot of the pre-install state → $SNAPSHOT"
  v8wp db export "$SNAPSHOT" --add-drop-table
}

require_pre_install() {
  [ -f "$V8/wp-config.php" ] || die "no site at $V8 — run 'create' first."
  [ -d "$V8/wp-content/plugins/$SLUG" ] && die "the plugin is installed — this is not the pre-install state. Run 'reset' first."
  local pattern traces=0
  for pattern in 'fs_*' 'pdwchs*' 'pd-woo-*'; do
    traces=$(( traces + $(v8wp option list --search="$pattern" --format=count --skip-plugins --skip-themes) ))
  done
  [ "$traces" -eq 0 ] || die "$traces Freemius/plugin options in the DB — not the pre-install state. Run 'reset' first."
}

cmd_snapshot() {
  require_pre_install
  snapshot
}

# The video always runs the latest WordPress (owner rule, 7 Oct 2026), whatever
# the plugin readme's "Tested up to" says. Downloads from wordpress.org.
cmd_update() {
  require_pre_install
  step "Northline theme synced from ch (the git-tracked source)"
  rsync -a --delete "$CH/wp-content/themes/northline/" "$V8/wp-content/themes/northline/"
  step "Latest WordPress, plugins and themes (from wordpress.org)"
  v8wp core update --skip-plugins --skip-themes
  v8wp core update-db
  v8wp plugin update --all
  v8wp theme update --all
  v8wp transient delete --all
  snapshot
}

# ── reset ────────────────────────────────────────────────────────────────

cmd_reset() {
  [ -f "$SNAPSHOT" ] || die "no snapshot at $SNAPSHOT — run 'create' first."

  step "Remove the PureDevs plugin (files only — its uninstall hook must not run) and its digest CSVs"
  rm -rf "$V8/wp-content/plugins/$SLUG" "$V8/wp-content/plugins/$SLUG-pro"
  rm -rf "$V8/wp-content/upgrade" "$V8/wp-content/upgrade-temp-backup"
  find "$V8/wp-content/uploads" -type f \( -name 'customers_order_history_from_*.csv' -o -name 'top_customers_history_from_*.csv' \) -delete

  step "Drop every table (the plugin's included), then load the snapshot"
  v8wp eval --skip-plugins --skip-themes '
    global $wpdb;
    foreach ( $wpdb->get_col( "SHOW TABLES" ) as $t ) { $wpdb->query( "DROP TABLE `$t`" ); }
    echo "dropped tables\n";'
  v8wp db import "$SNAPSHOT"
  v8wp cache flush --skip-plugins --skip-themes
  cmd_check
}

# ── check ────────────────────────────────────────────────────────────────

cmd_check() {
  [ -f "$V8/wp-config.php" ] || die "no site at $V8 — run 'create' first."
  step "Record / don't-record verdict for the V8 install take"
  CH_FREE="$(ch_free_version)" SLUG="$SLUG" SNAPSHOT="$SNAPSHOT" \
  SHOT_EMAIL="$SHOT_EMAIL" PLUGIN_DIR="$V8/wp-content/plugins" HTACCESS="$V8/.htaccess" \
    v8wp eval-file "$CH/scripts/v8-site-check.php"
}

# ── destroy ──────────────────────────────────────────────────────────────

cmd_destroy() {
  [ "${1-}" = "--yes" ] || die "destroy drops $DB and deletes $V8 — pass --yes."
  db_sql "DROP DATABASE IF EXISTS \`$DB\`"
  rm -rf "$V8"
  echo "ch-v8 destroyed."
}

case "${1-}" in
  create)  cmd_create ;;
  reset)   cmd_reset ;;
  check)   cmd_check ;;
  update)  cmd_update ;;
  snapshot) cmd_snapshot ;;
  wp)      shift; v8wp "$@" ;;
  destroy) shift; cmd_destroy "${1-}" ;;
  *) sed -n "2,27p" "$0"; exit 2 ;;
esac
