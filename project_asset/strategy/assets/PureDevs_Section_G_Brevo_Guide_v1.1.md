# PureDevs — Section G Guide: Brevo

**Version:** v1.1 · **Date:** 21 Sep 2026 · **Owner:** Yeasir
**Companion to:** `PureDevs_Ad_Launch_Prerequisites_Checklist_v1.1.md` (items G1–G5)
**Builds on, does not replace:** `PureDevs_Brevo_Automation_Setup_v1.md` (August). That file stays the click-by-click guide for the four automations. This guide says what to build when, what changed since August, and how to test it.
**Email copy:** `PureDevs_Lifecycle_Emails_v1.3.md`.
**Time:** Y about 1.5 h (decisions, approving seven emails, one inbox check) · VA about 1 day · DEV about 1 h.

---

## G0. Where things stand on 21 Sep

| Exists | Missing |
|---|---|
| Brevo account · four lists: *Leads — Lead Magnet*, *Free — Opted In*, *Trial — Active* (ID 5), *Customers — Pro* | **Every automation.** Nothing is sent to anyone today |
| The webhook at `webhook.puredevs.com`, which adds contacts to those lists | Website trials reaching *Trial — Active* (Section C guide v1.2, C4) |
| SPF and DKIM on puredevs.com (confirmed in August) | DMARC check · two attributes · two helper lists |
| The automation design and the email copy | The trial emails that respect decision A2 (now written: v1.3) |

**The rule everything rests on:** the webhook only ever **adds** a contact to a list. Brevo does all the removing, branching and exiting.

---

## G2. Decisions — settled here, overrule any of them

| # | Question | Decision | Why |
|---|---|---|---|
| a | Trial 5 variant | **Lapse variant only** | A no-card trial cannot auto-convert. Confirm "no card" in C3 first |
| b | Sender | **"Yeasir" <yeasir@puredevs.com>** everywhere, replies monitored. The video narrator uses the same name | Already unified in the August Playbook drip; closes strategy open item 6 |
| c | Who receives the trial sequence | **Every trial user**, including those who did not tick marketing consent | It is onboarding for something they asked for, and every email carries an unsubscribe link. That is the usual reading of the UK / EU soft opt-in. Not legal advice |
| d | Who joins the newsletter after a lapsed trial | Only `OPTED_IN = yes`. Set it at trial entry if the contact is in *Free — Opted In* **or** `FS_MARKETING = yes` | Keeps the August consent logic and extends it to website trials |
| e | May emails link to question pages, not only /pricing? | **Yes** | Those pages carry the trial button. Closes strategy open item 5 |
| f | Does A2 bind the Playbook drip? | **Leave the drip as written for now** | It teaches churn and RFM as ideas, only form leads receive it, and no ad page carries the form. Review after launch |
| g | Freemius's own trial-expiring reminder | **Off** on the day the trial sequence goes live | Otherwise users get two reminders |

---

## G4. Do this first: test the Playbook form — VA · 30 min · **today, Mon 21 Sep**

The live site promises "one email, then the playbook". With no automations built, that email is probably not being sent.

1. Submit the form on the homepage with a fresh address (a Gmail alias works).
2. Within 5 minutes check: the contact is in *Leads — Lead Magnet* · an email arrived · the PDF link opens · it is not in spam.
3. Repeat from a phone.
4. **If no email arrives:** build the delivery step alone, now. Automations → new → trigger "added to *Leads — Lead Magnet*" → send the PDF email (Playbook drip Email 1) → end. 20 minutes. The rest of the drip follows in G5.
5. Send the PDF by hand to anyone already on that list who never received it, with one line of apology.

**Done when:** a form submission gets the PDF within 5 minutes.

---

## G1. Deliverability — DEV 30 min + VA 30 min · due Wed 23 Sep

1. Brevo → account menu (top right) → **Senders, Domains & Dedicated IPs → Domains**. puredevs.com must show **Authenticated** for the Brevo code and DKIM.
2. **DMARC.** If the DMARC row is not green, add one TXT record at `_dmarc.puredevs.com`:
   `v=DMARC1; p=none; rua=mailto:dmarc-reports@puredevs.com`
   Start at `p=none`. Move to `quarantine` after two to four clean weeks.
3. A domain may have **one** SPF record and **one** DMARC record. The SPF record must keep Google Workspace, because your personal emails leave from the same address. Add Brevo to it only if the Domains screen asks.
4. **Senders** tab → add or confirm "Yeasir" <yeasir@puredevs.com> → verify.
5. Build the emails as plain templates: simple rich-text editor, one column, no images, no design blocks. Keep Brevo's unsubscribe footer and postal address. They are required.
6. Send Trial 1 as a test to the address shown on mail-tester.com. **Pass mark 9/10.** Then to one Gmail and one Outlook inbox: it must land in the main inbox.
- The free Brevo plan adds a small "Sent with Brevo" mark. Fine for launch; removing it needs a paid plan.

