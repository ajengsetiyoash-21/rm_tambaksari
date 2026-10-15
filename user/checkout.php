<?php
session_start();
include "../config.php";

// CEK KERANJANG KOSONG
if (empty($_SESSION['keranjang'])) {
    header('Location: keranjang.php');
    exit;
}

// AMBIL DATA MEJA
$id_meja = $_SESSION['id_meja'] ?? 1;
$id_user = $_SESSION['id_user'] ?? null;

// AMBIL DATA MENU
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

// PROSES CHECKOUT
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_bayar = trim($_POST['metode_bayar'] ?? 'Cash');
    $catatan = trim($_POST['catatan'] ?? '');

    $stmt = mysqli_prepare($db, "
        INSERT INTO pesanan
        (id_user, id_meja, total_harga, status_bayar, metode_bayar, catatan, created_at)
        VALUES (?, ?, ?, 'belum_bayar', ?, ?, NOW())
    ");

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            'iidss',
            $id_user,
            $id_meja,
            $grand_total,
            $metode_bayar,
            $catatan
        );

        if (mysqli_stmt_execute($stmt)) {
            $id_pesanan = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            $stmt2 = mysqli_prepare($db, "
                INSERT INTO detail_pesanan
                (id_pesanan, id_menu, jumlah, harga_satuan, subtotal, status_memasak)
                VALUES (?, ?, ?, ?, ?, 'antre')
            ");

            if ($stmt2) {
                foreach ($items as $it) {
                    mysqli_stmt_bind_param(
                        $stmt2,
                        'iiiii',
                        $id_pesanan,
                        $it['id_menu'],
                        $it['qty'],
                        $it['harga'],
                        $it['subtotal']
                    );

                    mysqli_stmt_execute($stmt2);
                }

                mysqli_stmt_close($stmt2);
            }

            $_SESSION['last_order_id'] = $id_pesanan;
            $_SESSION['keranjang'] = [];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            font-family: 'Plus Jakarta Sans', sans-serif;
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
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            background-color: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body {
            padding-bottom: 150px;
            min-height: 100vh;
        }

        .batik-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: .10;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120'><circle cx='20' cy='20' r='5' fill='%23f0d9a0'/><circle cx='90' cy='70' r='5' fill='%23f0d9a0'/><circle cx='70' cy='110' r='5' fill='%23f0d9a0'/></svg>");
            background-size: 120px 120px;
        }

        .header {
            position: relative;
            background: linear-gradient(150deg, var(--primary-dark), var(--primary), var(--primary-light));
            padding: 20px 22px 60px;
            color: var(--cream);
            overflow: hidden;
            border-radius: 0 0 32px 32px;
            box-shadow: 0 12px 32px rgba(36,22,9,.28);
        }

        .header-inner {
            position: relative;
            z-index: 2;
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
        }

        .header-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            letter-spacing: .8px;
        }

        .header-text small {
            display: block;
            font-size: 9px;
            letter-spacing: 2px;
            color: var(--accent-soft);
            margin-top: 4px;
        }

        .cart-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(250,246,238,.10);
            border: 1px solid rgba(201,169,97,.35);
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-soft);
        }

        .cart-badge .num {
            background: var(--accent);
            color: var(--primary-dark);
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .summary-wrap {
            padding: 0 22px;
            margin-top: -42px;
            position: relative;
            z-index: 5;
        }

        .summary-card {
            background: linear-gradient(135deg, #fffdf8, var(--cream-warm));
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 20px 40px rgba(61,40,23,.16);
            border: 1px solid rgba(201,169,97,.3);
        }

        .summary-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .summary-total {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 900;
            color: var(--primary);
        }

        .summary-total .cur {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
        }

        .summary-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 15px;
            padding-top: 13px;
            border-top: 1px solid var(--border);
        }

        .summary-meta .item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 12px;
        }

        .summary-meta .item span:first-child {
            color: var(--text-muted);
        }

        .summary-meta .item span:last-child {
            font-weight: 800;
        }

        .section-head {
            padding: 0 22px;
            margin: 28px 0 13px;
        }

        .section-head h2 {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }

        .order-list {
            padding: 0 22px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            border-radius: 15px;
            padding: 14px;
            margin-bottom: 10px;
            border: 1px solid var(--border);
        }

        .order-item .qty-badge {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            background: var(--cream-warm);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
            flex-shrink: 0;
        }

        .order-item .info {
            flex: 1;
            min-width: 0;
        }

        .order-item .info h4 {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .order-item .info span {
            font-size: 11px;
            color: var(--text-muted);
        }

        .order-item .subtotal {
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .form-wrap {
            padding: 0 22px;
        }

        .form-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .form-textarea {
            width: 100%;
            min-height: 90px;
            resize: vertical;
            padding: 13px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            font-size: 13px;
            background: #fffdf8;
        }

        .form-textarea:focus {
            border-color: var(--accent);
        }

        .form-textarea::placeholder {
            color: #a99a80;
        }

        .pay-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .pay-option {
            cursor: pointer;
        }

        .pay-option input {
            position: absolute;
            opacity: 0;
        }

        .pay-option .pay-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 14px;
            font-size: 13px;
            font-weight: 800;
            color: var(--text-muted);
            background: #fffdf8;
        }

        .pay-option .pay-box svg {
            width: 22px;
            height: 22px;
        }

        .pay-option input:checked + .pay-box {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--accent-glow);
        }

        .error-box {
            margin: 20px 22px 0;
            padding: 13px;
            border-radius: 12px;
            color: var(--danger);
            background: #fadbd8;
            font-size: 12px;
        }

        .cost-list {
            padding: 0 22px;
        }

        .cost-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
        }

        .cost-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 13px;
            font-size: 12px;
        }

        .cost-row.total {
            border-top: 1px dashed var(--border);
            padding-top: 15px;
            margin-bottom: 0;
            font-weight: 800;
        }

        .cost-row.total .val {
            color: var(--primary);
            font-size: 15px;
        }

        .checkout-bar {
            position: fixed;
            bottom: 68px;
            left: 0;
            right: 0;
            z-index: 10;
            background: rgba(255,253,248,.96);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border);
            padding: 13px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .checkout-bar .info .label {
            color: var(--text-muted);
            font-size: 10px;
            margin-bottom: 4px;
        }

        .checkout-bar .info .amount {
            font-size: 17px;
            font-weight: 900;
            color: var(--primary);
        }

        .checkout-bar .info .amount .cur {
            font-size: 11px;
        }

        .btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--accent-glow);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-submit:disabled {
            cursor: wait;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 11;
            display: flex;
            justify-content: space-around;
            background: var(--primary-dark);
            padding: 10px 5px;
        }

        .bottom-nav .batik-overlay {
            opacity: .08;
        }

        .nav-item {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: var(--accent-soft);
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
        }

        .nav-item svg {
            width: 21px;
            height: 21px;
            stroke-width: 1.8;
        }

        .nav-item.active,
        .nav-item:hover {
            color: white;
        }

        /* TAMPILAN BARCODE QRIS */
        .qris-container {
            margin-top: 16px;
        }

        .qris-card {
            text-align: center;
            background: #fffdf8;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px 15px;
        }

        .qris-card h3 {
            color: var(--primary);
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .qris-card p {
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .qris-image {
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px;
            margin: 0 auto 14px;
            max-width: 280px;
        }

        .qris-image img {
            width: 100%;
            max-width: 240px;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .qris-name {
            color: var(--primary);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .qris-card small {
            color: var(--text-muted);
            font-size: 10px;
            line-height: 1.5;
        }

        @media (min-width: 900px) {
            .header,
            .summary-wrap,
            .section-head,
            .order-list,
            .form-wrap,
            .cost-list {
                padding-left: 32px;
                padding-right: 32px;
            }

            .error-box {
                margin-left: 32px;
                margin-right: 32px;
            }
        }
    </style>
</head>

<body>

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

<!-- RINGKASAN TOTAL -->
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
            <div class="qty-badge">
                <?= $it['qty']; ?>×
            </div>

            <div class="info">
                <h4><?= htmlspecialchars($it['nama_menu']); ?></h4>
                <span>
                    Rp <?= number_format($it['harga'], 0, ',', '.'); ?>
                </span>
            </div>

            <div class="subtotal">
                Rp <?= number_format($it['subtotal'], 0, ',', '.'); ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- FORM CHECKOUT -->
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
                        <input
                            type="radio"
                            name="metode_bayar"
                            value="Cash"
                            checked
                        >

                        <div class="pay-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                <circle cx="12" cy="12" r="2"></circle>
                            </svg>
                            Cash
                        </div>
                    </label>

                    <label class="pay-option">
                        <input
                            type="radio"
                            name="metode_bayar"
                            value="QRIS"
                        >

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
                </div>

                <!-- BARCODE QRIS -->
                <div
                    class="qris-container"
                    id="qrisContainer"
                    style="display: none;"
                >
                    <div class="qris-card">
                        <h3>Pembayaran QRIS</h3>

                        <p>
                            Scan barcode di bawah menggunakan aplikasi
                            pembayaran kamu.
                        </p>

                        <div class="qris-image">
                            <img
                                src="qris-restoran.png"
                                alt="Barcode QRIS RM. Tambaksari"
                            >
                        </div>

                        <div class="qris-name">
                            RM. TAMBAKSARI
                        </div>

                        <small>
                            Pastikan nama penerima sesuai sebelum membayar.
                        </small>
                    </div>
                </div>
            </div>

            <!-- CATATAN PESANAN -->
            <div class="form-group">
                <label class="form-label" for="catatan">
                    Catatan (Opsional)
                </label>

                <textarea
                    class="form-textarea"
                    id="catatan"
                    name="catatan"
                    placeholder="Contoh: tidak pedas, tanpa bawang..."
                ><?= htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
            </div>

        </div>
    </div>

    <!-- RINCIAN BIAYA -->
    <div class="section-head">
        <h2>Rincian Biaya</h2>
    </div>

    <div class="cost-list">
        <div class="cost-card">

            <div class="cost-row">
                <span>Subtotal (<?= $total_qty; ?> porsi)</span>
                <span>
                    Rp <?= number_format($total, 0, ',', '.'); ?>
                </span>
            </div>

            <div class="cost-row">
                <span>PPN 10%</span>
                <span>
                    Rp <?= number_format($tax, 0, ',', '.'); ?>
                </span>
            </div>

            <div class="cost-row">
                <span>Biaya Layanan</span>
                <span>
                    Rp <?= number_format($service, 0, ',', '.'); ?>
                </span>
            </div>

            <div class="cost-row total">
                <span>Total Bayar</span>
                <span class="val">
                    Rp <?= number_format($grand_total, 0, ',', '.'); ?>
                </span>
            </div>

        </div>
    </div>
</form>

<!-- TOMBOL BUAT PESANAN -->
<div class="checkout-bar">
    <div class="info">
        <div class="label">Total Bayar</div>

        <div class="amount">
            <span class="cur">Rp</span>
            <?= number_format($grand_total, 0, ',', '.'); ?>
        </div>
    </div>

    <button
        type="submit"
        form="checkoutForm"
        class="btn-submit"
        id="btnSubmit"
    >
        <span>Buat Pesanan</span>

        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </button>
</div>

<!-- NAVIGASI BAWAH -->
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
    const checkoutForm = document.getElementById('checkoutForm');
    const btn = document.getElementById('btnSubmit');
    const qrisContainer = document.getElementById('qrisContainer');

    // Tampilkan barcode hanya saat QRIS dipilih
    function toggleQRIS() {
        const selected = document.querySelector(
            'input[name="metode_bayar"]:checked'
        );

        qrisContainer.style.display =
            selected && selected.value === 'QRIS'
                ? 'block'
                : 'none';
    }

    document.querySelectorAll('input[name="metode_bayar"]').forEach(
        function(input) {
            input.addEventListener('change', toggleQRIS);
        }
    );

    // Ubah tombol ketika formulir dikirim
    checkoutForm.addEventListener('submit', function() {
        btn.querySelector('span').textContent = 'Memproses...';
        btn.style.opacity = '0.7';
        btn.disabled = true;
    });

    // Atur tampilan saat halaman pertama dibuka
    toggleQRIS();
</script>

</body>
</html>