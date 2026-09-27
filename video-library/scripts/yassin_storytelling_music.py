import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import numpy as np, wave, subprocess, imageio_ffmpeg

SR, DUR = 44100, 30.0
S = 1.5  # stretch the 20s arrangement to 30s
N = int(SR * DUR)
t = np.arange(N) / SR
rng = np.random.default_rng(3)
BASE = OUT

def hz(midi): return 440.0 * 2 ** ((midi - 69) / 12)
def env_adsr(n, a, r):
    e = np.ones(n); na, nr = int(a * SR), int(r * SR)
    e[:na] = np.linspace(0, 1, na); e[-nr:] *= np.linspace(1, 0, nr); return e

L = np.zeros(N); R = np.zeros(N)

# --- drone: D2 + A2, slowly breathing
drone = np.zeros(N)
for m, amp in ((38, 0.22), (45, 0.12), (50, 0.06)):
    f = hz(m)
    for k, ha in ((1, 1.0), (2, 0.35), (3, 0.15), (4, 0.08)):
        drone += amp * ha * np.sin(2 * np.pi * f * k * t + rng.uniform(0, 6))
drone *= 0.75 + 0.25 * np.sin(2 * np.pi * 0.12 * t)
L += drone; R += drone

# --- string pad chords per scene (D Hijaz colour)
chords = [(0, 3, (62, 65, 69)),      # Dm
          (3, 6, (62, 66, 69)),      # D (hijaz major)
          (6, 9, (67, 70, 74)),      # Gm
          (9, 12, (63, 67, 70)),     # Eb
          (12, 15, (60, 63, 67)),    # Cm
          (15, 17.2, (62, 66, 69)),  # D
          (17.2, 20, (62, 65, 69))]  # Dm (resolve)
for s, e, notes in chords:
    s, e = s * S, e * S
    i0, i1 = int(s * SR), min(N, int((e + 0.8) * SR))
    n = i1 - i0; tt = np.arange(n) / SR
    seg_l = np.zeros(n); seg_r = np.zeros(n)
    for m in notes:
        for det, pan in ((-0.12, 0.8), (0.12, 0.2)):
            f = hz(m) * 2 ** (det / 12)
            saw = sum(np.sin(2 * np.pi * f * k * tt) / k for k in range(1, 7))
            seg_l += pan * saw; seg_r += (1 - pan) * saw
    ev = env_adsr(n, 1.0, 1.2) * 0.035
    L[i0:i1] += seg_l * ev; R[i0:i1] += seg_r * ev

# --- oud-like plucks (Karplus-Strong) on text entries
def pluck(freq, dur=2.2, bright=0.5):
    n = int(SR * dur); p = int(SR / freq)
    buf = rng.uniform(-1, 1, p); out = np.zeros(n)
    for i in range(n):
        out[i] = buf[i % p]
        buf[i % p] = 0.5 * (buf[i % p] + buf[(i + 1) % p]) * 0.996
    return out * np.linspace(1, 0.2, n) ** 0.5

hijaz = [62, 63, 66, 67, 69, 70, 72, 74]  # D Eb F# G A Bb C D
melody = [(0.3, 74), (0.9, 72), (1.4, 70), (2.0, 69),
          (3.35, 62), (3.7, 63), (4.1, 66), (4.4, 67), (4.8, 69),
          (6.35, 70), (6.8, 69), (7.4, 67), (7.8, 66), (8.2, 63),
          (9.35, 67), (9.7, 69), (10.4, 70), (10.8, 72), (11.2, 70),
          (12.35, 74), (12.8, 72), (13.4, 70), (13.8, 69), (14.3, 67),
          (15.35, 66), (15.9, 63), (16.5, 62),
          (17.6, 69), (18.2, 66), (18.7, 63), (19.1, 62)]
for st, m in melody:
    st *= S
    x = pluck(hz(m)) * 0.16
    i0 = int(st * SR); i1 = min(N, i0 + len(x)); x = x[:i1 - i0]
    pan = 0.35 + 0.3 * ((m % 5) / 5)
    L[i0:i1] += x * pan; R[i0:i1] += x * (1 - pan)

# --- soft frame-drum hits on scene changes
for st in (0.0, 3.0, 6.0, 9.0, 12.0, 15.0, 17.2):
    st *= S
    n = int(SR * 1.2); tt = np.arange(n) / SR
    f = 55 * np.exp(-tt * 3) + 45
    hit = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-tt * 4) * 0.35
    hit += rng.normal(0, 1, n) * np.exp(-tt * 30) * 0.03
    i0 = int(st * SR); i1 = min(N, i0 + n)
    L[i0:i1] += hit[:i1 - i0]; R[i0:i1] += hit[:i1 - i0]

# --- reverb (convolution with decaying noise)
def reverb(x, secs=2.2, wet=0.28, seed=0):
    ir = np.random.default_rng(seed).normal(0, 1, int(SR * secs)) * np.exp(-np.linspace(0, 6, int(SR * secs)))
    ir /= np.sqrt(np.sum(ir ** 2))
    nfft = 1 << int(np.ceil(np.log2(len(x) + len(ir))))
    y = np.fft.irfft(np.fft.rfft(x, nfft) * np.fft.rfft(ir, nfft), nfft)[:len(x)]
    return (1 - wet) * x + wet * y
L, R = reverb(L, seed=1), reverb(R, seed=2)

# --- master fades + normalise
fade = np.ones(N)
fi, fo = int(1.0 * SR), int(1.5 * SR)
fade[:fi] = np.linspace(0, 1, fi); fade[-fo:] = np.linspace(1, 0, fo)
mix = np.stack([L, R], 1) * fade[:, None]
mix = np.tanh(mix / np.abs(mix).max() * 1.1) * 0.85
pcm = (mix * 32767).astype('<i2')
with wave.open(BASE + 'music.wav', 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes(pcm.tobytes())

ff = imageio_ffmpeg.get_ffmpeg_exe()
subprocess.run([ff, '-y', '-i', BASE + 'syeikh_ahmad_yassin_30s.mp4', '-i', BASE + 'music.wav',
                '-map', '0:v', '-map', '1:a', '-c:v', 'copy', '-c:a', 'aac', '-b:a', '192k',
                '-shortest', '-movflags', '+faststart', BASE + 'syeikh_ahmad_yassin_30s_muzik.mp4'],
               check=True, stderr=subprocess.DEVNULL)
print('ok')
