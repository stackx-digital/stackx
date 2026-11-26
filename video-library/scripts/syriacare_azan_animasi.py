"""Syria Care Malaysia: "Bila Azan Kembali Bergema", animated NGO version (37.6s), synced to the original voiceover.
Motion graphics over duotone footage stills, line-drawn mosque with azan sound rings, burned-in captions.
The original audio track (voice + music) from the client's video is reused unchanged.
FMT=916 (1080x1920 Reels/Stories), FMT=45 (1080x1350 Feed) or FMT=169 (1920x1080 YouTube).
Usage: FMT=916 python3 syriacare_azan_animasi.py [preview seconds ...]"""
import os, sys, math, random, subprocess
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import imageio_ffmpeg

LIB = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
AS = os.path.join(LIB, 'assets', 'syriacare') + '/'
OUTD = os.path.join(LIB, 'output') + '/'
os.makedirs(OUTD, exist_ok=True)
FD = os.path.join(LIB, 'fonts', 'pjs', 'PlusJakartaSans-2.7.1', 'ttf') + '/'
FMT = os.environ.get('FMT', '916')
W, H = {'916': (1080, 1920), '45': (1080, 1350), '169': (1920, 1080)}[FMT]
BY = {'916': 260, '45': 175, '169': 40}[FMT]          # 1000px-tall content box (Meta safe zone on 9:16)
FPS, DUR = 30, 37.58
N = int(FPS * DUR)
OUT = OUTD + f'syriacare_azan_{FMT}.mp4'
AUDIO = AS + 'audio_asal.m4a'

TEAL = (25, 99, 108)          # Syria Care brand teal (#19636C)
TEAL_D = (8, 40, 46)
TEAL_L = (196, 226, 228)
GOLD = (242, 190, 92)
RED = (205, 3, 53)            # CTA red from the original video
WHITE = (255, 255, 255)
END_BG = (232, 231, 237)

def font(w, s): return ImageFont.truetype(FD + f'PlusJakartaSans-{w}.ttf', s)
def clamp(x, a=0.0, b=1.0): return max(a, min(b, x))
def ease_out(x): x = clamp(x); return 1 - (1 - x) ** 3
def ease_io(x): x = clamp(x); return x * x * (3 - 2 * x)
def Y(v): return BY + v
CX = W / 2

def life(t, s, e, fin=0.6, fout=0.45):
    a = clamp(min(ease_out((t - s) / fin), 1 - ease_io((t - (e - fout)) / fout)))
    return a, 1.05 - 0.05 * ease_out((t - s) / fin)

_sc = {}
def sprite(text, fnt, fill=WHITE):
    k = (text, id(fnt), fill)
    if k not in _sc:
        l, t, r, b = fnt.getbbox(text); p = 24
        m = Image.new('L', (r - l + 2 * p, b - t + 2 * p), 0)
        ImageDraw.Draw(m).text((p - l, p - t), text, font=fnt, fill=255)
        im = Image.new('RGBA', m.size, fill + (255,)); im.putalpha(m); _sc[k] = im
    return _sc[k]

def place(f, img, cx, cy, a=1.0, s=1.0):
    if a <= 0.01 or s <= 0.01: return
    if abs(s - 1) > 0.003: img = img.resize((max(1, int(img.width * s)), max(1, int(img.height * s))), Image.BICUBIC)
    if a < 0.999:
        img = img.copy(); img.putalpha(img.getchannel('A').point(lambda v: int(v * a)))
    f.alpha_composite(img, (int(cx - img.width / 2), int(cy - img.height / 2)))

# ---------- backgrounds: duotone footage with a slow pan ----------
CLEAN = {'f6.5': (420, 150, 1900, 830), 'f11': (700, 150, 1900, 880), 'f13.5': (1080, 100, 1900, 600),
         'f16.5': (380, 160, 1820, 850), 'f19': (650, 0, 1900, 760), 'f24.5': (420, 0, 1900, 820)}
def still(key): return Image.open(AS + key + '.jpg').convert('RGB').crop(CLEAN[key])
def cover(img, w, h):
    s = max(w / img.width, h / img.height)
    img = img.resize((int(img.width * s + 1), int(img.height * s + 1)), Image.LANCZOS)
    x, y = (img.width - w) // 2, (img.height - h) // 2
    return img.crop((x, y, x + w, y + h))
