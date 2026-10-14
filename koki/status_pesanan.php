
<?php
session_start();
include "../config.php";

// Cek login dan role koki
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Pastikan data dikirim menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pesanan.php");
    exit;
}

$id_detail = isset($_POST['id_detail'])
    ? (int) $_POST['id_detail']
    : 0;

$status = $_POST['status_memasak'] ?? '';

$status_valid = ['antre', 'dimasak', 'selesai'];

if ($id_detail <= 0 || !in_array($status, $status_valid, true)) {
    echo "<script>
        alert('Data atau status tidak valid!');
        window.location.href = 'pesanan.php';
    </script>";
    exit;
}

// Periksa apakah detail pesanan tersedia
$stmt = mysqli_prepare(
    $db,
    "SELECT id_detail FROM detail_pesanan WHERE id_detail = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_detail);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>
        alert('Detail pesanan tidak ditemukan!');
        window.location.href = 'pesanan.php';
    </script>";
    exit;
}

// Perbarui status memasak
$stmt = mysqli_prepare(
    $db,
    "UPDATE detail_pesanan
     SET status_memasak = ?
     WHERE id_detail = ?"
);

mysqli_stmt_bind_param($stmt, "si", $status, $id_detail);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    echo "<script>
        alert('Status pesanan berhasil diperbarui!');
        window.location.href = 'pesanan.php';
    </script>";
    exit;
}

mysqli_stmt_close($stmt);

echo "<script>
    alert('Gagal memperbarui status pesanan!');
    window.location.href = 'pesanan.php';
</script>";
exit;
?>
