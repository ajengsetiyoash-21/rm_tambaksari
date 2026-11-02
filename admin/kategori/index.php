<?php
session_start();
include "../../config.php";

// Cek login admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != "admin") {
    header("Location: ../../login.php");
    exit;
}

// Ambil data kategori
$kategori = [];
$ambildata = mysqli_query($db, "SELECT * FROM kategori ORDER BY id_kategori ASC");
while ($ambildata && $row = mysqli_fetch_assoc($ambildata)) {
    $kategori[] = $row;
}
$total_kategori = count($kategori);

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
<title>Kategori - RM. Tambaksari</title>
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

/* ========== PANEL ========== */
.panel { background: var(--card); border-radius: 20px; padding: 20px; border: 1px solid var(--line); }
.ph { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; }
.ph h2 { font-size: 18px; font-weight: 800; color: var(--ink); }
.ph .count { display: block; font-size: 12.5px; font-weight: 500; color: var(--muted); margin-top: 1px; }

.btn-add { display: inline-flex; align-items: center; gap: 8px; background: var(--maroon); color: #fff; padding: 11px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 700; white-space: nowrap; }
.btn-add:hover { background: var(--maroon-2); }

/* ========== TABEL (desktop) ========== */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; font-size: 12.5px; font-weight: 700; color: var(--muted); padding: 10px 12px; border-bottom: 1px solid var(--line); white-space: nowrap; }
td { padding: 14px 12px; border-bottom: 1px solid var(--line); font-size: 14px; vertical-align: middle; }
tr:last-child td { border-bottom: 0; }
tbody tr:hover td { background: #fcf8f8; }
th.c, td.c { text-align: center; }
.who { display: flex; align-items: center; gap: 12px; font-weight: 700; color: var(--ink); }

.ib { width: 40px; height: 40px; border-radius: 12px; flex: none; display: grid; place-items: center; background: var(--tint); color: var(--maroon); }
.idtag { display: inline-block; background: #f1eded; color: var(--muted); padding: 4px 10px; border-radius: 8px; font-size: 12.5px; font-weight: 700; }

.act { display: inline-flex; gap: 8px; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 14px; border-radius: 10px; font-size: 13px; font-weight: 700; }
.btn.edit  { background: #e3e9ff; color: #2f4fb3; }
.btn.edit:hover  { background: #d0daff; }
.btn.hapus { background: var(--red-bg); color: var(--red); }
.btn.hapus:hover { background: #f9cdd1; }

.empty { text-align: center; color: var(--muted); padding: 28px 10px; font-size: 14px; }

.cards { display: none; }
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

    .panel { padding: 16px 14px; border-radius: 18px; }
    .ph h2 { font-size: 16px; }
    .btn-add { padding: 10px 14px; }

    .table-wrap { display: none; }
    .cards { display: grid; gap: 12px; list-style: none; }
    .k { border: 1px solid var(--line); border-radius: 16px; padding: 14px; background: #fff; }
    .k .row { display: flex; align-items: center; gap: 12px; }
    .k .info { flex: 1; min-width: 0; }
    .k .info b { display: block; font-size: 15px; overflow-wrap: anywhere; }
    .k .act { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--line); }
    .k .btn { padding: 11px; }

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
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></symbol>
    <symbol id="i-edit" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></symbol>
    <symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9Z"/><circle cx="8" cy="8" r="1.4"/></symbol>
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
        <li><a href="../menu/index.php"><?= ico('menu'); ?> Menu</a></li>
        <li><a href="index.php" class="on"><?= ico('grid'); ?> Kategori</a></li>
        <li><a href="../meja/index.php"><?= ico('table'); ?> Meja</a></li>
        <li><a href="../laporan/index.php"><?= ico('chart'); ?> Laporan</a></li>
    </ul>
    <div class="gap"></div>
    <a href="../../logout.php" class="out"><?= ico('logout'); ?> Logout</a>
</aside>

<main class="main">

    <header class="top">
        <div>
            <h1>Kategori</h1>
            <p>Kelola kategori menu RM. Tambaksari</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial_adm; ?></div>
            <div class="n">
                <b><?= $nama_admin; ?></b>
                <small><?= ico('shield'); ?> Administrator</small>
            </div>
        </div>
    </header>

    <section class="panel">
        <div class="ph">
            <h2>Data Kategori<span class="count"><?= $total_kategori; ?> kategori terdaftar</span></h2>
            <a href="tambah.php" class="btn-add"><?= ico('plus', 'i sm'); ?> Tambah Kategori</a>
        </div>

        <?php if ($total_kategori > 0): ?>

        <!-- Tabel (desktop) -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:110px">ID Kategori</th>
                        <th>Nama Kategori</th>
                        <th class="c" style="width:200px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($kategori as $k):
                    $id = (int)$k['id_kategori'];
                ?>
                    <tr>
                        <td class="c"><span class="idtag"><?= $id; ?></span></td>
                        <td>
                            <div class="who">
                                <div class="ib"><?= ico('tag'); ?></div>
                                <?= htmlspecialchars($k['nama_kategori']); ?>
                            </div>
                        </td>
                        <td class="c">
                            <div class="act">
                                <a href="edit.php?id_kategori=<?= $id; ?>" class="btn edit"><?= ico('edit', 'i sm'); ?> Edit</a>
                                <a href="hapus.php?id_kategori=<?= $id; ?>" class="btn hapus" onclick="return confirm('Yakin ingin menghapus kategori ini?');"><?= ico('trash', 'i sm'); ?> Hapus</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Kartu (HP) -->
        <ul class="cards">
            <?php foreach ($kategori as $k):
                $id = (int)$k['id_kategori'];
            ?>
            <li class="k">
                <div class="row">
                    <div class="ib"><?= ico('tag'); ?></div>
                    <div class="info">
                        <b><?= htmlspecialchars($k['nama_kategori']); ?></b>
                    </div>
                    <span class="idtag">ID <?= $id; ?></span>
                </div>
                <div class="act">
                    <a href="edit.php?id_kategori=<?= $id; ?>" class="btn edit"><?= ico('edit', 'i sm'); ?> Edit</a>
                    <a href="hapus.php?id_kategori=<?= $id; ?>" class="btn hapus" onclick="return confirm('Yakin ingin menghapus kategori ini?');"><?= ico('trash', 'i sm'); ?> Hapus</a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php else: ?>
            <p class="empty">Belum ada data kategori. Tekan "Tambah Kategori" untuk menambahkan.</p>
        <?php endif; ?>
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
    <a href="../users/index.php" role="menuitem"><?= ico('user', 'i lg'); ?> User</a>
    <a href="index.php" class="on" role="menuitem"><?= ico('grid', 'i lg'); ?> Kategori</a>
    <a href="../../logout.php" role="menuitem"><?= ico('logout', 'i lg'); ?> Logout</a>
</div>

<script>
(function () {
    var b = document.body, btn = document.getElementById('more');
    function set(o) { b.classList.toggle('open', o); btn.setAttribute('aria-expanded', o ? 'true' : 'false'); }
    btn.addEventListener('click', function () { set(!b.classList.contains('open')); });
    document.getElementById('scrim').addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
})();
</script>

</body>
</html>