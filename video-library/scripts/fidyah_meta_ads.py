"""Bayar Fidyah, voiceover version laid out for Meta ads. FMT=916 (1080x1920) or FMT=45 (1080x1350)."""
import os
LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))   # video-library/
ASSETS = os.path.join(LIB, 'assets') + '/'
FONTS = os.path.join(LIB, 'fonts') + '/'
OUT = os.path.join(LIB, 'output') + '/'
os.makedirs(OUT, exist_ok=True)
import subprocess, math, sys
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import imageio_ffmpeg

SP = OUT
FMT = os.environ.get('FMT', '916')                # '916' = 1080x1920 Reels/Stories, '45' = 1080x1350 Feed
W, H = (1080, 1920) if FMT == '916' else (1080, 1350)
FPS, DUR = 30, 51.5
# every scene is laid out in a 1080x1000 box; 9:16 puts it inside Meta's Reels safe zone (~14% top, ~35% bottom)
BY = 260 if FMT == '916' else 175
OUT = SP + f'fidyah_meta_{FMT}_silent.mp4'
SHIFT = 5.0
HOOK = 1.5      # extra seconds for the hook opener
GALLERY = 3.5   # extra seconds for the distribution gallery
N = int(FPS * DUR)

FD = FONTS + 'pjs/PlusJakartaSans-2.7.1/ttf/'
_MAP = {'InterDisplay-Bold': 'PlusJakartaSans-ExtraBold', 'InterDisplay-SemiBold': 'PlusJakartaSans-Bold',
        'Inter-Medium': 'PlusJakartaSans-Medium', 'Inter-SemiBold': 'PlusJakartaSans-SemiBold'}
def font(n, s): return ImageFont.truetype(FD + _MAP.get(n, n) + '.ttf', s)
HERO = font('InterDisplay-Bold', 190)
BIG = font('InterDisplay-Bold', 112)
H2 = font('InterDisplay-Bold', 80)
CARD = font('InterDisplay-SemiBold', 40)
NUM = font('InterDisplay-Bold', 100)
RES = font('InterDisplay-Bold', 150)
ROW = font('InterDisplay-SemiBold', 56)
CAP = font('Inter-Medium', 40)
SMALL = font('Inter-Medium', 34)
TINY = font('Inter-Medium', 28)
UI = font('Inter-SemiBold', 30)
CARD_T = font('InterDisplay-SemiBold', 27)

# bayarfidyahonline.com palette
NAVY = (12, 26, 48)
WHITE = NAVY                # default text colour on the light theme
PURE = (255, 255, 255)
GREY = (100, 116, 139)
BLUE, SKY = (6, 107, 227), (56, 189, 248)
TINT = (234, 243, 254)
LINE = (226, 232, 240)
EMERALD = BLUE
MINT = BLUE
GOLD_A = BLUE

def clamp(x, a=0.0, b=1.0): return max(a, min(b, x))
def ease_out(x): x = clamp(x); return 1 - (1 - x) ** 4
def ease_io(x): x = clamp(x); return 3 * x * x - 2 * x * x * x
def roll(v0, v1, t, st, dur): return v0 + (v1 - v0) * ease_io((t - st) / dur)

_cache = {}
def sprite(text, fnt, fill=WHITE, grad=None):
    key = (text, id(fnt), fill, grad)
    if key in _cache: return _cache[key]
    l, t, r, b = fnt.getbbox(text); pad = 40
    w, h = r - l + pad * 2, b - t + pad * 2
    mask = Image.new('L', (w, h), 0)
    ImageDraw.Draw(mask).text((pad - l, pad - t), text, font=fnt, fill=255)
    if grad:
        c0, c1 = (BLUE, (37, 150, 245)) if grad == 'green' else ((6, 95, 212), SKY)
        k = np.clip(0.7 * np.linspace(0, 1, w)[None, :, None] + 0.3 * np.linspace(0, 1, h)[:, None, None], 0, 1)
        col = (np.array(c0) * (1 - k) + np.array(c1) * k).astype('uint8')
        img = Image.fromarray(np.broadcast_to(col, (h, w, 3)).copy()).convert('RGBA')
    else:
        img = Image.new('RGBA', (w, h), fill + (255,))
    img.putalpha(mask); _cache[key] = img
    return img

