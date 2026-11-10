"""Siri Fidyah 4/5 re-timed to the ElevenLabs voiceover (38s). Same scenes as fidyah_siri4_lewat_qada.py.
Usage: FMT=916 python3 fidyah_siri4_vo.py [preview seconds ...]"""
import sys, subprocess
import numpy as np
import imageio_ffmpeg
import fidyah_siri4_lewat_qada as V

DUR = 38.0
N = int(V.FPS * DUR)
OUT = V.B.SP + f'siri4_vo_{V.B.FMT}_silent.mp4'
# (time on the narration timeline, time in the 51s animation): pins each scene, chip, badge and number to its spoken line
WARP = [(0, 0), (2.6, 2.2), (3.7, 3.5), (4.1, 4.4), (8.5, 6.6), (9.0, 7.6), (10.4, 8.4), (11.9, 13.0), (13.3, 15.0),
        (15.5, 21.0), (16.0, 21.8), (18.2, 27.0), (18.4, 27.2), (21.4, 30.6), (21.5, 30.7), (26.2, 40.0), (26.5, 40.6),
        (28.2, 42.6), (29.0, 45.5), (29.1, 45.7), (30.3, 46.0), (31.1, 46.3), (32.8, 46.8), (33.6, 47.4), (38.0, 51.0)]
def warp(T): return float(np.interp(T, [a for a, _ in WARP], [b for _, b in WARP]))

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: V.render(warp(float(ts))).save(f'{V.B.SP}s4vo_{V.B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{V.W}x{V.H}', '-r', str(V.FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(V.render(warp(i / V.FPS)).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
