<?php
session_start();
include "../config.php";

// =========================
// AMBIL ID PESANAN
// =========================
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// =========================
// AMBIL DATA PESANAN + JOIN MEJA
// =========================
$stmt = mysqli_prepare($db, "
    SELECT p.*, m.nomor_meja 
    FROM pesanan p 
    LEFT JOIN meja m ON p.id_meja = m.id_meja 
    WHERE p.id_pesanan = ? 
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    die('Pesanan tidak ditemukan.');
}

// =========================
// AMBIL DETAIL PESANAN
// =========================
$details = [];
$stmt2 = mysqli_prepare($db, "
    SELECT d.*, mn.nama_menu 
    FROM detail_pesanan d 
    LEFT JOIN menu mn ON d.id_menu = mn.id_menu 
    WHERE d.id_pesanan = ? 
    ORDER BY d.id_detail ASC
");
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($res2)) {
    $details[] = $row;
}
mysqli_stmt_close($stmt2);

// =========================
// AMBIL DATA DARI SESSION
// =========================
$metode_bayar = $_SESSION['metode_bayar'] ?? 'Cash';
$catatan      = $_SESSION['catatan']      ?? '';

// =========================
// HITUNG ULANG SUBTOTAL, PAJAK, SERVICE
// Formula: grand_total = subtotal + (subtotal * 0.10) + 2000
// Maka: subtotal = (grand_total - 2000) / 1.10
// =========================
$grand_total  = floatval($pesanan['total_harga']);
$service      = 2000;
$subtotal_raw = round(($grand_total - $service) / 1.10);
$tax_calc     = round($subtotal_raw * 0.10);
$grand_calc   = $subtotal_raw + $tax_calc + $service;

// Label status yang ramah
$status_label = [
    'belum_bayar' => 'Bayar di Kasir',
    'lunas'       => 'Lunas'
];
$status_bayar = $pesanan['status_bayar'] ?? 'belum_bayar';
$label_status = $status_label[$status_bayar] ?? ucfirst(str_replace('_', ' ', $status_bayar));

