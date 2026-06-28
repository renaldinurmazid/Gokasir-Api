import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Privacy({ tableCode }) {
    return (
        <div className="min-h-screen bg-white flex flex-col font-sans max-w-[480px] mx-auto relative shadow-xl">
            <Head title="Kebijakan Privasi" />

            {/* Header */}
            <header className="h-[60px] bg-white border-b border-gray-100 flex items-center justify-center sticky top-0 z-50 px-4">
                <Link href={`/profile/${tableCode}`} className="absolute left-4 p-2 -ml-2 text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </Link>
                <h1 className="font-bold text-gray-900 text-sm">Kebijakan Privasi</h1>
            </header>

            <div className="p-4 space-y-4 text-sm text-gray-700 leading-relaxed">
                <h2 className="font-bold text-gray-900 text-lg">Kebijakan Privasi GoKasir</h2>
                <p>
                    GoKasir menghormati privasi Anda. Halaman ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda saat menggunakan layanan pemesanan restoran kami.
                </p>
                <h3 className="font-bold text-gray-900 mt-4">1. Informasi yang Kami Kumpulkan</h3>
                <p>
                    Kami hanya mengumpulkan informasi yang Anda berikan secara sukarela, seperti nama dan nomor telepon saat Anda melakukan pemesanan (khususnya untuk pembayaran online/cashless).
                </p>
                <h3 className="font-bold text-gray-900 mt-4">2. Penggunaan Informasi</h3>
                <p>
                    Informasi yang kami kumpulkan digunakan semata-mata untuk memproses pesanan Anda, mengirimkan pembaruan status pesanan, dan mengidentifikasi Anda di restoran.
                </p>
                <h3 className="font-bold text-gray-900 mt-4">3. Keamanan Data</h3>
                <p>
                    Kami menerapkan standar keamanan industri untuk melindungi informasi Anda dari akses yang tidak sah. Data sesi Anda juga akan berakhir setelah pesanan selesai.
                </p>
            </div>
        </div>
    );
}
