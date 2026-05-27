@extends('layouts.order')

@section('title', 'GoKasir - Kategori ' . $category->name)

@section('content')
    <!-- Back Bar -->
    <header class="h-[52px] bg-white border-b border-slate-100 shadow-sm sticky top-0 z-50 px-4 flex items-center gap-3">
        <a href="/order/{{ $tableCode }}" class="p-1 hover:bg-slate-50 rounded-full transition-colors text-slate-700 -ml-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="font-bold text-sm text-slate-800 tracking-wide uppercase">{{ $category->name }}</h1>
    </header>

    <!-- Scrollable Content Viewport -->
    <div class="space-y-5 flex-1 pb-24">
        
        <!-- Category Banner -->
        <div class="bg-gradient-to-r from-brand-700 to-brand-500 text-white px-5 py-6 shadow-inner relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-6xl opacity-10 pointer-events-none">🍳</div>
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] text-brand-100 font-extrabold uppercase tracking-wider block">Kategori</span>
                <h2 class="font-extrabold text-lg tracking-tight">{{ $category->name }}</h2>
                <p class="text-[11px] text-brand-50 font-medium inline-block bg-white/10 px-2 py-0.5 rounded">
                    {{ $products->count() }} Menu Tersedia
                </p>
            </div>
        </div>

        <!-- Product Grid List Section -->
        <main class="px-4">
            @if ($products->isNotEmpty())
                <div class="grid grid-cols-2 gap-3.5">
                    @foreach ($products as $product)
                        <div class="bg-white border border-slate-100 rounded-2xl p-3 shadow-sm hover:shadow-md transition-shadow duration-300 flex flex-col justify-between space-y-3">
                            <!-- Image square viewport -->
                            <div class="aspect-square bg-slate-50 rounded-xl overflow-hidden border border-slate-100/50 flex items-center justify-center relative">
                                @if ($product->image)
                                    <img src="{{ $product->photo_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-3xl">🍽️</span>
                                @endif
                            </div>
                            
                            <div class="space-y-2">
                                <h6 class="font-bold text-[13px] text-slate-800 line-clamp-2 leading-snug min-h-[2.25rem]">
                                    {{ $product->name }}
                                </h6>
                                <div class="flex flex-col gap-2 pt-1 border-t border-slate-50/50">
                                    <span class="text-[13px] font-extrabold text-brand-500">
                                        Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                    </span>
                                    <button onclick="addToCart({{ json_encode($product) }})" 
                                            class="w-full h-8 bg-brand-500 hover:bg-brand-600 text-white text-[10px] font-bold rounded-lg shadow-sm hover:shadow-md active:scale-95 transition-all flex items-center justify-center gap-1">
                                        <span>+ Tambah</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-20 space-y-4">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-utensils-crossed"><path d="m16 2-2.3 2.3a3 3 0 0 0 0 4.2l1.8 1.8a3 3 0 0 0 4.2 0L22 8Z"/><path d="M14 6.5 9 11.5"/><path d="m3 21 6-6"/><path d="M14 18V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v13a3 3 0 0 0 3 3h6a3 3 0 0 0 3-3Zm-4 0v-7H4v7Z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-slate-700 text-sm">Belum Ada Menu</h3>
                        <p class="text-xs text-slate-400 max-w-[220px] mx-auto leading-normal">
                            Tidak ada hidangan aktif yang tersedia di kategori ini saat ini.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="/order/{{ $tableCode }}" class="inline-flex border border-brand-500 text-brand-500 font-bold text-xs px-5 py-2.5 rounded-xl hover:bg-brand-50 active:scale-95 transition-all">
                            Kembali ke Menu Utama
                        </a>
                    </div>
                </div>
            @endif
        </main>
    </div>

    <!-- Fixed Bottom Navigation Bar -->
    <nav class="h-[64px] bg-white border-t border-slate-100 shadow-md fixed bottom-0 inset-x-0 max-w-[480px] mx-auto z-50 flex justify-around items-center">
        <!-- Tab 1: Menu (Active) -->
        <a href="/order/{{ $tableCode }}" class="flex-1 h-full flex flex-col items-center justify-center relative text-brand-500">
            <div class="absolute top-0 left-6 right-6 h-[3px] bg-brand-500 rounded-full"></div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span class="text-[10px] font-bold mt-1 tracking-wide">Menu</span>
        </a>
        
        <!-- Tab 2: Keranjang -->
        <a href="/order/{{ $tableCode }}/checkout" class="flex-1 h-full flex flex-col items-center justify-center text-slate-400 hover:text-slate-500 relative">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span id="nav-cart-badge" class="absolute -top-2.5 -right-2.5 bg-brand-500 text-white font-bold text-[9px] w-5 h-5 rounded-full flex items-center justify-center scale-0 transition-transform duration-200">0</span>
            </div>
            <span class="text-[10px] font-semibold mt-1 tracking-wide">Keranjang</span>
        </a>
        
        <!-- Tab 3: Pesanan -->
        <a href="/order/{{ $tableCode }}/history" class="flex-1 h-full flex flex-col items-center justify-center text-slate-400 hover:text-slate-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span class="text-[10px] font-semibold mt-1 tracking-wide">Pesanan</span>
        </a>
    </nav>

    <!-- Cart Script -->
    <script>
        function addToCart(product) {
            let cart = JSON.parse(localStorage.getItem('gokasir_cart') || '[]');
            const existing = cart.find(item => item.product_id === product.id);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({
                    product_id: product.id,
                    name: product.name,
                    price: product.selling_price,
                    qty: 1,
                    image: product.photo_url || null
                });
            }
            localStorage.setItem('gokasir_cart', JSON.stringify(cart));
            
            // Trigger badge bounce effect
            const badge = document.getElementById('nav-cart-badge');
            if (badge) {
                badge.classList.remove('animate-bounce-badge');
                void badge.offsetWidth;
                badge.classList.add('animate-bounce-badge');
            }

            updateFloatingCart();
        }

        function updateFloatingCart() {
            const cart = JSON.parse(localStorage.getItem('gokasir_cart') || '[]');
            const cartBadge = document.getElementById('nav-cart-badge');

            if (cart.length > 0) {
                let totalQty = 0;
                cart.forEach(item => {
                    totalQty += item.qty;
                });

                cartBadge.innerText = totalQty;
                cartBadge.classList.remove('scale-0');
                cartBadge.classList.add('scale-100');
            } else {
                cartBadge.classList.add('scale-0');
                cartBadge.classList.remove('scale-100');
            }
        }

        document.addEventListener('DOMContentLoaded', updateFloatingCart);
    </script>
@endsection
