import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import subprocess, math, sys
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter, ImageEnhance
import imageio_ffmpeg

BASE = LIB
SP = OUT + ''
OUT = SP + 'yassin_apple_portrait_silent.mp4'
W, H, FPS, DUR = 1080, 1920, 30, 30.0
N = int(FPS * DUR)

FD = FONTS + 'extras/ttf/'
def font(n, s): return ImageFont.truetype(FD + n + '.ttf', s)
HERO = font('InterDisplay-Bold', 190)
YEAR = font('InterDisplay-Bold', 300)
BIG = font('InterDisplay-Bold', 150)
NAME = font('InterDisplay-Bold', 128)
MID = font('InterDisplay-SemiBold', 110)
CAP = font('Inter-Medium', 50)
SMALL = font('Inter-Medium', 40)
QUOTE = font('InterDisplay-SemiBold', 64)

WHITE = (245, 245, 247)
GREY = (134, 134, 139)
GOLD_A, GOLD_B = (255, 214, 150), (200, 150, 75)

def clamp(x, a=0.0, b=1.0): return max(a, min(b, x))
def ease_out(x): x = clamp(x); return 1 - (1 - x) ** 4
def ease_io(x): x = clamp(x); return 3 * x * x - 2 * x * x * x

# ---------- text sprites ----------
_cache = {}
def sprite(text, fnt, fill=WHITE, gradient=False):
    key = (text, id(fnt), fill, gradient)
    if key in _cache: return _cache[key]
    l, t, r, b = fnt.getbbox(text)
    pad = 40
    w, h = r - l + pad * 2, b - t + pad * 2
    mask = Image.new('L', (w, h), 0)
    ImageDraw.Draw(mask).text((pad - l, pad - t), text, font=fnt, fill=255)
    if gradient:
        x = np.linspace(0, 1, w)[None, :, None]; y = np.linspace(0, 1, h)[:, None, None]
        k = np.clip(0.65 * x + 0.35 * y, 0, 1)
        col = (np.array(GOLD_A) * (1 - k) + np.array(GOLD_B) * k).astype('uint8')
        img = Image.fromarray(np.broadcast_to(col, (h, w, 3)).copy()).convert('RGBA')
    else:
        img = Image.new('RGBA', (w, h), fill + (255,))
    img.putalpha(mask)
    _cache[key] = img
    return img

def place(frame, spr, cx, cy, alpha=1.0, scale=1.0, blur=0.0, anchor='c'):
    if alpha <= 0.01: return
    s = spr
    if abs(scale - 1) > 0.002:
        s = s.resize((max(1, int(s.width * scale)), max(1, int(s.height * scale))), Image.BICUBIC)
    if blur > 0.3: s = s.filter(ImageFilter.GaussianBlur(blur))
    if alpha < 1:
        a = s.getchannel('A').point(lambda v: int(v * alpha)); s = s.copy(); s.putalpha(a)
    x = int(cx - s.width / 2) if anchor == 'c' else int(cx - 40 * scale)
    frame.alpha_composite(s, (x, int(cy - s.height / 2)))

def life(t, s, e, fin=0.7, fout=0.5):
    """(alpha, blur, scale) for an element living in [s, e]."""
    a_in = ease_out((t - s) / fin); a_out = 1 - ease_io((t - (e - fout)) / fout)
    a = clamp(min(a_in, a_out))
    blur = 18 * (1 - a_in) + 14 * (1 - a_out)
    scale = 1.06 - 0.06 * a_in - 0.03 * (1 - a_out)
    return a, blur, scale

# ---------- photos ----------
src1 = Image.open(ASSETS + 'images/1.jpg').convert('RGB')
src3 = Image.open(ASSETS + 'images/3.jpg').convert('RGB')
smile = src3.crop((230, 415, 790, 1000))     # 560x585
seated = src1.crop((620, 270, 1080, 820))    # 460x550

def vfade_mask(w, h, top=0.18, bottom=0.35):
    y = np.linspace(0, 1, h)
    m = np.minimum(np.clip(y / top, 0, 1), np.clip((1 - y) / bottom, 0, 1))
    m = m ** 1.4
    x = np.linspace(0, 1, w)
    mx = np.clip(np.minimum(x / 0.08, (1 - x) / 0.08), 0, 1)
    return Image.fromarray((np.outer(m, mx) * 255).astype('uint8'))

