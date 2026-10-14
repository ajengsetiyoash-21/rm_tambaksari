<?php
session_start();
include "../../config.php";

/* =========================
   CEK ID MENU
========================= */

if (!isset($_GET['id_menu'])) {
    header("Location: index.php");
    exit;
}

$id_menu = $_GET['id_menu'];


/* =========================
   AMBIL DATA MENU
========================= */

$query = mysqli_query($db, "
    SELECT *
    FROM menu
    WHERE id_menu = '$id_menu'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>
            alert('Data menu tidak ditemukan!');
            window.location='index.php';
          </script>";
    exit;
}


/* =========================
   PROSES EDIT
========================= */

if (isset($_POST['update'])) {

    $nama_menu = mysqli_real_escape_string($db, $_POST['nama_menu']);
    $id_kategori = $_POST['id_kategori'];
    $harga = $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($db, $_POST['deskripsi']);
    $status_stok = $_POST['status_stok'];

    // Foto lama
    $foto_lama = $data['foto'];

    // Default menggunakan foto lama
    $nama_foto = $foto_lama;


    /* =========================
       CEK FOTO BARU
    ========================= */

    if (
        isset($_FILES['foto']) &&
        $_FILES['foto']['error'] == 0 &&
        $_FILES['foto']['name'] != ""
    ) {

        $nama_asli = $_FILES['foto']['name'];
        $tmp_foto = $_FILES['foto']['tmp_name'];

        // Ambil ekstensi
        $ekstensi = strtolower(
            pathinfo($nama_asli, PATHINFO_EXTENSION)
        );

        // Ekstensi yang diperbolehkan
        $ekstensi_valid = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        if (!in_array($ekstensi, $ekstensi_valid)) {

            echo "<script>
                    alert('Format foto harus JPG, JPEG, PNG, atau WEBP!');
                    window.location='edit.php?id_menu=$id_menu';
                  </script>";
            exit;
        }


        // Nama foto baru
        $nama_foto = time() . "_" . uniqid() . "." . $ekstensi;


        // Folder foto
        $folder_foto = __DIR__ . "/../../assets/images/menu/";


        // Buat folder jika belum ada
        if (!is_dir($folder_foto)) {
            mkdir($folder_foto, 0777, true);
        }


        // Upload foto baru
        if (!move_uploaded_file(
            $tmp_foto,
            $folder_foto . $nama_foto
        )) {

            echo "<script>
                    alert('Foto gagal diupload!');
                    window.location='edit.php?id_menu=$id_menu';
                  </script>";
            exit;
        }


        // Hapus foto lama
        if (!empty($foto_lama)) {

            $file_lama = $folder_foto . $foto_lama;

            if (file_exists($file_lama)) {
                unlink($file_lama);
            }
        }
    }


    /* =========================
       UPDATE DATA
    ========================= */

    $update = mysqli_query($db, "
        UPDATE menu SET

            id_kategori = '$id_kategori',

            nama_menu = '$nama_menu',

            harga = '$harga',

            foto = '$nama_foto',

            deskripsi = '$deskripsi',

            status_stok = '$status_stok'

        WHERE id_menu = '$id_menu'
    ");


    if ($update) {

        echo "<script>
                alert('Menu berhasil diperbarui!');
                window.location='index.php';
              </script>";

    } else {

        echo "<script>
                alert('Menu gagal diperbarui!');
                window.location='edit.php?id_menu=$id_menu';
              </script>";
    }
}


/* =========================
   AMBIL DATA KATEGORI
========================= */

$kategori = mysqli_query(
    $db,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Menu - RM. Tambaksari</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            width: 240px;
            height: 100vh;
            background: #5b1018;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
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
           SIDEBAR MENU
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
           CARD
        ========================= */

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 800px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
        }


        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
            color: #444;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #5b1018;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }


        /* =========================
           FOTO LAMA
        ========================= */

        .foto-lama {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 8px;
            display: block;
        }

        .tidak-ada-foto {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee;
            color: #888;
            border-radius: 8px;
            font-size: 12px;
            text-align: center;
            margin-bottom: 8px;
        }

        .keterangan {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }


        /* =========================
           BUTTON
        ========================= */

        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 8px;
        }

        .btn {
            border: none;
            padding: 10px 18px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-update {
            background: #5b1018;
            color: white;
        }

        .btn-update:hover {
            background: #741b25;
        }

        .btn-kembali {
            background: #ddd;
            color: #333;
        }

        .btn-kembali:hover {
            background: #ccc;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 20px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

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

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- HEADER -->

    <div class="topbar">

        <div>

            <h1>Edit Menu</h1>

            <p>
                Ubah data makanan atau minuman RM. Tambaksari
            </p>

        </div>


        <div class="admin-name">

            👤

            <?= htmlspecialchars(
                $_SESSION['username'] ?? 'admin'
            ); ?>

        </div>

    </div>


    <!-- FORM -->

    <div class="card">

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- NAMA MENU -->

            <div class="form-group">

                <label>Nama Menu</label>

                <input
                    type="text"
                    name="nama_menu"
                    value="<?= htmlspecialchars($data['nama_menu']); ?>"
                    required
                >

            </div>


            <!-- KATEGORI -->

            <div class="form-group">

                <label>Kategori</label>

                <select
                    name="id_kategori"
                    required
                >

                    <option value="">
                        -- Pilih Kategori --
                    </option>

                    <?php while ($kat = mysqli_fetch_assoc($kategori)) { ?>

                        <option
                            value="<?= $kat['id_kategori']; ?>"
                            <?= (
                                $kat['id_kategori'] == $data['id_kategori']
                            ) ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars(
                                $kat['nama_kategori']
                            ); ?>
                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- HARGA -->

            <div class="form-group">

                <label>Harga</label>

                <input
                    type="number"
                    name="harga"
                    value="<?= $data['harga']; ?>"
                    min="0"
                    required
                >

            </div>


            <!-- FOTO -->

            <div class="form-group">

                <label>Foto Menu</label>


                <?php if (!empty($data['foto'])) { ?>

                    <img
                        src="../../assets/images/menu/<?= htmlspecialchars($data['foto']); ?>"
                        class="foto-lama"
                        alt="Foto <?= htmlspecialchars($data['nama_menu']); ?>"
                        onerror="this.style.display='none'; document.getElementById('foto-error').style.display='flex';"
                    >

                    <div
                        id="foto-error"
                        class="tidak-ada-foto"
                        style="display:none;"
                    >
                        Foto tidak ditemukan
                    </div>

                <?php } else { ?>

                    <div class="tidak-ada-foto">
                        Tidak ada foto
                    </div>

                <?php } ?>


                <input
                    type="file"
                    name="foto"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <p class="keterangan">
                    Kosongkan jika tidak ingin mengganti foto.
                </p>

            </div>


            <!-- DESKRIPSI -->

            <div class="form-group">

                <label>Deskripsi</label>

                <textarea
                    name="deskripsi"
                    placeholder="Masukkan deskripsi menu"
                ><?= htmlspecialchars($data['deskripsi']); ?></textarea>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>Status Stok</label>

                <select
                    name="status_stok"
                    required
                >

                    <option
                        value="tersedia"
                        <?= $data['status_stok'] == 'tersedia'
                            ? 'selected'
                            : ''; ?>
                    >
                        Tersedia
                    </option>

                    <option
                        value="habis"
                        <?= $data['status_stok'] == 'habis'
                            ? 'selected'
                            : ''; ?>
                    >
                        Habis
                    </option>

                </select>

            </div>


            <!-- BUTTON -->

            <div class="button-group">

                <button
                    type="submit"
                    name="update"
                    class="btn btn-update"
                >
                    💾 Simpan Perubahan
                </button>

                <a
                    href="index.php"
                    class="btn btn-kembali"
                >
                    ← Kembali
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>