<?php
session_start();
include "../config.php";

// Cek login koki
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Ambil data menu dan kategori
$query = mysqli_query($db, "
    SELECT menu.*, kategori.nama_kategori
    FROM menu
    LEFT JOIN kategori ON menu.id_kategori = kategori.id_kategori
    ORDER BY menu.nama_menu ASC
");

if (!$query) {
    die("Gagal mengambil data menu.");
}

$menus = [];
$hitung = ['semua' => 0, 'tersedia' => 0, 'habis' => 0];
while ($row = mysqli_fetch_assoc($query)) {
    $row['stok'] = ($row['status_stok'] == 'tersedia') ? 'tersedia' : 'habis';
    $menus[] = $row;
    $hitung['semua']++;
    $hitung[$row['stok']]++;
}

// Helper
function ico($id, $cls = 'i') { return '<svg class="' . $cls . '" aria-hidden="true"><use href="#i-' . $id . '"/></svg>'; }

$nama_user = htmlspecialchars($_SESSION['username'] ?? 'Koki');
$inisial   = htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'K', 0, 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#5b1018">
<title>Menu Dapur - RM. Tambaksari</title>
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

/* ========== CARI & FILTER ========== */
.search { position: relative; margin-bottom: 12px; max-width: 520px; }
.search .i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
.search input {
    width: 100%; min-height: 50px; padding: 12px 14px 12px 44px;
    border: 1.5px solid var(--line); border-radius: 14px;
    font-family: inherit; font-size: 16px; color: var(--ink); background: #fff;
}
.search input::placeholder { color: #a79ea0; }
.search input:focus { outline: 0; border-color: var(--maroon-2); box-shadow: 0 0 0 4px #f3d6d9; }

.chips { display: flex; gap: 8px; overflow-x: auto; padding: 2px 2px 10px; margin-bottom: 8px; scrollbar-width: none; }
.chips::-webkit-scrollbar { display: none; }
.chip {
    flex: none; display: inline-flex; align-items: center; gap: 8px;
    background: var(--card); border: 1.5px solid var(--line); color: var(--muted);
    padding: 9px 14px; border-radius: 999px; font: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer;
}
.chip span { background: #f1eded; color: var(--muted); border-radius: 999px; min-width: 24px; padding: 1px 8px; font-size: 12px; text-align: center; }
.chip.on { background: var(--maroon); border-color: var(--maroon); color: #fff; }
.chip.on span { background: rgba(255,255,255,.2); color: #fff; }

/* ========== DAFTAR MENU ========== */
.ph { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 14px; }
.ph h2 { font-size: 18px; font-weight: 800; color: var(--ink); }
.ph .count { font-size: 13px; color: var(--muted); font-weight: 600; }

.list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; list-style: none; }
.m { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 12px; display: flex; gap: 14px; align-items: center; }
.m.hide { display: none; }
.m.habis { background: #fbfbfb; }
.m.habis .foto, .m.habis .no-foto { opacity: .55; }

.foto, .no-foto { width: 84px; height: 84px; border-radius: 14px; flex: none; }
.foto { object-fit: cover; border: 1px solid var(--line); display: block; }
.no-foto { background: #f1eded; color: #a79ea0; display: grid; place-items: center; }

.m .info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.m .info b { font-size: 16px; font-weight: 800; color: var(--ink); line-height: 1.3; overflow-wrap: anywhere; }
.kat { align-self: flex-start; background: var(--tint); color: var(--maroon); padding: 3px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.bot { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.harga { font-size: 14.5px; font-weight: 800; color: var(--maroon); white-space: nowrap; }

.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.badge.tersedia { background: var(--green-bg); color: var(--green); }
.badge.habis    { background: var(--red-bg); color: var(--red); }

.empty { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 36px 16px; text-align: center; color: var(--muted); font-size: 14.5px; }
.empty b { display: block; color: var(--ink); font-size: 16px; margin-bottom: 2px; }
#kosongFilter { display: none; }

.tabbar { display: none; }

/* ========== HP ========== */
@media (max-width: 800px) {
    .side { display: none; }
    .main { margin-left: 0; padding: calc(16px + env(safe-area-inset-top, 0px)) 14px calc(var(--tab-h) + 28px + env(safe-area-inset-bottom, 0px)); }
    .top { align-items: flex-start; }
    .top > div:first-child { min-width: 0; }
    .top h1 { font-size: 20px; }
    .top p { font-size: 12.5px; line-height: 1.35; margin-top: 2px; }
    .profile b { max-width: 90px; }

    .search { max-width: none; }
    .chips { margin-left: -14px; margin-right: -14px; padding-left: 14px; padding-right: 14px; }
    .list { grid-template-columns: 1fr; gap: 10px; }

    .tabbar {
        display: grid; grid-template-columns: repeat(4, 1fr);
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
        height: calc(var(--tab-h) + env(safe-area-inset-bottom, 0px));
        padding-bottom: env(safe-area-inset-bottom, 0px);
        background: #fff; border-top: 1px solid var(--line);
        box-shadow: 0 -6px 20px rgba(60,10,16,.08);
    }
    .tabbar a {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
        font-size: 11px; font-weight: 600; color: var(--muted);
    }
    .tabbar .pill { display: grid; place-items: center; padding: 4px 16px; border-radius: 12px; }
    .tabbar .on { color: var(--maroon); }
    .tabbar .on .pill { background: var(--tint); }
    .tabbar .out { color: var(--red); }
}

@media (max-width: 360px) { .profile b { max-width: 60px; } .foto, .no-foto { width: 72px; height: 72px; } }
@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }

/* ========== CETAK ========== */
@media print {
    .side, .tabbar, .profile, .search, .chips { display: none !important; }
    body { background: #fff; }
    .main { margin: 0; padding: 8px; max-width: none; }
    .m { break-inside: avoid; }
    .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>

<body>

<!-- SPRITE IKON -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6a2 2 0 0 0 2 2h3Zm0 0v7"/></symbol>
    <symbol id="i-list" viewBox="0 0 24 24"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="M9 12h6"/><path d="M9 16h4"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></symbol>
    <symbol id="i-flame" viewBox="0 0 24 24"><path d="M12 3c1 3 5 5 5 10a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-6 1-9Z"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="1.8"/><path d="m21 15-4.5-4.5L6 21"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
</svg>

<!-- SIDEBAR DESKTOP -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../assets/images/logo.png" alt="Logo RM Tambaksari">
        <div><b>RM. TAMBAKSARI</b><span>Onggorawe Demak</span></div>
    </div>
    <ul>
        <li><a href="dashboard.php"><?= ico('home'); ?> Dashboard Koki</a></li>
        <li><a href="pesanan.php"><?= ico('list'); ?> Daftar Pesanan</a></li>
        <li><a href="menu.php" class="on"><?= ico('menu'); ?> Menu</a></li>
    </ul>
    <div class="gap"></div>
    <a href="../logout.php" class="out"><?= ico('logout'); ?> Logout</a>
</aside>

<main class="main">

    <header class="top">
        <div>
            <h1>Menu Dapur</h1>
            <p>Daftar makanan dan minuman RM. Tambaksari.</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial; ?></div>
            <div class="n">
                <b><?= $nama_user; ?></b>
                <small><?= ico('flame'); ?> Koki</small>
            </div>
        </div>
    </header>

    <?php if ($hitung['semua'] > 0): ?>

    <!-- CARI & FILTER STOK -->
    <div class="search">
        <?= ico('search'); ?>
        <input type="search" id="cari" placeholder="Cari nama menu atau kategori" autocomplete="off" aria-label="Cari menu">
    </div>

    <div class="chips" aria-label="Filter status stok">
        <button type="button" class="chip on" data-filter="semua">Semua <span><?= $hitung['semua']; ?></span></button>
        <button type="button" class="chip" data-filter="tersedia">Tersedia <span><?= $hitung['tersedia']; ?></span></button>
        <button type="button" class="chip" data-filter="habis">Habis <span><?= $hitung['habis']; ?></span></button>
    </div>

    <div class="ph">
        <h2>Daftar makanan dan minuman</h2>
        <span class="count" id="jumlahTampil"><?= $hitung['semua']; ?> menu</span>
    </div>

    <ul class="list" id="daftar">
        <?php foreach ($menus as $d):
            $kategori = $d['nama_kategori'] ?? '-';
        ?>
        <li class="m <?= $d['stok'] == 'habis' ? 'habis' : ''; ?>"
            data-stok="<?= $d['stok']; ?>"
            data-cari="<?= htmlspecialchars(mb_strtolower($d['nama_menu'] . ' ' . $kategori)); ?>">

            <?php if (!empty($d['foto'])): ?>
                <img src="../assets/images/menu/<?= htmlspecialchars($d['foto']); ?>" alt="<?= htmlspecialchars($d['nama_menu']); ?>" class="foto" loading="lazy"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <div class="no-foto" style="display:none;"><?= ico('image', 'i lg'); ?></div>
            <?php else: ?>
                <div class="no-foto"><?= ico('image', 'i lg'); ?></div>
            <?php endif; ?>

            <div class="info">
                <b><?= htmlspecialchars($d['nama_menu']); ?></b>
                <span class="kat"><?= htmlspecialchars($kategori); ?></span>
                <div class="bot">
                    <span class="harga">Rp <?= number_format((float)$d['harga'], 0, ',', '.'); ?></span>
                    <?php if ($d['stok'] == 'tersedia'): ?>
                        <span class="badge tersedia"><?= ico('check', 'i sm'); ?> Tersedia</span>
                    <?php else: ?>
                        <span class="badge habis"><?= ico('x', 'i sm'); ?> Habis</span>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="empty" id="kosongFilter">
        <b>Menu tidak ditemukan</b>
        Coba kata kunci lain atau ganti filter stok.
    </div>

    <?php else: ?>
        <div class="empty">
            <b>Belum ada data menu</b>
            Menu akan muncul di sini setelah ditambahkan oleh admin.
        </div>
    <?php endif; ?>

</main>

<!-- NAVIGASI BAWAH (HP) -->
<nav class="tabbar" aria-label="Navigasi">
    <a href="dashboard.php"><span class="pill"><?= ico('home', 'i lg'); ?></span>Dashboard</a>
    <a href="pesanan.php"><span class="pill"><?= ico('list', 'i lg'); ?></span>Pesanan</a>
    <a href="menu.php" class="on"><span class="pill"><?= ico('menu', 'i lg'); ?></span>Menu</a>
    <a href="../logout.php" class="out"><span class="pill"><?= ico('logout', 'i lg'); ?></span>Keluar</a>
</nav>

<script>
(function () {
    var cari = document.getElementById('cari');
    if (!cari) return;

    var chips = document.querySelectorAll('.chip'),
        items = document.querySelectorAll('#daftar .m'),
        kosong = document.getElementById('kosongFilter'),
        jumlah = document.getElementById('jumlahTampil'),
        filter = 'semua';

    function terapkan() {
        var kata = cari.value.trim().toLowerCase(), tampil = 0;
        items.forEach(function (li) {
            var cocokStok = (filter === 'semua' || li.getAttribute('data-stok') === filter);
            var cocokKata = (kata === '' || li.getAttribute('data-cari').indexOf(kata) !== -1);
            var ok = cocokStok && cocokKata;
            li.classList.toggle('hide', !ok);
            if (ok) tampil++;
        });
        chips.forEach(function (c) { c.classList.toggle('on', c.getAttribute('data-filter') === filter); });
        jumlah.textContent = tampil + ' menu';
        kosong.style.display = (tampil === 0) ? 'block' : 'none';
    }

    chips.forEach(function (c) {
        c.addEventListener('click', function () { filter = c.getAttribute('data-filter'); terapkan(); });
    });
    cari.addEventListener('input', terapkan);
})();
</script>

</body>
</html>