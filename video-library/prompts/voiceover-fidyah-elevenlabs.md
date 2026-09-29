# Voiceover: Bayar Fidyah Online (gaya Vox, 40 saat) untuk ElevenLabs

Skrip ini selari dengan video `scripts/fidyah_vox_40s.py` (dan versi biru 40s, masa babak sama).
Sekitar 95 perkataan untuk 40 saat, iaitu kira-kira 2.4 perkataan sesaat: rentak tenang ala narator Vox.

## Nada

- **Watak:** narator penerangan yang tenang, jelas dan mesra, seperti kawan yang pandai menerangkan. Bukan iklan yang menjerit.
- **Emosi:** ingin tahu pada soalan pembuka, yakin dan jelas semasa menerangkan, hangat dan mengajak pada penutup.
- **Rentak:** sederhana; berhenti sejenak (0.3 hingga 0.6 saat) selepas setiap perkara penting.
- **Bahasa:** Bahasa Melayu baku, sebutan jelas. Angka disebut penuh (empat ringgit, lapan puluh empat ringgit).

## Skrip mengikut babak

| Masa | Babak di skrin | Bacaan | Nada |
|---|---|---|---|
| 0.0 – 2.3 | "Ada hutang puasa yang belum diganti?" | Ada hutang puasa... yang belum diganti? | Ingin tahu, perlahan |
| 2.3 – 4.5 | FIDYAH · Panduan ringkas | Mungkin, anda perlu bayar fidyah. | Tenang, yakin |
| 4.5 – 7.0 | Secupak beras = 700 gram | Secupak beras, bagi setiap hari puasa yang ditinggalkan. | Menerangkan |
| 7.0 – 13.7 | 01 Siapa perlu bayar (3 kad) | Siapa yang perlu bayar? Warga emas yang uzur... pesakit kronik... dan ibu hamil, yang bimbangkan keselamatan anaknya. | Jelas, satu per satu |
| 13.7 – 20.5 | 3 kad seterusnya | Begitu juga ibu menyusu... mereka yang melewatkan qada' hingga Ramadan berikutnya... dan waris, bagi pihak si mati. | Sama |
| 20.5 – 29.0 | 02 Cara kira + resit | Cara kiranya mudah. Hari, darab kadar, darab tahun. Tujuh hari, empat ringgit sehari, lewat tiga tahun... jumlahnya, lapan puluh empat ringgit. | Tenang, tekan pada jumlah |
| 29.0 – 33.5 | 03 Bayar online (kalkulator) | Bayar terus dari telefon. Kira, bayar, dan terima resit... dalam masa kurang tiga minit. | Ringkas, yakin |
| 33.5 – 37.0 | 04 Galeri agihan | Dan setiap sumbangan, sampai kepada yang memerlukan. | Hangat |
| 37.0 – 40.0 | Penutup + alamat laman | Tunaikan fidyah anda hari ini, di bayar fidyah online dot com. | Mengajak, mesra |

## Teks untuk ditampal: Eleven v3 (dengan tag nada)

Tag dalam kurungan siku ialah petunjuk nada untuk model Eleven v3; ia tidak dibaca. Titik tiga (...) memberi jeda.

```
[curious] Ada hutang puasa... yang belum diganti?
[calm] Mungkin, anda perlu bayar fidyah.
Secupak beras, bagi setiap hari puasa yang ditinggalkan.

[clear] Siapa yang perlu bayar? Warga emas yang uzur... pesakit kronik... dan ibu hamil, yang bimbangkan keselamatan anaknya.
Begitu juga ibu menyusu... mereka yang melewatkan qadak hingga Ramadan berikutnya... dan waris, bagi pihak si mati.

[calm] Cara kiranya mudah. Hari, darab kadar, darab tahun.
Tujuh hari, empat ringgit sehari, lewat tiga tahun... [emphasis] jumlahnya, lapan puluh empat ringgit.

[confident] Bayar terus dari telefon. Kira, bayar, dan terima resit... dalam masa kurang tiga minit.
[warm] Dan setiap sumbangan, sampai kepada yang memerlukan.
[warm] Tunaikan fidyah anda hari ini, di bayar fidyah online dot com.
```

