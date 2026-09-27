# PureDevs — Section C Guide: Freemius and Checkout

**Version:** v1.2 · **Date:** 21 Sep 2026 · **Owner:** Yeasir
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.1.md` (items C1–C4) and the Section B guide (this file supplies the code behind B4)
**Your total time in C:** about 1 hour (C3 plus two approvals). C1, C2 and C4 are DEV.
**API facts below were checked against the Freemius docs on 19 Sep 2026.** Anything marked *(confirm)* the DEV checks in the dashboard.

**Order:** C3 first (it unblocks everything) → C1 + C2 together → C4.

---

## C3. Freemius dashboard check — Y · 40 min · due Mon 21 Sep (moved up from 23 Sep)

1. **Trial settings.** Plans → Pro → Free trial: **enabled · 30 days · payment method not required**. The trial button in C2 does nothing unless this is on.
2. **Prices match the site.** Single $79 · 5 sites $179 · unlimited $299 · all annual.
3. **FOUNDING50 coupon.** Discount applies to renewals too ("locked for life") · redemption limit 50 · redemptions used = 50 minus the number shown on the site (17 today).
4. **Refund policy** set to 30 days, so the money-back badge in the checkout is true.
5. **Freemius's own emails.**
   - Keep: trial-started email (it carries the download link and license key), receipts, cart-abandonment recovery.
   - Trial-expiring reminder: leave **ON** now. Turn it **OFF** the day the Brevo trial sequence (G3) goes live, or users get two reminders.
6. **Team access.** Add the DEV as a team member so they can reach Settings, Webhooks and the checkout snippet.
7. **Hand the DEV** (password manager, never chat or email): Product ID, Plan ID, public key, **secret key**, and a **sandbox token + ctx** generated in the dashboard.

**Done when:** all seven are ticked and the DEV has the keys.

---

## C1 + C2. Overlay checkout and trial button — DEV 1 day, Y 15 min · due Fri 25 Sep

**What changes for a visitor:** every buy or trial button opens the Freemius checkout **on top of the page** instead of sending them to checkout.freemius.com. That is what makes `trial_start` and `purchase` trackable.

### How buttons are marked (Gutenberg-friendly)
Add one class to the Button block (Advanced → Additional CSS class). Keep the current `checkout.freemius.com` link as the button URL, so it still works if JavaScript fails.

| Class | Opens |
|---|---|
| `pd-trial` | 30-day free trial, 1 site |
| `pd-buy-1` | Buy, 1 site |
| `pd-buy-5` | Buy, 5 sites |
| `pd-buy-unlimited` | Buy, unlimited sites |
| `pd-founding` | Buy, 1 site, coupon FOUNDING50 applied |

### Where they go
- **/pricing:** `pd-trial` as the primary button on the Pro card · `pd-founding` on the Founding 50 offer · the multi-site card gets **two** buttons, `pd-buy-5` and `pd-buy-unlimited` (today its single button opens unlimited only).
- **/founding-50:** `pd-founding`.
- **Landing pages (D1, D2):** `pd-trial` only.

### Script 1 — load (theme `functions.php`)
```php
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_script('fs-checkout', 'https://checkout.freemius.com/js/v1/', [], null, true); // (confirm URL against the dashboard snippet)
  wp_enqueue_script('pd-checkout', get_stylesheet_directory_uri() . '/js/pd-checkout.js', ['fs-checkout'], '1.0.0', true);
  wp_localize_script('pd-checkout', 'PD_FS', [
    'product_id' => '7511',
    'plan_id'    => '12279',
    'public_key' => PD_FS_PUBLIC_KEY,          // only if the dashboard snippet includes it
    'image'      => 'https://puredevs.com/path/to/icon-256.png',
    // 'sandbox' => ['token' => '...', 'ctx' => '...'],   // staging only
  ]);
});
```

### Script 2 — `js/pd-checkout.js`
```js
(function () {
  var cfg = window.PD_FS;
  if (!cfg || !window.FS || !FS.Checkout) return;   // fail safe: buttons keep their normal link

  var MODES = {
    'pd-trial':         { trial: true,  licenses: 1 },
    'pd-buy-1':         { trial: false, licenses: 1 },
    'pd-buy-5':         { trial: false, licenses: 5 },
    'pd-buy-unlimited': { trial: false, licenses: 'unlimited' },
    'pd-founding':      { trial: false, licenses: 1, coupon: 'FOUNDING50' }
  };

  var checkout = new FS.Checkout({
    product_id: cfg.product_id, plan_id: cfg.plan_id,
    public_key: cfg.public_key, image: cfg.image
  });

  window.dataLayer = window.dataLayer || [];
  var sent = {};
  function pushOnce(key, obj) { if (sent[key]) return; sent[key] = 1; dataLayer.push(obj); }
  function isTrial(r) { return !!(r && (r.trial || (r.purchase && r.purchase.trial_ends != null))); }

  document.addEventListener('click', function (e) {
    var cls = Object.keys(MODES).find(function (c) { return e.target.closest('.' + c); });
    if (!cls) return;
    e.preventDefault();

    var m = MODES[cls];
    var cta = cls + '|' + location.pathname;
    dataLayer.push({ event: 'checkout_open', plan_id: String(cfg.plan_id), cta_location: cta });

    var opts = {
      licenses: m.licenses,
      trial: m.trial,
      show_refund_badge: true,
      purchaseCompleted: function (r) {
        if (isTrial(r)) {
          pushOnce('trial', { event: 'trial_start', plan_id: String(cfg.plan_id), trial_days: 30, cta_location: cta });
          return;
        }
        var p = r && r.purchase;
        if (!p) return;
        var value = p.initial_amount != null ? p.initial_amount : p.gross;
        pushOnce('buy-' + p.id, {
          event: 'purchase', transaction_id: String(p.id), value: Number(value),
          currency: String(p.currency || 'usd').toUpperCase(),
          plan_id: String(p.plan_id), coupon: m.coupon || '', cta_location: cta
        });
      },
      success: function (r) { if (isTrial(r)) location.href = '/trial-started/'; }
    };
    if (m.coupon) opts.coupon = m.coupon;
    if (cfg.sandbox) opts.sandbox = cfg.sandbox;
    checkout.open(opts);
  });
})();
```

**Why it is built this way**
- Events fire from `purchaseCompleted`, not `success`. `success` only runs after the buyer clicks "Got it", and many people close the tab first.
- A no-card trial arrives as a `trial` object in the response; a card trial arrives as `purchase.trial_ends`. The code handles both.
- `pushOnce` stops a double click from counting two conversions.

**DEV must also**
- Exclude both scripts from any "delay / defer JavaScript" optimisation, or the first click fails.
- Make sure the consent tool does not block `checkout.freemius.com` (treat as necessary).
- Test on staging with the sandbox keys, then remove them.

### Trial-started page (`/trial-started/`) — does not block launch
A no-card web trial ends with an email, not an installed plugin. A trial that is never installed never converts, so this page closes the gap:
1. "Check your inbox" — the email from Freemius has your download and license key.
2. "Upload it" — Plugins → Add New → Upload → activate → paste the key.
3. "Open this screen first" — link to the same demo video the visitor saw on the landing page (the D1 hero), so their first minute repeats what the ad promised.
Set the page to `noindex`. Y approves the copy.

**Done when:** on staging, a sandbox trial and a sandbox purchase each open as an overlay and push the right event into GTM Preview.

---

## C4. Freemius → Brevo sync — audit and extend the webhook you already have — DEV 0.5 day · due Mon 28 Sep

**Correction to v1.0–v1.1.** Those versions told the DEV to build a new sync. One already exists: the webhook at `webhook.puredevs.com` adds contacts to your four Brevo lists, and the August Brevo design depends on one rule: **the webhook only ever ADDS to a list. Brevo does all the removing, branching and exiting.** Keep that rule. Do not build a second sync.

### Audit (30 minutes)
1. **Signature.** Does it verify every request before acting? If not, add this first:
```php
$raw  = $request->get_body();                         // raw body, untouched
$sig  = $request->get_header('x-signature');
$calc = hash_hmac('sha256', $raw, PD_FS_SECRET_KEY);  // kept outside the web root or in wp-config.php
if (!$sig || !hash_equals($calc, $sig)) { http_response_code(401); exit; }
```
2. **Which Freemius events it listens to today.** Write them next to the table below and mark the gaps.
3. It answers `200` quickly, ignores a repeated event `id`, and logs each event with the Brevo response.
4. Keys live in server config, never in JavaScript or the database.

### Events → list to ADD the contact to
| Freemius event | Meaning | Add to Brevo list | Status |
|---|---|---|---|
| (whatever it uses today for an opted-in free user) | Free user who opted in | *Free — Opted In* | Exists: confirm |
| `install.trial.started` | Trial started **inside the plugin** | *Trial — Active* (ID 5) | Exists: confirm |
| **`user.trial.started`** | Trial started **on the website** (the new C2 button) | *Trial — Active* (ID 5) | **Almost certainly missing.** Website trials did not exist before C2. Without this, ad-driven trials never get the trial emails. |
| `payment.created` | Money actually received | *Customers — Pro* | Exists: confirm it is tied to a payment, not only to a subscription being created |
| `install.uninstalled` | Plugin removed | **New list** *Uninstalled* | Add. Every automation exits on membership of this list, which keeps the add-only rule |
| `license.expired` · `payment.refund` | No longer paying | New list *Churned* | After launch |

### Attributes to set while adding (cheap, and they make reports possible)
`TRIAL_START` (date) · `TRIAL_SOURCE` (`web` or `plugin`) · `FS_MARKETING` (`yes` / `no`, from the Freemius user's marketing flag).
**Do not write to `OPTED_IN`.** The August design sets that attribute inside Brevo when the trial starts. The Section G guide shows how `FS_MARKETING` feeds it.

**Register** any new event under Freemius → Webhooks → Listeners for the existing URL. *(Confirm payload field names from Freemius → Webhooks → Events.)*

**Done when:** a sandbox **website** trial puts a contact in *Trial — Active* with `TRIAL_SOURCE = web` within a minute, and a sandbox purchase adds the same contact to *Customers — Pro*.

---

## Hand-offs to other sections

- **G2 (settled by C3):** a no-card trial cannot auto-convert, so it lapses to free. Trial 5 = the **lapse** variant. Delete the auto-convert variant.
- **G3 (from Video Topic Filter v4):** place the `drop_off_funnel` video around trial day 3 and `free_to_pro` before trial end, each as a GIF linking to its page.
- **G3 (needs work):** `PureDevs_Lifecycle_Emails_v1.md` Trial 1 and Trial 2 lead with the at-risk list, churn scores and RFM segments. That conflicts with decision A2. They need a rewrite around the demo questions before G3 goes live.
- **G (decision):** whether the trial sequence sends to contacts with `OPTED_IN = false`.
- **D3:** pricing page gets the button classes above and the second multi-site button.
- **J2:** add "sandbox trial → contact lands in Brevo Trial list" to the QA run.
- **Known gap, accepted:** trials and upgrades started inside wp-admin never touch the website, so GA4 and Ads will undercount. The webhook still catches them for Brevo. Freemius stays the revenue truth; reconcile weekly.

## C sign-off (feeds QA item J2)

- [ ] Trial enabled, 30 days, no card · prices and FOUNDING50 verified
- [ ] Every buy and trial button opens the overlay; links still work with JavaScript off
- [ ] Sandbox trial pushes `trial_start` once · sandbox purchase pushes `purchase` once with value and currency
- [ ] Multi-site card has separate 5-site and unlimited buttons
- [ ] Webhook rejects a request with a bad signature (401)
- [ ] Sandbox **website** trial lands in Brevo *Trial — Active*; sandbox purchase adds it to *Customers — Pro*
- [ ] Sandbox keys removed from production

## Changelog
- **v1.2 — 21 Sep 2026.** C4 rewritten. A Freemius → Brevo webhook already exists and follows an add-only rule, so C4 is now an audit plus three additions (`user.trial.started` for website trials, an *Uninstalled* list, three attributes) instead of a new build. `OPTED_IN` is left to Brevo; the webhook writes `FS_MARKETING`.
- **v1.1 — 20 Sep 2026.** Trial-started page now points to the landing page's hero video by role, not by number, after Video Topic Filter v4 renumbered the list. Added v4's video placements to the G3 hand-off. Code and event map unchanged.
- **v1.0 — 19 Sep 2026.** First version. Overlay checkout with class-based buttons, trial mode, verified callback and webhook names, self-hosted Freemius → Brevo sync, and a trial-started page to lift activation.
