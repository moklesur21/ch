# Demo-site lockdown — read-only public viewer

Turns the live demo (ch.puredevs.com) into a safe public playground: anyone can
log in as `demo` / `demo1234`, explore every Customer History screen with real
demo data, but change nothing. Everyone except administrators and the demo user
is blocked from signing in.

## Why it works this way

The product plugin gates **every** admin screen *and* every write action behind
one hardcoded capability, `manage_woocommerce`
(`admin/class-pd-customer-history-admin.php:216`) — there is no read-vs-write
split and it isn't filterable. A user who can *view* the analytics can therefore
also recalculate metrics, delete sessions, save segments, send emails, and create
coupons. So "read-only" can't come from capabilities. Instead the demo role keeps
`manage_woocommerce` (so it can see everything) and a dedicated enforcement layer
blocks the write paths, keyed off the role.

All of it lives in our own `pdwchs-demo-tools` plugin:
`includes/class-pdwchs-demo-lockdown.php` (class `PDWCHS_Demo_Lockdown`), wired in
from `pdwchs-demo-tools.php`. The whole module is **inert unless
`PDWCHS_DEMO_LOCKDOWN` is defined true** in `wp-config.php`.

## What it does when enabled

- **Provisions** a `pdwchs_demo_viewer` role (caps: `read`, `manage_woocommerce`,
  `view_woocommerce_reports`) and a `demo` user with that role. Runs once, guarded
  by the `pdwchs_demo_lockdown_ver` option; bump `PROVISION_VERSION` to re-run.
  Password comes from `PDWCHS_DEMO_USER_PASS` if defined, else `demo1234`.
- **Login allowlist** (`authenticate`, priority 99): only administrators and the
  demo user may log in; everyone else gets a branded refusal. Existing sessions
  are untouched. Also: password reset off for non-admins, XML-RPC disabled,
  application passwords disabled.
- **Write blocking** for the demo role, by context:
  - REST (`rest_pre_dispatch`): any non-GET/HEAD/OPTIONS → 403. Closes the WC core
    REST writes (`PUT /wc/v3/settings/*`, `system_status/tools/*`) that
    `manage_woocommerce` would otherwise authorize.
  - admin-ajax: only an explicit read-only action allowlist (`ALLOWED_AJAX`) runs;
    everything else is refused. **New plugin read endpoints must be added to that
    constant or they'll break for the demo user.**
  - Other admin/front-end writes (`admin_init`, priority 0 so it beats the plugin's
    cap-less CSV import): write-method form posts blocked; the GET-based session
    trash/restore (`act=` on `pd-woo-sessions|searches|trash`) blocked. The two
    read-only CSV exports are explicitly allowed.
  - `user_has_cap` strips dangerous caps (`edit_users`, `install_*`,
    `manage_options`, …) as defense-in-depth.
- **Trims the admin surface**: hides Posts, Comments, Appearance, Plugins, Users,
  Tools, Settings, and WC Settings/Status. Keeps Customer History, WooCommerce
  Orders + Analytics, and the Products list. Adds a persistent "read-only demo"
  banner.

The demo-data refresh flow is unaffected: it authenticates by token and runs as
user 0 (never the demo role), so the role-scoped blockers leave it alone.

## Live-site deploy checklist (ch.puredevs.com)

In the live `wp-config.php`:

```php
define( 'PDWCHS_DEMO_LOCKDOWN', true );
define( 'PDWCHS_DEMO_TOKEN', '<random-hex>' );   // demo-data refresh trigger
// Freemius sandbox secret key (see freemius-sandbox.md) — accepted decision
define( 'WP_FS__puredevs-customer-history-for-woocommerce_SECRET_KEY', 'sk_...' );
define( 'DISALLOW_FILE_EDIT', true );
```

And make sure:

- `WP_DEBUG` / `WP_DEBUG_LOG` are **off** (public site; WC Status → Logs can print
  `debug.log`).
- `WP_ENVIRONMENT_TYPE` is unset or `'production'` — never `'local'` (that would
  re-enable passwordless auto-login).
- `WP_FS__DEV_MODE` and `WP_FS__SKIP_EMAIL_ACTIVATION` are **never** defined
  (dev-only: the Freemius Debug menu and the opt-in activation shortcut).
- Self-registration is off: `users_can_register = 0` (self-registration is
  anonymous, so the role blocker can't stop it — this setting must).
- `wp-content/mu-plugins/auto_login_local.php` is **absent** from the live box.
  It's already env-gated (local-only) and refuses when lockdown is defined, but
  keep it out of the deploy as belt-and-suspenders — it logs in as any user with
  zero authentication.
- The `pdwchs-demo-tools` plugin is active (or its mu-loader installed) so the
  lockdown module actually loads.
- After deploy: confirm `demo` / `demo1234` logs in and lands on the Customer
  History dashboard, can browse Orders/Analytics/Products, and cannot write
  (try a recalc button and a settings save — both should be refused).

## Local testing

The dev box normally runs with lockdown **off** and passwordless auto-login on.
To test the lockdown locally, temporarily add `define('PDWCHS_DEMO_LOCKDOWN',
true);` to `wp-config.php` (this disables auto-login while set), activate
`pdwchs-demo-tools`, exercise the demo login, then remove the constant and delete
the `demo` user + `pdwchs_demo_viewer` role when done.
