"""Siri Fidyah 5/5: Bagi pihak si mati, "Melunaskan hutang arwah" (~55s).
Soft storytelling on the light brand background (no brown paper), slow fades, piano + pad (synthesised here).
FMT=916 (1080x1920 Reels/Stories) or FMT=45 (1080x1350 Feed).
Usage: FMT=916 python3 fidyah_siri5_si_mati.py [preview seconds ...]"""
import math, sys, subprocess, wave
import numpy as np
from PIL import Image, ImageDraw, ImageFilter
import imageio_ffmpeg
import fidyah_meta_ads as B
from fidyah_meta_ads import (W, H, FPS, BY, Y, sprite, place, life, layer, alpha_img, font, clamp,
                             ease_out, ease_io, roll, BLUE, NAVY, GREY, PURE, LINE, TINT, H2, TINY)

DUR = 57.0
N = int(FPS * DUR)
SR = 44100
OUT = B.SP + f'siri5_si_mati_{B.FMT}.mp4'
WAV = B.SP + f'siri5_si_mati_{B.FMT}.wav'
GOLD = (214, 160, 60)
SOFT = (148, 163, 184)
#        hook  story  rule  hadith  calc  gallery  outro
CUT = [0, 5.5, 17.5, 24.5, 34.5, 43.0, 51.0, 57.0]

def series_pill(f, a):
    s = 'SIRI FIDYAH · 5/5'
    fnt = font('Inter-SemiBold', 26)
    w = int(fnt.getlength(s)) + 44
    lay = layer(); d = ImageDraw.Draw(lay)
    d.rounded_rectangle((W / 2 - w / 2, Y(0), W / 2 + w / 2, Y(48)), 24, fill=TINT + (255,))
    d.text((W / 2, Y(24)), s, font=fnt, fill=BLUE + (255,), anchor='mm')
    f.alpha_composite(alpha_img(lay, a))

def shadow_card(img, r=32, border=14):
    w, h = img.width + border * 2, img.height + border * 2
    out = Image.new('RGBA', (w + 60, h + 70), (0, 0, 0, 0))
    sh = Image.new('RGBA', out.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((30, 44, 30 + w, 44 + h), r, fill=(15, 40, 80, 55))
    out.alpha_composite(sh.filter(ImageFilter.GaussianBlur(18)))
    ImageDraw.Draw(out).rounded_rectangle((30, 30, 30 + w, 30 + h), r, fill=PURE)
    m = Image.new('L', img.size, 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, img.width - 1, img.height - 1), r - 8, fill=255)
    im = img.convert('RGBA'); im.putalpha(m)
    out.alpha_composite(im, (30 + border, 30 + border))
    return out

def crop_to(path, w, h):
    im = Image.open(path).convert('RGB')
    s = max(w / im.width, h / im.height)
    im = im.resize((int(im.width * s + 1), int(im.height * s + 1)), Image.LANCZOS)
    x, y = (im.width - w) // 2, (im.height - h) // 2
    return im.crop((x, y, x + w, y + h))

