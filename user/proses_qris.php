<?php
session_start();
require 'qris_helper.php';
require '../config.php';

header('Content-Type: application/json');

if (empty($_SESSION['keranjang'])) {
    echo json_encode(['error' => 'Keranjang kosong']);
    exit;
}

$total = 0;
$ids_str = implode(',', array_map('intval', array_keys($_SESSION['keranjang'])));

$q = mysqli_query($db, "SELECT * FROM menu WHERE id_menu IN ($ids_str)");
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $qty = $_SESSION['keranjang'][$row['id_menu']];
        $total += $row['harga'] * $qty;
    }
}

$tax     = round($total * 0.10);
$service = $total > 0 ? 2000 : 0;
$grand_total = $total + $tax + $service;

$qris_dinamis = generateDynamicQRIS(QRIS_STATIS, $grand_total);
$qr_url = qrisToImageUrl($qris_dinamis, 300);

echo json_encode([
    'success' => true,
    'total' => $grand_total,
    'total_format' => 'Rp ' . number_format($grand_total, 0, ',', '.'),
    'qr_url' => $qr_url,
    'qris_raw' => $qris_dinamis
]);