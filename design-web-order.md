# 📱 goKasir Web Order — Design System

> Sistem desain untuk **Web Order Customer** goKasir — antarmuka pemesanan berbasis web yang diakses pelanggan melalui QR Code di meja.
> Tema selaras dengan aplikasi mobile Flutter goKasir (`primary: #d30f28`, Poppins).

---

## 1. Konteks & Prinsip Desain

### Konteks Penggunaan
- Diakses via **smartphone pelanggan** setelah scan QR Code di meja
- **Mobile-first** — viewport utama 375–430px, tidak ada sidebar
- **Session berbasis meja** — semua URL mengandung `tableCode`
- **Guest user** — tidak ada login, state disimpan di `localStorage` / `sessionStorage`
- **Bahasa:** Indonesia

### Prinsip Utama

| Prinsip | Penerapan |
|---|---|
| **Cepat** | Skeleton loader, optimistic UI, gambar lazy-load |
| **Jelas** | Nama produk & harga selalu terlihat, CTA kontras tinggi |
| **Ramah** | Bahasa santun, ikon intuitif, animasi ringan |
| **Konsisten** | Token warna & tipografi sama dengan app Flutter goKasir |

---

## 2. Brand & Identitas

```
Nama Produk : goKasir Web Order
Sub-tagline : Pesan Mudah, Langsung dari Meja Anda
Primary     : #d30f28
Font        : Poppins (Google Fonts)
Icon Library: Lucide Icons (stroke-based, konsisten dengan app Flutter)
```

---

## 3. Color Palette

### Primary (sama dengan app Flutter)

| Token | Hex | Penggunaan Web Order |
|---|---|---|
| `primary-700` | `#8a0a1a` | Pressed state tombol, gradient dalam |
| `primary-600` | `#b50d23` | Hover tombol utama |
| `primary-500` | `#d30f28` | **Brand color** — tombol pesan, badge, header aksen |
| `primary-400` | `#e83a50` | Icon highlight, tag aktif |
| `primary-100` | `#fce8eb` | Background chip kategori aktif, badge keranjang |
| `primary-50`  | `#fff0f2` | Background section banner, highlight ringan |

### Neutral

| Token | Hex | Penggunaan |
|---|---|---|
| `neutral-900` | `#111827` | Nama produk, heading halaman |
| `neutral-700` | `#374151` | Deskripsi, body teks |
| `neutral-500` | `#6B7280` | Harga per unit, placeholder, label sekunder |
| `neutral-300` | `#D1D5DB` | Divider, border card, garis pemisah |
| `neutral-100` | `#F3F4F6` | Background chip kategori nonaktif, skeleton |
| `neutral-50`  | `#F9FAFB` | Background halaman |
| `white`       | `#FFFFFF` | Surface card, header, bottom bar |

### Semantic

| Token | Hex | Penggunaan |
|---|---|---|
| `success-500` | `#16A34A` | Status pesanan dikonfirmasi, lunas |
| `success-100` | `#DCFCE7` | Background status badge sukses |
| `warning-500` | `#D97706` | Status pending, sedang diproses |
| `warning-100` | `#FEF3C7` | Background status badge pending |
| `danger-500`  | `#DC2626` | Dibatalkan, error, hapus item |
| `danger-100`  | `#FEE2E2` | Background status badge batal |
| `info-500`    | `#2563EB` | Informasi, nomor meja |
| `info-100`    | `#DBEAFE` | Background info chip |

---

## 4. Typography