def place(frame, spr, cx, cy, alpha=1.0, scale=1.0, blur=0.0, anchor='c'):
    if alpha <= 0.01: return
    s = spr
    if abs(scale - 1) > 0.002:
        s = s.resize((max(1, int(s.width * scale)), max(1, int(s.height * scale))), Image.BICUBIC)
    if blur > 0.3: s = s.filter(ImageFilter.GaussianBlur(blur))
    if alpha < 1:
        s = s.copy(); s.putalpha(s.getchannel('A').point(lambda v: int(v * alpha)))
    x = int(cx - s.width / 2) if anchor == 'c' else int(cx - 40 * scale)
    frame.alpha_composite(s, (x, int(cy - s.height / 2)))

def life(t, s, e, fin=0.7, fout=0.5):
    a_in = ease_out((t - s) / fin); a_out = 1 - ease_io((t - (e - fout)) / fout)
    a = clamp(min(a_in, a_out))
    return a, 18 * (1 - a_in) + 14 * (1 - a_out), 1.06 - 0.06 * a_in - 0.03 * (1 - a_out)

def layer(): return Image.new('RGBA', (W, H), (0, 0, 0, 0))
def alpha_img(img, a):
    img = img.copy(); img.putalpha(img.getchannel('A').point(lambda v: int(v * a))); return img

# ---------- assets from bayarfidyahonline.com ----------
def rounded(img, size, r):
    img = img.convert('RGB').resize((size, size), Image.LANCZOS).convert('RGBA')
    m = Image.new('L', (size, size), 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, size - 1, size - 1), r, fill=255)
    img.putalpha(m); return img
IMGDIR = ASSETS + 'images/'
CATS = [('warga-emas', 'Warga emas uzur', 'Fidyah sahaja'),
        ('uzur-sakit', 'Sakit berpanjangan', 'Fidyah sahaja'),
        ('hamil', 'Ibu hamil', 'Qada\' + fidyah*'),
        ('menyusu', 'Ibu menyusu', 'Qada\' + fidyah*'),
        ('lewat-qada', 'Lewat qada\' puasa', 'Qada\' + fidyah'),
        ('meninggal-dunia', 'Bagi pihak si mati', 'Dibayar waris')]
# clearer illustrations supplied by the user; warga-emas still from the site
CAT_SRC = {'warga-emas': ASSETS + 'fid/kat_warga-emas.webp', 'uzur-sakit': IMGDIR + '4.webp', 'hamil': IMGDIR + '5.webp',
           'menyusu': IMGDIR + '6.webp', 'meninggal-dunia': IMGDIR + '7.webp', 'lewat-qada': IMGDIR + '8.webp'}
CAT_IMG = {k: rounded(Image.open(CAT_SRC[k]), 266, 28) for k, _, _ in CATS}
logo = Image.open(ASSETS + 'fid/logo.png').convert('RGBA'); logo.thumbnail((300, 100), Image.LANCZOS)
LOGO = Image.open(ASSETS + 'fid/logo.png').convert('RGBA'); LOGO.thumbnail((380, 130), Image.LANCZOS)
# hero photo from the site (fidyah distribution), washed out like the site hero
LOGO_PILL = Image.new('RGBA', (logo.width + 80, logo.height + 44), (0, 0, 0, 0))
ImageDraw.Draw(LOGO_PILL).rounded_rectangle((0, 0, LOGO_PILL.width - 1, LOGO_PILL.height - 1), 30, fill=(255, 255, 255, 255))
LOGO_PILL.alpha_composite(logo, (40, 22))

# ---------- animated background ----------
LW, LH = W // 8, H // 8
gy, gx = np.mgrid[0:LH, 0:LW] / np.array([LH, LW])[:, None, None]
def bg(t):
    img = np.zeros((LH, LW, 3)) + np.array([248, 251, 255])
    blobs = [((0.15 + 0.08 * math.sin(t * 0.3), 0.2 + 0.08 * math.cos(t * 0.25)), 0.4, (-45, -25, -5)),
             ((0.88 + 0.06 * math.cos(t * 0.21), 0.8 + 0.08 * math.sin(t * 0.27)), 0.45, (-40, -18, 0)),
             ((0.55 + 0.1 * math.cos(t * 0.18), 1.05), 0.3, (-25, -8, 0))]
    for (cx, cy), r, col in blobs:
        d = ((gx - cx) / r) ** 2 + ((gy - cy) / (r * 0.6)) ** 2
        img += np.exp(-d)[..., None] * np.array(col)
    return Image.fromarray(img.clip(0, 255).astype('uint8')).resize((W, H), Image.BICUBIC).convert('RGBA')

