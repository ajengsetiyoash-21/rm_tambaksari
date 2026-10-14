<?php
session_start();
include "../config.php";

// Pastikan yang masuk adalah koki
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Mengubah status memasak
if (isset($_POST['ubah_status'])) {
    $id_detail = (int) $_POST['id_detail'];
    $status_baru = $_POST['status_baru'];

    $status_valid = ['dimasak', 'selesai'];

    if (in_array($status_baru, $status_valid, true)) {
        $status_sql = mysqli_real_escape_string($db, $status_baru);

        $update = mysqli_query($db, "
            UPDATE detail_pesanan
            SET status_memasak = '$status_sql'
            WHERE id_detail = $id_detail
        ");

        if ($update) {
            header("Location: dashboard.php?pesan=berhasil");
            exit;
        }
    }

    header("Location: dashboard.php?pesan=gagal");
    exit;
}

// Ringkasan status memasak
$query_antre = mysqli_query($db, "
    SELECT COUNT(*) AS jumlah
    FROM detail_pesanan
    WHERE status_memasak = 'antre'
");

$query_dimasak = mysqli_query($db, "
    SELECT COUNT(*) AS jumlah
    FROM detail_pesanan
    WHERE status_memasak = 'dimasak'
");

$query_selesai = mysqli_query($db, "
    SELECT COUNT(*) AS jumlah
    FROM detail_pesanan
    WHERE status_memasak = 'selesai'
");

if (!$query_antre || !$query_dimasak || !$query_selesai) {
    die("Gagal mengambil ringkasan: " . mysqli_error($db));
}

$jumlah_antre = mysqli_fetch_assoc($query_antre)['jumlah'];
$jumlah_dimasak = mysqli_fetch_assoc($query_dimasak)['jumlah'];
$jumlah_selesai = mysqli_fetch_assoc($query_selesai)['jumlah'];

// Ambil daftar pesanan dapur
$query_pesanan = mysqli_query($db, "
    SELECT
        dp.id_detail,
        dp.id_pesanan,
        dp.jumlah,
        dp.status_memasak,
        m.nomor_meja,
        mn.nama_menu,
        p.created_at
    FROM detail_pesanan dp
    JOIN pesanan p
        ON dp.id_pesanan = p.id_pesanan
    JOIN menu mn
        ON dp.id_menu = mn.id_menu
    JOIN meja m
        ON p.id_meja = m.id_meja
    WHERE dp.status_memasak IN ('antre', 'dimasak')
    ORDER BY p.created_at ASC, dp.id_detail ASC
");

if (!$query_pesanan) {
    die("Gagal mengambil data pesanan: " . mysqli_error($db));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Koki - RM. Tambaksari</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background: #f5f1ed;
    color: #333;
}

/* =========================
   SIDEBAR
========================= */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 240px;
    height: 100vh;
    background: #5b1018;
    color: white;
    padding: 25px 15px;
}

.logo {
    text-align: center;
    margin-bottom: 35px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
}

.logo img {
    width: 50px;
    height: 50px;
    object-fit: contain;
    margin-bottom: 6px;
}

.logo h2 {
    font-size: 18px;
    color: white;
}

.logo p {
    font-size: 12px;
    color: #eadada;
    margin-top: 5px;
}

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 8px;
}

.menu a {
    display: block;
    padding: 13px 15px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
}

.menu a:hover {
    background: #8d2630;
}

.menu a.active {
    background: #922733;
}

.menu .logout {
    margin-top: 25px;
}

/* =========================
   MAIN
========================= */
.main {
    margin-left: 240px;
    padding: 30px;
}

/* HEADER */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.topbar h1 {
    color: #5b1018;
    font-size: 28px;
    margin-bottom: 5px;
}

.topbar p {
    color: #555;
    font-size: 15px;
}

.admin-name {
    background: white;
    padding: 10px 15px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    font-size: 15px;
}

/* PESAN NOTIFIKASI */
.pesan {
    background: #e0f5e7;
    color: #198754;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.pesan.gagal {
    background: #fde5e5;
    color: #b42318;
}

/* KARTU RINGKASAN */
.ringkasan {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.kartu {
    background: white;
    border-radius: 12px;
    padding: 23px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

.kartu p {
    margin: 0 0 12px;
    color: #777;
    font-size: 14px;
}

.kartu h2 {
    margin: 0;
    color: #5b1018;
    font-size: 30px;
}

/* CONTENT BOX */
.content-box {
    background: white;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.content-header h2 {
    color: #5b1018;
    font-size: 21px;
}

/* TABEL */
.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    min-width: 900px;
}

th {
    background: #6d1720;
    color: white;
    padding: 14px 12px;
    text-align: center;
    font-size: 13px;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #eeeeee;
    font-size: 13px;
    text-align: center;
    vertical-align: middle;
}

tr:hover td {
    background: #fff8f5;
}

/* BADGE STATUS */
.badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.badge-antre {
    background: #fff0ce;
    color: #946200;
}

.badge-dimasak {
    background: #dcecff;
    color: #1455a0;
}

/* TOMBOL AKSI */
.btn-status {
    border: none;
    padding: 9px 14px;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
}

.btn-mulai {
    background: #b7791f;
}

.btn-mulai:hover {
    background: #946200;
}

.btn-selesai {
    background: #198754;
}

.btn-selesai:hover {
    background: #146c43;
}

.kosong {
    text-align: center;
    padding: 25px;
    color: #777;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .ringkasan {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
    }
}

@media (max-width: 600px) {
    .sidebar {
        width: 65px;
        padding: 20px 8px;
    }

    .logo h2,
    .logo p {
        display: none;
    }

    .logo img {
        width: 42px;
        height: 42px;
    }

    .menu a {
        text-align: center;
        padding: 12px 5px;
        font-size: 18px;
    }

    .main {
        margin-left: 65px;
        padding: 20px;
    }

    .topbar h1 {
        font-size: 23px;
    }

    .topbar p {
        font-size: 13px;
    }

    .admin-name {
        font-size: 13px;
        padding: 8px 10px;
    }
}

/* CETAK */
@media print {
    .sidebar,
    .btn-status {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 10px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="../assets/images/logo.png" alt="Logo RM Tambaksari">
        <h2>RM. TAMBAKSARI</h2>
        <p>Onggorawe Demak</p>
    </div>

    <ul class="menu">
        <li>
            <a href="dashboard.php" class="active">
                🏠 &nbsp; Dashboard Koki
            </a>
        </li>
        <li>
            <a href="pesanan.php">
                📋 &nbsp; Daftar Pesanan
            </a>
        </li>
        <li>
            <a href="menu.php">
                🍽️ &nbsp; Menu
            </a>
        </li>
        <li>
            <a href="../logout.php" class="logout">
                🚪 &nbsp; Logout
            </a>
        </li>
    </ul>
</aside>

<!-- MAIN CONTENT -->
<main class="main">

    <!-- HEADER -->
    <div class="topbar">
        <div>
            <h1>Dashboard Koki</h1>
            <p>Kelola antrean dan proses memasak pesanan pelanggan.</p>
        </div>

        <div class="admin-name">
            👤 <?= htmlspecialchars($_SESSION['username'] ?? 'koki'); ?>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if (isset($_GET['pesan'])) { ?>
        <?php if ($_GET['pesan'] == 'berhasil') { ?>
            <div class="pesan">
                Status pesanan berhasil diperbarui.
            </div>
        <?php } elseif ($_GET['pesan'] == 'gagal') { ?>
            <div class="pesan gagal">
                Status pesanan gagal diperbarui.
            </div>
        <?php } ?>
    <?php } ?>

    <!-- RINGKASAN -->
    <div class="ringkasan">
        <div class="kartu">
            <p>Pesanan Antre</p>
            <h2><?= (int)$jumlah_antre; ?></h2>
        </div>

        <div class="kartu">
            <p>Sedang Dimasak</p>
            <h2><?= (int)$jumlah_dimasak; ?></h2>
        </div>

        <div class="kartu">
            <p>Pesanan Selesai</p>
            <h2><?= (int)$jumlah_selesai; ?></h2>
        </div>
    </div>

    <!-- TABEL PESANAN -->
    <div class="content-box">

        <div class="content-header">
            <h2>Antrean Pesanan Dapur</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Pesanan</th>
                        <th>Nomor Meja</th>
                        <th>Nama Menu</th>
                        <th>Jumlah</th>
                        <th>Waktu Pesan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                $no = 1;

                while ($data = mysqli_fetch_assoc($query_pesanan)) {
                ?>
                    <tr>
                        <td><?= $no++; ?></td>

                        <td>
                            #<?= (int)$data['id_pesanan']; ?>
                        </td>

                        <td>
                            Meja <?= htmlspecialchars((string)$data['nomor_meja']); ?>
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($data['nama_menu']); ?></strong>
                        </td>

                        <td>
                            <?= (int)$data['jumlah']; ?>
                        </td>

                        <td>
                            <?= date('d-m-Y H:i', strtotime($data['created_at'])); ?>
                        </td>

                        <td>
                            <?php if ($data['status_memasak'] == 'antre') { ?>
                                <span class="badge badge-antre">Antre</span>
                            <?php } else { ?>
                                <span class="badge badge-dimasak">Dimasak</span>
                            <?php } ?>
                        </td>

                        <td>
                            <form method="POST">
                                <input
                                    type="hidden"
                                    name="id_detail"
                                    value="<?= (int)$data['id_detail']; ?>"
                                >

                                <?php if ($data['status_memasak'] == 'antre') { ?>

                                    <input
                                        type="hidden"
                                        name="status_baru"
                                        value="dimasak"
                                    >

                                    <button
                                        type="submit"
                                        name="ubah_status"
                                        class="btn-status btn-mulai"
                                    >
                                        Mulai Masak
                                    </button>

                                <?php } else { ?>

                                    <input
                                        type="hidden"
                                        name="status_baru"
                                        value="selesai"
                                    >

                                    <button
                                        type="submit"
                                        name="ubah_status"
                                        class="btn-status btn-selesai"
                                    >
                                        Selesai
                                    </button>

                                <?php } ?>
                            </form>
                        </td>
                    </tr>
                <?php } ?>

                <?php if ($no == 1) { ?>
                    <tr>
                        <td colspan="8" class="kosong">
                            Belum ada pesanan yang perlu diproses.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    </div>

</main>

</body>
</html>