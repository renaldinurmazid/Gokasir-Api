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
// iPaymu webhook callback (unotify) signature uses:
// signature = hash_hmac('sha256', JSONBody, VA) 
// Let's try JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
ksort($payload);
$json = json_encode($payload, JSON_UNESCAPED_SLASHES);
echo "JSON_UNESCAPED_SLASHES: " . hash_hmac('sha256', $json, $va) . "\n";

$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo "JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE: " . hash_hmac('sha256', $json, $va) . "\n";

// Try without sorting?
$json = json_encode($payload, JSON_UNESCAPED_SLASHES);
echo "NO SORT, UNESCAPED_SLASHES: " . hash_hmac('sha256', $json, $va) . "\n";

// Let's print out what iPaymu webhook documentation says about signature verify.
// In https://ipaymu.com/en/documentation#callback :
// No specific mention, but the iPaymu library does this:
// hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_SLASHES), $va)
