<?php
session_start();
include "../../config.php";

/* =========================
   CEK ID USER
========================= */

if (!isset($_GET['id_user'])) {
    header("Location: index.php");
    exit;
}

$id_user = $_GET['id_user'];


/* =========================
   AMBIL DATA USER
========================= */

$query = mysqli_query($db, "
    SELECT *
    FROM users
    WHERE id_user = '$id_user'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>
            alert('Data user tidak ditemukan!');
            window.location='index.php';
          </script>";
    exit;
}


/* =========================
   PROSES UPDATE
========================= */

if (isset($_POST['update'])) {

    $username = mysqli_real_escape_string(
        $db,
        $_POST['username']
    );

    $nama = mysqli_real_escape_string(
        $db,
        $_POST['nama']
    );

    $role = $_POST['role'];


    /* =========================
       PASSWORD
    ========================= */

    $password = $_POST['password'];

    // Jika password dikosongkan,
    // gunakan password lama
    if ($password == "") {

        $password_query = "";

    } else {

        $password = mysqli_real_escape_string(
            $db,
            $password
        );

        $password_query = ",
            password = '$password'
        ";
    }


    /* =========================
       UPDATE USER
    ========================= */

    $update = mysqli_query($db, "
        UPDATE users SET

            username = '$username',

            nama = '$nama',

            role = '$role'

            $password_query

        WHERE id_user = '$id_user'
    ");


    if ($update) {

        echo "<script>
                alert('Data user berhasil diperbarui!');
                window.location='index.php';
              </script>";

    } else {

        echo "<script>
                alert('Data user gagal diperbarui!');
                window.location='edit.php?id_user=$id_user';
              </script>";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit User - RM. Tambaksari</title>


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
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #5b1018;
        }


        /* =========================
           KETERANGAN
        ========================= */

        .keterangan {
            font-size: 12px;
            color: #777;
            margin-top: 6px;
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
            <a href="index.php" class="active">
                👤 &nbsp; Users
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

            <h1>Edit User</h1>

            <p>
                Ubah data pengguna RM. Tambaksari
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

        <form method="POST">


            <!-- USERNAME -->

            <div class="form-group">

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    value="<?= htmlspecialchars(
                        $data['username']
                    ); ?>"
                    required
                >

            </div>


            <!-- NAMA -->

            <div class="form-group">

                <label>Nama Lengkap</label>

                <input
                    type="text"
                    name="nama"
                    value="<?= htmlspecialchars(
                        $data['nama']
                    ); ?>"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label>Password Baru</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Masukkan password baru"
                >

                <p class="keterangan">
                    Kosongkan jika tidak ingin mengubah password.
                </p>

            </div>


            <!-- ROLE -->

            <div class="form-group">

                <label>Role</label>

                <select
                    name="role"
                    required
                >

                    <option
                        value="admin"
                        <?= $data['role'] == 'admin'
                            ? 'selected'
                            : ''; ?>
                    >
                        Admin
                    </option>

                    <option
                        value="pelayan"
                        <?= $data['role'] == 'pelayan'
                            ? 'selected'
                            : ''; ?>
                    >
                        Pelayan
                    </option>

                    <option
                        value="koki"
                        <?= $data['role'] == 'koki'
                            ? 'selected'
                            : ''; ?>
                    >
                        Koki
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