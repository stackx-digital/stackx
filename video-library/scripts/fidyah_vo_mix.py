"""Mix the ElevenLabs voiceover over the music bed with ducking, then mux with a silent video.
Usage: python3 fidyah_vo_mix.py voiceover.mp3 music.wav video_silent.mp4 out.mp4"""
import sys, wave, subprocess
import numpy as np, imageio_ffmpeg
vo_path, mus_path, vid, out = sys.argv[1:5]
SR, OFFSET = 44100, 0.2
ff = imageio_ffmpeg.get_ffmpeg_exe()
raw = subprocess.run([ff, '-loglevel', 'error', '-i', vo_path, '-f', 's16le', '-ac', '1', '-ar', str(SR), '-'], capture_output=True).stdout
vo = np.frombuffer(raw, '<i2').astype(np.float32) / 32768
with wave.open(mus_path) as w:
    mus = np.frombuffer(w.readframes(w.getnframes()), '<i2').reshape(-1, 2).astype(np.float32) / 32768
N = len(mus)
v = np.zeros(N, np.float32); i0 = int(OFFSET * SR); v[i0:i0 + len(vo)] = vo[:N - i0]
v *= 0.89 / max(1e-6, np.abs(v).max())
lvl = np.sqrt(np.convolve(v ** 2, np.ones(int(0.05 * SR)) / int(0.05 * SR), 'same'))
act = np.clip(lvl / 0.03, 0, 1)
env = np.zeros(N, np.float32); a_up, a_dn = 1 - np.exp(-1 / (0.03 * SR)), 1 - np.exp(-1 / (0.45 * SR)); e = 0.0
for i in range(0, N, 32):
    tgt = act[i]; e += (a_up if tgt > e else a_dn) * 32 * (tgt - e); e = min(max(e, 0), 1); env[i:i + 32] = e
mix = mus * (0.55 * (1 - 0.55 * env))[:, None] + v[:, None]
mix = np.tanh(mix * 1.05) * 0.95
wav = out[:-4] + '_audio.wav'
with wave.open(wav, 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes((mix * 32767).astype('<i2').tobytes())
subprocess.run([ff, '-y', '-loglevel', 'error', '-i', vid, '-i', wav, '-map', '0:v', '-map', '1:a', '-c:v', 'copy',
                '-c:a', 'aac', '-b:a', '192k', '-shortest', '-movflags', '+faststart', out], check=True)
print('siap:', out)
