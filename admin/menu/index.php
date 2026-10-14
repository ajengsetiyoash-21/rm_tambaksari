<?php
session_start();
include "../../config.php";

// Ambil data menu
$ambildata = mysqli_query($db, "
    SELECT 
        menu.*,
        kategori.nama_kategori
    FROM menu
    LEFT JOIN kategori 
        ON menu.id_kategori = kategori.id_kategori
    ORDER BY menu.id_menu ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menu - RM. Tambaksari</title>

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

        /* =========================
           MENU SIDEBAR
        ========================= */

        .menu-sidebar {
            list-style: none;
        }

        .menu-sidebar li {
            margin-bottom: 8px;
        }

        .menu-sidebar a {
            display: block;
            padding: 13px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .menu-sidebar a:hover {
            background: #8d2630;
        }

        .menu-sidebar a.active {
            background: #922733;
        }

        .menu-sidebar .logout {
            margin-top: 25px;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        /* =========================
           HEADER
        ========================= */

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

        /* =========================
           CONTENT
        ========================= */

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

        /* =========================
           TOMBOL TAMBAH
        ========================= */

        .btn-tambah {
            display: inline-block;
            background: #6d1720;
            color: white;
            text-decoration: none;
            padding: 9px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .btn-tambah:hover {
            background: #8d2630;
        }

        /* =========================
           TABEL
        ========================= */

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #6d1720;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 13px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eeeeee;
            font-size: 13px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #fff8f5;
        }

        /* =========================
           FOTO MENU
        ========================= */

        .foto-menu {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            display: block;
        }

        .no-foto {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee;
            color: #888;
            border-radius: 8px;
            font-size: 11px;
            text-align: center;
        }

        /* =========================
           HARGA
        ========================= */

        .harga {
            font-weight: bold;
            color: #6d1720;
        }

        /* =========================
           STATUS STOK
        ========================= */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .tersedia {
            background: #dff5e4;
            color: #197238;
        }

        .habis {
            background: #ffe0e3;
            color: #a52332;
        }

        /* =========================
           TOMBOL AKSI
        ========================= */

        .btn-edit {
            display: inline-block;
            background: #1976d2;
            color: white;
            text-decoration: none;
            padding: 6px 11px;
            border-radius: 5px;
            font-size: 12px;
            margin-right: 4px;
        }

        .btn-edit:hover {
            background: #125ca5;
        }

        .btn-hapus {
            display: inline-block;
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 6px 11px;
            border-radius: 5px;
            font-size: 12px;
        }

        .btn-hapus:hover {
            background: #b52a37;
        }

        /* =========================
           DATA KOSONG
        ========================= */

        .kosong {
            text-align: center;
            color: #777;
            padding: 25px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

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

            .menu-sidebar a {
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

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
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

        <img
            src="../../assets/images/logo.png"
            alt="Logo RM Tambaksari"
        >

        <h2>RM. TAMBAKSARI</h2>

        <p>Onggorawe Demak</p>

    </div>


    <ul class="menu-sidebar">

        <li>
            <a href="../dashboard.php">
                🏠 &nbsp; Dashboard
            </a>
        </li>

        <li>
            <a href="../users/index.php">
                👤 &nbsp; User
            </a>
        </li>

        <li>
            <a href="index.php" class="active">
                🍽️ &nbsp; Menu
            </a>
        </li>

        <li>
            <a href="../kategori/index.php">
                📋 &nbsp; Kategori
            </a>
        </li>

        <li>
            <a href="../meja/index.php">
                🪑 &nbsp; Meja
            </a>
        </li>

        <li>
            <a href="../laporan/index.php">
                📊 &nbsp; Laporan
            </a>
        </li>

        <li>
            <a href="../../logout.php" class="logout">
                🚪 &nbsp; Logout
            </a>
        </li>

    </ul>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">

    <!-- HEADER -->

    <div class="topbar">

        <div>

            <h1>Menu</h1>

            <p>
                Kelola makanan dan minuman RM. Tambaksari
            </p>

        </div>


        <div class="admin-name">

            👤

            <?= htmlspecialchars($_SESSION['username'] ?? 'admin'); ?>

        </div>

    </div>


    <!-- DATA MENU -->

    <div class="content-box">

        <div class="content-header">

            <h2>Data Menu</h2>

            <a
                href="tambah.php"
                class="btn-tambah"
            >
                + Tambah Menu
            </a>

        </div>


        <div class="table-container">

            <table>

                <tr>

                    <th>ID</th>

                    <th>Foto</th>

                    <th>Nama Menu</th>

                    <th>Kategori</th>

                    <th>Harga</th>

                    <th>Stok</th>

                    <th>Aksi</th>

                </tr>


                <?php

                if (mysqli_num_rows($ambildata) > 0) {

                    while ($tampildata = mysqli_fetch_assoc($ambildata)) {

                ?>

                <tr>

                    <!-- ID -->

                    <td>
                        <?= $tampildata['id_menu']; ?>
                    </td>


                    <!-- FOTO -->

                    <td>

                        <?php if (!empty($tampildata['foto'])) { ?>

                            <img
                                src="../../assets/images/menu/<?= htmlspecialchars($tampildata['foto']); ?>"
                                class="foto-menu"
                                alt="<?= htmlspecialchars($tampildata['nama_menu']); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >

                            <div
                                class="no-foto"
                                style="display:none;"
                            >
                                Foto tidak ditemukan
                            </div>

                        <?php } else { ?>

                            <div class="no-foto">
                                Tidak ada foto
                            </div>

                        <?php } ?>

                    </td>


                    <!-- NAMA MENU -->

                    <td>
                        <?= htmlspecialchars($tampildata['nama_menu']); ?>
                    </td>


                    <!-- KATEGORI -->

                    <td>
                        <?= htmlspecialchars($tampildata['nama_kategori'] ?? '-'); ?>
                    </td>


                    <!-- HARGA -->

                    <td class="harga">

                        Rp
                        <?= number_format(
                            $tampildata['harga'],
                            0,
                            ',',
                            '.'
                        ); ?>

                    </td>


                    <!-- STATUS -->

                    <td>

                        <span
                            class="status <?= htmlspecialchars($tampildata['status_stok']); ?>"
                        >

                            <?= ucfirst(
                                htmlspecialchars($tampildata['status_stok'])
                            ); ?>

                        </span>

                    </td>


                    <!-- AKSI -->

                    <td>

                        <a
                            href="edit.php?id_menu=<?= $tampildata['id_menu']; ?>"
                            class="btn-edit"
                        >
                            Edit
                        </a>


                        <a
                            href="hapus.php?id_menu=<?= $tampildata['id_menu']; ?>"
                            class="btn-hapus"
                            onclick="return confirm('Yakin ingin menghapus menu ini?');"
                        >
                            Hapus
                        </a>

                    </td>

                </tr>

                <?php

                    }

                } else {

                ?>

                <tr>

                    <td
                        colspan="7"
                        class="kosong"
                    >
                        Belum ada data menu.
                    </td>

                </tr>

                <?php

                }

                ?>

            </table>

        </div>

    </div>

</main>

</body>
</html>