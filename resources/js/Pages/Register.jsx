import React, { useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "axios";

export default function Register() {
    const [step, setStep] = useState(1);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});

    // Step 1: Registration Form State
    const [formData, setFormData] = useState({
        business_name: "",
        business_type: "",
        name: "",
        email: "",
        phone: "",
        store_name: "",
        password: "",
        password_confirmation: "",
        referral_code: "",
    });

    // Step 2: OTP State
    const [otp, setOtp] = useState("");

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
        // Clear error when user types
        if (errors[name]) {
            setErrors((prev) => ({ ...prev, [name]: "" }));
        }
    };

    const handleRegisterSubmit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const response = await axios.post("/api/auth/register", formData);
            if (response.status === 201) {
                // Registration successful, move to OTP step
                setStep(2);
            }
        } catch (error) {
            if (
                error.response &&
                error.response.data &&
                error.response.data.errors
            ) {
                setErrors(error.response.data.errors);
            } else if (
                error.response &&
                error.response.data &&
                error.response.data.message
            ) {
                setErrors({ general: error.response.data.message });
            } else {
                setErrors({ general: "Terjadi kesalahan. Silakan coba lagi." });
            }
        } finally {
            setProcessing(false);
        }
    };

    const handleOtpSubmit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const response = await axios.post("/api/auth/verify-otp", {
                phone: formData.phone,
                otp_code: otp,
            });

            if (response.status === 200) {
                // OTP successful, move to success step
                setStep(3);
            }
        } catch (error) {
            if (
                error.response &&
                error.response.data &&
                error.response.data.message
            ) {
                setErrors({ otp: error.response.data.message });
            } else {
                setErrors({ otp: "Kode OTP tidak valid atau kadaluarsa." });
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
            <Head title="Daftar Akun GoKasir" />

            <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
                <Link href="/" className="inline-flex items-center gap-2 mb-4">
                    <svg
                        className="w-10 h-10"
                        viewBox="0 0 42 42"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M5 6H9L12.5 23H31L35 11H13"
                            stroke="#E31B23"
                            strokeWidth="3.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                        <circle cx="15" cy="30" r="3" fill="#E31B23" />
                        <circle cx="28" cy="30" r="3" fill="#E31B23" />
                        <circle cx="22" cy="16" r="6" fill="#E31B23" />
                        <path
                            d="M19.5 16L21 17.5L24.5 14"
                            stroke="#FFFFFF"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                        <rect
                            x="30"
                            y="3"
                            width="4"
                            height="4"
                            fill="#F5A623"
                        />
                        <rect
                            x="34"
                            y="7"
                            width="4"
                            height="4"
                            fill="#F5A623"
                        />
                        <rect
                            x="30"
                            y="7"
                            width="4"
                            height="4"
                            fill="#F5A623"
                            fillOpacity="0.5"
                        />
                    </svg>
                    <div className="text-left leading-none">
                        <span className="text-xl font-black tracking-tight">
                            <span className="text-brand-500">Go</span>
                            <span className="text-slate-800">Kasir</span>
                        </span>
                    </div>
                </Link>
                <h2 className="mt-2 text-center text-3xl font-extrabold text-gray-900">
                    {step === 1 && "Buat Akun Bisnis"}
                    {step === 2 && "Verifikasi Nomor HP"}
                    {step === 3 && "Pendaftaran Berhasil!"}
                </h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    {step === 1 && <>Kembangkan bisnis anda bersama kami</>}
                    {step === 2 &&
                        "Masukkan 6 digit kode OTP yang kami kirim ke WhatsApp Anda."}
                    {step === 3 && "Akun GoKasir Anda siap digunakan."}
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-xl">
                <div className="bg-white py-8 px-4 shadow-xl shadow-brand-500/5 sm:rounded-2xl sm:px-10 border border-gray-100 relative overflow-hidden">
                    {/* Decorative Blob */}
                    <div className="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 rounded-full bg-brand-50 opacity-50 blur-2xl pointer-events-none"></div>

                    {errors.general && (
                        <div className="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-start gap-3 text-red-600">
                            <svg
                                className="w-5 h-5 mt-0.5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <span className="text-sm font-medium">
                                {errors.general}
                            </span>
                        </div>
                    )}

                    {step === 1 && (
                        <form
                            className="space-y-5 relative"
                            onSubmit={handleRegisterSubmit}
                        >
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                {/* Nama Bisnis */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Nama Bisnis{" "}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="business_name"
                                        required
                                        value={formData.business_name}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.business_name ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="Contoh: Toko Berkah"
                                    />
                                    {errors.business_name && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.business_name[0]}
                                        </p>
                                    )}
                                </div>

                                {/* Tipe Bisnis */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Tipe Bisnis
                                    </label>
                                    <select
                                        name="business_type"
                                        value={formData.business_type}
                                        onChange={handleInputChange}
                                        className={`w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-opacity-20 transition-all text-sm bg-white`}
                                    >
                                        <option value="">
                                            Pilih tipe bisnis
                                        </option>
                                        <option value="retail">
                                            Retail / Toko Umum
                                        </option>
                                        <option value="grocery">
                                            Toko Sembako / Grocery
                                        </option>
                                        <option value="minimarket">
                                            Minimarket
                                        </option>
                                        <option value="fashion">
                                            Fashion / Pakaian
                                        </option>
                                        <option value="food_beverage">
                                            Makanan & Minuman
                                        </option>
                                        <option value="restaurant">
                                            Restoran
                                        </option>
                                        <option value="cafe">
                                            Cafe / Coffee Shop
                                        </option>
                                        <option value="bakery">
                                            Bakery / Roti & Kue
                                        </option>
                                        <option value="pharmacy">Apotek</option>
                                        <option value="beauty">
                                            Beauty / Kosmetik
                                        </option>
                                        <option value="barbershop">
                                            Barbershop / Salon
                                        </option>
                                        <option value="laundry">Laundry</option>
                                        <option value="electronics">
                                            Elektronik & Gadget
                                        </option>
                                        <option value="computer_store">
                                            Komputer & Aksesoris
                                        </option>
                                        <option value="phone_store">
                                            Counter HP / Pulsa
                                        </option>
                                        <option value="automotive">
                                            Otomotif / Bengkel
                                        </option>
                                        <option value="hardware">
                                            Bangunan / Material
                                        </option>
                                        <option value="pet_shop">
                                            Pet Shop
                                        </option>
                                        <option value="book_store">
                                            Toko Buku / ATK
                                        </option>
                                        <option value="furniture">
                                            Furniture
                                        </option>
                                        <option value="health">
                                            Kesehatan
                                        </option>
                                        <option value="sports">Olahraga</option>
                                        <option value="jewelry">
                                            Perhiasan
                                        </option>
                                        <option value="wholesale">
                                            Grosir
                                        </option>
                                        <option value="service">
                                            Jasa / Service
                                        </option>
                                        <option value="online_shop">
                                            Online Shop
                                        </option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    {errors.business_type && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.business_type[0]}
                                        </p>
                                    )}
                                </div>

                                {/* Nama Pemilik */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Nama Pemilik{" "}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="name"
                                        required
                                        value={formData.name}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.name ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="Nama lengkap Anda"
                                    />
                                    {errors.name && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.name[0]}
                                        </p>
                                    )}
                                </div>

                                {/* No. WhatsApp */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        No. WhatsApp{" "}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        required
                                        value={formData.phone}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.phone ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="081234567890"
                                    />
                                    {errors.phone && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.phone[0]}
                                        </p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        OTP akan dikirim ke nomor ini
                                    </p>
                                </div>

                                {/* Email */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Email (Opsional)
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        value={formData.email}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.email ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="email@bisnis.com"
                                    />
                                    {errors.email && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.email[0]}
                                        </p>
                                    )}
                                </div>

                                {/* Nama Toko Utama */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Nama Cabang Pertama
                                    </label>
                                    <input
                                        type="text"
                                        name="store_name"
                                        value={formData.store_name}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.store_name ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="Kosongkan jika sama dg Nama Bisnis"
                                    />
                                    {errors.store_name && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.store_name[0]}
                                        </p>
                                    )}
                                </div>

                                {/* Password */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Kata Sandi{" "}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="password"
                                        name="password"
                                        required
                                        minLength="6"
                                        value={formData.password}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.password ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="Minimal 6 karakter"
                                    />
                                    {errors.password && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.password[0]}
                                        </p>
                                    )}
                                </div>

                                {/* Konfirmasi Password */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Ulangi Kata Sandi{" "}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        minLength="6"
                                        value={formData.password_confirmation}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border border-gray-300 focus:border-brand-500 focus:ring-brand-500 px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm`}
                                        placeholder="Ulangi sandi"
                                    />
                                </div>

                                {/* Kode Referral */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Kode Referral (Opsional)
                                    </label>
                                    <input
                                        type="text"
                                        name="referral_code"
                                        value={formData.referral_code}
                                        onChange={handleInputChange}
                                        className={`w-full appearance-none rounded-xl border ${errors.referral_code ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-3 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all text-sm uppercase`}
                                        placeholder="Contoh: REF123"
                                    />
                                    {errors.referral_code && (
                                        <p className="mt-1.5 text-xs text-red-600 font-medium">
                                            {errors.referral_code[0]}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="pt-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-brand-500/20 text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    {processing ? (
                                        <>
                                            <svg
                                                className="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
                                            </svg>{" "}
                                            Memproses...
                                        </>
                                    ) : (
                                        "Daftar Sekarang - Gratis!"
                                    )}
                                </button>
                            </div>

                            <p className="text-xs text-center text-gray-500 mt-4">
                                Dengan mendaftar, Anda menyetujui{" "}
                                <a
                                    href="#"
                                    className="font-semibold text-brand-500"
                                >
                                    Syarat & Ketentuan
                                </a>{" "}
                                serta{" "}
                                <a
                                    href="#"
                                    className="font-semibold text-brand-500"
                                >
                                    Kebijakan Privasi
                                </a>{" "}
                                kami.
                            </p>
                        </form>
                    )}

                    {step === 2 && (
                        <form className="space-y-6" onSubmit={handleOtpSubmit}>
                            <div className="bg-brand-50/50 rounded-xl p-5 border border-brand-100 text-center mb-6">
                                <span className="block text-sm text-gray-600 mb-1">
                                    Kode OTP telah dikirim ke WhatsApp:
                                </span>
                                <span className="block text-lg font-bold text-gray-900 tracking-wide">
                                    {formData.phone}
                                </span>
                            </div>

                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2 text-center">
                                    Masukkan Kode OTP
                                </label>
                                <input
                                    type="text"
                                    required
                                    maxLength="6"
                                    value={otp}
                                    onChange={(e) =>
                                        setOtp(
                                            e.target.value.replace(/\D/g, ""),
                                        )
                                    }
                                    className={`w-full text-center text-3xl tracking-[0.5em] appearance-none rounded-xl border ${errors.otp ? "border-red-300 focus:border-red-500 focus:ring-red-500" : "border-gray-300 focus:border-brand-500 focus:ring-brand-500"} px-4 py-4 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-opacity-20 transition-all font-mono`}
                                    placeholder="••••••"
                                    autoFocus
                                />
                                {errors.otp && (
                                    <p className="mt-2 text-sm text-red-600 font-medium text-center">
                                        {errors.otp}
                                    </p>
                                )}
                            </div>

                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={processing || otp.length !== 6}
                                    className="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-brand-500/20 text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    {processing
                                        ? "Memverifikasi..."
                                        : "Verifikasi Akun"}
                                </button>
                            </div>

                            <p className="text-sm text-center text-gray-500 mt-4">
                                Belum menerima kode?{" "}
                                <button
                                    type="button"
                                    className="font-semibold text-brand-500 hover:text-brand-600"
                                >
                                    Kirim Ulang
                                </button>
                            </p>
                        </form>
                    )}

                    {step === 3 && (
                        <div className="text-center py-8">
                            <div className="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                                <svg
                                    className="h-10 w-10 text-green-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <h3 className="text-xl font-extrabold text-gray-900 mb-2">
                                Selamat Bergabung!
                            </h3>
                            <p className="text-gray-600 mb-8 max-w-sm mx-auto">
                                Akun bisnis Anda telah berhasil diverifikasi.
                                Silakan download aplikasi GoKasir untuk mulai
                                mengelola bisnis Anda.
                            </p>

                            <a
                                href="https://play.google.com/store/apps/details?id=com.gokasir.net"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex w-full justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-brand-500/20 text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 transition-all"
                            >
                                Download Aplikasi Sekarang
                            </a>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
