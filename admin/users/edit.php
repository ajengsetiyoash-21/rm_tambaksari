<?php
session_start();
include "../../config.php";

// Cek login admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != "admin") {
    header("Location: ../../login.php");
    exit;
}

/* =========================
   CEK ID USER
========================= */
if (!isset($_GET['id_user'])) {
    header("Location: index.php");
    exit;
}
$id_user = (int)$_GET['id_user'];

/* =========================
   AMBIL DATA USER
========================= */
$stmt = mysqli_prepare($db, "SELECT * FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$data  = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

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
$role_valid = ['admin' => 'Admin', 'pelayan' => 'Pelayan', 'koki' => 'Koki'];
$error = '';
$v = ['username' => $data['username'], 'nama' => $data['nama'], 'role' => $data['role']];

if (isset($_POST['update'])) {

    $v['username'] = trim($_POST['username'] ?? '');
    $v['nama']     = trim($_POST['nama'] ?? '');
    $v['role']     = $_POST['role'] ?? '';
    $password      = $_POST['password'] ?? '';

    if ($v['username'] === '' || $v['nama'] === '') {
        $error = 'Username dan nama lengkap wajib diisi.';
    } elseif (!isset($role_valid[$v['role']])) {
        $error = 'Silakan pilih role yang tersedia.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } else {

        // Cek username tidak dipakai user lain
        $cek = mysqli_prepare($db, "SELECT id_user FROM users WHERE username = ? AND id_user <> ?");
        mysqli_stmt_bind_param($cek, "si", $v['username'], $id_user);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        $dipakai = mysqli_stmt_num_rows($cek) > 0;
        mysqli_stmt_close($cek);

        if ($dipakai) {
            $error = 'Username "' . $v['username'] . '" sudah dipakai user lain.';
        } else {

            if ($password === '') {
                // Password dikosongkan: pakai password lama
                $up = mysqli_prepare($db, "UPDATE users SET username = ?, nama = ?, role = ? WHERE id_user = ?");
                mysqli_stmt_bind_param($up, "sssi", $v['username'], $v['nama'], $v['role'], $id_user);
            } else {
                // Password baru disimpan dalam bentuk hash (sama seperti saat tambah user)
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $up = mysqli_prepare($db, "UPDATE users SET username = ?, nama = ?, role = ?, password = ? WHERE id_user = ?");
                mysqli_stmt_bind_param($up, "ssssi", $v['username'], $v['nama'], $v['role'], $password_hash, $id_user);
            }

            $update = mysqli_stmt_execute($up);
            mysqli_stmt_close($up);

            if ($update) {
                echo "<script>
                        alert('Data user berhasil diperbarui!');
                        window.location='index.php';
                      </script>";
                exit;
            } else {
                $error = 'Data user gagal diperbarui. Silakan coba lagi.';
            }
        }
    }
}

// Ikon SVG (sprite di bawah)
function ico($id, $cls = 'i') { return '<svg class="' . $cls . '" aria-hidden="true"><use href="#i-' . $id . '"/></svg>'; }

$nama_admin  = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$inisial_adm = htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#5b1018">
<title>Edit User - RM. Tambaksari</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --maroon: #5b1018;
    --maroon-2: #8d2630;
    --ink: #2a0a0e;
    --bg: #f3f1f1;
    --card: #fff;
    --line: #ebe7e7;
    --muted: #70686a;
    --gold: #f2c46d;
    --tint: #f7e7e9;
    --red: #b3262f;  --red-bg: #fde3e5;
    --side: 250px;
    --tab-h: 66px;
}
* { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    background: var(--bg); color: #1d1a1b; line-height: 1.45;
}
a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid #e7a3aa; outline-offset: 2px; }

