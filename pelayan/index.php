<?php
session_start();
include "../config.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: ../login.php");
    exit;
}

// Handle action tandai diantar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'antar') {
    $id_pesanan = intval($_POST['id_pesanan'] ?? 0);
    if ($id_pesanan > 0) {
        mysqli_query($db, "UPDATE detail_pesanan SET status_memasak = 'diantar' WHERE id_pesanan = $id_pesanan");
    }
    header('Location: index.php');
    exit;
}

// Statistik
$q_proses = mysqli_query($db, "SELECT COUNT(DISTINCT id_pesanan) AS total FROM detail_pesanan WHERE status_memasak = 'dimasak'");
$total_proses = mysqli_fetch_assoc($q_proses)['total'] ?? 0;

$q_siap = mysqli_query($db, "SELECT COUNT(DISTINCT id_pesanan) AS total FROM detail_pesanan WHERE status_memasak = 'selesai'");
$total_siap = mysqli_fetch_assoc($q_siap)['total'] ?? 0;

// Daftar pesanan siap
$q_pesanan = mysqli_query($db, "
    SELECT p.id_pesanan, m.nomor_meja, p.created_at,
        GROUP_CONCAT(CONCAT(mn.nama_menu, ' × ', dp.jumlah) SEPARATOR '||') AS daftar_menu
    FROM pesanan p
    LEFT JOIN meja m ON p.id_meja = m.id_meja
    INNER JOIN detail_pesanan dp ON dp.id_pesanan = p.id_pesanan
    LEFT JOIN menu mn ON mn.id_menu = dp.id_menu
    WHERE p.id_pesanan IN (SELECT DISTINCT id_pesanan FROM detail_pesanan WHERE status_memasak = 'selesai')
    GROUP BY p.id_pesanan, m.nomor_meja, p.created_at
    ORDER BY p.created_at ASC
");

$pesanan_siap = [];
if ($q_pesanan) {
    while ($row = mysqli_fetch_assoc($q_pesanan)) {
        $row['items'] = !empty($row['daftar_menu']) ? explode('||', $row['daftar_menu']) : [];
        $pesanan_siap[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2d6a4f">
    <title>Dashboard Pelayan - RM. Tambaksari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        :root { --green: #1e5e4e; --green-light: #2d6a4f; --green-soft: #d1fae5; --green-bright: #40916c; --bg: #f0f2f5; --card: #ffffff; --text: #1a1a1a; --text-muted: #6b7280; --border: #e5e7eb; --warn: #f59e0b; --warn-soft: #fef3c7; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; padding: 20px 16px 60px; }
        .container { max-width: 720px; margin: 0 auto; }

        /* ================================
           OVERLAY "MULAI BERTUGAS"
        ================================ */
        .overlay-start {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, var(--green), var(--green-light));
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 24px;
            text-align: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .overlay-start.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .overlay-start::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .overlay-start::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .overlay-icon {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin-bottom: 24px;
            border: 2px solid rgba(255,255,255,0.3);
            animation: pulse 2s infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255,255,255,0); }
        }

        .overlay-start h2 {
            font-family: 'Playfair Display', serif;
            color: white;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .overlay-start p {
            color: rgba(255,255,255,0.9);
            font-size: 14.5px;
            line-height: 1.6;
            max-width: 400px;
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }

        .overlay-start p strong {
            color: white;
            font-weight: 700;
        }

        .btn-start {
            background: white;
            color: var(--green);
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 32px rgba(0,0,0,0.25);
            transition: all 0.3s;
            font-family: inherit;
            position: relative;
            z-index: 1;
            letter-spacing: 0.5px;
        }

        .btn-start:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 16px 40px rgba(0,0,0,0.3);
        }

        .btn-start:active {
            transform: translateY(0) scale(0.98);
        }

        .overlay-hint {
            color: rgba(255,255,255,0.7);
            font-size: 11.5px;
            margin-top: 20px;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        /* ================================
           AUDIO STATUS BADGE
        ================================ */
        .audio-status {
            position: fixed;
            top: 14px;
            right: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-size: 11.5px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 30px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 9999;
            animation: bounceIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .audio-status .dot {
            width: 7px;
            height: 7px;
            background: currentColor;
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        @keyframes bounceIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* ================================
           HEADER
        ================================ */
        .header {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-light) 100%);
            border-radius: 16px;
            padding: 20px 22px;
            color: white;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            box-shadow: 0 12px 32px rgba(30, 94, 78, 0.15);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
        }

        .header-text { position: relative; z-index: 1; }
        .header-text h1 { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 800; margin-bottom: 3px; }
        .header-text p { font-size: 12.5px; opacity: 0.9; font-weight: 500; }

        /* ================================
           STATS CARDS
        ================================ */
        .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px; }
        .stat-card { background: var(--card); border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid var(--border); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--green-bright); }
        .stat-card.siap::before { background: var(--green-light); }
        .stat-card.proses::before { background: var(--warn); }
        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 8px; }
        .stat-value { font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 800; color: var(--green); line-height: 1; }
        .stat-card.siap .stat-value { color: var(--green-light); }
        .stat-card.proses .stat-value { color: var(--warn); }

        /* ================================
           ALERT BANNER
        ================================ */
        .alert-banner { background: var(--card); border-radius: 14px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid var(--border); border-left: 4px solid var(--warn); display: flex; align-items: center; gap: 12px; margin-bottom: 18px; animation: pulseAlert 2s infinite; }
        @keyframes pulseAlert { 0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.06); } 50% { box-shadow: 0 4px 20px rgba(245, 158, 11, 0.25); } }
        .alert-icon { font-size: 24px; flex-shrink: 0; }
        .alert-text { flex: 1; }
        .alert-text strong { display: block; font-size: 15px; color: var(--text); margin-bottom: 2px; }
        .alert-text small { font-size: 12.5px; color: var(--text-muted); }
        .badge-new { background: var(--green-soft); color: var(--green); font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ================================
           ORDER CARD
        ================================ */
        .order-card { background: var(--card); border-radius: 16px; padding: 20px 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 1px solid var(--border); margin-bottom: 16px; animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .order-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--green-light), var(--green-bright)); }
        .order-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .order-id { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.3px; }
        .order-tujuan { font-size: 13.5px; color: var(--text-muted); font-weight: 500; display: flex; align-items: center; gap: 6px; }
        .order-time { background: var(--warn-soft); color: #92400e; font-size: 11px; font-weight: 700; padding: 5px 11px; border-radius: 20px; letter-spacing: 0.3px; white-space: nowrap; }
        .items-title { font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; }
        .items-list { list-style: none; padding: 0; margin-bottom: 18px; }
        .items-list li { padding: 10px 0; font-size: 14.5px; color: var(--text); font-weight: 500; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed var(--border); }
        .items-list li:last-child { border-bottom: none; }
        .items-list li::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--green-light); flex-shrink: 0; }
        .btn-diantar { width: 100%; background: linear-gradient(135deg, var(--green-light), var(--green-bright)); color: white; border: none; padding: 15px 20px; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; letter-spacing: 0.3px; box-shadow: 0 6px 16px rgba(45, 106, 79, 0.3); transition: all 0.25s; font-family: inherit; }
        .btn-diantar:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(45, 106, 79, 0.4); }

        /* ================================
           EMPTY STATE
        ================================ */
        .empty-state { background: var(--card); border-radius: 16px; padding: 60px 30px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid var(--border); }
        .empty-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: var(--green-soft); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; }
        .empty-state h3 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--text); margin-bottom: 8px; font-weight: 800; }
        .empty-state p { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; }
        .footer-note { text-align: center; font-size: 11px; color: var(--text-muted); margin-top: 24px; opacity: 0.7; }

        /* TOAST */
        .toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(120px); background: linear-gradient(135deg, var(--green), var(--green-light)); color: white; padding: 14px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 16px 40px rgba(30, 94, 78, 0.4); opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); z-index: 999; pointer-events: none; max-width: 90%; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast .check { width: 24px; height: 24px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0; }
    </style>
