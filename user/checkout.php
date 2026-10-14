<?php
session_start();
include "../config.php";

// =========================
// CEK KERANJANG KOSONG
// =========================
if (empty($_SESSION['keranjang'])) {
    header('Location: keranjang.php');
    exit;
}

// =========================
// AMBIL DATA MEJA (dari session atau default)
// =========================
$id_meja = $_SESSION['id_meja'] ?? 1;
$id_user = $_SESSION['id_user'] ?? null;

// =========================
// AMBIL DATA MENU DARI KERANJANG
// =========================
$items = [];
$total = 0;
$total_qty = 0;

$ids = array_keys($_SESSION['keranjang']);
$ids_str = implode(',', array_map('intval', $ids));

$q = mysqli_query($db, "
    SELECT menu.*, kategori.nama_kategori 
    FROM menu 
    LEFT JOIN kategori ON menu.id_kategori = kategori.id_kategori 
    WHERE menu.id_menu IN ($ids_str)
");

if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $qty = $_SESSION['keranjang'][$row['id_menu']];
        $subtotal = $row['harga'] * $qty;
        $total += $subtotal;
        $total_qty += $qty;
        $row['qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $items[] = $row;
    }
}

$tax = round($total * 0.10);
$service = $total > 0 ? 2000 : 0;
$grand_total = $total + $tax + $service;