# ---------- scenes (box coordinates: Y(v) = BY + v) ----------
def Y(v): return BY + v
def lines_at(f, rows, fnt, y0, gap, a, b, fill=GREY):
    for i, ln in enumerate(rows): place(f, sprite(ln, fnt, fill), W / 2, Y(y0 + i * gap), a, 1, b)
def wrap(text, fnt, maxw):
    words, out, cur = text.split(), [], ''
    for w_ in words:
        tt = (cur + ' ' + w_).strip()
        if fnt.getlength(tt) <= maxw: cur = tt
        else: out.append(cur); cur = w_
    out.append(cur); return out

def scene_intro(f, t):
    a, b, s_ = life(t, 0.15, 2.4, fin=0.6, fout=0.4)
    place(f, sprite('Ada hutang', BIG), W / 2, Y(330), a, s_, b)
    a1, b1, s1 = life(t, 0.35, 2.4, fin=0.6, fout=0.4)
    place(f, sprite('puasa yang', BIG), W / 2, Y(465), a1, s1, b1)
    a2, b2, s2 = life(t, 0.55, 2.4, fin=0.6, fout=0.4)
    place(f, sprite('belum diganti?', BIG, grad='green'), W / 2, Y(600), a2, s2, b2)
    a3, b3, s3 = life(t, 2.3, 4.5)
    place(f, sprite('Fidyah.', HERO, grad='green'), W / 2, Y(430), a3, s3, b3)
    a4, b4, _ = life(t, 2.9, 4.5)
    lines_at(f, ['Siapa perlu bayar?', 'Bagaimana nak kira?'], CAP, 600, 56, a4, b4)

def scene_beras(f, t):
    a, b, s = life(t, 3.1, 5.6)
    place(f, sprite('Secupak', BIG), W / 2, Y(330), a, s, b)
    place(f, sprite('beras.', BIG), W / 2, Y(455), a, s, b)
    a2, b2, _ = life(t, 3.7, 5.6)
    lines_at(f, ['700 gram makanan asasi bagi', 'setiap hari puasa yang ditinggalkan,', 'diberikan kepada fakir miskin.'], CAP, 610, 56, a2, b2)

DESC = {'warga-emas': "Golongan tua yang tidak mampu berpuasa dan tiada harapan untuk mengqada' puasa.",
        'uzur-sakit': 'Pesakit kronik yang disahkan doktor tidak berupaya berpuasa dan tiada harapan sembuh.',
        'hamil': 'Meninggalkan puasa kerana bimbang akan keselamatan anak yang dikandung.',
        'menyusu': 'Meninggalkan puasa kerana bimbang akan keselamatan anak yang disusukan.',
        'lewat-qada': "Melewatkan qada' puasa sehingga masuk Ramadan berikutnya tanpa keuzuran.",
        'meninggal-dunia': "Waris membayar bagi pihak si mati yang meninggalkan puasa dan sempat mengqada'."}
IMG_S = 214
SMCAT = {k: rounded(Image.open(CAT_SRC[k]), IMG_S, 24) for k, _, _ in CATS}
CT = font('InterDisplay-Bold', 38)
CD = font('Inter-Medium', 25)
TAGF = font('Inter-SemiBold', 24)
PAGES = [(CATS[:3], 5.6, 12.2), (CATS[3:], 12.2, 19.0)]

