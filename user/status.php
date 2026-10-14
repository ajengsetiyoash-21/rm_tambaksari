<?php
session_start();
include "../config.php";

// =========================
// AMBIL ID USER DARI SESSION
// =========================
$id_user = $_SESSION['id_user'] ?? 0;

// =========================
// AMBIL DATA PESANAN
// =========================
$pesanan_list = [];

if ($id_user > 0) {
    // Kalau user login → tampilkan pesanan miliknya
    $q = mysqli_query($db, "
        SELECT 
            p.*,
            m.nomor_meja,
            GROUP_CONCAT(DISTINCT dp.status_memasak) AS status_memasak
        FROM pesanan p
        LEFT JOIN meja m ON p.id_meja = m.id_meja
        LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        WHERE p.id_user = $id_user
        GROUP BY p.id_pesanan
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
} else {
    // Kalau tidak login → tampilkan 5 pesanan terbaru (guest)
    $q = mysqli_query($db, "
        SELECT 
            p.*,
            m.nomor_meja,
            GROUP_CONCAT(DISTINCT dp.status_memasak) AS status_memasak
        FROM pesanan p
        LEFT JOIN meja m ON p.id_meja = m.id_meja
        LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        GROUP BY p.id_pesanan
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
}

if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        // Ambil detail menu
        $id_p = $row['id_pesanan'];
        $q_d = mysqli_query($db, "
            SELECT dp.*, mn.nama_menu 
            FROM detail_pesanan dp 
            LEFT JOIN menu mn ON mn.id_menu = dp.id_menu
            WHERE dp.id_pesanan = $id_p
        ");
        $items = [];
        while ($d = mysqli_fetch_assoc($q_d)) {
            $items[] = $d;
        }
        $row['items'] = $items;
        $pesanan_list[] = $row;
    }
}

// =========================
// HELPER: TENTUKAN STAGE PESANAN
// =========================
function getStage($status_masak, $status_bayar) {
    // Ambil status paling "maju" dari semua item
    $statuses = $status_masak ? explode(',', $status_masak) : ['antre'];
    $statuses = array_map('trim', $statuses);

    // Prioritas status
    $prioritas = ['antre' => 1, 'dimasak' => 2, 'selesai' => 3, 'diantar' => 4];
    $max = 1;
    foreach ($statuses as $s) {
        if (isset($prioritas[$s]) && $prioritas[$s] > $max) {
            $max = $prioritas[$s];
        }
    }

    // Kalau sudah lunas → selesai
    if ($status_bayar === 'lunas' && $max >= 4) {
        return 5; // Selesai total
    }

    return $max;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2b1d0e">
    <title>Status Pesanan - RM. Tambaksari</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary: #3d2817;
            --primary-light: #5a3c24;
            --primary-dark: #241609;
            --accent: #c9a961;
            --accent-soft: #e0c896;
            --accent-glow: #f0d9a0;
            --cream: #faf6ee;
            --cream-warm: #f2e9d6;
            --bg: #f7efdc;
            --card-bg: #ffffff;
            --text: #2b1d0e;
            --text-muted: #8a7554;
            --border: #e6d4b0;
            --success: #2d6a4f;
            --success-soft: #d1fae5;
            --warn: #d97706;
            --warn-soft: #fef3c7;
            --info: #0284c7;
            --info-soft: #e0f2fe;
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            background-color: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body {
            padding-bottom: 110px;
            min-height: 100vh;
        }

        /* =========================
           HEADER
        ========================= */
        .header {
            position: relative;
            background: linear-gradient(150deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            padding: 20px 22px 60px 22px;
            color: var(--cream);
            overflow: hidden;
            border-radius: 0 0 32px 32px;
            box-shadow: 0 12px 32px rgba(36, 22, 9, 0.28);
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 22px;
            background: var(--bg);
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
            z-index: 2;
        }

        .header-inner {
            position: relative;
            z-index: 3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent-glow), var(--accent));
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(0,0,0,0.3), inset 0 0 0 1.5px rgba(255,255,255,0.35);
            flex-shrink: 0;
        }

        .header-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.8px;
            line-height: 1.15;
        }

        .header-text small {
            display: block;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.4px;
            color: var(--accent-soft);
            margin-top: 4px;
            opacity: 0.9;
        }

        .refresh-btn {
            background: rgba(250, 246, 238, 0.10);
            border: 1px solid rgba(201, 169, 97, 0.35);
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-soft);
            backdrop-filter: blur(8px);
            white-space: nowrap;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .refresh-btn:hover {
            background: rgba(250, 246, 238, 0.2);
        }

        .refresh-btn.spinning svg {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* =========================
           SUMMARY
        ========================= */
        .summary-wrap {
            padding: 0 22px;
            margin-top: -42px;
            position: relative;
            z-index: 5;
        }

        .summary-card {
            background: linear-gradient(135deg, #fffdf8 0%, var(--cream-warm) 100%);
            border-radius: 22px;
            padding: 20px 22px;
            box-shadow: 0 20px 40px rgba(61, 40, 23, 0.16), inset 0 0 0 1px rgba(201, 169, 97, 0.25);
            border: 1px solid rgba(201, 169, 97, 0.3);
        }

        .summary-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .summary-total {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .summary-total .cur {
            font-size: 13px;
            font-weight: 700;
            opacity: 0.7;
        }

        .summary-meta {
            display: flex;
            gap: 16px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed rgba(201, 169, 97, 0.4);
        }

        .summary-meta .item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .summary-meta .item span:first-child {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .summary-meta .item span:last-child {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
        }

        /* =========================
           SECTION
        ========================= */
        .section-head {
            padding: 22px 22px 4px 22px;
        }

        .section-head h2 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: var(--primary);
            font-weight: 800;
        }

        /* =========================
           ORDER CARD
        ========================= */
        .order-list {
            padding: 12px 22px 30px 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .order-card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(61, 40, 23, 0.06);
            border: 1px solid rgba(230, 212, 176, 0.7);
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .order-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .order-id {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .order-meta {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .order-badge {
            font-size: 10.5px;
            font-weight: 800;
            padding: 5px 11px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .order-badge.antre {
            background: var(--warn-soft);
            color: #92400e;
        }

        .order-badge.dimasak {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-badge.selesai {
            background: var(--success-soft);
            color: var(--success);
        }

        .order-badge.diantar {
            background: var(--info-soft);
            color: var(--info);
        }

        .order-badge.lunas {
            background: #dcfce7;
            color: #166534;
        }

        /* =========================
           TIMELINE / STEPPER
        ========================= */
        .timeline {
            display: flex;
            align-items: center;
            padding: 14px 0;
            margin: 10px 0;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 12px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }

        .step.done:not(:last-child)::after {
            background: var(--success);
        }

        .step .circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px;
            position: relative;
            z-index: 1;
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 800;
        }

        .step.done .circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .step.active .circle {
            background: var(--warn);
            border-color: var(--warn);
            color: white;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(217, 119, 6, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(217, 119, 6, 0); }
        }

        .step .label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            line-height: 1.2;
        }

        .step.done .label,
        .step.active .label {
            color: var(--primary);
        }

        /* =========================
           ORDER ITEMS
        ========================= */
        .order-items {
            padding: 12px 0;
            border-top: 1px dashed var(--border);
        }

        .order-items-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .order-item-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: var(--text);
        }

        .order-item-line .name {
            font-weight: 600;
        }

        .order-item-line .qty {
            color: var(--text-muted);
            font-weight: 500;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            margin-top: 8px;
            border-top: 1.5px solid var(--primary);
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 800;
            color: var(--primary);
        }

        /* =========================
           EMPTY
        ========================= */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: var(--card-bg);
            border-radius: 18px;
            margin: 12px 22px;
            box-shadow: 0 2px 8px rgba(61, 40, 23, 0.06);
            border: 1px dashed var(--border);
        }

        .empty-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cream-warm), #e8dcc2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .btn-explore {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            color: var(--accent-soft);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 6px 18px rgba(61, 40, 23, 0.3);
        }

        /* =========================
           BOTTOM NAV
        ========================= */
        .bottom-nav {
            position: fixed;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 28px);
            max-width: 520px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 10px 6px 12px 6px;
            box-shadow: 0 12px 40px rgba(36, 22, 9, 0.45), 0 4px 12px rgba(0,0,0,0.15);
            z-index: 100;
            border-radius: 22px;
            border: 1px solid rgba(201, 169, 97, 0.3);
            backdrop-filter: blur(10px);
        }

        .nav-item {
            color: rgba(250, 246, 238, 0.5);
            text-decoration: none;
            font-size: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 6px 16px;
            border-radius: 14px;
            font-weight: 700;
            letter-spacing: 0.4px;
            transition: all 0.3s;
            position: relative;
        }

        .nav-item svg {
            width: 21px;
            height: 21px;
            stroke-width: 2;
        }

        .nav-item.active,
        .nav-item:hover {
            color: var(--accent-soft);
            background: rgba(201, 169, 97, 0.08);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%);
            width: 22px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent-glow));
            border-radius: 0 0 4px 4px;
            box-shadow: 0 0 12px rgba(201, 169, 97, 0.7);
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-inner">
        <div class="header-title">
            <div class="header-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            <div class="header-text">
                <h1>Status Pesanan</h1>
                <small>LACAK PESANAN ANDA</small>
            </div>
        </div>
        <button class="refresh-btn" id="refreshBtn" onclick="refreshPage()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
            Refresh
        </button>
    </div>
