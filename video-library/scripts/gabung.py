"""Gabungkan video senyap dengan muzik: python3 gabung.py video.mp4 muzik.wav hasil.mp4 [crf]
Beri crf (cth 30) untuk mampatkan semula video yang ada butiran filem (gaya Vox)."""
import sys, subprocess, imageio_ffmpeg
video, audio, out = sys.argv[1:4]
crf = sys.argv[4] if len(sys.argv) > 4 else None
vc = ['-c:v', 'copy'] if not crf else ['-c:v', 'libx264', '-preset', 'slow', '-crf', crf, '-tune', 'grain', '-pix_fmt', 'yuv420p']
subprocess.run([imageio_ffmpeg.get_ffmpeg_exe(), '-y', '-loglevel', 'error', '-i', video, '-i', audio, '-map', '0:v', '-map', '1:a',
                *vc, '-c:a', 'aac', '-b:a', '192k', '-shortest', '-movflags', '+faststart', out], check=True)
print('siap:', out)
