"""Siri Fidyah 3/5: Ibu hamil & menyusu (Vox explainer style, ~55s). FMT=916 (1080x1920) or FMT=45 (1080x1350).
Usage: FMT=916 python3 fidyah_siri3_ibu_hamil.py [preview seconds ...]"""
"""Bayar Fidyah Online — Vox-style explainer (paper, cut-outs, highlighter, marker), 1920x1080, 40s."""
import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import subprocess, math, sys, random
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import imageio_ffmpeg

SP = OUT
IMGDIR = ASSETS + 'images/'
FMT = os.environ.get('FMT', '916')                 # '916' = 1080x1920 Reels/Stories, '45' = 1080x1350 Feed
W, H = (1080, 1920) if FMT == '916' else (1080, 1350)
FPS, DUR = 30, 55.0
BY = 260 if FMT == '916' else 175                 # 1080x1000 safe box (inside Meta's Reels safe zone for 9:16)
OUT = SP + f'siri3_ibu_hamil_{FMT}_silent.mp4'
N = int(FPS * DUR)

FI = FONTS + 'extras/ttf/'
def font(n, s): return ImageFont.truetype(FI + n + '.ttf', s)
BLACK = lambda s: font('InterDisplay-Black', s)
BOLD = lambda s: font('InterDisplay-Bold', s)
MED = lambda s: font('Inter-Medium', s)
SEMI = lambda s: font('Inter-SemiBold', s)
MONO = lambda s: ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf', s)

PAPER = (250, 252, 255)          # light blue-white paper (brand), not cream
INK = (12, 26, 48)                # navy ink
GREY = (92, 88, 82)
YELLOW = (255, 222, 60)
RED = (214, 48, 38)
BLUE = (6, 107, 227)

def clamp(x, a=0.0, b=1.0): return max(a, min(b, x))
def ease_out(x): x = clamp(x); return 1 - (1 - x) ** 3
def back_out(x):
    x = clamp(x); c = 1.7
    return 1 + (c + 1) * (x - 1) ** 3 + c * (x - 1) ** 2
def q(t, fps=12): return math.floor(t * fps) / fps           # stop-motion time (animate "on twos")

def boil(seed, t, amp=2.0):
    """small per-element jitter that changes 8x per second — the hand-made Vox feel"""
    r = random.Random(seed * 1000 + math.floor(t * 8))
    return r.uniform(-amp, amp), r.uniform(-amp, amp)

