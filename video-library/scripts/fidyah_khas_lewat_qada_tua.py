"""Video khas: Lewat qada' bertahun-tahun, "Tak pernah terlambat" (~56s).
For people who skipped fasts when young and, older now, have repented and want to settle qada' + fidyah.
Gentle storytelling on the light brand background; piano + pad from fidyah_siri5_si_mati.
FMT=916 (1080x1920 Reels/Stories) or FMT=45 (1080x1350 Feed).
Usage: FMT=916 python3 fidyah_khas_lewat_qada_tua.py [preview seconds ...]"""
import math, sys, subprocess
import numpy as np
from PIL import Image, ImageDraw, ImageFilter
import imageio_ffmpeg
import fidyah_meta_ads as B
import fidyah_siri5_si_mati as S5                      # shared helpers: cards, chips, URL pill, piano music
from fidyah_meta_ads import (W, H, FPS, BY, Y, sprite, place, life, layer, alpha_img, font, clamp,
                             ease_out, ease_io, roll, BLUE, NAVY, GREY, PURE, LINE, TINT, H2, TINY)

DUR = 56.0
N = int(FPS * DUR)
OUT = B.SP + f'khas_lewat_qada_tua_{B.FMT}.mp4'
WAV = B.SP + f'khas_lewat_qada_tua_{B.FMT}.wav'
GOLD, SOFT = S5.GOLD, S5.SOFT
#        hook  years  taubat  steps  calc  agihan  outro
CUT = [0, 5.0, 15.0, 22.0, 31.0, 41.0, 49.5, 56.0]
Y0, Y1, DAYS = 1996, 2026, 10
YEARS = Y1 - Y0                                       # Ramadans passed since the missed fasts

def top_pill(f, a, s='BAYAR FIDYAH ONLINE'):
    fnt = font('Inter-SemiBold', 26)
    w = int(fnt.getlength(s)) + 44
    lay = layer(); d = ImageDraw.Draw(lay)
    d.rounded_rectangle((W / 2 - w / 2, Y(0), W / 2 + w / 2, Y(48)), 24, fill=TINT + (255,))
    d.text((W / 2, Y(24)), s, font=fnt, fill=BLUE + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, a))

# ---------- 1. hook ----------
HOOK_F = font('InterDisplay-Bold', 92)
def scene_hook(f, t):
    e = CUT[1] + 0.3
    a, b, s = life(t, 0.0, e, fin=0.8)
    f.alpha_composite(alpha_img(S5.GLOW, a * 0.8))
    top_pill(f, a)
    for i, ln in enumerate(['Puasa yang ditinggal', 'masa muda...']):
        ka, kb, ks = life(t, 0.3 + i * 0.5, e, fin=0.9)
        place(f, sprite(ln, HOOK_F, NAVY, grad='green' if i == 1 else None), W / 2, Y(330 + i * 110), ka, ks, kb)
    ka, kb, ks = life(t, 2.4, e, fin=0.9)
    place(f, sprite('masih boleh dilunaskan.', font('InterDisplay-SemiBold', 54), GREY), W / 2, Y(620), ka, ks, kb)

