import React, { useState, useEffect } from "react";
import { Head, Link, router } from "@inertiajs/react";
import useCart from "../../hooks/useCart";

export default function Checkout({ tableCode, table }) {
    const { cart, addToCart, updateQty, updateNote, totalQty, subtotal, clearCart } =
        useCart(tableCode);
    const [step, setStep] = useState(1);

    // Tax Calculation
    const taxSetting = table.store.tenant?.tax_setting || table.store.tenant?.taxSetting;
    const taxRate = taxSetting?.tax_enabled
        ? parseFloat(taxSetting.tax_rate || 0)
        : 0;
    const taxName = taxSetting?.tax_enabled
        ? taxSetting.tax_name || "Pajak"
        : "Pajak";
    const taxAmount = Math.round(subtotal * (taxRate / 100));
    const grandTotal = subtotal + taxAmount;

    // Form Data
    const [formData, setFormData] = useState({
        customer_name: "",
        customer_phone: "",
        notes: "",
        payment_type: "cash",
        payment_method: "",
        payment_channel: "",
        pax: 1,
    });

    const [paymentChannels, setPaymentChannels] = useState([]);
    const [isProcessing, setIsProcessing] = useState(false);

    useEffect(() => {
        // Load saved info
        const savedName = localStorage.getItem(`customer_name_${tableCode}`);
        const savedPhone = localStorage.getItem(`customer_phone_${tableCode}`);
        if (savedName)
            setFormData((prev) => ({ ...prev, customer_name: savedName }));
        if (savedPhone)
            setFormData((prev) => ({ ...prev, customer_phone: savedPhone }));

        // Fetch payment channels
        fetch("/api/public/payment-methods")
            .then((res) => res.json())
            .then((result) => {
                if (result.success) {
                    const data =
                        result.data.Data || result.data.data || result.data;
                    setPaymentChannels(Array.isArray(data) ? data : []);
                }
            })
            .catch(console.error);
    }, [tableCode]);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handlePaymentSelection = (type, method = "", channel = "") => {
        setFormData((prev) => ({
            ...prev,
            payment_type: type,
            payment_method: method,
            payment_channel: channel,
        }));
    };

    const submitOrder = async () => {
        if (!formData.customer_name.trim()) {
            alert("Nama Lengkap wajib diisi.");
            return;
        }
        if (
            formData.payment_type === "cashless" &&
            !formData.customer_phone.trim()
        ) {
            alert("Nomor Handphone wajib diisi untuk transaksi cashless.");
            return;
        }

        setIsProcessing(true);

        try {
            // 1. Session Start
            const sessionRes = await fetch(
                `/api/public/order/${tableCode}/session`,
                {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        pax: parseInt(formData.pax),
                        customer_name: formData.customer_name.trim(),
                        customer_phone: formData.customer_phone.trim() || null,
                    }),
                },
            );
            const sessionResult = await sessionRes.json();

            if (!sessionResult.success) {
                alert("Gagal memulai sesi order: " + sessionResult.message);
                setIsProcessing(false);
                return;
            }

            const token = sessionResult.data.session_token;
            localStorage.setItem(`session_token_${tableCode}`, token);
            localStorage.setItem(
                `customer_name_${tableCode}`,
                formData.customer_name.trim(),
            );
            if (formData.customer_phone.trim()) {
                localStorage.setItem(
                    `customer_phone_${tableCode}`,
                    formData.customer_phone.trim(),
                );
            }

            // 2. Place Order
            const payload = {
                session_token: token,
                payment_type: formData.payment_type,
                notes: formData.notes.trim() || null,
                items: cart.map((item) => ({
                    product_id: item.product_id,
                    qty: item.qty,
                    notes: item.note || null,
                })),
            };

            if (formData.payment_type === "cashless") {
                payload.payment_method = formData.payment_method;
                payload.payment_channel = formData.payment_channel;
                payload.customer_phone = formData.customer_phone.trim();
            }

            const orderRes = await fetch(
                `/api/public/order/${tableCode}/place`,
                {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload),
                },
            );
            const orderResult = await orderRes.json();

            if (orderResult.success) {
                // Save history
                try {
                    const historyList = JSON.parse(
                        localStorage.getItem("gokasir_order_history") || "[]",
                    );
                    if (
                        !historyList.some(
                            (item) =>
                                item.order_number ===
                                orderResult.data.order_number,
                        )
                    ) {
                        historyList.push({
                            order_number: orderResult.data.order_number,
                            table_code: tableCode,
                            grand_total: orderResult.data.grand_total,
                            payment_type:
                                orderResult.data.payment_type ||
                                formData.payment_type,
                            date: new Date().toISOString(),
                        });
                        localStorage.setItem(
                            "gokasir_order_history",
                            JSON.stringify(historyList),
                        );
                    }
                } catch (e) {
                    console.error(
                        "Gagal menyimpan riwayat pesanan ke localStorage",
                        e,
                    );
                }

                clearCart();
                router.visit(
                    `/order/${tableCode}/status/${orderResult.data.order_number}`,
                );
            } else {
                alert("Gagal mengirim pesanan: " + orderResult.message);
                setIsProcessing(false);
            }
        } catch (err) {
            console.error(err);
            alert("Terjadi kesalahan jaringan.");
            setIsProcessing(false);
        }
    };

    if (cart.length === 0) {
        return (
            <div className="min-h-screen bg-gray-50 flex flex-col font-sans max-w-[480px] mx-auto relative shadow-xl items-center justify-center p-6 text-center">
                <Head title="Keranjang Kosong" />
                <div className="w-120 h-120 flex items-center justify-center mb-2">
                    <img src="/illustration/cart-empty.svg" alt="Keranjang Kosong" className="w-full h-full object-contain" />
                </div>
                <h3 className="font-bold text-gray-800 mb-2">
                    Keranjang Belanja Kosong
                </h3>
                <p className="text-gray-500 text-sm mb-6">
                    Silakan pilih menu masakan atau minuman terlebih dahulu.
                </p>
                <Link
                    href={`/order/${tableCode}`}
                    className="bg-[#EF5350] text-white px-6 py-3 rounded-xl font-bold shadow-md"
                >
                    Kembali ke Menu
                </Link>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col font-sans pb-28 max-w-[480px] mx-auto relative shadow-xl">
            <Head title="Checkout Pesanan" />

            {/* Header */}
            <header className="h-[56px] bg-white border-b border-gray-100 flex items-center justify-center sticky top-0 z-50 px-4">
                <button
                    onClick={() =>
                        step === 2
                            ? setStep(1)
                            : router.visit(`/order/${tableCode}`)
                    }
                    className="absolute left-4 p-2 -ml-2 text-gray-800"
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
                </button>
                <h1 className="font-bold text-gray-900 text-base">
                    {step === 1 ? "Pesanan" : "Pembayaran"}
                </h1>
            </header>

            {/* Content Step 1: Order Summary */}
            {step === 1 && (
                <div className="p-4 space-y-4">
                    {/* Order Type */}
                    <div className="bg-red-50/50 border border-red-100 rounded-xl p-3 flex justify-between items-center">
                        <span className="text-sm font-semibold text-gray-700">
                            Tipe Pemesanan
                        </span>
                        <div className="flex items-center gap-1.5 text-sm font-bold text-gray-900">
                            Makan di tempat
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="3"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="text-gray-900"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>
                    </div>

                    {/* Order Items */}
                    <div>
                        <div className="flex justify-between items-center mb-3">
                            <h3 className="font-bold text-gray-900 text-sm">
                                Item yang dipesan ({totalQty})
                            </h3>
                            <Link
                                href={`/order/${tableCode}`}
                                className="text-[#EF5350] border border-[#EF5350] px-3 py-1 rounded-full text-[11px] font-bold"
                            >
                                + Tambah Item
                            </Link>
                        </div>
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-5">
                            {cart.map((item) => (
                                <div
                                    key={item.product_id}
                                    className="flex gap-3"
                                >
                                    <div className="flex-1">
                                        <div className="flex justify-between items-start">
                                            <h4 className="font-bold text-sm text-gray-800 leading-snug">
                                                {item.name}
                                            </h4>
                                        </div>
                                        <div className="text-[12px] text-gray-500 mt-1 flex items-center gap-1.5 border-b border-dashed border-gray-200 pb-1 w-full max-w-[200px]">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="12"
                                                height="12"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                className="text-gray-400"
                                            >
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                            <input 
                                                type="text"
                                                value={item.note || ''}
                                                onChange={(e) => updateNote(item.product_id, e.target.value)}
                                                placeholder="Tambah catatan..."
                                                className="border-none bg-transparent p-0 focus:ring-0 w-full text-[12px] italic placeholder-gray-400 text-gray-600 h-5"
                                            />
                                        </div>
                                        <div className="flex justify-between items-center mt-3">
                                            <span className="font-bold text-sm text-[#EF5350]">
                                                Rp
                                                {(
                                                    item.price * item.qty
                                                ).toLocaleString("id-ID")}
                                            </span>
                                            <div className="flex items-center justify-between border border-gray-200 rounded-lg overflow-hidden h-7 w-24">
                                                <button
                                                    onClick={() =>
                                                        updateQty(
                                                            item.product_id,
                                                            item.qty - 1,
                                                        )
                                                    }
                                                    className="w-8 flex justify-center text-gray-600 font-bold bg-gray-50 h-full items-center active:bg-gray-200"
                                                >
                                                    -
                                                </button>
                                                <span className="text-[11px] font-bold px-2">
                                                    {item.qty}
                                                </span>
                                                <button
                                                    onClick={() =>
                                                        updateQty(
                                                            item.product_id,
                                                            item.qty + 1,
                                                        )
                                                    }
                                                    className="w-8 flex justify-center text-gray-600 font-bold bg-gray-50 h-full items-center active:bg-gray-200"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            <div className="border-t border-dashed border-gray-200 pt-4">
                                <label className="flex items-center gap-2 text-sm text-gray-500 font-medium">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                    <input
                                        type="text"
                                        placeholder="Tambah catatan lainnya"
                                        name="notes"
                                        value={formData.notes}
                                        onChange={handleInputChange}
                                        className="border-none bg-transparent flex-1 focus:ring-0 text-sm px-0 placeholder-gray-400"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Payment Summary */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <h3 className="font-bold text-gray-900 text-sm text-center mb-3">
                            Rincian Pembayaran
                        </h3>
                        <div className="space-y-2">
                            <div className="flex justify-between text-xs text-gray-600">
                                <span>Subtotal ({totalQty} menu)</span>
                                <span className="font-bold text-gray-800">
                                    Rp{subtotal.toLocaleString("id-ID")}
                                </span>
                            </div>
                            {taxRate > 0 && (
                                <div className="flex justify-between text-xs text-gray-600">
                                    <span>{taxName} ({taxRate}%)</span>
                                    <span className="font-bold text-gray-800">
                                        Rp{taxAmount.toLocaleString("id-ID")}
                                    </span>
                                </div>
                            )}
                            <div className="flex justify-between text-sm font-bold text-gray-900 pt-3 border-t border-dashed border-gray-200 mt-2">
                                <span>Total</span>
                                <span className="text-[#EF5350]">
                                    Rp{grandTotal.toLocaleString("id-ID")}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Content Step 2: Payment */}
            {step === 2 && (
                <div className="p-4 space-y-4">
                    <div className="bg-red-50/50 border border-red-100 rounded-xl p-3 flex justify-between items-center">
                        <span className="text-sm font-semibold text-gray-700">
                            Tipe Pemesanan
                        </span>
                        <div className="flex items-center gap-1.5 text-sm font-bold text-gray-900">
                            Makan di tempat
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="3"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="text-gray-900"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>
                    </div>

                    <div className="space-y-4 mt-6">
                        <h3 className="font-bold text-gray-900 text-sm">
                            Informasi Pemesan
                        </h3>
                        <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                Nama Lengkap
                                <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="absolute left-3 top-3 text-gray-400"
                                >
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <input
                                    type="text"
                                    name="customer_name"
                                    required
                                    value={formData.customer_name}
                                    onChange={handleInputChange}
                                    placeholder="Nama Lengkap"
                                    className="w-full border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-1 focus:ring-[#EF5350] focus:border-[#EF5350] outline-none"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                Nomor Ponsel (untuk info promo)
                            </label>
                            <div className="relative">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="absolute left-3 top-3 text-gray-400"
                                >
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                </svg>
                                <input
                                    type="tel"
                                    name="customer_phone"
                                    value={formData.customer_phone}
                                    onChange={handleInputChange}
                                    placeholder="Nomor Ponsel"
                                    className="w-full border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-1 focus:ring-[#EF5350] focus:border-[#EF5350] outline-none"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                Kirim struk ke email
                            </label>
                            <div className="relative">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="absolute left-3 top-3 text-gray-400"
                                >
                                    <rect
                                        width="20"
                                        height="16"
                                        x="2"
                                        y="4"
                                        rx="2"
                                    />
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                </svg>
                                <input
                                    type="email"
                                    placeholder="Email"
                                    className="w-full border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-1 focus:ring-[#EF5350] focus:border-[#EF5350] outline-none"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1">
                                Nomor Meja
                                <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="absolute left-3 top-3 text-gray-400"
                                >
                                    <path d="M12 2v20" />
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                </svg>
                                <input
                                    type="text"
                                    readOnly
                                    value={table.name}
                                    className="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-600 outline-none"
                                />
                            </div>
                        </div>
                    </div>
                    {/* <div className="border-t border-gray-100 my-4 py-4">
                        <p className="text-xs text-gray-500 font-medium mb-1">
                            Kamu pesan dari
                        </p>
                        <p className="text-sm font-bold text-gray-800">
                            {table.store.name}
                        </p>
                    </div> */}
                    <div className="space-y-3">
                        <h3 className="font-bold text-gray-900 text-sm">
                            Metode Pembayaran
                        </h3>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                onClick={() =>
                                    handlePaymentSelection("cashless")
                                }
                                className={`flex items-center justify-center gap-2 py-3 rounded-xl border transition-all ${formData.payment_type === "cashless" ? "border-[#EF5350] bg-red-50 text-[#EF5350]" : "border-gray-200 bg-white text-gray-600"}`}
                            >
                                <span className="text-lg leading-none">📱</span>
                                <span className="text-xs font-bold">
                                    Pembayaran Online
                                </span>
                            </button>
                            <button
                                onClick={() => handlePaymentSelection("cash")}
                                className={`flex items-center justify-center gap-2 py-3 rounded-xl border transition-all ${formData.payment_type === "cash" ? "border-[#EF5350] bg-red-50 text-[#EF5350]" : "border-gray-200 bg-white text-gray-600"}`}
                            >
                                <span className="text-lg leading-none">💵</span>
                                <span className="text-xs font-bold">
                                    Bayar di Kasir
                                </span>
                            </button>
                        </div>
                    </div>

                    {formData.payment_type === "cashless" && (
                        <div className="mt-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
                            <h3 className="font-bold text-gray-800 text-sm mb-3">
                                Selesaikan Pembayaran
                            </h3>
                            <div className="space-y-2">
                                {paymentChannels.map((cat, catIdx) => (
                                    <React.Fragment key={cat.Code || cat.code || `cat-${catIdx}`}>
                                        {(
                                            cat.Channels ||
                                            cat.channels ||
                                            []
                                        ).map((ch, chIdx) => {
                                            const code = ch.Code || ch.code;
                                            const name = ch.Name || ch.name;
                                            const isSelected =
                                                formData.payment_method ===
                                                    (cat.Code || cat.code) &&
                                                formData.payment_channel ===
                                                    code;
                                            return (
                                                <button
                                                    key={code || `ch-${chIdx}`}
                                                    onClick={() =>
                                                        handlePaymentSelection(
                                                            "cashless",
                                                            cat.Code ||
                                                                cat.code,
                                                            code,
                                                        )
                                                    }
                                                    className={`w-full flex items-center justify-between p-3 rounded-lg border transition-all ${isSelected ? "border-[#EF5350] bg-red-50/30" : "border-gray-100 hover:bg-gray-50"}`}
                                                >
                                                    <span className="text-xs font-bold text-gray-700">
                                                        {name}
                                                    </span>
                                                    <div
                                                        className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${isSelected ? "border-[#EF5350]" : "border-gray-300"}`}
                                                    >
                                                        {isSelected && (
                                                            <div className="w-2 h-2 rounded-full bg-[#EF5350]" />
                                                        )}
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </React.Fragment>
                                ))}
                                {paymentChannels.length === 0 && (
                                    <div className="text-xs text-gray-500 text-center py-2">
                                        Memuat metode pembayaran...
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Bottom Actions */}
            <div className="fixed bottom-0 inset-x-0 mx-auto max-w-[480px] bg-white border-t border-gray-100 shadow-md p-4 z-50 rounded-t-3xl">
                {step === 1 ? (
                    <div className="flex flex-col gap-3">
                        <div className="flex justify-between items-center mt-2">
                            <div>
                                <p className="text-[10px] text-gray-500 font-medium">
                                    Total Pembayaran
                                </p>
                                <p className="text-lg font-black text-gray-900 leading-none mt-1">
                                    Rp{grandTotal.toLocaleString("id-ID")}
                                </p>
                            </div>
                            <button
                                onClick={() => setStep(2)}
                                className="bg-[#EF5350] hover:bg-[#D32F2F] text-white px-6 py-3 rounded-xl font-bold shadow-md active:scale-95 transition-transform text-sm"
                            >
                                Lanjut Pembayaran
                            </button>
                        </div>
                    </div>
                ) : (
                    <div className="flex justify-between items-center">
                        <div>
                            <p className="text-[10px] text-gray-500 font-medium flex items-center gap-1">
                                Total Pembayaran{" "}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="12"
                                    height="12"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="m18 15-6-6-6 6" />
                                </svg>
                            </p>
                            <p className="text-lg font-black text-gray-900 leading-none mt-1">
                                Rp{grandTotal.toLocaleString("id-ID")}
                            </p>
                        </div>
                        <button
                            disabled={isProcessing}
                            onClick={submitOrder}
                            className="bg-[#EF5350] hover:bg-[#D32F2F] text-white px-10 py-3 rounded-xl font-bold shadow-md active:scale-95 transition-transform text-sm disabled:opacity-70 flex items-center gap-2"
                        >
                            {isProcessing && (
                                <svg
                                    className="animate-spin h-4 w-4 text-white"
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
                            )}
                            Bayar
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
