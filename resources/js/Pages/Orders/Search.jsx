import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import useCart from '../../hooks/useCart';

export default function Search({ tableCode, table, products, searchQuery }) {
    const { cart, addToCart, updateQty, totalQty, subtotal } = useCart(tableCode);
    const [query, setQuery] = useState(searchQuery || '');

    React.useEffect(() => {
        if (query === searchQuery) return; // Prevent unnecessary refetches if query matches the current URL

        const delay = setTimeout(() => {
            router.get(`/order/${tableCode}/search`, { q: query }, { 
                preserveState: true, 
                replace: true,
                preserveScroll: true
            });
        }, 400); // 400ms debounce

        return () => clearTimeout(delay);
    }, [query, tableCode, searchQuery]);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(`/order/${tableCode}/search`, { q: query }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    };

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col font-sans pb-24 max-w-[480px] mx-auto relative shadow-xl">
            <Head title={`Pencarian - ${table.store.name}`} />

            <div className="bg-white border-b border-gray-100">
                <header className="h-[60px] px-4 flex items-center gap-3 sticky top-0 z-50">
                    <Link href={`/order/${tableCode}`} className="p-2 -ml-2 text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </Link>
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-3 top-2.5 text-gray-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input 
                                type="text"
                                value={query}
                                onChange={e => setQuery(e.target.value)}
                                placeholder="Lagi pengen apa hari ini?"
                                className="w-full bg-white border border-gray-300 rounded-lg pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 text-sm focus:ring-1 focus:ring-[#EF5350] focus:border-[#EF5350] outline-none transition-all"
                                autoFocus
                            />
                        </div>
                    </form>
                </header>
            </div>

            <div className="p-4 bg-white min-h-[calc(100vh-60px)]">
                {searchQuery && (
                    <h3 className="font-bold text-gray-900 text-sm mb-4">
                        Hasil pencarian untuk "{searchQuery}"
                    </h3>
                )}
                {!searchQuery && products && products.length > 0 && (
                    <h3 className="font-bold text-gray-900 text-sm mb-4">Wajib Coba Minggu Ini</h3>
                )}
                
                <div className="space-y-4">
                {products && products.length > 0 ? (
                    products.map(product => {
                        const cartItem = cart.find(i => i.product_id === product.id);
                        return (
                            <div key={product.id} className="flex gap-3 items-center border-b border-gray-100 pb-4 last:border-0">
                                <div className="w-16 h-16 rounded-full overflow-hidden flex-shrink-0 relative shadow-sm border border-gray-100 bg-gray-50">
                                    {product.photo_url ? (
                                        <img src={product.photo_url} alt={product.name} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-2xl">🍽️</div>
                                    )}
                                    {cartItem && (
                                        <div className="absolute top-0 right-0 bg-[#EF5350] text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white">
                                            {cartItem.qty}
                                        </div>
                                    )}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <h4 className="font-bold text-sm text-gray-900 truncate">{product.name}</h4>
                                    <p className="text-[10px] text-gray-500 truncate mt-0.5">{product.description || product.name}</p>
                                    <p className="font-bold text-xs text-gray-900 mt-1">Rp{parseFloat(product.selling_price).toLocaleString('id-ID')}</p>
                                </div>
                                <div className="flex-shrink-0">
                                    {cartItem ? (
                                        <div className="flex items-center justify-between border border-[#EF5350] rounded-lg overflow-hidden h-7 w-20">
                                            <button onClick={() => updateQty(product.id, cartItem.qty - 1)} className="w-7 flex justify-center text-[#EF5350] font-bold h-full items-center active:bg-red-50">-</button>
                                            <span className="text-[11px] font-bold text-[#EF5350]">{cartItem.qty}</span>
                                            <button onClick={() => addToCart(product)} className="w-7 flex justify-center text-[#EF5350] font-bold h-full items-center active:bg-red-50">+</button>
                                        </div>
                                    ) : (
                                        <button 
                                            onClick={() => addToCart(product)}
                                            className="border border-[#EF5350] text-[#EF5350] font-bold text-xs rounded-lg px-4 py-1.5 active:bg-red-50 transition-colors">
                                            Tambah
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    })
                ) : searchQuery ? (
                    <div className="text-center py-10 text-gray-500 text-sm font-medium">
                        Menu yang Anda cari tidak ditemukan.
                    </div>
                ) : (
                    <div className="text-center py-10 text-gray-500 text-sm font-medium">
                        Ketikkan nama menu di atas.
                    </div>
                )}
                </div>
            </div>

            {totalQty > 0 && (
                <div className="fixed bottom-0 inset-x-0 mx-auto max-w-[480px] p-4 z-50">
                    <Link href={`/order/${tableCode}/checkout`} className="bg-[#EF5350] hover:bg-[#D32F2F] text-white rounded-xl shadow-lg p-3 px-4 flex justify-between items-center transform transition-transform active:scale-95">
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                <span className="absolute -top-2 -right-2 bg-white text-[#EF5350] text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border border-[#EF5350]">
                                    {totalQty}
                                </span>
                            </div>
                            <div className="flex flex-col">
                                <span className="text-[10px] font-medium text-white/80 leading-none">Total</span>
                                <span className="text-sm font-bold leading-none mt-1">Rp{subtotal.toLocaleString('id-ID')}</span>
                            </div>
                        </div>
                        <div className="font-bold text-sm">
                            CHECK OUT <span className="ml-1">({totalQty})</span>
                        </div>
                    </Link>
                </div>
            )}
        </div>
    );
}