---

## G3. Trial sequence live — VA 3 h, Y approves the copy · due Mon 28 Sep

**Build order for launch:** two attributes → two helper lists → Automation B (Pro cleanup) → **Automation A (Trial sequence)**. Follow the August guide for the clicks, with these changes:

| In the August guide | Now |
|---|---|
| Emails = Lifecycle Emails v1, Trial 1–5 | **Lifecycle Emails v1.3.** Trial 1 and 2 are new; 3, 4 and 5 are revised |
| `OPTED_IN = yes` if the contact is in *Free — Opted In* at entry | …**or** if `FS_MARKETING = yes` (written by the webhook for website trials) |
| One exit guard, before Trial 4 | **The same guard before every email:** in *Customers — Pro* → exit · in *Uninstalled* → exit · in *Suppress — Personal* → exit |
| Trial 5 in two variants | Lapse variant only |
| — | **New list *Uninstalled*:** the webhook adds to it (C4) |
| — | **New list *Suppress — Personal*:** you add people by hand: TRY60 extended trials, and anyone you are emailing personally that week |
| Waits 2 / 13 / 8 / 6 days | Unchanged |
| Entry: added to *Trial — Active*, enter once | Unchanged. It depends on C4 adding **website** trials to that list |

**Every [LINK] line in v1.3 stays out until its page is live.** At launch that means no demo links at all, or only Trial 1's if the V1 page is up.

### Test before switching it on (45 min)
1. Duplicate Automation A as "TEST — Trial sequence". Trigger = added to a list "TEST — Trial". Set every wait to **1 minute**.
2. Contact 1, with no first name: all five emails arrive in order · greeting reads "Hi there" · every link opens with its UTMs · unsubscribe works.
3. Contact 2: after Trial 2 arrives, add it to *Customers — Pro* → nothing further arrives.
4. Contact 3: add it to *Suppress — Personal* first → nothing arrives.
5. **End to end:** run a sandbox **website** trial (Section C). The contact appears in *Trial — Active* and the real Trial 1 arrives within minutes. This is the QA check that proves C2, C4 and G3 together.
6. Delete the test automation and list. Activate Automation A. Turn off Freemius's trial-expiring reminder (decision g).

---

## G5. The rest — VA · does not block launch

| Order | What | Due | Note |
|---|---|---|---|
| 1 | **Automation D — Welcome flow** (Welcome 1 and 2 from v1.3) | Wed 30 Sep | Visitors who choose the free install instead of the trial land here. Ads cannot see those installs, so this flow is the only thing working on them |
| 2 | **Automation C — Playbook drip**, Emails 2–6 (Email 1 is live from G4) | Fri 2 Oct | Copy = Playbook drip v1.1, unchanged (decision f) |
| 3 | Newsletter **segment**: (*Free — Opted In* or *Customers — Pro*) and not *Trial — Active* and not *Leads — Lead Magnet* | Fri 2 Oct | Add "and not *Uninstalled*" |
| 4 | Remove the "Sent with Brevo" mark, custom tracking domain | After launch | Optional |

---

## G sign-off (feeds QA item J2)

- [ ] Playbook form delivers the PDF within 5 minutes
- [ ] Domain shows Authenticated · DMARC record present · mail-tester 9/10 or better · test lands in the main inbox on Gmail and Outlook
- [ ] Attributes `NURTURED`, `OPTED_IN` and lists *Uninstalled*, *Suppress — Personal* exist
- [ ] Test automation passed all four contact tests
- [ ] Sandbox website trial → *Trial — Active* → Trial 1 arrives
- [ ] No email contains a dollar amount, a missing-page link, or a lead on RFM, churn or health scores
- [ ] Freemius trial-expiring reminder is off

## Hand-offs to other sections

- **C4:** the Section C guide is now v1.2. C4 is an audit of the existing webhook plus three additions, not a new build. I had specified a new build without checking the August Brevo work. That was my error.
- **D:** question-page URLs in the emails come from the Section D guide v1.3. If a URL changes there, change it in the emails.
- **E:** the narrator introduces himself as Yeasir, matching the sender.
- **H:** review requests never share an email with an offer and go out weeks apart from one. They are not part of these automations.
- **A7 settled:** no 100-customer cap. The emails are now v1.3 with the two cap phrases removed.

## Changelog
- **v1.1 — 21 Sep 2026.** Points to Lifecycle Emails v1.3 after decision A7 (no 100-customer cap). Nothing else changed.
- **v1.0 — 21 Sep 2026.** First version. Records what exists, settles seven decisions, puts the Playbook form test first, lists the changes to the August automation design, and adds a one-minute-wait test procedure.
