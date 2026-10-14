<?php
session_start();
include "../config.php";

// Cek login admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit;
}

// =========================
// DATA DASHBOARD
// =========================

// Total User
$q_user = mysqli_query($db, "SELECT COUNT(*) AS total FROM users");
$total_user = mysqli_fetch_assoc($q_user)['total'];

// Total Menu
$q_menu = mysqli_query($db, "SELECT COUNT(*) AS total FROM menu");
$total_menu = mysqli_fetch_assoc($q_menu)['total'];

// Total Meja
$q_meja = mysqli_query($db, "SELECT COUNT(*) AS total FROM meja");
$total_meja = mysqli_fetch_assoc($q_meja)['total'];

// Total Transaksi
$q_transaksi = mysqli_query($db, "SELECT COUNT(*) AS total FROM pesanan");
$total_transaksi = mysqli_fetch_assoc($q_transaksi)['total'];

// Total Pendapatan (hanya yang LUNAS)
$q_pendapatan = mysqli_query($db, "
    SELECT COALESCE(SUM(total_harga), 0) AS total
    FROM pesanan
    WHERE status_bayar = 'lunas'
");
$total_pendapatan = mysqli_fetch_assoc($q_pendapatan)['total'];

// Total Belum Dibayar
$q_belum = mysqli_query($db, "
    SELECT COALESCE(SUM(total_harga), 0) AS total
    FROM pesanan
    WHERE status_bayar = 'belum_bayar'
");
$total_belum = mysqli_fetch_assoc($q_belum)['total'];

// =========================
// PESANAN TERBARU
// =========================
$q_pesanan = mysqli_query($db, "
    SELECT 
        p.id_pesanan,
        m.nomor_meja,
        p.total_harga,
        p.status_bayar,
        p.created_at,
        GROUP_CONCAT(DISTINCT dp.status_memasak) AS status_memasak
    FROM pesanan p
    LEFT JOIN meja m ON p.id_meja = m.id_meja
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    GROUP BY 
        p.id_pesanan,
        m.nomor_meja,
        p.total_harga,
        p.status_bayar,
        p.created_at
    ORDER BY p.created_at DESC
    LIMIT 5
");

// =========================
// STATUS MEJA
// =========================
$q_status_meja = mysqli_query($db, "
    SELECT nomor_meja, status_meja
    FROM meja
    ORDER BY nomor_meja ASC
    LIMIT 8
");

// =========================
// PENDAPATAN 7 HARI TERAKHIR
// =========================
$data_grafik = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));

    $q_grafik = mysqli_query($db, "
        SELECT COALESCE(SUM(total_harga), 0) AS total
        FROM pesanan
        WHERE status_bayar = 'lunas'
        AND DATE(created_at) = '$tanggal'
    ");

    $hasil = mysqli_fetch_assoc($q_grafik);

    $data_grafik[] = [
        'tanggal' => date('d/m', strtotime($tanggal)),
        'total' => (float)$hasil['total']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - RM. Tambaksari</title>

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
            margin-bottom: 8px;
        }

        .logo h2 {
            font-size: 18px;
        }

        .logo p {
            font-size: 12px;
            color: #e8d6d6;
            margin-top: 4px;
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
            transition: background 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #8d2630;
        }

        .menu .logout {
            margin-top: 25px;
            color: #ffdddd;
        }

        /* =========================
           MAIN
        ========================= */
        .main {
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
            font-size: 28px;
            color: #4e0e15;
        }

        .topbar p {
            color: #777;
            font-size: 14px;
            margin-top: 4px;
        }

        .admin-name {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            font-size: 14px;
        }

        /* =========================
           STAT CARD
        ========================= */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,.07);
            border-left: 4px solid #8d2630;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(141, 38, 48, 0.15);
        }

        .card.lunas {
            border-left-color: #10b981;
        }

        .card.belum {
            border-left-color: #f59e0b;
        }

        .card .icon {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .card p {
            color: #777;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .card h2 {
            color: #4e0e15;
            font-size: 25px;
        }

        .card.lunas h2 { color: #065f46; }
        .card.belum h2 { color: #92400e; }

        /* =========================
           CONTENT
        ========================= */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,.07);
        }

        .box-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .box-title h3 {
            color: #4e0e15;
            font-size: 17px;
        }

        .box-title strong {
            color: #8d2630;
            font-size: 16px;
        }

        .box-title a {
            color: #8d2630;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .box-title a:hover {
            text-decoration: underline;
        }

        /* =========================
           GRAFIK
        ========================= */
        .chart {
            height: 260px;
            display: flex;
            align-items: flex-end;
            gap: 15px;
            padding: 20px 10px 30px;
            border-bottom: 1px solid #ddd;
        }

        .bar-container {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
        }

        .bar {
            width: 70%;
            min-height: 5px;
            background: linear-gradient(180deg, #b9313f 0%, #8d2630 100%);
            border-radius: 6px 6px 0 0;
            transition: all 0.3s;
        }

        .bar:hover {
            background: linear-gradient(180deg, #d94654 0%, #a8323e 100%);
        }

        .bar-label {
            margin-top: 8px;
            font-size: 11px;
            color: #777;
            font-weight: 600;
        }

        /* =========================
           PESANAN TERBARU
        ========================= */
        .order {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
        }

        .order:last-child {
            border-bottom: none;
        }

        .order-info strong {
            display: block;
            color: #4e0e15;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .order-info small {
            color: #777;
            font-size: 12.5px;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .antre {
            background: #fff0c2;
            color: #8a6500;
        }

        .dimasak {
            background: #ffe1cc;
            color: #a84d00;
        }

        .selesai {
            background: #d9f5df;
            color: #16702d;
        }

        .diantar {
            background: #e1e8ff;
            color: #304da8;
        }

        .lunas {
            background: #d1fae5;
            color: #065f46;
        }

        /* =========================
           MEJA
        ========================= */
        .meja-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .meja {
            text-align: center;
            padding: 18px 8px;
            border-radius: 10px;
            font-weight: bold;
            transition: transform 0.2s;
        }

        .meja:hover {
            transform: scale(1.03);
        }

        .meja.kosong {
            background: #dff6e5;
            color: #19743a;
        }

        .meja.terisi {
            background: #ffe0e3;
            color: #a52332;
        }

        .meja small {
            display: block;
            margin-top: 5px;
            font-weight: normal;
            font-size: 11px;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media(max-width: 1000px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 700px) {
            .sidebar {
                width: 70px;
                padding: 20px 8px;
            }
            .logo h2,
            .logo p,
            .menu a span {
                display: none;
            }
            .main {
                margin-left: 70px;
                padding: 20px;
            }
            .cards {
                grid-template-columns: 1fr;
            }
            .meja-list {
                grid-template-columns: repeat(2, 1fr);
            }
            .topbar h1 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->
<aside class="sidebar">
    <div class="logo">
        <img src="../assets/images/logo.png" alt="Logo RM Tambaksari">
        <h2>RM. TAMBAKSARI</h2>
        <p>Onggorawe Demak</p>
    </div>

    <ul class="menu">
        <li><a href="dashboard.php" class="active">🏠 <span>Dashboard</span></a></li>
        <li><a href="users/index.php">👤 <span>User</span></a></li>
        <li><a href="menu/index.php">🍽️ <span>Menu</span></a></li>
        <li><a href="kategori/index.php">📋 <span>Kategori</span></a></li>
        <li><a href="meja/index.php">🪑 <span>Meja</span></a></li>
        <li><a href="laporan/index.php">📊 <span>Laporan</span></a></li>
        <li><a href="../logout.php" class="logout">🚪 <span>Logout</span></a></li>
    </ul>
</aside>


<!-- =========================
     MAIN
========================= -->
<main class="main">

    <div class="topbar">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Selamat datang di sistem RM. Tambaksari</p>
        </div>
        <div class="admin-name">
            👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
        </div>
    </div>


    <!-- =========================
         STATISTIK
    ========================= -->
    <div class="cards">

        <div class="card">
            <div class="icon">👤</div>
            <p>TOTAL USER</p>
            <h2><?= number_format($total_user, 0, ',', '.'); ?></h2>
        </div>

        <div class="card">
            <div class="icon">🍽️</div>
            <p>TOTAL MENU</p>
            <h2><?= number_format($total_menu, 0, ',', '.'); ?></h2>
        </div>

        <div class="card">
            <div class="icon">🪑</div>
            <p>TOTAL MEJA</p>
            <h2><?= number_format($total_meja, 0, ',', '.'); ?></h2>
        </div>

        <div class="card">
            <div class="icon">🧾</div>
            <p>TOTAL TRANSAKSI</p>
            <h2><?= number_format($total_transaksi, 0, ',', '.'); ?></h2>
        </div>

    </div>

    <!-- =========================
         RINGKASAN PENDAPATAN
    ========================= -->
    <div class="cards" style="grid-template-columns: repeat(2, 1fr);">

        <div class="card lunas">
            <div class="icon">💰</div>
            <p>PENDAPATAN LUNAS</p>
            <h2>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h2>
        </div>

        <div class="card belum">
            <div class="icon">⏳</div>
            <p>BELUM DIBAYAR</p>
            <h2>Rp <?= number_format($total_belum, 0, ',', '.'); ?></h2>
        </div>

    </div>


    <!-- =========================
         PENDAPATAN + PESANAN TERBARU
    ========================= -->
    <div class="content-grid">

        <div class="box">
            <div class="box-title">
                <h3>Pendapatan 7 Hari Terakhir</h3>
                <strong>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></strong>
            </div>

            <div class="chart">
                <?php
                $nilai_maksimal = 0;
                foreach ($data_grafik as $data) {
                    if ($data['total'] > $nilai_maksimal) {
                        $nilai_maksimal = $data['total'];
                    }
                }
                if ($nilai_maksimal == 0) {
                    $nilai_maksimal = 1;
                }
                ?>

                <?php foreach ($data_grafik as $data): ?>
                    <?php
                    $tinggi = ($data['total'] / $nilai_maksimal) * 180;
                    if ($tinggi < 5 && $data['total'] > 0) {
                        $tinggi = 5;
                    }
                    ?>
                    <div class="bar-container">
                        <div class="bar" style="height: <?= $tinggi; ?>px;"
                             title="Rp <?= number_format($data['total'], 0, ',', '.'); ?>"></div>
                        <div class="bar-label"><?= $data['tanggal']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>


        <!-- PESANAN TERBARU -->
        <div class="box">
            <div class="box-title">
                <h3>Pesanan Terbaru</h3>
                <a href="laporan/index.php">Lihat semua</a>
            </div>

            <?php if ($q_pesanan && mysqli_num_rows($q_pesanan) > 0): ?>
                <?php while ($pesanan = mysqli_fetch_assoc($q_pesanan)): ?>
                    <div class="order">
                        <div class="order-info">
                            <strong>
                                Meja <?= sprintf("%02d", (int)$pesanan['nomor_meja']); ?>
                            </strong>
                            <small>
                                Rp <?= number_format((float)$pesanan['total_harga'], 0, ',', '.'); ?>
                            </small>
                        </div>

                        <?php
                        // Tentukan status yang ditampilkan
                        if (($pesanan['status_bayar'] ?? '') === 'lunas') {
                            $status = 'lunas';
                        } else {
                            // Ambil status memasak pertama dari GROUP_CONCAT
                            $status_masak = 'antre';
                            if (!empty($pesanan['status_memasak'])) {
                                $arr = explode(',', $pesanan['status_memasak']);
                                $status_masak = trim($arr[0]);
                            }
                            // Map 'dimasak' sesuai class CSS
                            $status = $status_masak;
                        }

                        $nama_status = [
                            'antre'   => 'Antre',
                            'dimasak' => 'Dimasak',
                            'selesai' => 'Selesai',
                            'diantar' => 'Diantar',
                            'lunas'   => 'Lunas'
                        ];
                        ?>

                        <span class="status <?= htmlspecialchars($status); ?>">
                            <?= $nama_status[$status] ?? ucfirst($status); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#777;">Belum ada pesanan.</p>
            <?php endif; ?>
        </div>

    </div>


    <!-- =========================
         STATUS MEJA
    ========================= -->
    <div class="box">
        <div class="box-title">
            <h3>Status Meja</h3>
            <a href="meja/index.php">Kelola Meja</a>
        </div>

        <div class="meja-list">
            <?php if ($q_status_meja && mysqli_num_rows($q_status_meja) > 0): ?>
                <?php while ($meja = mysqli_fetch_assoc($q_status_meja)): ?>
                    <div class="meja <?= htmlspecialchars($meja['status_meja']); ?>">
                        Meja <?= sprintf("%02d", (int)$meja['nomor_meja']); ?>
                        <small><?= ucfirst($meja['status_meja']); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#777;">Belum ada meja.</p>
            <?php endif; ?>
        </div>
    </div>

</main>

</body>
</html>