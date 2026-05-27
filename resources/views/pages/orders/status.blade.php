@extends('layouts.order')

@section('title', 'GoKasir - Status Pesanan')

@section('content')
    <!-- Back Bar -->
    <header class="h-[52px] bg-white border-b border-slate-100 shadow-sm sticky top-0 z-50 px-4 flex items-center gap-3">
        <a href="/order/{{ $tableCode }}" class="p-1 hover:bg-slate-50 rounded-full transition-colors text-slate-700 -ml-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="text-md font-[600] text-slate-900 tracking-wide">Status Pesanan</h1>
    </header>

    <!-- Scrollable container -->
    <main class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50 pb-20">

        <!-- Order Info Card -->
        <div class="bg-brand-50 border border-brand-100 rounded-2xl p-4 shadow-xs relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-5xl opacity-10 pointer-events-none">📋</div>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs text-slate-500 font-medium uppercase tracking-wider block">No. Pesanan</span>
                    <span class="font-mono font-bold text-lg text-brand-500 block">#{{ $orderNumber }}</span>
                </div>
                <div class="bg-[#DBEAFE] text-[#2563EB] border border-blue-100 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wide">
                    Meja {{ $table->name }}
                </div>
            </div>
        </div>

        <!-- Live Status Message Card -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center space-y-3">
            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Status Saat Ini</span>
            <span id="status-badge" class="inline-block text-[10px] font-extrabold px-3 py-1 rounded-full bg-slate-100 text-slate-400 uppercase tracking-wider animate-pulse border border-slate-200">
                MEMUAT...
            </span>
            <p id="status-message" class="text-xs text-slate-500 leading-relaxed px-2 font-medium">
                Sedang menghubungkan ke server dapur untuk memantau pesanan Anda...
            </p>
        </div>

        <!-- Vertical Timeline Tracker Component -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm space-y-5">
            <h3 class="font-bold text-[10px] text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-50">Timeline Pesanan</h3>
            
            <div class="relative flex flex-col gap-6 pl-1 pt-1">
                
                <!-- Step 1: Diterima -->
                <div class="flex items-start gap-4 relative">
                    <!-- Connector line down -->
                    <div id="line-step-1" class="absolute left-[11px] top-6 h-[calc(100%+8px)] transition-all duration-300"></div>
                    
                    <div id="dot-step-1" class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300">
                        1
                    </div>
                    <div class="space-y-0.5">
                        <h4 id="title-step-1" class="text-xs font-[600]">Pesanan Diterima</h4>
                        <p id="time-step-1" class="text-[10px] text-slate-500 font-normal"></p>
                    </div>
                </div>

                <!-- Step 2: Diproses -->
                <div class="flex items-start gap-4 relative">
                    <!-- Connector line down -->
                    <div id="line-step-2" class="absolute left-[11px] top-6 h-[calc(100%+8px)] transition-all duration-300"></div>
                    
                    <div id="dot-step-2" class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300">
                        2
                    </div>
                    <div class="space-y-0.5">
                        <h4 id="title-step-2" class="text-xs font-[600]">Sedang Diproses</h4>
                        <p id="time-step-2" class="text-[10px] text-slate-500 font-normal"></p>
                    </div>
                </div>

                <!-- Step 3: Siap Diambil -->
                <div class="flex items-start gap-4 relative">
                    <!-- Connector line down -->
                    <div id="line-step-3" class="absolute left-[11px] top-6 h-[calc(100%+8px)] transition-all duration-300"></div>
                    
                    <div id="dot-step-3" class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300">
                        3
                    </div>
                    <div class="space-y-0.5">
                        <h4 id="title-step-3" class="text-xs font-[600]">Siap Diambil</h4>
                        <p id="time-step-3" class="text-[10px] text-slate-500 font-normal"></p>
                    </div>
                </div>

                <!-- Step 4: Selesai -->
                <div class="flex items-start gap-4">
                    <div id="dot-step-4" class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300">
                        4
                    </div>
                    <div class="space-y-0.5">
                        <h4 id="title-step-4" class="text-xs font-[600]">Pesanan Selesai</h4>
                        <p id="time-step-4" class="text-[10px] text-slate-500 font-normal"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estimasi Waktu Penyiapan Card (Section 9.4) -->
        <div id="estimation-card" class="bg-[#FEF3C7] border border-amber-100 rounded-2xl p-4 shadow-xs flex items-center gap-3 text-[#D97706] font-semibold text-xs transition-all duration-300">
            <span class="text-base flex-shrink-0">⏱️</span>
            <span>Estimasi siap dalam ~10–15 menit</span>
        </div>

        <!-- Payment Details Card (For Cashless Pending VA or QRIS) -->
        <div id="payment-details-card" class="hidden bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">Instruksi Pembayaran</span>
                <span id="payment-method-badge" class="text-[9px] font-bold px-2.5 py-0.5 rounded bg-brand-500 text-white uppercase tracking-wider">
                    -
                </span>
            </div>

            <div class="p-4 space-y-4 text-center">
                <!-- QRIS Section -->
                <div id="qris-payment-area" class="hidden flex flex-col items-center space-y-3">
                    <div class="bg-white border-2 border-slate-100 p-2.5 rounded-2xl shadow-xs inline-block relative overflow-hidden" id="qris-img-container">
                        <!-- QR code dynamically injected here -->
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed max-w-[220px]">
                        Pindai kode QRIS di atas dengan aplikasi bank (BCA, Mandiri, BRI, dll) atau e-wallet (Gopay, OVO, Dana, LinkAja).
                    </p>
                </div>

                <!-- VA Details Section -->
                <div id="va-payment-area" class="hidden space-y-3.5">
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 flex flex-col items-center justify-center relative shadow-inner">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Nomor Virtual Account</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span id="va-number" class="text-base font-extrabold text-slate-800 tracking-wider">-</span>
                            <button onclick="copyToClipboard()" class="p-1 hover:bg-slate-200/50 active:scale-95 rounded-md text-brand-500 transition-all flex items-center justify-center" title="Salin nomor VA" id="copy-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </button>
                        </div>
                        <span id="toast-salin" class="absolute -bottom-3.5 bg-slate-800 text-white text-[9px] font-semibold px-2 py-0.5 rounded shadowopacity-0 transition-opacity duration-200">
                            Tersalin!
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-left">
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 shadow-xs">
                            <span class="text-[9px] text-slate-400 block font-bold uppercase tracking-wider">Nama Bank</span>
                            <span id="va-bank" class="text-xs font-bold text-slate-700 mt-0.5 block">-</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 shadow-xs">
                            <span class="text-[9px] text-slate-400 block font-bold uppercase tracking-wider">Biaya Layanan</span>
                            <span id="va-fee" class="text-xs font-bold text-slate-700 mt-0.5 block">-</span>
                        </div>
                    </div>
                </div>

                <div class="bg-brand-50 border border-brand-100 text-brand-700 rounded-xl px-4 py-2 text-[10px] font-bold flex items-center justify-center gap-1.5 shadow-xs inline-flex">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                    <span>Bayar sebelum waktu kedaluwarsa</span>
                </div>
            </div>
        </div>

        <!-- Ordered Items Summary list -->
        <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-slate-50 px-4 py-3 border-b border-slate-100">
                <h4 class="font-bold text-[10px] text-slate-700 uppercase tracking-wider">Rincian Belanja</h4>
            </div>

            <div class="divide-y divide-slate-50 px-4" id="order-items-list">
                <div class="py-4 text-center text-slate-400 text-xs font-medium">Memuat rincian pesanan...</div>
            </div>

            <div class="bg-slate-50/50 p-4 flex items-center justify-between border-t border-slate-100 text-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Transaksi</span>
                <span id="order-grand-total" class="font-extrabold text-sm text-brand-500">Rp0</span>
            </div>
        </div>

        <!-- CTA Order additionals -->
        <div class="pt-2 text-center space-y-4">
            <a href="/order/{{ $tableCode }}"
                class="w-full bg-transparent hover:bg-brand-50 border-2 border-brand-500 text-brand-500 font-bold py-3.5 rounded-xl transition-all shadow-xs text-xs flex items-center justify-center gap-1.5 active:scale-98">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Pesan Menu Tambahan</span>
            </a>
            
            <!-- Destructive Ghost Cancel Button (Section 9.4) -->
            <div id="cancel-order-container" class="hidden">
                <a href="/order/{{ $tableCode }}/cancel/{{ $orderNumber }}"
                    class="w-full bg-transparent hover:bg-rose-50 text-[#DC2626] font-bold py-3.5 rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 active:scale-98">
                    <span>Batalkan Pesanan</span>
                </a>
            </div>

            <p class="text-[9px] text-slate-400 leading-relaxed px-4">
                Ingin menambah hidangan atau minuman penyegar? Anda dapat memesan kembali tanpa memutus sesi meja aktif Anda.
            </p>
        </div>

    </main>

    <!-- Polling & Rendering Script -->
    <script>
        const tableCode = "{{ $tableCode }}";
        const orderNumber = "{{ $orderNumber }}";
        const publicApiUrl = `/api/public`;

        let pollingInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            fetchOrderStatus();
            pollingInterval = setInterval(fetchOrderStatus, 5000);
        });

        window.addEventListener('beforeunload', () => {
            if (pollingInterval) clearInterval(pollingInterval);
        });

        async function fetchOrderStatus() {
            try {
                const response = await fetch(`${publicApiUrl}/order/${tableCode}/status/${orderNumber}`);
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    updateUI(data);

                    // Self-healing: save order to history if not exists in localStorage
                    try {
                        const historyList = JSON.parse(localStorage.getItem('gokasir_order_history') || '[]');
                        if (!historyList.some(item => item.order_number === orderNumber)) {
                            historyList.push({
                                order_number: orderNumber,
                                table_code: tableCode,
                                grand_total: data.grand_total,
                                payment_type: data.payment_type,
                                date: new Date().toISOString()
                            });
                            localStorage.setItem('gokasir_order_history', JSON.stringify(historyList));
                        }
                    } catch (e) {
                        console.error('Gagal menyinkronkan riwayat pesanan:', e);
                    }

                    if (data.status === 'paid' || data.status === 'cancelled') {
                        if (pollingInterval) {
                            clearInterval(pollingInterval);
                            pollingInterval = null;
                        }
                    }
                }
            } catch (err) {
                console.error('Error fetching status:', err);
            }
        }

        function copyToClipboard() {
            const vaNum = document.getElementById('va-number').innerText;
            navigator.clipboard.writeText(vaNum).then(() => {
                const toast = document.getElementById('toast-salin');
                toast.classList.remove('opacity-0');
                setTimeout(() => {
                    toast.classList.add('opacity-0');
                }, 2000);
            }).catch(err => {
                console.error('Gagal menyalin:', err);
            });
        }

        // Stepper & Badge details UI compiler
        function updateUI(order) {
            const statusBadge = document.getElementById('status-badge');
            const statusMessage = document.getElementById('status-message');

            // 1. Badge status compilations
            if (order.status === 'pending') {
                if (order.payment_type === 'cashless' && (order.payment_status === 'pending_payment' || order.payment_status === 'unpaid')) {
                    statusBadge.className = 'inline-block text-[9px] font-extrabold px-3 py-1 rounded-lg bg-warning-100 text-warning-500 border border-warning-500/20 uppercase tracking-wider';
                    statusBadge.innerText = '⏳ MENUNGGU PEMBAYARAN';
                    statusMessage.innerText = 'Silakan selesaikan pembayaran cashless Anda menggunakan QRIS atau Virtual Account di bawah.';
                } else {
                    statusBadge.className = 'inline-block text-[9px] font-extrabold px-3 py-1 rounded-lg bg-warning-100 text-warning-500 border border-warning-500/20 uppercase tracking-wider animate-pulse';
                    statusBadge.innerText = '⏳ MENUNGGU KONFIRMASI';
                    statusMessage.innerText = 'Pesanan Anda telah diterima sistem kasir. Menunggu persetujuan kasir restoran.';
                }
            } else if (order.status === 'confirmed') {
                statusBadge.className = 'inline-block text-[9px] font-extrabold px-3 py-1 rounded-lg bg-info-100 text-info-500 border border-info-500/20 uppercase tracking-wider animate-pulse';
                statusBadge.innerText = '🍳 SEDANG DIPROSES';
                statusMessage.innerText = 'Hore! Pesanan Anda telah dikonfirmasi dan sedang diracik oleh juru masak dapur kami.';
            } else if (order.status === 'paid') {
                statusBadge.className = 'inline-block text-[9px] font-extrabold px-3 py-1 rounded-lg bg-success-100 text-success-500 border border-success-500/20 uppercase tracking-wider';
                statusBadge.innerText = '✓ PESANAN SELESAI';
                statusMessage.innerText = 'Pembayaran tuntas! Pesanan Anda telah selesai diproses. Selamat menikmati hidangan!';
            } else if (order.status === 'cancelled') {
                statusBadge.className = 'inline-block text-[9px] font-extrabold px-3 py-1 rounded-lg bg-danger-100 text-danger-500 border border-danger-500/20 uppercase tracking-wider';
                statusBadge.innerText = '✗ DIBATALKAN';
                statusMessage.innerText = 'Mohon maaf, pesanan ini telah dibatalkan oleh pihak kasir atau pelayan restoran.';
            }

            // 2. Timeline steps updates (Section 12)
            const dot1 = document.getElementById('dot-step-1');
            const dot2 = document.getElementById('dot-step-2');
            const dot3 = document.getElementById('dot-step-3');
            const dot4 = document.getElementById('dot-step-4');

            const line1 = document.getElementById('line-step-1');
            const line2 = document.getElementById('line-step-2');
            const line3 = document.getElementById('line-step-3');

            const title1 = document.getElementById('title-step-1');
            const title2 = document.getElementById('title-step-2');
            const title3 = document.getElementById('title-step-3');
            const title4 = document.getElementById('title-step-4');

            const checkSvg = `<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`;
            const activeDot = `<span class="w-2 h-2 rounded-full bg-white"></span>`;

            // Reset values
            dot1.innerHTML = '1';
            dot2.innerHTML = '2';
            dot3.innerHTML = '3';
            dot4.innerHTML = '4';

            title4.innerText = 'Pesanan Selesai';

            dot1.className = 'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300 ';
            dot2.className = 'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300 ';
            dot3.className = 'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300 ';
            dot4.className = 'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 font-bold text-[11px] transition-all duration-300 ';

            line1.className = 'absolute left-[11px] top-6 h-[calc(100%+8px)] transition-all duration-300 ';
            line2.className = 'absolute left-[11px] top-6 h-[calc(100%+8px)] transition-all duration-300 ';
            line3.className = 'absolute left-[11px] top-6 h-[calc(100%+8px)] transition-all duration-300 ';

            title1.className = 'text-xs font-[600] transition-colors duration-300 ';
            title2.className = 'text-xs font-[600] transition-colors duration-300 ';
            title3.className = 'text-xs font-[600] transition-colors duration-300 ';
            title4.className = 'text-xs font-[600] transition-colors duration-300 ';

            if (order.status === 'pending') {
                dot1.className += 'bg-brand-500 text-white border border-brand-500 pulse-effect';
                dot1.innerHTML = activeDot;
                title1.className += 'text-brand-500';

                dot2.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title2.className += 'text-slate-400';
                line1.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot3.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title3.className += 'text-slate-400';
                line2.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot4.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title4.className += 'text-slate-400';
                line3.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

            } else if (order.status === 'confirmed') {
                dot1.className += 'bg-success-500 text-white border border-success-500 shadow-xs';
                dot1.innerHTML = checkSvg;
                title1.className += 'text-slate-900';
                line1.className += 'w-[2px] bg-success-500';

                dot2.className += 'bg-brand-500 text-white border border-brand-500 pulse-effect';
                dot2.innerHTML = activeDot;
                title2.className += 'text-brand-500';
                line2.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot3.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title3.className += 'text-slate-400';
                line3.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot4.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title4.className += 'text-slate-400';

            } else if (order.status === 'paid') {
                dot1.className += 'bg-success-500 text-white border border-success-500 shadow-xs';
                dot1.innerHTML = checkSvg;
                title1.className += 'text-slate-900';
                line1.className += 'w-[2px] bg-success-500';

                dot2.className += 'bg-success-500 text-white border border-success-500 shadow-xs';
                dot2.innerHTML = checkSvg;
                title2.className += 'text-slate-900';
                line2.className += 'w-[2px] bg-success-500';

                dot3.className += 'bg-success-500 text-white border border-success-500 shadow-xs';
                dot3.innerHTML = checkSvg;
                title3.className += 'text-slate-900';
                line3.className += 'w-[2px] bg-success-500';

                dot4.className += 'bg-success-500 text-white border border-success-500 shadow-xs';
                dot4.innerHTML = checkSvg;
                title4.className += 'text-success-500';

            } else if (order.status === 'cancelled') {
                dot1.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title1.className += 'text-slate-400';
                line1.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot2.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title2.className += 'text-slate-400';
                line2.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot3.className += 'border-2 border-slate-200 bg-white text-slate-400';
                title3.className += 'text-slate-400';
                line3.className += 'w-0 border-l-2 border-dashed border-slate-300 bg-transparent';

                dot4.className += 'bg-danger-500 text-white border border-danger-500';
                dot4.innerHTML = `<span class="text-[9px]">✗</span>`;
                title4.className += 'text-danger-500';
                title4.innerText = 'Pesanan Dibatalkan';
            }

            // 3. Payment details info
            const paymentCard = document.getElementById('payment-details-card');
            if (order.payment_type === 'cashless' && order.payment_status !== 'paid' && order.status !== 'cancelled') {
                paymentCard.classList.remove('hidden');

                document.getElementById('payment-method-badge').innerText = `${order.payment_name || 'Cashless'}`;

                const qrisArea = document.getElementById('qris-payment-area');
                const vaArea = document.getElementById('va-payment-area');

                const isQris = (order.payment_method && order.payment_method.toLowerCase().includes('qris')) ||
                    (order.payment_channel && order.payment_channel.toLowerCase().includes('qris')) ||
                    (order.payment_name && order.payment_name.toLowerCase().includes('qris')) ||
                    (order.payment_url && order.payment_url.toLowerCase().includes('qris')) ||
                    (order.payment_no && order.payment_no.startsWith('000201'));

                if (isQris) {
                    qrisArea.classList.remove('hidden');
                    vaArea.classList.add('hidden');

                    const qrisContainer = document.getElementById('qris-img-container');
                    if (order.payment_no && order.payment_no.startsWith('000201')) {
                        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(order.payment_no)}`;
                        qrisContainer.innerHTML = `<img src="${qrUrl}" alt="QRIS QR Code" class="w-44 h-44 object-contain" />`;
                    } else if (order.payment_url && order.payment_url.startsWith('data:image')) {
                        qrisContainer.innerHTML = `<img src="${order.payment_url}" alt="QRIS QR Code" class="w-44 h-44 object-contain" />`;
                    } else {
                        qrisContainer.innerHTML = `
                            <div class="w-44 h-44 flex flex-col items-center justify-center bg-slate-50 border border-dashed border-slate-200 rounded-xl p-3">
                                <span class="text-3xl mb-1">📱</span>
                                <span class="text-center text-[10px] text-slate-500 font-semibold leading-normal">
                                    Silakan selesaikan pembayaran QRIS via aplikasi utama Anda.
                                </span>
                            </div>
                        `;
                    }
                } else {
                    qrisArea.classList.add('hidden');
                    vaArea.classList.remove('hidden');

                    document.getElementById('va-number').innerText = order.payment_no || '-';
                    document.getElementById('va-bank').innerText = order.payment_name || '-';
                    document.getElementById('va-fee').innerText = order.payment_fee > 0 ? formatRupiah(order.payment_fee) : 'Gratis';
                }
            } else {
                paymentCard.classList.add('hidden');
            }

            // Show/hide cancel button (only during pending status)
            const cancelContainer = document.getElementById('cancel-order-container');
            if (order.status === 'pending') {
                cancelContainer.classList.remove('hidden');
            } else {
                cancelContainer.classList.add('hidden');
            }

            // Show/hide estimation card (hide on paid/cancelled)
            const estCard = document.getElementById('estimation-card');
            if (order.status === 'paid' || order.status === 'cancelled') {
                estCard.classList.add('hidden');
            } else {
                estCard.classList.remove('hidden');
            }

            // 4. Update grand total
            document.getElementById('order-grand-total').innerText = formatRupiah(order.grand_total);

            // 5. Ordered menu items summary list
            const itemsList = document.getElementById('order-items-list');
            itemsList.innerHTML = '';

            order.items.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = 'py-3 flex items-start justify-between gap-4';
                itemEl.innerHTML = `
                    <div class="min-w-0 flex-1">
                        <span class="font-bold text-slate-800 text-xs block leading-tight truncate">
                            ${item.product_name}
                        </span>
                        ${item.notes ? `
                            <span class="text-[9px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded inline-block mt-1.5 truncate max-w-full">
                                ✎ ${item.notes}
                            </span>
                        ` : ''}
                        <span class="text-[10px] text-slate-400 block mt-1 font-semibold">
                            ${formatRupiah(item.price)} x ${parseFloat(item.qty).toLocaleString('id-ID')}
                        </span>
                    </div>
                    <span class="font-extrabold text-slate-750 text-xs whitespace-nowrap ml-2">
                        ${formatRupiah(item.subtotal)}
                    </span>
                `;
                itemsList.appendChild(itemEl);
            });
        }

        function formatRupiah(amount) {
            return 'Rp' + parseFloat(amount).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }
    </script>
@endsection
