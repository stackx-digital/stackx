"""Siri Fidyah 4/5: Lewat qada' puasa (~51s). Apple-style light blue, laid out in the Meta safe box.
FMT=916 (1080x1920 Reels/Stories) or FMT=45 (1080x1350 Feed).
Usage: FMT=916 python3 fidyah_siri4_lewat_qada.py [preview seconds ...]"""
import math, sys, subprocess
import numpy as np
from PIL import Image, ImageDraw
import imageio_ffmpeg
import fidyah_meta_ads as B          # shared look: background, fonts, sprites, phone with real calculator shots
from fidyah_meta_ads import (W, H, FPS, BY, Y, sprite, place, life, layer, alpha_img, font, clamp,
                             ease_out, ease_io, roll, BLUE, NAVY, GREY, PURE, LINE, TINT, CAP, SMALL, TINY, BIG, H2, ROW)

DUR = 51.0
N = int(FPS * DUR)
OUT = B.SP + f'siri4_lewat_qada_{B.FMT}_silent.mp4'
CORAL = (232, 84, 60)
CUT = [0, 3.5, 13.0, 27.0, 40.0, 45.5]   # hook, rule, timeline, calculator, warning, outro

def series_pill(f, a):
    s = 'SIRI FIDYAH · 4/5'
    fnt = font('Inter-SemiBold', 26)
    w = int(fnt.getlength(s)) + 44
    lay = layer(); d = ImageDraw.Draw(lay)
    d.rounded_rectangle((W / 2 - w / 2, Y(0), W / 2 + w / 2, Y(48)), 24, fill=TINT + (255,))
    d.text((W / 2, Y(24)), s, font=fnt, fill=BLUE + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, a))

# ---------- 1. hook: calendar pages fly 2023 -> 2026 ----------
def cal_card(year, hijri):
    w, h = 380, 420
    im = Image.new('RGBA', (w + 30, h + 40), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((14, 22, 14 + w, 22 + h), 40, fill=(15, 40, 80, 40))
    d.rounded_rectangle((0, 0, w, h), 40, fill=PURE, outline=LINE, width=3)
    d.rounded_rectangle((0, 0, w, 110), 40, fill=BLUE); d.rectangle((0, 70, w, 110), fill=BLUE)
    for x in (110, 270): d.rounded_rectangle((x - 9, -18, x + 9, 28), 9, fill=NAVY)
    d.text((w / 2, 58), 'RAMADAN', font=font('InterDisplay-Bold', 40), fill=PURE, anchor='mm')
    d.text((w / 2, 245), str(year), font=font('InterDisplay-Bold', 132), fill=NAVY, anchor='mm')
    d.text((w / 2, 350), hijri, font=font('Inter-Medium', 34), fill=GREY, anchor='mm')
    return im
CALS = [cal_card(y, h) for y, h in ((2023, '1444H'), (2024, '1445H'), (2025, '1446H'), (2026, '1447H'))]

def scene_hook(f, t):
    a, b, s = life(t, 0.1, 3.5, fin=0.5, fout=0.4)
    series_pill(f, a)
    place(f, sprite('Puasa 2023', BIG), W / 2, Y(150), a, s, b)
    place(f, sprite('tak ganti lagi?', BIG, grad='green'), W / 2, Y(275), a, s, b)
    flips = [1.3, 1.75, 2.2]
    idx = sum(t >= x for x in flips)
    cx, cy = W / 2, Y(640)
    if idx < 3: place(f, CALS[idx + 1], cx, cy, a)                    # page underneath
    place(f, CALS[idx], cx, cy, a) if idx == 3 else None
    if idx < 3:
        k = clamp((t - (flips[idx] - 0.35)) / 0.35)                   # current page flies off up-left
        if k <= 0: place(f, CALS[idx], cx, cy, a, 1.0)
        else:
            pg = CALS[idx].rotate(25 * k, expand=True, resample=Image.BICUBIC)
            place(f, pg, cx - 420 * k, cy - 260 * k, a * (1 - k), 1 - 0.2 * k)
    if idx == 3:
        k = ease_out((t - 2.2) / 0.4)
        lay = layer(); d = ImageDraw.Draw(lay)
        d.rounded_rectangle((cx + 120, cy - 240, cx + 300, cy - 180), 30, fill=CORAL + (255,))
        d.text((cx + 210, cy - 210), '3 tahun!', font=font('InterDisplay-Bold', 32), fill=PURE + (255,), anchor='mm')
        f.alpha_composite(alpha_img(lay, a * k))

# ---------- 2. the rule ----------
def chip(text, ok=True):
    fnt = font('InterDisplay-Bold', 44)
    w = int(fnt.getlength(text)) + 130
    im = Image.new('RGBA', (w, 104), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, w - 1, 103), 52, fill=PURE, outline=LINE, width=3)
    d.ellipse((22, 22, 82, 82), fill=BLUE)
    d.line([(38, 53), (49, 64), (68, 42)], fill=PURE, width=7, joint='curve')
    d.text((100, 52), text, font=fnt, fill=NAVY, anchor='lm')
    return im
