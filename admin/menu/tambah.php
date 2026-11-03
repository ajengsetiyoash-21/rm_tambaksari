<?php
session_start();
include "../../config.php";

// Cek login admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != "admin") {
    header("Location: ../../login.php");
    exit;
}

$nama_admin = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$inisial_adm = htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)));

function ico($id, $cls = 'i') { return '<svg class="' . $cls . '" aria-hidden="true"><use href="#i-' . $id . '"/></svg>'; }

// Proses Tambah Data
if (isset($_POST['simpan'])) {
    $nama_menu = mysqli_real_escape_string($db, $_POST['nama_menu']);
    $id_kategori = (int)$_POST['id_kategori'];
    $harga = (int)$_POST['harga'];
    $status_stok = mysqli_real_escape_string($db, $_POST['status_stok']);
    
    // Upload foto (opsional)
    $foto = "";
    if ($_FILES['foto']['name'] != '') {
        $ekstensi = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ekstensi, $allowed)) {
            $foto = time() . '_' . rand(100,999) . '.' . $ekstensi;
            $target = "../../assets/images/menu/" . $foto;
            // Buat folder jika belum ada
            if (!is_dir("../../assets/images/menu/")) {
                mkdir("../../assets/images/menu/", 0777, true);
            }
            move_uploaded_file($_FILES['foto']['tmp_name'], $target);
        }
    }

    $query = "INSERT INTO menu (nama_menu, id_kategori, harga, status_stok, foto) 
              VALUES ('$nama_menu', '$id_kategori', '$harga', '$status_stok', '$foto')";
    mysqli_query($db, $query);

    header("Location: index.php");
    exit;
}

// Ambil data kategori untuk dropdown
$kategori_q = mysqli_query($db, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
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
body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--bg); color: #1d1a1b; line-height: 1.45; }
a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid #e7a3aa; outline-offset: 2px; }

.i { width: 20px; height: 20px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.i.lg { width: 22px; height: 22px; }
.i.sm { width: 16px; height: 16px; }

/* SIDEBAR (Disamakan) */
.side { position: fixed; inset: 0 auto 0 0; width: var(--side); background: var(--maroon); color: #fff; padding: 24px 14px; display: flex; flex-direction: column; overflow-y: auto; z-index: 50; }
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

/* MAIN & HEADER */
.main { margin-left: var(--side); padding: 28px 32px 40px; max-width: 800px; }
.top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 24px; }
.top h1 { font-size: 26px; font-weight: 800; color: var(--ink); letter-spacing: -.3px; }
.top p { color: var(--muted); font-size: 13.5px; }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); padding: 6px 16px 6px 6px; border-radius: 999px; }
.avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--maroon); color: var(--gold); display: grid; place-items: center; font-weight: 800; font-size: 16px; }
.profile b { display: block; font-size: 14px; }
.profile small { display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: var(--maroon-2); font-weight: 700; }
.profile small .i { width: 12px; height: 12px; stroke-width: 2.2; }

/* FORM PANEL */
.panel { background: var(--card); border-radius: 20px; padding: 28px; border: 1px solid var(--line); }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 8px; }
.form-group label .req { color: var(--red); }
.form-control { width: 100%; padding: 13px 16px; border: 1.5px solid var(--line); border-radius: 12px; font-size: 14px; font-family: inherit; color: var(--ink); background: #fafafa; transition: all 0.2s; }
.form-control:focus { border-color: var(--maroon); background: #fff; outline: none; box-shadow: 0 0 0 4px var(--tint); }
select.form-control { cursor: pointer; }
input[type="file"].form-control { padding: 10px; background: #fff; }

.radio-group { display: flex; gap: 12px; }
.radio-group label { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: 1.5px solid var(--line); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; color: var(--muted); background: #fafafa; transition: all 0.2s; }
.radio-group label:hover { border-color: var(--maroon-2); }
.radio-group input[type="radio"] { accent-color: var(--maroon); width: 16px; height: 16px; }
.radio-group input[type="radio"]:checked + span { color: var(--maroon); }

.form-actions { display: flex; gap: 12px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-save { background: var(--maroon); color: #fff; flex: 1; }
.btn-save:hover { background: var(--maroon-2); }
.btn-cancel { background: #f1eded; color: var(--muted); text-decoration: none; }
.btn-cancel:hover { background: #e5e1e1; }

/* RESPONSIVE */
@media (max-width: 800px) {
    .side { display: none; }
    .main { margin-left: 0; padding: 20px 16px; }
    .top h1 { font-size: 20px; }
    .panel { padding: 20px 16px; border-radius: 16px; }
    .form-actions { flex-direction: column-reverse; }
    .btn { width: 100%; }
}
</style>
</head>
<body>

<!-- SPRITE IKON (Sama seperti index) -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6a2 2 0 0 0 2 2h3Zm0 0v7"/></symbol>
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="i-table" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="3.5" rx="1.2"/><path d="M5 8.5V19"/><path d="M19 8.5V19"/><path d="M9 8.5V14"/><path d="M15 8.5V14"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M8 16v-4"/><path d="M13 16V8"/><path d="M18 16v-7"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6Z"/><path d="m9 12 2 2 4-4"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></symbol>
    <symbol id="i-save" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></symbol>
    <symbol id="i-arrow-left" viewBox="0 0 24 24"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></symbol>
</svg>

<!-- SIDEBAR -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../../assets/images/logo.png" alt="Logo">
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
            <p>Lengkapi data untuk menambahkan menu baru</p>
        </div>
        <div class="profile">
            <div class="avatar"><?= $inisial_adm; ?></div>
            <div>
                <b><?= $nama_admin; ?></b>
                <small><?= ico('shield'); ?> Administrator</small>
            </div>
        </div>
    </header>

    <section class="panel">
        <form method="post" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Nama Menu <span class="req">*</span></label>
                <input type="text" name="nama_menu" class="form-control" placeholder="Contoh: Nasi Goreng Spesial" required>
            </div>

            <div class="form-group">
                <label>Kategori <span class="req">*</span></label>
                <select name="id_kategori" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($kat = mysqli_fetch_assoc($kategori_q)): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Harga (Rp) <span class="req">*</span></label>
                <input type="number" name="harga" class="form-control" placeholder="Contoh: 15000" required>
            </div>

            <div class="form-group">
                <label>Status Ketersediaan <span class="req">*</span></label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="status_stok" value="tersedia" checked>
                        <span>Tersedia</span>
                    </label>
                    <label>
                        <input type="radio" name="status_stok" value="habis">
                        <span>Habis</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Foto Menu (Opsional)</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
                <p style="font-size:12px; color:var(--muted); margin-top:6px;">Format: JPG, PNG, WEBP. Maksimal 2MB.</p>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel"><?= ico('arrow-left', 'i sm'); ?> Batal</a>
                <button type="submit" name="simpan" class="btn btn-save"><?= ico('save', 'i sm'); ?> Simpan Menu</button>
            </div>

        </form>
    </section>
</main>

</body>
</html>