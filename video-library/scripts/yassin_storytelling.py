import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import subprocess, math, random
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import imageio_ffmpeg

BASE = LIB
VIDEO = OUT + 'syeikh_ahmad_yassin_30s.mp4'
W, H, FPS, DUR = 1920, 1080, 30, 30.0
STRETCH = 1.5  # story timeline authored at 20s, played back over 30s
N = int(FPS * DUR)

GREEN = (31, 77, 51)
GREEN_D = (18, 48, 31)
CREAM = (238, 226, 200)
INK = (46, 36, 24)
GOLD = (196, 160, 90)

F = '/usr/share/fonts/truetype/'
def font(name, size): return ImageFont.truetype(F + name, size)
TITLE = font('dejavu/DejaVuSerif-Bold.ttf', 92)
SUB = font('liberation/LiberationSerif-Italic.ttf', 46)
LABEL = font('dejavu/DejaVuSans-Bold.ttf', 26)
YEAR = font('dejavu/DejaVuSerif-Bold.ttf', 50)
BODY = font('liberation/LiberationSerif-Regular.ttf', 46)
QUOTE = font('liberation/LiberationSerif-Italic.ttf', 58)
SMALL = font('dejavu/DejaVuSans.ttf', 24)
TICK = font('dejavu/DejaVuSans-Bold.ttf', 20)

def clamp(x, a=0.0, b=1.0): return max(a, min(b, x))
def ease(x): x = clamp(x); return 1 - (1 - x) ** 3
def prog(t, start, dur): return ease((t - start) / dur)

# ---------- source images ----------
src1 = Image.open(ASSETS + 'images/1.jpg').convert('RGB')
src3 = Image.open(ASSETS + 'images/3.jpg').convert('RGB')
portrait = src1.crop((620, 270, 1080, 820))          # seated portrait
smile = src3.crop((0, 415, 1080, 1000))              # smiling photo