CHIP_Q, CHIP_F = chip("Qada' puasa"), chip('Bayar fidyah')

def scene_rule(f, t):
    a, b, s = life(t, 3.5, 13.0)
    place(f, sprite("Lewat qada'?", BIG, grad='green'), W / 2, Y(120), a, s, b)
    a2, b2, _ = life(t, 4.4, 13.0)
    for i, ln in enumerate(["Bila qada' puasa dilewatkan", 'sampai masuk Ramadan berikutnya', 'tanpa uzur yang dibenarkan,']):
        place(f, sprite(ln, CAP, GREY), W / 2, Y(280 + i * 58), a2, 1, b2)
    a3, b3, _ = life(t, 6.6, 13.0)
    place(f, sprite('anda perlu buat dua-dua:', font('InterDisplay-SemiBold', 46)), W / 2, Y(500), a3, 1, b3)
    for i, (c, st) in enumerate(((CHIP_Q, 7.6), (CHIP_F, 8.4))):
        k = B.life(t, st, 13.0, fin=0.5)[0]
        sp = 1 - math.exp(-max(0, t - st) * 9) * math.cos(max(0, t - st) * 20) if t >= st else 0
        place(f, c, W / 2, Y(640 + i * 140), k, max(0.01, sp))
    a4, b4, _ = life(t, 9.6, 13.0)
    place(f, sprite("Rujuk pihak berkuasa agama negeri jika ragu.", TINY, GREY), W / 2, Y(930), a4, 1, b4)

# ---------- 3. timeline: the fidyah doubles every Ramadan that passes ----------
NODES = [(2023, '1444H'), (2024, '1445H'), (2025, '1446H'), (2026, '1447H')]
def scene_timeline(f, t):
    a, b, s = life(t, 13.0, 27.0)
    place(f, sprite('Fidyah berganda', H2, grad='green'), W / 2, Y(70), a, s, b)
    place(f, sprite('ikut bilangan tahun.', H2, grad='green'), W / 2, Y(165), a, s, b)
    xs = [150, 410, 670, 930]; ly = Y(470)
    lay = layer(); d = ImageDraw.Draw(lay)
    d.line([(xs[0], ly), (xs[-1], ly)], fill=LINE + (255,), width=10)
    prog = clamp((t - 15.0) / 6.0)                                     # progress line sweeps 2023 -> 2026
    d.line([(xs[0], ly), (xs[0] + (xs[-1] - xs[0]) * ease_io(prog), ly)], fill=BLUE + (255,), width=10)
    for i, (yr, hj) in enumerate(NODES):
        lit = i == 0 or prog >= i / 3 - 0.001
        col = (CORAL if i == 0 else BLUE) if lit else LINE
        d.ellipse((xs[i] - 26, ly - 26, xs[i] + 26, ly + 26), fill=PURE + (255,), outline=col + (255,), width=8)
        if lit: d.ellipse((xs[i] - 12, ly - 12, xs[i] + 12, ly + 12), fill=col + (255,))
        d.text((xs[i], ly + 64), str(yr), font=font('InterDisplay-Bold', 40), fill=NAVY + (255,), anchor='mm')
        d.text((xs[i], ly + 108), hj, font=font('Inter-Medium', 28), fill=GREY + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, a))
    # 2023 note
    na = life(t, 14.0, 27.0)[0]
    lay = layer(); d = ImageDraw.Draw(lay)
    d.rounded_rectangle((xs[0] - 110, ly - 150, xs[0] + 110, ly - 80), 35, fill=CORAL + (255,))
    d.text((xs[0], ly - 115), 'tinggal 7 hari', font=font('InterDisplay-Bold', 30), fill=PURE + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, na))
    # multiplier badges pop as each Ramadan passes
    for i in range(1, 4):
        st = 15.0 + 6.0 * i / 3
        if t < st: continue
        sp = 1 - math.exp(-(t - st) * 9) * math.cos((t - st) * 20)
        badge = sprite(f'×{i}', font('InterDisplay-Bold', 64), grad='green')
        place(f, badge, xs[i], ly - 115, a, max(0.01, sp))
    fa, fb, _ = life(t, 21.8, 27.0)
    place(f, sprite('(Hari × Kadar) × Tahun', ROW), W / 2, Y(760), fa, 1, fb)
    place(f, sprite('RM4.00 sehari · kadar piawai kebangsaan', SMALL, B.MINT), W / 2, Y(840), fa, 1, fb)

