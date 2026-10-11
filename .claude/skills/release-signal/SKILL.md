---
name: release-signal
description: After a plugin release, read the release signal(s) the dev repo wrote, install the tagged build here, refresh the demo, and update the marketing brief and copy that the release changed. Yeasir runs it when Asad reports a release.
argument-hint: "[tag, e.g. ch-v3.2.0 — default: every signal newer than the installed build]"
disable-model-invocation: true
---

# /release-signal $ARGUMENTS

A new plugin version has shipped. Bring this repo (ch: demo store + marketing) up to date with it,
working with **Yeasir**. Read `CLAUDE.md` in this repo first; every rule in it applies.

The dev repo is `${PDWCHS_PLUGIN_REPO:-/Applications/MAMP/htdocs/puredevs-customer-history}`
(call it DEV). The signal format is DEV's `wp-content/assets/common/RELEASE-SIGNALS.md`; read it.
Signals live on DEV's `release` branch in `wp-content/assets/<slug>/release-signals/<tag>.md`.
Never check out, switch or edit anything in DEV; only fetch and read.

## 1. Find the signals

1. `git -C DEV fetch origin --prune --tags` (Asad pushes from his own machine, so DEV's local
   branches can be stale; read from `origin/release`).
2. Read `project_asset/plugin-build.txt` for the installed version.
3. List the signals:
   `git -C DEV ls-tree --name-only origin/release wp-content/assets/puredevs-customer-history-for-woocommerce/release-signals/`
   (and the Win-Back folder, once it exists). Take the one named in the arguments, or every signal
   whose version is newer than the installed one, oldest first.
4. Read each with `git -C DEV show origin/release:<path>`. If there is none, say so and stop.

Show Yeasir a short summary per signal (version, the changes table in one line each, the claims)
and ask before installing.

## 2. Install and refresh the demo

1. `scripts/install-plugin.sh <tag>` (for example `ch-v3.2.0`). It builds that tag and swaps in
   the free and pro folders; `plugin-build.txt` records it. Win-Back signals: `install-plugin.sh`
   handles Customer History only today; extend it for the Win-Back slug before the first Win-Back
   install, with Yeasir's OK.
2. Load wp-admin once (version healers run), then do the signal's **Demo store** steps.
3. `scripts/demo-refresh.sh` and read its verdict.
4. Check each screen in the signal's changes table on the demo store, in the right tier. Note any
   screen that does not show what the signal says, and report it to Yeasir; that is a question
   for Asad, not something to paper over in copy.

## 3. Update the marketing material

The brief is versioned by file name and its `file:line` references point into the installed
plugin folders, so a new version gets a new brief.

1. **New brief.** Copy the current `project_asset/marketing/MARKETING-BRIEF-<previous>.md` to
   `MARKETING-BRIEF-<version>.md` with `cp` (it is large; never retype it). In the copy:
   - header table: version documented, date, source tag and commit;
   - §2 Free vs Pro: apply the signal's "Free vs Pro changes";
   - each changed screen: update its entry (what it shows, how it's computed, interactions,
     empty state, demo prerequisites, accuracy notes, `file:line` sources re-checked against the
     installed folders);
   - each new screen: a new entry in the brief's entry template, with a new ID in the right family;
   - each removed or renamed item: update or retire its entry;
   - §C: a new C.1 table for this version (change, tier, where to see it as entry IDs); the
     previous tables move down;
   - §D do-not-claim: apply the signal's "Claims" (add what is no longer true; remove bans on what
     is now true);
   - fix the brief's known stale notes while there (for example the 100-customer cap entries, which
     the owner closed on 2026-09-21).
2. **Screenshots.** Write the reshoot list: every changes-table row marked `new` or `reshoot`, with
   the entry ID and the screen. Capture only if Yeasir asks (`project_asset/marketing/tooling/`).
3. **Downstream copy.** `grep -rln "<previous version>" project_asset` and list each document that
   pins the old version or describes a changed feature (topic filter, prompt pack, landing pages,
   lifecycle emails, wp.org guide, execution list, ad checklist, video docs). Propose the edits per
   document; apply them only after Yeasir says which.
4. **Log.** Append to `project_asset/marketing/RELEASE-UPDATES.md` (create it if missing): tag,
   date, brief file, entries changed, reshoots pending, documents updated, documents left for later.

## 4. Finish

- Old brief and PDFs: ask Yeasir whether to delete the superseded brief (this repo's convention is
  one current version per document) or keep it until the new PDF exists.
- Commit with Yeasir's OK, staging named paths only. Remind him the live demo site must be
  upgraded to the same version before the next `scripts/live-refresh.sh`.
- End with a short report: installed version, demo verdict, brief file, reshoot count, documents
  updated and still pending, and any screen that did not match its signal.