$tanggal_formatted = date('d M Y, H:i', strtotime($pesanan['created_at']));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2b1d0e">
    <title>Struk - #<?= str_pad($pesanan['id_pesanan'], 5, '0', STR_PAD_LEFT); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

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
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            background-color: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body {
            padding: 20px 16px 60px 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .success-banner {
            width: 100%;
            max-width: 480px;
            background: linear-gradient(135deg, var(--success), #40916c);
            color: white;
            border-radius: 20px;
            padding: 20px 22px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 12px 32px rgba(45, 106, 79, 0.3);
            position: relative;
            overflow: hidden;
        }

        .success-banner::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .success-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 22px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .success-text {
            position: relative;
            z-index: 1;
        }

        .success-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .success-text p {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 500;
        }

        .receipt {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(61, 40, 23, 0.16);
            position: relative;
        }

        .receipt::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 12px;
            background-image: 
                linear-gradient(45deg, var(--bg) 25%, transparent 25%),
                linear-gradient(-45deg, var(--bg) 25%, transparent 25%);
            background-size: 20px 20px;
            background-position: 0 0;
            z-index: 3;
        }

        .receipt::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 12px;
            background-image: 
                linear-gradient(135deg, var(--bg) 25%, transparent 25%),
                linear-gradient(-135deg, var(--bg) 25%, transparent 25%);
            background-size: 20px 20px;
            background-position: 0 0;
            transform: rotate(180deg);
            z-index: 3;
        }

        .receipt-inner {
            padding: 30px 24px;
        }

        .brand {
            text-align: center;
            padding-bottom: 18px;
            border-bottom: 1.5px dashed var(--border);
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 12px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(61, 40, 23, 0.3), inset 0 0 0 2px rgba(201, 169, 97, 0.3);
        }

        .brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: 1.2px;
            margin-bottom: 4px;
        }

        .brand .tagline {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2.5px;
            color: var(--accent);
            text-transform: uppercase;
        }

        .brand .address {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 8px;
            font-weight: 500;
            line-height: 1.5;
        }

        .meta {
            padding: 16px 0;
            border-bottom: 1.5px dashed var(--border);
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 12px;
        }

        .meta-row .label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .meta-row .value {
            color: var(--primary);
            font-weight: 700;
        }

        .meta-row .kode {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
            background: var(--cream-warm);
            padding: 4px 10px;
            border-radius: 6px;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.5px;
            border: 1px solid var(--border);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            font-size: 10.5px;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-badge.lunas {
            background: linear-gradient(135deg, #d1fae5, #6ee7b7);
            color: #065f46;
        }

        .status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

        .items-head {
            display: flex;
            justify-content: space-between;
            padding: 14px 0 8px 0;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .items {
            padding-bottom: 14px;
            border-bottom: 1.5px dashed var(--border);
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 0;
            gap: 10px;
        }

        .item-left {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.35;
            margin-bottom: 3px;
        }

        .item-detail {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .item-total {
            font-size: 13px;
            font-weight: 800;
            color: var(--primary);
            white-space: nowrap;
            text-align: right;
        }

        .totals {
            padding: 14px 0;
            border-bottom: 1.5px dashed var(--border);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 12.5px;
        }

        .total-row .label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .total-row .value {
            color: var(--text);
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        .total-row.grand {
            margin-top: 8px;
            padding-top: 12px;
            border-top: 2px solid var(--primary);
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 900;
            color: var(--primary);
        }

        .total-row.grand .label {
            color: var(--primary);
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .total-row.grand .value {
            color: var(--primary);
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 900;
        }

        .payment {
            padding: 14px 0;
            border-bottom: 1.5px dashed var(--border);
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12.5px;
        }

        .payment-row .label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .payment-row .method {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--accent-soft);
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .catatan {
            padding: 14px 0;
            border-bottom: 1.5px dashed var(--border);
        }

        .catatan .label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .catatan .text {
            font-size: 12.5px;
            color: var(--text);
            font-weight: 500;
            line-height: 1.5;
            background: var(--cream-warm);
            padding: 10px 12px;
            border-radius: 10px;
            border-left: 3px solid var(--accent);
        }

        .footer {
            padding: 18px 0 0 0;
            text-align: center;
        }

        .footer .thanks {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .footer .msg {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .footer .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0;
        }

        .footer .divider::before,
        .footer .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .footer .divider span {
            font-size: 14px;
            color: var(--accent);
        }

        .footer .small {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .actions {
            width: 100%;
            max-width: 480px;
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            font-family: inherit;
            letter-spacing: 0.3px;
            border: none;
            text-decoration: none;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--accent-soft);
            box-shadow: 0 8px 20px rgba(61, 40, 23, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(61, 40, 23, 0.4);
        }

        .btn-home {
            background: var(--card-bg);
            color: var(--primary);
            border: 1.5px solid var(--border);
        }

        .btn-home:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .success-banner,
            .actions {
                display: none !important;
            }
            .receipt {
                box-shadow: none;
                max-width: 100%;
                border-radius: 0;
            }
            .receipt::before,
            .receipt::after {
                display: none;
            }
            .receipt-inner {
                padding: 20px 16px;
            }
        }

        @media (max-width: 480px) {
            body { padding: 14px 12px 40px 12px; }
            .receipt-inner { padding: 26px 20px; }
            .brand h1 { font-size: 20px; }
            .total-row.grand { font-size: 16px; }
            .total-row.grand .value { font-size: 18px; }
        }
    </style>
</head>
<body>

<div class="success-banner">
    <div class="success-icon">✓</div>
    <div class="success-text">
        <h2>Pesanan Berhasil!</h2>
        <p>Pesanan Anda sedang kami siapkan 🍽️</p>
    </div>
</div>

<div class="receipt">
    <div class="receipt-inner">

        <div class="brand">
            <div class="brand-logo">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h1>RM. TAMBAKSARI</h1>
            <div class="tagline">RASA AUTENTIK SEJAK 1990</div>
            <div class="address">
                Jl. Tambaksari No. 88, Surabaya<br>
                Telp: (031) 555-1990
            </div>
        </div>

        <div class="meta">
            <div class="meta-row">
                <span class="label">No. Pesanan</span>
                <span class="kode">#<?= str_pad($pesanan['id_pesanan'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="meta-row">
                <span class="label">Tanggal</span>
                <span class="value"><?= $tanggal_formatted; ?> WIB</span>
            </div>
            <div class="meta-row">
                <span class="label">Meja</span>
                <span class="value"><?= htmlspecialchars($pesanan['nomor_meja'] ?? $pesanan['id_meja']); ?></span>
            </div>
            <div class="meta-row" style="margin-top:6px;">
                <span class="label">Status Bayar</span>
                <span class="status-badge <?= $status_bayar === 'lunas' ? 'lunas' : ''; ?>">
                    <span class="dot"></span>
                    <?= strtoupper($label_status); ?>
                </span>
            </div>
        </div>

        <div class="items-head">
            <span>Pesanan</span>
            <span>Total</span>
        </div>

        <div class="items">
            <?php foreach ($details as $d) : ?>
                <div class="item">
                    <div class="item-left">
                        <div class="item-name"><?= htmlspecialchars($d['nama_menu'] ?? 'Menu #' . $d['id_menu']); ?></div>
                        <div class="item-detail">
                            <?= $d['jumlah']; ?> × Rp <?= number_format($d['harga_satuan'], 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="item-total">
                        Rp <?= number_format($d['subtotal'], 0, ',', '.'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="totals">
            <div class="total-row">
                <span class="label">Subtotal</span>
                <span class="value">Rp <?= number_format($subtotal_raw, 0, ',', '.'); ?></span>
            </div>
            <div class="total-row">
                <span class="label">PPN 10%</span>
                <span class="value">Rp <?= number_format($tax_calc, 0, ',', '.'); ?></span>
            </div>
            <div class="total-row">
                <span class="label">Biaya Layanan</span>
                <span class="value">Rp <?= number_format($service, 0, ',', '.'); ?></span>
            </div>
            <div class="total-row grand">
                <span class="label">TOTAL</span>
                <span class="value">Rp <?= number_format($grand_total, 0, ',', '.'); ?></span>
            </div>
        </div>

        <div class="payment">
            <div class="payment-row">
                <span class="label">Metode Pembayaran</span>
                <span class="method">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                        <circle cx="12" cy="12" r="2"></circle>
                    </svg>
                    <?= htmlspecialchars($metode_bayar); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($catatan)) : ?>
        <div class="catatan">
            <div class="label">Catatan</div>
            <div class="text"><?= nl2br(htmlspecialchars($catatan)); ?></div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div class="thanks">Terima Kasih! 🙏</div>
            <div class="msg">
                Pesanan Anda akan segera kami siapkan.<br>
                Silakan tunjukkan struk ini ke pelayan.
            </div>
            <div class="divider"><span>✦</span></div>
            <div class="small">STRUK INI BUKTI PEMBAYARAN YANG SAH</div>
        </div>

    </div>
</div>

<div class="actions">
    <button class="btn btn-print" onclick="window.print()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Cetak Struk
    </button>
    <a href="index.php" class="btn btn-home">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        </svg>
        Ke Menu
    </a>
</div>

</body>
</html>