# ---------- paper, vignette and grain ----------
rng = np.random.default_rng(5)
def make_paper():
    base = np.ones((H, W, 3)) * np.array(PAPER, float)
    low = rng.normal(0, 1, (H // 6, W // 6))
    low = np.array(Image.fromarray(((low + 3) * 40).clip(0, 255).astype('uint8')).resize((W, H), Image.BICUBIC)) / 255.0
    base *= (0.95 + 0.07 * low[..., None])
    fib = Image.new('L', (W, H), 0); d = ImageDraw.Draw(fib)
    for _ in range(900):
        x, y = rng.uniform(0, W), rng.uniform(0, H); a = rng.uniform(0, math.pi); l = rng.uniform(8, 40)
        d.line((x, y, x + l * math.cos(a), y + l * math.sin(a)), fill=int(rng.uniform(10, 35)), width=1)
    base -= np.array(fib)[..., None] * 0.22
    yy, xx = np.mgrid[0:H, 0:W]
    v = ((xx - W / 2) / (W * 0.62)) ** 2 + ((yy - H / 2) / (H * 0.62)) ** 2
    base *= (1 - 0.10 * v[..., None] ** 1.3)
    return Image.fromarray(base.clip(0, 255).astype('uint8')).convert('RGBA')
PAPER_IMG = make_paper()
GRAIN = [rng.normal(0, 3.5, (H // 2, W // 2)).astype('float32') for _ in range(4)]   # lighter grain keeps files small

def layer(): return Image.new('RGBA', (W, H), (0, 0, 0, 0))
def with_alpha(img, a):
    if a >= 0.999: return img
    img = img.copy(); img.putalpha(img.getchannel('A').point(lambda v: int(v * a))); return img

# ---------- cut-out helpers ----------
def jagged_poly(w, h, pad, seed, jag=3.5):
    r = random.Random(seed); pts = []
    def edge(x0, y0, x1, y1, n):
        for i in range(n):
            k = i / n
            pts.append((x0 + (x1 - x0) * k + r.uniform(-jag, jag), y0 + (y1 - y0) * k + r.uniform(-jag, jag)))
    edge(pad, pad, w - pad, pad, 30); edge(w - pad, pad, w - pad, h - pad, 20)
    edge(w - pad, h - pad, pad, h - pad, 30); edge(pad, h - pad, pad, pad, 20)
    return pts

def cutout(img, border=14, angle=0.0, seed=1, shadow=True, tape=True, paper=(252, 250, 245)):
    """white-bordered cut-out with torn edge, drop shadow and a strip of tape"""
    img = img.convert('RGBA')
    pad = 30
    w, h = img.width + 2 * border + 2 * pad, img.height + 2 * border + 2 * pad
    card = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    m = Image.new('L', (w, h), 0); ImageDraw.Draw(m).polygon(jagged_poly(w, h, pad, seed), fill=255)
    face = Image.new('RGBA', (w, h), paper + (255,)); face.alpha_composite(img, (pad + border, pad + border))
    face.putalpha(m)
    if shadow:
        sh = Image.new('RGBA', (w, h), (30, 25, 20, 0)); sh.putalpha(m.point(lambda v: int(v * 0.42)))
        sh = sh.filter(ImageFilter.GaussianBlur(9))
        card.alpha_composite(sh, (0, 0))
        card = card.transform(card.size, Image.AFFINE, (1, 0, -7, 0, 1, -11))
    card.alpha_composite(face)
    if tape:
        r = random.Random(seed)
        tw, th = int(min(170, w * 0.4)), 44
        tp = Image.new('RGBA', (tw, th), (236, 226, 196, 170))
        td = ImageDraw.Draw(tp)
        for x in range(0, tw, 5): td.line((x, 0, x, 3), fill=(0, 0, 0, 0)); td.line((x + 2, th - 3, x + 2, th), fill=(0, 0, 0, 0))
        tp = tp.rotate(r.uniform(-8, 8), expand=True, resample=Image.BICUBIC)
        card.alpha_composite(tp, (int(w / 2 - tp.width / 2 + r.uniform(-40, 40)), pad - 18))
    return card.rotate(angle, expand=True, resample=Image.BICUBIC)

def place(frame, img, cx, cy, a=1.0, scale=1.0, seed=None, t=0.0, boil_amp=1.6):
    if a <= 0.01: return
    if abs(scale - 1) > 0.003:
        img = img.resize((max(1, int(img.width * scale)), max(1, int(img.height * scale))), Image.BICUBIC)
    dx, dy = boil(seed, t, boil_amp) if seed is not None else (0, 0)
    frame.alpha_composite(with_alpha(img, a), (int(cx - img.width / 2 + dx), int(cy - img.height / 2 + dy)))

_tc = {}
def text_img(text, fnt, fill=INK):
    k = (text, id(fnt), fill)
    if k not in _tc:
        l, t_, r, b = fnt.getbbox(text)
        im = Image.new('RGBA', (r - l + 20, b - t_ + 20), (0, 0, 0, 0))
        ImageDraw.Draw(im).text((10 - l, 10 - t_), text, font=fnt, fill=fill + (255,))
        _tc[k] = im
    return _tc[k]

def text_at(frame, text, fnt, x, y, fill=INK, a=1.0, anchor='l', seed=None, t=0.0):
    im = text_img(text, fnt, fill)
    cx = x + im.width / 2 - 10 if anchor == 'l' else x
    place(frame, im, cx, y, a, 1.0, seed, t, 1.2)

def highlight(frame, fnt, x, y, text, sub, prog, color=YELLOW, seed=0, anchor='l'):
    """highlighter swipe behind `sub` inside `text` drawn at (x, y) with left or centre anchor"""
    if prog <= 0: return
    full = fnt.getlength(text)
    x0 = x if anchor == 'l' else x - full / 2
    i = text.index(sub)
    sx = x0 + fnt.getlength(text[:i]) - 10
    sw = fnt.getlength(sub) + 20
    asc, desc = fnt.getmetrics()
    hh = (asc + desc) * 0.62
    r = random.Random(seed)
    lay = layer(); d = ImageDraw.Draw(lay)
    ww = sw * prog
    pts = [(sx + r.uniform(-4, 4), y - hh / 2 + r.uniform(-3, 3)), (sx + ww, y - hh / 2 + r.uniform(-4, 2)),
           (sx + ww + r.uniform(-2, 6), y + hh / 2 + r.uniform(-2, 4)), (sx + r.uniform(-6, 2), y + hh / 2 + r.uniform(-2, 3))]
    d.polygon(pts, fill=color + (215,))
    frame.alpha_composite(lay)

def marker_ellipse(frame, cx, cy, rx, ry, prog, seed=0, width=7, color=RED):
    if prog <= 0: return
    r = random.Random(seed)
    lay = layer(); d = ImageDraw.Draw(lay)
    total = 2 * math.pi * 1.12 * prog
    st = r.uniform(-2.4, -1.8)
    pts = []
    n = max(2, int(80 * prog))
    for i in range(n + 1):
        a = st + total * i / n
        wob = 1 + 0.04 * math.sin(a * 3 + seed) + 0.03 * (i / n)
        pts.append((cx + rx * wob * math.cos(a), cy + ry * wob * math.sin(a)))
    d.line(pts, fill=color + (235,), width=width, joint='curve')
    frame.alpha_composite(lay)

def marker_line(frame, x0, y0, x1, y1, prog, seed=0, width=7, color=RED):
    if prog <= 0: return
    r = random.Random(seed)
    lay = layer(); d = ImageDraw.Draw(lay)
    n = 20; pts = []
    for i in range(int(n * prog) + 1):
        k = i / n
        pts.append((x0 + (x1 - x0) * k, y0 + (y1 - y0) * k + math.sin(k * 6 + seed) * 3 + r.uniform(-1, 1)))
    if len(pts) > 1: d.line(pts, fill=color + (235,), width=width, joint='curve')
    frame.alpha_composite(lay)

def label(frame, text, x, y, prog, fnt=None, seed=0, t=0.0):
    """Vox chapter tag: black box with white caps"""
    if prog <= 0: return
    fnt = fnt or BLACK(34)
    tw = fnt.getlength(text) + 44
    lay = layer(); d = ImageDraw.Draw(lay)
    dx, dy = boil(seed, t, 1.0)
    w = tw * ease_out(prog)
    d.rectangle((x + dx, y - 30 + dy, x + w + dx, y + 30 + dy), fill=INK + (255,))
    frame.alpha_composite(lay)
    if prog > 0.5:
        d2 = ImageDraw.Draw(frame)
        d2.text((x + 22 + dx, y + dy), text, font=fnt, fill=PAPER + (255,), anchor='lm')

def life(t, s, e, fin=0.35, fout=0.25):
    return clamp(min((t - s) / fin, 1 - (t - (e - fout)) / fout))

# ---------- assets ----------
def load_sq(path, size):
    im = Image.open(path).convert('RGB')
    s = min(im.width, im.height); im = im.crop(((im.width - s) // 2, (im.height - s) // 2, (im.width + s) // 2, (im.height + s) // 2))
    return im.resize((size, size), Image.LANCZOS)

def fit(path, w, h, fy=0.4):
    im = Image.open(path).convert('RGB')
    s = max(w / im.width, h / im.height)
    im = im.resize((int(im.width * s + 1), int(im.height * s + 1)), Image.LANCZOS)
    x = (im.width - w) // 2; y = int((im.height - h) * fy)
    return im.crop((x, y, x + w, y + h))


# ---------- Siri Fidyah 3/5: Ibu hamil & menyusu (Vox style, 1080x1000 safe box) ----------
def Y(v): return BY + v
CX = W / 2
CUTS = [0, 4.2, 10.0, 16.0, 34.0, 44.0, 49.5]

def centered_label(f, text, y, prog, seed, t):
    fnt = BLACK(34)
    label(f, text, CX - (fnt.getlength(text) + 44) / 2, y, prog, fnt, seed, t)

def note(lines, w, h, seed, angle):
    """white paper note with stacked (text, font, colour) lines, cut out and taped"""
    card = Image.new('RGBA', (w, h), (252, 250, 245, 255)); d = ImageDraw.Draw(card)
    total = sum(fn.size * 1.22 for _, fn, _ in lines)
    y = (h - total) / 2
    for txt, fn, col in lines:
        d.text((w / 2, y + fn.size * 0.61), txt, font=fn, fill=col, anchor='mm'); y += fn.size * 1.22
    return cutout(card, border=0, angle=angle, seed=seed)

HAMIL = cutout(load_sq(IMGDIR + '5.webp', 420), border=14, angle=-4, seed=301)
SUSU = cutout(load_sq(IMGDIR + '6.webp', 420), border=14, angle=3, seed=302)
TOP = note([('Ibu hamil / menyusu', BLACK(44), INK), ('tidak berpuasa', MED(34), GREY)], 640, 150, 303, -1.5)
LEFT = note([('Bimbang', MED(32), GREY), ('DIRI SENDIRI', BLACK(42), INK)], 420, 170, 304, 2.0)
RIGHT = note([('Bimbang keselamatan', MED(30), GREY), ('ANAK', BLACK(48), INK)], 420, 170, 305, -2.0)
TAG_SELF = note([('Diri sendiri?', BLACK(40), INK)], 330, 100, 306, -3)
TAG_KID = note([('Anak?', BLACK(40), INK)], 250, 100, 307, 3)
logo = Image.open(ASSETS + 'fid/logo.png').convert('RGBA'); logo.thumbnail((320, 110), Image.LANCZOS)

def drop(f, img, cx, cy, tq, st, a, seed, t, dist=200):
    k = back_out((tq - st) / 0.35)
    if k <= 0: return
    place(f, img, cx, cy - dist * (1 - k), a * clamp((tq - st) / 0.1), 1.0, seed, t)

def s_hook(f, t, tq):
    a = life(t, 0.05, 4.4)
    drop(f, HAMIL, CX, Y(300), tq, 0.1, a, 10, t)
    text_at(f, 'Tinggal puasa sebab', BLACK(68), CX, Y(640), INK, a * clamp((tq - 0.6) / 0.2), anchor='c', seed=11, t=t)
    text_at(f, 'mengandung.', BLACK(68), CX, Y(720), INK, a * clamp((tq - 0.8) / 0.2), anchor='c', seed=12, t=t)
    highlight(f, BLACK(80), CX, Y(860), 'Kena bayar fidyah tak?', 'fidyah', ease_out((tq - 1.9) / 0.4) * a, seed=13, anchor='c')
    text_at(f, 'Kena bayar fidyah tak?', BLACK(80), CX, Y(854), INK, a * clamp((tq - 1.4) / 0.2), anchor='c', seed=14, t=t)

def s_title(f, t, tq):
    a = life(t, 4.2, 10.2)
    centered_label(f, 'SIRI FIDYAH · 3/5', Y(60), clamp((tq - 4.3) / 0.3) * a, 20, t)
    for i, (txt, st) in enumerate((("Qada'?", 4.6), ('Fidyah?', 5.2), ('Atau dua-dua?', 5.8))):
        fn = BLACK(118)
        if i == 2: highlight(f, fn, CX, Y(500 + 6), txt, 'dua-dua', ease_out((tq - 6.6) / 0.4) * a, seed=21, anchor='c')
        s = 1 + 0.3 * (1 - back_out((tq - st) / 0.3))
        if tq >= st: place(f, text_img(txt, fn), CX, Y(260 + i * 120), a, s, 22 + i, t)
    text_at(f, 'Jawapannya bergantung pada', MED(40), CX, Y(700), GREY, a * clamp((tq - 7.6) / 0.2), anchor='c', seed=25, t=t)
    text_at(f, 'satu soalan...', MED(40), CX, Y(752), GREY, a * clamp((tq - 7.8) / 0.2), anchor='c', seed=26, t=t)

def s_question(f, t, tq):
    a = life(t, 10.0, 16.2)
    label(f, '01  KENAPA TAK PUASA?', 60, Y(40), clamp((tq - 10.1) / 0.3) * a, seed=30, t=t)
    text_at(f, 'Siapa yang anda', BLACK(88), CX, Y(300), INK, a * clamp((tq - 10.4) / 0.2), anchor='c', seed=31, t=t)
    text_at(f, 'bimbangkan?', BLACK(118), CX, Y(420), INK, a * clamp((tq - 10.8) / 0.2), anchor='c', seed=32, t=t)
    marker_line(f, CX - 330, Y(495), CX + 330, Y(492), ease_out((tq - 11.6) / 0.5) * a, seed=33)
    drop(f, TAG_SELF, CX - 220, Y(700), tq, 12.6, a, 34, t, 120)
    drop(f, TAG_KID, CX + 230, Y(700), tq, 13.4, a, 35, t, 120)
    marker_ellipse(f, CX - 220, Y(700), 200, 76, ease_out((tq - 13.0) / 0.4) * a, seed=36, width=6)
    marker_ellipse(f, CX + 230, Y(700), 160, 76, ease_out((tq - 13.8) / 0.4) * a, seed=37, width=6)

def s_tree(f, t, tq):
    a = life(t, 16.0, 34.2)
    label(f, '02  CARTA PILIHAN', 60, Y(40), clamp((tq - 16.1) / 0.3) * a, seed=40, t=t)
    drop(f, TOP, CX, Y(190), tq, 16.3, a, 41, t, 120)
    LX, RX = 280, 800
    # left branch: worried about herself -> qada' only
    marker_line(f, CX - 60, Y(275), LX, Y(385), ease_out((tq - 17.4) / 0.5) * a, seed=42)
    drop(f, LEFT, LX, Y(480), tq, 18.0, a, 43, t, 100)
    marker_line(f, LX, Y(580), LX, Y(690), ease_out((tq - 19.8) / 0.4) * a, seed=44)
    highlight(f, BLACK(54), LX, Y(770 + 6), "Qada' sahaja", "Qada' sahaja", ease_out((tq - 20.8) / 0.4) * a, seed=45, anchor='c')
    text_at(f, "Qada' sahaja", BLACK(54), LX, Y(770), INK, a * clamp((tq - 20.4) / 0.2), anchor='c', seed=46, t=t)
    text_at(f, 'tiada fidyah', MED(32), LX, Y(842), GREY, a * clamp((tq - 21.6) / 0.2), anchor='c', seed=47, t=t)
    # right branch: worried about the baby -> qada' + fidyah
    marker_line(f, CX + 60, Y(275), RX, Y(385), ease_out((tq - 25.0) / 0.5) * a, seed=48)
    drop(f, RIGHT, RX, Y(480), tq, 25.6, a, 49, t, 100)
    marker_line(f, RX, Y(580), RX, Y(690), ease_out((tq - 27.4) / 0.4) * a, seed=50)
    highlight(f, BLACK(54), RX, Y(770 + 6), "Qada' + fidyah", "Qada' + fidyah", ease_out((tq - 28.4) / 0.4) * a, seed=51, anchor='c')
    text_at(f, "Qada' + fidyah", BLACK(54), RX, Y(770), INK, a * clamp((tq - 28.0) / 0.2), anchor='c', seed=52, t=t)
    marker_ellipse(f, RX, Y(775), 240, 66, ease_out((tq - 29.4) / 0.5) * a, seed=53)
    text_at(f, 'secupak beras (RM4) sehari', MED(30), RX, Y(870), GREY, a * clamp((tq - 30.4) / 0.2), anchor='c', seed=54, t=t)

def s_example(f, t, tq):
    a = life(t, 34.0, 44.2)
    label(f, '03  CONTOH', 60, Y(40), clamp((tq - 34.1) / 0.3) * a, seed=60, t=t)
    rc_a = a * clamp((tq - 34.4) / 0.15)
    if rc_a > 0:
        k = back_out((tq - 34.4) / 0.35)
        rw, rh = 660, 520
        rc = Image.new('RGBA', (rw, rh), (252, 250, 245, 255)); d = ImageDraw.Draw(rc)
        mono = MONO(30)
        lines = [('IBU HAMIL · BIMBANG ANAK', 34.8), ('', 0), ('Hari ditinggalkan ..... 10', 35.4), ("Qada' .......... 10 hari", 36.0),
                 ('Fidyah ...... 10 x RM4.00', 36.6), ('-' * 25, 37.1), ('JUMLAH FIDYAH ... RM40.00', 37.5)]
        for i, (ln, st) in enumerate(lines):
            if not ln: continue
            n = int(len(ln) * clamp((tq - st) / 0.35))
            d.text((40, 44 + i * 64), ln[:n], font=mono, fill=INK)
        card = cutout(rc, border=0, angle=-1.5, seed=61)
        cy = Y(420) - 180 * (1 - k)
        place(f, card, CX, cy, rc_a, 1.0, 62, t)
        marker_ellipse(f, CX + 150, cy + 168, 160, 46, ease_out((tq - 38.4) / 0.5) * a, seed=63)
    ha = a * clamp((tq - 39.4) / 0.2)
    highlight(f, BLACK(64), CX, Y(820 + 6), "Qada' 10 hari + RM40", 'RM40', ease_out((tq - 40.0) / 0.4) * a, seed=64, anchor='c')
    text_at(f, "Qada' 10 hari + RM40", BLACK(64), CX, Y(820), INK, ha, anchor='c', seed=65, t=t)
    text_at(f, "Lewat qada' hingga Ramadan berikutnya? Fidyah lewat turut dikenakan.", MED(25), CX, Y(910), GREY,
            a * clamp((tq - 41.4) / 0.2), anchor='c', seed=66, t=t)

def s_susu(f, t, tq):
    a = life(t, 44.0, 49.7)
    label(f, '04  IBU MENYUSU', 60, Y(40), clamp((tq - 44.1) / 0.3) * a, seed=70, t=t)
    drop(f, SUSU, CX, Y(370), tq, 44.3, a, 71, t)
    highlight(f, BLACK(84), CX, Y(700 + 6), 'Hukumnya sama.', 'sama', ease_out((tq - 45.6) / 0.4) * a, seed=72, anchor='c')
    text_at(f, 'Hukumnya sama.', BLACK(84), CX, Y(700), INK, a * clamp((tq - 45.0) / 0.2), anchor='c', seed=73, t=t)
    text_at(f, "Bimbang diri: qada' sahaja.", MED(36), CX, Y(810), GREY, a * clamp((tq - 46.4) / 0.2), anchor='c', seed=74, t=t)
    text_at(f, "Bimbang anak: qada' + fidyah.", MED(36), CX, Y(860), GREY, a * clamp((tq - 46.8) / 0.2), anchor='c', seed=75, t=t)

def s_outro(f, t, tq):
    a = life(t, 49.5, 56.0)
    centered_label(f, 'SIRI FIDYAH · 3/5', Y(60), clamp((tq - 49.6) / 0.3) * a, 80, t)
    for i, ln in enumerate(['Jangan Tangguh,', 'Puasa Hutang kita', 'Dengan Allah']):
        st = 49.9 + i * 0.35
        if i == 2: highlight(f, BLACK(80), CX, Y(420 + 6), ln, ln, ease_out((tq - 51.2) / 0.4) * a, seed=81, anchor='c')
        text_at(f, ln, BLACK(80), CX, Y(220 + i * 100), INK, a * clamp((tq - st) / 0.2), anchor='c', seed=82 + i, t=t)
    highlight(f, BLACK(56), CX, Y(600 + 6), 'www.bayarfidyahonline.com', 'www.bayarfidyahonline.com', ease_out((tq - 52.0) / 0.5) * a, seed=86, anchor='c')
    text_at(f, 'www.bayarfidyahonline.com', BLACK(56), CX, Y(600), INK, a * clamp((tq - 51.8) / 0.2), anchor='c', seed=87, t=t)
    text_at(f, 'Platform oleh', MED(28), CX, Y(760), GREY, a * clamp((tq - 52.6) / 0.2), anchor='c', seed=88, t=t)
    place(f, logo, CX, Y(840), a * clamp((tq - 52.6) / 0.2), 1.0, 89, t, 1.0)

def render(t):
    tq = q(t)
    f = PAPER_IMG.copy()
    if t < 4.6: s_hook(f, t, tq)
    if 4.0 <= t < 10.4: s_title(f, t, tq)
    if 9.8 <= t < 16.4: s_question(f, t, tq)
    if 15.8 <= t < 34.4: s_tree(f, t, tq)
    if 33.8 <= t < 44.4: s_example(f, t, tq)
    if 43.8 <= t < 49.9: s_susu(f, t, tq)
    if t >= 49.3: s_outro(f, t, tq)
    c0 = max(c for c in CUTS if c <= t)
    z = 1.0 + min(0.03, 0.006 * (t - c0))          # gentle push, capped so edges never crop
    if z > 1.001:
        w, h = int(W / z), int(H / z)
        f = f.crop(((W - w) // 2, (H - h) // 2, (W - w) // 2 + w, (H - h) // 2 + h)).resize((W, H), Image.BILINEAR)
    arr = np.asarray(f.convert('RGB')).astype('float32')
    g = GRAIN[math.floor(t * 12) % 4]
    arr += np.repeat(np.repeat(g, 2, 0), 2, 1)[:H, :W, None]
    fade = clamp((t - (DUR - 0.6)) / 0.6)
    if fade > 0: arr = arr * (1 - fade) + np.array(PAPER, 'float32') * fade
    return Image.fromarray(arr.clip(0, 255).astype('uint8'))

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{SP}s3_{FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