.i { width: 20px; height: 20px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.i.lg { width: 22px; height: 22px; }
.i.sm { width: 16px; height: 16px; }

/* ========== SIDEBAR ========== */
.side {
    position: fixed; inset: 0 auto 0 0; width: var(--side);
    background: var(--maroon); color: #fff; padding: 24px 14px;
    display: flex; flex-direction: column; overflow-y: auto; z-index: 50;
}
.brand { display: flex; align-items: center; gap: 12px; padding: 0 8px 20px; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,.15); }
.brand img { width: 42px; height: 42px; object-fit: contain; background: rgba(255,255,255,.09); border-radius: 10px; padding: 4px; }
.brand b { display: block; font-size: 15px; }
.brand span { font-size: 12px; color: #e9cfd2; }
.side ul { list-style: none; display: grid; gap: 4px; }
.side a { display: flex; align-items: center; gap: 12px; padding: 13px 14px; border-radius: 12px; font-size: 14.5px; font-weight: 500; color: #f6e6e8; }
.side a:hover { background: rgba(255,255,255,.1); }
.side a.on { background: #fff; color: var(--maroon); font-weight: 700; }
.side .gap { flex: 1; }
.side .out { color: #ffc9ce; font-weight: 600; }

/* ========== HEADER ========== */
.main { margin-left: var(--side); padding: 28px 32px 40px; max-width: 1240px; }
.top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
.top h1 { font-size: 26px; font-weight: 800; color: var(--ink); letter-spacing: -.3px; }
.top p { color: var(--muted); font-size: 13.5px; }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); padding: 6px 16px 6px 6px; border-radius: 999px; min-width: 0; }
.avatar { width: 40px; height: 40px; border-radius: 50%; flex: none; background: var(--maroon); color: var(--gold); display: grid; place-items: center; font-weight: 800; font-size: 16px; }
.profile .n { min-width: 0; }
.profile b { display: block; font-size: 14px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
.profile small { display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: var(--maroon-2); font-weight: 700; }
.profile small .i { width: 12px; height: 12px; stroke-width: 2.2; }

/* ========== FORM ========== */
.back { display: inline-flex; align-items: center; gap: 6px; color: var(--maroon-2); font-weight: 700; font-size: 13.5px; margin-bottom: 14px; padding: 4px 0; }

.panel { background: var(--card); border-radius: 20px; padding: 24px; border: 1px solid var(--line); max-width: 560px; }
.ph { margin-bottom: 20px; display: flex; align-items: center; gap: 14px; }
.ph .ava { width: 48px; height: 48px; border-radius: 14px; flex: none; display: grid; place-items: center; font-weight: 800; font-size: 18px; background: var(--tint); color: var(--maroon); }
.ph h2 { font-size: 18px; font-weight: 800; color: var(--ink); }
.ph p { font-size: 13px; color: var(--muted); margin-top: 1px; }

.alert { display: flex; gap: 10px; align-items: flex-start; background: var(--red-bg); color: var(--red); border-radius: 12px; padding: 12px 14px; font-size: 13.5px; font-weight: 600; margin-bottom: 18px; }
.alert .i { margin-top: 1px; }

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13.5px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
.field input, .field select {
    width: 100%; min-height: 50px; padding: 12px 14px;
    border: 1.5px solid var(--line); border-radius: 14px;
    font-family: inherit; font-size: 16px; color: var(--ink); background: #fff;
}
.field input::placeholder { color: #a79ea0; }
.field input:focus, .field select:focus { outline: 0; border-color: var(--maroon-2); box-shadow: 0 0 0 4px #f3d6d9; }
.field select {
    appearance: none; -webkit-appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2370686a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 44px;
}
.field .hint { font-size: 12.5px; color: var(--muted); margin-top: 6px; }

.pw { position: relative; }
.pw input { padding-right: 96px; }
.pw button {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
    background: #f1eded; color: var(--ink); border: 0; border-radius: 10px;
    padding: 8px 12px; font: inherit; font-size: 12.5px; font-weight: 700; cursor: pointer;
}
.pw button:hover { background: #e8e1e2; }

.acts { display: flex; gap: 10px; margin-top: 24px; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 20px; border-radius: 14px; font-size: 14.5px; font-weight: 700; border: 0; cursor: pointer; font-family: inherit; }
.btn.p { background: var(--maroon); color: #fff; }
.btn.p:hover { background: var(--maroon-2); }
.btn.g { background: #f1eded; color: var(--ink); }
.btn.g:hover { background: #e8e1e2; }

.tabbar, .sheet, .scrim { display: none; }

/* ========== HP ========== */
@media (max-width: 800px) {
    .side { display: none; }
    .main { margin-left: 0; padding: calc(16px + env(safe-area-inset-top, 0px)) 14px calc(var(--tab-h) + 28px + env(safe-area-inset-bottom, 0px)); }
    .top { align-items: flex-start; }
    .top > div:first-child { min-width: 0; }
    .top h1 { font-size: 20px; }
    .top p { font-size: 12.5px; line-height: 1.35; margin-top: 2px; }
    .profile b { max-width: 100px; }

    .panel { padding: 18px 16px; border-radius: 18px; max-width: none; }
    .acts { flex-direction: column-reverse; }
    .acts .btn { width: 100%; padding: 15px; }

    .tabbar {
        display: grid; grid-template-columns: repeat(5, 1fr);
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
        height: calc(var(--tab-h) + env(safe-area-inset-bottom, 0px));
        padding-bottom: env(safe-area-inset-bottom, 0px);
        background: #fff; border-top: 1px solid var(--line);
        box-shadow: 0 -6px 20px rgba(60,10,16,.08);
    }
    .tabbar a, .tabbar button {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
        background: none; border: 0; font: inherit; font-size: 11px; font-weight: 600; color: var(--muted); cursor: pointer;
    }
    .tabbar .pill { display: grid; place-items: center; padding: 4px 16px; border-radius: 12px; }
    .tabbar .on { color: var(--maroon); }
    .tabbar .on .pill { background: var(--tint); }

    .scrim { display: block; position: fixed; inset: 0; background: rgba(20,5,8,.5); z-index: 70; opacity: 0; pointer-events: none; transition: opacity .2s; }
    .sheet {
        display: block; position: fixed; left: 0; right: 0; bottom: 0; z-index: 80;
        background: #fff; border-radius: 24px 24px 0 0;
        padding: 12px 16px calc(20px + env(safe-area-inset-bottom, 0px));
        transform: translateY(100%); transition: transform .26s ease;
    }
    .sheet .grab { width: 40px; height: 4px; background: #d9d2d3; border-radius: 4px; margin: 0 auto 10px; }
    .sheet a { display: flex; align-items: center; gap: 14px; padding: 15px 8px; font-size: 15px; font-weight: 600; border-bottom: 1px solid var(--line); }
    .sheet a:last-child { border-bottom: 0; color: var(--red); }
    .sheet a .i { color: var(--maroon); }
    .sheet a:last-child .i { color: var(--red); }
    .sheet a.on { color: var(--maroon); background: var(--tint); border-radius: 12px; border-bottom-color: transparent; }
    body.open .scrim { opacity: 1; pointer-events: auto; }
    body.open .sheet { transform: translateY(0); }
    body.open { overflow: hidden; }
}

@media (max-width: 360px) { .profile b { max-width: 70px; } }
@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<!-- SPRITE IKON -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6a2 2 0 0 0 2 2h3Zm0 0v7"/></symbol>
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="i-table" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="3.5" rx="1.2"/><path d="M5 8.5V19"/><path d="M19 8.5V19"/><path d="M9 8.5V14"/><path d="M15 8.5V14"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M8 16v-4"/><path d="M13 16V8"/><path d="M18 16v-7"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></symbol>
    <symbol id="i-more" viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6Z"/><path d="m9 12 2 2 4-4"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></symbol>
    <symbol id="i-back" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></symbol>
</svg>

<!-- SIDEBAR DESKTOP -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../../assets/images/logo.png" alt="Logo RM Tambaksari">
        <div><b>RM. TAMBAKSARI</b><span>Onggorawe Demak</span></div>
    </div>
    <ul>
        <li><a href="../dashboard.php"><?= ico('home'); ?> Dashboard</a></li>
        <li><a href="index.php" class="on"><?= ico('user'); ?> User</a></li>
        <li><a href="../menu/index.php"><?= ico('menu'); ?> Menu</a></li>
        <li><a href="../kategori/index.php"><?= ico('grid'); ?> Kategori</a></li>
        <li><a href="../meja/index.php"><?= ico('table'); ?> Meja</a></li>
        <li><a href="../laporan/index.php"><?= ico('chart'); ?> Laporan</a></li>
    </ul>
    <div class="gap"></div>
    <a href="../../logout.php" class="out"><?= ico('logout'); ?> Logout</a>
</aside>

<main class="main">

    <header class="top">
        <div>
            <h1>Edit User</h1>
            <p>Ubah data pengguna RM. Tambaksari</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial_adm; ?></div>
            <div class="n">
                <b><?= $nama_admin; ?></b>
                <small><?= ico('shield'); ?> Administrator</small>
            </div>
        </div>
    </header>

    <a href="index.php" class="back"><?= ico('back', 'i sm'); ?> Kembali ke data user</a>

    <section class="panel">
        <div class="ph">
            <div class="ava" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($data['nama'] ?: $data['username'], 0, 1))); ?></div>
            <div>
                <h2><?= htmlspecialchars($data['nama']); ?></h2>
                <p>@<?= htmlspecialchars($data['username']); ?></p>
            </div>
        </div>

        <?php if ($error != ''): ?>
        <div class="alert" role="alert"><?= ico('alert'); ?> <span><?= htmlspecialchars($error); ?></span></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($v['username']); ?>"
                       autocapitalize="none" autocorrect="off" required>
            </div>

            <div class="field">
                <label for="nama">Nama lengkap</label>
                <input type="text" id="nama" name="nama"
                       value="<?= htmlspecialchars($v['nama']); ?>" required>
            </div>

            <div class="field">
                <label for="password">Password baru</label>
                <div class="pw">
                    <input type="password" id="password" name="password"
                           placeholder="Masukkan password baru" autocomplete="new-password">
                    <button type="button" id="togglePw" aria-controls="password">Tampilkan</button>
                </div>
                <div class="hint">Kosongkan jika tidak ingin mengubah password.</div>
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <?php foreach ($role_valid as $kode => $label): ?>
                        <option value="<?= $kode; ?>" <?= $v['role'] === $kode ? 'selected' : ''; ?>><?= $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="acts">
                <a href="index.php" class="btn g">Kembali</a>
                <button type="submit" name="update" class="btn p"><?= ico('check', 'i sm'); ?> Simpan perubahan</button>
            </div>

        </form>
    </section>

</main>

<!-- NAVIGASI BAWAH (HP) -->
<nav class="tabbar" aria-label="Navigasi">
    <a href="../dashboard.php"><span class="pill"><?= ico('home', 'i lg'); ?></span>Beranda</a>
    <a href="../menu/index.php"><span class="pill"><?= ico('menu', 'i lg'); ?></span>Menu</a>
    <a href="../meja/index.php"><span class="pill"><?= ico('table', 'i lg'); ?></span>Meja</a>
    <a href="../laporan/index.php"><span class="pill"><?= ico('chart', 'i lg'); ?></span>Laporan</a>
    <button type="button" id="more" class="on" aria-haspopup="true" aria-expanded="false"><span class="pill"><?= ico('more', 'i lg'); ?></span>Lainnya</button>
</nav>

<div class="scrim" id="scrim"></div>
<div class="sheet" id="sheet" role="menu">
    <div class="grab"></div>
    <a href="index.php" class="on" role="menuitem"><?= ico('user', 'i lg'); ?> User</a>
    <a href="../kategori/index.php" role="menuitem"><?= ico('grid', 'i lg'); ?> Kategori</a>
    <a href="../../logout.php" role="menuitem"><?= ico('logout', 'i lg'); ?> Logout</a>
</div>

<script>
(function () {
    var b = document.body, btn = document.getElementById('more');
    function set(o) { b.classList.toggle('open', o); btn.setAttribute('aria-expanded', o ? 'true' : 'false'); }
    btn.addEventListener('click', function () { set(!b.classList.contains('open')); });
    document.getElementById('scrim').addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });

    var pw = document.getElementById('password'), tg = document.getElementById('togglePw');
    tg.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        tg.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    });
})();
</script>

</body>
</html>