<?php
session_start();
include "../config.php";

// Cek login koki
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Ambil daftar pesanan (per item), lalu dikelompokkan per pesanan
$query = mysqli_query($db, "
    SELECT
        dp.id_detail,
        dp.id_pesanan,
        dp.jumlah,
        dp.status_memasak,
        mn.nama_menu,
        m.nomor_meja,
        p.created_at
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    JOIN menu mn ON dp.id_menu = mn.id_menu
    JOIN meja m ON p.id_meja = m.id_meja
    WHERE dp.id_pesanan NOT IN (
        SELECT id_pesanan FROM detail_pesanan WHERE status_memasak = 'diantar'
    )
    ORDER BY p.created_at DESC, p.id_pesanan DESC, dp.id_detail ASC
");

if (!$query) {
    die("Gagal mengambil data pesanan.");
}

$pesanan = [];   // key = id_pesanan
while ($row = mysqli_fetch_assoc($query)) {
    $id = (int)$row['id_pesanan'];
    if (!isset($pesanan[$id])) {
        $pesanan[$id] = [
            'id_pesanan' => $id,
            'nomor_meja' => $row['nomor_meja'],
            'created_at' => $row['created_at'],
            'items'      => [],
            'status'     => 'antre',
        ];
    }
    $pesanan[$id]['items'][] = $row;
}

// Tentukan status per pesanan + hitung untuk filter
$hitung = ['semua' => 0, 'antre' => 0, 'dimasak' => 0, 'selesai' => 0];
foreach ($pesanan as &$p) {
    $semuaSelesai = true;
    $adaDimasak   = false;
    foreach ($p['items'] as $it) {
        if ($it['status_memasak'] !== 'selesai') $semuaSelesai = false;
        if ($it['status_memasak'] === 'dimasak' || $it['status_memasak'] === 'selesai') $adaDimasak = true;
    }
    $p['status'] = $semuaSelesai ? 'selesai' : ($adaDimasak ? 'dimasak' : 'antre');
    $hitung['semua']++;
    $hitung[$p['status']]++;
}
unset($p);

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
<title>Daftar Pesanan Koki - RM. Tambaksari</title>
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
    --blue: #1b52a8;   --blue-bg: #e0ecff;
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

/* ========== FILTER STATUS ========== */
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

/* ========== DAFTAR ========== */
.ph { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 14px; }
.ph h2 { font-size: 18px; font-weight: 800; color: var(--ink); }
.ph .count { font-size: 13px; color: var(--muted); font-weight: 600; }

.tickets { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; list-style: none; align-items: start; }
.t { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.t.masak { border-color: #b9d2f6; }
.t.beres { background: #fbfbfb; }
.t.hide { display: none; }

.t .row1 { display: flex; align-items: center; gap: 12px; }
.mj { width: 52px; height: 52px; border-radius: 15px; flex: none; background: var(--tint); color: var(--maroon); display: grid; place-items: center; text-align: center; line-height: 1; }
.mj small { display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .4px; margin-bottom: 2px; }
.mj b { font-size: 20px; font-weight: 800; }
.t .info { flex: 1; min-width: 0; }
.t .info span { display: block; font-size: 12.5px; color: var(--muted); font-weight: 600; }
.t .info em { font-style: normal; display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--muted); }

.badge { padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.badge.antre   { background: var(--amber-bg); color: var(--amber); }
.badge.dimasak { background: var(--blue-bg); color: var(--blue); }
.badge.selesai { background: var(--green-bg); color: var(--green); }

/* Daftar menu dalam satu pesanan */
.items { list-style: none; display: grid; gap: 8px; }
.items li { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; background: #faf7f7; border-radius: 12px; }
.items li b { font-size: 16px; font-weight: 700; color: var(--ink); line-height: 1.25; overflow-wrap: anywhere; }
.items li.ok b { text-decoration: line-through; color: var(--muted); }

.qty { flex: none; background: var(--ink); color: #fff; border-radius: 12px; padding: 4px 10px; font-size: 14px; font-weight: 800; }
.t.beres .qty { background: #8a8184; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 50px; padding: 13px 18px; border-radius: 14px; font-size: 15px; font-weight: 800; border: 0; cursor: pointer; font-family: inherit; color: #fff; }
.btn.mulai   { background: #b7791f; }
.btn.mulai:hover { background: #946200; }
.btn.selesai { background: var(--green); }
.btn.selesai:hover { background: #0b6140; }
.done { display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; border-radius: 14px; background: var(--green-bg); color: var(--green); font-size: 14px; font-weight: 700; }

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

    .chips { margin-left: -14px; margin-right: -14px; padding-left: 14px; padding-right: 14px; }
    .tickets { grid-template-columns: 1fr; }

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

@media (max-width: 360px) { .profile b { max-width: 60px; } }
@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }

/* ========== CETAK ========== */
@media print {
    .side, .tabbar, .profile, .btn, .chips { display: none !important; }
    body { background: #fff; }
    .main { margin: 0; padding: 8px; max-width: none; }
    .t { break-inside: avoid; }
    .badge, .done { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></symbol>
</svg>

<!-- SIDEBAR DESKTOP -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../assets/images/logo.png" alt="Logo RM Tambaksari">
        <div><b>RM. TAMBAKSARI</b><span>Onggorawe Demak</span></div>
    </div>
    <ul>
        <li><a href="dashboard.php"><?= ico('home'); ?> Dashboard Koki</a></li>
        <li><a href="pesanan.php" class="on"><?= ico('list'); ?> Daftar Pesanan</a></li>
        <li><a href="menu.php"><?= ico('menu'); ?> Menu</a></li>
    </ul>
    <div class="gap"></div>
    <a href="../logout.php" class="out"><?= ico('logout'); ?> Logout</a>
</aside>

<main class="main">

    <header class="top">
        <div>
            <h1>Daftar Pesanan</h1>
            <p>Kelola antrean dan status memasak pesanan pelanggan.</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial; ?></div>
            <div class="n">
                <b><?= $nama_user; ?></b>
                <small><?= ico('flame'); ?> Koki</small>
            </div>
        </div>
    </header>

    <!-- FILTER STATUS -->
    <div class="chips" role="tablist" aria-label="Filter status memasak">
        <button type="button" class="chip on" data-filter="semua">Semua <span><?= $hitung['semua']; ?></span></button>
        <button type="button" class="chip" data-filter="antre">Antre <span><?= $hitung['antre']; ?></span></button>
        <button type="button" class="chip" data-filter="dimasak">Dimasak <span><?= $hitung['dimasak']; ?></span></button>
        <button type="button" class="chip" data-filter="selesai">Selesai <span><?= $hitung['selesai']; ?></span></button>
    </div>

    <div class="ph">
        <h2>Antrean pesanan dapur</h2>
        <span class="count" id="jumlahTampil"><?= $hitung['semua']; ?> pesanan</span>
    </div>

    <?php if ($hitung['semua'] > 0): ?>
    <ul class="tickets" id="daftar">
        <?php foreach ($pesanan as $p):
            $kelas_st = $p['status'];
        ?>
        <li class="t <?= $kelas_st === 'dimasak' ? 'masak' : ($kelas_st === 'selesai' ? 'beres' : ''); ?>" data-status="<?= $kelas_st; ?>">
            <div class="row1">
                <div class="mj"><div><small>MEJA</small><b><?= htmlspecialchars((string)$p['nomor_meja']); ?></b></div></div>
                <div class="info">
                    <span>Pesanan #<?= (int)$p['id_pesanan']; ?></span>
                    <em><?= ico('clock', 'i sm'); ?> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($p['created_at']))); ?></em>
                </div>
                <span class="badge <?= $kelas_st; ?>"><?= ucfirst($kelas_st); ?></span>
            </div>

            <ul class="items">
                <?php foreach ($p['items'] as $it): ?>
                <li class="<?= $it['status_memasak'] === 'selesai' ? 'ok' : ''; ?>">
                    <b><?= htmlspecialchars($it['nama_menu']); ?></b>
                    <span class="qty">&times;<?= (int)$it['jumlah']; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($kelas_st === 'antre'): ?>
                <form method="POST" action="status_pesanan.php">
                    <input type="hidden" name="id_pesanan" value="<?= (int)$p['id_pesanan']; ?>">
                    <input type="hidden" name="status_memasak" value="dimasak">
                    <button type="submit" class="btn mulai"><?= ico('flame', 'i sm'); ?> Mulai memasak</button>
                </form>
            <?php elseif ($kelas_st === 'dimasak'): ?>
                <form method="POST" action="status_pesanan.php">
                    <input type="hidden" name="id_pesanan" value="<?= (int)$p['id_pesanan']; ?>">
                    <input type="hidden" name="status_memasak" value="selesai">
                    <button type="submit" class="btn selesai"><?= ico('check', 'i sm'); ?> Selesai memasak</button>
                </form>
            <?php else: ?>
                <div class="done"><?= ico('check', 'i sm'); ?> Sudah selesai</div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="empty" id="kosongFilter">
        <b>Tidak ada pesanan</b>
        Belum ada pesanan dengan status ini.
    </div>
    <?php else: ?>
        <div class="empty">
            <b>Belum ada pesanan</b>
            Pesanan pelanggan akan muncul di sini.
        </div>
    <?php endif; ?>

</main>

<!-- NAVIGASI BAWAH (HP) -->
<nav class="tabbar" aria-label="Navigasi">
    <a href="dashboard.php"><span class="pill"><?= ico('home', 'i lg'); ?></span>Dashboard</a>
    <a href="pesanan.php" class="on"><span class="pill"><?= ico('list', 'i lg'); ?></span>Pesanan</a>
    <a href="menu.php"><span class="pill"><?= ico('menu', 'i lg'); ?></span>Menu</a>
    <a href="../logout.php" class="out"><span class="pill"><?= ico('logout', 'i lg'); ?></span>Keluar</a>
</nav>

<script>
(function () {
    var chips = document.querySelectorAll('.chip'),
        items = document.querySelectorAll('#daftar > .t'),
        kosong = document.getElementById('kosongFilter'),
        jumlah = document.getElementById('jumlahTampil');

    function terapkan(filter) {
        var tampil = 0;
        items.forEach(function (li) {
            var cocok = (filter === 'semua' || li.getAttribute('data-status') === filter);
            li.classList.toggle('hide', !cocok);
            if (cocok) tampil++;
        });
        chips.forEach(function (c) { c.classList.toggle('on', c.getAttribute('data-filter') === filter); });
        if (jumlah) jumlah.textContent = tampil + ' pesanan';
        if (kosong) kosong.style.display = (tampil === 0) ? 'block' : 'none';
        try { sessionStorage.setItem('filterPesananKoki', filter); } catch (e) {}
    }

    chips.forEach(function (c) {
        c.addEventListener('click', function () { terapkan(c.getAttribute('data-filter')); });
    });

    // Ingat filter terakhir (berguna setelah menekan tombol status)
    var simpan = 'semua';
    try { simpan = sessionStorage.getItem('filterPesananKoki') || 'semua'; } catch (e) {}
    terapkan(simpan);

    // Muat ulang otomatis tiap 15 detik agar pesanan yang sudah diantar pelayan hilang
    // (filter tetap diingat lewat sessionStorage)
    setInterval(function () {
        if (document.visibilityState === 'visible') { location.reload(); }
    }, 15000);
})();
</script>

</body>
</html>