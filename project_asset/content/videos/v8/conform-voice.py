#!/usr/bin/env python3
"""
V8 — fit the picture to the voice, place every phrase on its moment, and re-time the captions.

    python3 conform-voice.py            # writes edit.json (ranges, durations, audio) + recordings/captions.srt
    node ../../../marketing/tooling/render-edit.mjs --video v8

Inputs:
  audio/<chunk>.mp3            Yeasir's ElevenLabs takes (originals, untouched)
  recordings/voice/V8_*.wav    the same takes after one shared loudness chain (recordings/voice/PROCESSING.txt):
                               −14 LUFS, true peak ≤ −1.4 dBTP, pauses kept at about −64 dB. Same timeline as the mp3s.
  recordings/scene-NN.steps.json  the recorder's step log: when each click happened in the clip

Phrases are the PHRASES below (the voice script's words, split where a piece or a caption starts). Their edges
come from the pauses; which pause is which boundary comes from voice-gen.mjs's word times
(marketing/tooling/voice_phrases.py), so a take that breathes mid-phrase still splits right. A web-UI take (no
word times) must split on its pauses alone; if it can't, this script stops and says which chunk.
"""
import json, re, sys, os

HERE = os.path.dirname(os.path.abspath(__file__))
os.chdir(HERE)
sys.path.insert(0, os.path.join(HERE, '../../../marketing/tooling'))
from voice_phrases import phrase_spans, words

# The voice script's words per chunk, split into the phrases the pieces and captions below index (0, 1, …).
PHRASES = {
    'b1': ['Where does your store data...', 'go when you install this plugin?'],
    'b2a': ["Here's the whole install,", 'in one take...', 'Plugins,', 'Add Plugin.', 'Search.', 'Install.', 'Activate.'],
    'b2b': ["There's one prompt:", 'an optional opt-in from Freemius, our licensing provider...', 'We skip it.'],
    'b2c': ['No account.', 'No API key.', 'No setup wizard...', "The dashboard is already reading the store's own orders —",
            "and it's self-hosted:", 'what it records stays in your own WordPress database.'],
    'b3a': ['Now a shopper —', 'not logged in —', 'searches the store for "hoodie",', 'and opens two products.'],
    'b3b': ['Back in wp-admin:', 'Customer History, Search Analytics...', 'There\'s "hoodie".', 'And there are the two products they opened.',
            "Your own admin browsing isn't recorded —", 'this came from...', 'the shopper.'],
    'b3c': ["Even the plugin's own usage counters stay here.", 'Tools,', 'Site Health,', 'Info:', '"This information stays on your server."'],
    'b4': ['So here is the decision this gives you...', "Whether customer analytics has to mean sending your customer data to someone else's servers.",
           "With a plugin like this, it doesn't —", 'no SaaS account,', 'no API key,', 'and the data sits in...', 'the same database as your orders.'],
    'b5': ['Everything you just saw is the free version.', 'Free shows the last 90 days;', 'Pro shows your full history...',
           'Install it free —', 'the link is below.'],
}
script = open('recordings/voice-over-elevenlabs.md').read()
for c, ph in PHRASES.items():                       # the phrases must be the voice script's chunk, word for word
    m = re.search(r'^## V8_' + c + r'\b[^\n]*\n+```[^\n]*\n([\s\S]*?)\n```', script, re.M)
    if not m or words(m.group(1)) != words(' '.join(ph)):
        sys.exit(f'{c}: PHRASES no longer match the voice script — update them')

def mapper(ranges):
    segs, o = [], 0.0
    for a, b, sp in ranges:
        segs.append((a, b, sp, o)); o += (b - a) / sp
    def f(t):
        if t <= segs[0][0]: return 0.0
        for a, b, sp, oo in segs:
            if t <= b: return oo + (max(t, a) - a) / sp
        return o
    return f, o

steps = {sc: json.load(open(f'recordings/{sc}.steps.json'))['events'] for sc in ('scene-02', 'scene-03', 'scene-04', 'scene-05')}
def at(scene, do, nth=0, target=None):
    """Source time of the nth step of a kind (optionally matching a target substring)."""
    ev = [e for e in steps[scene] if e['do'] == do and (target is None or target in e.get('target', ''))]
    return ev[nth]['start']

