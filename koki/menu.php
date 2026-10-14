<?php
session_start();
include "../config.php";

// Cek login koki
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Ambil data menu dan kategori
$query = mysqli_query($db, "
    SELECT
        menu.*,
        kategori.nama_kategori
    FROM menu
    LEFT JOIN kategori
        ON menu.id_kategori = kategori.id_kategori
    ORDER BY menu.nama_menu ASC
");

if (!$query) {
    die("Gagal mengambil data menu: " . mysqli_error($db));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Menu Dapur - RM. Tambaksari</title>

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

/* FOTO MENU */
.foto-menu {
    width: 65px;
    height: 65px;
    object-fit: cover;
    border-radius: 8px;
    background: #eee;
    display: block;
    margin: 0 auto;
}

.no-foto {
    width: 65px;
    height: 65px;
    background: #eee;
    color: #777;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    font-size: 10px;
    text-align: center;
    padding: 4px;
}

/* BADGE STATUS STOK */
.badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.tersedia {
    background: #d7f5e3;
    color: #198754;
}

.habis {
    background: #fde5e5;
    color: #b42318;
}

/* KOSONG */
.kosong {
    text-align: center;
    color: #777;
    padding: 25px;
}

/* RESPONSIVE */
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
    .sidebar {
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
            <a href="dashboard.php">
                🏠 &nbsp; Dashboard Koki
            </a>
        </li>
        <li>
            <a href="pesanan.php">
                📋 &nbsp; Daftar Pesanan
            </a>
        </li>
        <li>
            <a href="menu.php" class="active">
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
            <h1>Menu Dapur</h1>
            <p>Daftar makanan dan minuman RM. Tambaksari.</p>
        </div>

        <div class="admin-name">
            👤 <?= htmlspecialchars($_SESSION['username'] ?? 'koki'); ?>
        </div>
    </div>

    <!-- CONTENT BOX -->
    <div class="content-box">

        <div class="content-header">
            <h2>Daftar Menu Makanan dan Minuman</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Status Stok</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                $no = 1;

                while ($data = mysqli_fetch_assoc($query)) {
                ?>
                    <tr>
                        <td><?= $no++; ?></td>

                        <td>
                            <?php if (!empty($data['foto'])) { ?>
                                <img
                                    src="../assets/images/menu/<?= htmlspecialchars($data['foto']); ?>"
                                    alt="<?= htmlspecialchars($data['nama_menu']); ?>"
                                    class="foto-menu"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                >
                                <span class="no-foto" style="display:none;">
                                    Foto tidak ada
                                </span>
                            <?php } else { ?>
                                <span class="no-foto">Tidak ada foto</span>
                            <?php } ?>
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($data['nama_menu']); ?></strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($data['nama_kategori'] ?? '-'); ?>
                        </td>

                        <td>
                            Rp <?= number_format((float)$data['harga'], 0, ',', '.'); ?>
                        </td>
                        
                        <td>
                            <?php if ($data['status_stok'] == 'tersedia') { ?>
                                <span class="badge tersedia">Tersedia</span>
                            <?php } else { ?>
                                <span class="badge habis">Habis</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>

                <?php if ($no == 1) { ?>
                    <tr>
                        <td colspan="7" class="kosong">
                            Belum ada data menu.
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