def duotone(path, dark=TEAL_D, light=TEAL_L, k=0.55):
    g = np.asarray(cover(still(path).convert('L').filter(ImageFilter.GaussianBlur(2)), int(W * 1.08), int(H * 1.08))).astype(float) / 255
    g = g ** 1.1 * k                                  # keep it dark so white text reads
    arr = np.array(dark)[None, None] * (1 - g[..., None]) + np.array(light)[None, None] * g[..., None]
    im = Image.fromarray(arr.astype('uint8')).convert('RGBA')
    vg = Image.new('L', im.size, 0); ImageDraw.Draw(vg).ellipse((-im.width * 0.2, -im.height * 0.1, im.width * 1.2, im.height * 1.1), fill=255)
    vg = vg.filter(ImageFilter.GaussianBlur(im.width // 6))
    dark_l = Image.new('RGBA', im.size, dark + (255,)); dark_l.putalpha(vg.point(lambda v: int((255 - v) * 0.6)))
    im.alpha_composite(dark_l)
    return im
BG = {k: duotone(k) for k in ('f6.5', 'f13.5', 'f16.5', 'f19')}
def bg(f, key, t, t0, a=1.0, dx=1):
    im = BG[key]
    ox = int((im.width - W) / 2 + dx * (im.width - W) / 2 * math.sin(clamp((t - t0) / 10) * 1.2))
    oy = int((im.height - H) / 2)
    crop = im.crop((ox, oy, ox + W, oy + H))
    if a < 0.999: crop.putalpha(int(255 * a))
    f.alpha_composite(crop)
def brand_bg():
    y = np.linspace(0, 1, H)[:, None, None]
    arr = (np.array(TEAL) * (1 - y) + np.array(TEAL_D) * y).astype('uint8')
    im = Image.fromarray(np.broadcast_to(arr, (H, W, 3)).copy()).convert('RGBA')
    lay = Image.new('RGBA', (W, H), (0, 0, 0, 0)); d = ImageDraw.Draw(lay)
    s = 120                                           # faint 8-point star lattice
    for r in range(-1, H // s + 2):
        for c in range(-1, W // s + 2):
            x, yy = c * s + (s // 2 if r % 2 else 0), r * s
            pts = []
            for i in range(16):
                ang = i * math.pi / 8; rad = 26 if i % 2 == 0 else 14
                pts.append((x + rad * math.cos(ang), yy + rad * math.sin(ang)))
            d.polygon(pts, outline=(255, 255, 255, 22), width=2)
    im.alpha_composite(lay); return im
BRAND = brand_bg()

# ---------- photo cards ----------
def card(path, w, h, r=30, border=12):
    im = cover(still(path), w, h).convert('RGBA')
    m = Image.new('L', (w, h), 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, w - 1, h - 1), r - 6, fill=255); im.putalpha(m)
    out = Image.new('RGBA', (w + 2 * border + 60, h + 2 * border + 70), (0, 0, 0, 0))
    sh = Image.new('RGBA', out.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((30, 46, 30 + w + 2 * border, 46 + h + 2 * border), r, fill=(0, 0, 0, 110))
    out.alpha_composite(sh.filter(ImageFilter.GaussianBlur(18)))
    ImageDraw.Draw(out).rounded_rectangle((30, 30, 30 + w + 2 * border, 30 + h + 2 * border), r, fill=WHITE)
    out.alpha_composite(im, (30 + border, 30 + border))
    return out
CW, CH = 820, 500
C_GATHER = card('f13.5', CW, CH)
C_SOLAT = card('f11', CW, CH)
C_DAMAGE = card('f16.5', CW, CH)
C_WORK = card('f19', CW, CH)
C_JEMAAH = card('f24.5', CW, CH)

# ---------- line-drawn mosque + azan rings ----------
MW, MH = 640, 560
MIN_TOP = (520, 96)
def mosque_img(col=WHITE, fill_a=40, lw=7):
    im = Image.new('RGBA', (MW, MH), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    c = col + (255,); fc = col + (fill_a,)
    d.rectangle((70, 300, 450, 520), fill=fc, outline=c, width=lw)                  # prayer hall
    d.chord((110, 120, 410, 420), 180, 360, fill=fc, outline=c, width=lw)            # dome
    d.line((260, 120, 260, 82), fill=c, width=lw)                                    # finial
    d.ellipse((248, 62, 272, 86), outline=c, width=lw - 2)
    d.rounded_rectangle((210, 400, 310, 520), 50, outline=c, width=lw)               # door arch
    for x in (115, 365):
        d.rounded_rectangle((x, 360, x + 50, 430), 25, outline=c, width=lw - 2)      # windows
    d.rectangle((490, 160, 550, 520), fill=fc, outline=c, width=lw)                  # minaret
    d.rectangle((478, 250, 562, 270), fill=fc, outline=c, width=lw - 2)              # balcony
    d.polygon([(486, 160), (520, 110), (554, 160)], fill=fc, outline=c)              # cap
    d.line((520, 110, 520, 80), fill=c, width=lw - 2)
    d.arc((506, 52, 534, 80), 60, 300, fill=c, width=5)                              # crescent
    d.line((30, 522, 610, 522), fill=c, width=lw)                                    # ground
    return im
MOSQUE = mosque_img()
MOSQUE_GOLD = mosque_img(GOLD, 60)
def draw_on(img, k):
    """Reveal from the ground up."""
    if k >= 1: return img
    m = Image.new('L', img.size, 0)
    ImageDraw.Draw(m).rectangle((0, int(img.height * (1 - k)), img.width, img.height), fill=255)
    out = img.copy(); out.putalpha(Image.composite(img.getchannel('A'), m, m)); return out

def rings(f, ox, oy, t, t0, broken=False, col=WHITE, strength=1.0):
    lay = Image.new('RGBA', (W, H), (0, 0, 0, 0)); d = ImageDraw.Draw(lay)
    cx, cy = ox + MIN_TOP[0] - MW / 2, oy + MIN_TOP[1] - MH / 2
    rng = random.Random(int(t * 7))
    for i in range(4):
        ph = ((t - t0) * 0.55 + i / 4) % 1
        if t - t0 < i * 0.45: continue
        r = 40 + ph * 300
        a = (1 - ph) * 200 * strength
        if broken:
            a *= 0.35 + 0.65 * (rng.random() > 0.35)          # flicker
            for seg in range(6):                              # dashed arcs with random gaps
                if rng.random() < 0.45: continue
                st = -60 + seg * 20
                d.arc((cx - r, cy - r, cx + r, cy + r), st, st + 12, fill=col + (int(a),), width=6)
                d.arc((cx - r, cy - r, cx + r, cy + r), 180 - st - 12, 180 - st, fill=col + (int(a),), width=6)
        else:
            d.arc((cx - r, cy - r, cx + r, cy + r), -60, 60, fill=col + (int(a),), width=7)
            d.arc((cx - r, cy - r, cx + r, cy + r), 120, 240, fill=col + (int(a),), width=7)
    f.alpha_composite(lay)

# ---------- text helpers ----------
HEAD = font('ExtraBold', 74 if FMT != '169' else 70)
HEAD_S = font('Bold', 44)
CAP = font('SemiBold', 34)
def headline(f, lines, t, s, e, y0, gap=88, gold_last=False):
    for i, ln in enumerate(lines):
        a, sc = life(t, s + i * 0.25, e)
        col = GOLD if (gold_last and i == len(lines) - 1) else WHITE
        place(f, sprite(ln, HEAD, col), CX, Y(y0 + i * gap) + (1 - a) * 20, a, sc)

def pill(text, fnt, bg, fg=WHITE, padx=34, h=None, r=None):
    tw = fnt.getlength(text); h = h or int(fnt.size * 1.9); r = r if r is not None else h // 2
    im = Image.new('RGBA', (int(tw + 2 * padx), h), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, im.width - 1, h - 1), r, fill=bg + (255,))
    d.text((im.width / 2, h / 2), text, font=fnt, fill=fg, anchor='mm')
    return im

def pin_chip(text):
    fnt = font('Bold', 30)
    im = pill('     ' + text, fnt, WHITE, TEAL)
    d = ImageDraw.Draw(im); x, y = 40, im.height / 2
    d.ellipse((x - 13, y - 20, x + 13, y + 6), fill=RED); d.polygon([(x - 11, y - 3), (x + 11, y - 3), (x, y + 18)], fill=RED)
    d.ellipse((x - 5, y - 12, x + 5, y - 2), fill=WHITE)
    return im

def icon_chip(text, kind):
    fnt = font('Bold', 32)
    tw_ = fnt.getlength(text); im = Image.new('RGBA', (int(tw_) + 118, 72), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, im.width - 1, 71), 36, fill=WHITE)
    d.text((88, 36), text, font=fnt, fill=TEAL, anchor='lm')
    x, y = 44, 36
    d.ellipse((x - 24, y - 24, x + 24, y + 24), fill=TEAL)
    if kind == 'people':
        for dx in (-9, 9):
            d.ellipse((x + dx - 6, y - 15, x + dx + 6, y - 3), fill=WHITE); d.rounded_rectangle((x + dx - 8, y - 1, x + dx + 8, y + 14), 6, fill=WHITE)
    elif kind == 'solat':
        d.rounded_rectangle((x - 9, y - 16, x + 9, y + 16), 4, outline=WHITE, width=3); d.arc((x - 6, y - 14, x + 6, y - 2), 180, 360, fill=WHITE, width=3)
    else:
        d.polygon([(x, y + 14), (x - 15, y - 1), (x - 15, y - 9), (x - 8, y - 15), (x, y - 8), (x + 8, y - 15), (x + 15, y - 9), (x + 15, y - 1)], fill=WHITE)
    return im
CHIPS = [(icon_chip('Berkumpul', 'people'), 8.2), (icon_chip('Solat berjemaah', 'solat'), 11.2), (icon_chip('Silaturahim', 'heart'), 12.6)]

# ---------- captions (the original voiceover lines) ----------
SUBS = [(0.0, 2.5, 'Bayangkan, sudah sekian lama'), (2.5, 4.9, 'laungan azan tidak lagi bergema'),
        (4.9, 6.8, 'seperti dahulu di masjid ini.'), (6.8, 10.5, 'Dahulu, masjid menjadi tempat penduduk berkumpul,'),
        (10.5, 14.3, 'menunaikan solat berjemaah dan mengeratkan silaturahim.'),
        (14.6, 18.3, 'Konflik telah meninggalkan kesan pada Masjid Akko.'),
        (18.6, 22.6, 'Kini, kita berpeluang membantu memulihkan rumah Allah ini'),
        (22.6, 25.0, 'agar kembali dimanfaatkan masyarakat.'), (25.6, 28.4, 'Ayuh, wakaf bersama Syria Care.'),
        (28.6, 34.8, 'Semoga azan kembali bergema di Masjid Akko.')]
def wrap(text, fnt, maxw):
    out, cur = [], ''
    for w_ in text.split():
        tt = (cur + ' ' + w_).strip()
        if fnt.getlength(tt) <= maxw: cur = tt
        else: out.append(cur); cur = w_
    return out + [cur]
_cap = {}
def caption_img(text):
    if text not in _cap:
        lines = wrap(text, CAP, 880)
        imgs = [pill(l, CAP, TEAL, h=60, r=14, padx=22) for l in lines]
        w = max(i.width for i in imgs); h = sum(i.height for i in imgs) + 6 * (len(imgs) - 1)
        out = Image.new('RGBA', (w, h), (0, 0, 0, 0)); y = 0
        for i in imgs: out.alpha_composite(i, ((w - i.width) // 2, y)); y += i.height + 6
        _cap[text] = out
    return _cap[text]
def captions(f, t):
    for s, e, txt in SUBS:
        if s - 0.05 <= t < e + 0.2:
            a = clamp(min((t - s) / 0.2, (e + 0.2 - t) / 0.2))
            img = caption_img(txt)
            place(f, img, CX, Y(1000) - img.height / 2, a)

# ---------- scenes ----------
def scene_silent(f, t):                    # 0 - 6.9: when did the azan last sound here?
    e = 7.1
    a = clamp(min(t / 0.4, (e - t) / 0.4))
    bg(f, 'f6.5', t, 0, a)
    headline(f, ['Bila kali terakhir azan', 'bergema di masjid ini?'], t, 0.3, e, 90)
    k = ease_io((t - 0.4) / 1.8)
    mx, my = CX, Y(560)
    place(f, draw_on(MOSQUE, k), mx, my, a)
    if t > 1.2: rings(f, mx, my, t, 1.2, broken=t > 3.0, strength=a * (1 if t < 3 else clamp(1 - (t - 3) / 3.5)))
    if t > 4.3:                                                # silent: speaker with a slash
        ka = ease_out((t - 4.3) / 0.5) * a
        ic = Image.new('RGBA', (120, 120), (0, 0, 0, 0)); d = ImageDraw.Draw(ic)
        d.ellipse((0, 0, 119, 119), fill=RED + (255,))
        d.polygon([(30, 48), (46, 48), (66, 30), (66, 90), (46, 72), (30, 72)], fill=WHITE)
        d.line((78, 44, 98, 76), fill=WHITE, width=7); d.line((98, 44, 78, 76), fill=WHITE, width=7)
        place(f, ic, mx + MIN_TOP[0] - MW / 2 + 110, my + MIN_TOP[1] - MH / 2 - 20, ka, 0.6 + 0.4 * ka)

def scene_past(f, t):                      # 6.9 - 14.6: a place where people gathered
    s, e = 6.8, 14.8
    a = clamp(min((t - s) / 0.4, (e - t) / 0.4))
    bg(f, 'f13.5', t, s, a, -1)
    headline(f, ['Pernah menjadi tempat', 'masyarakat berhimpun'], t, s + 0.2, e, 70, gold_last=True)
    for i, (cimg, st) in enumerate(((C_GATHER, s + 0.4), (C_SOLAT, 10.4))):
        ka = ease_out((t - st) / 0.6)
        if ka <= 0: continue
        out = 1.0 if i == 1 else 1 - ease_io((t - 10.4 - 0.4) / 0.4)
        rot = (-2.5, 2)[i] * (1 - 0.4 * ka)
        im = cimg.rotate(rot, resample=Image.BICUBIC, expand=True)
        place(f, im, CX + (1 - ka) * (300 if i else -300), Y(460), a * ka * out, 0.88)
    pos = [(CX - 190, Y(738)), (CX + 190, Y(738)), (CX, Y(822))]
    for (chip, st), (x, y) in zip(CHIPS, pos):
        ka, sc = life(t, st, e)
        place(f, chip, x, y, a * ka, sc)

CRACKS = []
_r = random.Random(3)
for _ in range(4):
    x, y = _r.uniform(-300, 300), _r.uniform(-180, 180); pts = [(x, y)]
    for _ in range(9): x += _r.uniform(-40, 40); y += _r.uniform(-45, 45); pts.append((x, y))
    CRACKS.append(pts)
PIN = pin_chip('MASJID AKKO, SYRIA')
def scene_conflict(f, t):                  # 14.6 - 18.6: conflict left its mark
    s, e = 14.5, 18.8
    a = clamp(min((t - s) / 0.3, (e - t) / 0.4))
    bg(f, 'f16.5', t, s, a)
    ka, sc = life(t, s + 0.2, e); place(f, PIN, CX, Y(70), a * ka, sc)
    shake = (math.sin(t * 60) * 6 * clamp(1 - (t - s - 0.5) / 0.6)) if t > s + 0.5 else 0
    kc = ease_out((t - s - 0.1) / 0.6)
    place(f, C_DAMAGE, CX + shake, Y(430), a * kc, 0.9 + 0.1 * kc)
    lay = Image.new('RGBA', (W, H), (0, 0, 0, 0)); d = ImageDraw.Draw(lay)
    for ci, pts in enumerate(CRACKS):
        k = clamp((t - s - 0.6 - ci * 0.25) / 0.8)
        n = int(k * (len(pts) - 1))
        if n < 1: continue
        p = [(CX + x * 0.9 + shake, Y(430) + y * 0.9) for x, y in pts[:n + 1]]
        d.line(p, fill=(20, 20, 20, int(200 * a)), width=7, joint='curve'); d.line(p, fill=(255, 255, 255, int(120 * a)), width=2)
    f.alpha_composite(lay)
    headline(f, ['Konflik telah meninggalkan', 'kesan pada masjid ini'], t, s + 0.9, e, 780, gap=80)

def scene_restore(f, t):                   # 18.6 - 25.6: this mosque needs restoring
    s, e = 18.4, 25.8
    a = clamp(min((t - s) / 0.4, (e - t) / 0.4))
    bg(f, 'f19', t, s, a, -1)
    headline(f, ['Masjid ini memerlukan', 'BAIK PULIH'], t, s + 0.2, e, 70, gold_last=True)
    kc = ease_out((t - s - 0.4) / 0.6)
    wipe = ease_io((t - 22.5) / 0.9)                          # repair -> people praying again
    base = C_WORK.copy()
    if wipe > 0:
        m = Image.new('L', base.size, 0); ImageDraw.Draw(m).rectangle((0, 0, int(base.width * wipe), base.height), fill=255)
        base = Image.composite(C_JEMAAH, base, m)
    place(f, base, CX, Y(470), a * kc, 0.88 + 0.04 * kc)
    if 0 < wipe < 1:
        lay = Image.new('RGBA', (W, H), (0, 0, 0, 0))
        x = CX - base.width * 0.88 / 2 + base.width * 0.88 * wipe
        ImageDraw.Draw(lay).line((x, Y(470) - 240, x, Y(470) + 240), fill=GOLD + (255,), width=6)
        f.alpha_composite(lay)

BADGE = pill('WAKAF BAIK PULIH MASJID & SEKOLAH SYRIA', font('ExtraBold', 28), WHITE, TEAL, padx=30)
BTN = pill('SUMBANG HARI INI', font('ExtraBold', 46), RED, padx=60, h=104)
URL = sprite('www.syriacare.org.my', font('Bold', 38), WHITE)
def scene_cta(f, t):                       # 25.6 - 35.2: help the azan sound again
    s, e = 25.4, 35.4
    a = clamp(min((t - s) / 0.5, (e - t) / 0.4))
    b = BRAND.copy(); b.putalpha(int(255 * a)); f.alpha_composite(b)
    ka, sc = life(t, s + 0.2, e); place(f, BADGE, CX, Y(40), a * ka, sc)
    mx, my = CX, Y(330)
    k = ease_io((t - s - 0.3) / 1.4)
    place(f, draw_on(MOSQUE_GOLD, k), mx, my, a, 0.72)
    if t > 28.4:
        rings(f, mx + (MIN_TOP[0] - MW / 2) * (0.72 - 1), my + (MIN_TOP[1] - MH / 2) * (0.72 - 1) - 0, t, 28.4, col=GOLD, strength=a)
    headline(f, ['Bantu azan', 'kembali bergema.'], t, s + 0.6, e, 640, gap=84, gold_last=True)
    kb = ease_out((t - 27.6) / 0.5)
    pulse = 1 + 0.04 * math.sin(max(0, t - 28.2) * 5) if t > 28.2 else 1
    place(f, BTN, CX, Y(800), a * kb, (0.7 + 0.3 * kb) * pulse)
    ku, _ = life(t, 29.0, e); place(f, URL, CX, Y(880), a * ku)

def end_card():
    src = Image.open(AS + 't36.5.jpg').convert('RGB').crop((520, 200, 1400, 960))   # logo + URL from the original end card
    m = Image.new('L', src.size, 0); ImageDraw.Draw(m).rectangle((40, 40, src.width - 40, src.height - 40), fill=255)
    m = m.filter(ImageFilter.GaussianBlur(30))
    im = src.convert('RGBA'); im.putalpha(m); return im
END = end_card()
def scene_end(f, t):                       # 35.2 - end: Syria Care
    a = ease_out((t - 35.1) / 0.5)
    lay = Image.new('RGBA', (W, H), END_BG + (int(255 * a),)); f.alpha_composite(lay)
    sc = 0.95 + 0.05 * a
    place(f, END, CX, Y(500), a, sc * (1.0 if FMT != '169' else 1.15))

def render(t):
    f = Image.new('RGBA', (W, H), TEAL_D + (255,))
    if t < 7.2: scene_silent(f, t)
    if 6.7 <= t < 14.9: scene_past(f, t)
    if 14.4 <= t < 18.9: scene_conflict(f, t)
    if 18.3 <= t < 25.9: scene_restore(f, t)
    if 25.3 <= t < 35.5: scene_cta(f, t)
    if t >= 35.1: scene_end(f, t)
    if t < 35.1: captions(f, t)
    return f.convert('RGB')

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{OUTD}sc_{FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-i', AUDIO, '-map', '0:v', '-map', '1:a', '-c:v', 'libx264', '-preset', 'medium', '-crf', '21',
                          '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '160k', '-shortest', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
