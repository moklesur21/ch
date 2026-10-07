"""
Phrase timings for the per-video conform scripts: which stretch of a voice take is which phrase of the script.

    sys.path.insert(0, '<repo>/project_asset/marketing/tooling')
    from voice_phrases import phrase_spans
    spans, dur = phrase_spans(video_dir, 'b2b', ["There's one prompt:", "an optional opt-in from Freemius, our licensing provider...", "We skip it."])
    # spans = [(start, end), …] one per phrase, in seconds of audio/<chunk>.mp3

The edges come from the pauses (silencedetect on audio/<chunk>.mp3), so cuts land in silence. Which pause is a
phrase boundary comes from voice-gen.mjs's speech-to-text word times (audio/takes.json), aligned word for word to
the phrase texts — so a take that breathes inside a phrase, or runs two phrases together, still splits right.
Takes without word times (made in the web UI) fall back to the pauses alone: the count must then match exactly.
Normalisation matches voice-gen.mjs: tags and punctuation dropped, numbers as words, "w p admin" → "wp admin".
"""
import json, os, re, subprocess

ONES = 'zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen'.split()
TENS = ' ten twenty thirty forty fifty sixty seventy eighty ninety'.split(' ')

def _say(n):
    if n < 20: return ONES[n]
    if n < 100: return TENS[n // 10] + (' ' + ONES[n % 10] if n % 10 else '')
    return ONES[n // 100] + ' hundred' + (' ' + _say(n % 100) if n % 100 else '')

def words(s):
    s = re.sub(r'\[[^\]]*\]', ' ', s).lower()
    s = re.sub(r"[’']", '', s)
    s = re.sub(r'(\d+)\s*%', r'\1 percent', s)
    w = []
    for x in re.sub(r'[^a-z0-9]+', ' ', s).split():
        w += _say(int(x)).split() if x.isdigit() and int(x) < 1000 else [x]
    out, i = [], 0
    while i < len(w):
        j = i
        while j < len(w) and len(w[j]) == 1: j += 1
        if j - i >= 2: out.append(''.join(w[i:j])); i = j
        else: out.append(w[i]); i += 1
    return out

def _lev(a, b):
    d = list(range(len(b) + 1))
    for i in range(1, len(a) + 1):
        p, d[0] = d[0], i
        for j in range(1, len(b) + 1):
            p, d[j] = d[j], min(d[j] + 1, d[j - 1] + 1, p + (a[i - 1] != b[j - 1]))
    return d[len(b)]

def _same(a, b):
    return a == b or (min(len(a), len(b)) >= 4 and 1 - _lev(a, b) / max(len(a), len(b)) >= 0.75)

def _align(R, H):
    """For each script word, the index of the transcript word it matches (None if missing). Joined/split words count."""
    INF = float('inf')
    D = [[INF] * (len(H) + 1) for _ in range(len(R) + 1)]
    B = [[None] * (len(H) + 1) for _ in range(len(R) + 1)]
    D[0][0] = 0
    for i in range(len(R) + 1):
        for j in range(len(H) + 1):
            v = D[i][j]
            if v == INF: continue
            def go(ii, jj, cost, op):
                if ii <= len(R) and jj <= len(H) and v + cost < D[ii][jj]:
                    D[ii][jj] = v + cost; B[ii][jj] = (i, j, op)
            if i < len(R) and j < len(H): go(i + 1, j + 1, 0 if _same(R[i], H[j]) else 1, 'sub')
            if i < len(R): go(i + 1, j, 1, 'missing')
            if j < len(H): go(i, j + 1, 1, 'extra')
            if i < len(R) and j + 1 < len(H) and _same(R[i], H[j] + H[j + 1]): go(i + 1, j + 2, 0, 'join2')
            if i + 1 < len(R) and j < len(H) and _same(R[i] + R[i + 1], H[j]): go(i + 2, j + 1, 0, 'join1')
    at = [None] * len(R)
    i, j = len(R), len(H)
    while i or j:
        pi, pj, op = B[i][j]
        if op == 'sub': at[pi] = (pj, pj)
        if op == 'join2': at[pi] = (pj, pj + 1)
        if op == 'join1': at[pi] = at[pi + 1] = (pj, pj)
        i, j = pi, pj
    return at

def _pauses(f, noise='-38dB', d=0.18):
    """Speech intervals [(start, end)] of a file, from its pauses, and its duration."""
    out = subprocess.run(['ffmpeg', '-hide_banner', '-i', f, '-af', f'silencedetect=noise={noise}:d={d}', '-f', 'null', '-'],
                         capture_output=True, text=True).stderr
    dur = float(subprocess.run(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', f],
                               capture_output=True, text=True).stdout)
    st = [float(x) for x in re.findall(r'silence_start: ([0-9.]+)', out)]
    en = [float(x) for x in re.findall(r'silence_end: ([0-9.]+)', out)]
    spans, t = [], 0.0
    for a, b in zip(st, en + [dur] * (len(st) - len(en))):
        if a - t > 0.05: spans.append((round(t, 3), round(a, 3)))
        t = b
    if dur - t > 0.05: spans.append((round(t, 3), round(dur, 3)))
    return spans, dur

def _take_words(video_dir, chunk):
    f = os.path.join(video_dir, 'audio', 'takes.json')
    if not os.path.exists(f): return None
    st = json.load(open(f)).get('chunks', {}).get(chunk)
    if not st or not st.get('picked'): return None
    take = st['takes'][st['picked']]
    return take.get('words')

def phrase_spans(video_dir, chunk, phrases):
    """[(start, end)] per phrase of audio/<chunk>.mp3, edges in its pauses, and the file's duration."""
    spans, dur = _pauses(os.path.join(video_dir, 'audio', f'{chunk}.mp3'))
    tw = _take_words(video_dir, chunk)
    if tw is None:                                       # a web-UI take: the pauses alone must give the phrases
        if len(spans) != len(phrases):
            raise SystemExit(f'{chunk}: expected {len(phrases)} phrases, found {len(spans)} {spans} — no word times to group them')
        return spans, dur
    H, Ht = [], []                                       # transcript words (normalised), with their times
    for text, a, b in tw:
        for w in words(text): H.append(w); Ht.append((a, b))
    R, owner = [], []                                    # script words, and which phrase each belongs to
    for k, p in enumerate(phrases):
        for w in words(p): R.append(w); owner.append(k)
    at = _align(R, H)
    win = []                                             # each phrase's speech window from the word times
    for k in range(len(phrases)):
        ts = [Ht[j] for i, m in enumerate(at) if owner[i] == k and m for j in m]
        if not ts: raise SystemExit(f'{chunk}: phrase {k} "{phrases[k]}" not found in the take\'s transcript')
        win.append((min(a for a, _ in ts), max(b for _, b in ts)))
    # a boundary between phrases k and k+1: the middle of the gap between their words
    cut = [(win[k][1] + win[k + 1][0]) / 2 for k in range(len(win) - 1)]
    out = []
    for k in range(len(phrases)):
        lo = cut[k - 1] if k else -1.0
        hi = cut[k] if k < len(cut) else dur + 1
        mine = [(max(a, lo), min(b, hi)) for a, b in spans if min(b, hi) - max(a, lo) > 0.03]
        if not mine:
            raise SystemExit(f'{chunk}: no speech between {lo:.2f} and {hi:.2f} s for phrase {k} "{phrases[k]}"')
        a, b = min(x for x, _ in mine), max(y for _, y in mine)
        out.append((round(a, 3), round(b, 3)))
    straddle = [k for k in range(len(cut)) if any(a < cut[k] - 0.03 and b > cut[k] + 0.03 for a, b in spans)]
    if straddle:
        print(f'  note {chunk}: no pause between phrases ' + ', '.join(f'{k}|{k + 1}' for k in straddle) + ' — cut between their words')
    return out, dur
