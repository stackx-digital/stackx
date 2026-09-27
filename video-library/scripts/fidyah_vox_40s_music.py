"""Vox-style explainer bed: felt piano arpeggios, ticking clock, soft bass, paper whooshes on cuts (40s)."""
import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import numpy as np, wave

SR, DUR = 44100, 40.0
N = int(SR * DUR)
SP = OUT
rng = np.random.default_rng(42)
L = np.zeros(N); R = np.zeros(N)
CUTS = [2.3, 4.5, 7.0, 13.7, 20.5, 29.0, 33.5, 37.0]

def hz(m): return 440 * 2 ** ((m - 69) / 12)
def add(x, st, gain=1.0, pan=0.5):
    i0 = int(st * SR)
    if i0 >= N or i0 < 0: return
    x = x[:N - i0]
    L[i0:i0 + len(x)] += x * gain * (1 - pan) * 1.4
    R[i0:i0 + len(x)] += x * gain * pan * 1.4

def piano(m, dur=2.5, vel=1.0):
    """soft felt-piano tone: slightly inharmonic partials, fast attack, two-stage decay"""
    n = int(dur * SR); t = np.arange(n) / SR; f = hz(m)
    x = np.zeros(n)
    for k, amp in ((1, 1.0), (2, 0.42), (3, 0.18), (4, 0.09), (5, 0.04)):
        fk = f * k * (1 + 0.0004 * k * k)
        x += amp * np.sin(2 * np.pi * fk * t) * np.exp(-t * (1.6 + 0.9 * k))
    x += 0.25 * np.sin(2 * np.pi * f * t) * np.exp(-t * 0.6)          # sustain body
    att = np.minimum(1, t / 0.006)
    thump = rng.normal(0, 1, n) * np.exp(-t * 180) * 0.05             # felt hammer
    return (x * att + thump) * vel * 0.22

def tick(hi=True):
    n = int(0.04 * SR); t = np.arange(n) / SR
    click = np.diff(rng.normal(0, 1, n + 1)) * np.exp(-t * 900)
    tone = np.sin(2 * np.pi * (3100 if hi else 2500) * t) * np.exp(-t * 160)
    return (click * 0.5 + tone * 0.6)

def bass(m, dur=1.2):
    n = int(dur * SR); t = np.arange(n) / SR
    x = np.sin(2 * np.pi * hz(m) * t) + 0.25 * np.sin(2 * np.pi * hz(m) * 2 * t)
    return x * np.minimum(1, t / 0.02) * np.exp(-t * 2.2) * 0.35

def pad(notes, dur, gain=0.03):
    n = int(dur * SR); t = np.arange(n) / SR; x = np.zeros(n)
    for m in notes:
        for det in (-0.06, 0.06):
            x += np.sin(2 * np.pi * hz(m) * 2 ** (det / 12) * t + rng.uniform(0, 6))
    return x * np.minimum(1, t / 1.0) * np.minimum(1, (dur - t) / 1.0) * gain

def whoosh(dur=0.55, up=True):
    n = int(dur * SR); t = np.arange(n) / SR
    x = rng.normal(0, 1, n)
    y = np.zeros(n); a_prev = 0.0
    for i in range(n):                      # one-pole low-pass with a moving cutoff
        k = t[i] / dur
        c = (0.02 + 0.35 * np.sin(np.pi * k) ** 2)
        a_prev += c * (x[i] - a_prev); y[i] = a_prev
    y -= np.convolve(y, np.ones(40) / 40, 'same')   # remove rumble
    return y * np.sin(np.pi * np.clip(t / dur, 0, 1)) ** 1.5 * 0.9

