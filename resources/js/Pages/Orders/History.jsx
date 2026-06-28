import React, { useState, useEffect } from "react";
import { Head, Link } from "@inertiajs/react";

export default function History({ tableCode, table }) {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState("all");

    useEffect(() => {
        const fetchHistory = async () => {
            try {
                const token = localStorage.getItem(`session_token_${tableCode}`);
                let historyList = [];
                try {
                    historyList = JSON.parse(localStorage.getItem('gokasir_order_history') || '[]');
                } catch (e) {}

                const tableOrders = historyList
                    .filter(item => item.table_code === tableCode)
                    .map(item => item.order_number);

                if (!token && tableOrders.length === 0) {
                    setLoading(false);
                    return;
                }

                const params = new URLSearchParams();
                if (token) params.append('session_token', token);
                tableOrders.forEach(no => params.append('order_numbers[]', no));

                const res = await fetch(`/api/public/order/${tableCode}/history?${params.toString()}`);
                const result = await res.json();

                if (result.success && result.data) {
                    setOrders(result.data);
                }
            } catch (err) {
                console.error("Failed to fetch history", err);
            } finally {
                setLoading(false);
            }
        };

        fetchHistory();
    }, [tableCode]);

    const filteredOrders = orders.filter((o) => {
        if (filter === "active")
            return o.status === "pending" || o.status === "confirmed";
        if (filter === "completed") return o.status === "paid";
        if (filter === "cancelled") return o.status === "cancelled";
        return true;
    });

    const getBadgeStyle = (order) => {
        if (order.status === "pending") {
            if (
                order.payment_type === "cashless" &&
                (order.payment_status === "pending_payment" ||
                    order.payment_status === "unpaid")
            ) {
                return {
                    text: "BELUM BAYAR",
                    className: "bg-yellow-50 text-yellow-600 border-yellow-200",
                };
            }
            return {
                text: "MENUNGGU",
                className: "bg-red-50 text-red-500 border-red-200",
            };
        }
        if (order.status === "confirmed")
            return {
                text: "DIPROSES",
                className: "bg-blue-50 text-blue-600 border-blue-200",
            };
        if (order.status === "paid")
            return {
                text: "SELESAI",
                className: "bg-green-50 text-green-600 border-green-200",
            };
        if (order.status === "cancelled")
            return {
                text: "BATAL",
                className: "bg-red-50 text-red-500 border-red-200",
            };
        return {
            text: order.status.toUpperCase(),
            className: "bg-gray-50 text-gray-500 border-gray-200",
        };
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col max-w-[480px] mx-auto relative shadow-xl pb-10">
            <Head title={`Riwayat Pesanan - ${table.store.name}`} />

            <header className="h-[56px] bg-white border-b border-gray-100 flex items-center gap-3 sticky top-0 z-50 px-4 shadow-sm">
                <Link
                    href={`/order/${tableCode}`}
                    className="p-2 -ml-2 text-gray-800"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </Link>
                <h1 className="font-bold text-gray-900">Riwayat Pesanan</h1>
            </header>

            <div className="p-4 space-y-4">
                {/* Filter Chips */}
                <div className="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                    {["all", "active", "completed", "cancelled"].map((f) => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`h-[32px] px-4 rounded-full flex items-center justify-center text-[10px] font-bold uppercase tracking-wider flex-shrink-0 transition-all ${filter === f ? "bg-[#EF5350] text-white shadow-md" : "bg-gray-100 text-gray-500 hover:bg-gray-200"}`}
                        >
                            {f === "all"
                                ? "Semua"
                                : f === "active"
                                  ? "Aktif"
                                  : f === "completed"
                                    ? "Selesai"
                                    : "Batal"}
                        </button>
                    ))}
                </div>

                {loading ? (
                    <div className="flex flex-col items-center justify-center py-20 space-y-3">
                        <svg
                            className="animate-spin h-8 w-8 text-[#EF5350]"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                className="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                strokeWidth="4"
                            ></circle>
                            <path
                                className="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span className="text-xs text-gray-400 font-semibold uppercase tracking-wider">
                            Memuat Transaksi...
                        </span>
                    </div>
                ) : filteredOrders.length > 0 ? (
                    <div className="space-y-4">
                        {filteredOrders.map((order) => {
                            const dateObj = new Date(order.date);
                            const displayDate =
                                dateObj.toLocaleDateString("id-ID", {
                                    day: "numeric",
                                    month: "short",
                                    year: "numeric",
                                }) +
                                " — " +
                                dateObj.toLocaleTimeString("id-ID", {
                                    hour: "2-digit",
                                    minute: "2-digit",
                                });
                            const badge = getBadgeStyle(order);
                            const itemsPreview = order.items
                                ?.map(
                                    (i) =>
                                        `${i.product_name} (${parseFloat(i.qty)}x)`,
                                )
                                .join(", ");

                            return (
                                <div
                                    key={order.order_number}
                                    className="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm space-y-4"
                                >
                                    <div className="flex items-center justify-between border-b border-gray-50 pb-3">
                                        <div>
                                            <span className="text-[9px] text-gray-400 font-bold uppercase block">
                                                No. Pesanan
                                            </span>
                                            <span className="font-bold text-xs text-gray-700 block">
                                                {order.order_number}
                                            </span>
                                        </div>
                                        <span
                                            className={`text-[9px] font-extrabold px-2.5 py-1 rounded-lg uppercase border ${badge.className}`}
                                        >
                                            {badge.text}
                                        </span>
                                    </div>

                                    <div className="space-y-3">
                                        <div className="bg-gray-50 rounded-xl p-3 border border-gray-100 shadow-inner">
                                            <span className="text-[9px] text-gray-400 font-bold uppercase block mb-1">
                                                Daftar Menu
                                            </span>
                                            <p className="text-xs text-gray-600 font-semibold line-clamp-2 leading-relaxed">
                                                {itemsPreview || "Menu"}
                                            </p>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3 text-xs">
                                            <div>
                                                <span className="text-[9px] text-gray-400 font-bold uppercase block">
                                                    Tanggal & Waktu
                                                </span>
                                                <span className="text-gray-600 font-medium block leading-snug">
                                                    {displayDate}
                                                </span>
                                            </div>
                                            <div className="text-right">
                                                <span className="text-[9px] text-gray-400 font-bold uppercase block">
                                                    Total Transaksi
                                                </span>
                                                <span className="text-[#EF5350] font-extrabold text-sm block leading-none">
                                                    Rp
                                                    {parseFloat(
                                                        order.grand_total,
                                                    ).toLocaleString("id-ID")}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between gap-3 pt-2 border-t border-gray-50">
                                        <div className="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                            <span className="text-xs">
                                                {order.payment_type ===
                                                "cashless"
                                                    ? "📱"
                                                    : "💵"}
                                            </span>
                                            <span className="capitalize">
                                                {order.payment_type ===
                                                "cashless"
                                                    ? "Bayar Cashless"
                                                    : "Bayar di Kasir"}
                                            </span>
                                        </div>
                                        <Link
                                            href={`/order/${tableCode}/status/${order.order_number}`}
                                            className="text-[10px] font-bold text-[#EF5350] bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-xl transition-all flex items-center gap-1"
                                        >
                                            Lacak Detail{" "}
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                className="h-3 w-3"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth="2.5"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M9 5l7 7m0 0l-7 7m7-7H3"
                                                />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="text-center py-20 space-y-4">
                        <div className="w-80 h-80 mx-auto flex items-center justify-center mb-2">
                            <img
                                src="/illustration/cart-empty.svg"
                                alt="Keranjang Kosong"
                                className="w-full h-full object-contain"
                            />
                        </div>
                        <div>
                            <h3 className="font-bold text-gray-700 text-sm">
                                Belum Ada Pesanan
                            </h3>
                            <p className="text-xs text-gray-400 max-w-[240px] mx-auto leading-normal mt-1">
                                Yuk, pilih hidangan makanan atau minuman segar
                                favorit Anda sekarang!
                            </p>
                        </div>
                        <div className="pt-2">
                            <Link
                                href={`/order/${tableCode}`}
                                className="inline-flex bg-[#EF5350] text-white font-bold text-xs px-6 py-3 rounded-xl shadow-md"
                            >
                                Mulai Pesan
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
