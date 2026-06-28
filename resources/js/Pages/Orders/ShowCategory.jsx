import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import useCart from '../../hooks/useCart';

export default function ShowCategory({ tableCode, table, category, products }) {
    const { cart, addToCart, updateQty, totalQty, subtotal } = useCart(tableCode);

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col font-sans pb-24 max-w-[480px] mx-auto relative shadow-xl">
            <Head title={`${category.name} - ${table.store.name}`} />

            <div className="bg-[#B71C1C] text-white">
                <header className="h-[56px] px-4 flex items-center gap-3 sticky top-0 z-50">
                    <Link href={`/order/${tableCode}`} className="p-2 -ml-2 text-white/80 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </Link>
                    <h1 className="font-bold text-lg">{category.name}</h1>
                </header>
            </div>

            <div className="p-4 space-y-3">
                {products && products.length > 0 ? (
                    products.map(product => {
                        const cartItem = cart.find(i => i.product_id === product.id);
                        return (
                            <div key={product.id} className="bg-white rounded-xl shadow-sm border border-gray-100 p-3 flex gap-3">
                                <div className="w-24 h-24 bg-gray-50 rounded-lg overflow-hidden flex-shrink-0">
                                    {product.photo_url ? (
                                        <img src={product.photo_url} alt={product.name} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-3xl">🍽️</div>
                                    )}
                                </div>
                                <div className="flex-1 flex flex-col justify-between py-1">
                                    <div>
                                        <h4 className="font-bold text-sm text-gray-800 leading-snug line-clamp-2">{product.name}</h4>
                                        <p className="font-bold text-sm text-gray-900 mt-1">Rp{parseFloat(product.selling_price).toLocaleString('id-ID')}</p>
                                    </div>
                                    <div className="flex justify-end mt-2">
                                        {cartItem ? (
                                            <div className="flex items-center justify-between border border-gray-200 rounded-lg overflow-hidden h-8 w-24">
                                                <button onClick={() => updateQty(product.id, cartItem.qty - 1)} className="w-8 flex justify-center text-gray-600 font-bold bg-gray-50 h-full items-center active:bg-gray-200">-</button>
                                                <span className="text-xs font-bold px-2">{cartItem.qty}</span>
                                                <button onClick={() => addToCart(product)} className="w-8 flex justify-center text-gray-600 font-bold bg-gray-50 h-full items-center active:bg-gray-200">+</button>
                                            </div>
                                        ) : (
                                            <button 
                                                onClick={() => addToCart(product)}
                                                className="border border-orange-500 text-orange-500 font-bold text-xs rounded-lg px-4 py-1.5 active:bg-orange-50 transition-colors">
                                                Tambah
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })
                ) : (
                    <div className="text-center py-10 text-gray-500 text-sm font-medium">
                        Tidak ada produk dalam kategori ini.
                    </div>
                )}
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