def scene_cats(f, t):
    a, b, s = life(t, 5.6, 19.0)
    place(f, sprite('Siapa perlu bayar?', H2, grad='green'), W / 2, Y(50), a, s, b)
    lay = layer(); d = ImageDraw.Draw(lay)
    for p in range(2):
        on = (t < 12.2) == (p == 0)
        d.rounded_rectangle((W / 2 - 34 + p * 44, Y(118), W / 2 - 34 + p * 44 + (32 if on else 16), Y(130)), 6,
                            fill=(BLUE if on else LINE) + (255,))
    f.alpha_composite(alpha_img(lay, a))
    cw, ch, gap = 980, 246, 20
    x = (W - cw) // 2
    for pi, (cats, ps, pe) in enumerate(PAGES):
        if not (ps - 0.1 <= t < pe + 0.1): continue
        for i, (k, title, tag) in enumerate(cats):
            st = ps + (0.5 if pi == 0 else 0.25) + i * 0.45
            ca, cb, _ = life(t, st, pe, fin=0.6, fout=0.45)
            if ca <= 0.01: continue
            dx = int(90 * (1 - ease_out((t - st) / 0.7)))
            y = Y(160 + i * (ch + gap))
            lay = layer(); d = ImageDraw.Draw(lay)
            d.rounded_rectangle((x + dx + 5, y + 14, x + dx + cw + 5, y + ch + 14), 30, fill=(15, 40, 80, 18))
            d.rounded_rectangle((x + dx, y, x + dx + cw, y + ch), 30, fill=PURE + (255,), outline=LINE + (255,), width=2)
            lay.alpha_composite(SMCAT[k], (x + dx + 16, y + 16))
            tx = x + dx + 16 + IMG_S + 28
            tw_max = cw - (16 + IMG_S + 28) - 28
            d.text((tx, y + 48), title, font=CT, fill=NAVY + (255,), anchor='lm')
            for li, line in enumerate(wrap(DESC[k], CD, tw_max)[:3]):
                d.text((tx, y + 100 + li * 34), line, font=CD, fill=GREY + (255,), anchor='lm')
            tgw = TAGF.getlength(tag) + 36
            d.rounded_rectangle((tx, y + ch - 56, tx + tgw, y + ch - 16), 20, fill=TINT + (255,))
            d.text((tx + tgw / 2, y + ch - 36), tag, font=TAGF, fill=BLUE + (255,), anchor='mm')
            if cb > 0.3: lay = lay.filter(ImageFilter.GaussianBlur(cb * 0.5))
            f.alpha_composite(alpha_img(lay, ca))
        na, nb, _ = life(t, ps + 1.8, pe, fout=0.45)
        place(f, sprite("* Hamil/menyusu: jika bimbang diri sendiri sahaja, qada' tanpa fidyah.", font('Inter-Medium', 23), GREY), W / 2, Y(985), na, 1, nb)

def scene_calc(f, t):
    a, b, s = life(t, 14.1, 22.5)
    place(f, sprite('Cara kira.', BIG, grad='green'), W / 2, Y(60), a, s, b)
    fa, fb, _ = life(t, 14.7, 22.5)
    place(f, sprite('(Hari × Kadar) × Tahun', ROW), W / 2, Y(185), fa, 1, fb)
    place(f, sprite("Tahun = gandaan bagi setiap Ramadan qada' dilewatkan", TINY, GREY), W / 2, Y(248), fa, 1, fb)
    na, nb, _ = life(t, 15.3, 22.5)
    place(f, sprite('RM4.00 sehari · kadar piawai kebangsaan', SMALL, MINT), W / 2, Y(318), na, 1, nb)
    ca, cb, _ = life(t, 15.8, 22.5)
    if ca <= 0.01: return
    X0, X1, Y0, Y1 = 70, 1010, Y(390), Y(990)
    cx = (X0 + X1) / 2
    lay = layer(); d = ImageDraw.Draw(lay)
    d.rounded_rectangle((X0 + 6, Y0 + 18, X1 + 6, Y1 + 18), 44, fill=(15, 40, 80, 18))
    d.rounded_rectangle((X0, Y0, X1, Y1), 44, fill=PURE + (255,), outline=LINE + (255,), width=2)
    d.text((cx, Y0 + 52), "CONTOH · Lewat qada' 3 tahun", font=TINY, fill=GREY + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, ca))
    days = int(round(roll(0, 7, t, 16.3, 0.8))); rate = roll(0, 4, t, 16.9, 0.7); yrs = int(round(roll(0, 3, t, 17.5, 0.6)))
    cols = [(X0 + 150, f'{days}', 'hari'), (cx, f'RM{rate:.0f}', 'sehari'), (X1 - 150, f'{yrs}', 'tahun')]
    for i, (x, v, lbl) in enumerate(cols):
        ea, eb, _ = life(t, 16.2 + i * 0.6, 22.5, fin=0.5)
        place(f, sprite(v, NUM), x, Y0 + 185, ea * ca, 1, eb)
        place(f, sprite(lbl, SMALL, GREY), x, Y0 + 270, ea * ca, 1, eb)
    for i, x in enumerate([X0 + 300, X1 - 300]):
        place(f, sprite('×', CAP, GREY), x, Y0 + 180, ca * ease_out((t - 16.6 - i * 0.6) / 0.4))
    la = ease_out((t - 18.3) / 0.5) * ca
    lay = layer(); ImageDraw.Draw(lay).rectangle((cx - 330 * la, Y0 + 330, cx + 330 * la, Y0 + 333), fill=LINE + (255,)); f.alpha_composite(lay)
    res = roll(0, 84, t, 18.6, 1.0)
    ra, rb, _ = life(t, 18.5, 22.5, fin=0.5)
    rbl = 5 * math.sin(math.pi * clamp((t - 18.6) / 1.0))
    place(f, sprite(f'RM{res:.0f}', RES, grad='gold'), cx, Y0 + 440, ra * ca, 1, rb + rbl)
    place(f, sprite('jumlah fidyah', SMALL, GREY), cx, Y0 + 535, ra * ca, 1, rb)

