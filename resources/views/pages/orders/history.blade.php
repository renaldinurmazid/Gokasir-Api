@extends('layouts.order')

@section('title', 'GoKasir - Riwayat Pesanan')

@section('content')
    <!-- Back Bar -->
    <header class="h-[52px] bg-white border-b border-slate-100 shadow-sm sticky top-0 z-50 px-4 flex items-center gap-3">
        <a href="/profile/{{ $tableCode }}" class="p-1 hover:bg-slate-50 rounded-full transition-colors text-slate-700 -ml-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="font-bold text-sm text-slate-800 tracking-wide uppercase">Riwayat Pesanan</h1>
    </header>

    <!-- Scrollable container -->
    <main class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50 min-h-[calc(100vh-52px)] pb-10">

        <!-- Interactive Filter Chips Row (Section 9.5) -->
        <div id="filter-chips-bar" class="hidden flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
            <button onclick="filterOrders('all')" id="chip-all" class="h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider bg-brand-500 text-white shadow-sm shadow-primary-hover flex-shrink-0 transition-all outline-none">
                Semua
            </button>
            <button onclick="filterOrders('active')" id="chip-active" class="h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 hover:bg-slate-200 flex-shrink-0 transition-all outline-none">
                Aktif
            </button>
            <button onclick="filterOrders('completed')" id="chip-completed" class="h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 hover:bg-slate-200 flex-shrink-0 transition-all outline-none">
                Selesai
            </button>
            <button onclick="filterOrders('cancelled')" id="chip-cancelled" class="h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 hover:bg-slate-200 flex-shrink-0 transition-all outline-none">
                Batal
            </button>
        </div>

        <!-- Empty State (Section 9.5) -->
        <div id="empty-history" class="hidden text-center py-20 space-y-4">
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/><path d="M9 8h6"/></svg>
            </div>
            <div class="space-y-1">
                <h3 class="font-bold text-slate-700 text-sm">Belum Ada Pesanan</h3>
                <p class="text-xs text-slate-400 max-w-[240px] mx-auto leading-normal">
                    Yuk, pilih hidangan makanan atau minuman segar favorit Anda sekarang!
                </p>
            </div>
            <div class="pt-2">
                <a href="/order/{{ $tableCode }}"
                    class="inline-flex bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs px-6 py-3 rounded-xl shadow-md shadow-primary-hover transition-all active:scale-95 items-center gap-1.5">
                    <span>Mulai Pesan</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- History cards list -->
        <div id="history-container" class="hidden space-y-4">
            <!-- Dynamic order card lists -->
        </div>

        <!-- Loader Spinner -->
        <div id="history-loader" class="flex flex-col items-center justify-center py-20 space-y-3">
            <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Memuat Transaksi...</span>
        </div>

    </main>

    <!-- Scripting for History loading & filtering -->
    <script>
        const tableCode = "{{ $tableCode }}";
        const publicApiUrl = `/api/public`;
        
        let loadedOrders = [];
        let activeFilter = 'all';

        document.addEventListener('DOMContentLoaded', async () => {
            await renderHistory();
        });

        // Main orders database fetcher
        async function renderHistory() {
            const loader = document.getElementById('history-loader');
            const emptyState = document.getElementById('empty-history');
            const container = document.getElementById('history-container');
            const filterBar = document.getElementById('filter-chips-bar');

            try {
                const sessionToken = localStorage.getItem(`session_token_${tableCode}`);

                if (!sessionToken) {
                    loader.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    container.classList.add('hidden');
                    filterBar.classList.add('hidden');
                    return;
                }

                // Fetch from database using standard endpoint
                const response = await fetch(`${publicApiUrl}/order/${tableCode}/history?session_token=${sessionToken}`);
                const result = await response.json();

                if (!result.success || !result.data || result.data.length === 0) {
                    loader.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    container.classList.add('hidden');
                    filterBar.classList.add('hidden');
                    return;
                }

                loadedOrders = result.data;

                loader.classList.add('hidden');
                emptyState.classList.add('hidden');
                container.classList.remove('hidden');
                filterBar.classList.remove('hidden');

                // Render current filtered list
                applyFilterRender();

            } catch (err) {
                console.error('History fetch failed:', err);
                loader.classList.add('hidden');
                emptyState.classList.remove('hidden');
                container.classList.add('hidden');
                filterBar.classList.add('hidden');
            }
        }

        // Apply filter logic and redraw cards
        function filterOrders(filterType) {
            activeFilter = filterType;
            
            // Toggle active styles on chips
            const chips = ['all', 'active', 'completed', 'cancelled'];
            chips.forEach(item => {
                const button = document.getElementById(`chip-${item}`);
                if (button) {
                    if (item === filterType) {
                        button.className = 'h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider bg-brand-500 text-white shadow-sm shadow-primary-hover flex-shrink-0 transition-all outline-none';
                    } else {
                        button.className = 'h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 hover:bg-slate-200 flex-shrink-0 transition-all outline-none';
                    }
                }
            });

            applyFilterRender();
        }

        // Compile and draw filtered lists
        function applyFilterRender() {
            const container = document.getElementById('history-container');
            container.innerHTML = '';

            let filteredList = [];
            if (activeFilter === 'all') {
                filteredList = loadedOrders;
            } else if (activeFilter === 'active') {
                filteredList = loadedOrders.filter(o => o.status === 'pending' || o.status === 'confirmed');
            } else if (activeFilter === 'completed') {
                filteredList = loadedOrders.filter(o => o.status === 'paid');
            } else if (activeFilter === 'cancelled') {
                filteredList = loadedOrders.filter(o => o.status === 'cancelled');
            }

            if (filteredList.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-16 space-y-3 bg-white border border-slate-100 rounded-2xl p-6 shadow-xs">
                        <span class="text-3xl block">📋</span>
                        <h4 class="font-bold text-slate-700 text-xs">Tidak ada pesanan di kategori ini</h4>
                    </div>
                `;
                return;
            }

            filteredList.forEach(item => {
                const card = document.createElement('div');
                card.className = 'bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow duration-300 space-y-4';

                let displayDate = 'Baru saja';
                try {
                    const dateObj = new Date(item.date);
                    displayDate = dateObj.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    }) + ' — ' + dateObj.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    console.error(e);
                }

                // Status mapping to design system Section 8.8
                let badgeText = 'MENUNGGU';
                let badgeClass = 'text-[9px] font-extrabold px-2.5 py-1 rounded-lg bg-brand-50 text-brand-500 uppercase tracking-wider border border-brand-100';

                if (item.status === 'pending') {
                    if (item.payment_type === 'cashless' && (item.payment_status === 'pending_payment' || item.payment_status === 'unpaid')) {
                        badgeText = 'BELUM BAYAR';
                        badgeClass = 'text-[9px] font-extrabold px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 uppercase tracking-wider border border-amber-100';
                    } else {
                        badgeText = 'MENUNGGU';
                        badgeClass = 'text-[9px] font-extrabold px-2.5 py-1 rounded-lg bg-brand-50 text-brand-500 uppercase tracking-wider border border-brand-100';
                    }
                } else if (item.status === 'confirmed') {
                    badgeText = 'DIPROSES';
                    badgeClass = 'text-[9px] font-extrabold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 uppercase tracking-wider border border-blue-100';
                } else if (item.status === 'paid') {
                    badgeText = 'SELESAI';
                    badgeClass = 'text-[9px] font-extrabold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 uppercase tracking-wider border border-emerald-100';
                } else if (item.status === 'cancelled') {
                    badgeText = 'BATAL';
                    badgeClass = 'text-[9px] font-extrabold px-2.5 py-1 rounded-lg bg-rose-50 text-rose-500 uppercase tracking-wider border border-rose-100';
                }

                const itemsPreview = item.items.map(i => `${i.product_name} (${parseFloat(i.qty).toLocaleString('id-ID')}x)`).join(', ');

                card.innerHTML = `
                    <div class="flex items-center justify-between border-b border-slate-50 pb-3">
                        <div class="space-y-0.5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">No. Pesanan</span>
                            <span class="font-mono font-bold text-xs text-slate-700 block">${item.order_number}</span>
                        </div>
                        <span class="${badgeClass}">
                            ${badgeText}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100/50 shadow-inner">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Daftar Menu</span>
                            <p class="text-xs text-slate-600 font-semibold line-clamp-2 leading-relaxed">
                                ${itemsPreview || 'Menu'}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="space-y-0.5">
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Tanggal & Waktu</span>
                                <span class="text-slate-600 font-medium block leading-snug">${displayDate}</span>
                            </div>
                            <div class="text-right space-y-0.5">
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Total Transaksi</span>
                                <span class="text-brand-500 font-extrabold text-sm block leading-none">${formatRupiah(item.grand_total)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-2 border-t border-slate-50/50">
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-semibold">
                            <span class="text-xs">${item.payment_type === 'cashless' ? '📱' : '💵'}</span>
                            <span class="capitalize tracking-wide">${item.payment_type === 'cashless' ? 'Bayar Cashless' : 'Bayar di Kasir'}</span>
                        </div>
                        <a href="/order/${tableCode}/status/${item.order_number}" 
                           class="text-[10px] font-bold text-brand-500 bg-brand-50 hover:bg-brand-100 hover:text-brand-600 px-3.5 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1 active:scale-95">
                            <span>Lacak Detail</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                `;

                container.appendChild(card);
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