// =========================
// PROSES CHECKOUT (POST)
// =========================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_bayar = trim($_POST['metode_bayar'] ?? 'Cash');
    $catatan      = trim($_POST['catatan'] ?? '');

    // =========================
    // INSERT KE TABEL pesanan (dengan metode_bayar & catatan)
    // =========================
    $stmt = mysqli_prepare($db, "
        INSERT INTO pesanan 
        (id_user, id_meja, total_harga, status_bayar, metode_bayar, catatan, created_at) 
        VALUES (?, ?, ?, 'belum_bayar', ?, ?, NOW())
    ");

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt, 'iidss',
            $id_user, $id_meja, $grand_total, $metode_bayar, $catatan
        );

        if (mysqli_stmt_execute($stmt)) {
            $id_pesanan = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            // =========================
            // INSERT DETAIL KE detail_pesanan
            // =========================
            $stmt2 = mysqli_prepare($db, "
                INSERT INTO detail_pesanan 
                (id_pesanan, id_menu, jumlah, harga_satuan, subtotal, status_memasak) 
                VALUES (?, ?, ?, ?, ?, 'antre')
            ");

            if ($stmt2) {
                foreach ($items as $it) {
                    mysqli_stmt_bind_param(
                        $stmt2, 'iiiii',
                        $id_pesanan, $it['id_menu'], $it['qty'],
                        $it['harga'], $it['subtotal']
                    );
                    mysqli_stmt_execute($stmt2);
                }
                mysqli_stmt_close($stmt2);
            }

            // Simpan id pesanan ke session
            $_SESSION['last_order_id'] = $id_pesanan;

            // Kosongkan keranjang
            $_SESSION['keranjang'] = [];

            // Redirect ke struk
            header('Location: struk.php?id=' . $id_pesanan);
            exit;
        } else {
            $error = 'Gagal menyimpan pesanan: ' . mysqli_error($db);
        }
    } else {
        $error = 'Gagal menyiapkan query: ' . mysqli_error($db);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2b1d0e">
    <title>Checkout - RM. Tambaksari</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">

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
            --danger: #c0392b;
            --danger-soft: #fadbd8;
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            background-color: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body {
            padding-bottom: 200px;
            min-height: 100vh;
        }

        .batik-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.10;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'><defs><g id='f'><circle cx='0' cy='0' r='2.5' fill='%23f0d9a0'/><ellipse cx='0' cy='-6' rx='3' ry='5.5' fill='%23f0d9a0'/><ellipse cx='0' cy='6' rx='3' ry='5.5' fill='%23f0d9a0'/><ellipse cx='-6' cy='0' rx='5.5' ry='3' fill='%23f0d9a0'/><ellipse cx='6' cy='0' rx='5.5' ry='3' fill='%23f0d9a0'/></g><g id='l'><path d='M 0 0 Q 5 -5 10 0 Q 5 5 0 0 Z' fill='%23f0d9a0'/></g></defs><use href='%23f' x='20' y='20'/><use href='%23l' x='60' y='30' transform='rotate(30 65 30)'/><use href='%23f' x='90' y='70'/><use href='%23l' x='30' y='90' transform='rotate(-40 35 90)'/><use href='%23f' x='70' y='110'/><use href='%23l' x='100' y='20' transform='rotate(60 105 20)'/></svg>");
            background-size: 120px 120px;
        }

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

        .cart-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(250, 246, 238, 0.10);
            border: 1px solid rgba(201, 169, 97, 0.35);
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-soft);
            backdrop-filter: blur(8px);
            white-space: nowrap;
        }

        .cart-badge .num {
            background: linear-gradient(135deg, var(--accent-glow), var(--accent));
            color: var(--primary-dark);
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10.5px;
            font-weight: 800;
            padding: 0 5px;
        }

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
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(201, 169, 97, 0.3);
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(201, 169, 97, 0.18) 0%, transparent 70%);
            border-radius: 50%;
        }

        .summary-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .summary-total {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: baseline;
            gap: 4px;
            position: relative;
            z-index: 1;
        }

        .summary-total .cur {
            font-size: 14px;
            font-weight: 700;
            opacity: 0.7;
        }

        .summary-meta {
            display: flex;
            gap: 16px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed rgba(201, 169, 97, 0.4);
            position: relative;
            z-index: 1;
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
            font-size: 13.5px;
            font-weight: 700;
            color: var(--primary);
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 22px 22px 4px 22px;
        }

        .section-head h2 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: var(--primary);
            font-weight: 800;
        }

        .order-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 12px 22px 10px 22px;
        }

        .order-item {
            background: var(--card-bg);
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            box-shadow: 0 2px 8px rgba(61, 40, 23, 0.06);
            border: 1px solid rgba(230, 212, 176, 0.7);
        }

        .order-item .qty-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--accent-soft);
            min-width: 30px;
            height: 30px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12.5px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .order-item .info {
            flex: 1;
            min-width: 0;
        }

        .order-item .info h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .order-item .info span {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .order-item .subtotal {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--primary);
            white-space: nowrap;
        }

        .form-wrap {
            padding: 0 22px;
        }

        .form-card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(61, 40, 23, 0.06);
            border: 1px solid rgba(230, 212, 176, 0.7);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group:last-child { margin-bottom: 0; }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 500;
            font-family: inherit;
            color: var(--text);
            background: #fff;
            transition: all 0.25s;
            outline: none;
            resize: vertical;
            min-height: 70px;
        }

        .form-textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.15);
        }

        .form-textarea::placeholder {
            color: #c4b396;
            font-weight: 400;
        }

        .pay-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .pay-option {
            position: relative;
            cursor: pointer;
        }

        .pay-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .pay-option .pay-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: #fff;
            transition: all 0.25s;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .pay-option .pay-box svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .pay-option input:checked + .pay-box {
            border-color: var(--accent);
            background: linear-gradient(135deg, #fffdf8, var(--cream-warm));
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(201, 169, 97, 0.2);
        }

        .error-box {
            margin: 0 22px 16px 22px;
            background: var(--danger-soft);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1px solid rgba(192, 57, 43, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cost-list {
            padding: 0 22px;
            margin-top: 20px;
        }

        .cost-card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(61, 40, 23, 0.06);
            border: 1px solid rgba(230, 212, 176, 0.7);
        }

        .cost-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .cost-row.total {
            border-top: 1.5px dashed var(--border);
            margin-top: 8px;
            padding-top: 14px;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 800;
        }

        .cost-row.total .val {
            color: var(--primary);
        }

        .checkout-bar {
            position: fixed;
            bottom: 90px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 28px);
            max-width: 520px;
            background: linear-gradient(135deg, #fffdf8, var(--cream-warm));
            border-radius: 18px;
            padding: 12px 14px;
            box-shadow: 0 12px 40px rgba(36, 22, 9, 0.22), inset 0 0 0 1px rgba(201, 169, 97, 0.4);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 90;
            border: 1px solid rgba(201, 169, 97, 0.35);
        }

        .checkout-bar .info {
            flex: 1;
            min-width: 0;
        }

        .checkout-bar .info .label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .checkout-bar .info .amount {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: baseline;
            gap: 3px;
            margin-top: 2px;
        }

        .checkout-bar .info .amount .cur {
            font-size: 11.5px;
            font-weight: 700;
            opacity: 0.7;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--accent-soft);
            border: none;
            padding: 13px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            letter-spacing: 0.4px;
            box-shadow: 0 6px 18px rgba(61, 40, 23, 0.35);
            transition: all 0.3s;
            font-family: inherit;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(201, 169, 97, 0.35), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover::after { left: 100%; }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(61, 40, 23, 0.45);
        }

        .btn-submit:active { transform: scale(0.97); }

        .btn-submit svg, .btn-submit span { position: relative; z-index: 1; }

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
            overflow: hidden;
        }

        .bottom-nav .batik-overlay { opacity: 0.08; }

        .nav-item {
            color: rgba(250, 246, 238, 0.5);
            text-decoration: none;
            font-size: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 700;
            letter-spacing: 0.4px;
            position: relative;
            padding: 6px 16px;
            border-radius: 14px;
            z-index: 1;
        }

        .nav-item svg {
            width: 21px;
            height: 21px;
            stroke-width: 2;
            transition: transform 0.3s;
        }

        .nav-item.active,
        .nav-item:hover {
            color: var(--accent-soft);
            background: rgba(201, 169, 97, 0.08);
        }

        .nav-item:hover svg,
        .nav-item.active svg {
            transform: translateY(-3px) scale(1.1);
        }

        @media (min-width: 900px) {
            .header, .summary-wrap, .section-head, .order-list, .form-wrap, .cost-list, .error-box {
                padding-left: 32px;
                padding-right: 32px;
            }
            .error-box { margin-left: 32px; margin-right: 32px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="batik-overlay"></div>
    <div class="header-inner">
        <div class="header-title">
            <div class="header-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            <div class="header-text">
                <h1>Checkout</h1>
                <small>KONFIRMASI PESANAN</small>
            </div>
        </div>
        <div class="cart-badge">
            <span class="num"><?= $total_qty; ?></span>
            item
        </div>
    </div>
</header>

<!-- SUMMARY -->
<div class="summary-wrap">
    <div class="summary-card">
        <div class="summary-label">Total Belanja</div>
        <div class="summary-total">
            <span class="cur">Rp</span>
            <?= number_format($grand_total, 0, ',', '.'); ?>
        </div>
        <div class="summary-meta">
            <div class="item">
                <span>Jumlah</span>
                <span><?= $total_qty; ?> porsi</span>
            </div>
            <div class="item">
                <span>Menu</span>
                <span><?= count($items); ?> jenis</span>
            </div>
        </div>
    </div>
</div>

<?php if ($error) : ?>
<div class="error-box">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <?= htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- RINCIAN PESANAN -->
<div class="section-head">
    <h2>Rincian Pesanan</h2>
</div>

<div class="order-list">
    <?php foreach ($items as $it) : ?>
        <div class="order-item">
            <div class="qty-badge"><?= $it['qty']; ?>×</div>
            <div class="info">
                <h4><?= htmlspecialchars($it['nama_menu']); ?></h4>
                <span>Rp <?= number_format($it['harga'], 0, ',', '.'); ?></span>
            </div>
            <div class="subtotal">Rp <?= number_format($it['subtotal'], 0, ',', '.'); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- FORM -->
<div class="section-head">
    <h2>Konfirmasi</h2>
</div>

<form method="POST" id="checkoutForm">
    <div class="form-wrap">
        <div class="form-card">
            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <div class="pay-options">
                    <label class="pay-option">
                        <input type="radio" name="metode_bayar" value="Cash" checked>
                        <div class="pay-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                <circle cx="12" cy="12" r="2"></circle>
                            </svg>
                            Cash
                        </div>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode_bayar" value="QRIS">
                        <div class="pay-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                <path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"></path>
                            </svg>
                            QRIS
                        </div>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode_bayar" value="Debit">
                        <div class="pay-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Debit
                        </div>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode_bayar" value="E-Wallet">
                        <div class="pay-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="6" width="20" height="13" rx="3"></rect>
                                <circle cx="17" cy="12.5" r="1.5"></circle>
                            </svg>
                            E-Wallet
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="catatan">Catatan (Opsional)</label>
                <textarea class="form-textarea" id="catatan" name="catatan" 
                          placeholder="Contoh: tidak pedas, tanpa bawang..."><?= htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <!-- BIAYA -->
    <div class="section-head">
        <h2>Rincian Biaya</h2>
    </div>

    <div class="cost-list">
        <div class="cost-card">
            <div class="cost-row">
                <span>Subtotal (<?= $total_qty; ?> porsi)</span>
                <span>Rp <?= number_format($total, 0, ',', '.'); ?></span>
            </div>
            <div class="cost-row">
                <span>PPN 10%</span>
                <span>Rp <?= number_format($tax, 0, ',', '.'); ?></span>
            </div>
            <div class="cost-row">
                <span>Biaya Layanan</span>
                <span>Rp <?= number_format($service, 0, ',', '.'); ?></span>
            </div>
            <div class="cost-row total">
                <span>Total Bayar</span>
                <span class="val">Rp <?= number_format($grand_total, 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>
</form>

<!-- BAR -->
<div class="checkout-bar">
    <div class="info">
        <div class="label">Total Bayar</div>
        <div class="amount">
            <span class="cur">Rp</span>
            <?= number_format($grand_total, 0, ',', '.'); ?>
        </div>
    </div>
    <button type="submit" form="checkoutForm" class="btn-submit" id="btnSubmit">
        <span>Buat Pesanan</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </button>
</div>

<!-- NAV -->
<div class="bottom-nav">
    <div class="batik-overlay"></div>
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
    <a href="status.php" class="nav-item">
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
    document.getElementById('checkoutForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');
        btn.querySelector('span').textContent = 'Memproses...';
        btn.style.opacity = '0.7';
        btn.disabled = true;
    });
</script>

</body>
</html>