SCR_W = 380
_s0 = Image.open(ASSETS + 'fid/ui/s00.jpg')
SCR_H = int(SCR_W * _s0.height / _s0.width)
_ui = [Image.open(ASSETS + f'fid/ui/s{i:02d}.jpg').convert('RGB').resize((SCR_W, SCR_H), Image.LANCZOS) for i in range(10)]
_um = Image.new('L', (SCR_W, SCR_H), 0); ImageDraw.Draw(_um).rounded_rectangle((0, 0, SCR_W - 1, SCR_H - 1), 46, fill=255)
UI_SHOTS = []
for im in _ui:
    im = im.convert('RGBA'); im.putalpha(_um); UI_SHOTS.append(im)
UI_SC = SCR_W / 1170
TAPS = [(23.0, 1, (580, 990)), *[(23.45 + j * 0.22, 2 + j, (1001, 1266)) for j in range(6)],
        (24.9, 8, (1001, 1518)), (25.15, 9, (1001, 1518))]

def phone(f, t, a, b, X, Y_):
    if a <= 0.01: return
    lay = layer(); d = ImageDraw.Draw(lay)
    rise = int(100 * (1 - ease_out((t - 22.6) / 0.9)))
    x0, y0 = X, Y_ + rise
    x1, y1 = x0 + SCR_W + 32, y0 + SCR_H + 32
    d.rounded_rectangle((x0 + 8, y0 + 24, x1 + 8, y1 + 24), 62, fill=(15, 40, 80, 30))
    d.rounded_rectangle((x0, y0, x1, y1), 62, fill=(18, 24, 36, 255), outline=(60, 70, 90, 255), width=5)
    idx = 0
    for tt, i, _ in TAPS:
        if t >= tt: idx = i
    lay.alpha_composite(UI_SHOTS[idx], (x0 + 16, y0 + 16))
    d = ImageDraw.Draw(lay)
    for tt, i, (px, py) in TAPS:
        k = (t - tt + 0.12) / 0.45
        if 0 <= k <= 1:
            cx, cy = x0 + 16 + px * UI_SC, y0 + 16 + py * UI_SC
            r = 16 + 18 * k
            d.ellipse((cx - r, cy - r, cx + r, cy + r), fill=(6, 107, 227, int(90 * (1 - k))), outline=(6, 107, 227, int(200 * (1 - k))), width=3)
    if b > 0.3: lay = lay.filter(ImageFilter.GaussianBlur(b * 0.5))
    f.alpha_composite(alpha_img(lay, a))