**Font Family:** `Poppins` — sama persis dengan app Flutter goKasir

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
```

### Type Scale

| Token | Size | Weight | Line Height | Penggunaan |
|---|---|---|---|---|
| `text-xs`   | `10px` | 500 / 600 | 1.6 | Badge status, label chip kategori, timestamp |
| `text-sm`   | `12px` | 400 / 500 | 1.6 | Deskripsi produk, helper text, harga satuan |
| `text-base` | `14px` | 400 / 600 | 1.6 | **Body utama** — nama produk list, label form |
| `text-md`   | `16px` | 500 / 700 | 1.5 | Nama produk card besar, harga, total |
| `text-lg`   | `18px` | 600 / 700 | 1.4 | Judul halaman, nama toko di header |
| `text-xl`   | `20px` | 700       | 1.3 | Grand total checkout, judul modal konfirmasi |

---

## 5. Spacing & Layout

### Spacing (kelipatan 4px)

| Token | Value | Penggunaan |
|---|---|---|
| `space-1` | `4px`  | Gap icon–label, margin badge |
| `space-2` | `8px`  | Padding chip, jarak antar elemen kecil |
| `space-3` | `12px` | Padding input, gap item produk |
| `space-4` | `16px` | Padding card, padding horizontal halaman |
| `space-5` | `20px` | Gap antar section |
| `space-6` | `24px` | Padding bottom bar, gap antar kartu |
| `space-8` | `32px` | Margin antar section besar |

### Layout Web (Mobile-First)

```
Max-width konten : 480px (centered di desktop)
Padding halaman  : 0 16px (kanan-kiri)
Safe area bawah  : padding-bottom 80px (ruang untuk bottom bar)
Safe area atas   : padding-top 60px (ruang untuk sticky header)
```

---

## 6. Border & Radius

| Token | Value | Penggunaan |
|---|---|---|
| `radius-sm`   | `4px`    | Badge, chip kategori |
| `radius-md`   | `8px`    | Tombol, input, card kecil |
| `radius-lg`   | `12px`   | Card produk, bottom sheet |
| `radius-xl`   | `16px`   | Modal, panel konfirmasi |
| `radius-full` | `9999px` | Pill badge, counter qty, avatar meja |

| Token | Value | Penggunaan |
|---|---|---|
| `border-default` | `1px solid #D1D5DB`    | Card, input |
| `border-focus`   | `1.5px solid #d30f28` | Input aktif |
| `border-error`   | `1.5px solid #DC2626` | Error form |

---

## 7. Shadow

| Token | Value | Penggunaan |
|---|---|---|
| `shadow-xs`      | `0 1px 2px rgba(0,0,0,0.05)`       | Chip, input ringan |
| `shadow-sm`      | `0 2px 8px rgba(0,0,0,0.08)`       | Card produk |
| `shadow-md`      | `0 4px 16px rgba(0,0,0,0.10)`      | Bottom bar, modal |
| `shadow-lg`      | `0 8px 32px rgba(0,0,0,0.14)`      | Bottom sheet full |
| `shadow-primary` | `0 4px 16px rgba(211,15,40,0.25)` | Tombol CTA hover/active |

---

## 8. Komponen Global

### 8.1 Header (Sticky Top)

```
Height          : 56px
Background      : #FFFFFF
Border-bottom   : 1px solid #F3F4F6
Shadow          : 0 2px 8px rgba(0,0,0,0.06)
Position        : sticky, top: 0, z-index: 100

Konten kiri     : Logo goKasir (lebar 90px) + nama toko (14px Semibold neutral-900)
Konten kanan    : Icon keranjang (Lucide ShoppingCart 22px)
                  Badge merah pill (#d30f28) menampilkan jumlah item
Info meja       : Chip kecil info-100 "Meja {tableCode}" 10px Semibold info-500
```

### 8.2 Bottom Navigation Bar

Digunakan pada halaman utama (menu & kategori):

```
Height          : 64px
Background      : #FFFFFF
Border-top      : 1px solid #F3F4F6
Shadow          : 0 -2px 12px rgba(0,0,0,0.06)
Position        : fixed, bottom: 0, z-index: 100

Tab items (3 tab):
  🏠 Menu       : icon UtensilsCrossed  — halaman utama
  🛒 Keranjang  : icon ShoppingCart     — checkout
  📋 Pesanan    : icon ClipboardList    — history & status

Tab aktif       : icon + label #d30f28, indicator bar 3px di atas
Tab nonaktif    : icon + label #9CA3AF
Label           : 10px Semibold
```

