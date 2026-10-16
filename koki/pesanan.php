<?php
session_start();
include "../config.php";

// Cek login koki
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Ambil daftar pesanan
$query = mysqli_query($db, "
    SELECT
        dp.id_detail,
        dp.id_pesanan,
        dp.jumlah,
        dp.status_memasak,
        mn.nama_menu,
        m.nomor_meja,
        p.created_at
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    JOIN menu mn ON dp.id_menu = mn.id_menu
    JOIN meja m ON p.id_meja = m.id_meja
    ORDER BY p.created_at DESC, dp.id_detail DESC
");

if (!$query) {
    die("Query gagal: " . mysqli_error($db));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pesanan Koki - RM Tambaksari</title>

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

        .menu a:hover,
        .menu a.active {
            background: #922733;
        }

        .menu .logout {
            margin-top: 25px;
        }

        /* KONTEN UTAMA */
        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
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
            white-space: nowrap;
        }

        .content-box {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
        }

        .content-header {
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
            border-bottom: 1px solid #eee;
            font-size: 13px;
            text-align: center;
            vertical-align: middle;
        }

        tr:hover td {
            background: #fff8f5;
        }

        /* STATUS */
        .status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .antre {
            background: #fff0cf;
            color: #8a5b00;
        }

        .dimasak {
            background: #dcecff;
            color: #1457a6;
        }

        .selesai {
            background: #d9f5df;
            color: #187332;
        }

        /* TOMBOL AKSI */
        .btn-aksi {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            border: none;
            font-size: 12.5px;
            font-weight: bold;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .btn-mulai {
            background: #b8860b;
        }

        .btn-mulai:hover {
            background: #d4a017;
            transform: translateY(-1px);
        }

        .btn-selesai {
            background: #187332;
        }

        .btn-selesai:hover {
            background: #21a04a;
            transform: translateY(-1px);
        }

        .selesai-text {
            color: #187332;
            font-weight: bold;
            font-size: 12.5px;
        }

        .kosong {
            text-align: center;
            padding: 25px;
            color: #777;
        }

        /* RESPONSIVE */
        @media (max-width: 800px) {
            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 20px;
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
                overflow: hidden;
                white-space: nowrap;
            }

            .main {
                margin-left: 65px;
                padding: 15px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
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

            .content-box {
                padding: 15px;
            }
        }

        @media print {
            .sidebar,
            .admin-name,
            form {
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
            <a href="pesanan.php" class="active">
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

<!-- KONTEN UTAMA -->
<main class="main">

    <div class="topbar">
        <div>
            <h1>Daftar Pesanan</h1>
            <p>Kelola antrean dan status memasak pesanan pelanggan.</p>
        </div>

        <div class="admin-name">
            👤 <?= htmlspecialchars($_SESSION['username'] ?? 'koki'); ?>
        </div>
    </div>

    <div class="content-box">

        <div class="content-header">
            <h2>Antrean Pesanan Dapur</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>ID Pesanan</th>
                        <th>Meja</th>
                        <th>Nama Menu</th>
                        <th>Jumlah</th>
                        <th>Waktu Pesanan</th>
                        <th>Status Memasak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($data = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?= $no++; ?></td>

                                <td>
                                    #<?= (int) $data['id_pesanan']; ?>
                                </td>

                                <td>
                                    Meja <?= htmlspecialchars($data['nomor_meja']); ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($data['nama_menu']); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= (int) $data['jumlah']; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        date('d-m-Y H:i', strtotime($data['created_at']))
                                    ); ?>
                                </td>

                                <td>
                                    <span class="status <?= htmlspecialchars($data['status_memasak']); ?>">
                                        <?= strtoupper(htmlspecialchars($data['status_memasak'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($data['status_memasak'] == 'antre'): ?>

                                        <!-- Tombol Mulai Memasak -->
                                        <form method="POST" action="status_pesanan.php" style="display:inline;">
                                            <input type="hidden" name="id_detail" value="<?= (int) $data['id_detail']; ?>">
                                            <input type="hidden" name="status_memasak" value="dimasak">
                                            <button type="submit" class="btn-aksi btn-mulai">
                                                ▶ Mulai Memasak
                                            </button>
                                        </form>

                                    <?php elseif ($data['status_memasak'] == 'dimasak'): ?>

                                        <!-- Tombol Selesai Memasak -->
                                        <form method="POST" action="status_pesanan.php" style="display:inline;">
                                            <input type="hidden" name="id_detail" value="<?= (int) $data['id_detail']; ?>">
                                            <input type="hidden" name="status_memasak" value="selesai">
                                            <button type="submit" class="btn-aksi btn-selesai">
                                                ✓ Selesai Memasak
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <span class="selesai-text">
                                            ✓ Sudah selesai
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="kosong">
                                Belum ada pesanan.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

</body>
</html>