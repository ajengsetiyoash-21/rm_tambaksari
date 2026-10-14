<?php
session_start();
include "../../config.php";

$ambildata = mysqli_query($db, "SELECT * FROM meja ORDER BY nomor_meja ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meja - RM. Tambaksari</title>

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


        /* LOGO */

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
           MENU
        ========================= */

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
           BOX KATEGORI (Content Box)
        ========================= */

        .content-box {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
        }


        /* HEADER DATA */

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

        th:first-child,
        td:first-child {
            width: 60px;
            text-align: center;
        }

        th:last-child,
        td:last-child {
            width: 180px;
            text-align: center;
        }


        /* =========================
           TOMBOL EDIT
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


        /* =========================
           TOMBOL HAPUS
        ========================= */

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
           QR CODE & STATUS (KHUSUS MEJA)
        ========================= */
        .qr {
            width: 100px;
            height: 100px;
            display: block;
            margin: 0 auto 5px auto;
        }

        .btn-cetak {
            display: inline-block;
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-kosong {
            background: #e5f7e9;
            color: #198754;
        }

        .status-terisi {
            background: #fde5e5;
            color: #dc3545;
        }


        /* =========================
           RESPONSIVE
        ========================= */

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

        <h2>
            RM. TAMBAKSARI
        </h2>

        <p>
            Onggorawe Demak
        </p>

    </div>


    <ul class="menu">

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
            <a href="../menu/index.php">
                🍽️ &nbsp; Menu
            </a>
        </li>

        <li>
            <a href="../kategori/index.php">
                📋 &nbsp; Kategori
            </a>
        </li>

        <li>
            <a href="index.php" class="active">
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
     MAIN CONTENT
========================= -->

<main class="main">


    <!-- HEADER -->

    <div class="topbar">

        <div>

            <h1>
                Meja
            </h1>

            <p>
                Kelola meja RM. Tambaksari
            </p>

        </div>


        <div class="admin-name">

            👤
            <?php
            echo htmlspecialchars(
                $_SESSION['username'] ?? 'admin'
            );
            ?>

        </div>

    </div>


    <!-- =========================
         DATA MEJA
    ========================= -->

    <div class="content-box">

        <div class="content-header">

            <h2>
                Data Meja
            </h2>

            <a
                href="tambah.php"
                class="btn-tambah"
            >
                + Tambah Meja
            </a>

        </div>


        <div class="table-container">

            <table>

                <tr>

                    <th>
                        No
                    </th>

                    <th>
                        Nomor Meja
                    </th>

                    <th>
                        QR Code
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Aksi
                    </th>

                </tr>


                <?php

                if (mysqli_num_rows($ambildata) > 0) {

                    $no = 1;

                    while (
                        $tampildata =
                        mysqli_fetch_assoc($ambildata)
                    ) {
                        
                        $link_qr = "http://localhost/rm_tambaksari/pesan.php?meja=" . $tampildata['id_meja'];

                ?>

                <tr>

                    <td>
                        <?php echo $no++; ?>
                    </td>

                    <td>
                        <strong>
                            Meja <?php echo htmlspecialchars($tampildata['nomor_meja']); ?>
                        </strong>
                    </td>

                    <td>
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($link_qr); ?>"
                            class="qr"
                            alt="QR Meja <?php echo $tampildata['nomor_meja']; ?>"
                        >
                        <a
                            href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($link_qr); ?>"
                            target="_blank"
                            class="btn-cetak"
                        >
                            Lihat QR
                        </a>
                    </td>

                    <td>
                        <?php if ($tampildata['status_meja'] == 'kosong') { ?>
                            <span class="status status-kosong">
                                Kosong
                            </span>
                        <?php } else { ?>
                            <span class="status status-terisi">
                                Terisi
                            </span>
                        <?php } ?>
                    </td>

                    <td>

                        <a
                            href="edit.php?id_meja=<?php echo $tampildata['id_meja']; ?>"
                            class="btn-edit"
                        >
                            Edit
                        </a>

                        <a
                            href="hapus.php?id_meja=<?php echo $tampildata['id_meja']; ?>"
                            class="btn-hapus"
                            onclick="return confirm('Yakin ingin menghapus meja ini?');"
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
                        colspan="5"
                        class="kosong"
                    >
                        Belum ada data meja.
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