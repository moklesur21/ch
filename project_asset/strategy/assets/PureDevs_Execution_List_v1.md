# PureDevs — Execution List

**Version:** v1.0 · **Date:** Sat 26 Sep 2026 · **Owner:** Yeasir
**Status today:** nothing in B–J has been executed. Done so far: every decision (A1–A8), V1 and V8 in filming, V1's brief, script, recording brief and placement kit.
**New dates (these override checklist v1.3):** QA **Sun 4 Oct** · fixes Mon 5 · **launch Tue 6 Oct, 18:00** · hard deadline Thu 8 Oct. Reason: the DEV has about five working days in B, C and D0 before any page can be built, and 1 Oct is four days away.
**One rule:** do the rows in order inside your lane. Each row names the file and section to open. Everything else in that file is context you can skip.

## The guides (already written; each row below points into one)

| File | What it holds |
|---|---|
| `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.3.md` | The full item list. Reference only; dates here override it |
| `PureDevs_Section_B_Tracking_Consent_Guide_v1.2.md` | Ads account, GTM, GA4, consent banner, events, Search Console, Clarity |
| `PureDevs_Section_C_Freemius_Checkout_Guide_v1.2.md` | Freemius checks, overlay checkout code, trial button, webhook audit |
| `PureDevs_Section_D_Landing_Pages_Guide_v1.5.md` | Block kit, page copy for D1, D2, pricing, question pages, schema |
| `PureDevs_Section_F_Google_Ads_Build_Guide_v1.md` | Campaign settings, keywords, negatives, ad copy, assets, audiences |
| `PureDevs_Section_G_Brevo_Guide_v1.1.md` + `PureDevs_Lifecycle_Emails_v1.4.md` | Deliverability, automations, test procedure; the seven emails |
| `PureDevs_Section_H_Capterra_G2_Guide_v1.md` | Listing copy, form answers, screenshots, review plan |
| `PureDevs_Section_I_WordPress_Org_Guide_v1.1.md` | Readme edits, screenshots, reviews |
| `PureDevs_Section_J_QA_Launch_Guide_v1.md` | Go/no-go, baseline, QA script, launch runbook, weekly rhythm |
| `PureDevs_Video_Chat_Handoff_Note_v1.1.md` | What the video chat owes the pages |

If any section needs more click-level detail than it has, name the section and I expand it.

---

## Lane Y — you, 2 h a day

| Day | # | Task | Open | Time |
|---|---|---|---|---|
| Sat 26 | Y1 | Create the new Google Ads account under yeasir@puredevs.com; billing; verification; auto-tagging on | B guide → B1 | 40 min |
| Sat 26 | Y2 | Freemius dashboard: trial ✔ · prices · FOUNDING50 · refund policy · Freemius emails · add DEV to the team · hand keys and a sandbox token to DEV via password manager | C guide → C3 | 40 min |
| Sun 27 | Y3 | Confirm the remaining §2 amendments in Video Topic Filter v4.2 (one message to the video chat) | Checklist → A6 | 10 min |
| Sun 27 | Y4 | Create GTM, GA4 and Search Console accounts; add DEV and VA | B guide → B2 steps 1–3, B5 step 1 | 20 min |
| Sun 27 | Y5 | Send the DEV and VA their hand-off messages (bottom of this file) | — | 10 min |
| Sun 27 | Y6 | Approve the Capterra / G2 copy; send company details, logo, LinkedIn page | H guide → Identity, Copy | 30 min |
| Mon 28 | Y7 | Review the V1 edit; approve for publishing | Video chat placement kit | 45 min |
| Tue 29 | Y8 | Link GA4 ↔ Ads; import `trial_start` (Primary) and `purchase` (Secondary) | B guide → B4 steps 7–10 | 15 min |
| Tue 29 | Y9 | Approve Trial 1 (must be final) and the Welcome emails | Lifecycle Emails v1.4 | 30 min |
| Wed 30 | Y10 | Approve D1, D2, pricing copy in the Gutenberg drafts | D guide → D1–D3 | 45 min |
| Thu 1 Oct | Y11 | Review the ads: 40-minute checklist | F guide → Y's review | 40 min |
| Fri 2 Oct | Y12 | Readiness snapshot; apply the go / fallback / slip rules; send me the numbers | J guide → J0 | 20 min |
| Sun 4 Oct | Y13 | QA run, your parts: checks 4 (phone), 5 (real trial), 6 (Ads) | J guide → J2 | 1 h |
| Mon 5 Oct | Y14 | Fixes re-tested; Freemius trial-expiring reminder off | J guide → J3, T-1 | 30 min |
| **Tue 6 Oct** | **Y15** | **Launch at 18:00: Brand first, then Non-brand; policy check at +1 h** | J guide → J3 steps | 30 min |
| From Wed 7 | Y16 | Daily 15 min; Monday scorecard; reply to trial-email replies | J guide → J4 | 15 min/day |