# ---------- 2. the years go by ----------
YEAR_F = font('InterDisplay-Bold', 210)
def scene_years(f, t):
    s0, e = CUT[1], CUT[2] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Kisah ramai di antara kita', font('Inter-SemiBold', 34), BLUE), W / 2, Y(30), a, 1, b)
    r0, r1 = s0 + 2.6, s0 + 6.6
    yr = int(round(roll(Y0, Y1, t, r0, r1 - r0)))
    age = 20 + (yr - Y0)
    place(f, sprite(str(yr), YEAR_F, grad='green' if yr == Y1 else None, fill=NAVY), W / 2, Y(250), a, s, b)
    place(f, sprite(f'Umur {age}', font('InterDisplay-SemiBold', 46), SOFT), W / 2, Y(400), a, 1, b)
    # one dot per Ramadan that passed without qada'
    lay = layer(); d = ImageDraw.Draw(lay)
    cols, gap = 15, 52
    x0 = W / 2 - (cols - 1) * gap / 2
    lit = clamp((t - r0) / (r1 - r0)) * YEARS
    for i in range(YEARS):
        cx, cy = x0 + (i % cols) * gap, Y(520) + (i // cols) * gap
        k = clamp(lit - i)
        col = tuple(int(LINE[j] + (GOLD[j] - LINE[j]) * k) for j in range(3))
        r = 13 + 4 * k
        d.ellipse((cx - r, cy - r, cx + r, cy + r), fill=col + (255,))
    f.alpha_composite(alpha_img(lay, a))
    ka, kb, _ = life(t, r0 + 0.2, e)
    n = int(lit + 0.0001)
    place(f, sprite(f'{n} Ramadan berlalu', font('Inter-SemiBold', 34), GOLD), W / 2, Y(650), ka, 1, kb)
    ka, kb, ks = life(t, s0 + 0.6, r0 + 0.2)
    place(f, sprite(f'Tinggal puasa {DAYS} hari, tanpa uzur.', font('InterDisplay-Bold', 52), NAVY), W / 2, Y(830), ka, ks, kb)
    ka, kb, ks = life(t, r1 + 0.4, e)
    place(f, sprite("Masih belum diganti.", font('InterDisplay-Bold', 56), grad='green'), W / 2, Y(830), ka, ks, kb)

# ---------- 3. a heart that turns back ----------
MAN = S5.shadow_card(S5.crop_to(B.ASSETS + 'images/8.webp', 460, 460), 34, 14)
def scene_taubat(f, t):
    s0, e = CUT[2], CUT[3] + 0.3
    a, b, s = life(t, s0, e, fin=0.9)
    z = 1 + 0.02 * clamp((t - s0) / 7)
    place(f, MAN, W / 2, Y(290), a, s * z, b)
    ka, kb, ks = life(t, s0 + 0.8, e)
    place(f, sprite('Kini, hati terdetik untuk bertaubat.', font('InterDisplay-Bold', 54), NAVY), W / 2, Y(620), ka, ks, kb)
    ka, kb, ks = life(t, s0 + 2.4, e, fin=1.0)
    place(f, sprite('“Janganlah kamu berputus asa', font('PlusJakartaSans-SemiBoldItalic', 46), BLUE), W / 2, Y(760), ka, 1, kb)
    place(f, sprite('dari rahmat Allah.”', font('PlusJakartaSans-SemiBoldItalic', 46), BLUE), W / 2, Y(824), ka, 1, kb)
    ka, kb, _ = life(t, s0 + 3.4, e)
    place(f, sprite('Az-Zumar: 53', font('Inter-SemiBold', 30), GREY), W / 2, Y(900), ka, 1, kb)

# ---------- 4. what to do ----------
def step_card(num, title, sub):
    w, h = 900, 200
    im = Image.new('RGBA', (w + 40, h + 50), (0, 0, 0, 0))
    sh = Image.new('RGBA', im.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((20, 30, 20 + w, 30 + h), 34, fill=(15, 40, 80, 40))
    im.alpha_composite(sh.filter(ImageFilter.GaussianBlur(14)))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle((20, 16, 20 + w, 16 + h), 34, fill=PURE)
    d.ellipse((56, 66, 156, 166), fill=BLUE)
    d.text((106, 116), str(num), font=font('InterDisplay-Bold', 54), fill=PURE, anchor='mm')
    d.text((190, 92), title, font=font('InterDisplay-Bold', 42), fill=NAVY, anchor='lm')
    d.text((190, 148), sub, font=font('Inter-Medium', 31), fill=GREY, anchor='lm')
    return im
STEP1 = step_card(1, "Qada' setiap hari", "Taubat tidak menggugurkan qada'")
STEP2 = step_card(2, "Bayar fidyah lewat qada'", 'Berganda ikut bilangan Ramadan berlalu')
def scene_steps(f, t):
    s0, e = CUT[3], CUT[4] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Apa yang perlu dibuat?', H2, NAVY), W / 2, Y(80), a, s, b)
    ka, kb, ks = life(t, s0 + 0.9, e); place(f, STEP1, W / 2, Y(330), ka, ks, kb)
    ka, kb, ks = life(t, s0 + 2.6, e); place(f, STEP2, W / 2, Y(580), ka, ks, kb)
    ka, kb, _ = life(t, s0 + 5.0, e)
    place(f, sprite('Tak ingat berapa hari? Anggarkan sebaik mungkin.', font('Inter-Medium', 32), GREY), W / 2, Y(820), ka, 1, kb)
    place(f, sprite('Jika ragu, ambil jumlah yang lebih.', font('Inter-SemiBold', 32), BLUE), W / 2, Y(870), ka, 1, kb)

# ---------- 5. calculation ----------
def scene_calc(f, t):
    s0, e = CUT[4], CUT[5] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Contoh kiraan', font('InterDisplay-Bold', 58), NAVY), W / 2, Y(60), a, s, b)
    lay = layer(); d = ImageDraw.Draw(lay)
    cx0, cy0, cw, ch = W / 2 - 430, Y(150), 860, 640
    d.rounded_rectangle((cx0, cy0, cx0 + cw, cy0 + ch), 44, fill=PURE + (255,), outline=LINE + (255,), width=2)
    f.alpha_composite(alpha_img(lay, a))
    rows = [('Hari ditinggal', f'{DAYS} hari'), ('Kadar sehari', 'RM4'), (f'Ramadan berlalu ({Y0}–{Y1})', f'× {YEARS}')]
    for i, (k_, v_) in enumerate(rows):
        ka, kb, _ = life(t, s0 + 0.7 + i * 0.8, e)
        yy = cy0 + 90 + i * 100
        ks_ = sprite(k_, font('Inter-Medium', 36), GREY); vs = sprite(v_, font('InterDisplay-Bold', 46), GOLD if i == 2 else NAVY)
        place(f, ks_, cx0 + 70 + ks_.width / 2 - 40, yy, ka, 1, kb)
        place(f, vs, cx0 + cw - 70 - vs.width / 2 + 40, yy, ka, 1, kb)
    lay = layer(); d = ImageDraw.Draw(lay)
    k = ease_io((t - s0 - 3.0) / 0.6)
    if k > 0: d.line([(cx0 + 70, cy0 + 360), (cx0 + 70 + (cw - 140) * k, cy0 + 360)], fill=LINE + (255,), width=3)
    f.alpha_composite(alpha_img(lay, a))
    ka, kb, ks = life(t, s0 + 3.3, e)
    v = int(round(roll(0, DAYS * 4 * YEARS, t, s0 + 3.3, 1.6)))
    place(f, sprite(f'RM{v:,}', font('InterDisplay-Bold', 140), grad='green'), W / 2, cy0 + 470, ka, ks, kb)
    ka, kb, _ = life(t, s0 + 4.6, e)
    place(f, sprite("fidyah, selain qada' 10 hari", font('Inter-Medium', 32), GREY), W / 2, cy0 + 580, ka, 1, kb)
    ka, kb, ks = life(t, s0 + 5.8, e)
    place(f, sprite('Kalkulator kira gandaan secara automatik.', font('Inter-SemiBold', 36), BLUE), W / 2, Y(880), ka, ks, kb)

# ---------- 6. it reaches people in need ----------
PHOTOS = [S5.GAL[1], S5.GAL[2]]
def scene_agihan(f, t):
    s0, e = CUT[5], CUT[6] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Fidyah disalurkan kepada asnaf', font('Inter-SemiBold', 34), BLUE), W / 2, Y(30), a, 1, b)
    for i, (img, _) in enumerate(PHOTOS):
        st = s0 + 0.3 + i * 2.4
        if t < st - 0.05: continue
        k = ease_out((t - st) / 0.7)
        out = 1.0 if i == len(PHOTOS) - 1 else 1 - ease_io((t - st - 2.4) / 0.5)
        if out <= 0.01: continue
        im = img.rotate((-2.5, 2)[i] * (1 - 0.3 * k), resample=Image.BICUBIC, expand=True)
        place(f, im, W / 2 + (1 - k) * (240 if i else -240), Y(390), a * k * out, 0.97 + 0.03 * k, b)
        ca = clamp((t - st - 0.3) / 0.4) * out
        place(f, S5.CHIPS[i + 1], W / 2, Y(735), a * ca, 1, b)
    ka, kb, ks = life(t, s0 + 2.2, e)
    place(f, sprite('Tak pernah terlambat', font('InterDisplay-Bold', 66), NAVY), W / 2, Y(840), ka, ks, kb)
    ka, kb, ks = life(t, s0 + 2.8, e)
    place(f, sprite('untuk mula melunaskan.', font('InterDisplay-Bold', 66), grad='green'), W / 2, Y(920), ka, ks, kb)

# ---------- 7. outro ----------
def scene_outro(f, t):
    s0 = CUT[6]
    a, b, s = life(t, s0, DUR + 1)
    top_pill(f, a)
    for i, ln in enumerate(['Jangan Tangguh,', 'Puasa Hutang kita', 'Dengan Allah']):
        ka, kb, ks = life(t, s0 + 0.2 + i * 0.35, DUR + 1)
        place(f, sprite(ln, H2, grad='green' if i == 2 else None), W / 2, Y(220 + i * 100), ka, ks, kb)
    pa = life(t, s0 + 1.5, DUR + 1)[0]
    x = max(0, t - s0 - 1.5)
    sp = 1 - math.exp(-x * 9) * math.cos(x * 20) if t >= s0 + 1.5 else 0
    place(f, S5.PILL, W / 2, Y(620), pa, max(0.01, sp))
    la, lb, _ = life(t, s0 + 2.1, DUR + 1)
    place(f, sprite('Platform oleh', TINY, GREY), W / 2, Y(790), la, 1, lb)
    place(f, B.LOGO, W / 2, Y(880), la, 1, lb)

SCENES = [scene_hook, scene_years, scene_taubat, scene_steps, scene_calc, scene_agihan]
def render(t):
    f = B.bg(t)
    for i, sc in enumerate(SCENES):
        if CUT[i] - 0.1 <= t < CUT[i + 1] + 0.4: sc(f, t)
    if t >= CUT[6] - 0.1: scene_outro(f, t)
    out = f.convert('RGB')
    fade = clamp((t - (DUR - 0.8)) / 0.8)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H), (248, 251, 255)), fade)
    return out

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{B.SP}lqt_{B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    S5.build_audio(WAV, DUR, CUT[1:-1])
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-i', WAV, '-map', '0:v', '-map', '1:a', '-c:v', 'libx264', '-preset', 'medium', '-crf', '21',
                          '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '192k', '-shortest', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