### 8.3 Back Bar (Halaman Detail / Sub-page)

Menggantikan bottom nav di halaman: checkout, status, history, cancel, profile.

```
Height          : 52px
Background      : #FFFFFF
Border-bottom   : 1px solid #F3F4F6
Konten          : Tombol back (<) + Judul halaman (16px Semibold neutral-900)
Back icon       : Lucide ChevronLeft 22px, tap area minimum 44x44px
```

### 8.4 Tombol

#### Primary (CTA Utama)
```
Background : #d30f28
Text       : #FFFFFF | 14px Semibold
Height     : 48px
Radius     : 8px
Padding    : 0 20px
Hover      : background #b50d23
Active     : background #8a0a1a + scale(0.98)
Shadow     : shadow-primary saat hover
Disabled   : background #D1D5DB text #9CA3AF cursor not-allowed
Width      : full-width untuk CTA checkout
```

#### Secondary
```
Background : transparent
Border     : 1.5px solid #d30f28
Text       : #d30f28 | 14px Semibold
Height     : 48px
Radius     : 8px
Hover      : background #fff0f2
```

#### Ghost / Destructive
```
Background : transparent
Text       : #DC2626 | 14px Medium (untuk batal pesanan)
Hover      : background #FEE2E2
```

#### Quantity Counter (+ / -)
```
Tombol     : 32x32px, radius 8px
- (kurang) : border 1px #D1D5DB, icon Minus 16px neutral-700
+ (tambah) : background #d30f28, icon Plus 16px white
Angka qty  : 14px Semibold neutral-900, min-width 28px, text-center
```

### 8.5 Input & Search

```
Height      : 44px
Background  : #FFFFFF
Border      : 1px solid #D1D5DB
Radius      : 8px
Padding     : 0 12px 0 40px (ada icon search di kiri)
Font        : 14px Regular Poppins
Placeholder : "Cari menu..." #9CA3AF

:focus      : border 1.5px #d30f28, box-shadow 0 0 0 3px rgba(211,15,40,0.12)
Icon search : Lucide Search 18px #9CA3AF, posisi absolute kiri
```

### 8.6 Card Produk

#### Grid Card (tampilan 2 kolom, halaman utama)
```
Width       : calc(50% - 8px)
Background  : #FFFFFF
Radius      : 12px
Shadow      : shadow-sm
Border      : 1px solid #F3F4F6
Overflow    : hidden

Gambar      : aspect-ratio 1/1 (square), object-fit cover, width 100%
              Placeholder: background #F3F4F6, icon UtensilsCrossed 32px #D1D5DB
Body        : padding 10px 12px

Nama produk : 13px Semibold neutral-900, 2 baris max (line-clamp: 2)
Harga       : 14px Bold #d30f28, margin-top 4px
Tombol      : full-width, height 32px, font 12px Semibold, radius 6px
              "Tambah" → background #d30f28 text white
              "Habis"  → background #F3F4F6 text #9CA3AF, disabled
```

#### List Card (tampilan 1 kolom, halaman search & keranjang)
```
Display     : flex, align-items center
Background  : #FFFFFF
Radius      : 12px
Shadow      : shadow-sm
Padding     : 12px

Gambar      : 72x72px, radius 8px, object-fit cover
Body        : flex-1, padding-left 12px
Nama        : 14px Semibold neutral-900
Deskripsi   : 12px neutral-500, 1 baris max
Harga       : 14px Bold #d30f28

Kanan       : Quantity counter (+ angka -)
```

### 8.7 Chip Kategori

