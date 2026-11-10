"""Siri Fidyah 1/5: Warga emas uzur, "Mak dah tak larat puasa" (~45s).
Chat story in a siblings' group chat, laid out in the Meta safe box. Music + SFX are synthesised here.
FMT=916 (1080x1920 Reels/Stories) or FMT=45 (1080x1350 Feed).
Usage: FMT=916 python3 fidyah_siri1_warga_emas.py [preview seconds ...]"""
import math, random, subprocess, sys, wave
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import imageio_ffmpeg
import fidyah_meta_ads as B          # shared look: background, fonts, logo, safe box
from fidyah_meta_ads import (W, H, FPS, BY, Y, sprite, place, life, layer, alpha_img, font, clamp,
                             ease_out, ease_io, BLUE, NAVY, GREY, PURE, LINE, TINT, H2, TINY)

SR = 44100
OUT = B.SP + f'siri1_warga_emas_{B.FMT}.mp4'
WAV = B.SP + f'siri1_warga_emas_{B.FMT}.wav'
CORAL = (232, 96, 70)
TEAL = (13, 148, 136)
READ = (150, 232, 255)
EMO = ImageFont.truetype('/usr/share/fonts/truetype/noto/NotoColorEmoji.ttf', 109)

def spring(x):
    if x <= 0: return 0.0
    if x >= 1.2: return 1.0
    return 1 - math.exp(-x * 9) * math.cos(x * 23)

# ---------------- text with emoji ----------------
_emo = {}
def is_emoji(c): o = ord(c); return o >= 0x1F000 or 0x2600 <= o < 0x27C0
def emoji_img(c, size):
    k = (c, size)
    if k not in _emo:
        im = Image.new('RGBA', (160, 160), (0, 0, 0, 0))
        ImageDraw.Draw(im).text((10, 10), c, font=EMO, embedded_color=True)
        im = im.crop(im.getbbox())
        _emo[k] = im.resize((int(im.width * size / im.height), size), Image.LANCZOS)
    return _emo[k]
def segs(s):
    out, cur, emo = [], '', None
    for c in s.replace('️', ''):
        e = is_emoji(c)
        if emo is None or e == emo: cur += c
        else: out.append((cur, emo)); cur = c
        emo = e
    if cur: out.append((cur, emo))
    return out
def tw(s, f):
    es = int(f.size * 1.08)
    return sum((len(t) * (es + 4)) if e else f.getlength(t) for t, e in segs(s))
def draw_rich(im, x, base, s, f, fill):
    d = ImageDraw.Draw(im); es = int(f.size * 1.08)
    for t, e in segs(s):
        if e:
            for c in t:
                ei = emoji_img(c, es); im.alpha_composite(ei, (int(x), int(base - es * 0.86))); x += es + 4
        else:
            d.text((x, base), t, font=f, fill=fill, anchor='ls'); x += f.getlength(t)
def wrap(s, f, maxw):
    words, lines, cur = s.split(' '), [], ''
    for w_ in words:
        t = (cur + ' ' + w_).strip()
        if tw(t, f) <= maxw: cur = t
        else: lines.append(cur); cur = w_
    lines.append(cur); return lines

# ---------------- chat window geometry (inside the safe box) ----------------
PX0, PX1 = 50, W - 50
PT, PB = Y(-10), Y(1010)
HDR, BAR = 128, 108
AX0, AY0 = PX0, PT + HDR                 # message area origin (frame coords)
AW, AH = PX1 - PX0, PB - BAR - (PT + HDR)

MSG = font('Inter-Medium', 38)
NAMEF = font('InterDisplay-SemiBold', 28)
SMALL = font('Inter-Medium', 26)
PADX, PADY, LH, MAXW, TAIL = 30, 22, 52, 600, 14
AV = 58                                   # avatar size
RX = 26 + AV + 12                         # received bubble x in area

PEOPLE = {'K': ('Kak Long', CORAL), 'A': ('Angah', TEAL), 'M': ('Adik', BLUE)}