def scene_platform(f, t):
    a, b, s = life(t, 22.5, 27.0)
    place(f, sprite('Kira. Bayar. Selesai.', H2, grad='green'), W / 2, Y(50), a, s, b)
    pa, pb, _ = life(t, 22.6, 27.0, fin=0.9)
    phone(f, t, pa, pb, 610, Y(125))
    steps = [('1', 'Kira jumlah', "Hari & gandaan qada' dikira automatik"),
             ('2', 'Isi butiran & bayar', 'FPX, kad atau QR melalui CHIP'),
             ('3', 'Terima resit', 'Resit rasmi terus ke emel')]
    hf, sf = font('InterDisplay-Bold', 40), font('Inter-Medium', 25)
    for i, (n, ttl, sub) in enumerate(steps):
        sa, sb, _ = life(t, 23.2 + i * 0.5, 27.0, fin=0.6)
        if sa <= 0.01: continue
        y = Y(200 + i * 190)
        lay = layer(); d = ImageDraw.Draw(lay)
        d.ellipse((50, y, 114, y + 64), fill=EMERALD + (255,))
        d.text((82, y + 32), n, font=CAP, fill=PURE + (255,), anchor='mm')
        d.text((134, y + 4), ttl, font=hf, fill=NAVY + (255,), anchor='lt')
        for j, ln in enumerate(wrap(sub, sf, 440)):
            d.text((134, y + 58 + j * 32), ln, font=sf, fill=GREY + (255,), anchor='lt')
        f.alpha_composite(alpha_img(lay, sa))
    ma, mb, _ = life(t, 24.9, 27.0)
    place(f, sprite('Kurang daripada', font('InterDisplay-Bold', 40), BLUE), 50, Y(800), ma, 1, mb, anchor='l')
    place(f, sprite('3 minit.', font('InterDisplay-Bold', 40), BLUE), 50, Y(852), ma, 1, mb, anchor='l')

GAL = [('g4', 'Sungai Buah'), ('g9', 'Sabak Bernam, Selangor'), ('g5', 'Sungai Buah'),
       ('g10', 'Zimbabwe'), ('g2', 'Sungai Buah'), ('g1', 'Sungai Buah'), ('g7', 'Sungai Buah')]
GW, GH = 900, 520
def _card(path):
    im = Image.open(ASSETS + f'fid/gal/{path}.jpg').convert('RGB')
    s_ = max(GW / im.width, GH / im.height)
    im = im.resize((int(im.width * s_ + 1), int(im.height * s_ + 1)), Image.LANCZOS)
    x = (im.width - GW) // 2; y = (im.height - GH) // 3
    im = im.crop((x, y, x + GW, y + GH)).convert('RGBA')
    m = Image.new('L', (GW, GH), 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, GW - 1, GH - 1), 32, fill=255)
    im.putalpha(m); return im
GAL_IMG = [_card(p) for p, _ in GAL]

def scene_gallery(f, t, s0, e0):
    a, b, s_ = life(t, s0, e0)
    pan = Y(330) - 1700 * ease_io((t - s0) / (e0 - s0))
    x = (W - GW) // 2
    for i, img in enumerate(GAL_IMG):
        st = s0 + 0.2 + i * 0.12
        ca, cb, _ = life(t, st, e0, fin=0.6)
        if ca <= 0.01: continue
        y = int(pan + i * (GH + 36)) + int(50 * (1 - ease_out((t - st) / 0.7)))
        if y > H or y + GH < 0: continue
        lay = layer(); d = ImageDraw.Draw(lay)
        d.rounded_rectangle((x + 6, y + 18, x + GW + 6, y + GH + 18), 32, fill=(15, 40, 80, 30))
        lay.alpha_composite(img, (x, y))
        cap = GAL[i][1]; tw_ = TINY.getlength(cap) + 44
        d.rounded_rectangle((x + 26, y + GH - 74, x + 26 + tw_, y + GH - 24), 25, fill=(255, 255, 255, 235))
        d.text((x + 26 + tw_ / 2, y + GH - 49), cap, font=TINY, fill=NAVY + (255,), anchor='mm')
        if cb > 0.3: lay = lay.filter(ImageFilter.GaussianBlur(cb * 0.5))
        f.alpha_composite(alpha_img(lay, ca))
    band = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    bh = Y(265)
    ImageDraw.Draw(band).rectangle((0, 0, W, bh), fill=(244, 248, 254, 238))
    fade = Image.linear_gradient('L').rotate(180).resize((W, 110)).point(lambda v: int(v * 238 / 255))
    fl = Image.new('RGBA', (W, 110), (244, 248, 254, 255)); fl.putalpha(fade)
    band.alpha_composite(fl, (0, bh))
    f.alpha_composite(alpha_img(band, a))
    place(f, sprite('Sampai kepada', H2, grad='green'), W / 2, Y(60), a, s_, b)
    place(f, sprite('yang memerlukan.', H2, grad='green'), W / 2, Y(155), a, s_, b)
    place(f, sprite('Agihan fidyah di Malaysia dan luar negara.', TINY, GREY), W / 2, Y(235), a, 1, b)