def photo(frame, img, t, s, e, cy, height, gray=False, z0=1.12, z1=1.0):
    a, _, _ = life(t, s, e, fin=1.2, fout=0.6)
    if a <= 0.01: return
    p = ease_out((t - s) / (e - s))
    z = z0 + (z1 - z0) * p
    w = W
    sc = max(w / img.width, height / img.height) * z
    r = img.resize((int(img.width * sc), int(img.height * sc)), Image.BICUBIC)
    x0 = (r.width - w) // 2; y0 = int((r.height - height) * 0.3)
    r = r.crop((x0, y0, x0 + w, y0 + height))
    if gray: r = r.convert('L').convert('RGB')
    r = ImageEnhance.Brightness(r).enhance(0.35 + 0.65 * a)
    r = r.filter(ImageFilter.UnsharpMask(2, 60, 2))
    m = vfade_mask(w, height).point(lambda v: int(v * a))
    rr = r.convert('RGBA'); rr.putalpha(m)
    frame.alpha_composite(rr, (0, int(cy - height / 2)))

# ---------- background ----------
yy, xx = np.mgrid[0:H, 0:W]
glow = np.exp(-(((xx - W / 2) / 700.0) ** 2 + ((yy - H * 0.45) / 900.0) ** 2))
BG = Image.fromarray(np.stack([glow * 10, glow * 16, glow * 12], -1).astype('uint8')).convert('RGBA')

# persistent portrait background (dimmed, slow Ken Burns across the whole video)
_bgsrc = src1.crop((705, 255, 1022, 818))                     # 317x563 ~ 9:16, above the green footer
_bgbig = _bgsrc.resize((int(W * 1.15), int(H * 1.15)), Image.LANCZOS).filter(ImageFilter.GaussianBlur(3))
_bgbig = ImageEnhance.Color(_bgbig).enhance(0.45)
_bgbig = ImageEnhance.Brightness(_bgbig).enhance(0.42)
_ym = np.linspace(0, 1, H)[:, None]
_band = 0.25 + 0.55 * np.exp(-((_ym - 0.52) / 0.2) ** 2)        # extra dark behind the text band
_band = np.maximum(_band, 0.55 * np.clip((_ym - 0.8) / 0.2, 0, 1))
SHADE = Image.fromarray(np.dstack([np.zeros((H, W, 3)), np.broadcast_to(_band * 255, (H, W))]).astype('uint8'), 'RGBA')

def bg_frame(t):
    p = t / DUR
    z = 1.0 + 0.13 * (1 - p)                                   # slow pull-back
    w, h = int(W * z), int(H * z)
    x0 = int((_bgbig.width - w) * 0.5); y0 = int((_bgbig.height - h) * (0.2 + 0.4 * p))
    im = _bgbig.crop((x0, y0, x0 + w, y0 + h)).resize((W, H), Image.BILINEAR).convert('RGBA')
    im.alpha_composite(SHADE)
    return im

def year_counter(frame, t, s, e, y0, y1, roll_at, roll_dur, cap0, cap1, cap2=None, cap2_at=None):
    a, blur, sc = life(t, s, e)
    k = ease_io((t - roll_at) / roll_dur)
    yr = int(round(y0 + (y1 - y0) * k))
    # slight motion blur while rolling
    rb = 6 * math.sin(math.pi * k) if 0 < k < 1 else 0
    place(frame, sprite(str(yr), YEAR, gradient=True), W / 2, 860, a, sc, blur + rb)
    c0a = a * (1 - ease_io((t - roll_at) / 0.35))
    c1a = a * ease_out((t - roll_at - roll_dur + 0.1) / 0.6)
    place(frame, sprite(cap0, CAP, GREY), W / 2, 1080, c0a, 1.0, 10 * (1 - c0a / max(a, 1e-3)))
    place(frame, sprite(cap1, CAP, WHITE), W / 2, 1080, c1a, 1.0, 10 * (1 - ease_out((t - roll_at - roll_dur + 0.1) / 0.6)))
    if cap2:
        c2 = a * ease_out((t - cap2_at) / 0.6)
        place(frame, sprite(cap2, CAP, GREY), W / 2, 1160, c2, 1.0, 10 * (1 - ease_out((t - cap2_at) / 0.6)))

