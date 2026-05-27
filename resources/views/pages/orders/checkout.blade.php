@extends('layouts.order')

@section('title', 'GoKasir - Checkout')

@php
    $taxSetting = $table->store->tenant->getActiveTaxSetting();
    $taxRate = $taxSetting->tax_rate ?? 0;
    $taxEnabled = $taxSetting->tax_enabled ?? false;
    $taxName = $taxSetting->tax_name ?? 'Pajak';
@endphp

@section('content')
    <!-- Back Bar -->
    <header class="h-[52px] bg-white border-b border-slate-100 shadow-sm sticky top-0 z-50 px-4 flex items-center gap-3">
        <a href="/order/{{ $tableCode }}" class="p-1 hover:bg-slate-50 rounded-full transition-colors text-slate-700 -ml-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="text-md font-[600] text-slate-900 tracking-wide">Keranjang</h1>
    </header>

    <!-- Main Container Body -->
    <div class="flex-1 overflow-y-auto pb-24 bg-slate-50">
        
        <!-- Empty State Container -->
        <div id="empty-state" class="hidden text-center py-20 space-y-4 px-6">
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            </div>
            <div class="space-y-1">
                <h3 class="font-bold text-slate-700 text-sm">Keranjang Belanja Kosong</h3>
                <p class="text-xs text-slate-400 max-w-[240px] mx-auto leading-normal">
                    Silakan pilih menu masakan atau minuman lezat terlebih dahulu sebelum checkout.
                </p>
            </div>
            <div class="pt-2">
                <a href="/order/{{ $tableCode }}"
                    class="inline-flex bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs px-6 py-3 rounded-xl shadow-md shadow-primary-hover active:scale-95 transition-all">
                    Kembali ke Menu
                </a>
            </div>
        </div>

        <!-- Checkout Action Panel -->
        <div id="checkout-panel" class="hidden p-4 space-y-4">
            
            <!-- Table Info Alert Chip -->
            <div class="bg-[#DBEAFE] border border-blue-100 rounded-xl p-3 flex items-center gap-2 text-info-500 shadow-xs">
                <span class="text-sm">📍</span>
                <span class="text-sm font-[600]">Meja {{ $table->name }} • {{ $table->store->name }}</span>
            </div>

            <!-- Cart Items List Card -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm space-y-3.5">
                <h3 class="font-bold text-[10px] text-slate-400 uppercase tracking-wider">Item Pesanan</h3>
                <div id="cart-items" class="divide-y divide-slate-50">
                    <!-- Dynamically populated card rows -->
                </div>
            </div>

            <!-- Customer Registration Information Card -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm space-y-4">
                <h3 class="font-bold text-[10px] text-slate-400 uppercase tracking-wider">Informasi Pemesan</h3>
                <div class="space-y-4">
                    <div>
                        <label for="cust-name" class="block text-sm font-semibold text-slate-750 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="cust-name" required placeholder="Contoh: Budi Santoso"
                            class="w-full bg-slate-50 border border-slate-200 focus-brand-ring rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-400 transition-all outline-none" />
                    </div>
                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label for="cust-pax" class="block text-sm font-semibold text-slate-750 mb-1.5">Jumlah Orang</label>
                            <select id="cust-pax"
                                class="w-full bg-slate-50 border border-slate-200 focus-brand-ring rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 transition-all outline-none">
                                <option value="1">1 Orang</option>
                                <option value="2" selected>2 Orang</option>
                                <option value="3">3 Orang</option>
                                <option value="4">4 Orang</option>
                                <option value="5">5+ Orang</option>
                            </select>
                        </div>
                        <div>
                            <label for="cust-phone" class="block text-sm font-semibold text-slate-750 mb-1.5">No. Handphone</label>
                            <input type="tel" id="cust-phone" placeholder="08xxxxxxxx"
                                class="w-full bg-slate-50 border border-slate-200 focus-brand-ring rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-400 transition-all outline-none" />
                        </div>
                    </div>
                    <div>
                        <label for="cust-notes" class="block text-sm font-semibold text-slate-750 mb-1.5">Catatan (opsional)</label>
                        <textarea id="cust-notes" placeholder="Contoh: Sambal dipisah, tidak pakai daun bawang..." rows="3"
                            class="w-full bg-slate-50 border border-slate-200 focus-brand-ring rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-400 transition-all outline-none resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Card Selector -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm space-y-4">
                <h3 class="font-bold text-[10px] text-slate-400 uppercase tracking-wider">Metode Pembayaran</h3>
                
                <div class="grid grid-cols-2 gap-3.5">
                    <label id="pay-cash-label"
                        class="border border-brand-500 bg-brand-50 cursor-pointer rounded-xl p-3 flex flex-col gap-1.5 transition-all text-slate-700 relative overflow-hidden shadow-xs">
                        <input type="radio" name="payment_type" value="cash" checked
                            onchange="handlePaymentTypeChange('cash')" class="sr-only" />
                        <div id="pay-cash-dot"
                            class="w-4 h-4 rounded-full border-4 border-brand-500 bg-white flex items-center justify-center">
                        </div>
                        <span class="font-bold text-xs">Bayar di Kasir</span>
                        <span class="text-[9px] text-slate-400">Bayar Cash / Debit Fisik</span>
                    </label>
                    <label id="pay-cashless-label"
                        class="border border-slate-200 hover:border-slate-350 hover:bg-slate-50 cursor-pointer rounded-xl p-3 flex flex-col gap-1.5 transition-all text-slate-700 relative overflow-hidden">
                        <input type="radio" name="payment_type" value="cashless"
                            onchange="handlePaymentTypeChange('cashless')" class="sr-only" />
                        <div class="w-4 h-4 rounded-full border border-slate-300 bg-white" id="pay-cashless-dot"></div>
                        <span class="font-bold text-xs">Bayar Sekarang</span>
                        <span class="text-[9px] text-slate-400">QRIS / Virtual Account</span>
                    </label>
                </div>

                <!-- Cashless Payment Channels Container -->
                <div id="cashless-details" class="hidden pt-2 space-y-3">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pilih Metode Cashless</label>
                    <div id="payment-channels-container" class="grid grid-cols-2 gap-2">
                        <!-- Dynamic payment channels rendered here -->
                    </div>
                </div>

                <!-- Cash Info banner alert -->
                <div id="cash-info-banner"
                    class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-[11px] flex gap-2 text-blue-700">
                    <span class="flex-shrink-0 text-sm">💡</span>
                    <p class="leading-relaxed font-semibold">Setelah memesan, silakan tunjukkan nomor pesanan ke kasir untuk melakukan pembayaran cash.</p>
                </div>
            </div>

            <!-- Total Price Summary Card -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm space-y-2.5">
                <div class="flex justify-between text-xs text-slate-500">
                    <span class="font-medium">Subtotal</span>
                    <span id="summary-subtotal" class="font-bold text-slate-700">Rp0</span>
                </div>
                <div class="flex justify-between text-xs text-slate-500">
                    <span class="font-medium">{{ $taxEnabled ? $taxName . ' (' . floatval($taxRate) . '%)' : 'Pajak (0%)' }}</span>
                    <span id="summary-tax" class="font-bold text-slate-700">Rp0</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-slate-800 pt-2.5 border-t border-dashed border-slate-200">
                    <span>Total Keseluruhan</span>
                    <span id="summary-total" class="text-brand-500 font-bold text-md">Rp0</span>
                </div>
            </div>

        </div>
    </div>

    <!-- CTA Fixed Bottom Bar -->
    <div id="checkout-action-bar" class="hidden h-[74px] bg-white border-t border-slate-100 shadow-md fixed bottom-0 inset-x-0 max-w-[480px] mx-auto z-40 px-4 flex items-center justify-center">
        <button onclick="submitOrder()" id="checkout-btn"
            class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3.5 rounded-xl shadow-md shadow-primary-hover active:scale-98 transition-all text-xs flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            <span id="checkout-btn-text">Kirim Pesanan Sekarang</span>
        </button>
    </div>

    <!-- Scripting for Checkout Logic -->
    <script>
        const tableCode = "{{ $tableCode }}";
        const publicApiUrl = `/api/public`;
        const taxRate = {{ $taxEnabled ? $taxRate : 0 }};
        const taxEnabled = {{ $taxEnabled ? 'true' : 'false' }};

        let selectedPaymentType = 'cash';
        let selectedPaymentMethod = null;
        let selectedPaymentChannel = null;

        document.addEventListener('DOMContentLoaded', () => {
            const savedName = localStorage.getItem(`customer_name_${tableCode}`);
            const savedPhone = localStorage.getItem(`customer_phone_${tableCode}`);
            if (savedName) document.getElementById('cust-name').value = savedName;
            if (savedPhone) document.getElementById('cust-phone').value = savedPhone;

            renderCart();
            fetchPaymentMethods();
        });

        // Cart items list renderer
        function renderCart() {
            const cart = JSON.parse(localStorage.getItem('gokasir_cart') || '[]');
            const emptyState = document.getElementById('empty-state');
            const checkoutPanel = document.getElementById('checkout-panel');
            const actionBar = document.getElementById('checkout-action-bar');
            const cartItems = document.getElementById('cart-items');

            if (cart.length === 0) {
                emptyState.classList.remove('hidden');
                checkoutPanel.classList.add('hidden');
                actionBar.classList.add('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            checkoutPanel.classList.remove('hidden');
            actionBar.classList.remove('hidden');
            cartItems.innerHTML = '';

            let subtotal = 0;
            cart.forEach(item => {
                const sub = item.price * item.qty;
                subtotal += sub;

                const itemRow = document.createElement('div');
                itemRow.className = 'py-3.5 flex items-center justify-between gap-3';
                
                itemRow.innerHTML = `
                    <div class="w-[72px] h-[72px] bg-slate-50 rounded-lg overflow-hidden border border-slate-100 flex-shrink-0 flex items-center justify-center">
                        ${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">` : `<span class="text-2xl">🍽️</span>`}
                    </div>
                    <div class="flex-1 min-w-0 pr-1 pl-3">
                        <span class="text-base font-[600] text-slate-900 block leading-snug truncate">${item.name}</span>
                        <span class="text-xs text-slate-500 font-medium block mt-1">Rp${parseFloat(item.price).toLocaleString('id-ID')}</span>
                    </div>
                    <div class="flex flex-col items-end justify-between gap-2 flex-shrink-0">
                        <span class="font-bold text-slate-900 text-xs">Rp${sub.toLocaleString('id-ID')}</span>
                        <div class="flex items-center gap-1.5">
                            <button onclick="updateQty(${item.product_id}, ${item.qty - 1})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition-colors font-medium text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minus"><path d="M5 12h14"/></svg>
                            </button>
                            <span class="text-xs font-semibold text-slate-950 w-6 text-center">${item.qty}</span>
                            <button onclick="updateQty(${item.product_id}, ${item.qty + 1})" class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-500 hover:bg-brand-600 text-white transition-colors font-medium text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            </button>
                        </div>
                    </div>
                `;
                cartItems.appendChild(itemRow);
            });

            const tax = taxEnabled ? Math.round(subtotal * (taxRate / 100)) : 0;
            const total = subtotal + tax;

            document.getElementById('summary-subtotal').innerText = 'Rp' + subtotal.toLocaleString('id-ID');
            document.getElementById('summary-tax').innerText = 'Rp' + tax.toLocaleString('id-ID');
            document.getElementById('summary-total').innerText = 'Rp' + total.toLocaleString('id-ID');
        }

        function updateQty(productId, qty) {
            let cart = JSON.parse(localStorage.getItem('gokasir_cart') || '[]');
            const idx = cart.findIndex(item => item.product_id === productId);
            if (idx > -1) {
                if (qty <= 0) {
                    cart.splice(idx, 1);
                } else {
                    cart[idx].qty = qty;
                }
            }
            localStorage.setItem('gokasir_cart', JSON.stringify(cart));
            renderCart();
        }

        let paymentChannels = [];

        async function fetchPaymentMethods() {
            try {
                const response = await fetch('/api/public/payment-methods');
                const result = await response.json();
                if (result.success) {
                    const channelsData = result.data.Data || result.data.data || result.data;
                    if (Array.isArray(channelsData)) {
                        if (channelsData.length > 0) {
                            const firstCat = channelsData[0];
                            const method = firstCat.Code || firstCat.code;
                            const firstCh = (firstCat.Channels || firstCat.channels || [])[0];
                            if (firstCh) {
                                selectedPaymentMethod = method;
                                selectedPaymentChannel = firstCh.Code || firstCh.code;
                            }
                        }
                        renderPaymentChannels(channelsData);
                    }
                }
            } catch (err) {
                console.error('Error fetching payment methods:', err);
                const fallbackData = [
                    {
                        code: 'qris',
                        channels: [{ code: 'mpm', name: 'QRIS' }]
                    },
                    {
                        code: 'va',
                        channels: [
                            { code: 'bri', name: 'BRI VA' },
                            { code: 'mandiri', name: 'Mandiri VA' },
                            { code: 'bni', name: 'BNI VA' }
                        ]
                    }
                ];
                selectedPaymentMethod = 'qris';
                selectedPaymentChannel = 'mpm';
                renderPaymentChannels(fallbackData);
            }
        }

        function getFallbackLogo(code) {
            const cleanCode = code.toLowerCase();
            const ipaymuBase = 'https://ipaymu.com/images/payment';
            if (cleanCode.includes('qris') || cleanCode === 'mpm') {
                return 'https://ipaymu.com/images/payment/qris.png';
            }
            if (cleanCode === 'bri') return `${ipaymuBase}/bri.png`;
            if (cleanCode === 'mandiri') return `${ipaymuBase}/mandiri.png`;
            if (cleanCode === 'bni') return `${ipaymuBase}/bni.png`;
            if (cleanCode === 'bca') return `${ipaymuBase}/bca.png`;
            if (cleanCode === 'permata') return `${ipaymuBase}/permata.png`;
            if (cleanCode === 'cimb') return `${ipaymuBase}/cimb.png`;
            if (cleanCode === 'danamon') return `${ipaymuBase}/danamon.png`;
            if (cleanCode === 'muamalat') return `${ipaymuBase}/muamalat.png`;
            return null;
        }

        function renderPaymentChannels(categories) {
            const container = document.getElementById('payment-channels-container');
            container.innerHTML = '';
            paymentChannels = [];

            categories.forEach(cat => {
                const method = cat.Code || cat.code;
                const channels = cat.Channels || cat.channels || [];

                channels.forEach(ch => {
                    const channelCode = ch.Code || ch.code;
                    const channelName = ch.Name || ch.name;
                    const channelLogo = ch.Logo || ch.logo || getFallbackLogo(channelCode);

                    paymentChannels.push({ method, channel: channelCode });

                    const isChecked = selectedPaymentMethod === method && selectedPaymentChannel === channelCode;

                    const label = document.createElement('label');
                    label.id = `channel-${method}-${channelCode}-label`;
                    label.className = isChecked ?
                        'border border-brand-500 bg-brand-50 cursor-pointer rounded-xl p-2.5 flex items-center justify-between gap-1.5 transition-all shadow-xs' :
                        'border border-slate-200 hover:bg-slate-50 cursor-pointer rounded-xl p-2.5 flex items-center justify-between gap-1.5 transition-all';

                    const logoHtml = channelLogo ? `<img src="${channelLogo}" alt="${channelName}" class="h-4 object-contain max-w-[40px] flex-shrink-0" onerror="this.style.display='none'">` : '';

                    label.innerHTML = `
                        <input type="radio" name="payment_channel_selector" value="${method}|${channelCode}" ${isChecked ? 'checked' : ''}
                            onchange="selectCashlessChannel('${method}', '${channelCode}')" class="sr-only" />
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-3.5 h-3.5 rounded-full ${isChecked ? 'border-4 border-brand-500 bg-white' : 'border border-slate-300 bg-white'} flex-shrink-0"
                                id="channel-${method}-${channelCode}-dot"></div>
                            <span class="font-bold text-[11px] text-slate-700 truncate">${channelName}</span>
                        </div>
                        ${logoHtml}
                    `;
                    container.appendChild(label);
                });
            });
        }

        function handlePaymentTypeChange(type) {
            selectedPaymentType = type;
            const cashLabel = document.getElementById('pay-cash-label');
            const cashlessLabel = document.getElementById('pay-cashless-label');
            const cashDot = document.getElementById('pay-cash-dot');
            const cashlessDot = document.getElementById('pay-cashless-dot');
            const cashlessDetails = document.getElementById('cashless-details');
            const cashBanner = document.getElementById('cash-info-banner');
            const checkoutBtnText = document.getElementById('checkout-btn-text');

            if (type === 'cash') {
                cashLabel.className =
                    'border border-brand-500 bg-brand-50 cursor-pointer rounded-xl p-3 flex flex-col gap-1.5 transition-all text-slate-700 relative overflow-hidden shadow-xs';
                cashlessLabel.className =
                    'border border-slate-200 hover:border-slate-350 hover:bg-slate-50 cursor-pointer rounded-xl p-3 flex flex-col gap-1.5 transition-all text-slate-700 relative overflow-hidden';
                cashDot.className = 'w-4 h-4 rounded-full border-4 border-brand-500 bg-white flex items-center justify-center';
                cashlessDot.className = 'w-4 h-4 rounded-full border border-slate-300 bg-white';
                cashlessDetails.classList.add('hidden');
                cashBanner.classList.remove('hidden');
                checkoutBtnText.innerText = 'Kirim Pesanan Sekarang';
                selectedPaymentMethod = null;
                selectedPaymentChannel = null;
            } else {
                cashLabel.className =
                    'border border-slate-200 hover:border-slate-350 hover:bg-slate-50 cursor-pointer rounded-xl p-3 flex flex-col gap-1.5 transition-all text-slate-700 relative overflow-hidden';
                cashlessLabel.className =
                    'border border-brand-500 bg-brand-50 cursor-pointer rounded-xl p-3 flex flex-col gap-1.5 transition-all text-slate-700 relative overflow-hidden shadow-xs';
                cashDot.className = 'w-4 h-4 rounded-full border border-slate-300 bg-white';
                cashlessDot.className = 'w-4 h-4 rounded-full border-4 border-brand-500 bg-white flex items-center justify-center';
                cashlessDetails.classList.remove('hidden');
                cashBanner.classList.add('hidden');
                checkoutBtnText.innerText = 'Lanjut Bayar Sekarang';
                if (selectedPaymentMethod && selectedPaymentChannel) {
                    selectCashlessChannel(selectedPaymentMethod, selectedPaymentChannel);
                }
            }
        }

        function selectCashlessChannel(method, channel) {
            selectedPaymentMethod = method;
            selectedPaymentChannel = channel;

            paymentChannels.forEach(item => {
                const label = document.getElementById(`channel-${item.method}-${item.channel}-label`);
                const dot = document.getElementById(`channel-${item.method}-${item.channel}-dot`);

                if (label && dot) {
                    if (item.method === method && item.channel === channel) {
                        label.className =
                            'border border-brand-500 bg-brand-50 cursor-pointer rounded-xl p-2.5 flex items-center gap-2 transition-all shadow-xs';
                        dot.className = 'w-3.5 h-3.5 rounded-full border-4 border-brand-500 bg-white flex-shrink-0';
                    } else {
                        label.className =
                            'border border-slate-200 hover:bg-slate-50 cursor-pointer rounded-xl p-2.5 flex items-center gap-2 transition-all';
                        dot.className = 'w-3.5 h-3.5 rounded-full border border-slate-300 bg-white flex-shrink-0';
                    }
                }
            });
        }

        // Place order submit function
        async function submitOrder() {
            const nameInput = document.getElementById('cust-name');
            const phoneInput = document.getElementById('cust-phone');
            const paxInput = document.getElementById('cust-pax');
            const notesInput = document.getElementById('cust-notes');
            const checkoutBtn = document.getElementById('checkout-btn');

            if (!nameInput.value.trim()) {
                alert('Nama Lengkap wajib diisi.');
                nameInput.focus();
                return;
            }

            if (selectedPaymentType === 'cashless' && !phoneInput.value.trim()) {
                alert('Nomor Handphone wajib diisi untuk transaksi cashless.');
                phoneInput.focus();
                return;
            }

            const cart = JSON.parse(localStorage.getItem('gokasir_cart') || '[]');
            if (cart.length === 0) return;

            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = `
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Mengirim Pesanan...</span>
            `;

            try {
                // 1. Start Session
                const sessionRes = await fetch(`${publicApiUrl}/order/${tableCode}/session`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pax: parseInt(paxInput.value),
                        customer_name: nameInput.value.trim(),
                        customer_phone: phoneInput.value.trim() || null
                    })
                });
                const sessionResult = await sessionRes.json();
                if (!sessionResult.success) {
                    alert('Gagal memulai sesi order: ' + sessionResult.message);
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = `<span>Kirim Pesanan Sekarang</span>`;
                    return;
                }

                const token = sessionResult.data.session_token;
                localStorage.setItem(`session_token_${tableCode}`, token);
                localStorage.setItem(`customer_name_${tableCode}`, nameInput.value.trim());
                if (phoneInput.value.trim()) {
                    localStorage.setItem(`customer_phone_${tableCode}`, phoneInput.value.trim());
                }

                // 2. Place Order
                const payload = {
                    session_token: token,
                    payment_type: selectedPaymentType,
                    notes: notesInput.value.trim() || null,
                    items: cart.map(item => ({
                        product_id: item.product_id,
                        qty: item.qty,
                        notes: null
                    }))
                };

                if (selectedPaymentType === 'cashless') {
                    payload.payment_method = selectedPaymentMethod;
                    payload.payment_channel = selectedPaymentChannel;
                    payload.customer_phone = phoneInput.value.trim();
                }

                const orderRes = await fetch(`${publicApiUrl}/order/${tableCode}/place`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const orderResult = await orderRes.json();

                if (orderResult.success) {
                    // Save to order history list in localStorage for offline backups / guests
                    try {
                        const historyList = JSON.parse(localStorage.getItem('gokasir_order_history') || '[]');
                        if (!historyList.some(item => item.order_number === orderResult.data.order_number)) {
                            historyList.push({
                                order_number: orderResult.data.order_number,
                                table_code: tableCode,
                                grand_total: orderResult.data.grand_total,
                                payment_type: orderResult.data.payment_type || selectedPaymentType,
                                date: new Date().toISOString()
                            });
                            localStorage.setItem('gokasir_order_history', JSON.stringify(historyList));
                        }
                    } catch (e) {
                        console.error('Gagal menyimpan riwayat pesanan ke localStorage:', e);
                    }

                    // Empty cart on success
                    localStorage.removeItem('gokasir_cart');

                    // Redirect to status page
                    window.location.href = `/order/${tableCode}/status/${orderResult.data.order_number}`;
                } else {
                    alert('Gagal mengirim pesanan: ' + orderResult.message);
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = `<span>Kirim Pesanan Sekarang</span>`;
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan jaringan.');
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = `<span>Kirim Pesanan Sekarang</span>`;
            }
        }
    </script>
@endsection
