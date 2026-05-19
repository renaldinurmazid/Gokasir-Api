# GoKasir Landing Page — Design Specification

> **Tagline:** Kasir Cerdas · Bisnis Sukses  
> **Versi:** 1.0.0  
> **Terakhir diperbarui:** Mei 2026

---

## 1. Brand Identity

### Logo
- Ikon: shopping cart merah dengan checkmark putih, pixel squares emas di pojok kanan atas
- Wordmark: **"Go"** (merah) + **"Kasir"** (navy), font weight 800
- Tagline: `KASIR CERDAS · BISNIS SUKSES` — spasi huruf lebar, weight 500
- Ukuran minimum logo: 120px wide (digital), 30mm (print)

### Brand Voice
- Percaya diri, hangat, dan memberdayakan
- Bahasa Indonesia yang natural — tidak kaku, tidak terlalu kasual
- Fokus pada manfaat nyata, bukan fitur teknis semata

---

## 2. Color System

```
--red:        #E31B23   /* Primary — aksi, highlight, brand */
--red-dark:   #B91219   /* Hover state primary */
--red-light:  #FF4D55   /* Tint, latar ilustrasi */
--navy:       #1A2233   /* Teks utama, background dark */
--navy-mid:   #2B3A55   /* Teks sekunder, elemen UI */
--gold:       #F5A623   /* Aksen, badge, pixel squares logo */
--gold2:      #F7C548   /* Gold terang, dark background */
--white:      #FFFFFF
--off:        #F7F8FC   /* Background section terang */
--gray:       #8894AA   /* Body text, label */
--border:     #E8ECF4   /* Garis pemisah, border card */
```

**Penggunaan warna:**
- Merah digunakan hanya untuk elemen aksi dan brand — jangan gunakan berlebihan
- Navy sebagai warna dominan teks dan elemen struktural
- Gold terbatas pada aksen, badge, dan highlight data penting
- Background bergantian antara `#FFFFFF` dan `#F7F8FC` antar section

---

## 3. Typography

