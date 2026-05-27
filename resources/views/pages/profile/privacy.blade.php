@extends('layouts.order')

@section('title', 'GoKasir - Kebijakan Privasi')

@section('content')
    <!-- Header -->
    <header class="bg-brand-500 text-white p-5 sticky top-0 z-20 shadow-md">
        <div class="flex items-center gap-3">
            <a href="/profile/{{ $tableCode }}" class="p-1 hover:bg-white/10 rounded-full transition-colors text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="font-semibold text-base leading-tight truncate">Kebijakan Privasi</h1>
                <p class="text-xs text-brand-100 truncate mt-0.5">GoKasir — QR Menu Ordering</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm border border-white/30 px-3 py-1 rounded-full text-xs font-semibold">
                Legal
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 overflow-y-auto p-5 space-y-5 bg-slate-50 min-h-[calc(100vh-73px)]">

        <!-- Welcome Banner -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm space-y-3 relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-6xl opacity-10">🛡️</div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">🔐</span>
                <h2 class="font-bold text-slate-800 text-sm">Keamanan Anda Prioritas Kami</h2>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                Kami berkomitmen penuh untuk melindungi privasi data Anda. Halaman ini menjelaskan informasi apa saja yang dikumpulkan dan bagaimana informasi tersebut dipergunakan untuk menunjang kenyamanan bersantap Anda di restoran.
            </p>
            <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider border-t border-slate-50 pt-2.5">
                Terakhir Diperbarui: 27 Mei 2026
            </div>
        </div>

        <!-- Accordion Section (AlpineJS Dynamic Interactive Sections) -->
        <div class="space-y-3" x-data="{ activeSection: 1 }">

            <!-- Card 1: Pengumpulan Data -->
            <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm transition-all duration-300">
                <button @click="activeSection = (activeSection === 1 ? 0 : 1)"
                    class="w-full flex items-center justify-between p-4 font-bold text-xs text-slate-700 hover:bg-slate-50 transition-colors uppercase tracking-wider text-left">
                    <span class="flex items-center gap-2">
                        <span class="text-sm">📝</span>
                        <span>1. Pengumpulan Data</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-300"
                        :class="activeSection === 1 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="activeSection === 1" x-collapse x-cloak
                    class="px-4 pb-5 pt-1 text-xs text-slate-500 leading-relaxed border-t border-slate-50 space-y-2">
                    <p>Saat Anda memindai kode QR meja restoran dan mengakses GoKasir, kami mengumpulkan data terbatas untuk memproses pesanan:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li><strong>Identitas Pemesan:</strong> Nama lengkap yang Anda masukkan untuk membantu pelayan mengantarkan hidangan ke meja Anda.</li>
                        <li><strong>Kontak:</strong> Nomor handphone Anda (hanya jika Anda menggunakan metode pembayaran non-tunai / cashless) untuk mengirimkan nomor pembayaran atau VA.</li>
                        <li><strong>Data Sesi Meja:</strong> Informasi nomor meja restoran tempat Anda memindai QR code.</li>
                        <li><strong>Keranjang Belanja:</strong> Daftar item menu makanan/minuman yang Anda masukkan ke dalam keranjang.</li>
                    </ul>
                </div>
            </div>

            <!-- Card 2: Penggunaan Informasi -->
            <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm transition-all duration-300">
                <button @click="activeSection = (activeSection === 2 ? 0 : 2)"
                    class="w-full flex items-center justify-between p-4 font-bold text-xs text-slate-700 hover:bg-slate-50 transition-colors uppercase tracking-wider text-left">
                    <span class="flex items-center gap-2">
                        <span class="text-sm">⚙️</span>
                        <span>2. Penggunaan Informasi</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-300"
                        :class="activeSection === 2 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="activeSection === 2" x-collapse x-cloak
                    class="px-4 pb-5 pt-1 text-xs text-slate-500 leading-relaxed border-t border-slate-50 space-y-2">
                    <p>Data yang dikumpulkan diolah untuk keperluan berikut:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>Mengirimkan pesanan meja secara real-time ke aplikasi kasir dan pencetak nota dapur restoran.</li>
                        <li>Menghitung pajak penjualan dan grand total secara transparan sesuai kebijakan kasir.</li>
                        <li>Melakukan verifikasi pembayaran cashless instan (QRIS / VA) melalui mitra payment gateway resmi kami.</li>
                        <li>Memantau status hidangan (Sedang Diproses/Selesai) dari panel dapur.</li>
                    </ul>
                </div>
            </div>

            <!-- Card 3: Cookies & Penyimpanan Lokal -->
            <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm transition-all duration-300">
                <button @click="activeSection = (activeSection === 3 ? 0 : 3)"
                    class="w-full flex items-center justify-between p-4 font-bold text-xs text-slate-700 hover:bg-slate-50 transition-colors uppercase tracking-wider text-left">
                    <span class="flex items-center gap-2">
                        <span class="text-sm">💾</span>
                        <span>3. Penyimpanan Browser (LocalStorage)</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-300"
                        :class="activeSection === 3 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="activeSection === 3" x-collapse x-cloak
                    class="px-4 pb-5 pt-1 text-xs text-slate-500 leading-relaxed border-t border-slate-50 space-y-2">
                    <p>
                        Sistem kami bekerja secara instan tanpa login akun. Oleh karena itu, kami menggunakan teknologi penyimpanan browser lokal (<strong>LocalStorage</strong>) pada perangkat Anda untuk menyimpan:
                    </p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>`gokasir_cart`: Caching sementara daftar belanja agar keranjang tidak hilang saat Anda tidak sengaja me-refresh halaman.</li>
                        <li>`session_token_${tableCode}`: Token otentikasi unik sesi meja Anda untuk menampilkan kembali pesanan yang sedang aktif.</li>
                        <li>`customer_name_${tableCode}` & `customer_phone_${tableCode}`: Mengingat data nama Anda agar tidak perlu menulis ulang di pesanan berikutnya.</li>
                    </ul>
                    <p class="text-[10px] text-amber-600 bg-amber-50 p-2.5 rounded-xl border border-amber-100 font-semibold mt-2.5">
                        ⚠️ Catatan: Menghapus data/history browser Anda akan mengosongkan LocalStorage dan menghapus riwayat pesanan serta keranjang aktif dari perangkat Anda.
                    </p>
                </div>
            </div>

            <!-- Card 4: Keterbukaan Pihak Ketiga -->
            <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm transition-all duration-300">
                <button @click="activeSection = (activeSection === 4 ? 0 : 4)"
                    class="w-full flex items-center justify-between p-4 font-bold text-xs text-slate-700 hover:bg-slate-50 transition-colors uppercase tracking-wider text-left">
                    <span class="flex items-center gap-2">
                        <span class="text-sm">🤝</span>
                        <span>4. Keterbukaan Pihak Ketiga</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-300"
                        :class="activeSection === 4 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="activeSection === 4" x-collapse x-cloak
                    class="px-4 pb-5 pt-1 text-xs text-slate-500 leading-relaxed border-t border-slate-50 space-y-2">
                    <p>
                        Kami **tidak pernah menjual atau memperdagangkan** informasi pribadi Anda kepada pihak lain. Kami hanya membagikan data transaksi secara terenkripsi kepada mitra penyedia layanan kami (seperti Payment Gateway IPaymu) yang membantu kami memverifikasi pembayaran Anda demi keamanan transaksi.
                    </p>
                </div>
            </div>

        </div>

        <!-- Contact Support Card -->
        <div class="bg-slate-100 border border-slate-200/50 rounded-2xl p-4 text-center space-y-3 shadow-inner">
            <span class="text-2xl block">💬</span>
            <div class="space-y-0.5">
                <h4 class="font-bold text-xs text-slate-700">Ada Pertanyaan Mengenai Legalitas?</h4>
                <p class="text-[10px] text-slate-400 max-w-[220px] mx-auto leading-relaxed">
                    Staf admin dan dukungan teknis kami siap menjawab keluhan privasi Anda secara cepat.
                </p>
            </div>
            <div class="pt-1">
                <a href="mailto:support@gokasir.id"
                    class="inline-flex bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-[10px] px-4 py-2.5 rounded-xl transition-all shadow-sm active:scale-95 items-center gap-1.5">
                    <span>Hubungi Support</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Action Button Got it -->
        <div class="pt-2">
            <a href="/profile/{{ $tableCode }}"
                class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary-hover active:scale-98 transition-all text-sm flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Saya Mengerti</span>
            </a>
        </div>

    </main>
@endsection