```
Height      : 34px
Padding     : 0 14px
Radius      : 9999px (pill)
Font        : 12px Semibold
Display     : inline-flex, align-items center, gap 6px

Default     : background #F3F4F6, text #6B7280
Aktif       : background #d30f28, text #FFFFFF, shadow-primary ringan
Scroll      : horizontal scroll, no-scrollbar (overflow-x auto)
Gap antar   : 8px
```

### 8.8 Badge Status Pesanan

| Status | Background | Text | Label |
|---|---|---|---|
| Menunggu | `#FEF3C7` | `#D97706` | ⏳ Menunggu |
| Diproses | `#DBEAFE` | `#2563EB` | 🍳 Diproses |
| Siap | `#DCFCE7` | `#16A34A` | ✅ Siap Diambil |
| Selesai | `#F3F4F6` | `#6B7280` | ✓ Selesai |
| Dibatalkan | `#FEE2E2` | `#DC2626` | ✗ Dibatalkan |

```
Font        : 10px Semibold
Padding     : 3px 10px
Radius      : 9999px
```

### 8.9 Bottom Sheet

Digunakan untuk detail produk & konfirmasi batal.

```
Overlay     : rgba(0,0,0,0.45), backdrop-filter blur(4px)
Panel       : background #FFFFFF, radius 20px 20px 0 0
Drag handle : 4x32px, radius-full, background #D1D5DB, margin auto 12px
Padding     : 0 16px 32px
Max-height  : 85vh, overflow-y auto
Animasi     : slide-up 250ms cubic-bezier(0.4, 0, 0.2, 1)
```

### 8.10 Toast / Snackbar

```
Position    : fixed, bottom 80px (di atas bottom nav), left 16px right 16px
Background  : #111827
Text        : #FFFFFF | 13px Medium
Radius      : 10px
Padding     : 12px 16px
Shadow      : shadow-lg
Animasi     : slide-up 250ms, auto-dismiss 3000ms
Max-width   : 448px, margin auto

Ikon status : 16px warna sesuai tipe (success/danger/warning/info)
```

### 8.11 Skeleton Loader

```
Background  : linear-gradient(90deg, #F3F4F6 25%, #E5E7EB 50%, #F3F4F6 75%)
Animation   : shimmer 1.5s infinite
Radius      : mengikuti elemen aslinya

Card produk : kotak 100% x 180px
List item   : baris 72px (gambar + 3 baris teks)
Header      : baris 20px lebar 60%
```

### 8.12 Empty State

```
Ilustrasi   : Ikon Lucide ukuran 64px, warna #D1D5DB
Judul       : 16px Semibold neutral-700
Subjudul    : 14px neutral-500, text-center
CTA         : Tombol primary (opsional)
Alignment   : center, padding vertical 48px
```

---

## 9. Halaman — Wireframe & Spesifikasi

### 9.1 Halaman Utama — Menu (`/order/{tableCode}`)

**Tujuan:** Pelanggan browse dan pilih menu.

**Struktur Halaman:**
```
[Sticky Header]
  Logo goKasir | Nama Toko        [🛒 badge qty]
  Chip "Meja {tableCode}"

[Banner Selamat Datang]          ← opsional, bisa dinonaktifkan
  Background gradient primary-700 → primary-500
  "Halo! Silakan pilih menu Anda 👋"
  "Meja {tableCode} · {nama_toko}"

[Search Bar]
  placeholder "Cari menu..."

[Scroll Horizontal Chip Kategori]
  [Semua] [Makan] [Minum] [Snack] [...]

[Grid Produk 2 Kolom]
  [Card Produk] [Card Produk]
  [Card Produk] [Card Produk]
  ... (infinite scroll atau pagination)

[Fixed Bottom Navigation]
  🏠 Menu   🛒 Keranjang (badge)   📋 Pesanan
```

