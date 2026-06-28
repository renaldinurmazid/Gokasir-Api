import React, { useEffect } from "react";
import { Head, Link } from "@inertiajs/react";

export default function Welcome() {
    useEffect(() => {
        // 1. Navbar Scroll Shadow Effect
        const navbar = document.querySelector(".navbar");
        window.addEventListener("scroll", function () {
            if (window.scrollY > 10) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        });

        // 2. Scroll Reveal Animation using Native IntersectionObserver
        const fadeUpElements = document.querySelectorAll(".fade-up");
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("visible");
                        observer.unobserve(entry.target); // Trigger only once
                    }
                });
            },
            {
                threshold: 0.12, // Trigger when 12% of the element is visible
            },
        );

        fadeUpElements.forEach((element) => {
            observer.observe(element);
        });

        // 3. Mobile Hamburger Menu Toggle
        const menuToggle = document.querySelector(".menu-toggle");
        const navLinks = document.querySelector(".nav-links");
        const navLinksItems = document.querySelectorAll(".nav-link");

        if (menuToggle && navLinks) {
            menuToggle.addEventListener("click", function () {
                navLinks.classList.toggle("mobile-active");
                menuToggle.classList.toggle("open");
            });

            // Close menu drawer when clicking a link
            navLinksItems.forEach((item) => {
                item.addEventListener("click", function () {
                    navLinks.classList.remove("mobile-active");
                    menuToggle.classList.remove("open");
                });
            });
        }
    }, []);

    return (
        <>
            <Head>
                <title>GoKasir — Kasir Cerdas · Bisnis Sukses</title>

                <meta
                    name="description"
                    content="GoKasir adalah aplikasi kasir pintar digital terbaik untuk UMKM Indonesia. Kelola stok otomatis, terima pembayaran QRIS, cetak struk thermal, dan pantau laporan keuangan real-time."
                />
                <meta
                    name="keywords"
                    content="aplikasi kasir, pos system, kasir digital, kasir pintar, qris umkm, pembukuan digital, gokasir"
                />
                <meta name="author" content="GoKasir" />

                <link
                    rel="shortcut icon"
                    href="/favicon.png"
                    type="image/x-icon"
                />

                {/*  Fonts  */}
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link
                    rel="preconnect"
                    href="https://fonts.gstatic.com"
                    crossorigin
                />
                <link
                    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
                    rel="stylesheet"
                />

                {/*  Styles  */}
                <style
                    dangerouslySetInnerHTML={{
                        __html: "\n        :root {\n            --red:        #E31B23;   /* Primary — aksi, highlight, brand */\n            --red-dark:   #B91219;   /* Hover state primary */\n            --red-light:  #FF4D55;   /* Tint, latar ilustrasi */\n            --navy:       #1A2233;   /* Teks utama, background dark */\n            --navy-mid:   #2B3A55;   /* Teks sekunder, elemen UI */\n            --gold:       #F5A623;   /* Aksen, badge, pixel squares logo */\n            --gold2:      #F7C548;   /* Gold terang, dark background */\n            --white:      #FFFFFF;\n            --off:        #F7F8FC;   /* Background section terang */\n            --gray:       #8894AA;   /* Body text, label */\n            --border:     #E8ECF4;   /* Garis pemisah, border card */\n            --r:          16px;      /* Card radius umum */\n            \n            --font-main: 'Plus Jakarta Sans', sans-serif;\n        }\n\n        /* Global Reset */\n        * {\n            box-sizing: border-box;\n            margin: 0;\n            padding: 0;\n        }\n\n        html {\n            scroll-behavior: smooth;\n        }\n\n        body {\n            font-family: var(--font-main);\n            background-color: var(--white);\n            color: var(--navy);\n            line-height: 1.7;\n            overflow-x: hidden;\n            -webkit-font-smoothing: antialiased;\n            -moz-osx-font-smoothing: grayscale;\n        }\n\n        h1, h2, h3, h4 {\n            line-height: 1.2;\n            font-weight: 800;\n        }\n\n        a {\n            text-decoration: none;\n            color: inherit;\n            transition: all 0.3s ease;\n        }\n\n        /* Ambient Decoration Blobs */\n        .blob {\n            position: absolute;\n            border-radius: 50%;\n            pointer-events: none;\n            z-index: 1;\n        }\n\n        .blob-hero-1 {\n            background: radial-gradient(circle, rgba(227, 27, 35, 0.08) 0%, transparent 70%);\n            width: 600px;\n            height: 600px;\n            top: -180px;\n            right: -180px;\n        }\n\n        .blob-hero-2 {\n            background: radial-gradient(circle, rgba(245, 166, 35, 0.06) 0%, transparent 70%);\n            width: 400px;\n            height: 400px;\n            bottom: -100px;\n            left: -100px;\n        }\n\n        .blob-cta-1 {\n            background: radial-gradient(circle, rgba(255, 77, 85, 0.12) 0%, transparent 70%);\n            width: 500px;\n            height: 500px;\n            top: -200px;\n            left: -200px;\n        }\n\n        .blob-cta-2 {\n            background: radial-gradient(circle, rgba(245, 166, 35, 0.12) 0%, transparent 70%);\n            width: 400px;\n            height: 400px;\n            bottom: -200px;\n            right: -200px;\n        }\n\n        /* Container & Layout */\n        .container {\n            max-width: 1160px;\n            margin: 0 auto;\n            padding: 0 28px;\n            position: relative;\n            z-index: 2;\n        }\n\n        section {\n            padding: 100px 0;\n            position: relative;\n        }\n\n        /* Navbar */\n        .navbar {\n            position: fixed;\n            top: 0;\n            left: 0;\n            right: 0;\n            height: 68px;\n            background: rgba(255, 255, 255, 0.92);\n            backdrop-filter: blur(16px);\n            -webkit-backdrop-filter: blur(16px);\n            border-bottom: 1px solid var(--border);\n            z-index: 1000;\n            transition: all 0.3s ease;\n        }\n\n        .navbar.scrolled {\n            box-shadow: 0 8px 30px rgba(26, 34, 51, 0.06);\n            background: rgba(255, 255, 255, 0.96);\n        }\n\n        .navbar .container {\n            display: flex;\n            align-items: center;\n            justify-content: space-between;\n            height: 100%;\n        }\n\n        /* Logo Identity */\n        .logo-wrapper {\n            display: flex;\n            align-items: center;\n            gap: 12px;\n        }\n\n        .logo-text {\n            display: flex;\n            flex-direction: column;\n            line-height: 1;\n        }\n\n        .logo-text .wordmark {\n            font-size: 1.4rem;\n            font-weight: 900;\n            letter-spacing: -0.02em;\n        }\n\n        .logo-text .wordmark .go {\n            color: var(--red);\n        }\n\n        .logo-text .wordmark .kasir {\n            color: var(--navy);\n        }\n\n        .logo-text .tagline {\n            font-size: 0.52rem;\n            font-weight: 700;\n            color: var(--navy-mid);\n            letter-spacing: 0.14em;\n            margin-top: 3px;\n        }\n\n        /* Menu Links */\n        .nav-links {\n            display: flex;\n            align-items: center;\n            gap: 32px;\n        }\n\n        .nav-link {\n            font-size: 0.88rem;\n            font-weight: 500;\n            color: var(--navy-mid);\n            position: relative;\n        }\n\n        .nav-link::after {\n            content: '';\n            position: absolute;\n            bottom: -6px;\n            left: 0;\n            width: 0;\n            height: 2px;\n            background-color: var(--red);\n            transition: width 0.3s ease;\n        }\n\n        .nav-link:hover {\n            color: var(--red);\n        }\n\n        .nav-link:hover::after {\n            width: 100%;\n        }\n\n        /* Interactive Hamburger Button */\n        .menu-toggle {\n            display: none;\n            background: none;\n            border: none;\n            cursor: pointer;\n            flex-direction: column;\n            gap: 6px;\n        }\n\n        .menu-toggle span {\n            display: block;\n            width: 24px;\n            height: 2px;\n            background-color: var(--navy);\n            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);\n        }\n\n        /* Buttons Styling */\n        .btn {\n            display: inline-flex;\n            align-items: center;\n            justify-content: center;\n            font-family: var(--font-main);\n            border: none;\n            cursor: pointer;\n            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);\n            text-align: center;\n            white-space: nowrap;\n        }\n\n        .btn-primary {\n            background-color: var(--red);\n            color: var(--white);\n            border-radius: 12px;\n            padding: 15px 30px;\n            font-weight: 700;\n            font-size: 0.95rem;\n            box-shadow: 0 8px 30px rgba(227, 27, 35, 0.35);\n        }\n\n        .btn-primary:hover {\n            transform: translateY(-2px);\n            box-shadow: 0 12px 35px rgba(227, 27, 35, 0.45);\n            background-color: var(--red-dark);\n        }\n\n        .btn-ghost {\n            background-color: transparent;\n            border: 2px solid var(--border);\n            color: var(--navy-mid);\n            border-radius: 12px;\n            padding: 13px 28px;\n            font-weight: 700;\n            font-size: 0.95rem;\n        }\n\n        .btn-ghost:hover {\n            border-color: var(--red);\n            color: var(--red);\n        }\n\n        .btn-nav {\n            background-color: var(--red);\n            color: var(--white);\n            border-radius: 10px;\n            padding: 10px 24px;\n            font-size: 0.85rem;\n            font-weight: 700;\n        }\n\n        .btn-nav:hover {\n            background-color: var(--red-dark);\n            transform: translateY(-1px);\n            box-shadow: 0 6px 20px rgba(227, 27, 35, 0.25);\n        }\n\n        .btn-white {\n            background-color: var(--white);\n            color: var(--navy);\n            border-radius: 12px;\n            padding: 15px 30px;\n            font-weight: 700;\n            font-size: 0.95rem;\n            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);\n        }\n\n        .btn-white:hover {\n            transform: translateY(-2px);\n            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);\n        }\n\n        /* 1. Hero Section */\n        .hero-section {\n            background: linear-gradient(135deg, #ffffff 55%, #fff5f5 100%);\n            padding-top: 150px;\n            padding-bottom: 100px;\n            overflow: hidden;\n        }\n\n        .hero-grid {\n            display: grid;\n            grid-template-columns: 1.15fr 0.85fr;\n            gap: 60px;\n            align-items: center;\n        }\n\n        .hero-content {\n            display: flex;\n            flex-direction: column;\n            align-items: flex-start;\n        }\n\n        .tag-eyebrow {\n            background: rgba(227, 27, 35, 0.08);\n            color: var(--red);\n            border-radius: 50px;\n            padding: 6px 14px;\n            font-size: 0.72rem;\n            font-weight: 700;\n            letter-spacing: 0.12em;\n            text-transform: uppercase;\n            margin-bottom: 20px;\n            display: inline-block;\n        }\n\n        .hero-title {\n            font-size: clamp(2.2rem, 5vw, 3.4rem);\n            font-weight: 900;\n            color: var(--navy);\n            line-height: 1.15;\n            margin-bottom: 24px;\n            letter-spacing: -0.01em;\n        }\n\n        .hero-title em {\n            font-style: normal;\n            color: var(--red);\n        }\n\n        .hero-body {\n            font-size: 1.05rem;\n            color: var(--gray);\n            line-height: 1.7;\n            max-width: 580px;\n            margin-bottom: 36px;\n        }\n\n        .hero-actions {\n            display: flex;\n            gap: 16px;\n            margin-bottom: 48px;\n        }\n\n        .hero-stats {\n            display: flex;\n            gap: 40px;\n            border-top: 1px solid var(--border);\n            padding-top: 28px;\n            width: 100%;\n        }\n\n        .stat-item {\n            display: flex;\n            flex-direction: column;\n        }\n\n        .stat-number {\n            font-size: 1.6rem;\n            font-weight: 900;\n            color: var(--navy);\n            line-height: 1.2;\n        }\n\n        .stat-label {\n            font-size: 0.8rem;\n            color: var(--gray);\n            font-weight: 500;\n            margin-top: 4px;\n        }\n\n        /* Hero Visual & Mockup composition */\n        .hero-visual {\n            position: relative;\n            width: 100%;\n            display: flex;\n            justify-content: center;\n            align-items: center;\n        }\n\n        .hero-composition {\n            position: relative;\n            width: 100%;\n            max-width: 440px;\n            height: 440px;\n            display: flex;\n            justify-content: center;\n            align-items: center;\n        }\n\n        /* Layered cashier welcome illustration in the background */\n        .hero-main-img {\n            width: 90%;\n            height: auto;\n            max-height: 380px;\n            object-fit: contain;\n            opacity: 0.95;\n            filter: drop-shadow(0 20px 40px rgba(26, 34, 51, 0.08));\n            border-radius: 24px;\n            margin-right: 50px;\n        }\n\n        /* Floating Phone Mockup */\n        .phone-mockup {\n            position: absolute;\n            top: 2%;\n            right: -2%;\n            width: 250px;\n            height: 430px;\n            background-color: var(--navy);\n            border: 8px solid #2B3A55;\n            border-radius: 36px;\n            box-shadow: 0 30px 60px rgba(26, 34, 51, 0.22);\n            overflow: hidden;\n            display: flex;\n            flex-direction: column;\n            z-index: 10;\n            animation: float 4s ease-in-out infinite;\n        }\n\n        @keyframes float {\n            0%, 100% { transform: translateY(0); }\n            50%       { transform: translateY(-18px); }\n        }\n\n        .phone-header {\n            background-color: var(--red);\n            color: var(--white);\n            padding: 14px 14px 10px;\n            display: flex;\n            flex-direction: column;\n            gap: 6px;\n        }\n\n        .phone-status-bar {\n            display: flex;\n            justify-content: space-between;\n            font-size: 0.6rem;\n            opacity: 0.8;\n            font-weight: 600;\n        }\n\n        .phone-total {\n            display: flex;\n            flex-direction: column;\n            gap: 2px;\n        }\n\n        .total-label {\n            font-size: 0.55rem;\n            text-transform: uppercase;\n            letter-spacing: 0.05em;\n            opacity: 0.85;\n        }\n\n        .total-amount {\n            font-size: 1.1rem;\n            font-weight: 800;\n        }\n\n        .phone-body {\n            padding: 12px;\n            display: flex;\n            flex-direction: column;\n            gap: 10px;\n            flex-grow: 1;\n            background-color: #1A2233;\n        }\n\n        .pos-item {\n            display: flex;\n            align-items: center;\n            gap: 10px;\n            background: rgba(255, 255, 255, 0.04);\n            padding: 8px;\n            border-radius: 10px;\n            border: 1px solid rgba(255, 255, 255, 0.05);\n        }\n\n        .item-icon {\n            width: 32px;\n            height: 32px;\n            border-radius: 8px;\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            font-size: 0.9rem;\n        }\n\n        .bg-green { background-color: rgba(46, 204, 113, 0.15); color: #2ecc71; }\n        .bg-gold { background-color: rgba(245, 166, 35, 0.15); color: #F5A623; }\n        .bg-blue { background-color: rgba(52, 152, 219, 0.15); color: #3498db; }\n\n        .item-info {\n            display: flex;\n            flex-direction: column;\n            flex-grow: 1;\n        }\n\n        .item-name {\n            font-size: 0.7rem;\n            font-weight: 600;\n            color: var(--white);\n        }\n\n        .item-qty {\n            font-size: 0.58rem;\n            color: var(--gray);\n        }\n\n        .item-price {\n            font-size: 0.7rem;\n            font-weight: 700;\n            color: var(--white);\n        }\n\n        .phone-footer {\n            padding: 10px 12px;\n            background-color: #151B29;\n        }\n\n        .phone-pay-btn {\n            width: 100%;\n            background-color: var(--red);\n            color: var(--white);\n            border: none;\n            padding: 9px;\n            border-radius: 10px;\n            font-size: 0.7rem;\n            font-weight: 700;\n            cursor: pointer;\n            letter-spacing: 0.05em;\n        }\n\n        /* Floating badges over composition */\n        .float-badge {\n            position: absolute;\n            background-color: var(--white);\n            padding: 8px 14px;\n            border-radius: 14px;\n            box-shadow: 0 10px 25px rgba(26, 34, 51, 0.08);\n            display: flex;\n            align-items: center;\n            gap: 8px;\n            z-index: 20;\n            border: 1px solid var(--border);\n            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);\n        }\n\n        .float-badge:hover {\n            transform: translateY(-3px) scale(1.05);\n            box-shadow: 0 15px 30px rgba(26, 34, 51, 0.12);\n        }\n\n        .badge-dot {\n            width: 8px;\n            height: 8px;\n            border-radius: 50%;\n        }\n\n        .dot-green { background-color: #2ecc71; box-shadow: 0 0 8px #2ecc71; }\n        .badge-icon { font-size: 0.85rem; }\n        .badge-text {\n            font-size: 0.68rem;\n            font-weight: 700;\n            color: var(--navy);\n            white-space: nowrap;\n        }\n\n        .float-badge-1 { top: 12%; left: -8%; }\n        .float-badge-2 { bottom: 12%; left: -6%; }\n        .float-badge-3 { bottom: 6%; right: 18%; }\n\n        /* 2. Fitur Section */\n        .features-section {\n            background-color: var(--off);\n        }\n\n        .section-header {\n            text-align: center;\n            max-width: 680px;\n            margin: 0 auto 60px;\n        }\n\n        .section-title {\n            font-size: clamp(1.8rem, 4vw, 2.6rem);\n            font-weight: 800;\n            color: var(--navy);\n            line-height: 1.2;\n            margin-top: 12px;\n            letter-spacing: -0.01em;\n        }\n\n        .section-subtitle {\n            font-size: 1.05rem;\n            color: var(--gray);\n            margin-top: 16px;\n        }\n\n        .features-grid {\n            display: grid;\n            grid-template-columns: repeat(3, 1fr);\n            gap: 24px;\n        }\n\n        .feature-card {\n            background-color: var(--white);\n            border: 1px solid var(--border);\n            border-radius: 16px;\n            padding: 32px 28px;\n            position: relative;\n            overflow: hidden;\n            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);\n            display: flex;\n            flex-direction: column;\n            align-items: flex-start;\n        }\n\n        .feature-card::before {\n            content: '';\n            position: absolute;\n            top: 0;\n            left: 0;\n            width: 100%;\n            height: 4px;\n            background-color: var(--red);\n            transform: scaleX(0);\n            transform-origin: left;\n            transition: transform 0.3s ease;\n        }\n\n        .feature-card:hover {\n            transform: translateY(-6px);\n            box-shadow: 0 15px 35px rgba(26, 34, 51, 0.08);\n            border-color: transparent;\n        }\n\n        .feature-card:hover::before {\n            transform: scaleX(1);\n        }\n\n        .feature-icon-wrapper {\n            width: 56px;\n            height: 56px;\n            background-color: rgba(227, 27, 35, 0.08);\n            border-radius: 14px;\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            margin-bottom: 24px;\n            transition: all 0.3s ease;\n        }\n\n        .feature-icon-wrapper svg {\n            transition: stroke 0.3s ease;\n        }\n\n        .feature-card:hover .feature-icon-wrapper {\n            background-color: var(--red);\n        }\n\n        .feature-card:hover .feature-icon-wrapper svg {\n            stroke: var(--white);\n        }\n\n        .feature-title {\n            font-size: 1.05rem;\n            font-weight: 700;\n            color: var(--navy);\n            margin-bottom: 12px;\n        }\n\n        .feature-description {\n            font-size: 0.95rem;\n            color: var(--gray);\n            line-height: 1.6;\n        }\n\n        /* 3. Cara Kerja Section */\n        .how-section {\n            background-color: var(--white);\n        }\n\n        .how-grid {\n            display: grid;\n            grid-template-columns: 1.05fr 0.95fr;\n            gap: 80px;\n            align-items: center;\n        }\n\n        .how-visual-container {\n            position: relative;\n            display: flex;\n            flex-direction: column;\n            gap: 30px;\n            width: 100%;\n        }\n\n        /* Dashboard Dark Card */\n        .dashboard-dark-card {\n            background-color: var(--navy);\n            border-radius: 20px;\n            padding: 24px;\n            box-shadow: 0 20px 45px rgba(26, 34, 51, 0.15);\n            border: 1px solid rgba(255, 255, 255, 0.05);\n            overflow: hidden;\n            width: 100%;\n            max-width: 520px;\n        }\n\n        .dash-header {\n            display: flex;\n            align-items: center;\n            gap: 16px;\n            border-bottom: 1px solid rgba(255, 255, 255, 0.06);\n            padding-bottom: 16px;\n            margin-bottom: 20px;\n        }\n\n        .window-controls {\n            display: flex;\n            gap: 6px;\n        }\n\n        .window-controls .dot {\n            width: 10px;\n            height: 10px;\n            border-radius: 50%;\n        }\n\n        .dot-red { background-color: #ff5f56; }\n        .dot-yellow { background-color: #ffbd2e; }\n        .dot-green { background-color: #27c93f; }\n\n        .dash-title {\n            font-size: 0.72rem;\n            font-weight: 600;\n            color: var(--gray);\n            letter-spacing: 0.05em;\n            text-transform: uppercase;\n        }\n\n        .kpi-row {\n            display: grid;\n            grid-template-columns: repeat(3, 1fr);\n            gap: 12px;\n            margin-bottom: 24px;\n        }\n\n        .kpi-card {\n            background: rgba(255, 255, 255, 0.03);\n            border: 1px solid rgba(255, 255, 255, 0.04);\n            padding: 12px;\n            border-radius: 10px;\n            display: flex;\n            flex-direction: column;\n        }\n\n        .kpi-label {\n            font-size: 0.58rem;\n            color: var(--gray);\n            font-weight: 600;\n            text-transform: uppercase;\n            margin-bottom: 4px;\n            letter-spacing: 0.03em;\n        }\n\n        .kpi-value {\n            font-size: 0.85rem;\n            font-weight: 800;\n        }\n\n        .kpi-value.text-red { color: var(--red-light); }\n        .kpi-value.text-gold { color: var(--gold2); }\n        .kpi-value.text-green { color: #2ecc71; }\n\n        .kpi-trend {\n            font-size: 0.55rem;\n            font-weight: 700;\n            margin-top: 4px;\n            color: #2ecc71;\n            display: flex;\n            align-items: center;\n            gap: 2px;\n        }\n\n        .dash-body-grid {\n            display: grid;\n            grid-template-columns: 1.15fr 0.85fr;\n            gap: 16px;\n        }\n\n        .chart-container, .table-container {\n            background: rgba(255, 255, 255, 0.02);\n            border: 1px solid rgba(255, 255, 255, 0.03);\n            border-radius: 12px;\n            padding: 14px;\n        }\n\n        .panel-title {\n            font-size: 0.65rem;\n            font-weight: 700;\n            color: var(--white);\n            display: block;\n            margin-bottom: 12px;\n            text-transform: uppercase;\n            letter-spacing: 0.05em;\n            opacity: 0.9;\n        }\n\n        .bar-chart {\n            display: flex;\n            align-items: flex-end;\n            justify-content: space-between;\n            height: 90px;\n            padding-top: 10px;\n        }\n\n        .bar-col {\n            display: flex;\n            flex-direction: column;\n            align-items: center;\n            gap: 6px;\n            flex-grow: 1;\n        }\n\n        .bar {\n            width: 12px;\n            border-radius: 4px 4px 0 0;\n            background-color: var(--red);\n        }\n\n        .bar.bg-gold { background-color: var(--gold); }\n\n        .bar-col span {\n            font-size: 0.52rem;\n            color: var(--gray);\n        }\n\n        .dash-table {\n            width: 100%;\n            border-collapse: collapse;\n        }\n\n        .dash-table th, .dash-table td {\n            padding: 6px;\n            text-align: left;\n            font-size: 0.62rem;\n        }\n\n        .dash-table th {\n            color: var(--gray);\n            border-bottom: 1px solid rgba(255, 255, 255, 0.06);\n            font-weight: 600;\n        }\n\n        .dash-table td {\n            color: var(--white);\n        }\n\n        .badge {\n            font-size: 0.55rem;\n            padding: 2px 6px;\n            border-radius: 4px;\n            font-weight: 700;\n        }\n\n        .badge-green { background-color: rgba(46, 204, 113, 0.15); color: #2ecc71; }\n        .badge-yellow { background-color: rgba(241, 196, 15, 0.15); color: #f1c40f; }\n\n        /* Secondary scanning illustration floating card */\n        .scanner-illustration-card {\n            position: absolute;\n            bottom: -30px;\n            right: -30px;\n            background-color: var(--white);\n            border-radius: 16px;\n            padding: 16px;\n            box-shadow: 0 15px 35px rgba(26, 34, 51, 0.12);\n            border: 1px solid var(--border);\n            width: 190px;\n            display: flex;\n            flex-direction: column;\n            gap: 10px;\n            z-index: 10;\n            animation: float 4s ease-in-out infinite;\n            animation-delay: 1.5s;\n        }\n\n        .scanner-badge {\n            font-size: 0.55rem;\n            background-color: rgba(227, 27, 35, 0.08);\n            color: var(--red);\n            padding: 4px 8px;\n            border-radius: 40px;\n            font-weight: 700;\n            display: inline-block;\n            letter-spacing: 0.05em;\n        }\n\n        .scanner-img {\n            width: 100%;\n            height: auto;\n            border-radius: 8px;\n            object-fit: cover;\n        }\n\n        /* Step Items column */\n        .how-content {\n            display: flex;\n            flex-direction: column;\n            align-items: flex-start;\n        }\n\n        .how-steps {\n            display: flex;\n            flex-direction: column;\n            gap: 28px;\n            margin-top: 10px;\n        }\n\n        .step-item {\n            display: flex;\n            gap: 20px;\n            align-items: flex-start;\n        }\n\n        .step-number {\n            width: 42px;\n            height: 42px;\n            background-color: rgba(227, 27, 35, 0.08);\n            color: var(--red);\n            font-weight: 700;\n            font-size: 1.05rem;\n            border-radius: 50%;\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            flex-shrink: 0;\n            transition: all 0.3s ease;\n        }\n\n        .step-item:hover .step-number {\n            background-color: var(--red);\n            color: var(--white);\n            transform: scale(1.08);\n        }\n\n        .step-content {\n            display: flex;\n            flex-direction: column;\n        }\n\n        .step-title {\n            font-size: 0.95rem;\n            font-weight: 700;\n            color: var(--navy);\n            margin-bottom: 6px;\n        }\n\n        .step-description {\n            font-size: 0.95rem;\n            color: var(--gray);\n            line-height: 1.6;\n        }\n\n        /* 4. Testimoni Section */\n        .testimonials-section {\n            background-color: var(--off);\n        }\n\n        .testimonials-grid {\n            display: grid;\n            grid-template-columns: repeat(3, 1fr);\n            gap: 24px;\n        }\n\n        .testimonial-card {\n            background-color: var(--white);\n            border: 1px solid var(--border);\n            border-radius: 16px;\n            padding: 36px 28px;\n            display: flex;\n            flex-direction: column;\n            justify-content: space-between;\n            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);\n        }\n\n        .testimonial-card:hover {\n            transform: translateY(-4px);\n            box-shadow: 0 10px 30px rgba(26, 34, 51, 0.05);\n        }\n\n        .stars {\n            display: flex;\n            gap: 4px;\n            margin-bottom: 20px;\n            color: var(--gold);\n        }\n\n        .quote {\n            font-size: 0.95rem;\n            color: var(--navy-mid);\n            line-height: 1.7;\n            margin-bottom: 24px;\n            font-weight: 400;\n            font-style: italic;\n        }\n\n        .author-info {\n            display: flex;\n            align-items: center;\n            gap: 12px;\n        }\n\n        .avatar-initial {\n            width: 44px;\n            height: 44px;\n            border-radius: 50%;\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            color: var(--white);\n            font-weight: 700;\n            font-size: 1rem;\n        }\n\n        .bg-avatar-1 { background-color: #3498db; }\n        .bg-avatar-2 { background-color: #2ecc71; }\n        .bg-avatar-3 { background-color: #9b59b6; }\n\n        .author-details {\n            display: flex;\n            flex-direction: column;\n        }\n\n        .author-name {\n            font-size: 0.9rem;\n            font-weight: 700;\n            color: var(--navy);\n        }\n\n        .author-business {\n            font-size: 0.78rem;\n            color: var(--gray);\n            font-weight: 500;\n        }\n\n        /* 5. Harga Section */\n        .pricing-section {\n            background-color: var(--white);\n        }\n\n        .pricing-grid {\n            display: grid;\n            grid-template-columns: repeat(3, 1fr);\n            gap: 28px;\n            align-items: stretch;\n            margin-top: 20px;\n        }\n\n        .pricing-card {\n            background-color: var(--white);\n            border: 1px solid var(--border);\n            border-radius: 20px;\n            padding: 40px 32px;\n            display: flex;\n            flex-direction: column;\n            justify-content: space-between;\n            position: relative;\n            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);\n        }\n\n        .pricing-card:hover {\n            transform: translateY(-6px);\n            box-shadow: 0 15px 35px rgba(26, 34, 51, 0.08);\n        }\n\n        /* Highlighted Pro Plan styling */\n        .pricing-card.popular {\n            background-color: var(--navy);\n            border: 2px solid var(--red);\n            color: var(--white);\n            box-shadow: 0 20px 40px rgba(227, 27, 35, 0.15);\n        }\n\n        .pricing-card.popular:hover {\n            box-shadow: 0 25px 45px rgba(227, 27, 35, 0.25);\n        }\n\n        .popular-badge {\n            position: absolute;\n            top: -15px;\n            left: 50%;\n            transform: translateX(-50%);\n            background-color: var(--red);\n            color: var(--white);\n            font-size: 0.7rem;\n            font-weight: 700;\n            padding: 6px 16px;\n            border-radius: 50px;\n            letter-spacing: 0.08em;\n            text-transform: uppercase;\n            box-shadow: 0 4px 15px rgba(227, 27, 35, 0.3);\n            white-space: nowrap;\n        }\n\n        .pricing-header {\n            border-bottom: 1px solid var(--border);\n            padding-bottom: 24px;\n            margin-bottom: 28px;\n        }\n\n        .pricing-card.popular .pricing-header {\n            border-bottom-color: rgba(255, 255, 255, 0.1);\n        }\n\n        .pricing-tier {\n            font-size: 0.78rem;\n            font-weight: 700;\n            color: var(--red);\n            text-transform: uppercase;\n            letter-spacing: 0.05em;\n            display: block;\n        }\n\n        .pricing-card.popular .pricing-tier {\n            color: var(--gold2);\n        }\n\n        .pricing-price {\n            font-size: 2.2rem;\n            font-weight: 900;\n            color: var(--navy);\n            margin-top: 8px;\n            display: flex;\n            align-items: baseline;\n        }\n\n        .pricing-card.popular .pricing-price {\n            color: var(--white);\n        }\n\n        .pricing-price span {\n            font-size: 0.95rem;\n            font-weight: 500;\n            color: var(--gray);\n            margin-left: 4px;\n        }\n\n        .pricing-card.popular .pricing-price span {\n            color: #a5b1c2;\n        }\n\n        .pricing-features {\n            list-style: none;\n            display: flex;\n            flex-direction: column;\n            gap: 14px;\n            margin-bottom: 36px;\n            flex-grow: 1;\n        }\n\n        .pricing-feature-item {\n            font-size: 0.92rem;\n            color: var(--navy-mid);\n            display: flex;\n            align-items: center;\n            gap: 10px;\n        }\n\n        .pricing-card.popular .pricing-feature-item {\n            color: #d1d8e0;\n        }\n\n        .pricing-feature-item svg {\n            flex-shrink: 0;\n            stroke: var(--red);\n        }\n\n        .pricing-card.popular .pricing-feature-item svg {\n            stroke: var(--gold2);\n        }\n\n        .pricing-action {\n            width: 100%;\n        }\n\n        /* 6. CTA Section */\n        .cta-section {\n            background-color: var(--navy);\n            color: var(--white);\n            overflow: hidden;\n            padding: 100px 0;\n        }\n\n        .cta-grid {\n            display: grid;\n            grid-template-columns: 1.25fr 0.75fr;\n            gap: 60px;\n            align-items: center;\n        }\n\n        .cta-content {\n            display: flex;\n            flex-direction: column;\n            align-items: flex-start;\n        }\n\n        .cta-title {\n            font-size: clamp(1.8rem, 4vw, 2.6rem);\n            font-weight: 800;\n            line-height: 1.2;\n            margin-bottom: 16px;\n            letter-spacing: -0.01em;\n        }\n\n        .cta-subtitle {\n            font-size: 1.05rem;\n            color: #d1d8e0;\n            margin-bottom: 32px;\n            max-width: 580px;\n            line-height: 1.7;\n        }\n\n        .cta-buttons {\n            display: flex;\n            gap: 16px;\n        }\n\n        .cta-visual {\n            position: relative;\n            display: flex;\n            justify-content: center;\n            align-items: center;\n            width: 100%;\n        }\n\n        .cta-composition {\n            position: relative;\n            width: 100%;\n            max-width: 320px;\n        }\n\n        .cta-main-img {\n            width: 100%;\n            height: auto;\n            border-radius: 20px;\n            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));\n            border: 1px solid rgba(255, 255, 255, 0.08);\n            transform: rotate(2deg);\n            transition: all 0.5s ease;\n        }\n\n        .cta-main-img:hover {\n            transform: rotate(0deg) scale(1.03);\n        }\n\n        /* 7. Footer Section */\n        .footer {\n            background-color: #0F1624;\n            color: #8894AA;\n            padding: 80px 0 40px;\n            border-top: 1px solid rgba(255, 255, 255, 0.04);\n        }\n\n        .footer-grid {\n            display: grid;\n            grid-template-columns: 1.6fr 1fr 1fr 1fr;\n            gap: 48px;\n            margin-bottom: 60px;\n        }\n\n        .footer-brand {\n            display: flex;\n            flex-direction: column;\n            align-items: flex-start;\n            gap: 16px;\n        }\n\n        .footer-brand .logo-text .wordmark .kasir {\n            color: var(--white);\n        }\n\n        .footer-brand .logo-text .tagline {\n            color: var(--gray);\n        }\n\n        .footer-desc {\n            font-size: 0.92rem;\n            line-height: 1.6;\n            max-width: 280px;\n        }\n\n        .footer-col {\n            display: flex;\n            flex-direction: column;\n            gap: 20px;\n        }\n\n        .footer-col-title {\n            font-size: 0.9rem;\n            font-weight: 700;\n            color: var(--white);\n            text-transform: uppercase;\n            letter-spacing: 0.05em;\n        }\n\n        .footer-links {\n            list-style: none;\n            display: flex;\n            flex-direction: column;\n            gap: 12px;\n        }\n\n        .footer-link {\n            font-size: 0.9rem;\n            color: #8894AA;\n        }\n\n        .footer-link:hover {\n            color: var(--white);\n        }\n\n        .footer-bottom {\n            border-top: 1px solid rgba(255, 255, 255, 0.05);\n            padding-top: 30px;\n            display: flex;\n            justify-content: space-between;\n            align-items: center;\n            flex-wrap: wrap;\n            gap: 20px;\n        }\n\n        .copyright {\n            font-size: 0.85rem;\n        }\n\n        .social-links {\n            display: flex;\n            gap: 12px;\n        }\n\n        .social-link {\n            width: 36px;\n            height: 36px;\n            background-color: rgba(255, 255, 255, 0.03);\n            border-radius: 50%;\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            color: #8894AA;\n            transition: all 0.3s ease;\n        }\n\n        .social-link:hover {\n            background-color: var(--red);\n            color: var(--white);\n            transform: translateY(-2px);\n        }\n\n        .social-link svg {\n            fill: currentColor;\n        }\n\n        /* Scroll Reveal Animation Styles */\n        .fade-up {\n            opacity: 0;\n            transform: translateY(30px);\n            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);\n        }\n\n        .fade-up.visible {\n            opacity: 1;\n            transform: none;\n        }\n\n        /* Delay Classes for stagger effect */\n        .delay-1 { transition-delay: 0.08s; }\n        .delay-2 { transition-delay: 0.16s; }\n        .delay-3 { transition-delay: 0.24s; }\n        .delay-4 { transition-delay: 0.32s; }\n        .delay-5 { transition-delay: 0.40s; }\n\n        /* Responsiveness and Breakpoints */\n\n        /* Tablet Viewport (<= 900px) */\n        @media (max-width: 900px) {\n            section {\n                padding: 80px 0;\n            }\n\n            .navbar .container {\n                position: relative;\n            }\n\n            .nav-links {\n                display: flex;\n                position: fixed;\n                top: 0;\n                right: -100%;\n                width: 280px;\n                height: 100vh;\n                background-color: var(--white);\n                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.05);\n                flex-direction: column;\n                align-items: flex-start;\n                padding: 100px 40px;\n                gap: 24px;\n                transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);\n                z-index: 999;\n            }\n\n            .nav-links.mobile-active {\n                right: 0;\n            }\n\n            .menu-toggle {\n                display: flex;\n                z-index: 1001;\n            }\n\n            /* Hamburger animation */\n            .menu-toggle.open span:nth-child(1) {\n                transform: rotate(45deg) translate(6px, 6px);\n            }\n            .menu-toggle.open span:nth-child(2) {\n                opacity: 0;\n            }\n            .menu-toggle.open span:nth-child(3) {\n                transform: rotate(-45deg) translate(5px, -5px);\n            }\n\n            .navbar .btn-nav {\n                display: none;\n            }\n\n            /* Main Grids to 1 column */\n            .hero-grid {\n                grid-template-columns: 1fr;\n                gap: 50px;\n                text-align: center;\n            }\n\n            .hero-content {\n                align-items: center;\n            }\n\n            .hero-actions {\n                justify-content: center;\n                width: 100%;\n            }\n\n            .hero-stats {\n                justify-content: center;\n                gap: 30px;\n            }\n\n            .features-grid {\n                grid-template-columns: repeat(2, 1fr);\n            }\n\n            .how-grid {\n                grid-template-columns: 1fr;\n                gap: 60px;\n            }\n\n            .how-steps {\n                width: 100%;\n            }\n\n            .testimonials-grid {\n                grid-template-columns: repeat(2, 1fr);\n            }\n\n            .pricing-grid {\n                grid-template-columns: 1.5fr;\n                justify-content: center;\n                gap: 32px;\n            }\n\n            .cta-grid {\n                grid-template-columns: 1fr;\n                text-align: center;\n                gap: 40px;\n            }\n\n            .cta-content {\n                align-items: center;\n            }\n\n            .cta-buttons {\n                justify-content: center;\n                width: 100%;\n            }\n\n            .footer-grid {\n                grid-template-columns: 1fr 1fr;\n                gap: 36px;\n            }\n        }\n\n        /* Mobile Viewport (<= 600px) */\n        @media (max-width: 600px) {\n            section {\n                padding: 60px 0;\n            }\n\n            .hero-section {\n                padding-top: 120px;\n                padding-bottom: 60px;\n            }\n\n            /* Hide phone mockup on mobile for clean UI */\n            .hero-visual {\n                display: none;\n            }\n\n            .features-grid {\n                grid-template-columns: 1fr;\n            }\n\n            .testimonials-grid {\n                grid-template-columns: 1fr;\n            }\n\n            .pricing-grid {\n                grid-template-columns: 1fr;\n            }\n\n            .footer-grid {\n                grid-template-columns: 1fr;\n                gap: 32px;\n            }\n\n            .footer-bottom {\n                flex-direction: column;\n                text-align: center;\n            }\n\n            .scanner-illustration-card {\n                position: relative;\n                bottom: auto;\n                right: auto;\n                width: 100%;\n                margin-top: 15px;\n                box-shadow: 0 8px 25px rgba(26, 34, 51, 0.06);\n            }\n\n            .dashboard-dark-card {\n                padding: 16px;\n            }\n\n            .dash-body-grid {\n                grid-template-columns: 1fr;\n            }\n\n            .kpi-row {\n                grid-template-columns: 1fr;\n                gap: 10px;\n            }\n\n            .hero-actions, .cta-buttons {\n                flex-direction: column;\n                width: 100%;\n            }\n\n            .btn {\n                width: 100%;\n            }\n\n            .hero-stats {\n                flex-wrap: wrap;\n                justify-content: space-around;\n                gap: 20px;\n            }\n        }\n    ",
                    }}
                />
            </Head>

            {/*  NAVBAR (fixed)  */}
            <nav className="navbar">
                <div className="container">
                    <a href="#" className="logo">
                        <div className="logo-wrapper">
                            {/*  Custom Inline SVG Logo complying with design.md rules  */}
                            <svg
                                className="logo-icon"
                                width="36"
                                height="36"
                                viewBox="0 0 42 42"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                {/*  Shopping Cart Red path  */}
                                <path
                                    d="M5 6H9L12.5 23H31L35 11H13"
                                    stroke="#E31B23"
                                    strokeWidth="3.5"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                                <circle cx="15" cy="30" r="3" fill="#E31B23" />
                                <circle cx="28" cy="30" r="3" fill="#E31B23" />
                                {/*  Red badge for checkmark  */}
                                <circle cx="22" cy="16" r="6" fill="#E31B23" />
                                <path
                                    d="M19.5 16L21 17.5L24.5 14"
                                    stroke="#FFFFFF"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                                {/*  Pixel Squares Emas di pojok kanan atas  */}
                                <rect
                                    x="30"
                                    y="3"
                                    width="4"
                                    height="4"
                                    fill="#F5A623"
                                />
                                <rect
                                    x="34"
                                    y="7"
                                    width="4"
                                    height="4"
                                    fill="#F5A623"
                                />
                                <rect
                                    x="30"
                                    y="7"
                                    width="4"
                                    height="4"
                                    fill="#F5A623"
                                    opacity="0.5"
                                />
                            </svg>
                            <div className="logo-text">
                                <span className="wordmark">
                                    <span className="go">Go</span>
                                    <span className="kasir">Kasir</span>
                                </span>
                                <span className="tagline">
                                    KASIR CERDAS · BISNIS SUKSES
                                </span>
                            </div>
                        </div>
                    </a>

                    {/*  Menu Navigation  */}
                    <div className="nav-links">
                        <a href="#fitur" className="nav-link">
                            Fitur
                        </a>
                        <a href="#cara-kerja" className="nav-link">
                            Cara Kerja
                        </a>
                        <a href="#testimoni" className="nav-link">
                            Testimoni
                        </a>
                        <a href="#harga" className="nav-link">
                            Harga
                        </a>
                    </div>

                    {/*  CTA Button  */}
                    <div
                        style={{
                            display: "flex",
                            gap: "16px",
                            alignItems: "center",
                        }}
                    >
                        <Link href="/register" className="btn btn-nav">
                            Daftar
                        </Link>
                        <a
                            href="https://play.google.com/store/apps/details?id=com.gokasir.net"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn btn-nav"
                            style={{ backgroundColor: "var(--navy)" }}
                        >
                            Download App
                        </a>
                    </div>

                    {/*  Mobile Hamburger Button  */}
                    <button className="menu-toggle" aria-label="Toggle Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </nav>

            {/*  HERO SECTION  */}
            <header className="hero-section">
                {/*  Radial Gradient Blobs for ambient decoration  */}
                <div className="blob blob-hero-1"></div>
                <div className="blob blob-hero-2"></div>

                <div className="container">
                    <div className="hero-grid">
                        {/*  Hero Left Column: Texts and actions  */}
                        <div className="hero-content fade-up">
                            <span className="tag-eyebrow">
                                Kasir Digital #1 untuk UMKM Indonesia
                            </span>
                            <h1 className="hero-title">
                                Kasir <em>Cerdas</em>,<br />
                                Bisnis <em>Sukses</em>
                            </h1>
                            <p className="hero-body">
                                Kelola penjualan, pantau stok barang real-time,
                                dan terima pembayaran digital QRIS dalam satu
                                aplikasi kasir pintar. Dirancang khusus untuk
                                wirausaha Indonesia yang ingin berkembang lebih
                                cepat dan teratur.
                            </p>

                            <div className="hero-actions">
                                <Link
                                    href="/register"
                                    className="btn btn-primary"
                                >
                                    Daftar Sekarang - Gratis
                                </Link>
                                <a
                                    href="https://play.google.com/store/apps/details?id=com.gokasir.net"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-ghost"
                                >
                                    Download Aplikasi
                                </a>
                            </div>

                            <div className="hero-stats">
                                <div className="stat-item">
                                    <span className="stat-number">50K+</span>
                                    <span className="stat-label">
                                        Pengguna Aktif
                                    </span>
                                </div>
                                <div className="stat-item">
                                    <span className="stat-number">120jt+</span>
                                    <span className="stat-label">
                                        Transaksi Berhasil
                                    </span>
                                </div>
                                <div className="stat-item">
                                    <span className="stat-number">4.9★</span>
                                    <span className="stat-label">
                                        Rating Pengguna
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/*  Hero Right Column: Layout composition with illustration and mockup  */}
                        <div className="hero-visual fade-up delay-1">
                            <div className="hero-composition">
                                {/*  Base illustration from public/illustration  */}
                                <img
                                    src="/illustration/illustration_cashier_welcome.png"
                                    className="hero-main-img"
                                    alt="Ilustrasi Kasir Menyambut Pelanggan"
                                />

                                {/*  Phone Mockup Floating on top  */}
                                <div className="phone-mockup">
                                    <div className="phone-header">
                                        <div className="phone-status-bar">
                                            <span className="phone-time">
                                                09:41
                                            </span>
                                            <div className="phone-icons">
                                                📶 🔋
                                            </div>
                                        </div>
                                        <div className="phone-total">
                                            <span className="total-label">
                                                Total Transaksi
                                            </span>
                                            <span className="total-amount">
                                                Rp 3.450.000
                                            </span>
                                        </div>
                                    </div>
                                    <div className="phone-body">
                                        <div className="pos-item">
                                            <div className="item-icon bg-green">
                                                ☕
                                            </div>
                                            <div className="item-info">
                                                <span className="item-name">
                                                    Kopi Susu Aren
                                                </span>
                                                <span className="item-qty">
                                                    3x · Rp 18.000
                                                </span>
                                            </div>
                                            <span className="item-price">
                                                Rp 54k
                                            </span>
                                        </div>
                                        <div className="pos-item">
                                            <div className="item-icon bg-gold">
                                                🍞
                                            </div>
                                            <div className="item-info">
                                                <span className="item-name">
                                                    Roti Bakar Cokelat
                                                </span>
                                                <span className="item-qty">
                                                    2x · Rp 22.000
                                                </span>
                                            </div>
                                            <span className="item-price">
                                                Rp 44k
                                            </span>
                                        </div>
                                        <div className="pos-item">
                                            <div className="item-icon bg-blue">
                                                🍵
                                            </div>
                                            <div className="item-info">
                                                <span className="item-name">
                                                    Es Matcha Latte
                                                </span>
                                                <span className="item-qty">
                                                    1x · Rp 25.000
                                                </span>
                                            </div>
                                            <span className="item-price">
                                                Rp 25k
                                            </span>
                                        </div>
                                    </div>
                                    <div className="phone-footer">
                                        <button className="phone-pay-btn">
                                            BAYAR SEKARANG
                                        </button>
                                    </div>
                                </div>

                                {/*  Floating Badges with high-end style  */}
                                <div className="float-badge float-badge-1">
                                    <span className="badge-dot dot-green"></span>
                                    <span className="badge-text">
                                        Transaksi berhasil
                                    </span>
                                </div>
                                <div className="float-badge float-badge-2">
                                    <span className="badge-icon">⭐</span>
                                    <span className="badge-text">
                                        Stok diperbarui
                                    </span>
                                </div>
                                <div className="float-badge float-badge-3">
                                    <span className="badge-icon">🖨️</span>
                                    <span className="badge-text">
                                        Struk dicetak
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/*  FITUR SECTION (6 cards, 3 columns)  */}
            <section id="fitur" className="features-section">
                <div className="container">
                    <div className="section-header fade-up">
                        <span className="tag-eyebrow">Fitur Unggulan</span>
                        <h2 className="section-title">
                            Semua yang Anda Butuhkan untuk Mengembangkan Bisnis
                        </h2>
                        <p className="section-subtitle">
                            Kelola operasional toko Anda lebih efisien dengan
                            fitur kasir pintar terlengkap.
                        </p>
                    </div>

                    <div className="features-grid">
                        {/*  Feature 1: Transaksi  */}
                        <div className="feature-card fade-up delay-1">
                            <div className="feature-icon-wrapper">
                                {/*  Credit Card SVG  */}
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#E31B23"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <rect
                                        x="2"
                                        y="5"
                                        width="20"
                                        height="14"
                                        rx="2"
                                        ry="2"
                                    />
                                    <line x1="2" y1="10" x2="22" y2="10" />
                                    <line x1="6" y1="15" x2="10" y2="15" />
                                </svg>
                            </div>
                            <h3 className="feature-title">
                                Transaksi Super Cepat
                            </h3>
                            <p className="feature-description">
                                Proses transaksi hanya dalam hitungan detik.
                                Mendukung scan barcode dan integrasi QRIS
                                dinamis.
                            </p>
                        </div>

                        {/*  Feature 2: Manajemen Stok  */}
                        <div className="feature-card fade-up delay-2">
                            <div className="feature-icon-wrapper">
                                {/*  Archive Box SVG  */}
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#E31B23"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <polyline points="21 8 21 21 3 21 3 8" />
                                    <rect x="1" y="3" width="22" height="5" />
                                    <line x1="10" y1="12" x2="14" y2="12" />
                                </svg>
                            </div>
                            <h3 className="feature-title">
                                Manajemen Stok Otomatis
                            </h3>
                            <p className="feature-description">
                                Stok barang berkurang otomatis saat terjual.
                                Dapatkan notifikasi saat stok menipis dan kelola
                                multi-gudang.
                            </p>
                        </div>

                        {/*  Feature 3: Thermal Printer  */}
                        <div className="feature-card fade-up delay-3">
                            <div className="feature-icon-wrapper">
                                {/*  Printer SVG  */}
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#E31B23"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <polyline points="6 9 6 2 18 2 18 9" />
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                    <rect x="6" y="14" width="12" height="8" />
                                </svg>
                            </div>
                            <h3 className="feature-title">
                                Cetak Struk Thermal
                            </h3>
                            <p className="feature-description">
                                Cetak struk belanja fisik via Bluetooth atau
                                WiFi printer. Kustomisasi logo toko Anda sendiri
                                di struk.
                            </p>
                        </div>

                        {/*  Feature 4: Laporan  */}
                        <div className="feature-card fade-up delay-1">
                            <div className="feature-icon-wrapper">
                                {/*  Bar chart lines SVG  */}
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#E31B23"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <line x1="18" y1="20" x2="18" y2="10" />
                                    <line x1="12" y1="20" x2="12" y2="4" />
                                    <line x1="6" y1="20" x2="6" y2="14" />
                                </svg>
                            </div>
                            <h3 className="feature-title">
                                Laporan Bisnis Real-Time
                            </h3>
                            <p className="feature-description">
                                Pantau penjualan dan profit harian kapan saja
                                dari mana saja. Ekspor laporan keuangan dalam
                                format Excel/PDF.
                            </p>
                        </div>

                        {/*  Feature 5: Multi-kasir  */}
                        <div className="feature-card fade-up delay-2">
                            <div className="feature-icon-wrapper">
                                {/*  Users group SVG  */}
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#E31B23"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                            <h3 className="feature-title">
                                Multi-Kasir & Multi-Toko
                            </h3>
                            <p className="feature-description">
                                Kelola banyak toko dengan hak akses akun kasir
                                masing-masing yang terpantau dalam satu
                                dashboard utama.
                            </p>
                        </div>

                        {/*  Feature 6: Loyalitas  */}
                        <div className="feature-card fade-up delay-3">
                            <div className="feature-icon-wrapper">
                                {/*  Heart SVG  */}
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#E31B23"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                </svg>
                            </div>
                            <h3 className="feature-title">
                                Loyalitas Pelanggan
                            </h3>
                            <p className="feature-description">
                                Kelola database pelanggan, berikan poin belanja,
                                dan luncurkan diskon promosi menarik untuk
                                menjaga loyalitas.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/*  CARA KERJA SECTION (2 columns)  */}
            <section id="cara-kerja" className="how-section">
                <div className="container">
                    <div className="how-grid">
                        {/*  How Left Column: Dashboard Graphic + Floating Scanner illustration  */}
                        <div className="how-visual-container fade-up">
                            {/*  Dashboard Mockup Card  */}
                            <div className="dashboard-dark-card">
                                <div className="dash-header">
                                    <div className="window-controls">
                                        <span className="dot dot-red"></span>
                                        <span className="dot dot-yellow"></span>
                                        <span className="dot dot-green"></span>
                                    </div>
                                    <span className="dash-title">
                                        GoKasir Dashboard · Laporan Real-Time
                                    </span>
                                </div>

                                {/*  KPI dashboard data points  */}
                                <div className="kpi-row">
                                    <div className="kpi-card">
                                        <span className="kpi-label">
                                            Omzet Bulan Ini
                                        </span>
                                        <span className="kpi-value text-red">
                                            Rp 45.820k
                                        </span>
                                        <span className="kpi-trend">
                                            ▲ +12.4%
                                        </span>
                                    </div>
                                    <div className="kpi-card">
                                        <span className="kpi-label">
                                            Transaksi
                                        </span>
                                        <span className="kpi-value text-gold">
                                            2.418 kali
                                        </span>
                                        <span className="kpi-trend">
                                            ▲ +8.2%
                                        </span>
                                    </div>
                                    <div className="kpi-card">
                                        <span className="kpi-label">
                                            Net Profit
                                        </span>
                                        <span className="kpi-value text-green">
                                            Rp 18.250k
                                        </span>
                                        <span className="kpi-trend">
                                            ▲ +15.1%
                                        </span>
                                    </div>
                                </div>

                                {/*  Dashboard Charts & Products table  */}
                                <div className="dash-body-grid">
                                    <div className="chart-container">
                                        <span className="panel-title">
                                            Grafik Mingguan
                                        </span>
                                        <div className="bar-chart">
                                            <div className="bar-col">
                                                <div
                                                    className="bar"
                                                    style={{ height: "60%" }}
                                                ></div>
                                                <span>Sen</span>
                                            </div>
                                            <div className="bar-col">
                                                <div
                                                    className="bar"
                                                    style={{ height: "45%" }}
                                                ></div>
                                                <span>Sel</span>
                                            </div>
                                            <div className="bar-col">
                                                <div
                                                    className="bar bg-gold"
                                                    style={{ height: "75%" }}
                                                ></div>
                                                <span>Rab</span>
                                            </div>
                                            <div className="bar-col">
                                                <div
                                                    className="bar"
                                                    style={{ height: "50%" }}
                                                ></div>
                                                <span>Kam</span>
                                            </div>
                                            <div className="bar-col">
                                                <div
                                                    className="bar"
                                                    style={{ height: "80%" }}
                                                ></div>
                                                <span>Jum</span>
                                            </div>
                                            <div className="bar-col">
                                                <div
                                                    className="bar bg-gold"
                                                    style={{ height: "95%" }}
                                                ></div>
                                                <span>Sab</span>
                                            </div>
                                            <div className="bar-col">
                                                <div
                                                    className="bar"
                                                    style={{ height: "85%" }}
                                                ></div>
                                                <span>Min</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="table-container">
                                        <span className="panel-title">
                                            Terlaris
                                        </span>
                                        <table className="dash-table">
                                            <thead>
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Kopi Aren</td>
                                                    <td>
                                                        <span className="badge badge-green">
                                                            Aman
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Roti Cokelat</td>
                                                    <td>
                                                        <span className="badge badge-yellow">
                                                            Menipis
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Matcha Latte</td>
                                                    <td>
                                                        <span className="badge badge-green">
                                                            Aman
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {/*  Mini scanner card overlapping with illustration_casher_scan_product.png  */}
                            <div className="scanner-illustration-card">
                                <div className="scanner-card-header">
                                    <span className="scanner-badge">
                                        REAL-TIME SCANNING
                                    </span>
                                </div>
                                <img
                                    src="/illustration/illustration_casher_scan_product.png"
                                    className="scanner-img"
                                    alt="Kasir Melakukan Scan Produk"
                                />
                            </div>
                        </div>

                        {/*  How Right Column: Steps  */}
                        <div className="how-content fade-up delay-1">
                            <span className="tag-eyebrow">Cara Kerja</span>
                            <h2 className="section-title">
                                Langkah Mudah Memulai GoKasir
                            </h2>
                            <p
                                className="section-subtitle"
                                style={{
                                    marginTop: "10px",
                                    marginBottom: "30px",
                                }}
                            >
                                Tinggalkan pembukuan manual yang membingungkan.
                                Mulai transformasi digital toko Anda dalam 3
                                langkah ringkas.
                            </p>

                            <div className="how-steps">
                                {/*  Step 1  */}
                                <div className="step-item">
                                    <div className="step-number">1</div>
                                    <div className="step-content">
                                        <h4 className="step-title">
                                            Daftar & Setup Toko
                                        </h4>
                                        <p className="step-description">
                                            Buat akun GoKasir secara gratis.
                                            Masukkan profil usaha dan daftarkan
                                            katalog produk Anda dengan sangat
                                            cepat.
                                        </p>
                                    </div>
                                </div>

                                {/*  Step 2  */}
                                <div className="step-item">
                                    <div className="step-number">2</div>
                                    <div className="step-content">
                                        <h4 className="step-title">
                                            Mulai Transaksi
                                        </h4>
                                        <p className="step-description">
                                            Gunakan tablet atau smartphone untuk
                                            melayani pembeli. Terima tunai
                                            maupun pembayaran non-tunai dengan
                                            barcode QRIS.
                                        </p>
                                    </div>
                                </div>

                                {/*  Step 3  */}
                                <div className="step-item">
                                    <div className="step-number">3</div>
                                    <div className="step-content">
                                        <h4 className="step-title">
                                            Pantau & Kembangkan
                                        </h4>
                                        <p className="step-description">
                                            Semua data transaksi otomatis masuk
                                            ke pembukuan. Analisis barang
                                            terlaris dan profit bersih langsung
                                            dari dashboard Anda.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/*  TESTIMONI SECTION (3 cards)  */}
            <section id="testimoni" className="testimonials-section">
                <div className="container">
                    <div className="section-header fade-up">
                        <span className="tag-eyebrow">Testimoni Pengguna</span>
                        <h2 className="section-title">
                            Kata Mereka yang Telah Sukses Bersama GoKasir
                        </h2>
                        <p className="section-subtitle">
                            Dengarkan kisah sukses langsung dari ribuan pemilik
                            bisnis yang mempercayakan GoKasir.
                        </p>
                    </div>

                    <div className="testimonials-grid">
                        {/*  Testimonial 1  */}
                        <div className="testimonial-card fade-up delay-1">
                            <div>
                                <div className="stars">★★★★★</div>
                                <p className="quote">
                                    "GoKasir sangat menghemat waktu kami. Dulu
                                    stok barang sering selisih, sekarang
                                    semuanya otomatis terupdate setiap ada
                                    transaksi baru. Laporan laba ruginya
                                    langsung jadi!"
                                </p>
                            </div>
                            <div className="author-info">
                                <div className="avatar-initial bg-avatar-1">
                                    BP
                                </div>
                                <div className="author-details">
                                    <span className="author-name">
                                        Budi Prasetyo
                                    </span>
                                    <span className="author-business">
                                        Owner Kopi Kenangan Indah
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/*  Testimonial 2  */}
                        <div className="testimonial-card fade-up delay-2">
                            <div>
                                <div className="stars">★★★★★</div>
                                <p className="quote">
                                    "Bisa menerima pembayaran QRIS tanpa ribet
                                    adalah penyelamat bisnis fashion saya.
                                    Pelanggan senang karena bayarnya cepat
                                    sekali, uang pun langsung aman masuk ke
                                    rekening toko."
                                </p>
                            </div>
                            <div className="author-info">
                                <div className="avatar-initial bg-avatar-2">
                                    SR
                                </div>
                                <div className="author-details">
                                    <span className="author-name">
                                        Siti Rahma
                                    </span>
                                    <span className="author-business">
                                        Pemilik Butik Rahma Style
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/*  Testimonial 3  */}
                        <div className="testimonial-card fade-up delay-3">
                            <div>
                                <div className="stars">★★★★★</div>
                                <p className="quote">
                                    "Fitur multi-kasir sangat berguna untuk
                                    memantau 3 cabang toko saya dari rumah. Saya
                                    bisa membatasi hak akses admin/kasir demi
                                    keamanan transaksi bisnis cabang."
                                </p>
                            </div>
                            <div className="author-info">
                                <div className="avatar-initial bg-avatar-3">
                                    HW
                                </div>
                                <div className="author-details">
                                    <span className="author-name">
                                        Hendra Wijaya
                                    </span>
                                    <span className="author-business">
                                        Franchise Martabak Bangka 88
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/*  PRICING SECTION (3 tiers)  */}
            {/*  PRICING SECTION (Single Tier)  */}
            <section id="harga" className="pricing-section">
                <div className="container">
                    <div className="section-header fade-up">
                        <span className="tag-eyebrow">Paket Harga</span>
                        <h2 className="section-title">
                            Sangat Ringan, Tanpa Beban Bulanan
                        </h2>
                        <p className="section-subtitle">
                            Pelanggan baru langsung mendapatkan 500 token
                            transaksi gratis untuk mulai memakai GoKasir.Net.
                        </p>
                    </div>

                    <div style={{ maxWidth: "600px", margin: "0 auto" }}>
                        <div className="pricing-card popular fade-up delay-1">
                            <div className="popular-badge">
                                Paling Ringan untuk UMKM
                            </div>
                            <div className="pricing-header">
                                <span className="pricing-tier">
                                    Gratis 500 Token Awal
                                </span>
                                <div className="pricing-price">Gratis</div>
                            </div>
                            <ul className="pricing-features">
                                <li className="pricing-feature-item">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#F7C548"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Gratis 500 token transaksi untuk pelanggan
                                    baru
                                </li>
                                <li className="pricing-feature-item">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#F7C548"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    1 transaksi = 1 struk = 1 token
                                </li>
                                <li className="pricing-feature-item">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#F7C548"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Setelah 500 token, hanya Rp 100 / transaksi
                                </li>
                                <li className="pricing-feature-item">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#F7C548"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Topup token minimal Rp 10.000
                                </li>
                                <li className="pricing-feature-item">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#F7C548"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Sangat ringan dan tidak membebani UMKM
                                </li>
                                <li className="pricing-feature-item">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#F7C548"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Laporan penjualan otomatis & dipantau online
                                </li>
                            </ul>

                            <div
                                style={{
                                    background: "rgba(255,255,255,0.05)",
                                    padding: "20px",
                                    borderRadius: "14px",
                                    marginBottom: "28px",
                                    border: "1px solid rgba(255,255,255,0.1)",
                                }}
                            >
                                <div
                                    style={{
                                        fontSize: "0.9rem",
                                        fontWeight: 700,
                                        color: "#F7C548",
                                        marginBottom: "10px",
                                    }}
                                >
                                    Contoh Perhitungan Ringan:
                                </div>
                                <p
                                    style={{
                                        fontSize: "0.9rem",
                                        color: "#d1d8e0",
                                        lineHeight: 1.5,
                                        marginBottom: "8px",
                                    }}
                                >
                                    Jika usaha memiliki 20 transaksi per hari:
                                </p>
                                <p
                                    style={{
                                        fontSize: "0.95rem",
                                        color: "#fff",
                                        fontWeight: 700,
                                        marginBottom: "8px",
                                    }}
                                >
                                    20 transaksi × Rp 100 = Rp 2.000 / hari
                                </p>
                                <p
                                    style={{
                                        fontSize: "0.9rem",
                                        color: "#d1d8e0",
                                        lineHeight: 1.6,
                                    }}
                                >
                                    Dalam 30 hari sekitar{" "}
                                    <strong style={{ color: "#fff" }}>
                                        Rp 60.000
                                    </strong>
                                    . Biaya mengikuti jumlah transaksi usaha
                                    Anda.
                                </p>
                            </div>

                            <div className="pricing-action">
                                <a
                                    href="https://play.google.com/store/apps/details?id=com.gokasir.net"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-primary"
                                    style={{
                                        width: "100%",
                                        boxShadow:
                                            "0 8px 25px rgba(227,27,35,0.4)",
                                        padding: "18px",
                                        fontSize: "1.05rem",
                                    }}
                                >
                                    Mulai Sekarang - Gratis 500 Token
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/*  CTA SECTION (dark)  */}
            <section className="cta-section">
                {/*  Decoration radial gradient blobs  */}
                <div className="blob blob-cta-1"></div>
                <div className="blob blob-cta-2"></div>

                <div className="container">
                    <div className="cta-grid">
                        {/*  CTA Left Content  */}
                        <div className="cta-content fade-up">
                            <h2 className="cta-title">
                                Siap Meningkatkan Omzet Bisnis Anda?
                            </h2>
                            <p className="cta-subtitle">
                                Bergabunglah dengan puluhan ribu wirausaha
                                cerdas lainnya sekarang juga. Kelola toko Anda
                                lebih tenang, aman, dan menguntungkan.
                            </p>
                            <div className="cta-buttons">
                                <a
                                    href="https://play.google.com/store/apps/details?id=com.gokasir.net"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-white"
                                >
                                    Download Aplikasi
                                </a>
                                <a
                                    href="#harga"
                                    className="btn btn-ghost"
                                    style={{
                                        color: "var(--white)",
                                        borderColor: "rgba(255,255,255,0.2)",
                                    }}
                                >
                                    Pelajari Selengkapnya
                                </a>
                            </div>
                        </div>

                        {/*  CTA Right Visual Composition utilizing cashier welcome image in a distinct angle  */}
                        <div className="cta-visual fade-up delay-1">
                            <div className="cta-composition">
                                <img
                                    src="/illustration/illustration_cashier_welcome.png"
                                    className="cta-main-img"
                                    alt="Bergabung dengan GoKasir"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/*  FOOTER  */}
            <footer className="footer">
                <div className="container">
                    <div className="footer-grid">
                        {/*  Footer Column 1: Brand Info  */}
                        <div className="footer-brand">
                            <div className="logo-wrapper">
                                <svg
                                    className="logo-icon"
                                    width="34"
                                    height="34"
                                    viewBox="0 0 42 42"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M5 6H9L12.5 23H31L35 11H13"
                                        stroke="#E31B23"
                                        strokeWidth="3.5"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                    <circle
                                        cx="15"
                                        cy="30"
                                        r="3"
                                        fill="#E31B23"
                                    />
                                    <circle
                                        cx="28"
                                        cy="30"
                                        r="3"
                                        fill="#E31B23"
                                    />
                                    <circle
                                        cx="22"
                                        cy="16"
                                        r="6"
                                        fill="#E31B23"
                                    />
                                    <path
                                        d="M19.5 16L21 17.5L24.5 14"
                                        stroke="#FFFFFF"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                    <rect
                                        x="30"
                                        y="3"
                                        width="4"
                                        height="4"
                                        fill="#F5A623"
                                    />
                                    <rect
                                        x="34"
                                        y="7"
                                        width="4"
                                        height="4"
                                        fill="#F5A623"
                                    />
                                    <rect
                                        x="30"
                                        y="7"
                                        width="4"
                                        height="4"
                                        fill="#F5A623"
                                        opacity="0.5"
                                    />
                                </svg>
                                <div className="logo-text">
                                    <span className="wordmark">
                                        <span className="go">Go</span>
                                        <span className="kasir">Kasir</span>
                                    </span>
                                    <span className="tagline">
                                        KASIR CERDAS · BISNIS SUKSES
                                    </span>
                                </div>
                            </div>
                            <p className="footer-desc">
                                GoKasir adalah platform POS (Point of Sale)
                                digital yang dirancang untuk mempercepat
                                pertumbuhan UMKM di Indonesia.
                            </p>
                        </div>

                        {/*  Footer Column 2: Fitur links  */}
                        <div className="footer-col">
                            <span className="footer-col-title">Fitur</span>
                            <ul className="footer-links">
                                <li>
                                    <a href="#fitur" className="footer-link">
                                        Kasir POS
                                    </a>
                                </li>
                                <li>
                                    <a href="#fitur" className="footer-link">
                                        Kelola Stok
                                    </a>
                                </li>
                                <li>
                                    <a href="#fitur" className="footer-link">
                                        Laporan Bisnis
                                    </a>
                                </li>
                                <li>
                                    <a href="#fitur" className="footer-link">
                                        Metode QRIS
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {/*  Footer Column 3: Perusahaan links  */}
                        <div className="footer-col">
                            <span className="footer-col-title">Perusahaan</span>
                            <ul className="footer-links">
                                <li>
                                    <a href="#" className="footer-link">
                                        Tentang Kami
                                    </a>
                                </li>
                                <li>
                                    <a href="#" className="footer-link">
                                        Kisah Sukses
                                    </a>
                                </li>
                                <li>
                                    <a href="#" className="footer-link">
                                        Karir
                                    </a>
                                </li>
                                <li>
                                    <a href="#" className="footer-link">
                                        Hubungi Kami
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {/*  Footer Column 4: Bantuan links  */}
                        <div className="footer-col">
                            <span className="footer-col-title">Bantuan</span>
                            <ul className="footer-links">
                                <li>
                                    <a href="#" className="footer-link">
                                        Pusat Bantuan
                                    </a>
                                </li>
                                <li>
                                    <a href="#" className="footer-link">
                                        Panduan Pengguna
                                    </a>
                                </li>
                                <li>
                                    <a href="#" className="footer-link">
                                        Kebijakan Privasi
                                    </a>
                                </li>
                                <li>
                                    <a href="#" className="footer-link">
                                        Syarat & Ketentuan
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {/*  Footer Bottom Area  */}
                    <div className="footer-bottom">
                        <span className="copyright">
                            © 2026 GoKasir. Seluruh Hak Cipta Dilindungi.
                        </span>

                        <div className="social-links">
                            <a
                                href="#"
                                className="social-link"
                                aria-label="Facebook"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24">
                                    <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c4.56-.93 8-4.96 8-9.75z" />
                                </svg>
                            </a>
                            <a
                                href="#"
                                className="social-link"
                                aria-label="Instagram"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                </svg>
                            </a>
                            <a
                                href="#"
                                className="social-link"
                                aria-label="Twitter"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>

            {/*  Scripts  */}
        </>
    );
}
