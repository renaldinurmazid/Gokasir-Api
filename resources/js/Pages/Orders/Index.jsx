import React, { useState, useEffect } from "react";
import { Head, Link, router } from "@inertiajs/react";
import useCart from "../../hooks/useCart";

export default function Index({
    tableCode,
    table,
    recommendedProducts,
    categories,
}) {
    const { cart, addToCart, totalQty, subtotal } = useCart(tableCode);
    const [searchQuery, setSearchQuery] = useState("");

    const handleSearch = (e) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.get(`/order/${tableCode}/search`, { q: searchQuery });
        }
    };

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col font-sans pb-24 max-w-[480px] mx-auto relative shadow-xl">
            <Head title={`Menu - ${table.store.name}`} />

            {/* Header Area */}
            <div className="bg-[#B71C1C] text-white">
                <header className="h-[56px] px-4 flex items-center justify-end sticky top-0 z-50">
                    <div className="flex gap-3">
                        <Link
                            href={`/order/${tableCode}/search`}
                            className="p-1 rounded-full bg-white/20 text-white"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                        </Link>
                        <Link
                            href={`/profile/${tableCode}`}
                            className="p-1 rounded-full bg-white/20 text-white"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <line x1="4" x2="20" y1="12" y2="12" />
                                <line x1="4" x2="20" y1="6" y2="6" />
                                <line x1="4" x2="20" y1="18" y2="18" />
                            </svg>
                        </Link>
                    </div>
                </header>

                {/* Store Banner / Logo Area */}
                <div className="pt-2 pb-6 px-4 flex justify-center items-center w-full">
                    {table.store.banner_url ? (
                        <img
                            src={table.store.banner_url}
                            alt={table.store.name}
                            className="w-full h-32 object-cover rounded-xl shadow-sm border border-white/20"
                        />
                    ) : (
                        <div className="text-center font-black text-3xl tracking-tighter uppercase italic drop-shadow-md">
                            {table.store.name}
                        </div>
                    )}
                </div>
            </div>

            {/* Store Info Card */}
            <div className="px-4 -mt-4 relative z-10">
                <div className="bg-white rounded-xl shadow-sm p-4 flex justify-between items-center border border-gray-100">
                    <div>
                        <h2 className="font-bold text-gray-800 text-sm truncate max-w-[200px]">
                            {table.store.name}
                        </h2>
                    </div>
                </div>
            </div>

            {/* Table Number Indicator */}
            <div className="px-4 mt-3">
                <div className="bg-orange-50 text-orange-800 text-xs font-bold text-center py-2.5 rounded-lg border border-orange-100">
                    Nomor Meja: {table.name}
                </div>
            </div>

            {/* Recommended Products */}
            {recommendedProducts && recommendedProducts.length > 0 && (
                <div className="mt-6 px-4">
                    <div className="flex justify-between items-end mb-3">
                        <h3 className="font-bold text-gray-800 text-sm">
                            Menu Recommendation
                        </h3>
                        <Link
                            href={`/order/${tableCode}/search`}
                            className="text-xs text-gray-500 flex items-center"
                        >
                            Lihat Semua{" "}
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="ml-0.5"
                            >
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </Link>
                    </div>
                    <div className="flex gap-3 overflow-x-auto pb-4 no-scrollbar -mx-4 px-4 snap-x">
                        {recommendedProducts.map((product) => {
                            const cartItem = cart.find(
                                (i) => i.product_id === product.id,
                            );
                            return (
                                <div
                                    key={product.id}
                                    className="bg-white rounded-xl shadow-sm border border-gray-100 w-[140px] flex-shrink-0 flex flex-col overflow-hidden snap-start"
                                >
                                    <div className="h-28 bg-gray-50 relative">
                                        {product.photo_url ? (
                                            <img
                                                src={product.photo_url}
                                                alt={product.name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-3xl">
                                                🍽️
                                            </div>
                                        )}
                                    </div>
                                    <div className="p-3 flex flex-col flex-1 justify-between">
                                        <div>
                                            <h4 className="font-bold text-[11px] text-gray-800 leading-snug line-clamp-2 min-h-[32px]">
                                                {product.name}
                                            </h4>
                                            <p className="font-bold text-[12px] text-gray-900 mt-1">
                                                Rp
                                                {parseFloat(
                                                    product.selling_price,
                                                ).toLocaleString("id-ID")}
                                            </p>
                                        </div>
                                        <div className="mt-3">
                                            {cartItem ? (
                                                <div className="flex items-center justify-between border border-gray-200 rounded-lg overflow-hidden h-7">
                                                    <button
                                                        onClick={() =>
                                                            updateQty(
                                                                product.id,
                                                                cartItem.qty -
                                                                    1,
                                                            )
                                                        }
                                                        className="w-8 flex justify-center text-gray-600 font-bold bg-gray-50 h-full items-center active:bg-gray-200"
                                                    >
                                                        -
                                                    </button>
                                                    <span className="text-[11px] font-bold px-2">
                                                        {cartItem.qty}
                                                    </span>
                                                    <button
                                                        onClick={() =>
                                                            addToCart(product)
                                                        }
                                                        className="w-8 flex justify-center text-gray-600 font-bold bg-gray-50 h-full items-center active:bg-gray-200"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            ) : (
                                                <button
                                                    onClick={() =>
                                                        addToCart(product)
                                                    }
                                                    className="w-full border border-orange-500 text-orange-500 font-bold text-[10px] rounded-lg py-1.5 active:bg-orange-50 transition-colors"
                                                >
                                                    Tambah
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Categories */}
            {categories && categories.length > 0 && (
                <div className="mt-4 px-4 pb-4">
                    <h3 className="font-bold text-gray-800 text-sm mb-3">
                        Daftar Kategori
                    </h3>
                    <div className="grid grid-cols-2 gap-3">
                        {categories.map((category) => {
                            const firstProduct = category.products?.[0];
                            return (
                                <Link
                                    href={`/order/${tableCode}/${category.name}`}
                                    key={category.id}
                                    className="relative rounded-xl overflow-hidden h-24 shadow-sm group"
                                >
                                    <div className="absolute inset-0 bg-black">
                                        {firstProduct &&
                                        firstProduct.photo_url ? (
                                            <img
                                                src={firstProduct.photo_url}
                                                className="w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-500"
                                            />
                                        ) : (
                                            <div className="w-full h-full bg-gradient-to-br from-orange-500 to-red-600 opacity-80"></div>
                                        )}
                                    </div>
                                    <div className="absolute inset-0 flex items-center justify-center p-2">
                                        <span className="text-white font-black text-sm text-center drop-shadow-md tracking-wide uppercase">
                                            {category.name}
                                        </span>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Floating Cart Bottom Bar */}
            {totalQty > 0 && (
                <div className="fixed bottom-0 inset-x-0 mx-auto max-w-[480px] p-4 z-50">
                    <Link
                        href={`/order/${tableCode}/checkout`}
                        className="bg-[#EF5350] hover:bg-[#D32F2F] text-white rounded-xl shadow-lg p-3 px-4 flex justify-between items-center transform transition-transform active:scale-95"
                    >
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <circle cx="8" cy="21" r="1" />
                                    <circle cx="19" cy="21" r="1" />
                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                                </svg>
                                <span className="absolute -top-2 -right-2 bg-white text-[#EF5350] text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border border-[#EF5350]">
                                    {totalQty}
                                </span>
                            </div>
                            <div className="flex flex-col">
                                <span className="text-[10px] font-medium text-white/80 leading-none">
                                    Total
                                </span>
                                <span className="text-sm font-bold leading-none mt-1">
                                    Rp{subtotal.toLocaleString("id-ID")}
                                </span>
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
