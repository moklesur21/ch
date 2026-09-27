# PureDevs — Lifecycle Emails v1.2

**Version:** v1.4 · **Date:** 24 Sep 2026 · **Owner:** Yeasir
**Supersedes:** `PureDevs_Lifecycle_Emails_v1.md` and v1.1 (7 Aug) for the Welcome flow and the Trial sequence. The Playbook drip keeps its own file.
**Covers:** Welcome flow (2 emails) · Trial conversion sequence (5 emails, 30-day no-card trial)
**Checked against:** decision A2 (never lead with RFM, churn, health scores, behavioural tags, ready-made segments) · the 3.1.2 Feature Reference §D · strategy §5.3 (one matching demo per email, linked to its question page).

**Rules kept from v1.1:** plain-text style, no images or templates · sender "Yeasir" <yeasir@puredevs.com>, replies monitored · one job and one CTA per email · **no dollar amounts** · observe-and-report language only · `Hi {{ contact.FIRSTNAME }}` with fallback "there".

**One marker used below**
- **[LINK — add when live]** A demo line goes in only when that question page exists. Never link to a page that is not live.

**Free-version limit stated in these emails:** the 90-day analytics window only. Per Yeasir (21 Sep), the free customer list has **no 100-customer cap**, so no email mentions one.

**UTMs on every link:** `?utm_source=email&utm_medium=email&utm_campaign=proof2026_trial_seq&utm_content=trial1` (then `trial2` … `trial5`). Welcome flow: `utm_campaign=proof2026_welcome_seq`, `utm_content=welcome1` / `welcome2`. This replaces the older `posttrial_seq` value.

---

## Flow map

| Email | Sent | Job | Screen it opens | Demo link, when live |
|---|---|---|---|---|
| Welcome 1 | On joining *Free — Opted In* | First win on data that already exists | Customers & Users | — |
| Welcome 2 | Day 3 | One useful free exercise | Search Analytics | `zero_result_searches` (V13) |
| Trial 1 | Day 0 | The core answer, from existing orders | Dashboard → Revenue Analytics | `returning_revenue` (V1) |
| Trial 2 | Day 2 | A second lens, and a modelled action | Customers & Users → Save as Segment | — (V15 is in the next window) |
| Trial 3 | Day 15 | What happened before the order | Statistics → Conversion Funnel | `drop_off_funnel` (V4) |
| Trial 4 | Day 23 | Honest expiry preview + pricing | /pricing | `free_to_pro` (V16) sits on that page |
| Trial 5 | Day 29 | The decision | /pricing | — |

Brevo waits between trial emails: **2 / 13 / 8 / 6 days.**

**Why this order:** Trial 1 and 2 read order history, so they work the minute Pro is active, even on a brand-new install. Searches, views and the funnel need days of storefront activity, so they wait for Day 15.

---

## WELCOME 1 — on sync

**Subject:** the people behind your orders (already in there)
**Alt subject:** worth two minutes: your customer list

> Hi {{FIRSTNAME}},
>
> Yeasir here. I build Customer History. Thanks for installing it.
>
> One suggestion before anything else: open **Customer History → Customers & Users** in your wp-admin. It is already filled from your existing order history: each customer with their order count and total spent. Your WooCommerce dashboard shows the totals. This shows the people behind them.
>
> One honest note about the free version: analytics cover the most recent 90 days. Everything is still being recorded in full from today. The limit is on the view, not on the collection. And your customer and order data stays in your own database.
>
> One page will be nearly empty today: **Search Analytics**. It records what shoppers type into your store search and which products they view, starting from the moment you activated. Give it a couple of days. I'll nudge you when it's worth a look.
>
> If anything looks off, just reply. I read every reply.
>
> Yeasir
> Founder, PureDevs

*Changed from v1:* menu names match 3.1.2 · "Nothing is sent to any outside service" replaced, because this very email reached them through Freemius and Brevo · average order value removed (not confirmed as a free column).

---

## WELCOME 2 — Day 3

**Subject:** a 5-minute look at what your shoppers search for

