import React, { useState, useEffect } from "react";
import { Head, Link } from "@inertiajs/react";

const IconChevronLeft = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
);

const IconPhone = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
    </svg>
);

const IconCash = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
);

const IconArrowRight = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
    </svg>
);

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
        if (filter === "completed") return o.status === "completed";
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
                    text: "Menunggu Pembayaran",
                    className: "bg-amber-50 text-amber-600 border-amber-200",
                };
            }
            return {
                text: "Menunggu",
                className: "bg-blue-50 text-blue-600 border-blue-200",
            };
        }
        if (order.status === "confirmed")
            return {
                text: "Diproses",
                className: "bg-indigo-50 text-indigo-600 border-indigo-200",
            };
        if (order.status === "completed")
            return {
                text: "Selesai",
                className: "bg-emerald-50 text-emerald-600 border-emerald-200",
            };
        if (order.status === "cancelled")
            return {
                text: "Batal",
                className: "bg-red-50 text-red-600 border-red-200",
            };
        return {
            text: order.status,
            className: "bg-gray-50 text-gray-600 border-gray-200 capitalize",
        };
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col font-sans max-w-[480px] mx-auto relative shadow-md pb-10">
            <Head title={`Riwayat Pesanan - ${table.store.name}`} />

            <header className="h-[56px] bg-white border-b border-gray-100 flex items-center gap-3 sticky top-0 z-50 px-4">
                <Link
                    href={`/order/${tableCode}`}
                    className="p-2 -ml-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <IconChevronLeft className="w-5 h-5" />
                </Link>
                <h1 className="font-semibold text-gray-900 text-[15px]">Riwayat Pesanan</h1>
            </header>

            <div className="p-4 space-y-4">
                {/* Filter Chips */}
                <div className="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                    {["all", "active", "completed", "cancelled"].map((f) => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`h-[32px] px-4 rounded-full flex items-center justify-center text-[11px] font-semibold tracking-wide flex-shrink-0 transition-all ${filter === f ? "bg-gray-900 text-white shadow-sm" : "bg-white border border-gray-200 text-gray-600 hover:bg-gray-50"}`}
                        >
                            {f === "all"
                                ? "Semua"
                                : f === "active"
                                  ? "Aktif"
                                  : f === "completed"
                                    ? "Selesai"
                                    : "Dibatalkan"}
                        </button>
                    ))}
                </div>

                {loading ? (
                    <div className="flex flex-col items-center justify-center py-20 space-y-4">
                        <svg
                            className="animate-spin h-8 w-8 text-gray-900"
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
                        <span className="text-[11px] text-gray-500 font-medium tracking-wide">
                            Memuat riwayat pesanan...
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
                                " • " +
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
                                    className="bg-white border border-gray-100 rounded-xl p-4 shadow-sm space-y-4"
                                >
                                    <div className="flex items-center justify-between border-b border-gray-50 pb-3">
                                        <div>
                                            <span className="text-[10px] text-gray-500 font-medium uppercase tracking-wider block mb-0.5">
                                                No. Pesanan
                                            </span>
                                            <span className="font-mono font-bold text-gray-900 text-[13px] block">
                                                #{order.order_number}
                                            </span>
                                        </div>
                                        <span
                                            className={`text-[10px] font-semibold px-2.5 py-1 rounded-md border ${badge.className}`}
                                        >
                                            {badge.text}
                                        </span>
                                    </div>

                                    <div className="space-y-3">
                                        <div className="bg-gray-50/50 rounded-lg p-3 border border-gray-50">
                                            <span className="text-[10px] text-gray-500 font-medium uppercase tracking-wider block mb-1">
                                                Daftar Menu
                                            </span>
                                            <p className="text-[13px] text-gray-700 font-medium line-clamp-2 leading-relaxed">
                                                {itemsPreview || "Menu"}
                                            </p>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <span className="text-[10px] text-gray-500 font-medium uppercase tracking-wider block mb-0.5">
                                                    Waktu
                                                </span>
                                                <span className="text-[11px] text-gray-700 font-medium block leading-snug">
                                                    {displayDate}
                                                </span>
                                            </div>
                                            <div className="text-right">
                                                <span className="text-[10px] text-gray-500 font-medium uppercase tracking-wider block mb-0.5">
                                                    Total Belanja
                                                </span>
                                                <span className="text-gray-900 font-bold text-[13px] block leading-none">
                                                    Rp
                                                    {parseFloat(
                                                        order.grand_total,
                                                    ).toLocaleString("id-ID")}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between gap-3 pt-3 border-t border-gray-50">
                                        <div className="flex items-center gap-1.5 text-[11px] text-gray-500 font-medium">
                                            {order.payment_type === "cashless" ? (
                                                <IconPhone className="w-4 h-4 text-gray-400" />
                                            ) : (
                                                <IconCash className="w-4 h-4 text-gray-400" />
                                            )}
                                            <span>
                                                {order.payment_type === "cashless"
                                                    ? "Cashless"
                                                    : "Kasir"}
                                            </span>
                                        </div>
                                        <Link
                                            href={`/order/${tableCode}/status/${order.order_number}`}
                                            className="text-[11px] font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-900 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1"
                                        >
                                            Detail Pesanan
                                            <IconArrowRight className="w-3 h-3" />
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="text-center py-16 space-y-4">
                        <div className="w-48 h-48 mx-auto flex items-center justify-center opacity-80">
                            <img
                                src="/illustration/cart-empty.svg"
                                alt="Keranjang Kosong"
                                className="w-full h-full object-contain grayscale opacity-50"
                            />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 text-sm">
                                Belum Ada Pesanan
                            </h3>
                            <p className="text-[13px] text-gray-500 max-w-[240px] mx-auto leading-relaxed mt-2">
                                Anda belum memesan hidangan apapun. Yuk mulai pesan sekarang!
                            </p>
                        </div>
                        <div className="pt-4">
                            <Link
                                href={`/order/${tableCode}`}
                                className="inline-flex bg-gray-900 hover:bg-gray-800 transition-colors text-white font-medium text-[13px] px-6 py-2.5 rounded-xl shadow-sm"
                            >
                                Lihat Menu
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
