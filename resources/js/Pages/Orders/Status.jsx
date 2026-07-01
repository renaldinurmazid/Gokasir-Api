import React, { useState, useEffect } from "react";
import { Head, Link } from "@inertiajs/react";

// --- Icons ---
const IconChevronLeft = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        className={className}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M15 19l-7-7 7-7"
        />
    </svg>
);

const IconClock = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        className={className}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </svg>
);

const IconCooking = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        className={className}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V4h-4v4a2 2 0 002 2zm3 0h5a2 2 0 012 2v8a2 2 0 01-2 2H9a2 2 0 01-2-2v-8a2 2 0 012-2z"
        />
    </svg>
);

const IconCheck = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        className={className}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </svg>
);

const IconCancel = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        className={className}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </svg>
);

const IconCopy = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        className={className}
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
        />
    </svg>
);

export default function Status({ tableCode, table, orderNumber, order }) {
    const [currentOrder, setCurrentOrder] = useState(order);

    useEffect(() => {
        let interval = setInterval(async () => {
            try {
                const res = await fetch(
                    `/api/public/order/${tableCode}/status/${orderNumber}`,
                );
                const result = await res.json();
                if (result.success) {
                    setCurrentOrder(result.data);

                    try {
                        const historyList = JSON.parse(
                            localStorage.getItem("gokasir_order_history") ||
                                "[]",
                        );
                        if (
                            !historyList.some(
                                (item) => item.order_number === orderNumber,
                            )
                        ) {
                            historyList.push({
                                order_number: orderNumber,
                                table_code: tableCode,
                                grand_total: result.data.grand_total,
                                payment_type: result.data.payment_type,
                                date: new Date().toISOString(),
                            });
                            localStorage.setItem(
                                "gokasir_order_history",
                                JSON.stringify(historyList),
                            );
                        }
                    } catch (e) {}

                    if (
                        result.data.status === "completed" ||
                        result.data.status === "cancelled"
                    ) {
                        clearInterval(interval);
                    }
                }
            } catch (error) {
                console.error("Failed to fetch order status");
            }
        }, 5000);

        return () => clearInterval(interval);
    }, [tableCode, orderNumber]);

    const isPending = currentOrder.status === "pending";
    const isConfirmed = currentOrder.status === "confirmed";
    const isPaid = currentOrder.status === "completed";
    const isCancelled = currentOrder.status === "cancelled";

    const getStatusInfo = () => {
        if (isPending) {
            if (
                currentOrder.payment_type === "cashless" &&
                (currentOrder.payment_status === "pending_payment" ||
                    currentOrder.payment_status === "unpaid")
            ) {
                return {
                    icon: IconClock,
                    title: "Menunggu Pembayaran",
                    color: "text-amber-600",
                    bg: "bg-amber-50",
                    borderColor: "border-amber-200",
                    text: "Silakan selesaikan pembayaran Anda menggunakan QRIS atau Virtual Account di bawah.",
                };
            }
            return {
                icon: IconClock,
                title: "Menunggu Konfirmasi",
                color: "text-blue-600",
                bg: "bg-blue-50",
                borderColor: "border-blue-200",
                text: "Pesanan telah diterima. Menunggu persetujuan kasir restoran.",
            };
        }
        if (isConfirmed) {
            return {
                icon: IconCooking,
                title: "Sedang Diproses",
                color: "text-indigo-600",
                bg: "bg-indigo-50",
                borderColor: "border-indigo-200",
                text: "Pesanan Anda telah dikonfirmasi dan sedang disiapkan.",
            };
        }
        if (isPaid) {
            return {
                icon: IconCheck,
                title: "Pesanan Selesai",
                color: "text-emerald-600",
                bg: "bg-emerald-50",
                borderColor: "border-emerald-200",
                text: "Pembayaran berhasil. Pesanan Anda telah selesai diproses.",
            };
        }
        if (isCancelled) {
            return {
                icon: IconCancel,
                title: "Dibatalkan",
                color: "text-red-600",
                bg: "bg-red-50",
                borderColor: "border-red-200",
                text: "Pesanan ini telah dibatalkan.",
            };
        }
        return {
            icon: IconClock,
            title: "Memuat...",
            color: "text-gray-500",
            bg: "bg-gray-50",
            borderColor: "border-gray-200",
            text: "",
        };
    };

    const statusInfo = getStatusInfo();
    const StatusIcon = statusInfo.icon;

    const isQris =
        currentOrder.payment_method?.toLowerCase().includes("qris") ||
        currentOrder.payment_channel?.toLowerCase().includes("qris") ||
        currentOrder.payment_name?.toLowerCase().includes("qris") ||
        currentOrder.payment_url?.toLowerCase().includes("qris") ||
        (currentOrder.payment_no &&
            currentOrder.payment_no.startsWith("000201"));

    const copyToClipboard = () => {
        navigator.clipboard.writeText(currentOrder.payment_no).then(() => {
            alert("Tersalin!");
        });
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col font-sans max-w-[480px] mx-auto relative shadow-md pb-10">
            <Head title={`Pesanan #${orderNumber}`} />

            <header className="h-[56px] bg-white border-b border-gray-100 flex items-center gap-3 sticky top-0 z-50 px-4">
                <Link
                    href={`/order/${tableCode}`}
                    className="p-2 -ml-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <IconChevronLeft className="w-5 h-5" />
                </Link>
                <h1 className="font-semibold text-gray-900 text-[15px]">
                    Status Pesanan
                </h1>
            </header>

            <div className="p-4 space-y-4">
                {/* Order Summary Header */}
                <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <p className="text-[11px] text-gray-500 font-medium uppercase tracking-wider mb-1">
                            No. Pesanan
                        </p>
                        <p className="font-mono font-bold text-gray-900 text-lg">
                            #{orderNumber}
                        </p>
                    </div>
                    <div className="bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">
                        Meja {table.name}
                    </div>
                </div>

                {/* Status Indicator */}
                <div
                    className={`rounded-xl p-5 border ${statusInfo.borderColor} ${statusInfo.bg} flex flex-col items-center text-center`}
                >
                    <StatusIcon
                        className={`w-10 h-10 ${statusInfo.color} mb-3`}
                    />
                    <h2
                        className={`font-semibold text-base mb-1 ${statusInfo.color}`}
                    >
                        {statusInfo.title}
                    </h2>
                    <p
                        className={`text-[13px] ${statusInfo.color} opacity-90 leading-relaxed max-w-[260px]`}
                    >
                        {statusInfo.text}
                    </p>
                </div>

                {/* Payment Section */}
                {isPending && currentOrder.payment_type === "cashless" && (
                    <div className="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
                        <div className="bg-gray-50/50 px-4 py-3 border-b border-gray-50 flex justify-between items-center">
                            <span className="text-xs font-medium text-gray-700">
                                Instruksi Pembayaran
                            </span>
                            <span className="text-[11px] font-semibold text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                {currentOrder.payment_name || "Cashless"}
                            </span>
                        </div>
                        <div className="p-5 flex flex-col items-center">
                            {isQris ? (
                                <div className="text-center w-full">
                                    <div className="bg-white border border-gray-100 p-4 rounded-xl shadow-sm mb-4 inline-block">
                                        {currentOrder.payment_no &&
                                        currentOrder.payment_no.startsWith(
                                            "000201",
                                        ) ? (
                                            <img
                                                src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(currentOrder.payment_no)}`}
                                                alt="QRIS"
                                                className="w-40 h-40 object-contain mx-auto"
                                            />
                                        ) : currentOrder.payment_url &&
                                          currentOrder.payment_url.startsWith(
                                              "data:image",
                                          ) ? (
                                            <img
                                                src={currentOrder.payment_url}
                                                alt="QRIS"
                                                className="w-40 h-40 object-contain mx-auto"
                                            />
                                        ) : (
                                            <div className="w-40 h-40 flex flex-col items-center justify-center bg-gray-50 text-gray-400 rounded-lg">
                                                <IconClock className="w-8 h-8 mb-2 opacity-50" />
                                                <span className="text-[11px] px-2 text-center">
                                                    Menunggu QR Code
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    <p className="text-[13px] text-gray-500">
                                        Pindai kode QRIS menggunakan aplikasi
                                        bank atau e-wallet (Gopay, OVO, Dana).
                                    </p>
                                </div>
                            ) : (
                                <div className="w-full">
                                    <div className="bg-gray-50 border border-gray-100 rounded-lg p-4 text-center">
                                        <p className="text-[11px] text-gray-500 font-medium mb-2">
                                            Nomor Virtual Account
                                        </p>
                                        <div className="flex items-center justify-center gap-3">
                                            <span className="text-xl font-bold text-gray-900 tracking-wide">
                                                {currentOrder.payment_no || "-"}
                                            </span>
                                            <button
                                                onClick={copyToClipboard}
                                                className="text-gray-400 hover:text-gray-700 transition-colors p-1"
                                                title="Salin nomor"
                                            >
                                                <IconCopy className="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Order Details */}
                <div className="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
                    <div className="bg-gray-50/50 px-4 py-3 border-b border-gray-50">
                        <span className="text-xs font-medium text-gray-700">
                            Rincian Belanja
                        </span>
                    </div>
                    <div className="p-4 space-y-4">
                        {currentOrder.items.map((item, index) => (
                            <div
                                key={item.id || item.product_id || index}
                                className="flex justify-between items-start gap-4"
                            >
                                <div className="flex-1">
                                    <span className="font-medium text-gray-900 text-[13px]">
                                        {item.product_name}
                                    </span>
                                    {item.notes && (
                                        <p className="text-[11px] text-gray-500 mt-1 italic">
                                            Catatan: {item.notes}
                                        </p>
                                    )}
                                    <p className="text-[11px] text-gray-500 mt-1">
                                        Rp
                                        {parseFloat(item.price).toLocaleString(
                                            "id-ID",
                                        )}{" "}
                                        × {parseFloat(item.qty)}
                                    </p>
                                </div>
                                <span className="font-semibold text-gray-900 text-[13px]">
                                    Rp
                                    {parseFloat(item.subtotal).toLocaleString(
                                        "id-ID",
                                    )}
                                </span>
                            </div>
                        ))}
                    </div>
                    <div className="bg-gray-50 p-4 border-t border-gray-50 flex justify-between items-center">
                        <span className="text-[13px] font-medium text-gray-600">
                            Total Transaksi
                        </span>
                        <span className="font-bold text-[15px] text-gray-900">
                            Rp
                            {parseFloat(
                                currentOrder.grand_total,
                            ).toLocaleString("id-ID")}
                        </span>
                    </div>
                </div>

                {/* Actions */}
                <div className="pt-2 space-y-3">
                    <Link
                        href={`/order/${tableCode}`}
                        className="flex w-full items-center justify-center bg-red-600 text-white font-medium py-3.5 rounded-xl text-[13px] hover:bg-gray-800 transition-colors"
                    >
                        Pesan Menu Tambahan
                    </Link>

                    {isPending && (
                        <a
                            href={`/order/${tableCode}/cancel/${orderNumber}`}
                            className="flex w-full items-center justify-center bg-white border border-gray-200 text-red-600 font-medium py-3.5 rounded-xl text-[13px] hover:bg-red-50 hover:border-red-100 transition-colors"
                        >
                            Batalkan Pesanan
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