# Am – F – C – G, 96 BPM, one chord per bar (2.5 s)
BPM = 96; BEAT = 60 / BPM; BAR = BEAT * 4
prog = [(57, (57, 60, 64)), (53, (53, 57, 60)), (48, (55, 60, 64)), (55, (55, 59, 62))]
def chord(t): return prog[int(t // BAR) % 4]

# pad throughout
for b in range(int(DUR / BAR) + 1):
    st = b * BAR
    if st >= 37.0: break
    add(pad(chord(st)[1], BAR + 0.8, 0.022 if st < 7 else 0.03), st)

# piano: sparse intro, 8th-note arpeggios from 4.5 s, 16th ripples in the calculation section
for i in range(int(37.0 / (BEAT / 2))):
    st = i * BEAT / 2
    root, notes = chord(st)
    step = i % 8
    if st < 4.5 and step not in (0, 3, 6): continue
    seq = [notes[0] + 12, notes[1] + 12, notes[2] + 12, notes[1] + 24, notes[2] + 12, notes[1] + 12, notes[0] + 24, notes[2] + 12]
    m = seq[step]
    vel = 0.8 + 0.25 * (step == 0) - 0.1 * (step % 2)
    if 20.5 <= st < 29.0 and step % 2 == 1:
        add(piano(m + 7, 0.8, 0.35), st + BEAT / 4, 1.0, 0.7)
    add(piano(m, 2.2, vel), st, 1.0, 0.3 + 0.4 * (step % 3) / 2)

# ticking clock from 7 s (tick-tock on 8ths), louder in the calculation & payment sections
for i in range(int(7.0 / (BEAT / 2)), int(37.0 / (BEAT / 2))):
    st = i * BEAT / 2
    g = 0.05 if st < 20.5 else 0.075 if st < 33.5 else 0.05
    add(tick(i % 2 == 0), st, g, 0.62 if i % 2 else 0.38)

# bass on beats 1 and 3 from 7 s, plus a soft heartbeat kick from 20.5 s
def kick():
    n = int(0.35 * SR); t = np.arange(n) / SR
    f = 48 + 70 * np.exp(-t * 28)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 9) * 0.5
for b in range(int(7.0 / BEAT), int(37.0 / BEAT)):
    st = b * BEAT
    if b % 2 == 0: add(bass(chord(st)[0] - 12), st, 1.0)
    if st >= 20.5 and b % 2 == 0: add(kick(), st, 0.55)

# paper whooshes on every cut
for c in CUTS:
    add(whoosh(0.5), c - 0.28, 0.35, 0.5)

# resolving ending: A minor → C major lift
for m in (57, 64, 69, 72, 76):
    add(piano(m, 3.2, 0.9), 37.0 + (m - 57) * 0.012, 1.0, 0.45)
add(pad((60, 64, 67), 3.0, 0.035), 37.0)

# gentle room reverb
def reverb(x, secs=1.6, wet=0.2, seed=0):
    n = int(SR * secs)
    ir = np.random.default_rng(seed).normal(0, 1, n) * np.exp(-np.linspace(0, 7, n)); ir /= np.sqrt((ir ** 2).sum())
    nfft = 1 << int(np.ceil(np.log2(len(x) + n)))
    y = np.fft.irfft(np.fft.rfft(x, nfft) * np.fft.rfft(ir, nfft), nfft)[:len(x)]
    return (1 - wet) * x + wet * y
L, R = reverb(L, seed=1), reverb(R, seed=2)

fade = np.ones(N); fo = int(1.6 * SR); fade[-fo:] = np.linspace(1, 0, fo) ** 1.4
fi = int(0.2 * SR); fade[:fi] = np.linspace(0, 1, fi)
mix = np.stack([L, R], 1) * fade[:, None]
mix = np.tanh(mix / np.percentile(np.abs(mix), 99.9) * 0.85) * 0.88
with wave.open(SP + 'music_vox40.wav', 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((mix * 32767).astype('<i2').tobytes())
for s in range(0, 40, 4):
    seg = mix[s * SR:(s + 4) * SR]
    print(s, 'rms %.3f peak %.3f' % (np.sqrt((seg ** 2).mean()), np.abs(seg).max()))