**Interaksi:**
- Tap card → buka bottom sheet detail produk
- Tap "Tambah" di card → langsung tambah ke keranjang, animasi counter
- Chip kategori → filter produk tanpa reload halaman
- Search → navigasi ke `/order/{tableCode}/search?q=...`
- Tab Keranjang → navigasi ke checkout jika ada item, atau empty state
- Floating badge keranjang bergetar (pulse animation) saat item ditambah

---

### 9.2 Halaman Search — (`/order/{tableCode}/search`)

**Struktur:**
```
[Back Bar]
  ← Cari Menu

[Search Bar — auto-focused]

[Hasil Pencarian — List Card]
  (muncul real-time atau submit)

[Empty State — jika tidak ada hasil]
  Ikon SearchX 64px
  "Menu tidak ditemukan"
  "Coba kata kunci lain"
```

**Interaksi:**
- Input auto-focus saat halaman dibuka
- Debounce 400ms sebelum trigger pencarian
- Highlight teks yang cocok (bold) pada nama produk
- Tap hasil → bottom sheet detail produk

---

### 9.3 Halaman Checkout — (`/order/{tableCode}/checkout`)

**Tujuan:** Review keranjang, pilih metode bayar, konfirmasi pesan.

**Struktur:**
```
[Back Bar]
  ← Keranjang

[Info Meja]
  Card info-100
  "📍 Meja {tableCode}" | 14px Semibold info-500

[Daftar Item Keranjang]
  ┌─────────────────────────────────┐
  │ [Gambar 56px] Nama Produk       │
  │               Rp 15.000         │
  │               [–] 2 [+]    [🗑] │
  └─────────────────────────────────┘
  (repeating list)

[Divider + Catatan Pesanan]
  Label "Catatan (opsional)"
  Textarea — 3 baris, placeholder "Contoh: Tanpa es, tidak pedas..."

[Ringkasan Biaya]
  Subtotal          Rp 45.000
  ──────────────────────────
  Total             Rp 45.000  ← 16px Bold #d30f28

[Metode Pembayaran]
  Label "Bayar dengan:"
  [Chip: Tunai ✓] [Chip: QRIS] [Chip: Bank] [Chip: Lainnya]

[CTA Fixed Bottom]
  Background white, shadow-md
  [Pesan Sekarang — Rp 45.000]   ← tombol primary full-width
```

**Interaksi:**
- Ubah qty inline → subtotal dan total update real-time
- Hapus item → konfirmasi kecil (snackbar "undo")
- Empty cart → redirect ke halaman menu dengan toast
- Tap "Pesan Sekarang" → loading state → redirect ke `/order/{tableCode}/status/{orderNumber}`

---

### 9.4 Halaman Status Pesanan — (`/order/{tableCode}/status/{orderNumber}`)

**Tujuan:** Pelanggan memantau progress pesanan secara real-time (polling atau SSE).

**Struktur:**
```
[Back Bar]
  ← Status Pesanan

[Order Number Card]
  Background primary-50
  "No. Pesanan" 12px neutral-500
  "#ORD-20260527-001" 18px Bold primary-500
  "Meja {tableCode}" chip info

[Status Tracker — Vertical Steps]
  ● Pesanan Diterima      [✓ hijau — completed]
  ● Sedang Diproses       [🔵 pulse animation — active]
  ○ Siap Diambil          [abu — pending]
  ○ Selesai               [abu — pending]

  Garis vertikal penghubung antar step (dashed jika belum, solid jika done)

[Detail Pesanan]
  Card white
  List item pesanan (nama + qty + subtotal)
  ─────────────────────────────
  Total         Rp 45.000

[Estimasi Waktu]
  Card warning-100
  ⏱ "Estimasi siap dalam ~10–15 menit"

[Tombol Batal]
  Ghost/destructive — "Batalkan Pesanan"
  Hanya tampil jika status masih "Menunggu"
  → navigate ke /order/{tableCode}/cancel/{orderNumber}

[Auto-refresh]
  Polling setiap 10 detik atau SSE
  Indicator "🔄 Memperbarui..." kecil di pojok atas
```