## Teks untuk ditampal: Multilingual v2 (dengan jeda tepat)

Model v2 tidak membaca tag nada, tetapi menyokong tag jeda `<break time="..." />`.

```
Ada hutang puasa... yang belum diganti? <break time="0.5s" />
Mungkin, anda perlu bayar fidyah. <break time="0.4s" />
Secupak beras, bagi setiap hari puasa yang ditinggalkan. <break time="0.6s" />
Siapa yang perlu bayar? <break time="0.3s" /> Warga emas yang uzur. <break time="0.3s" /> Pesakit kronik. <break time="0.3s" /> Dan ibu hamil, yang bimbangkan keselamatan anaknya. <break time="0.6s" />
Begitu juga ibu menyusu. <break time="0.3s" /> Mereka yang melewatkan qadak hingga Ramadan berikutnya. <break time="0.3s" /> Dan waris, bagi pihak si mati. <break time="0.7s" />
Cara kiranya mudah. <break time="0.3s" /> Hari, darab kadar, darab tahun. <break time="0.5s" />
Tujuh hari, empat ringgit sehari, lewat tiga tahun. <break time="0.4s" /> Jumlahnya, lapan puluh empat ringgit. <break time="0.7s" />
Bayar terus dari telefon. <break time="0.2s" /> Kira, bayar, dan terima resit, dalam masa kurang tiga minit. <break time="0.5s" />
Dan setiap sumbangan, sampai kepada yang memerlukan. <break time="0.5s" />
Tunaikan fidyah anda hari ini, di bayar fidyah online dot com.
```

## Prompt suara (Voice Design)

Guna di ElevenLabs > Voices > **Voice Design** jika tiada suara Melayu yang sesuai dalam Voice Library.

Lelaki:
```
A warm, clear Malaysian Malay male narrator in his early 30s. Calm, friendly and trustworthy documentary explainer voice,
like a Vox video narrator. Standard Malay (bahasa baku) pronunciation, medium pace, gentle smile in the voice,
confident but never salesy. Clean studio recording, close microphone, no background noise, no music.
```

Perempuan:
```
A warm, clear Malaysian Malay female narrator in her late 20s. Calm, caring and articulate explainer voice,
like a Vox documentary narrator. Standard Malay (bahasa baku) pronunciation, medium pace, soft and reassuring,
friendly without sounding like an advert. Clean studio recording, close microphone, no background noise, no music.
```

Teks contoh untuk Voice Design (perlu sekurang-kurangnya kira-kira 100 aksara): guna perenggan "Cara kiranya mudah..." di atas.

## Tetapan dicadangkan

| Tetapan | Nilai | Sebab |
|---|---|---|
| Model | Eleven v3 (nada lebih ekspresif) atau Eleven Multilingual v2 (lebih stabil, sokong `<break>`) | Kedua-duanya menyokong Bahasa Melayu |
| Stability | 45 – 55% (v3: pilih "Natural") | Ada emosi tetapi tidak berubah-ubah |
| Similarity | 75% | Kekal pada suara asal |
| Style exaggeration | 0 – 15% | Nada tenang, bukan dramatik |
| Speed | 0.95 – 1.0 | Muat dalam 40 saat |
| Speaker boost | Hidup | Suara lebih jelas atas muzik |
| Format | MP3 44.1 kHz 192 kbps atau WAV | Untuk digabungkan dengan video |

## Tip sebutan

- **qada'** ditulis "qadak" dalam teks bacaan supaya hentian glotis disebut betul.
- **RM84** ditulis "lapan puluh empat ringgit"; **RM4** ditulis "empat ringgit".
- **bayarfidyahonline.com** ditulis "bayar fidyah online dot com".
- Jika ada sebutan yang salah, ubah ejaan dalam teks bacaan sahaja (contoh "fid-yah"), atau guna kamus sebutan (Pronunciation Dictionary) ElevenLabs.
- Jana **setiap babak berasingan** jika mahu masa yang tepat, kemudian susun ikut jadual masa di atas.

## Selepas jana

Hantar fail MP3/WAV kepada Claude: suara akan diselaraskan dengan video, muzik diperlahankan secara automatik
semasa suara berbunyi (ducking), dan video akhir dikeluarkan semula.
