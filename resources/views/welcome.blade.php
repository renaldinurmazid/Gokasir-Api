<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>GoKasir — Kasir Cerdas · Bisnis Sukses</title>

    <meta name="description" content="GoKasir adalah aplikasi kasir pintar digital terbaik untuk UMKM Indonesia. Kelola stok otomatis, terima pembayaran QRIS, cetak struk thermal, dan pantau laporan keuangan real-time.">
    <meta name="keywords" content="aplikasi kasir, pos system, kasir digital, kasir pintar, qris umkm, pembukuan digital, gokasir">
    <meta name="author" content="GoKasir">

    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        :root {
            --red:        #E31B23;   /* Primary — aksi, highlight, brand */
            --red-dark:   #B91219;   /* Hover state primary */
            --red-light:  #FF4D55;   /* Tint, latar ilustrasi */
            --navy:       #1A2233;   /* Teks utama, background dark */
            --navy-mid:   #2B3A55;   /* Teks sekunder, elemen UI */
            --gold:       #F5A623;   /* Aksen, badge, pixel squares logo */
            --gold2:      #F7C548;   /* Gold terang, dark background */
            --white:      #FFFFFF;
            --off:        #F7F8FC;   /* Background section terang */
            --gray:       #8894AA;   /* Body text, label */
            --border:     #E8ECF4;   /* Garis pemisah, border card */
            --r:          16px;      /* Card radius umum */
            
            --font-main: 'Plus Jakarta Sans', sans-serif;
        }

        /* Global Reset */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--white);
            color: var(--navy);
            line-height: 1.7;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4 {
            line-height: 1.2;
            font-weight: 800;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        /* Ambient Decoration Blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .blob-hero-1 {
            background: radial-gradient(circle, rgba(227, 27, 35, 0.08) 0%, transparent 70%);
            width: 600px;
            height: 600px;
            top: -180px;
            right: -180px;
        }

        .blob-hero-2 {
            background: radial-gradient(circle, rgba(245, 166, 35, 0.06) 0%, transparent 70%);
            width: 400px;
            height: 400px;
            bottom: -100px;
            left: -100px;
        }

        .blob-cta-1 {
            background: radial-gradient(circle, rgba(255, 77, 85, 0.12) 0%, transparent 70%);
            width: 500px;
            height: 500px;
            top: -200px;
            left: -200px;
        }

        .blob-cta-2 {
            background: radial-gradient(circle, rgba(245, 166, 35, 0.12) 0%, transparent 70%);
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
        }

        /* Container & Layout */
        .container {
            max-width: 1160px;
            margin: 0 auto;
            padding: 0 28px;
            position: relative;
            z-index: 2;
        }

        section {
            padding: 100px 0;
            position: relative;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 8px 30px rgba(26, 34, 51, 0.06);
            background: rgba(255, 255, 255, 0.96);
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }

        /* Logo Identity */
        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .logo-text .wordmark {
            font-size: 1.4rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .logo-text .wordmark .go {
            color: var(--red);
        }

        .logo-text .wordmark .kasir {
            color: var(--navy);
        }

        .logo-text .tagline {
            font-size: 0.52rem;
            font-weight: 700;
            color: var(--navy-mid);
            letter-spacing: 0.14em;
            margin-top: 3px;
        }

        /* Menu Links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-link {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--navy-mid);
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--red);
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: var(--red);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Interactive Hamburger Button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            flex-direction: column;
            gap: 6px;
        }

        .menu-toggle span {
            display: block;
            width: 24px;
            height: 2px;
            background-color: var(--navy);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Buttons Styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-main);
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: center;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--red);
            color: var(--white);
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 8px 30px rgba(227, 27, 35, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(227, 27, 35, 0.45);
            background-color: var(--red-dark);
        }

        .btn-ghost {
            background-color: transparent;
            border: 2px solid var(--border);
            color: var(--navy-mid);
            border-radius: 12px;
            padding: 13px 28px;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .btn-ghost:hover {
            border-color: var(--red);
            color: var(--red);
        }

        .btn-nav {
            background-color: var(--red);
            color: var(--white);
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .btn-nav:hover {
            background-color: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(227, 27, 35, 0.25);
        }

        .btn-white {
            background-color: var(--white);
            color: var(--navy);
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        /* 1. Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #ffffff 55%, #fff5f5 100%);
            padding-top: 150px;
            padding-bottom: 100px;
            overflow: hidden;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 60px;
            align-items: center;
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .tag-eyebrow {
            background: rgba(227, 27, 35, 0.08);
            color: var(--red);
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 20px;
            display: inline-block;
        }

        .hero-title {
            font-size: clamp(2.2rem, 5vw, 3.4rem);
            font-weight: 900;
            color: var(--navy);
            line-height: 1.15;
            margin-bottom: 24px;
            letter-spacing: -0.01em;
        }

        .hero-title em {
            font-style: normal;
            color: var(--red);
        }

        .hero-body {
            font-size: 1.05rem;
            color: var(--gray);
            line-height: 1.7;
            max-width: 580px;
            margin-bottom: 36px;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            margin-bottom: 48px;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
            border-top: 1px solid var(--border);
            padding-top: 28px;
            width: 100%;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-number {
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--navy);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
            font-weight: 500;
            margin-top: 4px;
        }

        /* Hero Visual & Mockup composition */
        .hero-visual {
            position: relative;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-composition {
            position: relative;
            width: 100%;
            max-width: 440px;
            height: 440px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Layered cashier welcome illustration in the background */
        .hero-main-img {
            width: 90%;
            height: auto;
            max-height: 380px;
            object-fit: contain;
            opacity: 0.95;
            filter: drop-shadow(0 20px 40px rgba(26, 34, 51, 0.08));
            border-radius: 24px;
            margin-right: 50px;
        }

        /* Floating Phone Mockup */
        .phone-mockup {
            position: absolute;
            top: 2%;
            right: -2%;
            width: 250px;
            height: 430px;
            background-color: var(--navy);
            border: 8px solid #2B3A55;
            border-radius: 36px;
            box-shadow: 0 30px 60px rgba(26, 34, 51, 0.22);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 10;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-18px); }
        }

        .phone-header {
            background-color: var(--red);
            color: var(--white);
            padding: 14px 14px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .phone-status-bar {
            display: flex;
            justify-content: space-between;
            font-size: 0.6rem;
            opacity: 0.8;
            font-weight: 600;
        }

        .phone-total {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .total-label {
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.85;
        }

        .total-amount {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .phone-body {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex-grow: 1;
            background-color: #1A2233;
        }

        .pos-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.04);
            padding: 8px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .bg-green { background-color: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .bg-gold { background-color: rgba(245, 166, 35, 0.15); color: #F5A623; }
        .bg-blue { background-color: rgba(52, 152, 219, 0.15); color: #3498db; }

        .item-info {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .item-name {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--white);
        }

        .item-qty {
            font-size: 0.58rem;
            color: var(--gray);
        }

        .item-price {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--white);
        }

        .phone-footer {
            padding: 10px 12px;
            background-color: #151B29;
        }

        .phone-pay-btn {
            width: 100%;
            background-color: var(--red);
            color: var(--white);
            border: none;
            padding: 9px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.05em;
        }

        /* Floating badges over composition */
        .float-badge {
            position: absolute;
            background-color: var(--white);
            padding: 8px 14px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(26, 34, 51, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 20;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .float-badge:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(26, 34, 51, 0.12);
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .dot-green { background-color: #2ecc71; box-shadow: 0 0 8px #2ecc71; }
        .badge-icon { font-size: 0.85rem; }
        .badge-text {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--navy);
            white-space: nowrap;
        }

        .float-badge-1 { top: 12%; left: -8%; }
        .float-badge-2 { bottom: 12%; left: -6%; }
        .float-badge-3 { bottom: 6%; right: 18%; }

        /* 2. Fitur Section */
        .features-section {
            background-color: var(--off);
        }

        .section-header {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 60px;
        }

        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 800;
            color: var(--navy);
            line-height: 1.2;
            margin-top: 12px;
            letter-spacing: -0.01em;
        }

        .section-subtitle {
            font-size: 1.05rem;
            color: var(--gray);
            margin-top: 16px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-card {
            background-color: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--red);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(26, 34, 51, 0.08);
            border-color: transparent;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon-wrapper {
            width: 56px;
            height: 56px;
            background-color: rgba(227, 27, 35, 0.08);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .feature-icon-wrapper svg {
            transition: stroke 0.3s ease;
        }

        .feature-card:hover .feature-icon-wrapper {
            background-color: var(--red);
        }

        .feature-card:hover .feature-icon-wrapper svg {
            stroke: var(--white);
        }

        .feature-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 12px;
        }

        .feature-description {
            font-size: 0.95rem;
            color: var(--gray);
            line-height: 1.6;
        }

        /* 3. Cara Kerja Section */
        .how-section {
            background-color: var(--white);
        }

        .how-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 80px;
            align-items: center;
        }

        .how-visual-container {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 30px;
            width: 100%;
        }

        /* Dashboard Dark Card */
        .dashboard-dark-card {
            background-color: var(--navy);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 45px rgba(26, 34, 51, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
            width: 100%;
            max-width: 520px;
        }

        .dash-header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .window-controls {
            display: flex;
            gap: 6px;
        }

        .window-controls .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-red { background-color: #ff5f56; }
        .dot-yellow { background-color: #ffbd2e; }
        .dot-green { background-color: #27c93f; }

        .dash-title {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--gray);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 12px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
        }

        .kpi-label {
            font-size: 0.58rem;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.03em;
        }

        .kpi-value {
            font-size: 0.85rem;
            font-weight: 800;
        }

        .kpi-value.text-red { color: var(--red-light); }
        .kpi-value.text-gold { color: var(--gold2); }
        .kpi-value.text-green { color: #2ecc71; }

        .kpi-trend {
            font-size: 0.55rem;
            font-weight: 700;
            margin-top: 4px;
            color: #2ecc71;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .dash-body-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 16px;
        }

        .chart-container, .table-container {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 14px;
        }

        .panel-title {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--white);
            display: block;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.9;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 90px;
            padding-top: 10px;
        }

        .bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            flex-grow: 1;
        }

        .bar {
            width: 12px;
            border-radius: 4px 4px 0 0;
            background-color: var(--red);
        }

        .bar.bg-gold { background-color: var(--gold); }

        .bar-col span {
            font-size: 0.52rem;
            color: var(--gray);
        }

        .dash-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dash-table th, .dash-table td {
            padding: 6px;
            text-align: left;
            font-size: 0.62rem;
        }

        .dash-table th {
            color: var(--gray);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-weight: 600;
        }

        .dash-table td {
            color: var(--white);
        }

        .badge {
            font-size: 0.55rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        .badge-green { background-color: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .badge-yellow { background-color: rgba(241, 196, 15, 0.15); color: #f1c40f; }

        /* Secondary scanning illustration floating card */
        .scanner-illustration-card {
            position: absolute;
            bottom: -30px;
            right: -30px;
            background-color: var(--white);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 15px 35px rgba(26, 34, 51, 0.12);
            border: 1px solid var(--border);
            width: 190px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10;
            animation: float 4s ease-in-out infinite;
            animation-delay: 1.5s;
        }

        .scanner-badge {
            font-size: 0.55rem;
            background-color: rgba(227, 27, 35, 0.08);
            color: var(--red);
            padding: 4px 8px;
            border-radius: 40px;
            font-weight: 700;
            display: inline-block;
            letter-spacing: 0.05em;
        }

        .scanner-img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
        }

        /* Step Items column */
        .how-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .how-steps {
            display: flex;
            flex-direction: column;
            gap: 28px;
            margin-top: 10px;
        }

        .step-item {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .step-number {
            width: 42px;
            height: 42px;
            background-color: rgba(227, 27, 35, 0.08);
            color: var(--red);
            font-weight: 700;
            font-size: 1.05rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .step-item:hover .step-number {
            background-color: var(--red);
            color: var(--white);
            transform: scale(1.08);
        }

        .step-content {
            display: flex;
            flex-direction: column;
        }

        .step-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 6px;
        }

        .step-description {
            font-size: 0.95rem;
            color: var(--gray);
            line-height: 1.6;
        }

        /* 4. Testimoni Section */
        .testimonials-section {
            background-color: var(--off);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .testimonial-card {
            background-color: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 36px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .testimonial-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(26, 34, 51, 0.05);
        }

        .stars {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            color: var(--gold);
        }

        .quote {
            font-size: 0.95rem;
            color: var(--navy-mid);
            line-height: 1.7;
            margin-bottom: 24px;
            font-weight: 400;
            font-style: italic;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-initial {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 1rem;
        }

        .bg-avatar-1 { background-color: #3498db; }
        .bg-avatar-2 { background-color: #2ecc71; }
        .bg-avatar-3 { background-color: #9b59b6; }

        .author-details {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--navy);
        }

        .author-business {
            font-size: 0.78rem;
            color: var(--gray);
            font-weight: 500;
        }

        /* 5. Harga Section */
        .pricing-section {
            background-color: var(--white);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            align-items: stretch;
            margin-top: 20px;
        }

        .pricing-card {
            background-color: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .pricing-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(26, 34, 51, 0.08);
        }

        /* Highlighted Pro Plan styling */
        .pricing-card.popular {
            background-color: var(--navy);
            border: 2px solid var(--red);
            color: var(--white);
            box-shadow: 0 20px 40px rgba(227, 27, 35, 0.15);
        }

        .pricing-card.popular:hover {
            box-shadow: 0 25px 45px rgba(227, 27, 35, 0.25);
        }

        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--red);
            color: var(--white);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 50px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(227, 27, 35, 0.3);
            white-space: nowrap;
        }

        .pricing-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 24px;
            margin-bottom: 28px;
        }

        .pricing-card.popular .pricing-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        .pricing-tier {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--red);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
        }

        .pricing-card.popular .pricing-tier {
            color: var(--gold2);
        }

        .pricing-price {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--navy);
            margin-top: 8px;
            display: flex;
            align-items: baseline;
        }

        .pricing-card.popular .pricing-price {
            color: var(--white);
        }

        .pricing-price span {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray);
            margin-left: 4px;
        }

        .pricing-card.popular .pricing-price span {
            color: #a5b1c2;
        }

        .pricing-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 36px;
            flex-grow: 1;
        }

        .pricing-feature-item {
            font-size: 0.92rem;
            color: var(--navy-mid);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-card.popular .pricing-feature-item {
            color: #d1d8e0;
        }

        .pricing-feature-item svg {
            flex-shrink: 0;
            stroke: var(--red);
        }

        .pricing-card.popular .pricing-feature-item svg {
            stroke: var(--gold2);
        }

        .pricing-action {
            width: 100%;
        }

        /* 6. CTA Section */
        .cta-section {
            background-color: var(--navy);
            color: var(--white);
            overflow: hidden;
            padding: 100px 0;
        }

        .cta-grid {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 60px;
            align-items: center;
        }

        .cta-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .cta-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
            letter-spacing: -0.01em;
        }

        .cta-subtitle {
            font-size: 1.05rem;
            color: #d1d8e0;
            margin-bottom: 32px;
            max-width: 580px;
            line-height: 1.7;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
        }

        .cta-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .cta-composition {
            position: relative;
            width: 100%;
            max-width: 320px;
        }

        .cta-main-img {
            width: 100%;
            height: auto;
            border-radius: 20px;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));
            border: 1px solid rgba(255, 255, 255, 0.08);
            transform: rotate(2deg);
            transition: all 0.5s ease;
        }

        .cta-main-img:hover {
            transform: rotate(0deg) scale(1.03);
        }

        /* 7. Footer Section */
        .footer {
            background-color: #0F1624;
            color: #8894AA;
            padding: 80px 0 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 60px;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .footer-brand .logo-text .wordmark .kasir {
            color: var(--white);
        }

        .footer-brand .logo-text .tagline {
            color: var(--gray);
        }

        .footer-desc {
            font-size: 0.92rem;
            line-height: 1.6;
            max-width: 280px;
        }

        .footer-col {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .footer-col-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-link {
            font-size: 0.9rem;
            color: #8894AA;
        }

        .footer-link:hover {
            color: var(--white);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .copyright {
            font-size: 0.85rem;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 36px;
            height: 36px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8894AA;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background-color: var(--red);
            color: var(--white);
            transform: translateY(-2px);
        }

        .social-link svg {
            fill: currentColor;
        }

        /* Scroll Reveal Animation Styles */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-up.visible {
            opacity: 1;
            transform: none;
        }

        /* Delay Classes for stagger effect */
        .delay-1 { transition-delay: 0.08s; }
        .delay-2 { transition-delay: 0.16s; }
        .delay-3 { transition-delay: 0.24s; }
        .delay-4 { transition-delay: 0.32s; }
        .delay-5 { transition-delay: 0.40s; }

        /* Responsiveness and Breakpoints */

        /* Tablet Viewport (<= 900px) */
        @media (max-width: 900px) {
            section {
                padding: 80px 0;
            }

            .navbar .container {
                position: relative;
            }

            .nav-links {
                display: flex;
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background-color: var(--white);
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.05);
                flex-direction: column;
                align-items: flex-start;
                padding: 100px 40px;
                gap: 24px;
                transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
                z-index: 999;
            }

            .nav-links.mobile-active {
                right: 0;
            }

            .menu-toggle {
                display: flex;
                z-index: 1001;
            }

            /* Hamburger animation */
            .menu-toggle.open span:nth-child(1) {
                transform: rotate(45deg) translate(6px, 6px);
            }
            .menu-toggle.open span:nth-child(2) {
                opacity: 0;
            }
            .menu-toggle.open span:nth-child(3) {
                transform: rotate(-45deg) translate(5px, -5px);
            }

            .navbar .btn-nav {
                display: none;
            }

            /* Main Grids to 1 column */
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 50px;
                text-align: center;
            }

            .hero-content {
                align-items: center;
            }

            .hero-actions {
                justify-content: center;
                width: 100%;
            }

            .hero-stats {
                justify-content: center;
                gap: 30px;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .how-grid {
                grid-template-columns: 1fr;
                gap: 60px;
            }

            .how-steps {
                width: 100%;
            }

            .testimonials-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pricing-grid {
                grid-template-columns: 1.5fr;
                justify-content: center;
                gap: 32px;
            }

            .cta-grid {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 40px;
            }

            .cta-content {
                align-items: center;
            }

            .cta-buttons {
                justify-content: center;
                width: 100%;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 36px;
            }
        }

        /* Mobile Viewport (<= 600px) */
        @media (max-width: 600px) {
            section {
                padding: 60px 0;
            }

            .hero-section {
                padding-top: 120px;
                padding-bottom: 60px;
            }

            /* Hide phone mockup on mobile for clean UI */
            .hero-visual {
                display: none;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .scanner-illustration-card {
                position: relative;
                bottom: auto;
                right: auto;
                width: 100%;
                margin-top: 15px;
                box-shadow: 0 8px 25px rgba(26, 34, 51, 0.06);
            }

            .dashboard-dark-card {
                padding: 16px;
            }

            .dash-body-grid {
                grid-template-columns: 1fr;
            }

            .kpi-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .hero-actions, .cta-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
            }

            .hero-stats {
                flex-wrap: wrap;
                justify-content: space-around;
                gap: 20px;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR (fixed) -->
    <nav class="navbar">
        <div class="container">
            <a href="#" class="logo">
                <div class="logo-wrapper">
                    <!-- Custom Inline SVG Logo complying with design.md rules -->
                    <svg class="logo-icon" width="36" height="36" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Shopping Cart Red path -->
                        <path d="M5 6H9L12.5 23H31L35 11H13" stroke="#E31B23" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="15" cy="30" r="3" fill="#E31B23" />
                        <circle cx="28" cy="30" r="3" fill="#E31B23" />
                        <!-- Red badge for checkmark -->
                        <circle cx="22" cy="16" r="6" fill="#E31B23" />
                        <path d="M19.5 16L21 17.5L24.5 14" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Pixel Squares Emas di pojok kanan atas -->
                        <rect x="30" y="3" width="4" height="4" fill="#F5A623" />
                        <rect x="34" y="7" width="4" height="4" fill="#F5A623" />
                        <rect x="30" y="7" width="4" height="4" fill="#F5A623" opacity="0.5" />
                    </svg>
                    <div class="logo-text">
                        <span class="wordmark"><span class="go">Go</span><span class="kasir">Kasir</span></span>
                        <span class="tagline">KASIR CERDAS · BISNIS SUKSES</span>
                    </div>
                </div>
            </a>

            <!-- Menu Navigation -->
            <div class="nav-links">
                <a href="#fitur" class="nav-link">Fitur</a>
                <a href="#cara-kerja" class="nav-link">Cara Kerja</a>
                <a href="#testimoni" class="nav-link">Testimoni</a>
                <a href="#harga" class="nav-link">Harga</a>
            </div>

            <!-- CTA Button -->
            <a href="https://play.google.com/store/apps/details?id=com.gokasir.net" target="_blank" rel="noopener noreferrer" class="btn btn-nav">Download Aplikasi</a>

            <!-- Mobile Hamburger Button -->
            <button class="menu-toggle" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <header class="hero-section">
        <!-- Radial Gradient Blobs for ambient decoration -->
        <div class="blob blob-hero-1"></div>
        <div class="blob blob-hero-2"></div>

        <div class="container">
            <div class="hero-grid">
                <!-- Hero Left Column: Texts and actions -->
                <div class="hero-content fade-up">
                    <span class="tag-eyebrow">Kasir Digital #1 untuk UMKM Indonesia</span>
                    <h1 class="hero-title">Kasir <em>Cerdas</em>,<br>Bisnis <em>Sukses</em></h1>
                    <p class="hero-body">Kelola penjualan, pantau stok barang real-time, dan terima pembayaran digital QRIS dalam satu aplikasi kasir pintar. Dirancang khusus untuk wirausaha Indonesia yang ingin berkembang lebih cepat dan teratur.</p>
                    
                    <div class="hero-actions">
                        <a href="https://play.google.com/store/apps/details?id=com.gokasir.net" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Download Aplikasi</a>
                        <a href="#harga" class="btn btn-ghost">Lihat Harga</a>
                    </div>

                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number">50K+</span>
                            <span class="stat-label">Pengguna Aktif</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">120jt+</span>
                            <span class="stat-label">Transaksi Berhasil</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">4.9★</span>
                            <span class="stat-label">Rating Pengguna</span>
                        </div>
                    </div>
                </div>

                <!-- Hero Right Column: Layout composition with illustration and mockup -->
                <div class="hero-visual fade-up delay-1">
                    <div class="hero-composition">
                        <!-- Base illustration from public/illustration -->
                        <img src="{{ asset('illustration/illustration_cashier_welcome.png') }}" class="hero-main-img" alt="Ilustrasi Kasir Menyambut Pelanggan">
                        
                        <!-- Phone Mockup Floating on top -->
                        <div class="phone-mockup">
                            <div class="phone-header">
                                <div class="phone-status-bar">
                                    <span class="phone-time">09:41</span>
                                    <div class="phone-icons">📶 🔋</div>
                                </div>
                                <div class="phone-total">
                                    <span class="total-label">Total Transaksi</span>
                                    <span class="total-amount">Rp 3.450.000</span>
                                </div>
                            </div>
                            <div class="phone-body">
                                <div class="pos-item">
                                    <div class="item-icon bg-green">☕</div>
                                    <div class="item-info">
                                        <span class="item-name">Kopi Susu Aren</span>
                                        <span class="item-qty">3x · Rp 18.000</span>
                                    </div>
                                    <span class="item-price">Rp 54k</span>
                                </div>
                                <div class="pos-item">
                                    <div class="item-icon bg-gold">🍞</div>
                                    <div class="item-info">
                                        <span class="item-name">Roti Bakar Cokelat</span>
                                        <span class="item-qty">2x · Rp 22.000</span>
                                    </div>
                                    <span class="item-price">Rp 44k</span>
                                </div>
                                <div class="pos-item">
                                    <div class="item-icon bg-blue">🍵</div>
                                    <div class="item-info">
                                        <span class="item-name">Es Matcha Latte</span>
                                        <span class="item-qty">1x · Rp 25.000</span>
                                    </div>
                                    <span class="item-price">Rp 25k</span>
                                </div>
                            </div>
                            <div class="phone-footer">
                                <button class="phone-pay-btn">BAYAR SEKARANG</button>
                            </div>
                        </div>
                        
                        <!-- Floating Badges with high-end style -->
                        <div class="float-badge float-badge-1">
                            <span class="badge-dot dot-green"></span>
                            <span class="badge-text">Transaksi berhasil</span>
                        </div>
                        <div class="float-badge float-badge-2">
                            <span class="badge-icon">⭐</span>
                            <span class="badge-text">Stok diperbarui</span>
                        </div>
                        <div class="float-badge float-badge-3">
                            <span class="badge-icon">🖨️</span>
                            <span class="badge-text">Struk dicetak</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- FITUR SECTION (6 cards, 3 columns) -->
    <section id="fitur" class="features-section">
        <div class="container">
            <div class="section-header fade-up">
                <span class="tag-eyebrow">Fitur Unggulan</span>
                <h2 class="section-title">Semua yang Anda Butuhkan untuk Mengembangkan Bisnis</h2>
                <p class="section-subtitle">Kelola operasional toko Anda lebih efisien dengan fitur kasir pintar terlengkap.</p>
            </div>

            <div class="features-grid">
                <!-- Feature 1: Transaksi -->
                <div class="feature-card fade-up delay-1">
                    <div class="feature-icon-wrapper">
                        <!-- Credit Card SVG -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E31B23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2" ry="2" />
                            <line x1="2" y1="10" x2="22" y2="10" />
                            <line x1="6" y1="15" x2="10" y2="15" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Transaksi Super Cepat</h3>
                    <p class="feature-description">Proses transaksi hanya dalam hitungan detik. Mendukung scan barcode dan integrasi QRIS dinamis.</p>
                </div>

                <!-- Feature 2: Manajemen Stok -->
                <div class="feature-card fade-up delay-2">
                    <div class="feature-icon-wrapper">
                        <!-- Archive Box SVG -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E31B23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="21 8 21 21 3 21 3 8" />
                            <rect x="1" y="3" width="22" height="5" />
                            <line x1="10" y1="12" x2="14" y2="12" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Manajemen Stok Otomatis</h3>
                    <p class="feature-description">Stok barang berkurang otomatis saat terjual. Dapatkan notifikasi saat stok menipis dan kelola multi-gudang.</p>
                </div>

                <!-- Feature 3: Thermal Printer -->
                <div class="feature-card fade-up delay-3">
                    <div class="feature-icon-wrapper">
                        <!-- Printer SVG -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E31B23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <rect x="6" y="14" width="12" height="8" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Cetak Struk Thermal</h3>
                    <p class="feature-description">Cetak struk belanja fisik via Bluetooth atau WiFi printer. Kustomisasi logo toko Anda sendiri di struk.</p>
                </div>

                <!-- Feature 4: Laporan -->
                <div class="feature-card fade-up delay-1">
                    <div class="feature-icon-wrapper">
                        <!-- Bar chart lines SVG -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E31B23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10" />
                            <line x1="12" y1="20" x2="12" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="14" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Laporan Bisnis Real-Time</h3>
                    <p class="feature-description">Pantau penjualan dan profit harian kapan saja dari mana saja. Ekspor laporan keuangan dalam format Excel/PDF.</p>
                </div>

                <!-- Feature 5: Multi-kasir -->
                <div class="feature-card fade-up delay-2">
                    <div class="feature-icon-wrapper">
                        <!-- Users group SVG -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E31B23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Multi-Kasir & Multi-Toko</h3>
                    <p class="feature-description">Kelola banyak toko dengan hak akses akun kasir masing-masing yang terpantau dalam satu dashboard utama.</p>
                </div>

                <!-- Feature 6: Loyalitas -->
                <div class="feature-card fade-up delay-3">
                    <div class="feature-icon-wrapper">
                        <!-- Heart SVG -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E31B23" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Loyalitas Pelanggan</h3>
                    <p class="feature-description">Kelola database pelanggan, berikan poin belanja, dan luncurkan diskon promosi menarik untuk menjaga loyalitas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CARA KERJA SECTION (2 columns) -->
    <section id="cara-kerja" class="how-section">
        <div class="container">
            <div class="how-grid">
                <!-- How Left Column: Dashboard Graphic + Floating Scanner illustration -->
                <div class="how-visual-container fade-up">
                    <!-- Dashboard Mockup Card -->
                    <div class="dashboard-dark-card">
                        <div class="dash-header">
                            <div class="window-controls">
                                <span class="dot dot-red"></span>
                                <span class="dot dot-yellow"></span>
                                <span class="dot dot-green"></span>
                            </div>
                            <span class="dash-title">GoKasir Dashboard · Laporan Real-Time</span>
                        </div>

                        <!-- KPI dashboard data points -->
                        <div class="kpi-row">
                            <div class="kpi-card">
                                <span class="kpi-label">Omzet Bulan Ini</span>
                                <span class="kpi-value text-red">Rp 45.820k</span>
                                <span class="kpi-trend">▲ +12.4%</span>
                            </div>
                            <div class="kpi-card">
                                <span class="kpi-label">Transaksi</span>
                                <span class="kpi-value text-gold">2.418 kali</span>
                                <span class="kpi-trend">▲ +8.2%</span>
                            </div>
                            <div class="kpi-card">
                                <span class="kpi-label">Net Profit</span>
                                <span class="kpi-value text-green">Rp 18.250k</span>
                                <span class="kpi-trend">▲ +15.1%</span>
                            </div>
                        </div>

                        <!-- Dashboard Charts & Products table -->
                        <div class="dash-body-grid">
                            <div class="chart-container">
                                <span class="panel-title">Grafik Mingguan</span>
                                <div class="bar-chart">
                                    <div class="bar-col"><div class="bar" style="height: 60%"></div><span>Sen</span></div>
                                    <div class="bar-col"><div class="bar" style="height: 45%"></div><span>Sel</span></div>
                                    <div class="bar-col"><div class="bar bg-gold" style="height: 75%"></div><span>Rab</span></div>
                                    <div class="bar-col"><div class="bar" style="height: 50%"></div><span>Kam</span></div>
                                    <div class="bar-col"><div class="bar" style="height: 80%"></div><span>Jum</span></div>
                                    <div class="bar-col"><div class="bar bg-gold" style="height: 95%"></div><span>Sab</span></div>
                                    <div class="bar-col"><div class="bar" style="height: 85%"></div><span>Min</span></div>
                                </div>
                            </div>
                            <div class="table-container">
                                <span class="panel-title">Terlaris</span>
                                <table class="dash-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Kopi Aren</td>
                                            <td><span class="badge badge-green">Aman</span></td>
                                        </tr>
                                        <tr>
                                            <td>Roti Cokelat</td>
                                            <td><span class="badge badge-yellow">Menipis</span></td>
                                        </tr>
                                        <tr>
                                            <td>Matcha Latte</td>
                                            <td><span class="badge badge-green">Aman</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Mini scanner card overlapping with illustration_casher_scan_product.png -->
                    <div class="scanner-illustration-card">
                        <div class="scanner-card-header">
                            <span class="scanner-badge">REAL-TIME SCANNING</span>
                        </div>
                        <img src="{{ asset('illustration/illustration_casher_scan_product.png') }}" class="scanner-img" alt="Kasir Melakukan Scan Produk">
                    </div>
                </div>

                <!-- How Right Column: Steps -->
                <div class="how-content fade-up delay-1">
                    <span class="tag-eyebrow">Cara Kerja</span>
                    <h2 class="section-title">Langkah Mudah Memulai GoKasir</h2>
                    <p class="section-subtitle" style="margin-top: 10px; margin-bottom: 30px;">Tinggalkan pembukuan manual yang membingungkan. Mulai transformasi digital toko Anda dalam 3 langkah ringkas.</p>

                    <div class="how-steps">
                        <!-- Step 1 -->
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Daftar & Setup Toko</h4>
                                <p class="step-description">Buat akun GoKasir secara gratis. Masukkan profil usaha dan daftarkan katalog produk Anda dengan sangat cepat.</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Mulai Transaksi</h4>
                                <p class="step-description">Gunakan tablet atau smartphone untuk melayani pembeli. Terima tunai maupun pembayaran non-tunai dengan barcode QRIS.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Pantau & Kembangkan</h4>
                                <p class="step-description">Semua data transaksi otomatis masuk ke pembukuan. Analisis barang terlaris dan profit bersih langsung dari dashboard Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONI SECTION (3 cards) -->
    <section id="testimoni" class="testimonials-section">
        <div class="container">
            <div class="section-header fade-up">
                <span class="tag-eyebrow">Testimoni Pengguna</span>
                <h2 class="section-title">Kata Mereka yang Telah Sukses Bersama GoKasir</h2>
                <p class="section-subtitle">Dengarkan kisah sukses langsung dari ribuan pemilik bisnis yang mempercayakan GoKasir.</p>
            </div>

            <div class="testimonials-grid">
                <!-- Testimonial 1 -->
                <div class="testimonial-card fade-up delay-1">
                    <div>
                        <div class="stars">★★★★★</div>
                        <p class="quote">"GoKasir sangat menghemat waktu kami. Dulu stok barang sering selisih, sekarang semuanya otomatis terupdate setiap ada transaksi baru. Laporan laba ruginya langsung jadi!"</p>
                    </div>
                    <div class="author-info">
                        <div class="avatar-initial bg-avatar-1">BP</div>
                        <div class="author-details">
                            <span class="author-name">Budi Prasetyo</span>
                            <span class="author-business">Owner Kopi Kenangan Indah</span>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="testimonial-card fade-up delay-2">
                    <div>
                        <div class="stars">★★★★★</div>
                        <p class="quote">"Bisa menerima pembayaran QRIS tanpa ribet adalah penyelamat bisnis fashion saya. Pelanggan senang karena bayarnya cepat sekali, uang pun langsung aman masuk ke rekening toko."</p>
                    </div>
                    <div class="author-info">
                        <div class="avatar-initial bg-avatar-2">SR</div>
                        <div class="author-details">
                            <span class="author-name">Siti Rahma</span>
                            <span class="author-business">Pemilik Butik Rahma Style</span>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="testimonial-card fade-up delay-3">
                    <div>
                        <div class="stars">★★★★★</div>
                        <p class="quote">"Fitur multi-kasir sangat berguna untuk memantau 3 cabang toko saya dari rumah. Saya bisa membatasi hak akses admin/kasir demi keamanan transaksi bisnis cabang."</p>
                    </div>
                    <div class="author-info">
                        <div class="avatar-initial bg-avatar-3">HW</div>
                        <div class="author-details">
                            <span class="author-name">Hendra Wijaya</span>
                            <span class="author-business">Franchise Martabak Bangka 88</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING SECTION (3 tiers) -->
    <!-- PRICING SECTION (Single Tier) -->
    <section id="harga" class="pricing-section">
        <div class="container">
            <div class="section-header fade-up">
                <span class="tag-eyebrow">Paket Harga</span>
                <h2 class="section-title">Sangat Ringan, Tanpa Beban Bulanan</h2>
                <p class="section-subtitle">Pelanggan baru langsung mendapatkan 500 token transaksi gratis untuk mulai memakai GoKasir.Net.</p>
            </div>

            <div style="max-width: 600px; margin: 0 auto;">
                <div class="pricing-card popular fade-up delay-1">
                    <div class="popular-badge">Paling Ringan untuk UMKM</div>
                    <div class="pricing-header">
                        <span class="pricing-tier">Gratis 500 Token Awal</span>
                        <div class="pricing-price">Gratis</div>
                    </div>
                    <ul class="pricing-features">
                        <li class="pricing-feature-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F7C548" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                            Gratis 500 token transaksi untuk pelanggan baru
                        </li>
                        <li class="pricing-feature-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F7C548" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                            1 transaksi = 1 struk = 1 token
                        </li>
                        <li class="pricing-feature-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F7C548" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                            Setelah 500 token, hanya Rp 100 / transaksi
                        </li>
                        <li class="pricing-feature-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F7C548" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                            Topup token minimal Rp 10.000
                        </li>
                        <li class="pricing-feature-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F7C548" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                            Sangat ringan dan tidak membebani UMKM
                        </li>
                        <li class="pricing-feature-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F7C548" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                            Laporan penjualan otomatis & dipantau online
                        </li>
                    </ul>

                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 14px; margin-bottom: 28px; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="font-size: 0.9rem; font-weight: 700; color: #F7C548; margin-bottom: 10px;">Contoh Perhitungan Ringan:</div>
                        <p style="font-size: 0.9rem; color: #d1d8e0; line-height: 1.5; margin-bottom: 8px;">Jika usaha memiliki 20 transaksi per hari:</p>
                        <p style="font-size: 0.95rem; color: #fff; font-weight: 700; margin-bottom: 8px;">20 transaksi × Rp 100 = Rp 2.000 / hari</p>
                        <p style="font-size: 0.9rem; color: #d1d8e0; line-height: 1.6;">Dalam 30 hari sekitar <strong style="color: #fff;">Rp 60.000</strong>. Biaya mengikuti jumlah transaksi usaha Anda.</p>
                    </div>

                    <div class="pricing-action">
                        <a href="https://play.google.com/store/apps/details?id=com.gokasir.net" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="width: 100%; box-shadow: 0 8px 25px rgba(227,27,35,0.4); padding: 18px; font-size: 1.05rem;">Mulai Sekarang - Gratis 500 Token</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION (dark) -->
    <section class="cta-section">
        <!-- Decoration radial gradient blobs -->
        <div class="blob blob-cta-1"></div>
        <div class="blob blob-cta-2"></div>

        <div class="container">
            <div class="cta-grid">
                <!-- CTA Left Content -->
                <div class="cta-content fade-up">
                    <h2 class="cta-title">Siap Meningkatkan Omzet Bisnis Anda?</h2>
                    <p class="cta-subtitle">Bergabunglah dengan puluhan ribu wirausaha cerdas lainnya sekarang juga. Kelola toko Anda lebih tenang, aman, dan menguntungkan.</p>
                    <div class="cta-buttons">
                        <a href="https://play.google.com/store/apps/details?id=com.gokasir.net" target="_blank" rel="noopener noreferrer" class="btn btn-white">Download Aplikasi</a>
                        <a href="#harga" class="btn btn-ghost" style="color: var(--white); border-color: rgba(255,255,255,0.2);">Pelajari Selengkapnya</a>
                    </div>
                </div>

                <!-- CTA Right Visual Composition utilizing cashier welcome image in a distinct angle -->
                <div class="cta-visual fade-up delay-1">
                    <div class="cta-composition">
                        <img src="{{ asset('illustration/illustration_cashier_welcome.png') }}" class="cta-main-img" alt="Bergabung dengan GoKasir">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Footer Column 1: Brand Info -->
                <div class="footer-brand">
                    <div class="logo-wrapper">
                        <svg class="logo-icon" width="34" height="34" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 6H9L12.5 23H31L35 11H13" stroke="#E31B23" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="15" cy="30" r="3" fill="#E31B23" />
                            <circle cx="28" cy="30" r="3" fill="#E31B23" />
                            <circle cx="22" cy="16" r="6" fill="#E31B23" />
                            <path d="M19.5 16L21 17.5L24.5 14" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <rect x="30" y="3" width="4" height="4" fill="#F5A623" />
                            <rect x="34" y="7" width="4" height="4" fill="#F5A623" />
                            <rect x="30" y="7" width="4" height="4" fill="#F5A623" opacity="0.5" />
                        </svg>
                        <div class="logo-text">
                            <span class="wordmark"><span class="go">Go</span><span class="kasir">Kasir</span></span>
                            <span class="tagline">KASIR CERDAS · BISNIS SUKSES</span>
                        </div>
                    </div>
                    <p class="footer-desc">GoKasir adalah platform POS (Point of Sale) digital yang dirancang untuk mempercepat pertumbuhan UMKM di Indonesia.</p>
                </div>

                <!-- Footer Column 2: Fitur links -->
                <div class="footer-col">
                    <span class="footer-col-title">Fitur</span>
                    <ul class="footer-links">
                        <li><a href="#fitur" class="footer-link">Kasir POS</a></li>
                        <li><a href="#fitur" class="footer-link">Kelola Stok</a></li>
                        <li><a href="#fitur" class="footer-link">Laporan Bisnis</a></li>
                        <li><a href="#fitur" class="footer-link">Metode QRIS</a></li>
                    </ul>
                </div>

                <!-- Footer Column 3: Perusahaan links -->
                <div class="footer-col">
                    <span class="footer-col-title">Perusahaan</span>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">Tentang Kami</a></li>
                        <li><a href="#" class="footer-link">Kisah Sukses</a></li>
                        <li><a href="#" class="footer-link">Karir</a></li>
                        <li><a href="#" class="footer-link">Hubungi Kami</a></li>
                    </ul>
                </div>

                <!-- Footer Column 4: Bantuan links -->
                <div class="footer-col">
                    <span class="footer-col-title">Bantuan</span>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
                        <li><a href="#" class="footer-link">Panduan Pengguna</a></li>
                        <li><a href="#" class="footer-link">Kebijakan Privasi</a></li>
                        <li><a href="#" class="footer-link">Syarat & Ketentuan</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom Area -->
            <div class="footer-bottom">
                <span class="copyright">© 2026 GoKasir. Seluruh Hak Cipta Dilindungi.</span>
                
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c4.56-.93 8-4.96 8-9.75z"/></svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // 1. Navbar Scroll Shadow Effect
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', function () {
                if (window.scrollY > 10) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // 2. Scroll Reveal Animation using Native IntersectionObserver
            const fadeUpElements = document.querySelectorAll('.fade-up');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target); // Trigger only once
                    }
                });
            }, {
                threshold: 0.12 // Trigger when 12% of the element is visible
            });

            fadeUpElements.forEach(element => {
                observer.observe(element);
            });

            // 3. Mobile Hamburger Menu Toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('.nav-links');
            const navLinksItems = document.querySelectorAll('.nav-link');

            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', function () {
                    navLinks.classList.toggle('mobile-active');
                    menuToggle.classList.toggle('open');
                });

                // Close menu drawer when clicking a link
                navLinksItems.forEach(item => {
                    item.addEventListener('click', function () {
                        navLinks.classList.remove('mobile-active');
                        menuToggle.classList.remove('open');
                    });
                });
            }
        });
    </script>
</body>

</html>
