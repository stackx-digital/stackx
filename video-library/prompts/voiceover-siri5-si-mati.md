# Voiceover: Siri Fidyah 5/5, Bagi pihak si mati (57 saat) untuk ElevenLabs

Selari dengan `scripts/fidyah_siri5_si_mati.py`. Sekitar 110 perkataan: rentak perlahan (±2 perkataan sesaat), banyak ruang bernafas.

## Nada

- **Watak:** anak yang mengenang arwah ayahnya, bercerita dengan tenang. Lembut, penuh hormat; bukan sedih berlebihan, bukan iklan.
- **Emosi:** sayu dan perlahan pada pembuka, tenang dan jelas pada hukum, khusyuk pada hadis, hangat dan penuh harapan pada penutup.
- **Suara:** lelaki 30-an yang hangat dan rendah (Malique boleh, tetapi lebih perlahan), atau perempuan yang lembut.

## Skrip mengikut babak

| Masa | Babak di skrin | Bacaan |
|---|---|---|
| 0.0 – 5.5 | Pusara, cahaya pagi | Arwah ayah... ada puasa yang belum diganti. |
| 5.5 – 17.5 | Garis masa | Ramadan terakhirnya, arwah sakit. Lima belas hari tak dapat berpuasa. Alhamdulillah, arwah sempat sihat semula... tapi pergi, sebelum sempat qadak. Hutang puasanya... masih ada. |
| 17.5 – 24.5 | Bila waris perlu bayar | Jika arwah sempat sihat tapi belum qadak, waris tunaikan fidyah. Jika sakit hingga meninggal dunia, tiada fidyah. |
| 24.5 – 34.5 | Hadis | Dalam hadis riwayat at-Tirmizi: Sesiapa yang mati, sedangkan dia masih mempunyai puasa Ramadan, hendaklah diberi makan seorang miskin, bagi setiap hari yang ditinggalkannya. |
| 34.5 – 43.0 | Kad RM60 | Lima belas hari, empat ringgit sehari... enam puluh ringgit. Diambil dari harta arwah, sebelum faraid. Nyatakan nama arwah semasa membayar. |
| 43.0 – 51.0 | Gambar Update Agihan | Dan setiap hari yang tertinggal... menjadi makanan, untuk yang memerlukan. |
| 51.0 – 57.0 | Penutup siri | Jangan tangguh. Puasa, hutang kita dengan Allah. Bayar fidyah online dot com. |

## Teks untuk ditampal: Eleven v3 (tag nada + jeda yang v3 faham)

```
[softly] Arwah ayah... ada puasa yang belum diganti.
[long pause]
[gentle] Ramadan terakhirnya, arwah sakit. Lima belas hari... tak dapat berpuasa.
Alhamdulillah, arwah sempat sihat semula... [sad] tapi pergi, sebelum sempat qadak.
Hutang puasanya... masih ada.
[long pause]
[calm] Jika arwah sempat sihat tapi belum qadak... waris tunaikan fidyah.
Jika sakit hingga meninggal dunia... tiada fidyah.
[pause]
[reverent] Dalam hadis riwayat at-Tirmizi:
Sesiapa yang mati, sedangkan dia masih mempunyai puasa Ramadan... hendaklah diberi makan seorang miskin, bagi setiap hari yang ditinggalkannya.
[long pause]
[clear] Lima belas hari, empat ringgit sehari... enam puluh ringgit.
Diambil dari harta arwah, sebelum faraid. Nyatakan nama arwah semasa membayar.
[pause]
[warm] Dan setiap hari yang tertinggal... menjadi makanan, untuk yang memerlukan.
[pause]
[warm] Jangan tangguh... Puasa, hutang kita dengan Allah.
Bayar fidyah online dot com.
```

Jika `[pause]` / `[long pause]` dibaca kuat atau tidak berkesan, buang tag itu; titik tiga dan baris baru sudah memberi jeda.

## Teks untuk ditampal: Multilingual v2 (jeda tepat)

```
Arwah ayah... ada puasa yang belum diganti. <break time="1.2s" />
Ramadan terakhirnya, arwah sakit. <break time="0.3s" /> Lima belas hari, tak dapat berpuasa. <break time="0.5s" />
Alhamdulillah, arwah sempat sihat semula. <break time="0.4s" /> Tapi pergi, sebelum sempat qadak. <break time="0.6s" />
Hutang puasanya... masih ada. <break time="1.2s" />
Jika arwah sempat sihat tapi belum qadak, waris tunaikan fidyah. <break time="0.4s" />
Jika sakit hingga meninggal dunia, tiada fidyah. <break time="1.0s" />
Dalam hadis riwayat at-Tirmizi: <break time="0.5s" />
Sesiapa yang mati, sedangkan dia masih mempunyai puasa Ramadan, <break time="0.3s" /> hendaklah diberi makan seorang miskin, bagi setiap hari yang ditinggalkannya. <break time="1.2s" />
Lima belas hari, empat ringgit sehari. <break time="0.4s" /> Enam puluh ringgit. <break time="0.5s" />
Diambil dari harta arwah, sebelum faraid. <break time="0.3s" /> Nyatakan nama arwah semasa membayar. <break time="1.0s" />
Dan setiap hari yang tertinggal, <break time="0.3s" /> menjadi makanan, untuk yang memerlukan. <break time="1.0s" />
Jangan tangguh. <break time="0.3s" /> Puasa, hutang kita dengan Allah. <break time="0.4s" />
Bayar fidyah online dot com.
```

## Tetapan

| Tetapan | Nilai |
|---|---|
| Stability | 55 – 65% (v3: "Natural"): lebih stabil untuk nada khusyuk |
| Similarity | 75% |
| Style exaggeration | 0 – 10% |
| Speed | 0.9 – 0.95 (lebih perlahan daripada video lain) |
| Speaker boost | Hidup |

Sasaran panjang audio: 50 hingga 55 saat.

## Tip sebutan
- **qada'** → "qadak"; **at-Tirmizi** → jika tersalah sebut, tulis "at-Tir-mi-zi".
- **faraid** → jika disebut "fa-raid", tulis "fa-ra-id".
- **RM60** → "enam puluh ringgit".
