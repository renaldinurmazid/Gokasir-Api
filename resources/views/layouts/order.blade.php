<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#d30f28">
    <title>@yield('title', 'GoKasir - Order')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    {{-- Alpine.js --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
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

        body {
            font-family: var(--font-family);
            background-color: #F3F4F6;
        }

        /* Color classes mapping design system */
        .bg-brand-50 { background-color: var(--primary-50); }
        .bg-brand-100 { background-color: var(--primary-100); }
        .bg-brand-500 { background-color: var(--primary-500); }
        .bg-brand-600 { background-color: var(--primary-600); }
        .bg-brand-700 { background-color: var(--primary-700); }
        .text-brand-400 { color: var(--primary-400); }
        .text-brand-500 { color: var(--primary-500); }
        .text-brand-600 { color: var(--primary-600); }
        .text-brand-700 { color: var(--primary-700); }
        .border-brand-100 { border-color: var(--primary-100); }
        .border-brand-500 { border-color: var(--primary-500); }

        /* Semantic Colors mapping design system */
        .bg-success-500 { background-color: var(--success-500); }
        .bg-success-100 { background-color: var(--success-100); }
        .text-success-500 { color: var(--success-500); }
        .border-success-500 { border-color: var(--success-500); }
        .border-success-100 { border-color: var(--success-100); }

        .bg-warning-500 { background-color: var(--warning-500); }
        .bg-warning-100 { background-color: var(--warning-100); }
        .text-warning-500 { color: var(--warning-500); }
        .border-warning-500 { border-color: var(--warning-500); }
        .border-warning-100 { border-color: var(--warning-100); }

        .bg-danger-500 { background-color: var(--danger-500); }
        .bg-danger-100 { background-color: var(--danger-100); }
        .text-danger-500 { color: var(--danger-500); }
        .border-danger-500 { border-color: var(--danger-500); }
        .border-danger-100 { border-color: var(--danger-100); }

        .bg-info-500 { background-color: var(--info-500); }
        .bg-info-100 { background-color: var(--info-100); }
        .text-info-500 { color: var(--info-500); }
        .border-info-500 { border-color: var(--info-500); }
        .border-info-100 { border-color: var(--info-100); }

        .shadow-primary-hover:hover {
            box-shadow: var(--shadow-primary);
        }

        .focus-brand-ring:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(211, 15, 40, 0.12);
        }

        /* Webkit overflow scrolling for mobile horizontal scroll lists */
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
            -webkit-overflow-scrolling: touch;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none; /* Chrome, Safari and Opera */
        }

        /* Custom premium scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(107, 114, 128, 0.2);
            border-radius: 9999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.4);
        }

        /* Page Transitions */
        .fade-enter-active {
            animation: fade-in 200ms ease-out forwards;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Skeleton Shimmer loader */
        @keyframes shimmer {
            0%   { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #F3F4F6 25%, #E5E7EB 50%, #F3F4F6 75%);
            background-size: 400px 100%;
            animation: shimmer 1.5s infinite;
        }

        /* Pulse Ring for Active timeline step */
        @keyframes pulse-ring {
            0%   { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.6); opacity: 0; }
        }
        .pulse-effect {
            position: relative;
        }
        .pulse-effect::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 9999px;
            background: var(--primary-500);
            left: 0;
            top: 0;
            z-index: -1;
            animation: pulse-ring 1.8s infinite;
        }

        /* Cart Badge Bounce */
        @keyframes badge-bounce {
            0%, 100% { transform: scale(1); }
            30%       { transform: scale(1.4); }
            60%       { transform: scale(0.9); }
        }
        .animate-bounce-badge {
            animation: badge-bounce 300ms ease-out;
        }

        /* Bottom Sheet slide up */
        @keyframes sheet-up {
            from { transform: translateY(100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .bottom-sheet {
            animation: sheet-up 250ms cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 antialiased selection:bg-red-600 selection:text-white">
    <div class="min-h-screen flex justify-center">
        <div class="bg-slate-50 w-full max-w-[480px] min-h-screen overflow-x-hidden flex flex-col border-x border-slate-200 shadow-lg relative">
            @yield('content')
        </div>
    </div>
</body>

</html>