# ---------- 4. real calculator: 7 days x RM4 x 3 = RM84 ----------
def scene_calc(f, t):
    a, b, s = life(t, 27.0, 40.0)
    place(f, sprite('Kalkulator kira automatik.', font('InterDisplay-Bold', 62), grad='green'), W / 2, Y(35), a, s, b)
    tp = 22.6 + (t - 27.2) * 0.75                                         # slow the recorded taps a little
    pa, pb, _ = life(t, 27.2, 40.0, fin=0.9)
    B.phone(f, tp, pa, pb, int(W / 2 - (B.SCR_W + 32) / 2), Y(185))
    ra, rb, _ = life(t, 30.7, 40.0, fin=0.5)
    eq = sprite('7 hari × RM4 × 3 tahun =', font('InterDisplay-SemiBold', 40))
    rm = sprite('RM84', font('InterDisplay-Bold', 58), grad='green')
    total = eq.width + rm.width - 60
    x0 = W / 2 - total / 2
    place(f, eq, x0 + eq.width / 2, Y(118), ra, 1, rb)
    place(f, rm, x0 + eq.width - 60 + rm.width / 2, Y(114), ra, 1, rb)

# ---------- 5. warning: it keeps growing ----------
BARS = [(2024, 28), (2025, 56), (2026, 84), (2027, 112)]
def scene_warning(f, t):
    a, b, s = life(t, 40.0, 45.5)
    place(f, sprite('Makin lama tangguh,', H2), W / 2, Y(70), a, s, b)
    place(f, sprite('makin bertambah.', H2, grad='green'), W / 2, Y(165), a, s, b)
    base_y, maxh, bw = Y(820), 480, 170
    xs = [180, 420, 660, 900]
    lay = layer(); d = ImageDraw.Draw(lay)
    d.line([(90, base_y), (W - 90, base_y)], fill=LINE + (255,), width=4)
    for i, (yr, rm) in enumerate(BARS):
        k = ease_out((t - 40.6 - i * 0.35) / 0.6)
        if k <= 0: continue
        h = maxh * rm / 112 * k
        col = CORAL if yr == 2027 else BLUE
        d.rounded_rectangle((xs[i] - bw / 2, base_y - h, xs[i] + bw / 2, base_y), 22, fill=col + (255,))
        d.rectangle((xs[i] - bw / 2, base_y - min(h, 22), xs[i] + bw / 2, base_y), fill=col + (255,))
        d.text((xs[i], base_y - h - 40), f'RM{int(round(rm * min(1, k * 1.2)))}', font=font('InterDisplay-Bold', 44), fill=col + (255,), anchor='mm')
        d.text((xs[i], base_y + 44), str(yr) if yr != 2027 else 'Tahun depan', font=font('Inter-SemiBold', 30), fill=(CORAL if yr == 2027 else GREY) + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, a))
    ga, gb, _ = life(t, 42.6, 45.5)
    place(f, sprite('Jangan tunggu ia bertambah. Qada\' & bayar sekarang.', font('Inter-SemiBold', 32), NAVY), W / 2, Y(950), ga, 1, gb)

# ---------- 6. series outro ----------
def url_pill():
    fnt = font('InterDisplay-Bold', 46); s_ = 'www.bayarfidyahonline.com'
    w = int(fnt.getlength(s_)) + 90
    im = Image.new('RGBA', (w, 104), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, w - 1, 103), 52, fill=BLUE)
    d.text((w / 2, 52), s_, font=fnt, fill=PURE, anchor='mm')
    return im
PILL = url_pill()
def scene_outro(f, t):
    a, b, s = life(t, 45.5, 52.0)
    series_pill(f, a)
    for i, ln in enumerate(['Jangan Tangguh,', 'Puasa Hutang kita', 'Dengan Allah']):
        ka, kb, ks = life(t, 45.7 + i * 0.3, 52.0)
        place(f, sprite(ln, H2, grad='green' if i == 2 else None), W / 2, Y(220 + i * 100), ka, ks, kb)
    pa = life(t, 46.8, 52.0)[0]
    sp = 1 - math.exp(-max(0, t - 46.8) * 9) * math.cos(max(0, t - 46.8) * 20) if t >= 46.8 else 0
    place(f, PILL, W / 2, Y(620), pa, max(0.01, sp))
    la, lb, _ = life(t, 47.4, 52.0)
    place(f, sprite('Platform oleh', TINY, GREY), W / 2, Y(790), la, 1, lb)
    place(f, B.LOGO, W / 2, Y(880), la, 1, lb)

def render(t):
    f = B.bg(t)
    if t < 3.9: scene_hook(f, t)
    if 3.3 <= t < 13.3: scene_rule(f, t)
    if 12.8 <= t < 27.3: scene_timeline(f, t)
    if 26.8 <= t < 40.3: scene_calc(f, t)
    if 39.8 <= t < 45.8: scene_warning(f, t)
    if t >= 45.3: scene_outro(f, t)
    out = f.convert('RGB')
    fade = clamp((t - (DUR - 0.6)) / 0.6)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H), (248, 251, 255)), fade)
    return out

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{B.SP}s4_{B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
