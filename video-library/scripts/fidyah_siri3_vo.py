"""Siri Fidyah 3/5 re-timed to the ElevenLabs voiceover (47.5s). Same scenes as fidyah_siri3_ibu_hamil.py.
Usage: FMT=916 python3 fidyah_siri3_vo.py [preview seconds ...]"""
import sys, subprocess
import numpy as np
import imageio_ffmpeg
import fidyah_siri3_ibu_hamil as V

DUR = 47.5
N = int(V.FPS * DUR)
OUT = V.SP + f'siri3_vo_{V.FMT}_silent.mp4'
# (narration time, time in the 55s animation): each card, branch, receipt line and highlight lands on its spoken words
WARP = [(0, 0), (0.8, 0.6), (2.6, 1.4), (3.2, 1.9), (4.0, 4.2), (4.3, 4.6), (5.15, 5.2), (6.1, 5.8), (6.5, 6.6), (7.35, 7.6),
        (9.9, 10.0), (10.1, 10.4), (10.7, 10.8), (11.4, 11.6), (11.97, 12.6), (12.3, 13.0), (13.05, 13.4), (13.3, 13.8),
        (14.0, 16.0), (14.2, 16.3), (15.3, 17.4), (16.0, 18.0), (17.6, 19.8), (17.8, 20.4), (18.2, 20.8), (19.3, 21.6),
        (20.4, 25.0), (22.3, 25.6), (23.8, 27.4), (24.2, 28.0), (25.0, 28.4), (25.6, 29.4), (27.0, 30.4), (29.9, 34.0),
        (30.1, 34.4), (30.4, 34.8), (31.0, 35.4), (32.4, 36.0), (33.8, 36.6), (34.3, 37.1), (34.7, 37.5), (35.2, 38.4),
        (35.5, 39.4), (35.8, 40.0), (35.95, 41.4), (36.05, 44.0), (36.2, 44.3), (37.4, 45.0), (37.9, 45.6), (38.2, 46.4),
        (38.45, 46.8), (38.8, 49.5), (38.9, 49.9), (40.2, 50.25), (41.2, 50.6), (41.9, 51.2), (42.8, 51.8), (43.2, 52.0),
        (44.6, 52.6), (47.5, 55.0)]
def warp(T): return float(np.interp(T, [a for a, _ in WARP], [b for _, b in WARP]))

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: V.render(warp(float(ts))).save(f'{V.SP}s3vo_{V.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{V.W}x{V.H}', '-r', str(V.FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(V.render(warp(i / V.FPS)).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