---

### 9.5 Halaman History — (`/order/{tableCode}/history`)

**Tujuan:** Riwayat semua pesanan di sesi ini.

**Struktur:**
```
[Back Bar]
  ← Riwayat Pesanan

[Filter Chip Row]
  [Semua] [Aktif] [Selesai] [Dibatalkan]

[List Pesanan — Card per Pesanan]
  ┌────────────────────────────────────┐
  │ No. #ORD-xxx       [Badge Status] │
  │ 3 item · Rp 45.000                │
  │ 27 Mei 2026, 12:34                │
  │                    [Lihat Detail →]│
  └────────────────────────────────────┘

[Empty State — jika belum ada pesanan]
  Ikon ClipboardList 64px
  "Belum ada pesanan"
  "Yuk, pilih menu favoritmu!"
  [Mulai Pesan →]  ← primary button
```

**Interaksi:**
- Tap kartu atau "Lihat Detail" → navigate ke `/order/{tableCode}/status/{orderNumber}`
- Filter chip → filter list tanpa reload

---

### 9.6 Halaman Batal Pesanan — (`/order/{tableCode}/cancel/{orderNumber}`)

**Tujuan:** Konfirmasi pembatalan pesanan.

**Struktur:**
```
[Back Bar]
  ← Batalkan Pesanan

[Ilustrasi Peringatan]
  Icon AlertTriangle 64px #D97706
  "Yakin ingin membatalkan?"
  "Pesanan #ORD-xxx akan dibatalkan dan tidak dapat diproses kembali."

[Detail Pesanan yang Akan Dibatalkan]
  Card abu ringan
  Nama item + qty (summary)
  Total Rp 45.000

[Alasan Pembatalan — Optional]
  Label "Alasan (opsional)"
  Select / Radio:
    ○ Salah pesan
    ○ Terlalu lama
    ○ Berubah pikiran
    ○ Lainnya

[Dua Tombol]
  [Tidak, Kembali]     ← secondary, outline
  [Ya, Batalkan]       ← destructive, background #DC2626 text white
```

**Interaksi:**
- "Ya, Batalkan" → loading → redirect ke history dengan toast "Pesanan berhasil dibatalkan"
- "Tidak, Kembali" → back ke status

---

### 9.7 Halaman Kategori — (`/order/{tableCode}/{category}`)

**Tujuan:** Menampilkan produk dalam satu kategori spesifik.

**Struktur:**
```
[Back Bar]
  ← {Nama Kategori}

[Header Kategori]
  Background gradient primary-700 → primary-500
  Nama Kategori 20px Bold white
  "{n} menu tersedia" 12px primary-100

[Grid Produk 2 Kolom]
  (sama seperti halaman utama, sudah pre-filter kategori)

[Empty State — jika tidak ada produk]
  "Belum ada menu di kategori ini"
```

---

### 9.8 Halaman Profil Toko — (`/profile/{tableCode}`)

**Tujuan:** Info singkat tentang toko.

**Struktur:**
```
[Back Bar]
  ← Profil Toko

[Hero Section]
  Background #fff0f2
  Logo toko (80x80px, radius-full, border 3px white shadow-md)
  Nama Toko — 18px Bold neutral-900
  Alamat     — 13px neutral-500
  No. HP     — 13px neutral-500

[Info Grid — 2 kolom]
  [🏪 Nama Toko]   [📍 Alamat]
  [📞 Kontak]      [⏰ Jam Buka]

[Link]
  Kebijakan Privasi →  ← navigate ke /profile/{tableCode}/privacy

[Footer]
  "Powered by goKasir" 10px neutral-300
```

---

### 9.9 Halaman Privasi — (`/profile/{tableCode}/privacy`)

**Tujuan:** Menampilkan kebijakan privasi.

