<?php
session_start();
include "../config.php";

// Cek login koki
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != 'koki') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status     = $_POST['status_memasak'] ?? '';
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $id_detail  = (int)($_POST['id_detail'] ?? 0);   // dukungan halaman lama (per item)

    $stmt = null;

    if ($id_pesanan > 0) {
        // Update seluruh item dalam satu pesanan
        if ($status === 'dimasak') {
            $stmt = mysqli_prepare($db, "UPDATE detail_pesanan SET status_memasak='dimasak' WHERE id_pesanan=? AND status_memasak='antre'");
        } elseif ($status === 'selesai') {
            $stmt = mysqli_prepare($db, "UPDATE detail_pesanan SET status_memasak='selesai' WHERE id_pesanan=? AND status_memasak IN ('antre','dimasak')");
        }
        if ($stmt) mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
    } elseif ($id_detail > 0 && in_array($status, ['antre', 'dimasak', 'selesai'], true)) {
        // Update satu item saja
        $stmt = mysqli_prepare($db, "UPDATE detail_pesanan SET status_memasak=? WHERE id_detail=?");
        if ($stmt) mysqli_stmt_bind_param($stmt, "si", $status, $id_detail);
    }

    if ($stmt) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

header("Location: pesanan.php");
exit;