def scene_end(f, t):
    a, b, s = life(t, 26.9, 31.0)
    place(f, sprite('Tunaikan fidyah', H2), W / 2, Y(170), a, s, b)
    place(f, sprite('anda hari ini.', H2), W / 2, Y(268), a, s, b)
    a2, b2, s2 = life(t, 27.3, 31.0)
    place(f, sprite('www.bayarfidyahonline.com', font('InterDisplay-Bold', 64), grad='green'), W / 2, Y(420), a2, s2, b2)
    sa, sb, _ = life(t, 27.6, 31.0)
    payers = int(round(roll(0, 27219, t, 27.6, 1.2))); ben = int(round(roll(0, 50, t, 27.6, 1.2)))
    place(f, sprite(f'{payers:,}+', font('InterDisplay-Bold', 80), grad='gold'), W / 2 - 230, Y(600), sa, 1, sb)
    place(f, sprite('pembayar', SMALL, GREY), W / 2 - 230, Y(678), sa, 1, sb)
    place(f, sprite(f'{ben}K', font('InterDisplay-Bold', 80), grad='gold'), W / 2 + 260, Y(600), sa, 1, sb)
    place(f, sprite('penerima manfaat', SMALL, GREY), W / 2 + 260, Y(678), sa, 1, sb)
    la, lb, _ = life(t, 28.2, 31.0)
    place(f, sprite('Platform oleh', TINY, GREY), W / 2, Y(810), la, 1, lb)
    place(f, LOGO, W / 2, Y(900), la, 1, lb)

def render(t):
    f = bg(t)
    if t < 4.7: scene_intro(f, t)
    t1 = t - HOOK                      # original 35s timeline for the middle
    if 3.0 <= t1 < 5.9: scene_beras(f, t1)
    if 5.5 <= t1 < 19.3: scene_cats(f, t1)
    tt = t1 - SHIFT                    # original 30s timeline for calc/platform
    if 14.0 <= tt < 22.8: scene_calc(f, tt)
    if 22.4 <= tt < 27.3: scene_platform(f, tt)
    g0 = 22.4 + 4.6 + SHIFT + HOOK     # gallery starts as platform fades (t = 33.5)
    if g0 - 0.2 <= t < g0 + GALLERY + 0.4: scene_gallery(f, t, g0, g0 + GALLERY + 0.1)
    te = tt - GALLERY
    if te >= 26.8: scene_end(f, te)
    out = f.convert('RGB')
    fade = clamp((te - 29.4) / 0.6)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H)), fade)
    return out

# (time on the voiceover timeline, time in the original 40s animation): each pair pins a scene or card to its spoken line
WARP = [(0, 0), (3.4, 2.3), (5.9, 4.5), (9.6, 7.0), (12.0, 7.6), (13.7, 8.05), (14.9, 8.5), (18.0, 13.7),
        (18.9, 13.95), (20.1, 14.4), (23.4, 14.85), (25.7, 20.5), (27.5, 21.3), (30.3, 22.8), (31.3, 23.4),
        (32.6, 24.0), (34.1, 25.1), (36.7, 28.9), (38.5, 29.5), (41.0, 31.65), (42.5, 33.5), (46.0, 37.0),
        (50.8, 39.3), (51.5, 40.0)]
def warp(T): return float(np.interp(T, [a for a, _ in WARP], [b for _, b in WARP]))
N = int(FPS * DUR)

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(warp(float(ts))).save(f'{SP}meta_{FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(warp(i / FPS)).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