**Struktur:**
```
[Back Bar]
  ← Kebijakan Privasi

[Konten Artikel]
  Judul section: 14px Semibold neutral-900
  Body text    : 13px Regular neutral-700, line-height 1.7
  Divider      : 1px #F3F4F6 antar section

[Footer]
  "Terakhir diperbarui: {tanggal}" 11px neutral-400
  "Powered by goKasir" 10px neutral-300
```

---

## 10. Detail Produk — Bottom Sheet

Muncul saat tap card produk di halaman manapun:

```
[Drag Handle]

[Gambar Produk]
  width 100%, aspect-ratio 16/9, object-fit cover, radius 12px

[Konten]
  Nama Produk    : 18px Bold neutral-900
  Badge Kategori : chip primary-100 text primary-500 10px
  Harga          : 20px Bold #d30f28
  Deskripsi      : 13px neutral-500, expandable jika panjang

[Catatan Item]
  Input text kecil, placeholder "Catatan (contoh: tanpa gula)"

[Quantity Control]
  [–] {qty} [+]   (horizontal center)

[CTA]
  [Tambahkan ke Keranjang — Rp {subtotal}]
  Tombol primary full-width, disabled jika stok habis

  Jika stok habis:
  [Stok Habis]
  Tombol disabled, background #F3F4F6, text #9CA3AF
```

---

## 11. Loading & Animasi

### Page Transition
```
Masuk  : fade-in 200ms + translateY(8px → 0)
Keluar : fade-out 150ms
```

### Skeleton Loader
```css
@keyframes shimmer {
  0%   { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}
.skeleton {
  background: linear-gradient(90deg, #F3F4F6 25%, #E5E7EB 50%, #F3F4F6 75%);
  background-size: 400px 100%;
  animation: shimmer 1.5s infinite;
}
```

### Pulse (Status Aktif)
```css
@keyframes pulse-ring {
  0%   { transform: scale(1);    opacity: 1; }
  100% { transform: scale(1.8);  opacity: 0; }
}
/* Digunakan pada step aktif di status tracker */
```

### Cart Badge Bounce
```css
@keyframes badge-bounce {
  0%, 100% { transform: scale(1); }
  30%       { transform: scale(1.4); }
  60%       { transform: scale(0.9); }
}
/* Trigger setiap kali item ditambah ke keranjang */
```

### Bottom Sheet Slide Up
```css
@keyframes sheet-up {
  from { transform: translateY(100%); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
.bottom-sheet { animation: sheet-up 250ms cubic-bezier(0.4, 0, 0.2, 1); }
```

---

## 12. Status Tracker Component

Digunakan di halaman status pesanan:

```
Layout     : vertical timeline, gap 20px antar step
Step item  : flex row — [Indicator] [Content]

Indicator:
  Completed : circle 24px background #16A34A, icon Check white 14px
  Active    : circle 24px background #d30f28, dot putih 8px + pulse ring animasi
  Pending   : circle 24px border 2px #D1D5DB, background white

Connector line:
  Completed → Next : solid 2px #16A34A
  Active → Next    : dashed 2px #D1D5DB
  Pending          : dashed 2px #D1D5DB

Content:
  Label    : 14px Semibold neutral-900 (completed/active) / neutral-400 (pending)
  Waktu    : 12px neutral-500 (hanya jika sudah completed)
```

---

## 13. CSS Variables — Master Sheet

