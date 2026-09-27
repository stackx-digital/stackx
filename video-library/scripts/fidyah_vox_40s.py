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
OUT = SP + 'fidyah_vox_silent.mp4'
W, H, FPS, DUR = 1920, 1080, 30, 40.0
N = int(FPS * DUR)

FI = FONTS + 'extras/ttf/'
def font(n, s): return ImageFont.truetype(FI + n + '.ttf', s)
BLACK = lambda s: font('InterDisplay-Black', s)
BOLD = lambda s: font('InterDisplay-Bold', s)
MED = lambda s: font('Inter-Medium', s)
SEMI = lambda s: font('Inter-SemiBold', s)
MONO = lambda s: ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf', s)

PAPER = (238, 232, 219)
INK = (24, 22, 20)
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
    base -= np.array(fib)[..., None] * 0.35
    yy, xx = np.mgrid[0:H, 0:W]
    v = ((xx - W / 2) / (W * 0.62)) ** 2 + ((yy - H / 2) / (H * 0.62)) ** 2
    base *= (1 - 0.28 * v[..., None] ** 1.3)
    return Image.fromarray(base.clip(0, 255).astype('uint8')).convert('RGBA')
PAPER_IMG = make_paper()
GRAIN = [rng.normal(0, 7, (H // 2, W // 2)).astype('float32') for _ in range(4)]

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

CATS = [('warga-emas', 'Warga emas uzur', "Tidak mampu berpuasa & tiada harapan untuk mengqada'.", 'Fidyah sahaja', ASSETS + 'fid/kat_warga-emas.webp'),
        ('uzur-sakit', 'Sakit berpanjangan', 'Pesakit kronik yang tiada harapan sembuh.', 'Fidyah sahaja', IMGDIR + '4.webp'),
        ('hamil', 'Ibu hamil', 'Bimbang akan keselamatan anak yang dikandung.', "Qada' + fidyah", IMGDIR + '5.webp'),
        ('menyusu', 'Ibu menyusu', 'Bimbang akan keselamatan anak yang disusukan.', "Qada' + fidyah", IMGDIR + '6.webp'),
        ('lewat-qada', "Lewat qada' puasa", 'Hingga masuk Ramadan berikutnya tanpa uzur.', "Qada' + fidyah", IMGDIR + '8.webp'),
        ('meninggal-dunia', 'Bagi pihak si mati', "Waris membayar bagi puasa yang tertinggal.", 'Dibayar waris', IMGDIR + '7.webp')]

def note_card(img_path, title, desc, tag, seed, angle):
    cw = 420
    card = Image.new('RGBA', (cw, 660), (252, 250, 245, 255))
    card.alpha_composite(load_sq(img_path, cw).convert('RGBA'), (0, 0))
    d = ImageDraw.Draw(card)
    d.text((cw / 2, 462), title, font=BLACK(34), fill=INK, anchor='mm')
    fnt = MED(22); words, lines, cur = desc.split(), [], ''
    for w_ in words:
        tt = (cur + ' ' + w_).strip()
        if fnt.getlength(tt) <= cw - 40: cur = tt
        else: lines.append(cur); cur = w_
    lines.append(cur)
    for i, ln in enumerate(lines): d.text((cw / 2, 505 + i * 29), ln, font=fnt, fill=GREY, anchor='mm')
    tf = BLACK(30); tw = tf.getlength(tag) + 28; r = random.Random(seed)
    d.polygon([(cw / 2 - tw / 2 + r.uniform(-3, 3), 594), (cw / 2 + tw / 2, 592), (cw / 2 + tw / 2 + 4, 630), (cw / 2 - tw / 2 - 2, 632)], fill=YELLOW)
    d.text((cw / 2, 612), tag, font=tf, fill=INK, anchor='mm')
    return cutout(card, border=14, angle=angle, seed=seed)
ANG = [-3.2, 2.2, -1.6, 2.8, -2.4, 1.8]
CARDS = [note_card(p, ti, de, tg, 40 + i, ANG[i]) for i, (_, ti, de, tg, p) in enumerate(CATS)]

HERO = cutout(fit(ASSETS + 'fid/g/p14.jpg', 820, 560, 0.5), border=16, angle=-4, seed=7)

# real calculator screenshots (mobile), printed & cut out
SC = 0.34
UI = []
for i in range(10):
    im = Image.open(ASSETS + f'fid/ui/s{i:02d}.jpg').convert('RGB')
    im = im.crop((0, 140, im.width, im.height - 300))
    UI.append(im.resize((int(im.width * SC), int(im.height * SC)), Image.LANCZOS))
UI_ANG = 2.5
UI_CUT = [cutout(u, border=14, angle=UI_ANG, seed=90, tape=True) for u in UI]
def ui_point(px, py):
    """screenshot px → offset from the cut-out centre after rotation"""
    u = UI[0]
    x = px * SC - u.width / 2; y = (py - 140) * SC - u.height / 2
    a = math.radians(UI_ANG)
    return x * math.cos(a) + y * math.sin(a), -x * math.sin(a) + y * math.cos(a)
TAPS = [(23.0, 1, (580, 990)), *[(23.45 + j * 0.22, 2 + j, (1001, 1266)) for j in range(6)],
        (24.9, 8, (1001, 1518)), (25.15, 9, (1001, 1518))]

GAL = [('g4', 'Sungai Buah'), ('g9', 'Sabak Bernam'), ('g10', 'Zimbabwe'), ('g5', 'Sungai Buah')]
GAL_POS = [(285, 520, -4), (735, 480, 3), (1185, 530, -2), (1635, 480, 4)]
def polaroid(path, cap, seed, ang):
    im = fit(ASSETS + f'fid/gal/{path}.jpg', 420, 300, 0.35)
    card = Image.new('RGBA', (420, 370), (252, 250, 245, 255)); card.alpha_composite(im.convert('RGBA'))
    ImageDraw.Draw(card).text((210, 336), cap, font=BLACK(26), fill=INK, anchor='mm')
    return cutout(card, border=16, angle=ang, seed=seed)
GAL_IMG = [polaroid(p, c, 60 + i, GAL_POS[i][2]) for i, (p, c) in enumerate(GAL)]

logo = Image.open(ASSETS + 'fid/logo.png').convert('RGBA'); logo.thumbnail((330, 110), Image.LANCZOS)

# ---------- scenes (timings match the 40s music) ----------
def s_hook(f, t, tq):
    a = life(t, 0.1, 2.35)
    highlight(f, BLACK(128), W / 2 - 560, 438, 'Ada hutang puasa', 'hutang puasa', ease_out((tq - 0.5) / 0.5) * a, seed=3)
    text_at(f, 'Ada hutang puasa', BLACK(128), W / 2 - 560, 430, INK, a, seed=1, t=t)
    text_at(f, 'yang belum diganti?', BLACK(128), W / 2 - 560, 590, INK, a * clamp((tq - 0.4) / 0.2), seed=2, t=t)
    marker_line(f, W / 2 - 240, 670, W / 2 + 560, 668, ease_out((tq - 1.2) / 0.5) * a, seed=4)

def s_title(f, t, tq):
    a = life(t, 2.3, 4.55)
    s = 1 + 0.4 * (1 - back_out((tq - 2.3) / 0.35))
    place(f, text_img('FIDYAH', BLACK(260)), W / 2, 470, a, s, 5, t)
    label(f, 'PANDUAN RINGKAS', W / 2 - 200, 650, clamp((tq - 2.7) / 0.3) * a, seed=6, t=t)
    text_at(f, 'Siapa perlu bayar  ·  Bagaimana nak kira  ·  Cara bayar', MED(32), W / 2, 740, GREY,
            a * clamp((tq - 3.0) / 0.3), anchor='c', seed=7, t=t)

def s_beras(f, t, tq):
    a = life(t, 4.5, 7.05)
    k = back_out((tq - 4.5) / 0.4)
    place(f, HERO, 560 - 300 * (1 - k), 560, a, 1.0 + 0.02 * (t - 4.5), 8, t)
    fx = 1080
    text_at(f, 'Secupak beras', BLACK(96), fx, 360, INK, a * clamp((tq - 4.8) / 0.2), seed=9, t=t)
    y = 480
    highlight(f, BLACK(64), fx, y + 6, '= 700 gram', '700 gram', ease_out((tq - 5.3) / 0.4) * a, seed=10)
    text_at(f, '= 700 gram', BLACK(64), fx, y, INK, a * clamp((tq - 5.1) / 0.2), seed=11, t=t)
    for i, ln in enumerate(['makanan asasi bagi setiap hari', 'puasa yang ditinggalkan,', 'diberikan kepada fakir miskin.']):
        text_at(f, ln, MED(38), fx, 590 + i * 54, GREY, a * clamp((tq - 5.6 - i * 0.12) / 0.2), seed=12 + i, t=t)

def s_cats(f, t, tq):
    a = life(t, 7.0, 20.55)
    label(f, '01  SIAPA PERLU BAYAR?', 90, 95, clamp((tq - 7.1) / 0.3) * a, seed=20, t=t)
    for page, (ps, pe) in enumerate([(7.0, 13.75), (13.7, 20.55)]):
        if not (ps <= t < pe): continue
        pa = life(t, ps, pe, 0.2, 0.25)
        for j in range(3):
            i = page * 3 + j
            st = ps + 0.35 + j * 0.45
            k = back_out((tq - st) / 0.35)
            if k <= 0: continue
            cx = 380 + j * 580; cy = 575 - 220 * (1 - k)
            place(f, CARDS[i], cx, cy, pa * clamp((tq - st) / 0.1), 1.0, 30 + i, t)
        if page == 0:
            na = pa * clamp((tq - 9.8) / 0.3)
            text_at(f, "Ibu hamil & menyusu: jika bimbang keselamatan diri sendiri sahaja, qada' tanpa fidyah.", MED(26), W / 2, 1025, GREY, na, anchor='c', seed=24, t=t)

def s_calc(f, t, tq):
    a = life(t, 20.5, 29.05)
    label(f, '02  CARA KIRA', 90, 95, clamp((tq - 20.6) / 0.3) * a, seed=80, t=t)
    x0, y = 120, 330
    parts = [('(Hari', 0), (' × Kadar)', 1), (' × Tahun', 2)]
    fnt = BLACK(84)
    full = '(Hari × Kadar) × Tahun'
    for sub, i in [('Hari', 0), ('Kadar', 1), ('Tahun', 2)]:
        highlight(f, fnt, x0, y + 6, full, sub, ease_out((tq - 21.3 - i * 0.45) / 0.35) * a, seed=81 + i)
    text_at(f, full, fnt, x0, y, INK, a * clamp((tq - 20.8) / 0.2), seed=84, t=t)
    notes = [('Hari', 'bilangan hari puasa ditinggalkan'), ('Kadar', 'RM4.00 sehari · kadar piawai kebangsaan'),
             ('Tahun', "gandaan bagi setiap Ramadan qada' dilewatkan")]
    for i, (k_, v) in enumerate(notes):
        na = a * clamp((tq - 21.5 - i * 0.45) / 0.2)
        text_at(f, k_, BLACK(34), x0, 470 + i * 70, INK, na, seed=85 + i, t=t)
        text_at(f, '— ' + v, MED(32), x0 + 125, 472 + i * 70, GREY, na, seed=88 + i, t=t)
    text_at(f, 'Rujuk pihak berkuasa agama negeri untuk pengesahan kadar.', MED(26), x0, 700, GREY, a * clamp((tq - 23.0) / 0.3), seed=91, t=t)
    # receipt card, typed line by line
    rc_a = a * clamp((tq - 22.6) / 0.15)
    if rc_a > 0:
        k = back_out((tq - 22.6) / 0.35)
        rw, rh = 620, 520
        rc = Image.new('RGBA', (rw, rh), (252, 250, 245, 255)); d = ImageDraw.Draw(rc)
        mono = MONO(30)
        lines = [("CONTOH: LEWAT QADA' 3 TAHUN", 23.0), ('', 0), ('Hari ditinggalkan ....... 7', 23.4), ('Kadar sehari ....... RM4.00', 23.8),
                 ('Gandaan tahun ........... 3', 24.2), ('-' * 27, 24.5), ('JUMLAH ............ RM84.00', 24.8)]
        for i, (ln, st) in enumerate(lines):
            if not ln: continue
            n = int(len(ln) * clamp((tq - st) / 0.3))
            d.text((34, 40 + i * 62), ln[:n], font=mono, fill=INK)
        card = cutout(rc, border=0, angle=-2.0, seed=95)
        cx, cy = 1420, 600 - 200 * (1 - k)
        place(f, card, cx, cy, rc_a, 1.0, 96, t)
        # circle the total
        marker_ellipse(f, cx + 175, cy + 170, 125, 48, ease_out((tq - 25.2) / 0.5) * a, seed=97)
        big = ease_out((tq - 25.6) / 0.3) * a
        if big > 0:
            highlight(f, BLACK(90), x0, 856, 'Jumlah = RM84', 'RM84', big, seed=98)
            text_at(f, 'Jumlah = RM84', BLACK(90), x0, 850, INK, a, seed=99, t=t)

def s_pay(f, t, tq):
    a = life(t, 29.0, 33.55)
    label(f, '03  BAYAR ONLINE', 90, 95, clamp((tq - 29.1) / 0.3) * a, seed=100, t=t)
    lt = t - 6.5   # tap timings are defined on the original 30s timeline
    idx = 0
    for tt, i, _ in TAPS:
        if lt >= tt: idx = i
    k = back_out((tq - 29.1) / 0.4)
    cx, cy = 1420, 560 + 400 * (1 - k)
    place(f, UI_CUT[idx], cx, cy, a, 1.0, 101, t, 1.0)
    for tt, i, (px, py) in TAPS:
        pr = (lt - tt + 0.15) / 0.35
        if 0 <= pr <= 1.4:
            ox, oy = ui_point(px, py)
            marker_ellipse(f, cx + ox, cy + oy, 34, 30, ease_out(pr), seed=110 + i, width=5)
    if lt >= 25.15:
        ox, oy = ui_point(790, 1937)
        marker_ellipse(f, cx + ox, cy + oy, 110, 40, ease_out((lt - 25.3) / 0.45), seed=120)
    steps = [('Kira jumlah', 'hari & gandaan dikira automatik'), ('Isi butiran & bayar', 'FPX, kad atau QR melalui CHIP'),
             ('Terima resit', 'resit rasmi terus ke emel')]
    for i, (h_, s_) in enumerate(steps):
        st = 29.5 + i * 0.5
        sa = a * clamp((tq - st) / 0.15)
        y = 330 + i * 150
        text_at(f, f'{i + 1}.', BLACK(64), 120, y, RED, sa, seed=130 + i, t=t)
        text_at(f, h_, BLACK(58), 200, y, INK, sa, seed=133 + i, t=t)
        text_at(f, s_, MED(30), 202, y + 58, GREY, sa, seed=136 + i, t=t)
    ha = ease_out((tq - 31.5) / 0.4) * a
    highlight(f, BLACK(46), 120, 816, 'Kurang daripada 3 minit.', '3 minit', ha, seed=140)
    text_at(f, 'Kurang daripada 3 minit.', BLACK(46), 120, 810, INK, a * clamp((tq - 31.3) / 0.2), seed=141, t=t)

def s_gallery(f, t, tq):
    a = life(t, 33.5, 37.05)
    label(f, '04  SAMPAI KEPADA YANG MEMERLUKAN', 90, 95, clamp((tq - 33.6) / 0.3) * a, seed=150, t=t)
    zoom = 1.0 + 0.012 * (t - 33.5)
    for i, (x, y, _) in enumerate(GAL_POS):
        st = 33.7 + i * 0.3
        k = back_out((tq - st) / 0.3)
        if k <= 0: continue
        place(f, GAL_IMG[i], W / 2 + (x - W / 2) * zoom, y - 160 * (1 - k), a, (0.8 + 0.2 * k) * 0.9, 160 + i, t)
    ha = a * clamp((tq - 35.4) / 0.2)
    highlight(f, BLACK(50), W / 2, 936, 'Agihan di Malaysia & luar negara', 'luar negara', ease_out((tq - 35.6) / 0.4) * a, seed=170, anchor='c')
    text_at(f, 'Agihan di Malaysia & luar negara', BLACK(50), W / 2, 930, INK, ha, anchor='c', seed=171, t=t)

def s_end(f, t, tq):
    a = life(t, 37.0, 41.0)
    text_at(f, 'Tunaikan fidyah anda hari ini.', BLACK(76), W / 2, 330, INK, a * clamp((tq - 37.1) / 0.2), anchor='c', seed=180, t=t)
    highlight(f, BLACK(96), W / 2, 486, 'bayarfidyahonline.com', 'bayarfidyahonline.com', ease_out((tq - 37.6) / 0.5) * a, seed=181, anchor='c')
    text_at(f, 'bayarfidyahonline.com', BLACK(96), W / 2, 480, INK, a * clamp((tq - 37.4) / 0.2), anchor='c', seed=182, t=t)
    sa = a * clamp((tq - 38.0) / 0.2)
    text_at(f, '27,219+ pembayar   ·   50K penerima manfaat', SEMI(38), W / 2, 610, GREY, sa, anchor='c', seed=183, t=t)
    la = a * clamp((tq - 38.3) / 0.2)
    place(f, logo, W / 2, 760, la, 1.0, 184, t, 1.0)

def render(t):
    tq = q(t)
    f = PAPER_IMG.copy()
    if t < 2.5: s_hook(f, t, tq)
    if 2.2 <= t < 4.6: s_title(f, t, tq)
    if 4.4 <= t < 7.1: s_beras(f, t, tq)
    if 6.9 <= t < 20.6: s_cats(f, t, tq)
    if 20.4 <= t < 29.1: s_calc(f, t, tq)
    if 28.9 <= t < 33.6: s_pay(f, t, tq)
    if 33.4 <= t < 37.1: s_gallery(f, t, tq)
    if t >= 36.9: s_end(f, t, tq)
    # slow camera push, reset each scene
    cuts = [0, 2.3, 4.5, 7.0, 13.7, 20.5, 29.0, 33.5, 37.0]
    c0 = max(c for c in cuts if c <= t)
    z = 1.0 + 0.012 * (t - c0)
    if z > 1.001:
        w, h = int(W / z), int(H / z)
        f = f.crop(((W - w) // 2, (H - h) // 2, (W - w) // 2 + w, (H - h) // 2 + h)).resize((W, H), Image.BILINEAR)
    arr = np.asarray(f.convert('RGB')).astype('float32')
    g = GRAIN[math.floor(t * 12) % 4]
    arr += np.repeat(np.repeat(g, 2, 0), 2, 1)[..., None]
    fade = clamp((t - 39.3) / 0.7)
    if fade > 0: arr *= (1 - fade)
    return Image.fromarray(arr.clip(0, 255).astype('uint8'))

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{SP}vx_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done')