# ---------- static layers ----------
def parchment():
    rng = np.random.default_rng(7)
    base = np.ones((H, W, 3)) * np.array(CREAM)
    noise = rng.normal(0, 1, (H // 8, W // 8))
    noise = np.array(Image.fromarray(((noise + 3) * 40).clip(0, 255).astype('uint8')).resize((W, H), Image.BICUBIC)) / 255.0
    grain = rng.normal(0, 6, (H, W))
    base = base * (0.92 + 0.12 * noise[..., None]) + grain[..., None]
    yy, xx = np.mgrid[0:H, 0:W]
    d = np.sqrt(((xx - W / 2) / (W / 1.6)) ** 2 + ((yy - H / 2) / (H / 1.4)) ** 2)
    base = base * (1 - 0.35 * d[..., None] ** 2.2)
    return Image.fromarray(base.clip(0, 255).astype('uint8'))

PARCH = parchment()

def hgrad_mask(w, h, x0, x1, invert=False):
    x = np.linspace(0, 1, w)
    m = np.clip((np.arange(w) - x0) / max(1, (x1 - x0)), 0, 1)
    if invert: m = 1 - m
    return Image.fromarray((np.tile(m, (h, 1)) * 255).astype('uint8'))

def cover(img, w, h, zoom=1.0, fx=0.5, fy=0.5):
    s = max(w / img.width, h / img.height) * zoom
    nw, nh = int(img.width * s), int(img.height * s)
    r = img.resize((nw, nh), Image.BICUBIC)
    x = int((nw - w) * fx); y = int((nh - h) * fy)
    return r.crop((x, y, x + w, y + h))

def sepia(img, amt=0.35):
    g = img.convert('L')
    tone = Image.merge('RGB', [g.point(lambda v: min(255, v * 1.07)), g.point(lambda v: v * 0.95), g.point(lambda v: v * 0.78)])
    return Image.blend(img, tone, amt)

PORTRAIT_W = 820
PORT_MASK = hgrad_mask(PORTRAIT_W, H, 0, 260)

def text_layer(): return Image.new('RGBA', (W, H), (0, 0, 0, 0))

def draw_text_alpha(base, xy, text, fnt, fill, alpha, anchor='la'):
    if alpha <= 0: return
    lay = text_layer()
    ImageDraw.Draw(lay).text(xy, text, font=fnt, fill=fill + (int(255 * alpha),), anchor=anchor)
    base.alpha_composite(lay)

def wrap(text, fnt, maxw):
    words, lines, cur = text.split(), [], ''
    for w_ in words:
        t = (cur + ' ' + w_).strip()
        if fnt.getlength(t) <= maxw: cur = t
        else: lines.append(cur); cur = w_
    lines.append(cur)
    return lines

# ---------- timeline data ----------
YMIN, YMAX = 1938, 2004
SCENES = [
    # (start, end, chapter, [(badge, year_for_bar, text)], focus)
    (3.0, 6.0, 'BAB 1  ·  ASAL-USUL', [
        ('1938', 1938, 'Dilahirkan di kampung al-Jora, Majdal, wilayah Asqalan.'),
        ('1948', 1948, 'Bersama keluarga berhijrah ke Semenanjung Gaza.')]),
    (6.0, 9.0, 'BAB 2  ·  UJIAN & ILMU', [
        ('ZAMAN MUDA', 1952, 'Kemalangan ketika beriadah menyebabkan beliau lumpuh.'),
        ('1964', 1964, 'Melanjutkan pengajian di Universiti Ain Shams, Kaherah.')]),
    (9.0, 12.0, 'BAB 3  ·  GURU & PENDAKWAH', [
        ('1967', 1967, 'Dikenali di Gaza sebagai guru bahasa Arab dan khatib.'),
        ('1973', 1973, "Menubuhkan pertubuhan kebajikan Mujamma' al-Islami.")]),
    (12.0, 15.0, 'BAB 4  ·  GERAKAN & PENJARA', [
        ('1987', 1987, 'Menubuhkan HAMAS ketika tercetusnya Intifadah pertama.'),
        ('1989–1997', 1997, 'Dipenjarakan, kemudian dibebaskan pada tahun 1997.')]),
    (15.0, 17.2, 'BAB 5  ·  PENGAKHIRAN', [
        ('22 MAC 2004', 2004, 'Terbunuh dalam serangan udara Israel ketika pulang dari solat subuh.')]),
]
TRANS = 0.45

def bar_x(year): return 140 + (year - YMIN) / (YMAX - YMIN) * (1060 - 140)

def render_timeline(t, sc):
    start, end, chapter, items = sc
    lt = t - start
    img = PARCH.copy()
    # portrait on the right, slow push-in
    z = 1.0 + 0.06 * (lt / (end - start))
    p = sepia(cover(portrait, PORTRAIT_W, H, zoom=z, fx=0.5, fy=0.25), 0.25)
    img.paste(p, (W - PORTRAIT_W, 0), PORT_MASK)
    img = img.convert('RGBA')
    d = ImageDraw.Draw(img)

    # chapter label
    a = prog(lt, 0.1, 0.5)
    draw_text_alpha(img, (140 - 30 * (1 - a), 120), chapter, LABEL, GREEN, a)
    lay = text_layer(); ImageDraw.Draw(lay).rectangle((140, 162, 140 + int(380 * a), 165), fill=GOLD + (255,)); img.alpha_composite(lay)

    # entries
    y = 250
    for i, (badge, yr, text) in enumerate(items):
        s = 0.35 + i * 1.05
        a = prog(lt, s, 0.6)
        off = int(40 * (1 - a))
        lay = text_layer(); ld = ImageDraw.Draw(lay)
        bw = int(YEAR.getlength(badge)) + 60
        ld.rounded_rectangle((140, y + off, 140 + bw, y + 86 + off), radius=14, fill=GREEN + (int(255 * a),))
        ld.text((140 + bw / 2, y + 43 + off), badge, font=YEAR, fill=CREAM + (int(255 * a),), anchor='mm')
        ty = y + 110 + off
        for line in wrap(text, BODY, 900):
            ld.text((140, ty), line, font=BODY, fill=INK + (int(255 * a),))
            ty += 58
        img.alpha_composite(lay)
        y = ty + 50 - off

    # timeline bar
    by = 960
    lay = text_layer(); ld = ImageDraw.Draw(lay)
    ld.line((140, by, 1060, by), fill=INK + (90,), width=3)
    for yr in (1938, 1948, 1967, 1987, 2004):
        x = bar_x(yr)
        ld.ellipse((x - 5, by - 5, x + 5, by + 5), fill=INK + (140,))
        ld.text((x, by + 22), str(yr), font=TICK, fill=INK + (150,), anchor='mt')
    # progress marker travels between the scene's years
    y0, y1 = items[0][1], items[-1][1]
    cur = y0 + (y1 - y0) * prog(lt, 1.3, 0.8) if len(items) > 1 else y0
    xs = bar_x(YMIN)
    xc = bar_x(cur)
    ld.line((xs, by, xc, by), fill=GREEN + (255,), width=6)
    pulse = 12 + 3 * math.sin(lt * 6)
    ld.ellipse((xc - pulse, by - pulse, xc + pulse, by + pulse), fill=GREEN + (255,), outline=GOLD + (255,), width=3)
    img.alpha_composite(lay)
    return img.convert('RGB')

def render_title(t):
    img = Image.new('RGB', (W, H), GREEN_D)
    z = 1.0 + 0.08 * (t / 3.0)
    ph = cover(smile, 1150, H, zoom=z, fx=0.62, fy=0.35)
    img.paste(ph, (W - 1150, 0), hgrad_mask(1150, H, 0, 420))
    img = img.convert('RGBA')
    a1 = prog(t, 0.2, 0.8); a2 = prog(t, 0.8, 0.8); a3 = prog(t, 1.4, 0.8)
    draw_text_alpha(img, (130, 250 + 30 * (1 - a1)), 'SEBUAH KISAH', LABEL, GOLD, a1)
    draw_text_alpha(img, (130, 300 + 30 * (1 - a1)), 'SYEIKH', TITLE, CREAM, a1)
    draw_text_alpha(img, (130, 420 + 30 * (1 - a2)), 'AHMAD YASSIN', TITLE, CREAM, a2)
    lay = text_layer(); ImageDraw.Draw(lay).rectangle((130, 560, 130 + int(520 * a3), 564), fill=GOLD + (255,)); img.alpha_composite(lay)
    draw_text_alpha(img, (130, 590), '1938 – 2004', SUB, CREAM, a3)
    draw_text_alpha(img, (130, 650), 'Kronologi Hayat & Perjuangan', SUB, GOLD, a3)
    return img.convert('RGB')

def render_end(t):
    lt = t - 17.2
    img = Image.new('RGB', (W, H), GREEN_D)
    z = 1.1 - 0.06 * (lt / 2.8)
    ph = cover(smile, 1150, H, zoom=z, fx=0.62, fy=0.35).convert('L').convert('RGB')
    img.paste(ph, (W - 1150, 0), hgrad_mask(1150, H, 0, 420))
    img = img.convert('RGBA')
    a = prog(lt, 0.2, 0.9); b = prog(lt, 1.0, 0.7)
    draw_text_alpha(img, (130, 260), '“', font('liberation/LiberationSerif-Bold.ttf', 200), GOLD, a)
    y = 420
    for line in wrap('Perjuangan tidak akan mati selagi kebenaran terus dipertahankan.', QUOTE, 900):
        draw_text_alpha(img, (130, y + 20 * (1 - a)), line, QUOTE, CREAM, a); y += 76
    draw_text_alpha(img, (130, y + 30), '— Syeikh Ahmad Yassin  (1938 – 2004)', SUB, GOLD, b)
    out = img.convert('RGB')
    fade = clamp((t - 19.3) / 0.7)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H), (0, 0, 0)), fade)
    return out

def scene_frame(t):
    # returns list of (start, end, renderer) and blends at boundaries
    segs = [(0.0, 3.0, render_title)] + [(s[0], s[1], (lambda sc: (lambda tt: render_timeline(tt, sc)))(s)) for s in SCENES] + [(17.2, 20.0, render_end)]
    for i, (s, e, fn) in enumerate(segs):
        if s <= t < e or (i == len(segs) - 1 and t >= s):
            fr = fn(t)
            if i + 1 < len(segs) and t > e - TRANS:
                k = ease((t - (e - TRANS)) / TRANS)
                fr = Image.blend(fr, segs[i + 1][2](t), k)
            if i == 0 and t < 0.5:
                fr = Image.blend(Image.new('RGB', (W, H), (0, 0, 0)), fr, t / 0.5)
            return fr

if __name__ == '__main__':
    import sys
    if len(sys.argv) > 1:  # preview stills
        for ts in sys.argv[1:]:
            scene_frame(float(ts)).save(f'{OUT}still_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    proc = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                             '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', VIDEO],
                            stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for f in range(N):
        proc.stdin.write(scene_frame(f / FPS / STRETCH).tobytes())
    proc.stdin.close(); proc.wait()
    print('done', VIDEO)