> Hi {{FIRSTNAME}},
>
> Three days in, your **Search Analytics** page should have started to fill. Here is the exercise I'd run on any store. It takes about five minutes.
>
> First, open the **Zero-Result Searches** panel. Those are words shoppers typed into your store search that returned nothing. If a term keeps coming up, it is either something to stock or something you already sell under a different name.
>
> Then compare **Most Viewed Products** with your top sellers. A product with many views and few sales usually has a fixable problem: price, photos, or shipping clarity.
>
> WooCommerce's own reports start at the order, so the gap between what people look for and what they buy is normally invisible.
>
> **[LINK — add when live]** A short video of this exact exercise: `https://puredevs.com/questions/woocommerce-zero-result-searches/`
>
> If the panel stays empty and your store uses a search plugin, reply and tell me which one.
>
> You'll also notice some locked cards. That is the Pro layer: full history, views against purchases for every product, and the conversion funnel. No pressure on it. The free version is meant to be useful on its own.
>
> Reply and tell me the strangest search term you find. I'm building a collection.
>
> Yeasir

*Changed from v1:* uses the Zero-Result Searches panel, new in 3.1.2 · the Pro layer is no longer described as "RFM segments, health scores, churn" · "WooCommerce records none of this" softened.

---

## TRIAL 1 — Day 0

**Subject:** first thing: open this one screen
**Alt subject:** where your revenue really comes from

> Hi {{FIRSTNAME}},
>
> Your Pro trial is live. If you haven't installed Pro yet, the download and your licence key are in the email from Freemius.
>
> Once it's active, skip the settings. There is nothing to configure. Open **Customer History → Dashboard** and find **Revenue Analytics**.
>
> Look at "Revenue by Customer Type". Three cards: New Customers, Returning Customers and Guest Orders, each with revenue and order count for the dates you pick. It reads the orders you already have, so it works from minute one.
>
> WooCommerce lets you filter the orders report by new or returning customers. This puts all three side by side for the same dates, and that is where the decision is: does your next hour go to existing buyers, or to first-time buyers?
>
> **[LINK — add when live]** A short video of this screen: `https://puredevs.com/questions/woocommerce-new-vs-returning-customers/`
>
> Open it, then hit reply and tell me one thing: which of the three numbers surprised you? The answers genuinely shape what I build next.
>
> Yeasir

*Replaces* "open your at-risk list" (churn scores), which broke A2.

---

## TRIAL 2 — Day 2

**Subject:** everyone who bought one product, in one list

> Hi {{FIRSTNAME}},
>
> Second screen for your trial: **Customer History → Customers & Users**.
>
> In **Advance Search**, choose one of your best sellers under **Product Purchased** and press Search. That is every registered customer who bought it. Add a Total Spent filter if you only want the bigger buyers, then press **Save as Segment**. A segment stores the filter, not a fixed list, so it stays current as new orders arrive.
>
> From **Segments** you can export that list as a CSV, in a column layout that imports cleanly into Klaviyo or Mailchimp.
>
> Think of the last restock, new version or product problem you had. This is the list you needed that day. The plugin never contacts your customers itself. It shows you who, and you decide what to send.
>
> One honest limit: guest checkouts are not in this list, only customers with an account.
>
> Yeasir

*Replaces* "your champions, by name" (RFM segments and the ready-made High Value At Risk segment), which broke A2. "Advance Search" is spelled as it appears in the plugin.

---

## TRIAL 3 — Day 15

**Subject:** what happened before the order

> Hi {{FIRSTNAME}},
>
> Two weeks in, your trial has recorded enough storefront activity to show you something an order report cannot: what happened before the order.
>
> Open **Customer History → Statistics → Conversion Funnel**. Four bars: Visitors, Add to Cart, Checkout Started, Purchased, with the rate between each step. The biggest drop is the step to fix first.
>
> Two more tabs in the same place are worth a minute each. **Zero-Result Searches** shows the words shoppers typed that returned nothing. **Viewed vs Purchased Ratio** puts views and purchases side by side for every product.
>
> While you're there, **Abandoned Carts** lists the carts that never checked out and the value in them. The plugin observes and lists them. What you do about them is up to you.
>
> **[LINK — add when live]** A short video of the funnel: `https://puredevs.com/questions/woocommerce-where-shoppers-drop-off/`
>
> If one of those numbers made you change something in your store, reply and tell me what.
>
> Yeasir

*Changed:* the revenue split moved to Trial 1, so this email now carries the storefront-behaviour screens, which need two weeks of data to be worth opening.

---

## TRIAL 4 — Day 23

**Subject:** what happens when your trial ends (the honest version)