S = {c: phrase_spans(HERE, c, ph) for c, ph in PHRASES.items()}

PAD = 0.1                          # silence kept around a trimmed piece
VO = lambda c: f'recordings/voice/V8_{c}.wav'
def piece(c, i0, i1, out_speech_start, cap=None):
    """Voice piece = speech intervals i0..i1 of chunk c, placed so its first word starts at out_speech_start."""
    sp, dur = S[c]
    a = max(0.0, sp[i0][0] - PAD); b = min(dur, sp[i1][1] + PAD)
    start = round(out_speech_start - (sp[i0][0] - a), 3)
    return {'file': VO(c), 'at': start, 'in': round(a, 3), 'out': round(b, 3), 'chunk': c, 'i0': i0, 'i1': i1}

segments, captions = [], []
def caption(text, seg_start, p, i0, i1, hold=0.0):
    sp = S[p['chunk']][0]
    t0 = seg_start + p['at'] + (sp[i0][0] - p['in'])
    t1 = seg_start + p['at'] + (sp[i1][1] - p['in']) + hold
    captions.append((round(t0, 3), round(t1, 3), text))

T = 0.0
# ── V8_b1 — the question over the answer frame ──────────────────────────────────────────────────
b1 = piece('b1', 0, 1, 0.25)
d = round(0.25 + S['b1'][1] + 0.25, 2)
segments.append({'id': 'V8_b1', 'duration': d, 'audio': [b1]})
caption('Where does <b>your store data</b> go when you install this plugin?', T, b1, 0, 1, 0.2)
T += d

# ── V8_b2 — the one take: every phrase on its click ───────────────────────────────────────────────
s2 = 'scene-02'
plugins_click = at(s2, 'click', 0)
addplugin_click = at(s2, 'click', 1)
typing = at(s2, 'type', 0)
install_click = at(s2, 'click', 3)              # 0 Plugins, 1 Add Plugin, 2 search box, 3 Install Now
activate_click = at(s2, 'click', 4)
optin = at(s2, 'wait-for', 0, '#skip_activation')
skip_click = at(s2, 'click', 5)
dash = at(s2, 'wait-ready', 0)
R2 = [[0, plugins_click, 1], [plugins_click, addplugin_click, 1.5], [addplugin_click, 11.61, 3], [11.61, typing, 1.5],
      [typing, 15.87, 1], [15.87, 20.17, 3], [20.17, install_click, 1.5], [install_click, 34.61, 3],
      [34.61, activate_click, 1], [activate_click, optin, 3], [optin, skip_click, 1], [skip_click, dash, 3], [dash, 68.1, 1]]
R2 = [[round(a, 3), round(b, 3), s] for a, b, s in R2]
m2, _ = mapper(R2)
p1 = piece('b2a', 0, 1, 0.2)                                   # "Here's the whole install, in one take..."
p2 = piece('b2a', 2, 2, m2(plugins_click))                     # "Plugins,"
p3 = piece('b2a', 3, 3, m2(addplugin_click))                   # "Add Plugin."
p4 = piece('b2a', 4, 4, m2(typing))                            # "Search."
p5 = piece('b2a', 5, 5, m2(install_click))                     # "Install."
p6 = piece('b2a', 6, 6, m2(activate_click))                    # "Activate."
skip_out = m2(skip_click)
sb = S['b2b'][0]
b2b = piece('b2b', 0, 2, 0)
b2b['at'] = round(skip_out - (sb[2][0] - b2b['in']), 3)        # "We skip it." starts on the Skip click
b2c = piece('b2c', 0, 5, 0)
b2c['at'] = round(b2b['at'] + (sb[2][1] - b2b['in']) + 0.25, 3)  # right after "We skip it."
end2 = m2(68.1)
assert b2c['at'] + (S['b2c'][1] - b2c['in']) <= end2, 'V8_b2c runs past the end of the take'
segments.append({'id': 'V8_b2', 'ranges': R2, 'audio': [p1, p2, p3, p4, p5, p6, b2b, b2c]})
caption("Here's the whole install, in <b>one take</b>.", T, p1, 0, 1)
caption('Plugins, Add Plugin.', T, p2, 2, 2); captions[-1] = (captions[-1][0], round(T + p3['at'] + (S['b2a'][0][3][1] - p3['in']) + 0.15, 3), captions[-1][2])
caption('Search.', T, p4, 4, 4, 0.6)
caption('Install.', T, p5, 5, 5, 0.6)
caption('Activate.', T, p6, 6, 6, 0.6)
caption("There's one prompt: an <b>optional</b> opt-in from Freemius, our licensing provider.", T, b2b, 0, 1)
caption('We <b>skip it</b>.', T, b2b, 2, 2, 0.3)
caption('<b>No account. No API key.</b> No setup wizard.', T, b2c, 0, 2)
caption("The dashboard is already reading the store's own orders —", T, b2c, 3, 3)
caption("and it's self-hosted: what it records stays in <b>your own WordPress database</b>.", T, b2c, 4, 5, 0.2)
T += round(end2, 3)