# ---------- 1. hook: the grave in morning light ----------
GRAVE = crop_to(B.ASSETS + 'images/7.webp', 620, 520)
def glow():
    g = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    yy, xx = np.mgrid[0:H // 4, 0:W // 4]
    d = ((xx - W * 0.78 / 4) / (W * 0.55 / 4)) ** 2 + ((yy - Y(120) / 4) / (W * 0.5 / 4)) ** 2
    a = (np.exp(-d) * 120).astype('uint8')
    arr = np.zeros((H // 4, W // 4, 4), 'uint8'); arr[..., 0] = 255; arr[..., 1] = 236; arr[..., 2] = 196; arr[..., 3] = a
    return Image.fromarray(arr, 'RGBA').resize((W, H), Image.BICUBIC)
GLOW = glow()
HOOK_F = font('InterDisplay-Bold', 76)

def scene_hook(f, t):
    e = CUT[1] + 0.3
    a, b, s = life(t, 0.0, e, fin=1.0)
    f.alpha_composite(alpha_img(GLOW, a * (0.7 + 0.3 * math.sin(t * 0.8))))
    series_pill(f, a)
    z = 1.0 + 0.04 * clamp(t / e)                        # slow push-in
    gi = GRAVE.resize((int(620 * z), int(520 * z)), Image.LANCZOS)
    gi = gi.crop(((gi.width - 620) // 2, (gi.height - 520) // 2, (gi.width - 620) // 2 + 620, (gi.height - 520) // 2 + 520))
    place(f, shadow_card(gi), W / 2, Y(400), a, s, b)
    for i, ln in enumerate(['Arwah ayah ada puasa', 'yang belum diganti...']):
        ka, kb, ks = life(t, 1.0 + i * 0.6, e, fin=1.0)
        place(f, sprite(ln, HOOK_F, NAVY, grad='green' if i == 1 else None), W / 2, Y(780 + i * 92), ka, ks, kb)

# ---------- 2. story: a gentle timeline ----------
STEPS = [('Ramadan terakhir', 'Sakit, tak dapat puasa 15 hari'),
         ('Selepas Ramadan', 'Alhamdulillah, sempat sihat semula'),
         ('Kemudian', "Arwah pergi, sebelum sempat qada'")]
ST_T = [CUT[1] + 0.6, CUT[1] + 3.0, CUT[1] + 5.4]
def scene_story(f, t):
    s0, e = CUT[1], CUT[2] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Kisah yang biasa berlaku', font('Inter-SemiBold', 34), BLUE), W / 2, Y(30), a, 1, b)
    x0, y0, gap = 210, Y(170), 230
    lay = layer(); d = ImageDraw.Draw(lay)
    grow = ease_io((t - ST_T[0]) / (ST_T[2] - ST_T[0] + 0.6))
    if grow > 0:
        d.line([(x0, y0), (x0, y0 + gap * 2 * grow)], fill=LINE + (255,), width=6)
    for i, (h_, s_) in enumerate(STEPS):
        k = ease_out((t - ST_T[i]) / 0.7)
        if k <= 0: continue
        yy = y0 + i * gap
        r = 22 * k
        col = (GOLD if i == 2 else BLUE) + (255,)
        d.ellipse((x0 - r, yy - r, x0 + r, yy + r), fill=col)
        d.ellipse((x0 - r * 0.4, yy - r * 0.4, x0 + r * 0.4, yy + r * 0.4), fill=PURE + (255,))
    f.alpha_composite(alpha_img(lay, a))
    for i, (h_, s_) in enumerate(STEPS):
        ka, kb, ks = life(t, ST_T[i] + 0.1, e)
        yy = y0 + i * gap
        hs = sprite(h_, font('Inter-SemiBold', 32), SOFT)
        place(f, hs, x0 + 50 + hs.width / 2 - 40, yy - 30, ka, 1, kb)
        ts = sprite(s_, font('InterDisplay-Bold', 46), NAVY)
        place(f, ts, x0 + 50 + ts.width / 2 - 40, yy + 28, ka, 1, kb)
    ka, kb, ks = life(t, ST_T[2] + 2.2, e)
    place(f, sprite('Hutang puasanya masih ada.', font('InterDisplay-Bold', 58), grad='green'), W / 2, Y(860), ka, ks, kb)

# ---------- 3. rule: when do heirs pay fidyah ----------
def rule_card(title, sub, verdict, on):
    w, h = 900, 230
    im = Image.new('RGBA', (w + 40, h + 50), (0, 0, 0, 0))
    sh = Image.new('RGBA', im.size, (0, 0, 0, 0))
    ImageDraw.Draw(sh).rounded_rectangle((20, 30, 20 + w, 30 + h), 34, fill=(15, 40, 80, 45 if on else 15))
    im.alpha_composite(sh.filter(ImageFilter.GaussianBlur(14)))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle((20, 16, 20 + w, 16 + h), 34, fill=PURE, outline=(BLUE if on else LINE), width=4 if on else 2)
    d.text((60, 62), title, font=font('InterDisplay-Bold', 40), fill=NAVY if on else GREY, anchor='lm')
    d.text((60, 112), sub, font=font('Inter-Medium', 30), fill=GREY, anchor='lm')
    vf = font('InterDisplay-SemiBold', 32)
    vw = vf.getlength(verdict) + 48
    d.rounded_rectangle((60, 150, 60 + vw, 206), 28, fill=(BLUE if on else (241, 245, 249)))
    d.text((60 + vw / 2, 178), verdict, font=vf, fill=PURE if on else GREY, anchor='mm')
    return im
RULE_A = rule_card("Sempat sihat, tapi belum qada'", 'Ada peluang ganti, tetapi meninggal dunia', 'Waris bayar fidyah', True)
RULE_B = rule_card('Sakit hingga meninggal dunia', "Tiada peluang langsung untuk qada'", 'Tiada fidyah', False)
def scene_rule(f, t):
    s0, e = CUT[2], CUT[3] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Bila waris perlu bayar?', H2, NAVY), W / 2, Y(90), a, s, b)
    ka, kb, ks = life(t, s0 + 0.8, e); place(f, RULE_A, W / 2, Y(370), ka, ks, kb)
    ka, kb, ks = life(t, s0 + 1.8, e); place(f, RULE_B, W / 2, Y(650), ka, ks, kb)
    ka, kb, _ = life(t, s0 + 3.2, e)
    place(f, sprite('Ragu? Rujuk pejabat agama negeri.', font('Inter-Medium', 28), GREY), W / 2, Y(870), ka, 1, kb)

# ---------- 4. hadith ----------
HADITH = ('Sesiapa yang mati sedangkan dia masih mempunyai puasa Ramadan, '
          'hendaklah diberi makan seorang miskin bagi setiap hari yang ditinggalkannya.')   # as quoted on the site
HF = font('PlusJakartaSans-SemiBoldItalic', 50)
HLINES = B.wrap(HADITH, HF, 860)
def scene_hadith(f, t):
    s0, e = CUT[3], CUT[4] + 0.3
    a, b, s = life(t, s0, e, fin=1.0)
    place(f, sprite('“', font('InterDisplay-Bold', 260), (210, 226, 248)), W / 2, Y(120), a, 1, b)
    y0 = Y(500) - (len(HLINES) - 1) * 72 / 2
    for i, ln in enumerate(HLINES):
        ka, kb, ks = life(t, s0 + 0.6 + i * 0.75, e, fin=1.0)
        place(f, sprite(ln, HF, NAVY), W / 2, y0 + i * 72, ka, 1, kb)
    ka, kb, _ = life(t, s0 + 0.8 + len(HLINES) * 0.75, e)
    place(f, sprite('Riwayat al-Tirmizi', font('Inter-SemiBold', 34), BLUE), W / 2, y0 + len(HLINES) * 72 + 50, ka, 1, kb)
    lay = layer(); d = ImageDraw.Draw(lay)
    k = ease_io((t - s0 - 1.0 - len(HLINES) * 0.75) / 0.8)
    if k > 0: d.line([(W / 2 - 80 * k, y0 + len(HLINES) * 72 + 10), (W / 2 + 80 * k, y0 + len(HLINES) * 72 + 10)], fill=GOLD + (255,), width=4)
    f.alpha_composite(alpha_img(lay, a))

# ---------- 5. calculation ----------
def scene_calc(f, t):
    s0, e = CUT[4], CUT[5] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Waris tunaikan bagi pihak arwah', font('InterDisplay-Bold', 54), NAVY), W / 2, Y(70), a, s, b)
    lay = layer(); d = ImageDraw.Draw(lay)
    cx0, cy0, cw, ch = W / 2 - 430, Y(170), 860, 560
    d.rounded_rectangle((cx0, cy0, cx0 + cw, cy0 + ch), 44, fill=PURE + (255,), outline=LINE + (255,), width=2)
    f.alpha_composite(alpha_img(lay.filter(ImageFilter.GaussianBlur(0)), a))
    rows = [('Hari tertinggal', '15 hari', s0 + 0.7), ('Kadar sehari', 'RM4', s0 + 1.4)]
    for i, (k_, v_, st) in enumerate(rows):
        ka, kb, _ = life(t, st, e)
        yy = cy0 + 90 + i * 100
        ks = sprite(k_, font('Inter-Medium', 38), GREY); vs = sprite(v_, font('InterDisplay-Bold', 46), NAVY)
        place(f, ks, cx0 + 70 + ks.width / 2 - 40, yy, ka, 1, kb)
        place(f, vs, cx0 + cw - 70 - vs.width / 2 + 40, yy, ka, 1, kb)
    lay = layer(); d = ImageDraw.Draw(lay)
    k = ease_io((t - s0 - 2.0) / 0.6)
    if k > 0: d.line([(cx0 + 70, cy0 + 260), (cx0 + 70 + (cw - 140) * k, cy0 + 260)], fill=LINE + (255,), width=3)
    f.alpha_composite(alpha_img(lay, a))
    ka, kb, ks = life(t, s0 + 2.3, e)
    v = int(round(roll(0, 60, t, s0 + 2.3, 1.2)))
    place(f, sprite(f'RM{v}', font('InterDisplay-Bold', 150), grad='green'), W / 2, cy0 + 380, ka, ks, kb)
    ka, kb, _ = life(t, s0 + 3.4, e)
    place(f, sprite('Dari harta arwah, sebelum dibahagi ikut faraid', font('Inter-Medium', 30), GREY), W / 2, cy0 + 500, ka, 1, kb)
    ka, kb, ks = life(t, s0 + 4.6, e)
    place(f, sprite('Bayar online, nyatakan nama arwah pada borang.', font('Inter-SemiBold', 36), BLUE), W / 2, Y(860), ka, ks, kb)

# ---------- 6. gallery: photos from the site's "Update Agihan" reports ----------
AG = B.ASSETS + 'fid/agihan/'
GAL = [(shadow_card(crop_to(AG + p, 820, 560), 34, 14), cap) for p, cap in
       (('a2.jpg', 'Semporna, Sabah · 20 Okt 2026'), ('a6.jpg', 'Lahad Datu, Sabah · 17 Okt 2026'),
        ('a10.jpg', 'Rumah Ngaji · 16 Okt 2026'), ('a11.jpg', 'Rumah Ngaji · 16 Okt 2026'))]
def chip(text):
    fnt = font('Inter-SemiBold', 28); w = int(fnt.getlength(text)) + 90
    im = Image.new('RGBA', (w, 60), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, w - 1, 59), 30, fill=TINT)
    d.ellipse((26, 20, 46, 40), fill=BLUE); d.ellipse((32, 26, 40, 34), fill=PURE)
    d.text((60, 30), text, font=fnt, fill=BLUE, anchor='lm')
    return im
CHIPS = [chip(c) for _, c in GAL]
def scene_gallery(f, t):
    s0, e = CUT[5], CUT[6] + 0.3
    a, b, s = life(t, s0, e)
    place(f, sprite('Update agihan fidyah', font('Inter-SemiBold', 34), BLUE), W / 2, Y(30), a, 1, b)
    per = 1.6
    for i, (img, _) in enumerate(GAL):
        st = s0 + 0.3 + i * per
        if t < st - 0.05: continue
        k = ease_out((t - st) / 0.6)
        last = i == len(GAL) - 1
        out = 1.0 if last else 1 - ease_io((t - st - per) / 0.5)     # earlier cards tuck behind
        if out <= 0.01 and not last: continue
        rot = (-3, 2.5, -2, 1)[i]
        im = img.rotate(rot * (1 - 0.3 * k), resample=Image.BICUBIC, expand=True)
        z = 0.96 + 0.04 * k + 0.01 * (t - st)
        place(f, im, W / 2 + (1 - k) * (260 if i % 2 else -260), Y(400), a * k * out, z, b)
        ca = clamp((t - st - 0.3) / 0.4) * (out if not last else 1)
        place(f, CHIPS[i], W / 2, Y(745), a * ca, 1, b)
    for i, ln in enumerate(['Setiap hari yang tertinggal,', 'jadi makanan untuk yang memerlukan.']):
        ka, kb, ks = life(t, s0 + 2.0 + i * 0.6, e)
        place(f, sprite(ln, font('InterDisplay-Bold', 48), NAVY if i == 0 else BLUE), W / 2, Y(850 + i * 66), ka, ks, kb)

# ---------- 7. series outro ----------
def url_pill():
    fnt = font('InterDisplay-Bold', 46); s_ = 'www.bayarfidyahonline.com'
    w = int(fnt.getlength(s_)) + 90
    im = Image.new('RGBA', (w, 104), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rounded_rectangle((0, 0, w - 1, 103), 52, fill=BLUE)
    d.text((w / 2, 52), s_, font=fnt, fill=PURE, anchor='mm')
    return im
PILL = url_pill()
def scene_outro(f, t):
    s0 = CUT[6]
    a, b, s = life(t, s0, DUR + 1)
    series_pill(f, a)
    for i, ln in enumerate(['Jangan Tangguh,', 'Puasa Hutang kita', 'Dengan Allah']):
        ka, kb, ks = life(t, s0 + 0.2 + i * 0.35, DUR + 1)
        place(f, sprite(ln, H2, grad='green' if i == 2 else None), W / 2, Y(220 + i * 100), ka, ks, kb)
    pa = life(t, s0 + 1.5, DUR + 1)[0]
    x = max(0, t - s0 - 1.5)
    sp = 1 - math.exp(-x * 9) * math.cos(x * 20) if t >= s0 + 1.5 else 0
    place(f, PILL, W / 2, Y(620), pa, max(0.01, sp))
    la, lb, _ = life(t, s0 + 2.1, DUR + 1)
    place(f, sprite('Platform oleh', TINY, GREY), W / 2, Y(790), la, 1, lb)
    place(f, B.LOGO, W / 2, Y(880), la, 1, lb)

SCENES = [scene_hook, scene_story, scene_rule, scene_hadith, scene_calc, scene_gallery]
def render(t):
    f = B.bg(t)
    for i, sc in enumerate(SCENES):
        if CUT[i] - 0.1 <= t < CUT[i + 1] + 0.4: sc(f, t)
    if t >= CUT[6] - 0.1: scene_outro(f, t)
    out = f.convert('RGB')
    fade = clamp((t - (DUR - 0.8)) / 0.8)
    if fade > 0: out = Image.blend(out, Image.new('RGB', (W, H), (248, 251, 255)), fade)
    return out

# ---------- music: slow piano over a warm pad (no drums) ----------
def build_audio(path, dur=DUR, cuts=CUT[1:-1]):
    n = int(dur * SR); L = np.zeros(n); R = np.zeros(n)
    rng = np.random.default_rng(5)
    def hz(m): return 440 * 2 ** ((m - 69) / 12)
    def add(x, st, g=1.0, pan=0.5):
        i0 = int(st * SR)
        if i0 >= n or i0 < 0: return
        x = x[:n - i0]; L[i0:i0 + len(x)] += x * g * (1 - pan); R[i0:i0 + len(x)] += x * g * pan
    def piano(m, d=3.5):
        k = int(d * SR); tt = np.arange(k) / SR; fq = hz(m)
        x = sum(np.sin(2 * np.pi * fq * h * tt * (1 + 0.0004 * h)) / h ** 1.6 * np.exp(-tt * (0.9 + 0.7 * h)) for h in range(1, 7))
        return x * np.minimum(1, tt / 0.004)
    def pad(notes, d):
        k = int(d * SR); tt = np.arange(k) / SR; x = np.zeros(k)
        for m in notes:
            for det in (-0.08, 0.0, 0.08):
                x += np.sin(2 * np.pi * hz(m + det) * tt + rng.uniform(0, 6))
        env = np.minimum(1, tt / 1.5) * np.minimum(1, (d - tt) / 1.5)
        return x * env / (len(notes) * 3)
    BEAT = 60 / 66; BAR = BEAT * 4
    # Dmaj7 - Bm7 - Gmaj7 - A(sus) : tender, hopeful
    prog = [(50, (62, 66, 69, 73)), (47, (59, 62, 66, 69)), (43, (59, 62, 66, 67)), (45, (57, 61, 64, 69))]
    mel = [[74, 73, 69, None], [71, 69, 66, None], [67, 71, 74, 76], [73, None, 69, None]]
    nb = int(dur / BAR) + 1
    for bi in range(nb):
        st = bi * BAR; root, ch = prog[bi % 4]
        add(pad(ch, BAR + 1.5), st, 0.10, 0.5)
        add(piano(root - 12, 4.0), st, 0.16, 0.45)
        for j, m in enumerate(ch):            # soft broken chord
            add(piano(m, 3.0), st + j * BEAT * 0.5, 0.07, 0.35 + 0.1 * j)
        if bi >= 2 and st < dur - BAR * 1.5:   # melody enters after the hook
            for j, m in enumerate(mel[bi % 4]):
                if m: add(piano(m + 12, 3.0), st + j * BEAT, 0.085, 0.6)
    for c in cuts:                             # a single high bell note at each scene change
        add(piano(86, 3.0), c, 0.05, 0.5)
    mix = np.stack([L, R], 1)
    # light room reverb (a few decaying echoes)
    rev = np.zeros_like(mix)
    for dl, g in ((0.045, 0.35), (0.083, 0.28), (0.127, 0.22), (0.19, 0.16), (0.27, 0.11), (0.38, 0.07)):
        k = int(dl * SR); rev[k:] += mix[:-k] * g
    mix = mix + rev[:, ::-1] * 0.8
    tt = np.arange(n) / SR
    mix *= (np.minimum(1, tt / 1.2) * np.clip((dur - tt) / 3.0, 0, 1))[:, None]
    mix = np.tanh(mix / max(1e-6, np.percentile(np.abs(mix), 99.9)) * 0.55) * 0.9
    with wave.open(path, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((mix * 32767).astype('<i2').tobytes())

if __name__ == '__main__':
    if len(sys.argv) > 1:
        for ts in sys.argv[1:]: render(float(ts)).save(f'{B.SP}s5_{B.FMT}_{ts}.jpg', quality=85)
        sys.exit()
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    build_audio(WAV)
    p = subprocess.Popen([ff, '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-i', WAV, '-map', '0:v', '-map', '1:a', '-c:v', 'libx264', '-preset', 'medium', '-crf', '21',
                          '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '192k', '-shortest', '-movflags', '+faststart', OUT],
                         stdin=subprocess.PIPE, stderr=subprocess.DEVNULL)
    for i in range(N): p.stdin.write(render(i / FPS).tobytes())
    p.stdin.close(); p.wait(); print('done', OUT)
