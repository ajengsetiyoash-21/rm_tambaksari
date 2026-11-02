<?php
session_start();
include "../config.php";

// Pastikan yang masuk adalah koki
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || ($_SESSION['role'] ?? '') != 'koki') {
    header("Location: ../login.php");
    exit;
}

// Mengubah status memasak
if (isset($_POST['ubah_status'])) {
    $id_detail   = (int) ($_POST['id_detail'] ?? 0);
    $status_baru = $_POST['status_baru'] ?? '';

    $status_valid = ['dimasak', 'selesai'];

    if ($id_detail > 0 && in_array($status_baru, $status_valid, true)) {
        $up = mysqli_prepare($db, "UPDATE detail_pesanan SET status_memasak = ? WHERE id_detail = ?");
        mysqli_stmt_bind_param($up, "si", $status_baru, $id_detail);
        $update = mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        if ($update) {
            header("Location: dashboard.php?pesan=berhasil");
            exit;
        }
    }

    header("Location: dashboard.php?pesan=gagal");
    exit;
}

// Ringkasan status memasak (1 query)
$jumlah = ['antre' => 0, 'dimasak' => 0, 'selesai' => 0];
$q_ringkasan = mysqli_query($db, "
    SELECT status_memasak, COUNT(*) AS jumlah
    FROM detail_pesanan
    GROUP BY status_memasak
");
if (!$q_ringkasan) {
    die("Gagal mengambil ringkasan.");
}
while ($r = mysqli_fetch_assoc($q_ringkasan)) {
    if (isset($jumlah[$r['status_memasak']])) {
        $jumlah[$r['status_memasak']] = (int)$r['jumlah'];
    }
}

// Ambil daftar pesanan dapur
$query_pesanan = mysqli_query($db, "
    SELECT
        dp.id_detail,
        dp.id_pesanan,
        dp.jumlah,
        dp.status_memasak,
        m.nomor_meja,
        mn.nama_menu,
        p.created_at
    FROM detail_pesanan dp
    JOIN pesanan p  ON dp.id_pesanan = p.id_pesanan
    JOIN menu mn    ON dp.id_menu = mn.id_menu
    JOIN meja m     ON p.id_meja = m.id_meja
    WHERE dp.status_memasak IN ('antre', 'dimasak')
    ORDER BY p.created_at ASC, dp.id_detail ASC
");
if (!$query_pesanan) {
    die("Gagal mengambil data pesanan.");
}
$pesanan = [];
while ($row = mysqli_fetch_assoc($query_pesanan)) {
    $pesanan[] = $row;
}

// Helper
function ico($id, $cls = 'i') { return '<svg class="' . $cls . '" aria-hidden="true"><use href="#i-' . $id . '"/></svg>'; }
function sudah_berapa_lama($waktu) {
    $detik = max(0, time() - strtotime($waktu));
    $menit = (int) floor($detik / 60);
    if ($menit < 1)  return ['baru saja', 0];
    if ($menit < 60) return [$menit . ' menit lalu', $menit];
    $jam = (int) floor($menit / 60);
    return [$jam . ' jam lalu', $menit];
}

$pesan = $_GET['pesan'] ?? '';
$nama_user  = htmlspecialchars($_SESSION['username'] ?? 'Koki');
$inisial    = htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'K', 0, 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#5b1018">
<title>Dashboard Koki - RM. Tambaksari</title>
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

/* ========== NOTIFIKASI ========== */
.notif { display: flex; align-items: center; gap: 10px; border-radius: 14px; padding: 12px 14px; font-size: 13.5px; font-weight: 600; margin-bottom: 16px; }
.notif.ok { background: var(--green-bg); color: var(--green); }
.notif.no { background: var(--red-bg); color: var(--red); }

/* ========== RINGKASAN ========== */
.sum { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
.s { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 16px 18px; }
.s small { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 12.5px; font-weight: 600; }
.s b { display: block; font-size: 30px; font-weight: 800; color: var(--ink); line-height: 1.1; margin-top: 4px; }
.s.a small, .s.a b { color: var(--amber); }
.s.b small, .s.b b { color: var(--blue); }
.s.g small, .s.g b { color: var(--green); }

/* ========== ANTREAN ========== */
.ph { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 14px; }
.ph h2 { font-size: 18px; font-weight: 800; color: var(--ink); }
.ph .count { font-size: 13px; color: var(--muted); font-weight: 600; }

.tickets { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; list-style: none; }
.t { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.t.masak { border-color: #b9d2f6; }
.t .row1 { display: flex; align-items: center; gap: 12px; }
.mj { width: 52px; height: 52px; border-radius: 15px; flex: none; background: var(--tint); color: var(--maroon); display: grid; place-items: center; text-align: center; line-height: 1; }
.mj small { display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .4px; margin-bottom: 2px; }
.mj b { font-size: 20px; font-weight: 800; }
.t .info { flex: 1; min-width: 0; }
.t .info span { display: block; font-size: 12.5px; color: var(--muted); font-weight: 600; }
.t .info em { font-style: normal; display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--muted); }
.t .info em.lama { color: var(--red); font-weight: 700; }

.badge { padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.badge.antre   { background: var(--amber-bg); color: var(--amber); }
.badge.dimasak { background: var(--blue-bg); color: var(--blue); }

.t .menu { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.t .menu b { font-size: 18px; font-weight: 800; color: var(--ink); line-height: 1.25; overflow-wrap: anywhere; }
.qty { flex: none; background: var(--ink); color: #fff; border-radius: 12px; padding: 6px 12px; font-size: 16px; font-weight: 800; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 50px; padding: 13px 18px; border-radius: 14px; font-size: 15px; font-weight: 800; border: 0; cursor: pointer; font-family: inherit; color: #fff; }
.btn.mulai   { background: #b7791f; }
.btn.mulai:hover { background: #946200; }
.btn.selesai { background: var(--green); }
.btn.selesai:hover { background: #0b6140; }

.empty { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 36px 16px; text-align: center; color: var(--muted); font-size: 14.5px; }
.empty .i { width: 34px; height: 34px; color: var(--green); margin-bottom: 8px; }
.empty b { display: block; color: var(--ink); font-size: 16px; margin-bottom: 2px; }

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

    .sum { gap: 8px; }
    .s { padding: 12px; border-radius: 16px; }
    .s small { font-size: 11.5px; gap: 5px; }
    .s b { font-size: 24px; }

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

@media (max-width: 360px) { .profile b { max-width: 60px; } .s b { font-size: 21px; } }
@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }

/* ========== CETAK ========== */
@media print {
    .side, .tabbar, .profile, .btn, .notif { display: none !important; }
    body { background: #fff; }
    .main { margin: 0; padding: 8px; max-width: none; }
    .t, .s { break-inside: avoid; }
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
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></symbol>
    <symbol id="i-hourglass" viewBox="0 0 24 24"><path d="M6 3h12"/><path d="M6 21h12"/><path d="M7 3c0 5 5 6 5 9s-5 4-5 9"/><path d="M17 3c0 5-5 6-5 9s5 4 5 9"/></symbol>
</svg>

<!-- SIDEBAR DESKTOP -->
<aside class="side" aria-label="Menu utama">
    <div class="brand">
        <img src="../assets/images/logo.png" alt="Logo RM Tambaksari">
        <div><b>RM. TAMBAKSARI</b><span>Onggorawe Demak</span></div>
    </div>
    <ul>
        <li><a href="dashboard.php" class="on"><?= ico('home'); ?> Dashboard Koki</a></li>
        <li><a href="pesanan.php"><?= ico('list'); ?> Daftar Pesanan</a></li>
        <li><a href="menu.php"><?= ico('menu'); ?> Menu</a></li>
    </ul>
    <div class="gap"></div>
    <a href="../logout.php" class="out"><?= ico('logout'); ?> Logout</a>
</aside>

<main class="main">

    <header class="top">
        <div>
            <h1>Dashboard Koki</h1>
            <p>Kelola antrean dan proses memasak pesanan pelanggan.</p>
        </div>
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= $inisial; ?></div>
            <div class="n">
                <b><?= $nama_user; ?></b>
                <small><?= ico('flame'); ?> Koki</small>
            </div>
        </div>
    </header>

    <?php if ($pesan === 'berhasil'): ?>
        <div class="notif ok" role="status"><?= ico('check'); ?> Status pesanan berhasil diperbarui.</div>
    <?php elseif ($pesan === 'gagal'): ?>
        <div class="notif no" role="alert"><?= ico('alert'); ?> Status pesanan gagal diperbarui.</div>
    <?php endif; ?>

    <!-- RINGKASAN -->
    <section class="sum">
        <div class="s a"><small><?= ico('hourglass', 'i sm'); ?> Antre</small><b><?= $jumlah['antre']; ?></b></div>
        <div class="s b"><small><?= ico('flame', 'i sm'); ?> Dimasak</small><b><?= $jumlah['dimasak']; ?></b></div>
        <div class="s g"><small><?= ico('check', 'i sm'); ?> Selesai</small><b><?= $jumlah['selesai']; ?></b></div>
    </section>

    <!-- ANTREAN -->
    <div class="ph">
        <h2>Antrean pesanan dapur</h2>
        <span class="count"><?= count($pesanan); ?> menu</span>
    </div>

    <?php if (count($pesanan) > 0): ?>
    <ul class="tickets">
        <?php foreach ($pesanan as $d):
            $antre = ($d['status_memasak'] == 'antre');
            [$lama_teks, $lama_menit] = sudah_berapa_lama($d['created_at']);
        ?>
        <li class="t <?= $antre ? '' : 'masak'; ?>">
            <div class="row1">
                <div class="mj"><div><small>MEJA</small><b><?= htmlspecialchars((string)$d['nomor_meja']); ?></b></div></div>
                <div class="info">
                    <span>Pesanan #<?= (int)$d['id_pesanan']; ?></span>
                    <em class="<?= $lama_menit >= 15 ? 'lama' : ''; ?>"><?= ico('clock', 'i sm'); ?> <?= $lama_teks; ?> &middot; <?= date('H:i', strtotime($d['created_at'])); ?></em>
                </div>
                <span class="badge <?= $antre ? 'antre' : 'dimasak'; ?>"><?= $antre ? 'Antre' : 'Dimasak'; ?></span>
            </div>

            <div class="menu">
                <b><?= htmlspecialchars($d['nama_menu']); ?></b>
                <span class="qty">&times;<?= (int)$d['jumlah']; ?></span>
            </div>

            <form method="POST">
                <input type="hidden" name="id_detail" value="<?= (int)$d['id_detail']; ?>">
                <?php if ($antre): ?>
                    <input type="hidden" name="status_baru" value="dimasak">
                    <button type="submit" name="ubah_status" class="btn mulai"><?= ico('flame', 'i sm'); ?> Mulai masak</button>
                <?php else: ?>
                    <input type="hidden" name="status_baru" value="selesai">
                    <button type="submit" name="ubah_status" class="btn selesai"><?= ico('check', 'i sm'); ?> Selesai</button>
                <?php endif; ?>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <div class="empty">
            <?= ico('check'); ?>
            <b>Semua pesanan sudah selesai</b>
            Belum ada pesanan yang perlu diproses.
        </div>
    <?php endif; ?>

</main>

<!-- NAVIGASI BAWAH (HP) -->
<nav class="tabbar" aria-label="Navigasi">
    <a href="dashboard.php" class="on"><span class="pill"><?= ico('home', 'i lg'); ?></span>Dashboard</a>
    <a href="pesanan.php"><span class="pill"><?= ico('list', 'i lg'); ?></span>Pesanan</a>
    <a href="menu.php"><span class="pill"><?= ico('menu', 'i lg'); ?></span>Menu</a>
    <a href="../logout.php" class="out"><span class="pill"><?= ico('logout', 'i lg'); ?></span>Keluar</a>
</nav>

<script>
(function () {
    // Hilangkan ?pesan=... dari alamat agar notifikasi tidak muncul lagi saat halaman dimuat ulang
    if (location.search.indexOf('pesan=') !== -1 && window.history.replaceState) {
        history.replaceState(null, '', location.pathname);
    }

    // Muat ulang otomatis tiap 30 detik agar pesanan baru muncul (hanya saat halaman sedang dilihat)
    setInterval(function () {
        if (document.visibilityState === 'visible') { location.reload(); }
    }, 30000);
})();
</script>

</body>
</html>