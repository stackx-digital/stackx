"""Video khas "Tak pernah terlambat" re-timed to the ElevenLabs voiceover (64s). Same scenes as fidyah_khas_lewat_qada_tua.py.
Writes a silent video and a music bed (scene bells moved to the new cuts); mix the voice with fidyah_vo_mix.py.
Usage: FMT=916 python3 fidyah_khas_lewat_qada_tua_vo.py [preview seconds ...]"""
import sys, subprocess
import numpy as np
import imageio_ffmpeg
import fidyah_khas_lewat_qada_tua as V

DUR = 64.0
N = int(V.FPS * DUR)
OUT = V.B.SP + f'khas_lqt_vo_{V.B.FMT}_silent.mp4'
MUSIC = V.B.SP + 'music_khas_lqt_vo.wav'
# (narration time, time in the 56s animation)
WARP = [(0, 0), (2.6, 2.4), (5.2, 5.0), (7.9, 5.7),                              # hook; "Tinggal puasa sepuluh hari"
        (10.7, 7.2), (11.3, 7.6), (14.6, 11.6), (15.1, 12.0), (18.6, 14.9),     # years roll on "Tahun berganti... 30 Ramadan"
        (19.0, 15.1), (20.2, 15.8), (24.0, 17.4), (25.5, 18.5), (27.9, 21.8),   # taubat, Az-Zumar 53 as it is read
        (28.2, 22.0), (29.7, 22.9), (33.0, 24.6), (36.0, 27.0), (37.9, 30.8),   # "Pertama" / "Kedua" cards
        (38.1, 31.0), (39.0, 31.7), (40.1, 32.5), (41.5, 33.3),                 # 10 hari, RM4, x30 rows as spoken
        (42.8, 34.3), (44.4, 35.9), (44.9, 36.8), (48.4, 40.8),                 # RM1,200 on "seribu dua ratus"
        (48.6, 41.0), (51.9, 43.2), (53.2, 43.8), (54.9, 49.3),                 # agihan, "Tak pernah terlambat"
        (55.1, 49.6), (56.4, 50.05), (57.2, 50.4), (59.0, 51.0), (59.8, 51.6), (64.0, 56.0)]  # outro, URL on "bayar fidyah online"
XS, YS = [a for a, _ in WARP], [b for _, b in WARP]
def warp(T): return float(np.interp(T, XS, YS))
def unwarp(t): return float(np.interp(t, YS, XS))

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: V.render(warp(float(ts))).save(f'{V.B.SP}lqtvo_{V.B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    if V.B.FMT == '916': V.S5.build_audio(MUSIC, DUR, [unwarp(c) for c in V.CUT[1:-1]])
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{V.W}x{V.H}', '-r', str(V.FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '21', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(V.render(warp(i / V.FPS)).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