```css
:root {
  /* === BRAND (selaras app Flutter goKasir) === */
  --primary-50:  #fff0f2;
  --primary-100: #fce8eb;
  --primary-400: #e83a50;
  --primary-500: #d30f28;
  --primary-600: #b50d23;
  --primary-700: #8a0a1a;

  /* === NEUTRAL === */
  --neutral-50:  #F9FAFB;
  --neutral-100: #F3F4F6;
  --neutral-300: #D1D5DB;
  --neutral-500: #6B7280;
  --neutral-700: #374151;
  --neutral-900: #111827;
  --white:       #FFFFFF;

  /* === SEMANTIC === */
  --success-100: #DCFCE7;
  --success-500: #16A34A;
  --warning-100: #FEF3C7;
  --warning-500: #D97706;
  --danger-100:  #FEE2E2;
  --danger-500:  #DC2626;
  --info-100:    #DBEAFE;
  --info-500:    #2563EB;

  /* === TYPOGRAPHY === */
  --font-family:   'Poppins', sans-serif;
  --text-xs:       10px;
  --text-sm:       12px;
  --text-base:     14px;
  --text-md:       16px;
  --text-lg:       18px;
  --text-xl:       20px;
  --font-regular:  400;
  --font-medium:   500;
  --font-semibold: 600;
  --font-bold:     700;

  /* === SPACING === */
  --space-1:  4px;
  --space-2:  8px;
  --space-3:  12px;
  --space-4:  16px;
  --space-5:  20px;
  --space-6:  24px;
  --space-8:  32px;

  /* === RADIUS === */
  --radius-sm:   4px;
  --radius-md:   8px;
  --radius-lg:   12px;
  --radius-xl:   16px;
  --radius-full: 9999px;

  /* === SHADOW === */
  --shadow-xs:      0 1px 2px rgba(0,0,0,0.05);
  --shadow-sm:      0 2px 8px rgba(0,0,0,0.08);
  --shadow-md:      0 4px 16px rgba(0,0,0,0.10);
  --shadow-lg:      0 8px 32px rgba(0,0,0,0.14);
  --shadow-primary: 0 4px 16px rgba(211,15,40,0.25);

  /* === LAYOUT === */
  --max-width:       480px;
  --page-padding:    16px;
  --header-height:   56px;
  --bottom-bar-height: 64px;
  --safe-bottom:     calc(var(--bottom-bar-height) + 16px);
}
```

---

## 14. Aksesibilitas & UX

| Aspek | Implementasi |
|---|---|
| Tap target minimum | 44x44px untuk semua elemen interaktif |
| Kontras teks | Minimum 4.5:1 (WCAG AA) — semua teks di atas #d30f28 pakai putih |
| Font size minimum | 12px (tidak ada teks di bawah 10px untuk konten) |
| Loading feedback | Semua aksi async tampilkan skeleton atau spinner |
| Error state | Pesan error spesifik, bukan hanya "Terjadi kesalahan" |
| Scroll momentum | `-webkit-overflow-scrolling: touch` untuk scroll horizontal |
| Image fallback | Placeholder icon jika gambar gagal dimuat |
| Offline state | Toast "Koneksi terputus, mencoba ulang..." |

---

## 15. Mapping Halaman ↔ Komponen

| Halaman | Header | Nav | Komponen Utama |
|---|---|---|---|
| `/order/{code}` | Sticky Header | Bottom Nav | Grid Produk, Chip Kategori, Search Bar |
| `/order/{code}/search` | Back Bar | — | Search Input, List Card |
| `/order/{code}/checkout` | Back Bar | — | List Item, Ringkasan Biaya, Metode Bayar |
| `/order/{code}/status/{no}` | Back Bar | — | Status Tracker, Order Card |
| `/order/{code}/history` | Back Bar | — | Filter Chip, List Pesanan Card |
| `/order/{code}/cancel/{no}` | Back Bar | — | Konfirmasi Dialog, Alasan Radio |
| `/order/{code}/{category}` | Back Bar | — | Grid Produk (pre-filter) |
| `/profile/{code}` | Back Bar | — | Hero Info, Info Grid |
| `/profile/{code}/privacy` | Back Bar | — | Artikel teks |

---

*goKasir Web Order — Design System v1.0*
*Mobile-first · Tema selaras Flutter goKasir · Primary #d30f28 · Poppins*