</header>

<!-- SUMMARY -->
<div class="summary-wrap">
    <div class="summary-card">
        <div class="summary-label">Total Pesanan</div>
        <div class="summary-total">
            <span class="cur">Total</span>
            <?= count($pesanan_list); ?> Pesanan
        </div>
        <div class="summary-meta">
            <div class="item">
                <span>Terbaru</span>
                <span><?= !empty($pesanan_list) ? date('H:i', strtotime($pesanan_list[0]['created_at'])) : '-' ?></span>
            </div>
            <div class="item">
                <span>Status</span>
                <span><?= count($pesanan_list) > 0 ? 'Aktif' : 'Belum ada' ?></span>
            </div>
        </div>
    </div>
</div>

<!-- SECTION -->
<div class="section-head">
    <h2>Daftar Pesanan</h2>
</div>

<!-- ORDER LIST -->
<?php if (!empty($pesanan_list)) : ?>
    <div class="order-list">
        <?php foreach ($pesanan_list as $p) :
            $stage = getStage($p['status_memasak'], $p['status_bayar']);
            
            // Label status utama
            $stage_label = ['antre' => 'ANTRE', 'dimasak' => 'DIMASAK', 'selesai' => 'SIAP', 'diantar' => 'DIANTAR', 'lunas' => 'SELESAI'];
            $status_keys = ['antre', 'dimasak', 'selesai', 'diantar', 'lunas'];
            $current_status = $status_keys[$stage - 1] ?? 'antre';
        ?>
            <div class="order-card">
                <div class="order-head">
                    <div>
                        <div class="order-id">
                            Pesanan #<?= str_pad($p['id_pesanan'], 3, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="order-meta">
                            🪑 Meja <?= htmlspecialchars($p['nomor_meja'] ?? '-'); ?> 
                            • <?= date('d M, H:i', strtotime($p['created_at'])); ?>
                        </div>
                    </div>
                    <span class="order-badge <?= $current_status; ?>">
                        <?= $stage_label[$current_status] ?? strtoupper($current_status); ?>
                    </span>
                </div>

                <!-- TIMELINE -->
                <div class="timeline">
                    <div class="step <?= $stage >= 1 ? 'done' : ''; ?> <?= $stage == 1 ? 'active' : ''; ?>">
                        <div class="circle"><?= $stage > 1 ? '✓' : '1'; ?></div>
                        <div class="label">Dipesan</div>
                    </div>
                    <div class="step <?= $stage >= 2 ? 'done' : ''; ?> <?= $stage == 2 ? 'active' : ''; ?>">
                        <div class="circle"><?= $stage > 2 ? '✓' : '2'; ?></div>
                        <div class="label">Dimasak</div>
                    </div>
                    <div class="step <?= $stage >= 3 ? 'done' : ''; ?> <?= $stage == 3 ? 'active' : ''; ?>">
                        <div class="circle"><?= $stage > 3 ? '✓' : '3'; ?></div>
                        <div class="label">Siap</div>
                    </div>
                    <div class="step <?= $stage >= 4 ? 'done' : ''; ?> <?= $stage == 4 ? 'active' : ''; ?>">
                        <div class="circle"><?= $stage > 4 ? '✓' : '4'; ?></div>
                        <div class="label">Diantar</div>
                    </div>
                    <div class="step <?= $stage >= 5 ? 'done' : ''; ?> <?= $stage == 5 ? 'active' : ''; ?>">
                        <div class="circle"><?= $stage >= 5 ? '✓' : '5'; ?></div>
                        <div class="label">Selesai</div>
                    </div>
                </div>

                <!-- ITEMS -->
                <div class="order-items">
                    <div class="order-items-title">Detail Menu</div>
                    <?php foreach ($p['items'] as $it) : ?>
                        <div class="order-item-line">
                            <span class="name"><?= htmlspecialchars($it['nama_menu'] ?? 'Menu #' . $it['id_menu']); ?></span>
                            <span class="qty"><?= $it['jumlah']; ?>×</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-total">
                    <span>Total</span>
                    <span>Rp <?= number_format((float)$p['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>Belum Ada Pesanan</h3>
        <p>Anda belum membuat pesanan apapun.<br>Silakan pilih menu favorit Anda terlebih dahulu.</p>
        <a href="index.php" class="btn-explore">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Lihat Menu
        </a>
    </div>
<?php endif; ?>

<!-- BOTTOM NAV -->
<div class="bottom-nav">
    <a href="index.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Home
    </a>
    <a href="keranjang.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        Keranjang
    </a>
    <a href="status.php" class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        Status
    </a>
    <a href="profil.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        Profil
    </a>
</div>

<script>
    // Auto-refresh tiap 15 detik
    setInterval(() => {
        console.log('🔄 Auto-refresh status...');
        window.location.reload();
    }, 15000);

    // Manual refresh dengan animasi
    function refreshPage() {
        const btn = document.getElementById('refreshBtn');
        btn.classList.add('spinning');
        setTimeout(() => window.location.reload(), 300);
    }
</script>

</body>
</html>