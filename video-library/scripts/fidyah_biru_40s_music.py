import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import numpy as np, wave, subprocess, imageio_ffmpeg

SR, DUR = 44100, 40.0
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

# --- pads throughout (sparser at the end)
for bar in range(0, 19):
    st = bar * 2.0
    root, notes = chord_at(st)
    g = 0.035 if st < 7.0 else 0.03
    add(pad(notes, 2.4, g), st, 1.0, 0.5)
add(pad((65, 69, 72, 77), 3.0, 0.05), 37.0)

# --- arpeggio pluck (8ths intro, 16ths in main section)
for i in range(int(37.0 / 0.125)):
    st = i * 0.125
    if st < 1.0: continue
    step16 = st >= 7.0
    if not step16 and i % 2: continue
    root, notes = chord_at(st)
    seq = [notes[0], notes[1], notes[2], notes[1] + 12, notes[2], notes[1], notes[0] + 12, notes[2]]
    m = seq[(i // (1 if step16 else 2)) % 8] + 12
    gain = 0.11 if step16 else 0.14
    if 27.0 < st < 29.0: gain *= 1 + (st - 27.0) * 0.3
    add(tone(hz(m), 0.3, harms=5, decay=14), st, gain, 0.3 + 0.4 * ((i // 2) % 2))

# --- drums: 6.5 -> 23.0
for b in range(int(7.0 / BEAT), int(37.0 / BEAT)):
    st = b * BEAT
    add(kick(), st, 0.55)
    if b % 2: add(clap(), st, 0.5, 0.5)
    add(hat(), st + BEAT / 2, 0.18, 0.65)
    if st >= 13.5: add(hat(0.03), st + BEAT / 4 * 3, 0.08, 0.35)
    # sub bass 8ths
    root, _ = chord_at(st)
    for h in (0, 0.25):
        add(tone(hz(root - 12 + 12), 0.24, harms=3, decay=5), st + h, 0.22 if h else 0.28)
# drum fill before the big drop
for k in range(8):
    add(clap(), 28.0 + k * 0.125, 0.25 + k * 0.05, 0.5)

# --- opener: soft pulses on beats before drums
for b in range(1, int(7.0 / BEAT)):
    add(tone(hz(38), 0.4, harms=2, decay=6), b * BEAT, 0.18)

# --- risers & impacts on scene changes
add(riser(1.0), 1.3, 0.6)
add(impact(), 2.3, 0.25)
add(riser(1.5), 5.5, 1.0)
add(impact(), 7.0, 0.35)
add(riser(1.2), 12.5, 0.5)
add(impact(), 13.7, 0.2)
add(riser(1.5), 19.0, 0.7)
add(impact(), 20.5, 0.3)
add(riser(1.5), 27.5, 0.9)
add(impact(), 29.0, 0.4)
add(impact(), 33.5, 0.25)
# bright bell on the result reveal and the CTA
for st, m_ in ((25.1, 84), (25.35, 88), (25.6, 91), (37.4, 77), (37.65, 81), (37.9, 84), (38.2, 89)):
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
with wave.open(SP + 'music_fidyah40.wav', 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((mix * 32767).astype('<i2').tobytes())
print('music ok')
