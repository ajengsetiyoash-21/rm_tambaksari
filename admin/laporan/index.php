<?php
session_start();
include "../../config.php";

// =========================
// HANDLE UPDATE STATUS LUNAS
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lunas') {
    $id_pesanan = intval($_POST['id_pesanan'] ?? 0);
    if ($id_pesanan > 0) {
        mysqli_query($db, "UPDATE pesanan SET status_bayar = 'lunas' WHERE id_pesanan = $id_pesanan");
    }
    // Redirect supaya refresh tidak double submit
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: index.php' . $qs);
    exit;
}

// =========================
// FILTER TANGGAL
// =========================
$tanggal_awal  = $_GET['tanggal_awal']  ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

$where = [];

if ($tanggal_awal != '') {
    $awal = mysqli_real_escape_string($db, $tanggal_awal);
    $where[] = "DATE(p.created_at) >= '$awal'";
}

if ($tanggal_akhir != '') {
    $akhir = mysqli_real_escape_string($db, $tanggal_akhir);
    $where[] = "DATE(p.created_at) <= '$akhir'";
}

$filter = count($where) > 0
    ? "WHERE " . implode(" AND ", $where)
    : "";

// =========================
// QUERY DATA PESANAN
// =========================
$query = mysqli_query($db, "
    SELECT
        p.id_pesanan,
        p.total_harga,
        p.status_bayar,
        p.metode_bayar,
        p.catatan,
        p.created_at,
        m.nomor_meja
    FROM pesanan p
    LEFT JOIN meja m ON p.id_meja = m.id_meja
    $filter
    ORDER BY p.created_at DESC
");

if (!$query) {
    die("Gagal mengambil laporan: " . mysqli_error($db));
}

$data_laporan = [];
$total_transaksi   = 0;
$total_pendapatan  = 0;   // yang LUNAS saja
$total_belum_bayar = 0;   // yang BELUM BAYAR
$count_lunas       = 0;
$count_belum       = 0;

while ($data = mysqli_fetch_assoc($query)) {
    $data_laporan[] = $data;
    $total_transaksi++;

    if ($data['status_bayar'] === 'lunas') {
        $total_pendapatan += (float) $data['total_harga'];
        $count_lunas++;
    } else {
        $total_belum_bayar += (float) $data['total_harga'];
        $count_belum++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan - RM. Tambaksari</title>

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

/* SIDEBAR */
.sidebar {
    width: 240px;
    height: 100vh;
    background: #5b1018;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding: 25px 15px;
    overflow-y: auto;
}

.logo {
    text-align: center;
    margin-bottom: 35px;
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
    margin: 0;
    padding: 0;
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

/* KONTEN */
.content {
    margin-left: 240px;
    padding: 30px;
}

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

/* TOMBOL */
.btn {
    border: none;
    border-radius: 8px;
    padding: 11px 16px;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    font-family: inherit;
}

.btn-maroon {
    background: #5b1018;
    color: white;
}

.btn-maroon:hover {
    background: #7d2029;
}

.btn-gray {
    background: #777;
    color: white;
}

.btn-gray:hover {
    background: #555;
}

/* KARTU RINGKASAN */
.ringkasan {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.kartu {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.06);
    border-left: 4px solid #5b1018;
}

.kartu.lunas { border-left-color: #10b981; }
.kartu.belum { border-left-color: #f59e0b; }

.kartu p {
    margin: 0 0 10px;
    color: #777;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.kartu h2 {
    margin: 0;
    color: #5b1018;
    font-size: 23px;
    font-weight: 800;
}

.kartu.lunas h2 { color: #065f46; }
.kartu.belum h2 { color: #92400e; }

.kartu small {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: #888;
    font-weight: 500;
}

/* FILTER */
.filter-box,
.table-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.06);
}

.filter-box h3,
.table-box h3 {
    color: #5b1018;
    margin: 0 0 18px;
    font-size: 17px;
}

.filter-form {
    display: flex;
    align-items: end;
    gap: 12px;
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group label {
    font-size: 13px;
    font-weight: bold;
}

.form-group input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 7px;
    font-family: inherit;
}

/* TABEL */
.table-box {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th {
    background: #5b1018;
    color: white;
    padding: 14px 12px;
    text-align: center;
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #eee;
    text-align: center;
    font-size: 13px;
    vertical-align: middle;
}

tr:hover td {
    background: #faf7f5;
}

/* BADGE */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    border: none;
    font-family: inherit;
}

.badge-lunas {
    background: linear-gradient(135deg, #d1fae5, #6ee7b7);
    color: #065f46;
}

.badge-belum {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    cursor: pointer;
    transition: all 0.2s;
}

.badge-belum:hover {
    background: linear-gradient(135deg, #fde68a, #fbbf24);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.badge-metode {
    background: #f3f0ea;
    color: #5b1018;
    border: 1px solid #e0d8c9;
    padding: 5px 11px;
    font-size: 11px;
    font-weight: 700;
}

.kosong {
    padding: 40px 25px;
    text-align: center;
    color: #999;
    font-size: 14px;
}

/* CETAK */
@media print {
    .sidebar,
    .filter-box,
    .btn-cetak,
    .admin-name,
    .no-print {
        display: none !important;
    }

    .content {
        margin: 0;
        padding: 10px;
    }

    body {
        background: white;
    }

    .kartu,
    .table-box {
        box-shadow: none;
        border: 1px solid #ddd;
    }

    .badge-belum {
        cursor: default;
        background: #fff3cd !important;
        color: #856404 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .badge-lunas {
        background: #d1fae5 !important;
        color: #065f46 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

@media (max-width: 800px) {
    .sidebar {
        width: 190px;
    }

    .content {
        margin-left: 190px;
        padding: 18px;
    }

    .ringkasan {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">
        <img src="../../assets/images/logo.png" alt="Logo RM Tambaksari">
        <h2>RM. TAMBAKSARI</h2>
        <p>Onggorawe Demak</p>
    </div>

    <ul class="menu">
        <li><a href="../dashboard.php">🏠 &nbsp; Dashboard</a></li>
        <li><a href="../users/index.php">👤 &nbsp; User</a></li>
        <li><a href="../menu/index.php">🍽️ &nbsp; Menu</a></li>
        <li><a href="../kategori/index.php">📋 &nbsp; Kategori</a></li>
        <li><a href="../meja/index.php">🪑 &nbsp; Meja</a></li>
        <li><a href="index.php" class="active">📊 &nbsp; Laporan</a></li>
        <li><a href="../../logout.php" class="logout">🚪 &nbsp; Logout</a></li>
    </ul>
</aside>

<!-- KONTEN -->
<div class="content">

    <!-- HEADER -->
    <div class="topbar">
        <div>
            <h1>Laporan Pesanan</h1>
            <p>Lihat laporan transaksi RM. Tambaksari</p>
        </div>

        <div class="admin-name">
            👤 <?= htmlspecialchars($_SESSION['username'] ?? 'admin'); ?>
        </div>
    </div>

    <!-- TOMBOL CETAK -->
    <div style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-maroon btn-cetak">
            🖨️ Cetak Laporan
        </button>
    </div>

    <!-- RINGKASAN -->
    <div class="ringkasan">
        <div class="kartu">
            <p>TOTAL TRANSAKSI</p>
            <h2><?= number_format($total_transaksi, 0, ',', '.'); ?></h2>
            <small>Semua pesanan</small>
        </div>

        <div class="kartu lunas">
            <p>SUDAH DIBAYAR</p>
            <h2>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h2>
            <small><?= $count_lunas; ?> pesanan lunas</small>
        </div>

        <div class="kartu belum">
            <p>BELUM DIBAYAR</p>
            <h2>Rp <?= number_format($total_belum_bayar, 0, ',', '.'); ?></h2>
            <small><?= $count_belum; ?> pesanan menunggu</small>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-box">
        <h3>Filter Tanggal</h3>

        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="tanggal_awal">Dari Tanggal</label>
                <input type="date" name="tanggal_awal" id="tanggal_awal"
                       value="<?= htmlspecialchars($tanggal_awal); ?>">
            </div>

            <div class="form-group">
                <label for="tanggal_akhir">Sampai Tanggal</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir"
                       value="<?= htmlspecialchars($tanggal_akhir); ?>">
            </div>

            <button type="submit" class="btn btn-maroon">Tampilkan</button>
            <a href="index.php" class="btn btn-gray">Reset</a>
        </form>
    </div>

    <!-- TABEL DATA -->
    <div class="table-box">
        <h3>Data Transaksi Pesanan</h3>

        <table>
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th>ID Pesanan</th>
                    <th>Nomor Meja</th>
                    <th>Tanggal Pesanan</th>
                    <th>Total Harga</th>
                    <th>Metode Pembayaran</th>
                    <th>Status Pembayaran</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($data_laporan) > 0) { ?>
                <?php foreach ($data_laporan as $no => $data) { ?>
                    <tr>
                        <td><?= $no + 1; ?></td>

                        <td>
                            <strong>#<?= (int) $data['id_pesanan']; ?></strong>
                        </td>

                        <td>
                            Meja <?= htmlspecialchars((string)($data['nomor_meja'] ?? '-')); ?>
                        </td>

                        <td>
                            <?= $data['created_at']
                                ? date('d-m-Y H:i', strtotime($data['created_at']))
                                : '-'; ?>
                        </td>

                        <td>
                            <strong>Rp <?= number_format((float)$data['total_harga'], 0, ',', '.'); ?></strong>
                        </td>

                        <td>
                            <?php if (!empty($data['metode_bayar'])) : ?>
                                <span class="badge badge-metode">
                                    <?= htmlspecialchars(strtoupper($data['metode_bayar'])); ?>
                                </span>
                            <?php else : ?>
                                <span style="color:#aaa;">-</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($data['status_bayar'] === 'lunas') : ?>
                                <span class="badge badge-lunas">✓ LUNAS</span>
                            <?php else : ?>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Tandai pesanan #<?= $data['id_pesanan']; ?> sebagai LUNAS?')">
                                    <input type="hidden" name="action" value="lunas">
                                    <input type="hidden" name="id_pesanan" value="<?= $data['id_pesanan']; ?>">
                                    <button type="submit" class="badge badge-belum" title="Klik untuk tandai lunas">
                                        ● BELUM DIBAYAR
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="7" class="kosong">
                        Belum ada data pesanan.
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>