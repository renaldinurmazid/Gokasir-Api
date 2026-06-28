import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Status({ tableCode, table, orderNumber, order }) {
    const [currentOrder, setCurrentOrder] = useState(order);

    useEffect(() => {
        let interval = setInterval(async () => {
            try {
                const res = await fetch(`/api/public/order/${tableCode}/status/${orderNumber}`);
                const result = await res.json();
                if (result.success) {
                    setCurrentOrder(result.data);
                    
                    // Self-healing history sync
                    try {
                        const historyList = JSON.parse(localStorage.getItem('gokasir_order_history') || '[]');
                        if (!historyList.some(item => item.order_number === orderNumber)) {
                            historyList.push({
                                order_number: orderNumber,
                                table_code: tableCode,
                                grand_total: result.data.grand_total,
                                payment_type: result.data.payment_type,
                                date: new Date().toISOString()
                            });
                            localStorage.setItem('gokasir_order_history', JSON.stringify(historyList));
                        }
                    } catch (e) {}

                    if (result.data.status === 'paid' || result.data.status === 'cancelled') {
                        clearInterval(interval);
                    }
                }
            } catch (error) {
                console.error("Failed to fetch order status");
            }
        }, 5000);

        return () => clearInterval(interval);
    }, [tableCode, orderNumber]);

    const isPending = currentOrder.status === 'pending';
    const isConfirmed = currentOrder.status === 'confirmed';
    const isPaid = currentOrder.status === 'paid';
    const isCancelled = currentOrder.status === 'cancelled';

    const getStatusInfo = () => {
        if (isPending) {
            if (currentOrder.payment_type === 'cashless' && (currentOrder.payment_status === 'pending_payment' || currentOrder.payment_status === 'unpaid')) {
                return { badge: '⏳ MENUNGGU PEMBAYARAN', color: 'bg-yellow-100 text-yellow-600 border-yellow-200', text: 'Silakan selesaikan pembayaran cashless Anda menggunakan QRIS atau Virtual Account di bawah.' };
            }
            return { badge: '⏳ MENUNGGU KONFIRMASI', color: 'bg-yellow-100 text-yellow-600 border-yellow-200 animate-pulse', text: 'Pesanan Anda telah diterima sistem kasir. Menunggu persetujuan kasir restoran.' };
        }
        if (isConfirmed) {
            return { badge: '🍳 SEDANG DIPROSES', color: 'bg-blue-100 text-blue-600 border-blue-200 animate-pulse', text: 'Hore! Pesanan Anda telah dikonfirmasi dan sedang diracik oleh juru masak dapur kami.' };
        }
        if (isPaid) {
            return { badge: '✓ PESANAN SELESAI', color: 'bg-green-100 text-green-600 border-green-200', text: 'Pembayaran tuntas! Pesanan Anda telah selesai diproses. Selamat menikmati hidangan!' };
        }
        if (isCancelled) {
            return { badge: '✗ DIBATALKAN', color: 'bg-red-100 text-red-600 border-red-200', text: 'Mohon maaf, pesanan ini telah dibatalkan oleh pihak kasir atau pelayan restoran.' };
        }
        return { badge: 'MEMUAT...', color: 'bg-gray-100 text-gray-500', text: '' };
    };

    const statusInfo = getStatusInfo();

    const isQris = currentOrder.payment_method?.toLowerCase().includes('qris') || 
                   currentOrder.payment_channel?.toLowerCase().includes('qris') || 
                   currentOrder.payment_name?.toLowerCase().includes('qris') ||
                   currentOrder.payment_url?.toLowerCase().includes('qris') ||
                   (currentOrder.payment_no && currentOrder.payment_no.startsWith('000201'));

    const copyToClipboard = () => {
        navigator.clipboard.writeText(currentOrder.payment_no).then(() => {
            alert('Tersalin!');
        });
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col font-sans max-w-[480px] mx-auto relative shadow-xl pb-10">
            <Head title={`Pesanan #${orderNumber}`} />
            
            <header className="h-[56px] bg-white border-b border-gray-100 flex items-center gap-3 sticky top-0 z-50 px-4 shadow-sm">
                <Link href={`/order/${tableCode}`} className="p-2 -ml-2 text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </Link>
                <h1 className="font-bold text-gray-900 text-base">Status Pesanan</h1>
            </header>

            <div className="p-4 space-y-4">
                {/* Order Number Card */}
                <div className="bg-red-50 border border-red-100 rounded-2xl p-4 flex justify-between items-center relative overflow-hidden">
                    <div className="absolute -right-4 -bottom-4 text-5xl opacity-10">📋</div>
                    <div>
                        <span className="text-xs text-[#B71C1C] font-semibold uppercase block">No. Pesanan</span>
                        <span className="font-mono font-black text-lg text-[#B71C1C] block">#{orderNumber}</span>
                    </div>
                    <div className="bg-[#B71C1C] text-white px-3 py-1.5 rounded-full text-[10px] font-bold">
                        Meja {table.name}
                    </div>
                </div>

                {/* Status Card */}
                <div className="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm text-center">
                    <span className="text-[10px] text-gray-400 font-bold uppercase mb-2 block">Status Saat Ini</span>
                    <span className={`inline-block text-[10px] font-extrabold px-3 py-1 rounded-full border ${statusInfo.color}`}>
                        {statusInfo.badge}
                    </span>
                    <p className="text-xs text-gray-500 font-medium mt-3 px-2">
                        {statusInfo.text}
                    </p>
                </div>

                {/* Estimation */}
                {!isPaid && !isCancelled && (
                    <div className="bg-orange-50 border border-orange-100 rounded-2xl p-4 text-orange-600 font-semibold text-xs flex items-center gap-3 shadow-sm">
                        <span className="text-base">⏱️</span> Estimasi siap dalam ~10–15 menit
                    </div>
                )}

                {/* Payment Instructions (if pending cashless) */}
                {isPending && currentOrder.payment_type === 'cashless' && (
                    <div className="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                        <div className="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                            <span className="text-[10px] font-bold text-gray-700 uppercase">Instruksi Pembayaran</span>
                            <span className="text-[9px] font-bold px-2 py-0.5 rounded bg-[#EF5350] text-white uppercase">{currentOrder.payment_name || 'Cashless'}</span>
                        </div>
                        <div className="p-4 flex flex-col items-center text-center">
                            {isQris ? (
                                <>
                                    <div className="bg-white border-2 border-gray-100 p-3 rounded-2xl shadow-sm mb-3">
                                        {(currentOrder.payment_no && currentOrder.payment_no.startsWith('000201')) ? (
                                            <img src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(currentOrder.payment_no)}`} alt="QRIS" className="w-44 h-44 object-contain" />
                                        ) : (currentOrder.payment_url && currentOrder.payment_url.startsWith('data:image')) ? (
                                            <img src={currentOrder.payment_url} alt="QRIS" className="w-44 h-44 object-contain" />
                                        ) : (
                                            <div className="w-44 h-44 flex flex-col items-center justify-center bg-gray-50 text-gray-400">
                                                <span className="text-3xl">📱</span>
                                                <span className="text-[10px] mt-2">Scan QRIS dari aplikasi bank Anda.</span>
                                            </div>
                                        )}
                                    </div>
                                    <p className="text-[10px] text-gray-400 px-4">Pindai kode QRIS di atas dengan aplikasi bank atau e-wallet (Gopay, OVO, Dana).</p>
                                </>
                            ) : (
                                <div className="w-full text-left space-y-3">
                                    <div className="bg-gray-50 border border-gray-100 rounded-xl p-3 text-center relative shadow-inner">
                                        <span className="text-[9px] font-bold text-gray-400 uppercase">Nomor Virtual Account</span>
                                        <div className="flex items-center justify-center gap-2 mt-1">
                                            <span className="text-base font-extrabold text-gray-800 tracking-wider">{currentOrder.payment_no || '-'}</span>
                                            <button onClick={copyToClipboard} className="text-[#EF5350] p-1"><svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg></button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Order Summary */}
                <div className="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <div className="bg-gray-50 px-4 py-3 border-b border-gray-100">
                        <span className="text-[10px] font-bold text-gray-700 uppercase">Rincian Belanja</span>
                    </div>
                    <div className="p-4 space-y-3">
                        {currentOrder.items.map((item, index) => (
                            <div key={item.id || item.product_id || index} className="flex justify-between items-start gap-4 text-sm">
                                <div className="flex-1">
                                    <span className="font-bold text-gray-800 text-xs">{item.product_name}</span>
                                    {item.notes && <span className="block text-[9px] bg-gray-100 text-gray-500 font-bold px-2 py-0.5 rounded mt-1 w-fit">✎ {item.notes}</span>}
                                    <span className="block text-[10px] text-gray-400 font-semibold mt-0.5">Rp{parseFloat(item.price).toLocaleString('id-ID')} x {parseFloat(item.qty)}</span>
                                </div>
                                <span className="font-extrabold text-gray-800 text-xs whitespace-nowrap">Rp{parseFloat(item.subtotal).toLocaleString('id-ID')}</span>
                            </div>
                        ))}
                    </div>
                    <div className="bg-gray-50 p-4 border-t border-gray-100 flex justify-between items-center">
                        <span className="text-[10px] font-bold text-gray-500 uppercase">Total Transaksi</span>
                        <span className="font-black text-sm text-[#EF5350]">Rp{parseFloat(currentOrder.grand_total).toLocaleString('id-ID')}</span>
                    </div>
                </div>

                {/* Additional CTAs */}
                <div className="pt-2 text-center space-y-4">
                    <Link href={`/order/${tableCode}`} className="block w-full border-2 border-[#EF5350] text-[#EF5350] hover:bg-red-50 font-bold py-3.5 rounded-xl text-xs">
                        Pesan Menu Tambahan
                    </Link>

                    {isPending && (
                        <a href={`/order/${tableCode}/cancel/${orderNumber}`} className="block w-full text-red-500 hover:bg-red-50 font-bold py-3.5 rounded-xl text-xs">
                            Batalkan Pesanan
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
