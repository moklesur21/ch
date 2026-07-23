# Freemius sandbox checkout — setup & usage

Sandbox mode lets us complete real checkout flows (upgrade, trial, license
activation) with test credit cards — no real charges. Based on:
https://freemius.com/help/documentation/checkout/integration/testing/

## How it works here

Both the free and pro plugins init Freemius with the same product
(`id 7511`, slug `puredevs-customer-history-for-woocommerce`). The bundled
WordPress SDK has native sandbox support:

- `vendor/freemius/wordpress-sdk/includes/class-freemius.php` (~line 5152)
  reads the product secret key from a constant named
  `WP_FS__<slug>_SECRET_KEY` if it isn't passed to `fs_dynamic_init()`.
- When a secret key is present, `is_payments_sandbox()` returns true and
  `class-fs-checkout-manager.php` (~line 164) appends a signed `sandbox`
  token + `s_ctx_ts` timestamp to every checkout URL automatically.

So **no plugin code changes are needed** — only a constant in
`wp-config.php`, which is gitignored so the secret never reaches the repo.
On the **live demo** the same constant is deliberately present (accepted
decision — see demo-site-lockdown.md's deploy checklist): it keeps every
checkout on ch.puredevs.com in sandbox mode, so visitors can complete the
upgrade flow with test cards and can never be really charged. The dev-only
constants `WP_FS__DEV_MODE` / `WP_FS__SKIP_EMAIL_ACTIVATION` stay off the
live box.

## One-time activation (requires the secret key)

1. Log in to the Freemius Developer Dashboard.
2. Open **Customer History for WooCommerce → Settings → Keys** and copy the
   **Secret Key** (starts with `sk_`).
3. In `wp-config.php`, find the Freemius block (just above
   "That's all, stop editing!"), paste the key into the `define()` and
   uncomment it:

   ```php
   define( 'WP_FS__puredevs-customer-history-for-woocommerce_SECRET_KEY', 'sk_XXXX' );
   ```

## Testing a purchase

1. Make sure the **free** plugin is active (that's the realistic upgrade
   flow; the pro plugin is currently installed but inactive).
2. In WP Admin, go to the Customer History menu → **Upgrade / Pricing**
   (`admin.php?page=pd-woo-dashboard-pricing`).
3. Open the checkout. The URL/iframe request now carries `sandbox=` and
   `s_ctx_ts=` params, and the checkout shows it is running in sandbox mode.
4. Pay with a test card (any future expiry, any CVC):

   | Brand      | Number              |
   |------------|---------------------|
   | Visa       | 4242 4242 4242 4242 |
   | MasterCard | 5555 5555 5555 4444 |
   | Amex       | 3782 8224 6310 005  |
   | Discover   | 6011 1111 1111 1117 |

   For PayPal, use the sandbox accounts at sandbox.paypal.com (see the
   Freemius doc page above).
5. Complete the flow: the license is issued as a **sandbox license** — it
   activates normally in the plugin, and can be inspected/deleted from the
   Developer Dashboard without touching real customer data.

## Rules

- The secret key lives **only** in local `wp-config.php`. Never commit it,
  never add it to the live demo site — a deployed secret key would put the
  live checkout into sandbox mode and leak the key to anyone with file
  access.
- Sandbox licenses/users created while testing should be cleaned from the
  Developer Dashboard before they pollute analytics.

## Quick verification checklist

- [ ] Constant defined and uncommented in `wp-config.php`
- [ ] Checkout URL contains `sandbox=` and `s_ctx_ts=`
- [ ] Test card 4242… completes the purchase
- [ ] License activates and pro features unlock