**Font Family:** [Poppins](https://fonts.google.com/specimen/Poppins) — Google Fonts

```
Import: https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap
```

### Skala Tipografi

| Role             | Size (clamp)              | Weight | Color       |
|------------------|---------------------------|--------|-------------|
| Hero H1          | clamp(2.2rem, 5vw, 3.4rem)| 900    | --navy      |
| Section Title H2 | clamp(1.8rem, 4vw, 2.6rem)| 800    | --navy      |
| Card Title H3    | 1.05rem                   | 700    | --navy      |
| Step Title H4    | 0.95rem                   | 700    | --navy      |
| Body / Paragraf  | 1rem – 1.05rem            | 400    | --gray      |
| Caption / Label  | 0.72rem – 0.85rem         | 500–600| --gray      |
| Eyebrow / Tag    | 0.72rem – 0.78rem         | 700    | --red       |
| Price Large      | 2.2rem                    | 900    | --navy      |
| Stat Number      | 1.6rem                    | 900    | --navy      |
| Button           | 0.85rem – 0.95rem         | 700    | varies      |

### Aturan Tipografi
- Line-height body: **1.7**
- Line-height heading: **1.15 – 1.2**
- Letter-spacing eyebrow/tag: **0.08em – 0.12em + uppercase**
- Teks em berwarna merah digunakan untuk kata kunci di headline
- Hindari lebih dari 65 karakter per baris pada body text

---

## 4. Spacing & Layout

### Grid
- Container max-width: **1160px**
- Container padding: **0 28px**
- Section padding: **100px 0**

### Spacing Scale
```
4px   — micro gap (badge dot, ikon kecil)
8px   — gap inline
12px  — gap elemen dalam card
16px  — padding dalam badge / tag
20px  — gap step item
24px  — gap antar card dalam grid
28px  — padding card / container horizontal
32px  — padding card besar
36px  — padding pricing card
48px  — gap footer columns
60px  — gap hero grid / section header margin
80px  — gap how-it-works grid
100px — section vertical padding
```

### Grid Layout per Section
- Features: `repeat(3, 1fr)` → tablet `repeat(2, 1fr)` → mobile `1fr`
- Hero: `1fr 1fr` → mobile `1fr`
- How it works: `1fr 1fr` → mobile `1fr`
- Testimonials: `repeat(3, 1fr)` → mobile `1fr`
- Pricing: `repeat(3, 1fr)` → mobile `1fr`
- Footer: `1.6fr 1fr 1fr 1fr` → tablet `1fr 1fr` → mobile `1fr`

### Border Radius
```
--r: 16px   /* card umum */
10px        /* elemen dalam card, badge kecil */
12px        /* tombol */
14px        /* badge float hero */
20px        /* pricing card, dashboard illustration */
36px        /* phone mockup */
50px        /* pill/tag */
50%         /* avatar, dot, step number */
```

---

## 5. Komponen UI

### Navbar
- Position: `fixed`, top 0
- Background: `rgba(255,255,255,.92)` + `backdrop-filter: blur(16px)`
- Border bottom: `1px solid var(--border)`
- Height: **68px**
- Shadow muncul saat scroll > 10px
- Elemen: Logo kiri | Nav links tengah | CTA button kanan

### Button

**Primary (btn-primary)**
```
bg: --red
color: white
border-radius: 12px
padding: 15px 30px
font-weight: 700
box-shadow: 0 8px 30px rgba(227,27,35,.35)
hover: translateY(-2px), shadow lebih besar
```

**Ghost (btn-ghost)**
```
bg: transparent
border: 2px solid --border
border-radius: 12px
padding: 13px 28px
hover: border --red, text --red
```

**Nav CTA (btn-nav)**
```
bg: --red
border-radius: 10px
padding: 10px 24px
font-size: .85rem
```

**CTA White (dark section)**
```
bg: white
color: --navy
border-radius: 12px
padding: 15px 30px
```

### Tag / Eyebrow Label
```
bg: rgba(227,27,35,.08)
color: --red
border-radius: 50px
padding: 6px 14px
font-size: .72rem
font-weight: 700
letter-spacing: .12em
text-transform: uppercase
```

### Feature Card
- Background: white, border `1px solid --border`
- Border radius: 16px, padding: 32px 28px
- Hover: `translateY(-6px)` + shadow + top red bar animasi (scaleX)
- Icon container: 56×56px, bg `rgba(--red,.08)`, border-radius 14px

### Pricing Card — Popular
- Background: `--navy`
- Border: `2px solid --red`
- Badge "PALING POPULER" melayang di atas card
- Button: solid merah dengan shadow

---

## 6. Ilustrasi & Aset Visual

### Filosofi Ilustrasi
Semua ilustrasi dibuat sebagai **inline SVG** — tidak bergantung aset eksternal. Ilustrasi bersifat fungsional (mockup UI nyata) bukan dekoratif abstrak.

### Hero — Phone Mockup
- Dimensi: 280px wide
- Frame: dark navy `#1A2233`, border-radius 36px
- Header merah dengan total transaksi
- Body: 3 item POS dengan ikon berwarna (merah/gold/hijau)
- Footer: tombol bayar merah
- Animasi: `float` — translateY naik-turun 18px, durasi 4s infinite

**Floating badges (3 buah):**
- Badge 1 (kanan atas): "Transaksi berhasil" + dot hijau
- Badge 2 (kiri bawah): "Stok diperbarui" + ikon bintang gold
- Badge 3 (kanan bawah): "Struk dicetak" + ikon printer merah
- Style: white card, border-radius 14px, shadow lembut

### How It Works — Dashboard Dark Card
- Background: `--navy`
- Header bar merah dengan 3 dot + title
- KPI row (3 kolom): Omzet, Transaksi, Profit dengan warna berbeda
- Bar chart 7 batang dengan warna merah & gold
- Tabel produk terlaris dengan dot berwarna

### Ikon Fitur (SVG Stroke)
Semua ikon menggunakan stroke style, `stroke="#E31B23"`, `stroke-width: 2`, `stroke-linecap: round`.

| Fitur              | Ikon             |
|--------------------|------------------|
| Transaksi          | Credit card      |
| Manajemen Stok     | Archive/box      |
| Thermal Printer    | Printer          |
| Laporan            | Bar chart lines  |
| Multi-kasir        | Users/group      |
| Loyalitas          | Heart            |

---

## 7. Animasi & Motion

### Scroll Reveal (Fade Up)
```css
.fade-up {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity .6s ease, transform .6s ease;
}
.fade-up.visible {
  opacity: 1;
  transform: none;
}
```
Trigger: IntersectionObserver threshold 0.12

**Stagger delay per card:**
- Card 2: `transition-delay: .08s`
- Card 3: `transition-delay: .16s`
- Card 4: `transition-delay: .24s`
- Card 5: `transition-delay: .32s`
- Card 6: `transition-delay: .40s`

### Float Animation (Hero Phone)
```css
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-18px); }
}
animation: float 4s ease-in-out infinite;
```

### Feature Card Hover
- Top bar: `transform: scaleX(0)` → `scaleX(1)`, `transform-origin: left`, durasi .3s
- Card: `translateY(-6px)` + box-shadow, durasi .25s

### Button Hover
- Primary: `translateY(-2px)` + shadow lebih besar
- Ghost/Outline: perubahan border-color dan text-color saja

---

## 8. Background & Dekorasi

### Radial Gradient Blobs
Digunakan di Hero dan CTA section sebagai dekorasi ambient:
```css
/* Hero kanan atas */
background: radial-gradient(circle, rgba(227,27,35,.10) 0%, transparent 70%);
width: 600px; height: 600px; top: -180px; right: -180px;

/* Hero kiri bawah */
background: radial-gradient(circle, rgba(245,166,35,.10) 0%, transparent 70%);
width: 400px; height: 400px;
```

### Section Background
- Section 1 (Hero): `linear-gradient(135deg, #fff 55%, #fff5f5 100%)`
- Section 2 (Features): `#F7F8FC`
- Section 3 (How it Works): `#FFFFFF`
- Section 4 (Testimonials): `#F7F8FC`
- Section 5 (Pricing): `#FFFFFF`
- Section 6 (CTA): `#1A2233` (dark)
- Footer: `#0F1624`

---

## 9. Struktur Halaman & Konten

```
┌─────────────────────────────────┐
│  NAVBAR (fixed)                 │
│  Logo | Menu | CTA Button       │
├─────────────────────────────────┤
│  HERO                           │
│  Eyebrow · H1 · Body · CTA     │
│  Stats · Phone Mockup + Badges  │
├─────────────────────────────────┤
│  FITUR (6 cards, 3 kolom)       │
│  Tag · H2 · Sub · Card Grid     │
├─────────────────────────────────┤
│  CARA KERJA (2 kolom)           │
│  Dashboard Illustration · Steps │
├─────────────────────────────────┤
│  TESTIMONI (3 cards)            │
│  Stars · Quote · Author         │
├─────────────────────────────────┤
│  HARGA (3 tier)                 │
│  Starter · Pro (featured) · Ent │
├─────────────────────────────────┤
│  CTA SECTION (dark)             │
│  Ilustrasi · H2 · Dua tombol    │
├─────────────────────────────────┤
│  FOOTER                         │
│  Brand · 3 kolom link · Bottom  │
└─────────────────────────────────┘
```

### Konten per Section

**Hero**
- Eyebrow: "Kasir Digital #1 untuk UMKM Indonesia"
- H1: "Kasir **Cerdas**, Bisnis **Sukses**"
- Body: deskripsi 2 kalimat, fokus benefit
- CTA utama: "Mulai Gratis 14 Hari" (merah)
- CTA sekunder: "Lihat Demo" (ghost)
- Stats: 50K+ Pengguna · 120M+ Transaksi · 4.9★ Rating

**Features (6 item)**
1. Transaksi Super Cepat — barcode, QRIS
2. Manajemen Stok Otomatis — notifikasi, multi-gudang
3. Cetak Struk Thermal — BT/WiFi, custom desain
4. Laporan Bisnis Real-Time — ekspor Excel/PDF
5. Multi-Kasir & Multi-Toko — hak akses, monitoring
6. Manajemen Pelanggan & Loyalitas — poin, diskon

**How It Works (3 langkah)**
1. Daftar & Setup Toko
2. Mulai Transaksi
3. Pantau & Kembangkan

**Pricing**

| Tier       | Harga          | Highlight                   |
|------------|----------------|-----------------------------|
| Starter    | Gratis         | 1 kasir, 50 produk          |
| Pro        | Rp 149K/bln    | 5 kasir, stok penuh, QRIS   |
| Enterprise | Rp 399K/bln    | Unlimited, API, franchise   |

---

## 10. Responsif & Breakpoints

| Breakpoint | Lebar       | Perubahan Layout                          |
|------------|-------------|-------------------------------------------|
| Desktop    | > 900px     | Layout penuh, semua kolom                 |
| Tablet     | ≤ 900px     | Hero & How: 1 kolom, Features: 2 kolom   |
| Mobile     | ≤ 600px     | Semua: 1 kolom, nav links disembunyikan   |

**Elemen yang disembunyikan di mobile:**
- `.hero-visual` (phone mockup) — `display: none`
- `.nav-links` — `display: none`

---

## 11. Aksesibilitas

- Semua tombol dan link menggunakan tag semantik `<a>` atau `<button>`
- Kontras teks memenuhi standar WCAG AA minimum
- Font size minimum 12px (0.72rem)
- Navigasi anchor dengan `scroll-behavior: smooth`
- Elemen interaktif memiliki state hover yang jelas
- Warna tidak menjadi satu-satunya penanda informasi

---

## 12. Aset & Dependensi

| Aset            | Sumber                                      |
|-----------------|---------------------------------------------|
| Font Poppins    | Google Fonts CDN                            |
| Semua ikon      | Inline SVG (tidak ada dependensi eksternal) |
| Ilustrasi       | Inline SVG custom                           |
| Animasi         | CSS-only + IntersectionObserver JS native   |
| Framework       | Vanilla HTML/CSS/JS — nol dependensi        |

**File output:** `gokasir-landing.html` — single file, self-contained