# ── V8_b3a — the shopper ──────────────────────────────────────────────────────────────────────────
R3a = [[0, 6.36, 1], [6.36, 30.08, 5]]
m3a, d3a = mapper(R3a)
b3a = piece('b3a', 0, 3, 0.1)
segments.append({'id': 'V8_b3a', 'ranges': R3a, 'audio': [b3a]})
caption('Now a shopper — <b>not logged in</b> — searches the store for <b>"hoodie"</b>,', T, b3a, 0, 2)
caption('and opens two products.', T, b3a, 3, 3, 0.4)
T += round(d3a, 3)

# ── V8_b3b — Search Analytics: each sentence on its table ─────────────────────────────────────────
R3b = [[0, 7.14, 1.5], [7.14, 15.07, 2.5], [15.07, 23.02, 2.5], [23.02, 24.5, 2], [24.5, 29.4, 1]]
m3b, d3b = mapper(R3b)
s4 = 'scene-04'
hoodie = at(s4, 'move', 2)                          # cursor to the "hoodie" row
products = at(s4, 'move', 3)                        # cursor to The Northline Hoodie
notice = at(s4, 'move', 4)                          # cursor under the Heads-up notice
pa = piece('b3b', 0, 1, 0.1)                                    # "Back in wp-admin: Customer History, Search Analytics..."
pb = piece('b3b', 2, 2, m3b(hoodie) + 0.2)                      # "There's "hoodie"."
pc = piece('b3b', 3, 3, m3b(products) + 0.1)                    # "And there are the two products they opened."
pd = piece('b3b', 4, 6, m3b(notice) + 0.1)                      # "Your own admin browsing isn't recorded — this came from... the shopper."
assert pd['at'] + (pd['out'] - pd['in']) <= d3b + 0.01, 'V8_b3b voice runs past the clip'
segments.append({'id': 'V8_b3b', 'ranges': R3b, 'audio': [pa, pb, pc, pd]})
caption('Back in wp-admin: Customer History, Search Analytics.', T, pa, 0, 1)
caption('There\'s <b>"hoodie"</b>.', T, pb, 2, 2, 0.8)
caption('And there are the two products they opened.', T, pc, 3, 3, 0.3)
caption("Your own admin browsing isn't recorded — <b>this came from the shopper</b>.", T, pd, 4, 6, 0.3)
T += round(d3b, 3)

# ── V8_b3c — Site Health ──────────────────────────────────────────────────────────────────────────
R3c = [[0, 11.0, 1]]
m3c, d3c = mapper(R3c)
s5 = 'scene-05'
tools_rest = at(s5, 'hold', 1)                      # the hold with the cursor on Tools
opened = at(s5, 'wait-for', 0)                      # the section has opened
qa = piece('b3c', 0, 0, 0.0)                                    # "Even the plugin's own usage counters stay here."
qb = piece('b3c', 1, 3, max(tools_rest + 0.6, qa['at'] + S['b3c'][0][0][1] + 0.3))   # "Tools, Site Health, Info:"
qc = piece('b3c', 4, 4, m3c(opened) + 0.1)                      # "This information stays on your server."
segments.append({'id': 'V8_b3c', 'ranges': R3c, 'audio': [qa, qb, qc]})
caption("Even the plugin's own usage counters stay here.", T, qa, 0, 0)
caption('Tools, Site Health, Info:', T, qb, 1, 3, 0.3)
caption('"This information <b>stays on your server</b>."', T, qc, 4, 4, 0.4)
T += round(d3c, 3)

