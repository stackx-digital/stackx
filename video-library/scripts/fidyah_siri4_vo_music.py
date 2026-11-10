# Music bed for Siri Fidyah 4/5 with voiceover (38s, cuts follow the narration)
import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import numpy as np, wave, subprocess, imageio_ffmpeg

SR, DUR = 44100, 38.0
N = int(SR * DUR)
SP = OUT
rng = np.random.default_rng(21)
BEAT = 0.5  # 120 BPM
L = np.zeros(N); R = np.zeros(N)

def hz(m): return 440 * 2 ** ((m - 69) / 12)
def add(x, st, gain=1.0, pan=0.5):
    i0 = int(st * SR)
    if i0 >= N: return
    x = x[:N - i0]
    L[i0:i0 + len(x)] += x * gain * (1 - pan) * 2 ** 0.5
    R[i0:i0 + len(x)] += x * gain * pan * 2 ** 0.5

def tone(freq, dur, harms=6, decay=6.0, att=0.004):
    n = int(dur * SR); t = np.arange(n) / SR
    x = sum(np.sin(2 * np.pi * freq * k * t) / k ** 1.3 * np.exp(-t * decay * k * 0.5) for k in range(1, harms + 1))
    a = np.minimum(1, t / att)
    return x * a * np.exp(-t * decay * 0.3)

def kick():
    n = int(0.45 * SR); t = np.arange(n) / SR
    f = 50 + 110 * np.exp(-t * 30)
    return np.tanh(2.2 * np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 7))

def hat(d=0.05):
    n = int(d * SR); x = np.diff(rng.normal(0, 1, n + 1))
    return x * np.exp(-np.arange(n) / SR * 70)

def clap():
    n = int(0.25 * SR); t = np.arange(n) / SR
    x = np.diff(rng.normal(0, 1, n + 1))
    env = np.exp(-t * 18) + sum(np.exp(-np.maximum(0, t - d) * 120) * (t >= d) for d in (0.0, 0.011, 0.022)) * 0.6
    return x * env * 0.5

def riser(dur):
    n = int(dur * SR); t = np.arange(n) / SR
    x = rng.normal(0, 1, n)
    # crude rising high-pass: difference amount grows
    y = x - np.concatenate([[0], x[:-1]]) * (1 - t / dur * 0.9)
    return y * (t / dur) ** 2.5 * 0.35

def impact():
    n = int(3.0 * SR); t = np.arange(n) / SR
    f = 38 + 60 * np.exp(-t * 8)
    low = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 1.6)
    noise = rng.normal(0, 1, n) * np.exp(-t * 5) * 0.15
    return np.tanh(1.6 * (low + noise))

def pad(notes, dur, gain=0.05):
    n = int(dur * SR); t = np.arange(n) / SR
    x = np.zeros(n)
    for m in notes:
        for det in (-0.08, 0.0, 0.08):
            f = hz(m) * 2 ** (det / 12)
            x += sum(np.sin(2 * np.pi * f * k * t + rng.uniform(0, 6)) / k ** 1.6 for k in range(1, 6))
    env = np.minimum(1, t / 0.8) * np.minimum(1, (dur - t) / 0.8)
    return x * env * gain

# progression (per bar = 2s): Dm, Bb, F, C  (i - VI - III - VII)
prog = [(41, (65, 69, 72)), (48, (64, 67, 72)), (50, (62, 65, 69)), (46, (62, 65, 70))]  # F C Dm Bb
def chord_at(t): return prog[int(t // 2) % 4]

# --- arrangement for Siri Fidyah 4/5 (cuts at 3.5, 13, 27, 40, 45.5)
CUTS = [3.7, 11.9, 18.2, 26.2, 29.0]
for bar in range(0, 15):
    st = bar * 2.0
    root, notes = chord_at(st)
    add(pad(notes, 2.4, 0.035 if st < 3.7 else 0.03), st, 1.0, 0.5)
add(pad((65, 69, 72, 77), 8.5, 0.05), 29.0)
for i in range(int(29.0 / 0.125)):
    st = i * 0.125
    if st < 1.0: continue
    step16 = st >= 11.9
    if not step16 and i % 2: continue
    root, notes = chord_at(st)
    seq = [notes[0], notes[1], notes[2], notes[1] + 12, notes[2], notes[1], notes[0] + 12, notes[2]]
    m = seq[(i // (1 if step16 else 2)) % 8] + 12
    gain = 0.11 if step16 else 0.14
    if 24.6 < st < 26.2: gain *= 1 + (st - 24.6) * 0.3
    add(tone(hz(m), 0.3, harms=5, decay=14), st, gain, 0.3 + 0.4 * ((i // 2) % 2))
for b in range(int(3.7 / BEAT) + 1, int(29.0 / BEAT)):
    st = b * BEAT
    add(kick(), st, 0.55)
    if b % 2: add(clap(), st, 0.5, 0.5)
    add(hat(), st + BEAT / 2, 0.18, 0.65)
    if st >= 18.2: add(hat(0.03), st + BEAT / 4 * 3, 0.08, 0.35)
    root, _ = chord_at(st)
    for h in (0, 0.25):
        add(tone(hz(root), 0.24, harms=3, decay=5), st + h, 0.22 if h else 0.28)
for k in range(8):
    add(clap(), 25.2 + k * 0.125, 0.25 + k * 0.05, 0.5)
for b in range(1, int(3.7 / BEAT) + 1):
    add(tone(hz(38), 0.4, harms=2, decay=6), b * BEAT, 0.18)
add(riser(1.0), 2.7, 0.6)
for c, g in zip(CUTS, (0.35, 0.3, 0.35, 0.4, 0.3)):
    add(riser(1.4), c - 1.4, 0.7)
    add(impact(), c, g)
# bells: RM84 appears on the calculator, and the outro
for st, m_ in ((21.5, 84), (21.75, 88), (22.0, 91), (32.9, 77), (33.15, 81), (33.4, 84), (33.7, 89)):
    add(tone(hz(m_), 1.6, harms=3, decay=3.0), st, 0.12, 0.5)

# --- reverb
def reverb(x, secs=1.8, wet=0.22, seed=0):
    n = int(SR * secs)
    ir = np.random.default_rng(seed).normal(0, 1, n) * np.exp(-np.linspace(0, 7, n)); ir /= np.sqrt((ir ** 2).sum())
    nfft = 1 << int(np.ceil(np.log2(len(x) + n)))
    y = np.fft.irfft(np.fft.rfft(x, nfft) * np.fft.rfft(ir, nfft), nfft)[:len(x)]
    return (1 - wet) * x + wet * y
L, R = reverb(L, seed=1), reverb(R, seed=2)

fade = np.ones(N); fo = int(1.8 * SR); fade[-fo:] = np.linspace(1, 0, fo) ** 1.5
fi = int(0.3 * SR); fade[:fi] = np.linspace(0, 1, fi)
mix = np.stack([L, R], 1) * fade[:, None]
mix = np.tanh(mix / np.percentile(np.abs(mix), 99.9) * 0.9) * 0.9
with wave.open(SP + 'music_siri4_vo.wav', 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((mix * 32767).astype('<i2').tobytes())
print('music ok')