</head>
<body>

<!-- OVERLAY "MULAI BERTUGAS" -->
<div class="overlay-start" id="overlayStart">
    <div class="overlay-icon">🔔</div>
    <h2>Siap Menerima Notifikasi?</h2>
    <p>Klik tombol di bawah <strong>1 kali saja</strong> untuk mengaktifkan notifikasi suara.<br>Setelah itu, alarm akan otomatis berbunyi setiap ada pesanan siap diantar.</p>
    <button class="btn-start" onclick="mulaiBertugas()">
        <span>🚀</span>
        <span>Mulai Bertugas</span>
    </button>
    <div class="overlay-hint">⚠️ Pastikan volume HP/tablet TIDAK MUTE<br>dan biarkan halaman ini tetap terbuka</div>
</div>

<!-- AUDIO STATUS -->
<div class="audio-status" id="audioStatus" style="display:none;">
    <span class="dot"></span>
    <span>🔊 Suara Aktif</span>
</div>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="header-icon">🔔</div>
        <div class="header-text">
            <h1>Dashboard Pelayan</h1>
            <p>RM. Tambak Sari Demak Onggorawe</p>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card proses">
            <div class="stat-label">Sedang diproses</div>
            <div class="stat-value" id="statProses"><?= $total_proses; ?></div>
        </div>
        <div class="stat-card siap">
            <div class="stat-label">Siap diantar</div>
            <div class="stat-value" id="statSiap"><?= $total_siap; ?></div>
        </div>
    </div>

    <!-- ALERT -->
    <?php if ($total_siap > 0) : ?>
    <div class="alert-banner">
        <div class="alert-icon">🔔</div>
        <div class="alert-text">
            <strong>Pesanan siap diantar!</strong>
            <small><?= $total_siap; ?> pesanan menunggu untuk diantar ke meja</small>
        </div>
        <span class="badge-new">Baru</span>
    </div>
    <?php endif; ?>

    <!-- DAFTAR PESANAN -->
    <?php if (!empty($pesanan_siap)) : ?>
        <?php foreach ($pesanan_siap as $order) : ?>
            <div class="order-card">
                <div class="order-head">
                    <div>
                        <div class="order-id">Pesanan #<?= str_pad($order['id_pesanan'], 3, '0', STR_PAD_LEFT); ?></div>
                        <div class="order-tujuan">📍 Tujuan: Meja <?= htmlspecialchars($order['nomor_meja'] ?? '-'); ?></div>
                    </div>
                    <div class="order-time"><?= date('H:i', strtotime($order['created_at'])); ?></div>
                </div>

                <div class="items-title">Item yang dipesan</div>
                <ul class="items-list">
                    <?php foreach ($order['items'] as $item) : ?>
                        <li><?= htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>

                <form method="POST" onsubmit="return confirm('Tandai pesanan #<?= $order['id_pesanan']; ?> sudah diantar?')">
                    <input type="hidden" name="action" value="antar">
                    <input type="hidden" name="id_pesanan" value="<?= $order['id_pesanan']; ?>">
                    <button type="submit" class="btn-diantar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Tandai Sudah Diantar
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="empty-state">
            <div class="empty-icon">🍽️</div>
            <h3>Belum ada pesanan siap diantar</h3>
            <p>Tunggu notifikasi dari dapur.<br>Pesanan akan muncul otomatis saat selesai dimasak.</p>
        </div>
    <?php endif; ?>

    <div class="footer-note">Halaman ini update otomatis setiap 10 detik & memberi notifikasi alarm suara.</div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">
    <span class="check">✓</span>
    <span id="toastMessage">Pesanan telah diantar!</span>