def shadowed(w, h, col, x0, tail_side):
    im = Image.new('RGBA', (w + TAIL + 4, h + 10), (0, 0, 0, 0))
    sh = Image.new('RGBA', im.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((x0, 4, x0 + w, h + 4), 32, fill=(20, 40, 80, 30))
    im.alpha_composite(sh.filter(ImageFilter.GaussianBlur(4)))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle((x0, 0, x0 + w, h), 32, fill=col)
    if tail_side == 'r': d.polygon([(w - 36, h), (w + TAIL, h), (w - 6, h - 28)], fill=col)
    else: d.polygon([(TAIL + 36, h), (0, h), (TAIL + 6, h - 28)], fill=col)
    return im, d

def bubble(who, text, show_name):
    sent = who == 'M'
    lines = wrap(text, MSG, MAXW)
    nh = 40 if (show_name and not sent) else 0
    w = int(max([tw(l, MSG) for l in lines] + ([NAMEF.getlength(PEOPLE[who][0])] if nh else []))) + PADX * 2
    if sent: w = max(w, 190)
    h = nh + len(lines) * LH + PADY * 2 - 4 + (26 if sent else 0)
    x0 = 0 if sent else TAIL
    im, d = shadowed(w, h, BLUE if sent else PURE, x0, 'r' if sent else 'l')
    if nh: d.text((x0 + PADX, PADY + 2), PEOPLE[who][0], font=NAMEF, fill=PEOPLE[who][1], anchor='lt')
    link = text.startswith('www.')
    for i, l in enumerate(lines):
        base = nh + PADY + 40 + i * LH
        draw_rich(im, x0 + PADX, base, l, MSG, PURE)  if sent else draw_rich(im, x0 + PADX, base, l, MSG, NAVY)
        if link: d.line([(x0 + PADX, base + 7), (x0 + PADX + tw(l, MSG), base + 7)], fill=PURE, width=3)
    return im, (x0 + w, h)

def link_bubble():
    """Sent link with a preview card of the site."""
    w = 600
    pv_h = 170
    h = PADY + pv_h + 14 + LH + PADY + 22
    im, d = shadowed(w, h, BLUE, 0, 'r')
    d.rounded_rectangle((14, 14, w - 14, 14 + pv_h), 22, fill=PURE)
    lg = B.LOGO.copy(); lg.thumbnail((200, 64), Image.LANCZOS)
    im.alpha_composite(lg, (36, 36))
    d.text((36, 114), 'Bayar Fidyah Online', font=font('InterDisplay-SemiBold', 30), fill=NAVY, anchor='lt')
    d.text((36, 152), 'Kira, bayar & terima resit', font=SMALL, fill=GREY, anchor='lt')
    s = 'www.bayarfidyahonline.com'
    base = 14 + pv_h + 14 + 40
    d.text((PADX, base), s, font=MSG, fill=PURE, anchor='ls')
    d.line([(PADX, base + 7), (PADX + MSG.getlength(s), base + 7)], fill=PURE, width=3)
    return im, (w, h)

CARD_IMG = Image.open(B.ASSETS + 'fid/kat_warga-emas.webp').convert('RGB')
def info_card():
    """Forwarded card from the site (received from Angah)."""
    w = 620
    ih = 250
    body = ["Tak mampu puasa & tiada harapan", "untuk ganti:"]
    h = 40 + 34 + ih + 18 + 46 + len(body) * 44 + 58 + 40 + 40
    im, d = shadowed(w, h, PURE, TAIL, 'l')
    x = TAIL + PADX
    d.text((x, PADY + 2), 'Angah', font=NAMEF, fill=TEAL, anchor='lt')
    d.text((x, PADY + 42), '↪ Dikongsi', font=SMALL, fill=GREY, anchor='lt')
    y = PADY + 84
    ci = CARD_IMG.resize((w - 2 * PADX, int(CARD_IMG.height * (w - 2 * PADX) / CARD_IMG.width)), Image.LANCZOS)
    top = max(0, (ci.height - ih) // 2)
    ci = ci.crop((0, top, ci.width, top + ih)).convert('RGBA')
    m = Image.new('L', ci.size, 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, ci.width - 1, ci.height - 1), 20, fill=255)
    ci.putalpha(m); im.alpha_composite(ci, (x, y))
    y += ih + 18
    d.text((x, y), 'Warga emas uzur', font=font('InterDisplay-Bold', 38), fill=NAVY, anchor='lt'); y += 54
    for ln in body:
        d.text((x, y), ln, font=font('Inter-Medium', 32), fill=GREY, anchor='lt'); y += 44
    y += 6
    pill = "Tak perlu qada' → fidyah sahaja"
    pf = font('InterDisplay-SemiBold', 30)
    pw = pf.getlength(pill) + 40
    d.rounded_rectangle((x, y, x + pw, y + 52), 26, fill=TINT)
    d.text((x + 20, y + 26), pill, font=pf, fill=BLUE, anchor='lm'); y += 70
    d.text((x, y), 'bayarfidyahonline.com', font=SMALL, fill=GREY, anchor='lt')
    return im, (w, h)

def dots_bubble():
    im = Image.new('RGBA', (150 + TAIL, 84), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((TAIL, 0, TAIL + 150, 78), 32, fill=PURE)
    d.polygon([(TAIL + 36, 78), (0, 78), (TAIL + 6, 50)], fill=PURE)
    return im
DOTS = dots_bubble()

_av = {}
def avatar(who):
    if who not in _av:
        im = Image.new('RGBA', (AV, AV), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
        d.ellipse((0, 0, AV - 1, AV - 1), fill=PEOPLE[who][1])
        d.text((AV / 2, AV / 2 + 1), PEOPLE[who][0][0], font=font('InterDisplay-Bold', 28), fill=PURE, anchor='mm')
        _av[who] = im
    return _av[who]

# ---------------- script ----------------
# (who, kind, text): who K/A received, M = me (sent). kind: t text, c info card, l link
MSGS = [('K', 't', 'Mak dah tak larat puasa tahun ni... 😢'),
        ('A', 't', 'Doktor pun cakap mak tak boleh puasa dah. Umur dah 78 😔'),
        ('M', 't', 'Habis tu, kena ganti ke nanti?'),
        ('K', 't', 'Akak pun tak pasti...'),
        ('A', 'c', ''),
        ('M', 't', 'Oh, fidyah je. Kira macam mana?'),
        ('A', 't', 'Sehari secupak beras, RM4'),
        ('A', 't', '30 hari × RM4 = RM120 👍'),
        ('K', 't', 'Jom kita bayarkan untuk mak 🤲'),
        ('M', 'l', ''),
        ('M', 't', 'Dah bayar untuk mak. Resit masuk emel 📩')]

HOOK_END = 3.4
CPS = 0.032
GAP_SAME, GAP_SWITCH = 12, 26
SFX = []
items = []
t = HOOK_END + 0.5
y = 24
prev = None
pending_read = []
for who, kind, text in MSGS:
    if prev is not None: y += GAP_SAME if prev == who else GAP_SWITCH
    it = dict(who=who, kind=kind, text=text, top=y, first=(prev != who))
    if kind == 'c': it['img'], it['edge'] = info_card()
    elif kind == 'l': it['img'], it['edge'] = link_bubble()
    else: it['img'], it['edge'] = bubble(who, text, it['first'])
    it['h'] = it['img'].height
    if who == 'M':
        typed = text if kind == 't' else 'www.bayarfidyahonline.com'
        it['typed'] = typed
        it['type0'] = t; it['type1'] = t + len(typed) * CPS
        for i, c in enumerate(typed):
            if i % 2 == 0 and c != ' ': SFX.append((t + i * CPS, 'key', None))
        it['at'] = it['type1'] + 0.2
        SFX.append((it['at'] - 0.06, 'whoosh', None)); SFX.append((it['at'] + 0.2, 'pop_sent', None))
        pending_read.append(it)
        t = it['at'] + 0.5
    else:
        it['dots0'] = t + 0.15
        it['at'] = it['dots0'] + (1.0 if kind == 'c' else 0.55 + min(len(text), 50) * 0.01)
        for j in range(int((it['at'] - it['dots0']) / 0.4)): SFX.append((it['dots0'] + 0.1 + j * 0.4, 'blip', j))
        SFX.append((it['at'], 'pop_reply', None))
        for p in pending_read: p['read'] = it['dots0']
        if pending_read: SFX.append((it['dots0'], 'ticks', None))
        pending_read = []
        # reading time for the viewer
        t = it['at'] + (3.2 if kind == 'c' else 0.55 + len(text) * 0.03)
        if text.startswith('30 hari'):
            # big calculation card pops over the chat
            CALC0 = it['at'] + 0.6; CALC1 = CALC0 + 3.4
            SFX.append((CALC0, 'whoosh', None)); SFX.append((CALC0 + 0.75, 'ding', None))
            t = CALC1 + 0.3
    items.append(it)
    y += it['h']
    prev = who

last = items[-1]
for p in pending_read: p['read'] = last['at'] + 0.6
SFX.append((last['at'] + 0.6, 'ticks', None))
REACT_T = last['at'] + 1.0
SFX.append((REACT_T, 'heart', None)); SFX.append((REACT_T + 0.35, 'heart', None))
OUT0 = REACT_T + 2.0
DUR = round(OUT0 + 6.0, 1)
N = int(round(DUR * FPS))

VIS_BOTTOM = AH - 18
jumps, cur = [], 0.0
for it in items:
    if it['who'] != 'M':
        tgt = max(0.0, it['top'] + 90 - VIS_BOTTOM)
        if tgt > cur: jumps.append((it['dots0'], tgt - cur)); cur = tgt
    tgt = max(0.0, it['top'] + it['h'] + (40 if it is last else 0) - VIS_BOTTOM)
    if tgt > cur: jumps.append((it['at'], tgt - cur)); cur = tgt
def scroll(tt): return sum(dv * ease_io((tt - t0) / 0.45) for t0, dv in jumps)

# ---------------- static layers ----------------
def wallpaper():
    im = Image.new('RGBA', (AW, AH), (236, 242, 250, 255)); d = ImageDraw.Draw(im)
    for r_ in range(0, AH, 40):
        for c_ in range(0, AW + 40, 40):
            x = c_ + (20 if (r_ // 40) % 2 else 0)
            d.ellipse((x - 3, r_ - 3, x + 3, r_ + 3), fill=(214, 225, 240))
    return im
WALL = wallpaper()

def header(status, typing):
    im = Image.new('RGBA', (AW, HDR), PURE + (255,)); d = ImageDraw.Draw(im)
    d.line([(46, 64), (28, 46), (46, 28)], fill=BLUE, width=5, joint='curve')
    d.line([(46, 64), (28, 82), (46, 100)], fill=BLUE, width=0)
    # group avatar: three overlapping circles
    for j, who in enumerate('KAM'):
        cx = 92 + j * 26
        d.ellipse((cx - 26, 38, cx + 26, 90), fill=PEOPLE[who][1], outline=PURE, width=4)
    d.text((196, 46), 'Adik-beradik', font=font('InterDisplay-Bold', 36), fill=NAVY, anchor='lm')
    hx = 196 + font('InterDisplay-Bold', 36).getlength('Adik-beradik ') + 2
    im.alpha_composite(emoji_img('❤', 34), (int(hx), 30))
    d.text((196, 92), status, font=SMALL, fill=BLUE if typing else GREY, anchor='lm')
    for j in range(3): d.ellipse((AW - 50, 40 + j * 16, AW - 40, 50 + j * 16), fill=BLUE)
    d.line([(0, HDR - 1), (AW, HDR - 1)], fill=LINE, width=2)
    return im
_hdr = {}
def get_header(status, typing):
    k = (status, typing)
    if k not in _hdr: _hdr[k] = header(status, typing)
    return _hdr[k]

BARF = font('Inter-Medium', 34)
def bar(text):
    im = Image.new('RGBA', (AW, BAR), PURE + (255,)); d = ImageDraw.Draw(im)
    d.line([(0, 0), (AW, 0)], fill=LINE, width=2)
    d.rounded_rectangle((24, 18, AW - 120, BAR - 18), 36, fill=(243, 246, 250), outline=LINE, width=2)
    if text:
        s = text
        while tw(s, BARF) > AW - 200: s = s[1:]
        draw_rich(im, 56, 68, s, BARF, NAVY)
        cx = 56 + tw(s, BARF) + 4
        d.rectangle((cx, 34, cx + 3, 76), fill=BLUE)
    else:
        d.text((56, BAR / 2), 'Taip mesej', font=BARF, fill=(160, 172, 190), anchor='lm')
    d.ellipse((AW - 100, 16, AW - 24, 92), fill=BLUE)
    d.polygon([(AW - 76, 36), (AW - 40, 54), (AW - 76, 72), (AW - 68, 54)], fill=PURE)
    return im

def win_mask():
    m = Image.new('L', (AW, PB - PT), 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, AW - 1, PB - PT - 1), 40, fill=255)
    return m
WMASK = win_mask()
def win_shadow():
    sh = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((PX0, PT + 16, PX1, PB + 16), 40, fill=(15, 40, 80, 60))
    return sh.filter(ImageFilter.GaussianBlur(22))
WSHADOW = win_shadow()

def ticks(d, x, y, read):
    col = READ + (255,) if read else (255, 255, 255, 150)
    for off in (0, 13):
        d.line([(x + off, y), (x + off + 7, y + 8), (x + off + 22, y - 8)], fill=col, width=4, joint='curve')

def react_pill():
    im = Image.new('RGBA', (124, 56), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, 123, 55), 28, fill=PURE, outline=LINE, width=2)
    im.alpha_composite(emoji_img('❤', 30), (16, 13)); im.alpha_composite(emoji_img('🤲', 30), (52, 13))
    d.text((106, 28), '2', font=SMALL, fill=GREY, anchor='mm')
    return im
REACT = react_pill()

# ---------------- chat frame ----------------
def chat(tt):
    area = WALL.copy()
    sc = scroll(tt)
    status, typing = 'Kak Long, Angah, Anda', False
    for idx, it in enumerate(items):
        who, kind = it['who'], it['kind']
        top = it['top'] - sc
        if who != 'M' and it['dots0'] <= tt < it['at']:
            status, typing = f'{PEOPLE[who][0]} sedang menaip...', True
            k = spring((tt - it['dots0']) / 0.5)
            dm = DOTS.copy(); dd = ImageDraw.Draw(dm)
            for j in range(3):
                ph = (tt - it['dots0']) * 6 - j * 0.9
                bounce = max(0, math.sin(ph)) * 10
                dd.ellipse((TAIL + 40 + j * 34 - 9, 39 - 9 - bounce, TAIL + 40 + j * 34 + 9, 39 + 9 - bounce), fill=(150, 162, 182))
            if k > 0.02:
                sm = dm.resize((max(1, int(dm.width * k)), max(1, int(dm.height * k))), Image.BICUBIC)
                area.alpha_composite(sm, (RX, int(top + dm.height - sm.height)))
                if it['first']: area.alpha_composite(avatar(who), (26, int(top + dm.height - AV - 6)))
        if tt < it['at']: continue
        age = tt - it['at']
        k = spring(age / 0.55)
        img = it['img']
        if who == 'M':
            fly = ease_out(age / 0.3)
            fx, fy = AW - 24, top + img.height
            sx, sy = AW - 60, AH + 60
            ax, ay = sx + (fx - sx) * fly, sy + (fy - sy) * fly
            kk = max(k, 0.35 * (1 - fly)) if age < 0.3 else k
            sm = img if abs(kk - 1) < 0.01 else img.resize((max(1, int(img.width * kk)), max(1, int(img.height * kk))), Image.BICUBIC)
            area.alpha_composite(sm, (int(ax - sm.width), int(ay - sm.height)))
            if age > 0.35:
                d = ImageDraw.Draw(area)
                ex, eh = it['edge']
                ticks(d, int(fx - img.width + ex - 72), int(top + eh - 26), tt >= it.get('read', 1e9))
            if it is last and tt >= REACT_T:
                rk = spring((tt - REACT_T) / 0.6)
                if rk > 0.05:
                    rs = REACT.resize((max(1, int(REACT.width * rk)), max(1, int(REACT.height * rk))), Image.BICUBIC)
                    area.alpha_composite(rs, (int(fx - img.width + 30), int(top + img.height - 18)))
        else:
            sm = img if abs(k - 1) < 0.01 else img.resize((max(1, int(img.width * k)), max(1, int(img.height * k))), Image.BICUBIC)
            area.alpha_composite(sm, (RX, int(top + img.height - sm.height)))
            nxt = items[idx + 1] if idx + 1 < len(items) else None
            if it['first'] or True:
                # avatar sits by the last bubble of a run
                if nxt is None or nxt['who'] != who or tt < nxt['at']:
                    area.alpha_composite(avatar(who), (26, int(top + img.height - AV - 6)))
    win = Image.new('RGBA', (AW, PB - PT), (0, 0, 0, 0))
    win.alpha_composite(get_header(status, typing), (0, 0))
    win.alpha_composite(area, (0, HDR))
    bt = ''
    for it in items:
        if it['who'] == 'M' and it['type0'] <= tt < it['at']:
            n = int((tt - it['type0']) / CPS) + 1
            bt = it['typed'][:min(n, len(it['typed']))]
    win.alpha_composite(bar(bt), (0, PB - PT - BAR))
    win.putalpha(Image.composite(win.getchannel('A'), Image.new('L', win.size, 0), WMASK))
    return win

# ---------------- hook ----------------
def series_pill(f, a):
    s = 'SIRI FIDYAH · 1/5'
    fnt = font('Inter-SemiBold', 26)
    w = int(fnt.getlength(s)) + 44
    lay = layer(); d = ImageDraw.Draw(lay)
    d.rounded_rectangle((W / 2 - w / 2, Y(0), W / 2 + w / 2, Y(48)), 24, fill=TINT + (255,))
    d.text((W / 2, Y(24)), s, font=fnt, fill=BLUE + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, a))

def notif():
    w, h = 900, 168
    im = Image.new('RGBA', (w + 40, h + 50), (0, 0, 0, 0))
    sh = Image.new('RGBA', im.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((20, 30, 20 + w, 30 + h), 36, fill=(15, 40, 80, 60))
    im.alpha_composite(sh.filter(ImageFilter.GaussianBlur(14)))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle((20, 16, 20 + w, 16 + h), 36, fill=PURE)
    d.rounded_rectangle((48, 44, 108, 104), 16, fill=BLUE)
    d.ellipse((62, 58, 94, 88), outline=PURE, width=5); d.polygon([(66, 84), (60, 98), (76, 88)], fill=PURE)
    d.text((128, 58), 'Adik-beradik', font=font('InterDisplay-Bold', 32), fill=NAVY, anchor='lm')
    d.text((20 + w - 30, 58), 'sekarang', font=SMALL, fill=GREY, anchor='rm')
    im2 = im
    d.text((128, 104), 'Kak Long:', font=font('InterDisplay-SemiBold', 32), fill=CORAL, anchor='lm')
    draw_rich(im2, 128 + font('InterDisplay-SemiBold', 32).getlength('Kak Long: '), 116,
              'Mak dah tak larat puasa tahun ni... 😢', font('Inter-Medium', 32), NAVY)
    d.text((128, 152), '+2 mesej baru', font=SMALL, fill=GREY, anchor='lm')
    return im
NOTIF = notif()
HOOK_F = font('InterDisplay-Bold', 92)

def scene_hook(f, tt):
    a, b, s = life(tt, 0.0, HOOK_END + 0.3, fin=0.5)
    series_pill(f, a)
    for i, ln in enumerate(['Mak dah tak larat', 'puasa tahun ni...']):
        ka, kb, ks = life(tt, 0.15 + i * 0.25, HOOK_END + 0.3, fin=0.5)
        place(f, sprite(ln, HOOK_F, NAVY, grad='green' if i == 1 else None), W / 2, Y(200 + i * 110), ka, ks, kb)
    ka, kb, _ = life(tt, 0.9, HOOK_END + 0.3, fin=0.5)
    drop = (1 - ease_out((tt - 0.9) / 0.5)) * -60
    place(f, NOTIF, W / 2, Y(560) + drop, ka, 1, kb)
    qa, qb, qs = life(tt, 1.6, HOOK_END + 0.3, fin=0.5)
    place(f, sprite("Kena ganti, atau bayar fidyah?", font('InterDisplay-SemiBold', 46), GREY), W / 2, Y(800), qa, qs, qb)

# ---------------- calculation card overlay ----------------
def calc_card(tt):
    w, h = 820, 560
    im = Image.new('RGBA', (w + 60, h + 70), (0, 0, 0, 0))
    sh = Image.new('RGBA', im.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((30, 46, 30 + w, 46 + h), 44, fill=(15, 40, 80, 70))
    im.alpha_composite(sh.filter(ImageFilter.GaussianBlur(18)))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle((30, 26, 30 + w, 26 + h), 44, fill=PURE)
    cx = 30 + w / 2
    d.text((cx, 96), 'FIDYAH UNTUK MAK', font=font('Inter-SemiBold', 30), fill=BLUE, anchor='mm')
    d.text((cx, 196), '30 hari × RM4', font=font('InterDisplay-Bold', 78), fill=NAVY, anchor='mm')
    d.line([(cx - 260, 268), (cx + 260, 268)], fill=LINE, width=3)
    tot = sprite('RM120', font('InterDisplay-Bold', 150), grad='green')
    k = clamp((tt - CALC0 - 0.7) / 0.5)
    if k > 0:
        ts = alpha_img(tot, k)
        im.alpha_composite(ts, (int(cx - ts.width / 2), int(375 - ts.height / 2)))
    d.text((cx, 500), 'sehari secupak beras · fidyah sahaja', font=font('Inter-Medium', 32), fill=GREY, anchor='mm')
    return im

def scene_calc(f, tt):
    a = clamp(min((tt - CALC0) / 0.35, (CALC1 - tt) / 0.35))
    if a <= 0: return
    dim = Image.new('RGBA', (W, H), (12, 26, 48, int(110 * a)))
    f.alpha_composite(dim)
    sc = spring((tt - CALC0) / 0.6) if tt < CALC1 - 0.35 else 1 - 0.08 * (1 - a)
    place(f, calc_card(tt), W / 2, Y(500), a, max(0.05, sc))

# ---------------- outro (same for the whole series) ----------------
def url_pill():
    fnt = font('InterDisplay-Bold', 46); s_ = 'www.bayarfidyahonline.com'
    w = int(fnt.getlength(s_)) + 90
    im = Image.new('RGBA', (w, 104), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, w - 1, 103), 52, fill=BLUE)
    d.text((w / 2, 52), s_, font=fnt, fill=PURE, anchor='mm')
    return im
PILL = url_pill()
def scene_outro(f, tt):
    a, b, s = life(tt, OUT0 + 0.1, DUR + 1)
    series_pill(f, a)
    for i, ln in enumerate(['Jangan Tangguh,', 'Puasa Hutang kita', 'Dengan Allah']):
        ka, kb, ks = life(tt, OUT0 + 0.2 + i * 0.3, DUR + 1)
        place(f, sprite(ln, H2, grad='green' if i == 2 else None), W / 2, Y(220 + i * 100), ka, ks, kb)
    pa = life(tt, OUT0 + 1.3, DUR + 1)[0]
    x = max(0, tt - OUT0 - 1.3)
    sp = 1 - math.exp(-x * 9) * math.cos(x * 20) if tt >= OUT0 + 1.3 else 0
    place(f, PILL, W / 2, Y(620), pa, max(0.01, sp))
    la, lb, _ = life(tt, OUT0 + 1.9, DUR + 1)
    place(f, sprite('Platform oleh', TINY, GREY), W / 2, Y(790), la, 1, lb)
    place(f, B.LOGO, W / 2, Y(880), la, 1, lb)

def render(tt):
    f = B.bg(tt)
    if tt < HOOK_END + 0.3: scene_hook(f, tt)
    if HOOK_END - 0.1 <= tt < OUT0:
        win = chat(tt)
        a = clamp(min((tt - HOOK_END + 0.1) / 0.5, (OUT0 - tt) / 0.45))
        rise = (1 - ease_out((tt - HOOK_END + 0.1) / 0.6)) * 120 - (1 - clamp((OUT0 - tt) / 0.45)) * 80
        f.alpha_composite(alpha_img(WSHADOW, a))
        f.alpha_composite(alpha_img(win, a), (PX0, int(PT + rise)))
        if CALC0 <= tt <= CALC1: scene_calc(f, tt)
    if tt >= OUT0: scene_outro(f, tt)
    out = f.convert('RGB')
    fade = clamp((tt - (DUR - 0.6)) / 0.6)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H), (248, 251, 255)), fade)
    return out

