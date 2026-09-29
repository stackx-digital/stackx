# Perpustakaan Video (Video Library)

Rujukan, prompt gaya dan skrip untuk menjana video animasi pendek dengan Claude.
Semua video di sini dijana dengan kod (Python + PIL + ffmpeg), termasuk muzik, jadi tiada isu hak cipta audio.

**Halaman perpustakaan (untuk telefon / web):** https://claude.ai/artifact/RN42Psn8uD9BwuhnEw5HwB (peribadi)

## Isi kandungan

| Folder / fail | Isi |
|---|---|
| [`rujukan.md`](rujukan.md) | Laman rujukan (whatships.com, MotionPrompt), fakta Bayar Fidyah, kronologi Syeikh Ahmad Yassin, nota teknikal |
| [`prompts/`](prompts/) | Prompt gaya siap salin: tampal di bawah permintaan video anda dalam Claude |
| [`scripts/`](scripts/) | Skrip Python yang menghasilkan setiap video dan muziknya |
| [`assets/`](assets/) | Gambar sumber: poster, ilustrasi kategori, logo, tangkapan skrin kalkulator, gambar agihan |
| `setup.sh` | Pasang pakej, muat turun font, klon galeri MotionPrompt |

## Gaya yang sudah dibina

| Gaya | Prompt | Skrip | Contoh video |
|---|---|---|---|
| Storytelling kertas lama (landskap) | [prompts/storytelling-kertas-lama.md](prompts/storytelling-kertas-lama.md) | `yassin_storytelling.py` + `yassin_storytelling_music.py` | Syeikh Ahmad Yassin, 30s |
| Apple launch gelap + latar foto (potret) | [prompts/apple-launch-gelap.md](prompts/apple-launch-gelap.md) | `yassin_apple.py` + `yassin_apple_music.py` | Syeikh Ahmad Yassin, 30s 9:16 |
| Apple launch cerah, warna jenama (landskap) | [prompts/apple-launch-cerah-jenama.md](prompts/apple-launch-cerah-jenama.md) | `fidyah_biru_40s.py` + `fidyah_biru_40s_music.py` | Bayar Fidyah Online, 40s |
| Vox explainer (landskap) | [prompts/vox-explainer.md](prompts/vox-explainer.md) | `fidyah_vox_40s.py` + `fidyah_vox_40s_music.py` | Bayar Fidyah Online, 40s |
| Voiceover ElevenLabs (skrip + nada + tetapan) | [prompts/voiceover-fidyah-elevenlabs.md](prompts/voiceover-fidyah-elevenlabs.md) | – | Bayar Fidyah 40s |
| Templat umum (mana-mana topik) | [prompts/templat-umum.md](prompts/templat-umum.md) | – | – |

## Cara guna (Claude Code)

```bash
cd video-library
./setup.sh                                   # sekali sahaja setiap sesi baru

# contoh: video Bayar Fidyah gaya Vox
python3 scripts/fidyah_vox_40s.py            # video senyap  -> output/fidyah_vox_silent.mp4
python3 scripts/fidyah_vox_40s_music.py      # muzik         -> output/music_vox40.wav
python3 scripts/gabung.py output/fidyah_vox_silent.mp4 output/music_vox40.wav output/fidyah_vox_40s.mp4 30

# pratonton beberapa frame sahaja (cepat): beri masa dalam saat
python3 scripts/fidyah_vox_40s.py 3.8 12.5 27.5
```

Untuk ambil semula tangkapan skrin kalkulator: `python3 scripts/tangkap_kalkulator.py`
(perlu domain `bayarfidyahonline.com` dibenarkan dalam tetapan rangkaian).

## Cara guna (Claude di web / telefon)

1. Tulis apa video anda tentang (tajuk, teks, fakta, saiz, tempoh).
2. Buka satu fail dalam `prompts/`, salin blok prompt, tampal di bawah permintaan anda.
3. Lampirkan gambar (logo, foto produk, tangkapan skrin).

## Nota

- Folder `fonts/`, `output/` dan `rujukan/motionprompt/` tidak disimpan dalam repo; `setup.sh` menyediakannya.
- Video MP4 akhir tidak disimpan di sini kerana saiznya besar (5 hingga 24 MB); jana semula dengan skrip.
