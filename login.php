<?php
session_start();
include "config.php";

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($db, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $data = mysqli_fetch_array($query);

    if (mysqli_num_rows($query) > 0){

        $_SESSION['status'] = "login";
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['nama'] = $data['nama'];

        if($data['role'] == 'admin'){
            header("Location: admin/dashboard.php");
            exit;
        } elseif($data['role'] == 'pelayan'){
            header("Location: pelayan/index.php");
            exit;
        } elseif($data['role'] == 'koki'){
            header("Location: koki/dashboard.php");
            exit;
        }
        exit;

    } else {
        echo "<script>
        alert('Username atau Password salah!');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RM. Tambaksari</title>
    
    <!-- Import Font Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Import FontAwesome untuk Ikon (User & Lock) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- Reset & Base --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Background gambar restoran dengan efek gelap (overlay) */
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('assets/images/resto.png') no-repeat center center/cover;
            /* Catatan: Ganti 'assets/images/background-resto.jpg' dengan path gambar restoran Anda */
            background-color: #333; /* Fallback warna jika gambar gagal dimuat */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* --- Container Utama (Kotak Putih) --- */
        .login-card {
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            padding: 40px 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 0.5s ease-out;
            margin: 20px; /* Agar tidak mentok di layar HP */
        }

        /* --- Header (Logo) --- */
        .login-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .logo-icon {
            font-size: 40px;
            color: #5a1a22; /* Warna merah marun */
        }

        .logo-text {
            text-align: left;
        }

        .logo-text h1 {
            font-size: 20px;
            color: #333;
            font-weight: 700;
            line-height: 1.2;
        }

        .logo-text p {
            font-size: 13px;
            color: #d9384e; /* Warna merah terang */
            font-weight: 500;
        }

        /* --- Judul Form --- */
        .login-title {
            margin-bottom: 25px;
        }

        .login-title h2 {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .login-title p {
            font-size: 12px;
            color: #777;
        }

        /* --- Form & Input --- */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        /* Ikon di dalam input */
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Padding kiri lebih besar untuk ikon */
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .input-group input:focus {
            border-color: #d9384e;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(217, 56, 78, 0.1);
        }

        /* --- Tombol Login --- */
        .btn-submit {
            background-color: #8c1c2b; /* Warna merah marun sesuai gambar */
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #6e131f;
        }

        /* --- Footer di dalam Card --- */
        .login-footer {
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .login-footer p {
            font-size: 11px;
            color: #888;
        }

        /* --- Animasi --- */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <!-- Logo dan Judul -->
        <div class="login-header">
            <div class="logo-icon">
                <!-- Ikon rumah sebagai placeholder logo. Ganti dengan <img src="..."> jika punya logo asli -->
                <i class="fa-solid fa-house-chimney"></i>
            </div>
            <div class="logo-text">
                <h1>RM. Tambaksari</h1>
                <p>Onggorawe Demak</p>
            </div>
        </div>

        <div class="login-title">
            <h2>Login ke Akun Anda</h2>
            <p>Silakan masukkan username dan password</p>
        </div>

        <!-- Form Login -->
        <form method="post" class="login-form">
            
            <div class="input-group">
                <i class="fa-regular fa-user"></i>
                <input type="text" name="username" placeholder="Username" required autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" name="login" class="btn-submit">Login</button>

            <a href="index.php">Kembali Ke Index</a>
        </form>

        <!-- Footer Card -->
        <div class="login-footer">
            <p>&copy; 2026 RM. Tambaksari Onggorawe Demak</p>
        </div>

    </div>

</body>
</html>