> Hi {{FIRSTNAME}},
>
> Your trial has about a week left, so here is exactly what happens when it ends. No surprises.
>
> Nothing breaks and nothing is deleted. You go back to the free version, which keeps working: your customer list, the dashboard widgets, search and view analytics for the most recent 90 days, the revenue snapshot and the weekly digest. Searches, views and orders keep being recorded in full. Cart tracking pauses, because that part is Pro.
>
> What locks is the Pro view: history beyond 90 days, revenue by customer type, views against purchases, the conversion funnel, abandoned carts, unlimited segments, and the scoring screens. If you upgrade later, even months later, it all switches back on over the same data. No migration, nothing to rebuild.
>
> If the trial showed you something you'd act on, pricing is here: **[PRICING LINK]**. There is also a founding-members rate for the first 50 Pro licences. Details are on that page.
>
> And if it's not for you, that is fine. Keep the free version. It is meant to be useful on its own.
>
> Yeasir

*Changed:* the list of what locks now leads with history, revenue and storefront behaviour; the scoring features are named last · "everything captured keeps being captured" corrected, because cart tracking is Pro-only · the founding-members sentence stays only while the counter on the site is real.

---

## TRIAL 5 — Day 29 · lapse variant only

A no-card trial cannot auto-convert, so the auto-convert variant is deleted. Confirm "no card" in Freemius (checklist C3) before this ships.

**Subject:** your trial ends tomorrow

> Hi {{FIRSTNAME}},
>
> Quick one: your Pro trial ends tomorrow. After that the Pro screens lock and you're back on the free version, with all your data intact.
>
> If the revenue split, the search list or the funnel showed you something you'd otherwise have missed, you can keep the full view here: **[PRICING LINK]**.
>
> Either way, thanks for giving it a real look.
>
> Yeasir

*Optional line, only while the counter is live:* "The founding-members rate still has spots open. It's on the pricing page."

---

## Pre-flight, before these go into Brevo

1. **Open a 3.1.2 install and check every bold menu path above.** Correct any label that differs.
2. On a free 3.1.2 install with more than 100 customers, confirm the list shows them all and no "up to 100 customers" banner appears. The Feature Reference says otherwise, so one look settles it.
3. Trial terms confirmed 21 Sep: 30 days, no card. The waits stand.
4. Insert the real `/pricing/` link with its UTMs.
5. Leave every **[LINK]** line out until its page is live, then add it. No email may point to a missing page.
6. FIRSTNAME fallback set to "there" in Brevo.
7. Turn off Freemius's own trial-expiring reminder on the day the Trial sequence goes live, or users get two.
8. Exclusions that stay from v1.1: TRY60 extended trials are handled by hand and must not enter this automation · anyone getting a personal email from yeasir@ that week is held out · an uninstall exits every flow.
9. Signature: name, "Founder, PureDevs", puredevs.com. Nothing else.

## Guardrails honoured (keep them if you edit)

- The plugin **observes and reports**. Never "sends", "recovers", "wins back", "syncs" or "integrates".
- No email leads with RFM, churn scores, health scores, behavioural tags or the ready-made segments. They appear once, last, as "the scoring screens".
- Never "WooCommerce has no analytics". Say what WooCommerce shows, then what this adds.
- Free limits are read limits, stated plainly. Search and cart data are never described as retroactive.

## Changelog
- **v1.4 — 24 Sep 2026.** Question-page links move to the approved `/questions/<slug>/` pattern (V1 slug as approved). Trial terms now confirmed, so pre-flight item 3 is closed. No copy changed.
- **v1.3 — 21 Sep 2026.** Decision A7: no 100-customer cap on the free version. The two bracketed cap phrases (Welcome 1, Trial 4) and the A7 marker are removed. No other copy changed.
- **v1.2 — 21 Sep 2026.** Trial 1 and Trial 2 replaced (they led with churn scores and RFM). Trial 3 rebuilt around the funnel, searches and views, timed for Day 15. Trial 4's lock list reordered and its cart statement corrected. Trial 5 reduced to the lapse variant. Welcome 1 and 2 updated to 3.1.2 menu names and the Zero-Result Searches panel; an inaccurate data line removed. Demo-link lines added per strategy §5.3, each held until its page is live. UTM values aligned with the Section B convention.
- **v1.1 — 7 Aug 2026.** Cap language removed, plain-text format recorded, UTM and TRY60 items added, Trial 3 and Trial 5 notes sharpened.
- **v1.0.** First drafts.
