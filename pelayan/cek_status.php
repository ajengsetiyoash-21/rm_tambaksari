<?php
session_start();
include "../config.php";

// Cek login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Hitung pesanan yang sedang dimasak
$q_proses = mysqli_query($db, "
    SELECT COUNT(DISTINCT id_pesanan) AS total 
    FROM detail_pesanan 
    WHERE status_memasak = 'dimasak'
");
$proses = mysqli_fetch_assoc($q_proses)['total'] ?? 0;

// Hitung pesanan yang siap diantar
$q_siap = mysqli_query($db, "
    SELECT COUNT(DISTINCT id_pesanan) AS total 
    FROM detail_pesanan 
    WHERE status_memasak = 'selesai'
");
$siap = mysqli_fetch_assoc($q_siap)['total'] ?? 0;

echo json_encode([
    'proses' => (int)$proses,
    'siap'   => (int)$siap,
    'time'   => date('H:i:s')
]);