Anything not on this lane is not yours. V8 recording continues in the video chat.

---

## Lane DEV — full time, in this order (dependencies run downward)

| Day | # | Task | Open | Done when |
|---|---|---|---|---|
| Sun 27 | D-1 | Install GTM + GA4 tag; internal-traffic cookie flag; data filter | B guide → B2 steps 4–7 | `page_view` in GA4, filter Active |
| Sun 27 | D-2 | Privacy Policy + Terms pages; footer links; policy line under the Playbook forms | B guide → B3 steps 9–12 (H needs this by Mon) | Links live in both footers |
| Sun 27 | D-3 | Brevo domain: Authenticated, DMARC record, sender verified | G guide → G1 steps 1–4 | Domains screen all green |
| Mon 28 | D-4 | Overlay checkout: enqueue script, `pd-checkout.js`, button classes | C guide → C1 + C2, Scripts 1–2 | Sandbox trial and purchase open as overlays on staging |
| Tue 29 | D-5 | Finish C1 + C2; `/trial-started/` page (noindex) | C guide → Trial-started page | Events push once each in GTM Preview |
| Tue 29 | D-6 | GTM: data-layer variables, event trigger, GA4 event tag; key events | B guide → B4 steps 1–6 | Console-pushed `trial_start` in DebugView |
| Wed 30 | D-7 | CookieYes web app + GTM template; region defaults; consent checks on tags | B guide → B3 steps 1–8 | UK Denied by default, US Granted; video and checkout work untouched |
| Wed 30 – Thu 1 | D-8 | **Build kit**: Landing template + 6 patterns incl. click-to-load video | D guide → Build kit | VA can assemble a page from the patterns alone |
| Thu 1 | D-9 | Webhook audit: signature check; add `user.trial.started`; *Uninstalled* list; three attributes | C guide → C4 | Sandbox website trial lands in *Trial — Active* with `TRIAL_SOURCE = web` |
| Fri 2 | D-10 | Search-plugin test (FiboSearch, Advanced Woo Search, Woo Product Search block), 1 h | D guide → week plan | Result written into D1 FAQ 6 and sent to the video chat |
| Fri 2 – Sat 3 | D-11 | Readme edits in trunk and tags/3.1.2; validator | I guide → I1 texts A–F | Listing shows five tags, new opener, External services |
| Sat 3 | D-12 | Question-page template + schema | D guide → D4 template, Schema | V1 page live with `VideoObject` |
| Sun 4 | D-13 | QA run, DEV parts: checks 1, 2, 4, 8, and support on 3 | J guide → J2 | Fix list written |
| Mon 5 | D-14 | Fixes; publish GTM; back up; freeze | J guide → J2 check 8 | Container published |

Not before launch: I3 (V13 embed), the plugin-release items (I4, `utm_source=plugin`), and the V1 chat's order-count dev ticket.

---

## Lane VA — full time, in this order