# ---------------- audio: SFX + warm lo-fi bed ----------------
R = np.random.default_rng(7)
def env(n, a=0.002, d=0.08):
    t_ = np.arange(n) / SR
    return np.minimum(1, t_ / a) * np.exp(-t_ / d)
def tone(f0, f1, dur, d=0.06, harm=(1,), a=0.002):
    n = int(dur * SR); t_ = np.arange(n) / SR
    fr = f0 * (f1 / f0) ** (t_ / dur)
    ph = 2 * np.pi * np.cumsum(fr) / SR
    x = sum(np.sin(ph * h_) / (i + 1) ** 1.5 for i, h_ in enumerate(harm))
    return x * env(n, a, d)
def noise(dur): return R.normal(0, 1, int(dur * SR))
def fft_lp(x, cut):
    sp = np.fft.rfft(x); fr = np.fft.rfftfreq(len(x), 1 / SR)
    return np.fft.irfft(sp / np.sqrt(1 + (fr / cut) ** 4), len(x))
def sfx(name, arg):
    if name == 'key':
        n = noise(0.02); return np.diff(np.r_[0, n]) * env(len(n), 0.0005, 0.003) * 0.06
    if name == 'whoosh':
        n = noise(0.32); n = fft_lp(n, 2200) - fft_lp(n, 250)
        return n * np.sin(np.pi * np.linspace(0, 1, len(n))) ** 2 * 1.2
    if name == 'pop_sent': return tone(820, 1500, 0.09, 0.035, (1, 2)) * 0.6
    if name == 'pop_reply': return tone(380, 620, 0.12, 0.05, (1, 2, 3)) * 0.65
    if name == 'blip': return tone(1250 + 120 * (arg % 3), 1300 + 120 * (arg % 3), 0.05, 0.015) * 0.18
    if name == 'ticks':
        c = tone(3000, 3000, 0.012, 0.003) * 0.25; out = np.zeros(int(0.1 * SR))
        out[:len(c)] += c; out[int(0.07 * SR):int(0.07 * SR) + len(c)] += c; return out
    if name == 'ding':
        x = np.zeros(int(1.2 * SR))
        for j, fq in enumerate((1047, 1319, 1568)):
            s_ = tone(fq, fq, 0.9, 0.35, (1, 2)) * 0.22; i0 = int(j * 0.07 * SR); x[i0:i0 + len(s_)] += s_
        return x
    if name == 'heart':
        n = int(0.7 * SR); x = np.zeros(n)
        p = tone(500, 1400, 0.18, 0.07, (1, 2)); x[:len(p)] += p * 0.6
        for j, fq in enumerate((2400, 3000, 3600, 4500)):
            s_ = tone(fq, fq * 1.02, 0.12, 0.04) * 0.14; i0 = int((0.1 + j * 0.06) * SR); x[i0:i0 + len(s_)] += s_
        return x
    return np.zeros(1)