</div>

<script>
    // =========================
    // VARIABEL GLOBAL
    // =========================
    const statSiap = document.getElementById('statSiap');
    const statProses = document.getElementById('statProses');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const overlayStart = document.getElementById('overlayStart');
    const audioStatus = document.getElementById('audioStatus');

    let lastSiapCount = <?= (int)$total_siap; ?>;
    let audioCtx = null;
    let audioActive = false;
    let alarmPlaying = false;

    // =========================
    // MULAI BERTUGAS — Unlock Audio
    // =========================
    function mulaiBertugas() {
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();

            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            // Test beep 2x sebagai konfirmasi
            [0, 0.2].forEach((delay) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = 880;
                osc.type = 'sine';
                const tStart = audioCtx.currentTime + delay;
                gain.gain.setValueAtTime(0, tStart);
                gain.gain.linearRampToValueAtTime(0.3, tStart + 0.01);
                gain.gain.linearRampToValueAtTime(0, tStart + 0.12);
                osc.start(tStart);
                osc.stop(tStart + 0.14);
            });

            audioActive = true;

            // Sembunyikan overlay
            overlayStart.classList.add('hidden');

            // Tampilkan badge suara aktif
            audioStatus.style.display = 'flex';

            // Simpan status di sessionStorage (kalau user refresh tidak perlu klik lagi)
            sessionStorage.setItem('audioUnlocked', '1');

            showToast('🔊 Notifikasi suara aktif! Siap bertugas.');
            console.log('✅ Audio aktif');

        } catch (e) {
            console.error('Gagal aktifkan audio:', e);
            alert('❌ Browser tidak mendukung audio. Coba pakai Chrome/Edge/Safari terbaru.');
        }
    }

    // =========================
    // AUTO-CHECK: Kalau sudah unlock sebelumnya → skip overlay
    // =========================
    window.addEventListener('load', function() {
        if (sessionStorage.getItem('audioUnlocked') === '1') {
            // User sudah pernah klik "Mulai Bertugas" di session ini
            overlayStart.classList.add('hidden');
            audioStatus.style.display = 'flex';

            // Tetap buat audio context (biar siap mainkan alarm nanti)
            try {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                audioActive = true;
                console.log('✅ Audio sudah unlocked (dari session sebelumnya)');
            } catch (e) {
                console.warn('Gagal restore audio:', e);
            }
        }
    });

    // =========================
    // TOAST
    // =========================
    let toastTimer;
    function showToast(msg) {
        toastMessage.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // =========================
    // 🚨 ALARM KERAS — 4 DETIK × 2 PUTARAN = 8 DETIK
    // =========================
    function playNotif() {
        if (!audioCtx || !audioActive) {
            console.warn('Audio belum diaktifkan.');
            return;
        }

        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }

        alarmPlaying = true;
        const now = audioCtx.currentTime;
        const ALARM_DURATION = 4.0;
        const TOTAL_PUTARAN = 2;

        for (let putaran = 0; putaran < TOTAL_PUTARAN; putaran++) {
            const startOffset = putaran * (ALARM_DURATION + 0.2);

            // LAYER 1: SIRENE UTAMA (Square wave naik-turun)
            const oscSiren = audioCtx.createOscillator();
            const gainSiren = audioCtx.createGain();
            oscSiren.connect(gainSiren);
            gainSiren.connect(audioCtx.destination);
            oscSiren.type = 'square';

            const FREQ_HIGH = 1000;
            const FREQ_LOW = 500;
            const SWITCH_INTERVAL = 0.12;
            let step = 0;

            for (let t = 0; t < ALARM_DURATION; t += SWITCH_INTERVAL) {
                const freqValue = (step % 2 === 0) ? FREQ_HIGH : FREQ_LOW;
                oscSiren.frequency.setValueAtTime(freqValue, now + startOffset + t);
                step++;
            }

            gainSiren.gain.setValueAtTime(0, now + startOffset);
            gainSiren.gain.linearRampToValueAtTime(0.65, now + startOffset + 0.05);
            gainSiren.gain.setValueAtTime(0.65, now + startOffset + ALARM_DURATION - 0.2);
            gainSiren.gain.linearRampToValueAtTime(0, now + startOffset + ALARM_DURATION);
            oscSiren.start(now + startOffset);
            oscSiren.stop(now + startOffset + ALARM_DURATION);

            // LAYER 2: BASS RUMBLE
            const oscBass = audioCtx.createOscillator();
            const gainBass = audioCtx.createGain();
            oscBass.connect(gainBass);
            gainBass.connect(audioCtx.destination);
            oscBass.type = 'triangle';
            oscBass.frequency.value = 100;
            gainBass.gain.setValueAtTime(0, now + startOffset);
            gainBass.gain.linearRampToValueAtTime(0.45, now + startOffset + 0.1);
            gainBass.gain.setValueAtTime(0.45, now + startOffset + ALARM_DURATION - 0.2);
            gainBass.gain.linearRampToValueAtTime(0, now + startOffset + ALARM_DURATION);
            oscBass.start(now + startOffset);
            oscBass.stop(now + startOffset + ALARM_DURATION);

            // LAYER 3: TONE PENEKAN (TIT-TIT-TIT)
            for (let i = 0; i < 8; i++) {
                const tTone = now + startOffset + (i * 0.5);
                const durasi = 0.18;
                if (tTone + durasi > now + startOffset + ALARM_DURATION) break;

                const oscTone = audioCtx.createOscillator();
                const gainTone = audioCtx.createGain();
                oscTone.connect(gainTone);
                gainTone.connect(audioCtx.destination);
                oscTone.type = 'sine';
                oscTone.frequency.value = 1600;
                gainTone.gain.setValueAtTime(0, tTone);
                gainTone.gain.linearRampToValueAtTime(0.5, tTone + 0.01);
                gainTone.gain.setValueAtTime(0.5, tTone + durasi - 0.03);
                gainTone.gain.linearRampToValueAtTime(0, tTone + durasi);
                oscTone.start(tTone);
                oscTone.stop(tTone + durasi);
            }
        }

        const totalTime = (ALARM_DURATION + 0.2) * TOTAL_PUTARAN;
        setTimeout(() => { alarmPlaying = false; }, totalTime * 1000);
        console.log('🚨 ALARM diputar — ' + totalTime + ' detik');
    }

    // =========================
    // POLLING STATUS
    // =========================
    function cekStatus() {
        fetch('cek_status.php?_=' + Date.now())
            .then(res => res.json())
            .then(data => {
                if (!data || data.error) return;

                if (statSiap) statSiap.textContent = data.siap;
                if (statProses) statProses.textContent = data.proses;

                if (data.siap > lastSiapCount) {
                    console.log('🚨 Pesanan baru!', lastSiapCount, '→', data.siap);
                    if (!alarmPlaying) playNotif();
                    showToast('🔔 Pesanan baru siap diantar!');
                    setTimeout(() => window.location.reload(), 3000);
                }

                if (data.siap < lastSiapCount) {
                    window.location.reload();
                }

                lastSiapCount = data.siap;
            })
            .catch(err => console.warn('Gagal cek status:', err));
    }

    setInterval(cekStatus, 10000);
    setTimeout(cekStatus, 800);

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('👁️ Halaman aktif, cek status...');
            cekStatus();
        }
    });
</script>

</body>
</html>