| Day | # | Task | Open | Done when |
|---|---|---|---|---|
| Sat 26 | V-1 | Test the Playbook form; if no PDF arrives, build the one-step delivery automation today | G guide → G4 | PDF arrives within 5 min |
| Sat 26 – Sun 27 | V-2 | Baseline table; compute the allowable cost per trial from the real trial→paid rate | J guide → J1 | Table in the scorecard sheet |
| Sun 27 | V-3 | Screenshot set: pull the eight screens from the 3.1.2 brief PDFs (§F screenshot index, part 2 p.78); caption; retake only if a widget is in frame | I guide → I2; H guide → screenshots | 8 captioned images |
| Sun 27 | V-4 | Ads account settings: auto-apply off, dynamic assets off, three cost rules | F guide → F1, "Once, at account level" | Rules visible |
| Sun 27 | V-5 | Two campaigns, ad groups, keywords, negatives, all paused | F guide → F1 | Match types read Exact / Phrase |
| Mon 28 | V-6 | Publish V1 once approved: title, description, chapters, pinned link; hand back ID, poster, 3 screenshots, transcript | Hand-off note → §4 | ID and poster in the shared folder |
| Mon 28 | V-7 | Capterra submission (needs D-2 live) | H guide → H1 + H2, Form answers | Confirmation email |
| Tue 29 | V-8 | G2 submission | H guide | Confirmation email |
| Tue 29 | V-9 | Brevo: attributes, helper lists, Automation B, Automation A with the v1.4 emails; every [LINK] line left out | G guide → G3 | Automation A built, off |
| Wed 30 | V-10 | Automation test with 1-minute waits, contacts 1–4 | G guide → G3 test | Four tests pass |
| Wed 30 – Thu 1 | V-11 | Assemble D1, D2 and the pricing edits in Gutenberg from the patterns; V1 poster or still image | D guide → D1, D2, D3 | Y approves |
| Thu 1 | V-12 | D2 vendor fact check; transcripts cleaned; link and 404 check | D guide → D2 note | Revision note dated |
| Fri 2 | V-13 | Ads and assets created, paused, with final URLs; ★ lines per their conditions | F guide → F2 | Every ad shows Eligible by Sun |
| Fri 2 | V-14 | GA4 audiences as Observation; Search Console sitemap; UTM sheet; Clarity | F guide → F3; B guide → B5 | Audiences listed |
| Sat 3 | V-15 | Publish V8 when ready; D2 hero swap | E3 | — |
| Sun 4 | V-16 | QA run, VA parts: checks 3 and 7 | J guide → J2 | Fix list written |
| From Mon 5 | V-17 | Automation D (Welcome), Playbook drip 2–6, newsletter segment; question pages for V1 and V8; Monday scorecard | G guide → G5; D guide → D4; J guide → J4 | — |

---

## How to work each lane

**You.** Open the section named in the row, do its numbered steps, and stop at that section's "Done when" line. Where a guide says *(confirm)* or *(confirm menu name)*, the intent is fixed and the menu label may differ; use the search box inside Google Ads, GA4 or Freemius. Send me the row numbers done at the end of each day, plus anything that failed. I answer fixes the same day.

**DEV.** Every DEV row has code or exact settings in its section. Hand the guide file to Claude Code as the task file, the way you already work: "Implement Section C guide, C1 + C2, in the `puredevs-site` theme; test on staging with the sandbox keys; report against the section's Done-when line." Do C on staging with sandbox keys, everything else on production behind GTM Preview. Never install gtag.js or Site Kit next to GTM.

**VA.** Pages, ads, emails and listings are paste-ready in their guides. Copy exactly; the character limits were checked. Screenshots come from the brief PDFs first. Any wording change goes back to Yeasir before it goes live.

**Dependencies to respect:** Y1 before Y8 · Y2 before D-4 · D-1 before D-6 · D-2 before V-7 · D-8 before V-11 · V-11 URLs live before V-13 · D-9 and D-3 before the end-to-end trial test · everything before Sun 4 Oct.

---

## Hand-off messages (paste as they are)

**To the DEV**
> You have the DEV lane in `PureDevs_Execution_List_v1.md`, 14 rows from Sun 27 Sep to Mon 5 Oct, in order. Each row names the guide file and section that holds the code or settings. Give the guide to Claude Code as the task file and test against the section's "Done when" line. C1–C2 on staging with the Freemius sandbox keys I'm sending separately; the rest on production behind GTM Preview. Report row numbers done at the end of each day.

**To the VA**
> You have the VA lane in `PureDevs_Execution_List_v1.md`, 17 rows from Sat 26 Sep to Mon 5 Oct, in order. Every row names the guide file and section with paste-ready copy or settings. Copy exactly; if a change seems needed, ask me before it goes live. Screenshots come from the 3.1.2 brief PDFs first (§F index, part 2 p.78). Report row numbers done at the end of each day.

## Changelog
- **v1.0 — 26 Sep 2026.** First version. Built after confirming in the recent chats that only the videos have moved. Dates re-planned to a 6 Oct launch. Three lanes, each row mapped to a guide section.