BEAT = 60 / 78; BARLEN = BEAT * 4
def lofi(dur):
    n = int(dur * SR)
    L = np.zeros(n); Rr = np.zeros(n)
    def hz(m): return 440 * 2 ** ((m - 69) / 12)
    def add(x, st, g=1.0, pan=0.5):
        i0 = int(st * SR)
        if i0 >= n: return
        x = x[:n - i0]
        L[i0:i0 + len(x)] += x * g * (1 - pan); Rr[i0:i0 + len(x)] += x * g * pan
    # warm, a little tender: Fmaj7 Am7 Dm7 Bbmaj7
    chords = [(53, (57, 60, 64, 65)), (57, (60, 64, 67, 69)), (50, (53, 57, 60, 64)), (46, (50, 53, 57, 58))]
    for b_ in range(int(dur / BARLEN) + 1):
        root, notes = chords[b_ % 4]; st = b_ * BARLEN
        for hit in (0, 2.5):
            m = int((BEAT * 1.8) * SR); tt_ = np.arange(m) / SR
            ep = np.zeros(m)
            for nt in notes:
                fq = hz(nt)
                ep += (np.sin(2 * np.pi * fq * tt_) + 0.25 * np.sin(2 * np.pi * fq * 2 * tt_) * np.exp(-tt_ * 6)) * np.exp(-tt_ * 1.3)
            ep *= (1 + 0.15 * np.sin(2 * np.pi * 4.5 * tt_)) * np.minimum(1, tt_ / 0.01)
            add(ep * 0.05, st + hit * BEAT + 0.02, 1.0, 0.45)
        for bt, ln in ((0, 1.2), (1.5, 0.4), (2, 1.0), (3.5, 0.4)):
            m = int(ln * SR); tt_ = np.arange(m) / SR
            add(np.sin(2 * np.pi * hz(root - 12) * tt_) * np.minimum(1, tt_ / 0.01) * np.exp(-tt_ * 2.5) * 0.22, st + bt * BEAT)
        if st < 1.0: continue          # hook bar: keys + bass only
        for bt in (0, 2.5):
            m = int(0.3 * SR); tt_ = np.arange(m) / SR
            fq = 50 + 60 * np.exp(-tt_ * 30)
            add(np.sin(2 * np.pi * np.cumsum(fq) / SR) * np.exp(-tt_ * 10) * 0.3, st + bt * BEAT)
        for bt in (1, 3):
            m = int(0.25 * SR); tt_ = np.arange(m) / SR
            sn = fft_lp(R.normal(0, 1, m), 2500) * np.exp(-tt_ * 16) + np.sin(2 * np.pi * 190 * tt_) * np.exp(-tt_ * 20) * 0.4
            add(sn * 0.14, st + bt * BEAT + 0.015)
        for e8 in range(8):
            m = int(0.05 * SR); tt_ = np.arange(m) / SR
            hh = np.diff(np.r_[0, R.normal(0, 1, m)]) * np.exp(-tt_ * 90)
            add(hh * (0.03 if e8 % 2 else 0.045), st + e8 * BEAT / 2 + (0.06 if e8 % 2 else 0), 1.0, 0.6)
    mix = np.stack([L, Rr], 1)
    crk = np.zeros(n); pos = R.integers(0, n, int(dur * 8)); crk[pos] = R.uniform(-1, 1, len(pos)) * 0.2
    mix += (crk + fft_lp(R.normal(0, 1, n), 1500) * 0.004)[:, None]
    for ch in range(2): mix[:, ch] = fft_lp(mix[:, ch], 4200)
    tt_ = np.arange(n) / SR
    mix *= (np.minimum(1, tt_ / 0.8) * np.clip((dur - tt_) / 2.0, 0, 1))[:, None]
    return mix

def build_audio(path):
    n = int(DUR * SR)
    fx = np.zeros(n)
    for tt, name, arg in SFX:
        x = sfx(name, arg); i0 = int(tt * SR)
        if i0 >= n: continue
        fx[i0:i0 + len(x)] += x[:n - i0]
    bed = lofi(DUR)
    bed *= 0.34 / max(1e-6, np.percentile(np.abs(bed), 99.9))
    out = np.tanh((bed + fx[:, None] * 0.9) * 1.1) * 0.92
    with wave.open(path, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((out * 32767).astype('<i2').tobytes())

if __name__ == '__main__':
    print('duration %.1fs, calc %.1f-%.1f, outro %.1f' % (DUR, CALC0, CALC1, OUT0))
    for it in items: print('  %5.2f %s %s' % (it['at'], it['who'], it['text'] or it['kind']))
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{B.SP}s1_{B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    build_audio(WAV)
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-i', WAV, '-map', '0:v', '-map', '1:a', '-c:v', 'libx264', '-preset', 'medium', '-crf', '21',
                          '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '192k', '-shortest', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