# ── V8_b4 — the decision, slow pull-out on the answer frame ───────────────────────────────────────
b4 = piece('b4', 0, 6, 0.3)
d4 = round(0.3 + (b4['out'] - b4['in']) + 0.6, 2)
segments.append({'id': 'V8_b4', 'duration': d4, 'audio': [b4]})
caption('So here is <b>the decision</b> this gives you.', T, b4, 0, 0)
caption("Whether customer analytics has to mean sending your customer data to someone else's servers.", T, b4, 1, 1)
caption("With a plugin like this, it doesn't — no SaaS account, no API key,", T, b4, 2, 4)
caption('and the data sits in the <b>same database as your orders</b>.', T, b4, 5, 6, 0.3)
T += d4

# ── V8_b5 — next step; the end card arrives on "Install it free" ──────────────────────────────────
b5 = piece('b5', 0, 4, 0.3)
cta = b5['at'] + (S['b5'][0][3][0] - b5['in'])                 # "Install it free" starts
still5 = round(cta - 0.3, 2)                                    # the card has faded in just before the words
segments.append({'id': 'V8_b5', 'duration': still5, 'audio': [b5]})
caption('Everything you just saw is the <b>free version</b>.', T, b5, 0, 0)
caption('Free shows the last 90 days; Pro shows your full history.', T, b5, 1, 2)
caption('<b>Install it free</b> — the link is below.', T, b5, 3, 4, 0.8)
end_card = 5.0
T += still5 - 0.5 + end_card

# ── write edit.json ───────────────────────────────────────────────────────────────────────────────
e = json.load(open('edit.json'))
by = {s['id']: s for s in e['segments']}
for s in segments:
    t = by[s['id']]
    for k in ('ranges', 'duration'):
        if k in s: t[k] = s[k]
    t['audio'] = [[p['file'], p['at'], p['in'], p['out']] for p in s['audio']]
# zoom keyframes that depend on segment length
by['V8_b4']['zoom'] = [[0, 182, 150, 1100], [by['V8_b4']['duration'], 170, 70, 1730]]
e['voice'] = ('Conformed by conform-voice.py: picture fitted to the voice; each phrase starts on its moment '
              '(V8_b2: "Plugins," on the Plugins click … "We skip it." on the Skip click). audio entries are '
              '[file, start in the segment, in, out] — a piece of the take, trimmed in its pauses.')
s = json.dumps(e, indent=2, ensure_ascii=False)
s = re.sub(r'\[\s+([^\[\]{}]*?)\s+\]', lambda m: '[' + re.sub(r'\s*\n\s*', ' ', m.group(1)) + ']', s)
open('edit.json', 'w').write(s + '\n')

# ── write captions.srt ────────────────────────────────────────────────────────────────────────────
def ts(t):
    ms = int(round(t * 1000)); h, ms = divmod(ms, 3600000); m, ms = divmod(ms, 60000); sec, ms = divmod(ms, 1000)
    return f'{h:02}:{m:02}:{sec:02},{ms:03}'
caps = sorted(captions)
for i in range(len(caps) - 1):                       # never overlap the next line
    if caps[i][1] > caps[i + 1][0] - 0.05:
        caps[i] = (caps[i][0], round(caps[i + 1][0] - 0.05, 3), caps[i][2])
with open('recordings/captions.srt', 'w') as f:
    for i, (a, b, t) in enumerate(caps, 1):
        f.write(f'{i}\n{ts(a)} --> {ts(b)}\n{t}\n\n')
print(f'total {T:.2f} s · {len(caps)} captions')
for s in segments:
    print(s['id'], s.get('duration') or round(mapper(s['ranges'])[1], 2), [(os.path.basename(p['file']), p['at']) for p in s['audio']])
