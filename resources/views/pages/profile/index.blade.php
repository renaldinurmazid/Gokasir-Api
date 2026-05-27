@extends('layouts.order')

@section('content')
    <div class="space-y-4 flex-1 pb-28">
        <div class="py-4 px-4 border-b flex justify-between items-center bg-white sticky top-0 z-50 shadow-sm">
            <a href="/order/{{ $tableCode }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-arrow-left-icon lucide-arrow-left">
                    <path d="m12 19-7-7 7-7" />
                    <path d="M19 12H5" />
                </svg>
            </a>
            <div class="flex-1 justify-center">
                <h6 class="font-semibold text-md text-center">Profile</h6>
            </div>
        </div>
        <div class="bg-white p-4 space-y-3">
            <a href="/order/{{ $tableCode }}/history"
                class="border flex gap-2 items-center p-3 w-full border-slate-200 rounded-md hover:bg-slate-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-file-text-icon lucide-file-text text-slate-700 size-5">
                    <path
                        d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
                    <path d="M14 2v5a1 1 0 0 0 1 1h5" />
                    <path d="M10 9H8" />
                    <path d="M16 13H8" />
                    <path d="M16 17H8" />
                </svg>
                <p class="font-medium text-sm text-slate-700">Riwayat Pesanan</p>
            </a>
            {{-- <button class="border flex gap-2 items-center p-3 w-full border-slate-200 rounded-md">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-globe-icon lucide-globe text-slate-700 size-5">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                    <path d="M2 12h20" />
                </svg>
                <p class="font-medium text-sm text-slate-700">Bahasa</p>
            </button> --}}
            <a href="/profile/{{ $tableCode }}/privacy" class="border flex gap-2 items-center p-3 w-full border-slate-200 rounded-md hover:bg-slate-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-file-key-icon lucide-file-key text-slate-700 size-5">
                    <path d="M14 2v5a1 1 0 0 0 1 1h5" />
                    <path d="M4 12v6" />
                    <path d="M4 14h2" />
                    <path
                        d="M9.65 22H18a2 2 0 0 0 2-2V8a2.4 2.4 0 0 0-.706-1.706l-3.588-3.588A2.4 2.4 0 0 0 14 2H6a2 2 0 0 0-2 2v4" />
                    <circle cx="4" cy="20" r="2" />
                </svg>
                <p class="font-medium text-sm text-slate-700">Kebijakan Privasi</p>
            </a>
        </div>
    </div>
@endsection
