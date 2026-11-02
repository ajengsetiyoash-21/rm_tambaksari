<?php
session_start();
include "../../config.php";

// Cek login admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != "admin") {
    header("Location: ../../login.php");
    exit;
}

/* =========================
   AMBIL DATA KATEGORI
========================= */
$daftar_kategori = [];
$q_kat = mysqli_query($db, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
while ($q_kat && $k = mysqli_fetch_assoc($q_kat)) {
    $daftar_kategori[] = $k;
}

$error = '';
$v = ['nama_menu' => '', 'id_kategori' => '', 'harga' => '', 'deskripsi' => '', 'status_stok' => 'tersedia'];

if (isset($_POST['simpan'])) {

    $v['nama_menu']   = trim($_POST['nama_menu'] ?? '');
    $v['id_kategori'] = (int)($_POST['id_kategori'] ?? 0);
    $v['harga']       = trim($_POST['harga'] ?? '');
    $v['deskripsi']   = trim($_POST['deskripsi'] ?? '');
    $v['status_stok'] = $_POST['status_stok'] ?? 'tersedia';

    $harga = (int)$v['harga'];

    if ($v['nama_menu'] === '') {
        $error = 'Nama menu wajib diisi.';
    } elseif ($v['id_kategori'] < 1) {
        $error = 'Silakan pilih kategori.';
    } elseif ($v['harga'] === '' || $harga < 0) {
        $error = 'Harga harus berupa angka 0 atau lebih.';
    } elseif (!in_array($v['status_stok'], ['tersedia', 'habis'], true)) {
        $error = 'Status stok tidak valid.';
    }

    /* =========================
       UPLOAD FOTO
    ========================= */
    $nama_foto = "";

    if ($error === '' && isset($_FILES['foto']) && $_FILES['foto']['error'] != UPLOAD_ERR_NO_FILE) {

        if ($_FILES['foto']['error'] != UPLOAD_ERR_OK) {
            $error = 'Foto gagal diupload. Coba pilih foto lain (maksimal 2 MB).';
        } else {
            $nama_asli = $_FILES['foto']['name'];
            $tmp_foto  = $_FILES['foto']['tmp_name'];
            $ekstensi  = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

            if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $error = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 2 MB.';
            } elseif (@getimagesize($tmp_foto) === false) {
                $error = 'File yang dipilih bukan gambar yang valid.';
            } else {
                $nama_foto   = time() . "_" . uniqid() . "." . $ekstensi;
                $folder_foto = __DIR__ . "/../../assets/images/menu/";

                if (!is_dir($folder_foto)) {
                    mkdir($folder_foto, 0755, true);
                }

                if (!move_uploaded_file($tmp_foto, $folder_foto . $nama_foto)) {
                    $nama_foto = "";
                    $error = 'Foto gagal disimpan di server.';
                }
            }
        }
    }

    /* =========================
       SIMPAN DATA MENU
    ========================= */
    if ($error === '') {

        $stmt = mysqli_prepare($db, "
            INSERT INTO menu (id_kategori, nama_menu, harga, foto, deskripsi, status_stok)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt, "isisss",
            $v['id_kategori'], $v['nama_menu'], $harga, $nama_foto, $v['deskripsi'], $v['status_stok']
        );
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($query) {
            echo "<script>
                    alert('Menu berhasil ditambahkan!');
                    window.location='index.php';
                  </script>";
            exit;
        }

        // Jika database gagal, hapus foto yang sudah terupload
        if ($nama_foto != "") {
            $file_hapus = __DIR__ . "/../../assets/images/menu/" . $nama_foto;
            if (file_exists($file_hapus)) {
                unlink($file_hapus);
            }
        }
        $error = 'Menu gagal ditambahkan. Silakan coba lagi.';

    } elseif ($nama_foto != "") {
        // Ada error lain setelah foto terupload: bersihkan filenya
        $file_hapus = __DIR__ . "/../../assets/images/menu/" . $nama_foto;
        if (file_exists($file_hapus)) {
            unlink($file_hapus);
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
<title>Tambah Menu - RM. Tambaksari</title>
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
    --green: #0f7a4f;  --green-bg: #dcf5e8;
    --red: #b3262f;    --red-bg: #fde3e5;
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

.panel { background: var(--card); border-radius: 20px; padding: 24px; border: 1px solid var(--line); max-width: 640px; }
.ph { margin-bottom: 20px; }
.ph h2 { font-size: 18px; font-weight: 800; color: var(--ink); }
.ph p { font-size: 13px; color: var(--muted); margin-top: 2px; }

.alert { display: flex; gap: 10px; align-items: flex-start; background: var(--red-bg); color: var(--red); border-radius: 12px; padding: 12px 14px; font-size: 13.5px; font-weight: 600; margin-bottom: 18px; }
.alert .i { margin-top: 1px; }

.field { margin-bottom: 18px; }
.field label, .field .lbl { display: block; font-size: 13.5px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
.field input[type=text], .field input[type=number], .field select, .field textarea {
    width: 100%; min-height: 50px; padding: 12px 14px;
    border: 1.5px solid var(--line); border-radius: 14px;
    font-family: inherit; font-size: 16px; color: var(--ink); background: #fff;
}
.field textarea { min-height: 110px; resize: vertical; line-height: 1.5; }
.field ::placeholder { color: #a79ea0; }
.field input:focus, .field select:focus, .field textarea:focus { outline: 0; border-color: var(--maroon-2); box-shadow: 0 0 0 4px #f3d6d9; }
.field select {
    appearance: none; -webkit-appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2370686a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 44px;
}
.field .hint { font-size: 12.5px; color: var(--muted); margin-top: 6px; }

/* Harga dengan awalan Rp */
.rp { position: relative; }
.rp span { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--muted); font-size: 15px; pointer-events: none; }
.rp input { padding-left: 46px !important; }

/* Upload foto */
.upload { display: flex; align-items: center; gap: 14px; }
.preview {
    width: 88px; height: 88px; border-radius: 16px; flex: none; overflow: hidden;
    background: #f1eded; color: #a79ea0; display: grid; place-items: center; border: 1.5px dashed #d9d0d1;
}
.preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
.preview.has { border-style: solid; border-color: var(--line); }
.preview.has img { display: block; }
.preview.has .i { display: none; }
.upload .pick { min-width: 0; }
.upload input[type=file] { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
.btn-pick { display: inline-flex; align-items: center; gap: 8px; background: var(--tint); color: var(--maroon); padding: 11px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 700; cursor: pointer; }
.btn-pick:hover { background: #f1d7da; }
.upload input[type=file]:focus-visible + .btn-pick { outline: 3px solid #e7a3aa; outline-offset: 2px; }
.fname { display: block; margin-top: 6px; font-size: 12.5px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; }

/* Pilihan status stok */
.seg { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.seg input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.seg label {
    display: flex; align-items: center; justify-content: center; gap: 8px; margin: 0;
    min-height: 50px; border: 1.5px solid var(--line); border-radius: 14px;
    font-size: 14.5px; font-weight: 700; color: var(--muted); cursor: pointer; background: #fff;
}
.seg input:checked + label.ok  { background: var(--green-bg); border-color: var(--green); color: var(--green); }
.seg input:checked + label.no  { background: var(--red-bg); border-color: var(--red); color: var(--red); }
.seg input:focus-visible + label { outline: 3px solid #e7a3aa; outline-offset: 2px; }

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
    .fname { max-width: 180px; }

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
    <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></symbol>
    <symbol id="i-back" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="1.8"/><path d="m21 15-4.5-4.5L6 21"/></symbol>
    <symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></symbol>
</svg>

<!-- SIDEBAR DESKTOP -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../../assets/images/logo.png" alt="Logo RM Tambaksari">
        <div><b>RM. TAMBAKSARI</b><span>Onggorawe Demak</span></div>
    </div>
    <ul>
        <li><a href="../dashboard.php"><?= ico('home'); ?> Dashboard</a></li>
        <li><a href="../users/index.php"><?= ico('user'); ?> User</a></li>
        <li><a href="index.php" class="on"><?= ico('menu'); ?> Menu</a></li>
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
            <h1>Tambah Menu</h1>
            <p>Tambahkan data makanan atau minuman baru</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial_adm; ?></div>
            <div class="n">
                <b><?= $nama_admin; ?></b>
                <small><?= ico('shield'); ?> Administrator</small>
            </div>
        </div>
    </header>

    <a href="index.php" class="back"><?= ico('back', 'i sm'); ?> Kembali ke data menu</a>

    <section class="panel">
        <div class="ph">
            <h2>Data menu baru</h2>
            <p>Lengkapi informasi menu, foto bersifat opsional.</p>
        </div>

        <?php if ($error != ''): ?>
        <div class="alert" role="alert"><?= ico('alert'); ?> <span><?= htmlspecialchars($error); ?></span></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="field">
                <label for="nama_menu">Nama menu</label>
                <input type="text" id="nama_menu" name="nama_menu" placeholder="Masukkan nama menu"
                       value="<?= htmlspecialchars($v['nama_menu']); ?>" required autofocus>
            </div>

            <div class="field">
                <label for="id_kategori">Kategori</label>
                <select id="id_kategori" name="id_kategori" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($daftar_kategori as $k): ?>
                        <option value="<?= (int)$k['id_kategori']; ?>" <?= (int)$v['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($k['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($daftar_kategori) === 0): ?>
                    <div class="hint">Belum ada kategori. Tambahkan dulu di halaman Kategori.</div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="harga">Harga</label>
                <div class="rp">
                    <span>Rp</span>
                    <input type="number" id="harga" name="harga" inputmode="numeric" placeholder="0" min="0"
                           value="<?= htmlspecialchars((string)$v['harga']); ?>" required>
                </div>
            </div>

            <div class="field">
                <span class="lbl">Foto menu</span>
                <div class="upload">
                    <div class="preview" id="preview"><?= ico('image', 'i lg'); ?><img id="previewImg" alt="Pratinjau foto"></div>
                    <div class="pick">
                        <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <label for="foto" class="btn-pick"><?= ico('upload', 'i sm'); ?> Pilih foto</label>
                        <span class="fname" id="fname">Belum ada foto dipilih</span>
                    </div>
                </div>
                <div class="hint">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
            </div>

            <div class="field">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" placeholder="Masukkan deskripsi menu"><?= htmlspecialchars($v['deskripsi']); ?></textarea>
            </div>

            <div class="field">
                <span class="lbl">Status stok</span>
                <div class="seg">
                    <input type="radio" id="st_ada" name="status_stok" value="tersedia" <?= $v['status_stok'] === 'tersedia' ? 'checked' : ''; ?>>
                    <label for="st_ada" class="ok"><?= ico('check', 'i sm'); ?> Tersedia</label>
                    <input type="radio" id="st_habis" name="status_stok" value="habis" <?= $v['status_stok'] === 'habis' ? 'checked' : ''; ?>>
                    <label for="st_habis" class="no"><?= ico('x', 'i sm'); ?> Habis</label>
                </div>
            </div>

            <div class="acts">
                <a href="index.php" class="btn g">Kembali</a>
                <button type="submit" name="simpan" class="btn p"><?= ico('check', 'i sm'); ?> Simpan</button>
            </div>

        </form>
    </section>

</main>

<!-- NAVIGASI BAWAH (HP) -->
<nav class="tabbar" aria-label="Navigasi">
    <a href="../dashboard.php"><span class="pill"><?= ico('home', 'i lg'); ?></span>Beranda</a>
    <a href="index.php" class="on"><span class="pill"><?= ico('menu', 'i lg'); ?></span>Menu</a>
    <a href="../meja/index.php"><span class="pill"><?= ico('table', 'i lg'); ?></span>Meja</a>
    <a href="../laporan/index.php"><span class="pill"><?= ico('chart', 'i lg'); ?></span>Laporan</a>
    <button type="button" id="more" aria-haspopup="true" aria-expanded="false"><span class="pill"><?= ico('more', 'i lg'); ?></span>Lainnya</button>
</nav>

<div class="scrim" id="scrim"></div>
<div class="sheet" id="sheet" role="menu">
    <div class="grab"></div>
    <a href="../users/index.php" role="menuitem"><?= ico('user', 'i lg'); ?> User</a>
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

    // Pratinjau foto
    var input = document.getElementById('foto'),
        box = document.getElementById('preview'),
        img = document.getElementById('previewImg'),
        name = document.getElementById('fname');
    input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f) { box.classList.remove('has'); img.removeAttribute('src'); name.textContent = 'Belum ada foto dipilih'; return; }
        name.textContent = f.name;
        if (f.size > 2 * 1024 * 1024) { alert('Ukuran foto maksimal 2 MB.'); input.value = ''; box.classList.remove('has'); name.textContent = 'Belum ada foto dipilih'; return; }
        var r = new FileReader();
        r.onload = function (e) { img.src = e.target.result; box.classList.add('has'); };
        r.readAsDataURL(f);
    });
})();
</script>

</body>
</html>