def render(t):
    f = bg_frame(t)
    # dim the background further while a hero photo is on screen
    if 3.0 <= t < 6.8 or t >= 26.0:
        k = life(t, 3.0, 6.5, 1.2, 0.6)[0] if t < 6.8 else life(t, 26.0, 31.0, 1.2, 0.6)[0]
        f = Image.blend(f, Image.new('RGBA', (W, H), (0, 0, 0, 255)), 0.6 * k)
    # 1. opener
    if t < 3.2:
        a, b, s = life(t, 0.3, 3.0)
        place(f, sprite('Satu kisah.', HERO), W / 2, 900, a, s, b)
        a2, b2, _ = life(t, 1.2, 3.0)
        place(f, sprite('1938 — 2004', CAP, GREY), W / 2, 1060, a2, 1, b2)
    # 2. photo reveal + name
    if 3.0 <= t < 6.8:
        photo(f, smile, t, 3.0, 6.5, 780, 1300)
        a, b, s = life(t, 3.9, 6.5)
        place(f, sprite('Syeikh', CAP, GREY), W / 2, 1430, a, 1, b)
        place(f, sprite('Ahmad Yassin.', NAME, gradient=True), W / 2, 1560, a, s, b)
    # 3. 1938 -> 1948
    if 6.5 <= t < 11.8:
        year_counter(f, t, 6.6, 11.5, 1938, 1948, 8.6, 0.9,
                     'Lahir di al-Jora, Asqalan.', 'Berhijrah ke Semenanjung Gaza.')
    # 4. Lumpuh.
    if 11.5 <= t < 14.8:
        a, b, s = life(t, 11.6, 14.5)
        place(f, sprite('Lumpuh.', HERO), W / 2, 880, a, s, b)
        a2, b2, _ = life(t, 12.3, 14.5)
        place(f, sprite('Sejak usia muda, akibat kemalangan.', CAP, GREY), W / 2, 1050, a2, 1, b2)
        a3, b3, _ = life(t, 13.0, 14.5)
        place(f, sprite('Namun terus belajar dan mengajar.', CAP, WHITE), W / 2, 1125, a3, 1, b3)
    # 5. Ilmu. Dakwah. Kebajikan.
    if 14.5 <= t < 19.3:
        rows = [('Ilmu.', 'Universiti Ain Shams, Kaherah · 1964', 14.7),
                ('Dakwah.', 'Guru bahasa Arab & khatib · 1967', 15.7),
                ('Kebajikan.', "Mujamma' al-Islami · 1973", 16.7)]
        for i, (word, cap, st) in enumerate(rows):
            a, b, s = life(t, st, 19.0)
            y = 640 + i * 330
            place(f, sprite(word, BIG, gradient=(i == 2)), 110, y, a, s, b, anchor='l')
            place(f, sprite(cap, SMALL, GREY), 110, y + 110, a, 1, b, anchor='l')
    # 6. 1973 -> 1987
    if 19.0 <= t < 23.2:
        year_counter(f, t, 19.1, 23.0, 1973, 1987, 20.1, 0.9,
                     'Pertubuhan kebajikan.', 'Menubuhkan HAMAS.', 'Dipenjara 1989 – 1997.', 21.7)
    # 7. 22.03.2004
    if 23.0 <= t < 26.3:
        a, b, s = life(t, 23.1, 26.0, fin=0.9)
        place(f, sprite('22.03.2004', MID, WHITE), W / 2, 880, a, s, b)
        a2, b2, _ = life(t, 23.9, 26.0)
        place(f, sprite('Terbunuh dalam serangan udara Israel,', CAP, GREY), W / 2, 1030, a2, 1, b2)
        place(f, sprite('selepas solat subuh.', CAP, GREY), W / 2, 1100, a2, 1, b2)
    # 8. closing
    if t >= 26.0:
        photo(f, seated, t, 26.0, 31.0, 700, 1250, gray=True, z0=1.0, z1=1.08)
        a, b, _ = life(t, 26.8, 31.0, fin=0.9)
        place(f, sprite('“Perjuangan tidak akan mati', QUOTE), W / 2, 1330, a, 1, b)
        place(f, sprite('selagi kebenaran terus', QUOTE), W / 2, 1415, a, 1, b)
        place(f, sprite('dipertahankan.”', QUOTE), W / 2, 1500, a, 1, b)
        a2, b2, _ = life(t, 27.8, 31.0)
        place(f, sprite('Syeikh Ahmad Yassin · 1938 — 2004', SMALL, GREY), W / 2, 1640, a2, 1, b2)
    out = f.convert('RGB')
    fade = clamp((t - 29.2) / 0.8)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H)), fade)
    return out

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{SP}ap_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done')
