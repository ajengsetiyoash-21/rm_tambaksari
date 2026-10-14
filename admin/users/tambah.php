<?php
session_start();
include "../../config.php";

if (isset($_POST['simpan'])) {

    $username = mysqli_real_escape_string($db, $_POST['username']);
    $nama     = mysqli_real_escape_string($db, $_POST['nama']);
    $password = $_POST['password'];
    $role     = mysqli_real_escape_string($db, $_POST['role']);

    // Cek apakah username sudah dipakai
    $cek = mysqli_query($db, "
        SELECT * FROM users
        WHERE username = '$username'
    ");

    if (mysqli_num_rows($cek) > 0) {
        echo "<script>
                alert('Username sudah digunakan!');
                window.location='tambah.php';
              </script>";
        exit;
    }

    // Hash password (disarankan)
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $simpan = mysqli_query($db, "
        INSERT INTO users (username, password, nama, role)
        VALUES ('$username', '$password_hash', '$nama', '$role')
    ");

    if ($simpan) {
        echo "<script>
                alert('User berhasil ditambahkan!');
                window.location='index.php';
              </script>";
    } else {
        echo "<script>
                alert('User gagal ditambahkan: " . mysqli_error($db) . "');
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah User - RM. Tambaksari</title>

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
           CONTENT BOX
        ========================= */

        .content-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
            color: #5b1018;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #6d1720;
        }

        /* =========================
           TOMBOL
        ========================= */

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-simpan {
            background: #6d1720;
            color: white;
        }

        .btn-simpan:hover {
            background: #8d2630;
        }

        .btn-kembali {
            background: #777;
            color: white;
            margin-left: 5px;
        }

        .btn-kembali:hover {
            background: #555;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 800px) {
            .sidebar { width: 210px; }
            .main { margin-left: 210px; }
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

    </style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        <img src="../../assets/images/logo.png" alt="Logo RM Tambaksari">

        <h2>RM. TAMBAKSARI</h2>

        <p>Onggorawe Demak</p>

    </div>

    <ul class="menu">

        <li>
            <a href="../dashboard.php">
                🏠 &nbsp; Dashboard
            </a>
        </li>

        <li>
            <a href="index.php" class="active">
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

    <div class="topbar">

        <div>

            <h1>Tambah User</h1>

            <p>Tambahkan pengguna baru RM. Tambaksari</p>

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
         FORM TAMBAH USER
    ========================= -->

    <div class="content-box">

        <form method="POST">

            <div class="form-group">
                <label>Username</label>
                <input
                    type="text"
                    name="username"
                    placeholder="Contoh: pelayan01"
                    required
                >
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input
                    type="text"
                    name="nama"
                    placeholder="Contoh: Budi Santoso"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Minimal 6 karakter"
                    minlength="6"
                    required
                >
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin</option>
                    <option value="pelayan">Pelayan</option>
                    <option value="koki">Koki</option>
                </select>
            </div>

            <button
                type="submit"
                name="simpan"
                class="btn btn-simpan"
            >
                Simpan
            </button>

            <a
                href="index.php"
                class="btn btn-kembali"
            >
                Kembali
            </a>

        </form>

    </div>

</main>

</body>

</html>