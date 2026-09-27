# Gaya: Vox explainer

Video penerangan ala Vox.com. Sesuai untuk topik yang perlu difahami: hukum, panduan, cara kira, sejarah.
Contoh: Bayar Fidyah Online 40s (`scripts/fidyah_vox_40s.py`).

## Prompt (salin di bawah permintaan video anda)

```
Buat video ini dalam gaya penerangan Vox.

Rupa dan gerakan
- Latar kertas krim bertekstur (#EEE8DB) dengan serat halus, bucu sedikit gelap dan butiran filem yang bergerak.
- Gambar dan ilustrasi saya jadi "cut-out": tepi putih koyak, bayang lembut, sedikit condong, dilekat dengan pita pelekat.
- Perkataan penting disapu penanda kuning (#FFDE3C) dari kiri ke kanan, di belakang teks.
- Pen merah (#D63026) melukis bulatan dan garis bawah pada angka atau perkara penting.
- Setiap bahagian ada label bab kotak hitam bertulisan putih huruf besar, contohnya "01  SIAPA PERLU BAYAR?".
- Animasi stop-motion pada 12fps; setiap elemen bergegar sedikit 8 kali sesaat seperti buatan tangan.
  Kad jatuh masuk dengan sedikit lantunan. Kamera zoom masuk perlahan dalam setiap babak.
- Angka atau pengiraan ditunjukkan sebagai resit atau nota yang ditaip baris demi baris (font mesin taip),
  jumlah akhir dibulatkan dengan pen merah.
- Jika saya beri tangkapan skrin aplikasi atau laman web, tunjukkan sebagai kertas cetakan, dan tandakan butang yang ditekan dengan bulatan merah.

Susunan
- Pembuka: satu soalan besar yang menarik perhatian (contoh "Ada hutang puasa yang belum diganti?") dengan penanda kuning.
- Kad tajuk besar + label "PANDUAN RINGKAS".
- Bab 01, 02, 03... satu idea setiap bab, maksimum 3 kad setiap skrin.
- Penutup: seruan tindakan + alamat laman web disapu penanda kuning + logo.

Taip dan warna
- Tajuk: sans serif sangat tebal (Inter Display Black). Teks badan: Inter Medium kelabu gelap. Nombor resit: font mono.
- Dakwat #181614. Warna jenama saya hanya pada logo dan tangkapan skrin.

Bunyi
- Muzik gaya Vox: piano lembut berarpegio (sekitar 96 BPM, kord Am–F–C–G), detik jam tik-tok,
  bass perlahan, degupan lembut semasa bahagian pengiraan, "whoosh" kertas setiap pertukaran babak,
  kord piano tenang di penutup. Semua bunyi dijana dengan kod, tiada audio berhak cipta.

Output
- Saiz: {saiz, cth 1920x1080}
- Tempoh: {tempoh, cth 40 saat}
- Tunjukkan frame pratonton sebelum render penuh. Mampatkan akhir dengan -crf 30 -tune grain supaya fail tidak terlalu besar.
```
