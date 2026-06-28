import React from "react";
import { Head, Link } from "@inertiajs/react";

export default function Profile({ tableCode }) {
    return (
        <div className="min-h-screen bg-gray-50 flex flex-col font-sans max-w-[480px] mx-auto relative shadow-xl">
            <Head title="Profil Pengguna" />

            {/* Header */}
            <header className="h-[60px] bg-white border-b border-gray-100 flex items-center justify-center sticky top-0 z-50 px-4">
                <Link
                    href={`/order/${tableCode}`}
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
                </Link>
                <h1 className="font-bold text-gray-900 text-sm">Profil</h1>
            </header>

            <div className="p-4 space-y-4">
                {/* User Card */}
                {/* <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-4 items-center">
                    <div className="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                    </div>
                    <div className="flex-1 space-y-2">
                        <h2 className="font-bold text-gray-900 text-sm">Masuk sebagai tamu</h2>
                        <button className="w-full bg-[#EF5350] hover:bg-[#D32F2F] text-white font-bold text-xs py-2 rounded-lg transition-colors shadow-sm">
                            Masuk
                        </button>
                    </div>
                </div> */}

                {/* Menu Items */}
                <div className="space-y-3">
                    <Link
                        href={`/order/${tableCode}/history`}
                        className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex justify-between items-center hover:border-gray-200 transition-colors"
                    >
                        <div className="flex items-center gap-3 text-gray-700">
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
                            >
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                                <rect width="8" height="4" x="8" y="3" rx="1" />
                                <path d="M9 14h6" />
                                <path d="M9 10h6" />
                            </svg>
                            <span className="text-sm font-semibold">
                                Riwayat Pesanan
                            </span>
                        </div>
                    </Link>

                    {/* <button className="w-full bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex justify-between items-center hover:border-gray-200 transition-colors text-left">
                        <div className="flex items-center gap-3 text-gray-700">
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
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                                <path d="M2 12h20" />
                            </svg>
                            <span className="text-sm font-semibold">
                                Bahasa
                            </span>
                        </div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="text-gray-400"
                        >
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button> */}

                    <Link
                        href={`/profile/${tableCode}/privacy`}
                        className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex justify-between items-center hover:border-gray-200 transition-colors"
                    >
                        <div className="flex items-center gap-3 text-gray-700">
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
                            >
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            </svg>
                            <span className="text-sm font-semibold">
                                Kebijakan Privasi
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    );
}
