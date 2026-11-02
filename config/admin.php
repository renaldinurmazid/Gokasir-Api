<?php

return [
    // Zona waktu untuk batas "hari ini / minggu ini / bulan ini" di dashboard admin.
    // Data disimpan dalam config('app.timezone'); angka dikonversi saat dihitung.
    'timezone' => env('ADMIN_TIMEZONE', 'Asia/Jakarta'),

    // Tenant dianggap "aktif" jika punya transaksi dalam N hari terakhir.
    'active_days' => 30,

    // Masa berlaku token impersonate (menit).
    'impersonate_minutes' => 60,
];
