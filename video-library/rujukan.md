# Rujukan

## 1. Laman rujukan gaya video

### whatships.com
- https://whatships.com/
- Direktori bebas lebih 2,000 video pelancaran produk startup dari X (Twitter): demo produk, video "first look", walkthrough.
- Contoh: [Browserbase](https://whatships.com/videos/browserbase/), [Typeless](https://whatships.com/videos/typeless/),
  [Fastlane](https://whatships.com/videos/fastlane/), [Kickbacks](https://whatships.com/videos/kickbacks/), [Canopy](https://whatships.com/videos/canopy/).
- Ciri biasa video pelancaran startup: rentak laju (potongan 1 hingga 2 saat ikut muzik), demo produk dengan kursor yang mengklik,
  zoom ke butang dan angka penting, skrin condong 3D, teks besar satu ayat setiap skrin, struktur
  masalah > penyelesaian > demo > bukti > "Cuba sekarang".
- **Status akses:** disekat oleh rangkaian persekitaran Claude Code. Untuk membuka, tambah dalam Allowed domains:
  `whatships.com`, `video.twimg.com`, `pbs.twimg.com`, kemudian mulakan sesi baru.

### MotionPrompt (epool86)
- Laman: https://epool86.github.io/motionprompt/ (github.io disekat dalam sesi ini)
- Kod sumber: https://github.com/epool86/motionprompt (boleh diklon; `./setup.sh` klon ke `rujukan/motionprompt/`)
- Oleh Ahmad Saiful Bahri. Galeri gaya animasi untuk Claude: pilih gaya, salin prompt, tampal di bawah idea video anda, dapat MP4.
- Setiap gaya: `styles/<slug>/prompt.txt` (prompt, dengan `{size}` dan `{duration}`), `demo.html`, `tile.mp4`.
- Tiada fail lesen dalam repo, jadi prompt mereka **tidak** disalin ke sini. Baca terus dari klon: `cat rujukan/motionprompt/styles/app-showcase/prompt.txt`.
- Cadangan untuk video Bayar Fidyah: **App showcase**, **Infographic**, **Data story**, **Flat illustration**,
  **Whiteboard sketch**, **Festive Raya**, **Malaysian heritage**, **Chat story**.

| Gaya | Slug | Ringkasan (dari styles.json) | Tag |
|---|---|---|---|
| Kaiju attack | `kaiju-attack` | A retro monster movie: a giant rubber suit monster rises over a model city and burns your headline into the sky. | promo, retro, text |
| Infographic | `infographic` | A flat explainer poster: the camera glides from numbered steps to a people stat and a before and after. | explainer, data, social |
| Radial infographic | `radial-infographic` | A rainbow cycle wheel snaps together blade by blade as each numbered point lights up around your logo. | explainer, brand, data |
| Slide deck | `slide-deck` | Presentation slides spread on a tilted table, with the camera gliding from slide to slide as each one comes alive. | brand, explainer, product |
| Flat illustration | `flat-illustration` | A flat vector team scene on bright blue: a presenter at a flip chart, charts that draw themselves and a bold title. | explainer, brand, promo |
| Isometric diorama | `isometric-diorama` | A cute 3D diorama where objects pop onto a round stage as the camera orbits. | 3d, product, explainer |
| Kinetic typography | `kinetic-type` | Big bold words that punch in on the beat, then stack into one line. | text, social |
| Malaysian heritage | `malaysian-heritage` | Wau bulan fly as batik bunga raya draw and bloom, then your title unrolls on gold songket. | malaysia, festive, text |
| App showcase | `app-showcase` | A floating 3D phone with sliding app screens, pop out UI cards and a tap ripple. | product, 3d |
| Kawaii pastel | `kawaii-pastel` | A cute chibi girl waves hello among pastel stickers, hearts and bouncy bubble text. | social, promo, cute |
| Tech noir | `tech-noir` | A dark cinematic tech film: a dotted globe, glowing line-art devices and particle text. | 3d, explainer, product |
| Food menu | `food-menu` | A top down food flat lay: ingredients drop onto the plate and a menu card slides in. | food, promo |
| Neon sign | `neon-sign` | Neon tube lettering that flickers on letter by letter over a dark brick wall. | text, retro |
| Map journey | `map-journey` | A travel map where the route draws itself, pins drop and day cards pop up. | travel, explainer, malaysia |
| Liquid gradient | `liquid-gradient` | Soft colour blobs melt into each other under fine grain while a clean headline fades in. | background, text |
| Festive Raya | `festive-raya` | Gold star patterns, swaying ketupat and flickering pelita for a warm Raya greeting. | festive, malaysia, text | (tersembunyi)
| Data story | `data-story` | Numbers count up while bars grow, lines draw and donuts fill, card by card. | data, explainer |
| Claymation | `claymation` | A squishy clay world with a bouncy blob buddy, shot in choppy stop motion. | 3d, handmade, cute |
| Retro synthwave | `retro-synthwave` | 80s outrun: a neon grid, a striped setting sun and a chrome headline. | retro, background |
| Elegant wedding | `elegant-wedding` | Watercolour flowers bloom around a gold arch as the names write on in foil script. | festive, text, handmade |
| 3D logo spin | `logo-spin-3d` | A shiny 3D logo that spins, settles and floats, ready for an intro or outro. | 3d, brand |
| Kopitiam | `kopitiam` | A sunny kopitiam morning: teh tarik pulled high and a chalk menu that writes itself. | food, malaysia, retro | (tersembunyi)
| Comic pop art | `comic-pop-art` | Comic book panels slam in with halftone dots, speech bubbles and a big POW. | promo, retro, text |
| Photo slideshow | `photo-slideshow` | Your photos with slow Ken Burns zooms, bold captions and smooth editorial transitions. | photos, travel, social |
| Batik fashion | `batik-fashion` | Baju kurung looks in colourful batik, linked by bold batik scene transitions. | malaysia, product, promo |
| Merdeka | `merdeka` | The Jalur Gemilang waves over KL fireworks for Merdeka and Malaysia Day. | malaysia, festive, text | (tersembunyi)
| Product turntable | `product-turntable` | A glass bottle turns on a studio pedestal with light glints, callouts and a price card. | product, 3d, promo |
| Glitch | `glitch` | Digital glitch type with RGB split, sliced frames and a blinking terminal cursor. | text, social |
| Kampung sunset | `kampung-sunset` | A Malaysian kampung at golden hour, sinking into a firefly dusk. | malaysia, background, handmade | (tersembunyi)
| Chat story | `chat-story` | A phone chat that pops to life: typing dots, springy bubbles and a voice note. | social, promo, text |
| Sale countdown | `sale-countdown` | A punchy 3, 2, 1 countdown, a swinging price tag and a burst of confetti. | promo, social |
| Watercolour ink | `watercolour-ink` | Watercolour washes bloom on paper as an ink plum branch and a brushed headline paint in. | handmade, text, brand |
| Pasar malam | `pasar-malam` | A Malaysian night market lights up at dusk, with satay smoke and price cards popping. | food, malaysia, promo | (tersembunyi)
| Whiteboard sketch | `whiteboard-sketch` | Marker doodles and handwriting draw themselves on a warm whiteboard, explainer style. | explainer, handmade |
| Y2K chrome | `y2k-chrome` | A liquid chrome blob morphs under a shiny Y2K headline with holo stickers. | 3d, retro, brand |
| Chinese heritage | `chinese-new-year` | Red and gold heritage ad: paper cuts, lanterns, your product and red seal badges. | product, festive, promo |
| Property tour | `property-tour` | A 3D floor plan builds itself, rooms light up with sizes, then the price slides in. | 3d, product, explainer |
| Particle field | `particle-field` | Thousands of glowing dots drifting along soft currents, like smoke or wind. | background |
| Nostalgia 90s | `nostalgia-90s` | A chunky 90s TV flicks on to colour bars and a VHS home video title. | retro, malaysia, photos |
| Podcast audiogram | `podcast-audiogram` | A podcast clip card with a talking avatar, live sound bars and karaoke captions. | social, text |
| Borneo rainforest | `borneo-rainforest` | A misty Borneo rainforest with light rays, a gliding hornbill and a rafflesia in bloom. | travel, background, malaysia |
| Paper cut-out | `paper-cutout` | Layered paper hills, sun and clouds pop into place with a handmade stop motion wobble. | handmade, background |
| Pixel art | `pixel-art` | Your offer as a pixel game level: a Player 1 card, blocks that reveal it, a level up CTA. | retro, promo, product |
| Deepavali | `deepavali` | A kolam draws itself as clay diyas light up under marigold garlands for Deepavali. | festive, malaysia, text | (tersembunyi)
| Vintage archive | `vintage-archive` | Sepia photos, typewriter captions and an ink timeline that stamps the years on aged film. | photos, retro, brand |
| Swiss geometric | `swiss-geometric` | A bold Swiss poster that lists your services, with a geometric marker for each one. | brand, promo, text |
| Floating 3D icons | `floating-3d-icons` | Glossy 3D app icons burst out of a floating monitor and drift around it on a pink and purple gradient. | 3d, social, promo |
| Neon tech | `neon-tech` | A neon tech title: HUD rings spin up, holographic panels rise from an isometric laptop and your key word slams in magenta. | explainer, brand, promo |

### Gaya lain yang disebut
- **Vox** (video penerangan Vox.com): kertas bertekstur, gambar potongan bertepi putih + pita pelekat, penanda kuning,
  bulatan pen merah, label bab kotak hitam, gerakan stop-motion 12fps, butiran filem. Lihat [prompts/vox-explainer.md](prompts/vox-explainer.md).
- **Apple launch / keynote**: latar hitam atau cerah, tipografi besar bersih, teks kabur ke tajam, nombor berputar naik.

## 2. Bayar Fidyah Online (bayarfidyahonline.com)

Platform oleh **ihsanku**. Tajuk laman: "Bayar Fidyah Online — Mudah & Selamat".

- **Kadar:** RM4.00 sehari (kadar piawai kebangsaan; rujuk pihak berkuasa agama negeri untuk pengesahan).
  Secupak = 700 gram makanan asasi seperti beras bagi setiap hari puasa yang ditinggalkan.
- **Formula:** (Bilangan hari tidak berpuasa × Kadar fidyah) × Bilangan tahun qada' dilewatkan.
  - Contoh 1: 7 hari × RM4.00 = RM28.00
  - Contoh 2 (lewat 3 tahun, 1444H hingga 1447H): (7 × RM4.00) × 3 = **RM84.00**
- **Siapa wajib bayar:** warga emas / uzur; sakit berpanjangan (tiada harapan sembuh); ibu hamil & menyusu
  (bimbang keselamatan anak: qada' + fidyah; bimbang diri sendiri: qada' sahaja); lewat qada' hingga Ramadan berikutnya tanpa uzur;
  bagi pihak si mati (dibayar waris, jika si mati sempat mengqada').
- **Bayaran:** FPX, kad atau QR melalui CHIP, atau pindahan bank manual. Resit rasmi terus ke emel. Kurang 3 minit.
- **Statistik laman (Sep 2026):** 27,219+ pembayar, 50K penerima manfaat.
- **Agihan:** Kg. Sungai Buah, Sabak Bernam (Selangor), komuniti Muslim Zimbabwe; video: Tanzania 2025, Gaza Oktober 2025.
- **Hadis di laman:** "Sesiapa yang mati sedangkan dia masih mempunyai puasa Ramadan, hendaklah diberi makan seorang miskin
  bagi setiap hari yang ditinggalkannya." (Riwayat al-Tirmizi)
- **Jenama laman:** tema cerah, latar putih kebiruan, teks navy, biru utama `#066BE3`, biru langit logo `#38BDF8`,
  font **Plus Jakarta Sans**. Logo: `assets/fid/logo.svg`.
- **Kod sumber platform:** https://github.com/ihsankuit/web-bayar-fidyah-online (Next.js, Supabase, CHIP, Resend).

Aset dalam `assets/fid/`: ilustrasi kategori (`kat_*.webp`), logo, gambar hero (`g/p14.jpg`), gambar agihan (`gal/g1..g10.jpg`:
g1 gudang, g2 hingga g8 Sungai Buah, g9 Sabak Bernam, g10 Zimbabwe), tangkapan skrin kalkulator (`ui/s00..s09.jpg`, 1 hari hingga RM84).
Ilustrasi yang lebih jelas daripada pengguna: `assets/images/4.webp` sakit, `5` hamil, `6` menyusu, `7` si mati, `8` lewat qada'.

Sumber tambahan: [Jabatan Mufti WP](https://muftiwp.gov.my/en/artikel/irsyad-fatwa/irsyad-fatwa-khas-ramadhan-cat/5594-irsyad-fatwa-edisi-khas-ramadhan-siri-ke-232-siapakah-golongan-yang-wajib-membayar-fidyah-puasa),
[ecentral.my kadar 2026](https://ecentral.my/kadar-fidyah-2026/).

## 3. Syeikh Ahmad Yassin (kronologi, nada neutral)

Sumber: poster Misi Al-Aqsa / aqsamission.com (`assets/images/1.jpg`, `2.jpg`, `3.jpg`).

- 1938 lahir di al-Jora, Majdal, wilayah Asqalan · 1948 berhijrah ke Gaza · zaman muda lumpuh akibat kemalangan
- 1964 Universiti Ain Shams, Kaherah · 1967 guru bahasa Arab & khatib · 1973 menubuhkan Mujamma' al-Islami (kebajikan)
- 1987 menubuhkan HAMAS (Intifadah pertama) · 1989 hingga 1997 dipenjara · 22 Mac 2004 terbunuh dalam serangan udara Israel selepas solat subuh
- Video yang dibuat memilih nada dokumentari: tanpa kisah operasi bersenjata dan tanpa istilah "syahid".

## 4. Nota teknikal (Claude Code di cloud)

- Domain yang perlu dibenarkan (Network access > Allowed domains): `bayarfidyahonline.com`, `euksnhkmoirtdgiwdokc.supabase.co`
  (galeri), `whatships.com`, `epool86.github.io`.
- Gambar galeri Supabase boleh diambil melalui pengoptimum imej laman: `https://www.bayarfidyahonline.com/_next/image?url=<URL dikod>&w=1200&q=75` (q mesti 75).
- Chromium (Playwright) di cloud: percayai CA proksi sahaja dengan `--ignore-certificate-errors-spki-list=<hash SPKI>`
  (lihat `scripts/tangkap_kalkulator.py`); jangan matikan semakan TLS.
- Video dengan butiran filem (Vox) jadi besar (95 MB); mampatkan dengan `gabung.py ... 30` (`-crf 30 -tune grain`), kira-kira 24 MB.
