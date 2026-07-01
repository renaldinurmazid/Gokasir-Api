<?php
$va = "0000001284725661";
$received_sig = "7f1397a1c10d1bd393d9e008d33388b715340b48cf1ed5819f4e605d8ebf44f8";

$payload = [
    "amount" => "3920",
    "buyer_email" => "order@gokasir.id",
    "buyer_name" => "Renaldi",
    "buyer_phone" => "087843236707",
    "channel" => "qris",
    "created_at" => "2026-06-30 14:16:13",
    "expired_at" => "2026-07-01 14:16:13",
    "fee" => "28",
    "is_escrow" => "0",
    "paid_at" => "2026-06-30 14:17:17",
    "paid_off" => "3892",
    "payment_no" => "",
    "reference_id" => "ORD-20260630-JKUVD",
    "settlement_status" => "settled",
    "sid" => "ORD-20260630-JKUVD",
    "status" => "berhasil",
    "status_code" => "1",
    "sub_total" => "3920",
    "system_notes" => "Sandbox notify",
    "total" => "3920",
    "transaction_status_code" => "1",
    "trx_id" => "215190",
    "url" => "https://e281-2404-c0-a303-d340-3504-eca1-8015-4a1a.ngrok-free.app/api/webhooks/ipaymu-order",
    "via" => "qris"
];

ksort($payload);

// Try 1: just json_encode
$json1 = json_encode($payload);
echo "Try 1: " . hash_hmac('sha256', $json1, $va) . "\n";

// Try 2: unescaped slashes
$json2 = json_encode($payload, JSON_UNESCAPED_SLASHES);
echo "Try 2: " . hash_hmac('sha256', $json2, $va) . "\n";

// Try 3: payment_no removed?
$p3 = $payload;
unset($p3['payment_no']);
$json3 = json_encode($p3, JSON_UNESCAPED_SLASHES);
echo "Try 3: " . hash_hmac('sha256', $json3, $va) . "\n";

// Try 4: using http_build_query
echo "Try 4: " . hash_hmac('sha256', http_build_query($payload), $va) . "\n";
echo "Try 4 (urldecode): " . hash_hmac('sha256', urldecode(http_build_query($payload)), $va) . "\n";

// Try 5: raw POST body hash?
// Wait, the raw POST body has the signature in it. We have to parse, remove signature, rebuild.
