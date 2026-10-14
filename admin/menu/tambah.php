<?php
session_start();
include "../../config.php";

if (isset($_POST['simpan'])) {

    $nama_menu = mysqli_real_escape_string($db, $_POST['nama_menu']);
    $id_kategori = $_POST['id_kategori'];
    $harga = $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($db, $_POST['deskripsi']);
    $status_stok = $_POST['status_stok'];

    /* =========================
       UPLOAD FOTO
    ========================= */

    $nama_foto = "";

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

        $nama_asli = $_FILES['foto']['name'];
        $tmp_foto = $_FILES['foto']['tmp_name'];

        // Ambil ekstensi file
        $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

        // Ekstensi yang diperbolehkan
        $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ekstensi, $ekstensi_valid)) {

            echo "<script>
                    alert('Format foto harus JPG, JPEG, PNG, atau WEBP!');
                    window.location='tambah.php';
                  </script>";
            exit;

        }

        // Buat nama file baru
        $nama_foto = time() . "_" . uniqid() . "." . $ekstensi;

        // Lokasi folder foto
        $folder_foto = __DIR__ . "/../../assets/images/menu/";

        // Jika folder belum ada, buat folder
        if (!is_dir($folder_foto)) {
            mkdir($folder_foto, 0777, true);
        }

        // Upload foto
        if (!move_uploaded_file($tmp_foto, $folder_foto . $nama_foto)) {

            echo "<script>
                    alert('Foto gagal diupload!');
                    window.location='tambah.php';
                  </script>";
            exit;
        }
    }

    /* =========================
       SIMPAN DATA MENU
    ========================= */

    $query = mysqli_query($db, "
        INSERT INTO menu 
        (
            id_kategori,
            nama_menu,
            harga,
            foto,
            deskripsi,
            status_stok
        )
        VALUES
        (
            '$id_kategori',
            '$nama_menu',
            '$harga',
            '$nama_foto',
            '$deskripsi',
            '$status_stok'
        )
    ");

    if ($query) {

        echo "<script>
                alert('Menu berhasil ditambahkan!');
                window.location='index.php';
              </script>";

    } else {

        // Jika database gagal, hapus foto yang sudah terupload
        if ($nama_foto != "") {

            $file_hapus = __DIR__ . "/../../assets/images/menu/" . $nama_foto;

            if (file_exists($file_hapus)) {
                unlink($file_hapus);
            }
        }

        echo "<script>
                alert('Menu gagal ditambahkan!');
                window.location='tambah.php';
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Menu - RM. Tambaksari</title>

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
            padding: 20px 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 8px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .logo h2 {
            font-size: 17px;
            margin-bottom: 3px;
        }

        .logo span {
            font-size: 11px;
            color: #ddd;
        }

        .menu-sidebar {
            margin-top: 20px;
        }

        .menu-sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 11px 12px;
            margin-bottom: 5px;
            border-radius: 7px;
            font-size: 14px;
        }

        .menu-sidebar a:hover,
        .menu-sidebar a.active {
            background: #7a1b25;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h1 {
            font-size: 26px;
            color: #5b1018;
            margin-bottom: 5px;
        }

        .page-title p {
            font-size: 14px;
            color: #777;
        }

        /* =========================
           CARD
        ========================= */

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 800px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
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

        .btn-simpan {
            background: #5b1018;
            color: white;
        }

        .btn-simpan:hover {
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

        <div>

            <h2>RM. Tambaksari</h2>

            <span>Onggorawe Demak</span>

        </div>

    </div>


    <div class="menu-sidebar">

        <a href="../dashboard.php">
            🏠 Dashboard
        </a>

        <a href="../users/index.php">
            👤 Users
        </a>

        <a href="index.php" class="active">
            🍽️ Menu
        </a>

        <a href="../kategori/index.php">
            📂 Kategori
        </a>

        <a href="../meja/index.php">
            🪑 Meja
        </a>

        <a href="../laporan/index.php">
            📊 Laporan
        </a>

        <a href="../../logout.php">
            🚪 Logout
        </a>

    </div>

</div>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main">

    <div class="page-title">

        <h1>Tambah Menu</h1>

        <p>
            Tambahkan data makanan atau minuman baru.
        </p>

    </div>


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
                    placeholder="Masukkan nama menu"
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

                    <?php while ($data = mysqli_fetch_assoc($kategori)) { ?>

                        <option
                            value="<?= $data['id_kategori']; ?>"
                        >
                            <?= htmlspecialchars($data['nama_kategori']); ?>
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
                    placeholder="Masukkan harga"
                    min="0"
                    required
                >

            </div>


            <!-- FOTO -->

            <div class="form-group">

                <label>Foto Menu</label>

                <input
                    type="file"
                    name="foto"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>


            <!-- DESKRIPSI -->

            <div class="form-group">

                <label>Deskripsi</label>

                <textarea
                    name="deskripsi"
                    placeholder="Masukkan deskripsi menu"
                ></textarea>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>Status Stok</label>

                <select
                    name="status_stok"
                    required
                >

                    <option value="tersedia">
                        Tersedia
                    </option>

                    <option value="habis">
                        Habis
                    </option>

                </select>

            </div>


            <!-- BUTTON -->

            <div class="button-group">

                <button
                    type="submit"
                    name="simpan"
                    class="btn btn-simpan"
                >
                    💾 Simpan
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