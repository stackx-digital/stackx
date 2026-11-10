"""Siri Fidyah 1/5 (warga emas) re-timed to the ElevenLabs voiceover (43s). Same chat as fidyah_siri1_warga_emas.py.
Writes a silent video and a music + chat-SFX bed (SFX moved to the new times); mix the voice with fidyah_vo_mix.py.
Usage: FMT=916 python3 fidyah_siri1_vo.py [preview seconds ...]"""
import sys, subprocess, wave
import numpy as np
import imageio_ffmpeg
import fidyah_siri1_warga_emas as V

DUR = 43.0
N = int(V.FPS * DUR)
OUT = V.B.SP + f'siri1_vo_{V.B.FMT}_silent.mp4'
MUSIC = V.B.SP + 'music_siri1_vo.wav'
# (narration time, time in the 41.7s chat): each message lands on the words that describe it
WARP = [(0, 0), (2.3, 3.0), (3.3, 4.97), (4.4, 6.78), (5.3, 7.83),          # hook, Kak Long, Angah "doktor... 78"
        (8.2, 10.03), (9.5, 11.19), (10.6, 11.84), (11.3, 12.6),             # "Jadi... kena ganti?" typed + sent
        (12.2, 13.93), (12.9, 14.93), (18.9, 18.13), (19.5, 19.32),          # info card on "Bagi warga emas..."
        (20.2, 19.97), (21.1, 20.77), (23.6, 22.22), (24.2, 23.0),           # "Sehari... RM4", "Tiga puluh hari"
        (24.9, 23.6), (25.4, 24.3), (28.3, 27.0),                            # RM120 card on "seratus dua puluh"
        (28.5, 27.45), (28.9, 28.29), (30.0, 29.71), (31.0, 30.71),          # "bayarkan untuk mak", link on "telefon"
        (31.6, 31.21), (32.3, 32.66), (33.6, 33.66), (34.3, 35.21),          # "Resit... emel", reactions
        (34.6, 35.66), (35.8, 36.56), (38.4, 36.85), (38.6, 36.96),          # outro quote, URL on "bayar fidyah online"
        (40.6, 38.96), (43.0, 41.7)]
XS, YS = [a for a, _ in WARP], [b for _, b in WARP]
def warp(T): return float(np.interp(T, XS, YS))
def unwarp(t): return float(np.interp(t, YS, XS))

def build_music(path):
    n = int(DUR * V.SR)
    fx = np.zeros(n)
    for tt, name, arg in V.SFX:
        x = V.sfx(name, arg); i0 = int(unwarp(tt) * V.SR)
        if i0 >= n: continue
        fx[i0:i0 + len(x)] += x[:n - i0]
    bed = V.lofi(DUR)
    bed *= 0.34 / max(1e-6, np.percentile(np.abs(bed), 99.9))
    out = np.tanh((bed + fx[:, None] * 0.8) * 1.1) * 0.92
    with wave.open(path, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(V.SR)
        w.writeframes((out * 32767).astype('<i2').tobytes())

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: V.render(warp(float(ts))).save(f'{V.B.SP}s1vo_{V.B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    if V.B.FMT == '916': build_music(MUSIC)
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{V.W}x{V.H}', '-r', str(V.FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '21', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(V.render(warp(i / V.FPS)).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
