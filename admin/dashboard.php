<?php
session_start();
include "../config.php";

// Cek login admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != "admin") {
    header("Location: ../login.php");
    exit;
}

// =========================
// DATA DASHBOARD
// =========================
function hitung($db, $sql) {
    $q = mysqli_query($db, $sql);
    $r = $q ? mysqli_fetch_assoc($q) : null;
    return $r ? (float)$r['total'] : 0;
}

$total_user       = hitung($db, "SELECT COUNT(*) AS total FROM users");
$total_menu       = hitung($db, "SELECT COUNT(*) AS total FROM menu");
$total_meja       = hitung($db, "SELECT COUNT(*) AS total FROM meja");
$total_transaksi  = hitung($db, "SELECT COUNT(*) AS total FROM pesanan");
$total_pendapatan = hitung($db, "SELECT COALESCE(SUM(total_harga),0) AS total FROM pesanan WHERE status_bayar='lunas'");
$total_belum      = hitung($db, "SELECT COALESCE(SUM(total_harga),0) AS total FROM pesanan WHERE status_bayar='belum_bayar'");
$meja_terisi      = hitung($db, "SELECT COUNT(*) AS total FROM meja WHERE status_meja='terisi'");

$q_pesanan = mysqli_query($db, "
    SELECT p.id_pesanan, m.nomor_meja, p.total_harga, p.status_bayar, p.created_at,
           GROUP_CONCAT(DISTINCT dp.status_memasak) AS status_memasak
    FROM pesanan p
    LEFT JOIN meja m ON p.id_meja = m.id_meja
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    GROUP BY p.id_pesanan, m.nomor_meja, p.total_harga, p.status_bayar, p.created_at
    ORDER BY p.created_at DESC
    LIMIT 5
");

$q_status_meja = mysqli_query($db, "SELECT nomor_meja, status_meja FROM meja ORDER BY nomor_meja ASC LIMIT 8");

// Pendapatan 7 hari terakhir (1 query)
$hari = [];
for ($i = 6; $i >= 0; $i--) $hari[date('Y-m-d', strtotime("-$i days"))] = 0;
$q_grafik = mysqli_query($db, "
    SELECT DATE(created_at) AS tgl, COALESCE(SUM(total_harga),0) AS total
    FROM pesanan
    WHERE status_bayar='lunas' AND DATE(created_at) >= '" . array_key_first($hari) . "'
    GROUP BY DATE(created_at)
");
while ($q_grafik && $r = mysqli_fetch_assoc($q_grafik)) {
    if (isset($hari[$r['tgl']])) $hari[$r['tgl']] = (float)$r['total'];
}
$data_grafik = [];
$nama_hari = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
foreach ($hari as $tgl => $total) {
    $data_grafik[] = ['hari' => $nama_hari[(int)date('w', strtotime($tgl))], 'tgl' => date('d/m', strtotime($tgl)), 'total' => $total];
}
$total_7hari = array_sum(array_column($data_grafik, 'total'));
$maks = max(1, max(array_column($data_grafik, 'total')));

function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function ribu($n) {
    if ($n >= 1000000) return round($n / 1000000, 1) . 'jt';
    if ($n >= 1000) return round($n / 1000) . 'rb';
    return (string)(int)$n;
}
// Ikon SVG (sprite di bawah)
function ico($id, $cls = 'i') { return '<svg class="' . $cls . '" aria-hidden="true"><use href="#i-' . $id . '"/></svg>'; }

$nama_status = ['antre'=>'Antre','dimasak'=>'Dimasak','selesai'=>'Selesai','diantar'=>'Diantar','lunas'=>'Lunas'];
$nama_admin  = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$inisial     = htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#5b1018">
<title>Dashboard Admin - RM. Tambaksari</title>
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
    --amber: #96590a;  --amber-bg: #fdeccb;
    --red: #b3262f;    --red-bg: #fde3e5;
    --blue: #2f4fb3;   --blue-bg: #e3e9ff;
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

/* Ikon */
.i { width: 20px; height: 20px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.i.lg { width: 22px; height: 22px; }

/* ========== SIDEBAR (desktop) ========== */
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

/* ========== HEADER + PROFIL ========== */
.main { margin-left: var(--side); padding: 28px 32px 40px; max-width: 1240px; }

.top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
.top h1 { font-size: 26px; font-weight: 800; color: var(--ink); letter-spacing: -.3px; }
.top p { color: var(--muted); font-size: 13.5px; }

.profile {
    display: flex; align-items: center; gap: 10px;
    background: var(--card); border: 1px solid var(--line);
    padding: 6px 16px 6px 6px; border-radius: 999px; min-width: 0;
}
.avatar {
    width: 40px; height: 40px; border-radius: 50%; flex: none;
    background: var(--maroon); color: var(--gold);
    display: grid; place-items: center; font-weight: 800; font-size: 16px;
}
.profile .n { min-width: 0; }
.profile b { display: block; font-size: 14px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
.profile small { display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: var(--maroon-2); font-weight: 700; }
.profile small .i { width: 12px; height: 12px; stroke-width: 2.2; }

/* ========== HERO ========== */
.hero {
    position: relative; overflow: hidden;
    background: linear-gradient(140deg, var(--maroon) 0%, var(--maroon-2) 100%);
    color: #fff; border-radius: 24px; padding: 22px; margin-bottom: 18px;
}
.hero::after { content: ""; position: absolute; right: -50px; top: -50px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.07); }
.hero .lbl { display: flex; align-items: center; gap: 8px; color: #f1d4d7; font-size: 13px; position: relative; z-index: 1; }
.hero .big { font-size: 34px; font-weight: 800; letter-spacing: -.6px; margin: 4px 0 16px; position: relative; z-index: 1; }
.hero .two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; position: relative; z-index: 1; }
.hero .p { background: rgba(255,255,255,.14); border-radius: 14px; padding: 12px 14px; }
.hero .p small { display: flex; align-items: center; gap: 6px; color: #f1d4d7; font-size: 12.5px; }
.hero .p small .i { width: 14px; height: 14px; }
.hero .p b { display: block; font-size: 15.5px; margin-top: 2px; }
.hero .p.warn b { color: var(--gold); }

/* ========== STAT ========== */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
.stat { background: var(--card); border-radius: 18px; padding: 14px; display: flex; align-items: center; gap: 12px; border: 1px solid var(--line); }
.stat .ib { width: 42px; height: 42px; flex: none; border-radius: 13px; display: grid; place-items: center; background: var(--tint); color: var(--maroon); }
.stat small { display: block; color: var(--muted); font-size: 12.5px; }
.stat b { font-size: 20px; font-weight: 800; color: var(--ink); }

/* ========== PANEL ========== */
.grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; margin-bottom: 16px; }
.panel { background: var(--card); border-radius: 20px; padding: 18px; border: 1px solid var(--line); min-width: 0; }
.ph { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 16px; }
.ph h2 { font-size: 16px; font-weight: 700; color: var(--ink); }
.ph a { font-size: 13px; font-weight: 700; color: var(--maroon-2); padding: 6px 0; }
.ph strong { font-size: 14px; color: var(--maroon-2); }

/* Grafik */
.chart { display: flex; align-items: flex-end; gap: 8px; height: 200px; }
.col { flex: 1; min-width: 0; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 5px; }
.col .v { font-size: 10.5px; color: var(--muted); font-weight: 600; min-height: 14px; white-space: nowrap; }
.col .bar { width: 100%; max-width: 42px; min-height: 6px; border-radius: 9px 9px 4px 4px; background: #ecd0d3; }
.col.hi .bar { background: linear-gradient(180deg, var(--maroon-2), var(--maroon)); }
.col .d { font-size: 12px; font-weight: 700; color: var(--ink); }
.col .t { font-size: 10.5px; color: var(--muted); margin-top: -4px; }

/* Pesanan */
.orders { list-style: none; }
.orders li { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
.orders li:first-child { padding-top: 0; }
.orders li:last-child { border-bottom: 0; padding-bottom: 0; }
.no { width: 44px; height: 44px; flex: none; border-radius: 13px; background: var(--tint); color: var(--maroon); display: grid; place-items: center; font-weight: 800; }
.orders .info { flex: 1; min-width: 0; }
.orders .info b { display: block; font-size: 14.5px; }
.orders .info small { color: var(--muted); font-size: 12.5px; }
.badge { padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.badge.antre   { background: var(--amber-bg); color: var(--amber); }
.badge.dimasak { background: #ffe1cc; color: #a84d00; }
.badge.selesai { background: var(--green-bg); color: var(--green); }
.badge.diantar { background: var(--blue-bg); color: var(--blue); }
.badge.lunas   { background: #c9f0dc; color: #075c3b; }

/* Meja */
.tables { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.tb { border-radius: 14px; padding: 14px 6px; text-align: center; font-weight: 800; font-size: 15px; }
.tb small { display: block; font-weight: 600; font-size: 11.5px; margin-top: 2px; }
.tb.kosong { background: var(--green-bg); color: var(--green); }
.tb.terisi { background: var(--red-bg); color: var(--red); }
.empty { color: var(--muted); font-size: 14px; }

/* ========== TAB BAR (hp) ========== */
.tabbar, .sheet, .scrim { display: none; }

@media (max-width: 1000px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
    .grid { grid-template-columns: 1fr; }
}

@media (max-width: 800px) {
    .side { display: none; }
    .main { margin-left: 0; padding: calc(16px + env(safe-area-inset-top, 0px)) 14px calc(var(--tab-h) + 28px + env(safe-area-inset-bottom, 0px)); }
    .top { align-items: flex-start; }
    .top > div:first-child { min-width: 0; }
    .top h1 { font-size: 20px; }
    .top p { font-size: 12.5px; line-height: 1.35; margin-top: 2px; }
    .hero { border-radius: 22px; padding: 18px; }
    .hero .big { font-size: 28px; }
    .stats { gap: 10px; }
    .stat { padding: 12px; gap: 10px; }
    .stat .ib { width: 38px; height: 38px; }
    .stat b { font-size: 18px; }
    .panel { padding: 16px 14px; border-radius: 18px; }
    .chart { height: 180px; gap: 5px; }
    .col .v { font-size: 9.5px; }

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

@media (max-width: 360px) {
    .stat { flex-direction: column; align-items: flex-start; }
    .hero .big { font-size: 25px; }
    .profile b { max-width: 90px; }
}
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
    <symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2Z"/><path d="M9 8h6"/><path d="M9 12h6"/></symbol>
    <symbol id="i-wallet" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h15V9H5a2 2 0 0 1-2-2Z"/><circle cx="16.5" cy="14.5" r="1"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-trend" viewBox="0 0 24 24"><path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/></symbol>
    <symbol id="i-more" viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6Z"/><path d="m9 12 2 2 4-4"/></symbol>
</svg>

<!-- SIDEBAR DESKTOP -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../assets/images/logo.png" alt="Logo RM Tambaksari">
        <div><b>RM. TAMBAKSARI</b><span>Onggorawe Demak</span></div>
    </div>
    <ul>
        <li><a href="dashboard.php" class="on"><?= ico('home'); ?> Dashboard</a></li>
        <li><a href="users/index.php"><?= ico('user'); ?> User</a></li>
        <li><a href="menu/index.php"><?= ico('menu'); ?> Menu</a></li>
        <li><a href="kategori/index.php"><?= ico('grid'); ?> Kategori</a></li>
        <li><a href="meja/index.php"><?= ico('table'); ?> Meja</a></li>
        <li><a href="laporan/index.php"><?= ico('chart'); ?> Laporan</a></li>
    </ul>
    <div class="gap"></div>
    <a href="../logout.php" class="out"><?= ico('logout'); ?> Logout</a>
</aside>

<main class="main">

    <header class="top">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Selamat datang di sistem RM. Tambaksari</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial; ?></div>
            <div class="n">
                <b><?= $nama_admin; ?></b>
                <small><?= ico('shield'); ?> Administrator</small>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="lbl"><?= ico('wallet'); ?> Pendapatan lunas</div>
        <div class="big"><?= rp($total_pendapatan); ?></div>
        <div class="two">
            <div class="p"><small><?= ico('trend'); ?> 7 hari terakhir</small><b><?= rp($total_7hari); ?></b></div>
            <div class="p warn"><small><?= ico('clock'); ?> Belum dibayar</small><b><?= rp($total_belum); ?></b></div>
        </div>
    </section>

    <!-- STATISTIK -->
    <section class="stats">
        <div class="stat"><div class="ib"><?= ico('receipt', 'i lg'); ?></div><div><small>Transaksi</small><b><?= number_format($total_transaksi, 0, ',', '.'); ?></b></div></div>
        <div class="stat"><div class="ib"><?= ico('table', 'i lg'); ?></div><div><small>Meja terisi</small><b><?= (int)$meja_terisi; ?>/<?= (int)$total_meja; ?></b></div></div>
        <div class="stat"><div class="ib"><?= ico('menu', 'i lg'); ?></div><div><small>Menu</small><b><?= number_format($total_menu, 0, ',', '.'); ?></b></div></div>
        <div class="stat"><div class="ib"><?= ico('user', 'i lg'); ?></div><div><small>User</small><b><?= number_format($total_user, 0, ',', '.'); ?></b></div></div>
    </section>

    <div class="grid">

        <!-- GRAFIK -->
        <section class="panel">
            <div class="ph">
                <h2>Pendapatan 7 hari</h2>
                <strong><?= rp($total_7hari); ?></strong>
            </div>
            <div class="chart">
                <?php foreach ($data_grafik as $d):
                    $tinggi = ($d['total'] / $maks) * 100;
                    if ($d['total'] > 0 && $tinggi < 4) $tinggi = 4;
                    $is_hi = $d['total'] > 0 && $d['total'] == $maks;
                ?>
                <div class="col <?= $is_hi ? 'hi' : ''; ?>" title="<?= rp($d['total']); ?>">
                    <span class="v"><?= $d['total'] > 0 ? ribu($d['total']) : ''; ?></span>
                    <div class="bar" style="height: <?= round($tinggi * 0.62, 1); ?>%;"></div>
                    <span class="d"><?= $d['hari']; ?></span>
                    <span class="t"><?= $d['tgl']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- PESANAN TERBARU -->
        <section class="panel">
            <div class="ph">
                <h2>Pesanan terbaru</h2>
                <a href="laporan/index.php">Lihat semua</a>
            </div>
            <?php if ($q_pesanan && mysqli_num_rows($q_pesanan) > 0): ?>
            <ul class="orders">
                <?php while ($p = mysqli_fetch_assoc($q_pesanan)):
                    if (($p['status_bayar'] ?? '') === 'lunas') {
                        $status = 'lunas';
                    } else {
                        $status = 'antre';
                        if (!empty($p['status_memasak'])) {
                            $arr = explode(',', $p['status_memasak']);
                            $status = trim($arr[0]);
                        }
                    }
                    $label = $nama_status[$status] ?? ucfirst($status);
                    $kelas = isset($nama_status[$status]) ? $status : 'antre';
                ?>
                <li>
                    <div class="no"><?= sprintf("%02d", (int)$p['nomor_meja']); ?></div>
                    <div class="info">
                        <b>Meja <?= sprintf("%02d", (int)$p['nomor_meja']); ?></b>
                        <small><?= rp($p['total_harga']); ?></small>
                    </div>
                    <span class="badge <?= htmlspecialchars($kelas); ?>"><?= htmlspecialchars($label); ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php else: ?>
                <p class="empty">Belum ada pesanan masuk.</p>
            <?php endif; ?>
        </section>

    </div>

    <!-- STATUS MEJA -->
    <section class="panel">
        <div class="ph">
            <h2>Status meja</h2>
            <a href="meja/index.php">Kelola meja</a>
        </div>
        <?php if ($q_status_meja && mysqli_num_rows($q_status_meja) > 0): ?>
        <div class="tables">
            <?php while ($m = mysqli_fetch_assoc($q_status_meja)):
                $sm = in_array($m['status_meja'], ['kosong', 'terisi'], true) ? $m['status_meja'] : 'kosong';
            ?>
            <div class="tb <?= $sm; ?>">
                <?= sprintf("%02d", (int)$m['nomor_meja']); ?>
                <small><?= ucfirst($sm); ?></small>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <p class="empty">Belum ada meja. Tambahkan lewat Kelola meja.</p>
        <?php endif; ?>
    </section>

</main>

<!-- NAVIGASI BAWAH (HP) -->
<nav class="tabbar" aria-label="Navigasi">
    <a href="dashboard.php" class="on"><span class="pill"><?= ico('home', 'i lg'); ?></span>Beranda</a>
    <a href="menu/index.php"><span class="pill"><?= ico('menu', 'i lg'); ?></span>Menu</a>
    <a href="meja/index.php"><span class="pill"><?= ico('table', 'i lg'); ?></span>Meja</a>
    <a href="laporan/index.php"><span class="pill"><?= ico('chart', 'i lg'); ?></span>Laporan</a>
    <button type="button" id="more" aria-haspopup="true" aria-expanded="false"><span class="pill"><?= ico('more', 'i lg'); ?></span>Lainnya</button>
</nav>

<div class="scrim" id="scrim"></div>
<div class="sheet" id="sheet" role="menu">
    <div class="grab"></div>
    <a href="users/index.php" role="menuitem"><?= ico('user', 'i lg'); ?> User</a>
    <a href="kategori/index.php" role="menuitem"><?= ico('grid', 'i lg'); ?> Kategori</a>
    <a href="../logout.php" role="menuitem"><?= ico('logout', 'i lg'); ?> Logout</a>
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