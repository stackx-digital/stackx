"""Siri Fidyah 5/5 (si mati) re-timed to the ElevenLabs voiceover (64s). Same scenes as fidyah_siri5_si_mati.py.
Writes a silent video and a music bed (scene bells moved to the new cuts); mix the voice with fidyah_vo_mix.py.
Usage: FMT=916 python3 fidyah_siri5_vo.py [preview seconds ...]"""
import sys, subprocess
import numpy as np
import imageio_ffmpeg
import fidyah_siri5_si_mati as V

DUR = 64.0
N = int(V.FPS * DUR)
OUT = V.B.SP + f'siri5_vo_{V.B.FMT}_silent.mp4'
MUSIC = V.B.SP + 'music_siri5_vo.wav'
# (narration time, time in the 57s animation)
WARP = [(0, 0), (0.4, 1.0), (1.6, 1.6), (3.4, 3.6),                             # "Arwah ayah... ada puasa yang belum diganti"
        (4.1, 5.5), (4.6, 6.2), (10.4, 8.6), (13.7, 11.0), (16.5, 13.1),       # timeline: sakit / sihat / pergi / "masih ada"
        (19.2, 17.3), (19.5, 17.6), (19.8, 18.3), (24.4, 19.3), (26.0, 20.8),  # rule cards on "Jika arwah..." / "Jika sakit..."
        (28.2, 24.4), (28.4, 24.6), (30.75, 25.1), (32.8, 25.85), (35.1, 26.6),  # hadith, line by line as read
        (37.0, 27.35), (38.8, 28.1), (39.3, 28.55), (40.4, 29.4),
        (40.5, 34.1), (40.8, 35.2), (42.0, 35.9), (43.5, 36.8), (44.5, 38.0),  # 15 hari, RM4, RM60 on "enam puluh ringgit"
        (47.5, 39.1), (50.2, 43.0), (51.1, 45.0), (52.6, 45.6),                # "nama arwah", agihan photos + caption
        (55.6, 51.0), (56.9, 51.9), (59.2, 52.5), (60.0, 53.1), (64.0, 57.0)]  # outro, URL on "bayar fidyah online"
XS, YS = [a for a, _ in WARP], [b for _, b in WARP]
def warp(T): return float(np.interp(T, XS, YS))
def unwarp(t): return float(np.interp(t, YS, XS))

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: V.render(warp(float(ts))).save(f'{V.B.SP}s5vo_{V.B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    if V.B.FMT == '916': V.build_audio(MUSIC, DUR, [unwarp(c) for c in V.CUT[1:-1]])
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{V.W}x{V.H}', '-r', str(V.FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '21', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(V.render(warp(i / V.FPS)).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
