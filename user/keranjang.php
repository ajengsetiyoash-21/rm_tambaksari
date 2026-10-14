<?php
session_start();
include "../config.php";

// =========================
// INISIALISASI KERANJANG
// =========================
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// =========================
// HANDLE AJAX: GET COUNT (untuk badge di index.php)
// =========================
if (isset($_GET['count'])) {
    header('Content-Type: application/json');
    echo json_encode(['total_qty' => array_sum($_SESSION['keranjang'])]);
    exit;
}

// =========================
// HANDLE ACTION (TAMBAH / KURANG / HAPUS / KOSONGKAN)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $id_menu = intval($_POST['id_menu'] ?? 0);
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

    if ($action === 'tambah' && $id_menu > 0) {
        if (isset($_SESSION['keranjang'][$id_menu])) {
            $_SESSION['keranjang'][$id_menu]++;
        } else {
            $_SESSION['keranjang'][$id_menu] = 1;
        }
    } elseif ($action === 'kurang' && $id_menu > 0) {
        if (isset($_SESSION['keranjang'][$id_menu])) {
            $_SESSION['keranjang'][$id_menu]--;
            if ($_SESSION['keranjang'][$id_menu] <= 0) {
                unset($_SESSION['keranjang'][$id_menu]);
            }
        }
    } elseif ($action === 'hapus' && $id_menu > 0) {
        unset($_SESSION['keranjang'][$id_menu]);
    } elseif ($action === 'kosongkan') {
        $_SESSION['keranjang'] = [];
    }

    // Kalau request dari AJAX → balas JSON (tanpa redirect)
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'total_qty' => array_sum($_SESSION['keranjang'])
        ]);
        exit;
    }

    // Kalau form biasa → redirect
    header('Location: keranjang.php');
    exit;
}

// =========================
// AMBIL DATA MENU DARI KERANJANG
// =========================
$items = [];
$total = 0;
$total_qty = 0;

if (!empty($_SESSION['keranjang'])) {
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
}

// Pajak & biaya layanan (opsional)
$tax = round($total * 0.10);        // PPN 10%
$service = $total > 0 ? 2000 : 0;   // Biaya layanan
$grand_total = $total + $tax + $service;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2b1d0e">
    <title>Keranjang - RM. Tambaksari</title>

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
            --shadow-sm: 0 2px 8px rgba(61, 40, 23, 0.06);
            --shadow-md: 0 8px 24px rgba(61, 40, 23, 0.10);
            --shadow-lg: 0 20px 40px rgba(61, 40, 23, 0.16);
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            color: var(--text);
            padding-bottom: 200px;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            background-color: var(--bg);
        }

        /* ============================================================
           MOTIF BATIK — subtle overlay
        ============================================================ */
        .batik-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.10;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'><defs><g id='f'><circle cx='0' cy='0' r='2.5' fill='%23f0d9a0'/><ellipse cx='0' cy='-6' rx='3' ry='5.5' fill='%23f0d9a0'/><ellipse cx='0' cy='6' rx='3' ry='5.5' fill='%23f0d9a0'/><ellipse cx='-6' cy='0' rx='5.5' ry='3' fill='%23f0d9a0'/><ellipse cx='6' cy='0' rx='5.5' ry='3' fill='%23f0d9a0'/></g><g id='l'><path d='M 0 0 Q 5 -5 10 0 Q 5 5 0 0 Z' fill='%23f0d9a0'/></g></defs><use href='%23f' x='20' y='20'/><use href='%23l' x='60' y='30' transform='rotate(30 65 30)'/><use href='%23f' x='90' y='70'/><use href='%23l' x='30' y='90' transform='rotate(-40 35 90)'/><use href='%23f' x='70' y='110'/><use href='%23l' x='100' y='20' transform='rotate(60 105 20)'/></svg>");
            background-size: 120px 120px;
        }

        /* =========================
           HEADER BANNER MELENGKUNG
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

        /* =========================
           SUMMARY CARD MENGAMBANG
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
            box-shadow: var(--shadow-lg), inset 0 0 0 1px rgba(201, 169, 97, 0.25);
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

        /* =========================
           SECTION HEAD
        ========================= */
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

        .btn-clear {
            background: transparent;
            border: none;
            color: var(--danger);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.25s;
            font-family: inherit;
        }

        .btn-clear:hover {
            background: var(--danger-soft);
        }

        /* =========================
           CART LIST
        ========================= */
        .cart-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 12px 22px 30px 22px;
        }

        .cart-item {
            background: var(--card-bg);
            border-radius: 18px;
            display: flex;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(230, 212, 176, 0.7);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) backwards;
        }

        .cart-item:hover {
            box-shadow: 0 12px 28px rgba(61, 40, 23, 0.12);
            border-color: var(--accent);
        }

        .cart-thumb {
            width: 100px;
            min-width: 100px;
            height: 100px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--cream-warm), #e8dcc2);
            flex-shrink: 0;
        }

        .cart-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-info {
            flex: 1;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .cart-info-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }

        .cart-info h3 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .btn-remove {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .btn-remove:hover {
            color: var(--danger);
            background: var(--danger-soft);
        }

        .cart-info .price-small {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 2px;
        }

        .cart-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .cart-subtotal {
            font-size: 14px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: baseline;
            gap: 2px;
        }

        .cart-subtotal .cur {
            font-size: 10px;
            font-weight: 700;
            opacity: 0.65;
        }

        /* =========================
           QTY CONTROL
        ========================= */
        .qty-control {
            display: flex;
            align-items: center;
            gap: 0;
            background: var(--cream-warm);
            border-radius: 10px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .qty-control form { display: flex; }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: transparent;
            color: var(--primary);
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-family: inherit;
            line-height: 1;
        }

        .qty-btn:hover {
            background: var(--accent-soft);
            color: var(--primary-dark);
        }

        .qty-btn:active { transform: scale(0.9); }

        .qty-value {
            min-width: 26px;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            color: var(--primary-dark);
            padding: 0 4px;
        }

        /* =========================
           EMPTY STATE
        ========================= */
        .empty-cart {
            text-align: center;
            padding: 60px 30px;
            background: var(--card-bg);
            border-radius: 22px;
            margin: 0 22px;
            box-shadow: var(--shadow-sm);
            border: 1px dashed var(--border);
        }

        .empty-cart .ic {
            width: 90px;
            height: 90px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cream-warm), #e8dcc2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: inset 0 -4px 12px rgba(61, 40, 23, 0.08);
        }

        .empty-cart h3 {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            color: var(--primary);
            margin-bottom: 6px;
            font-weight: 800;
        }

        .empty-cart p {
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
            letter-spacing: 0.3px;
            box-shadow: 0 6px 18px rgba(61, 40, 23, 0.3);
            transition: all 0.3s;
        }

        .btn-explore:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(61, 40, 23, 0.4);
        }

        /* =========================
           CHECKOUT BAR (FIXED)
        ========================= */
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

        .btn-checkout {
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

        .btn-checkout::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(201, 169, 97, 0.35), transparent);
            transition: left 0.5s;
        }

        .btn-checkout:hover::after { left: 100%; }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(61, 40, 23, 0.45);
        }

        .btn-checkout:active { transform: scale(0.97); }

        .btn-checkout svg, .btn-checkout span { position: relative; z-index: 1; }

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

        .nav-item:hover svg,
        .nav-item.active svg {
            transform: translateY(-3px) scale(1.1);
        }

        .nav-item .nav-badge {
            position: absolute;
            top: 2px;
            right: 8px;
            background: var(--accent);
            color: var(--primary-dark);
            font-size: 8.5px;
            font-weight: 800;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        /* =========================
           TOAST
        ========================= */
        .toast {
            position: fixed;
            bottom: 105px;
            left: 50%;
            transform: translateX(-50%) translateY(120px) scale(0.9);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--cream);
            padding: 13px 22px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 16px 40px rgba(36, 22, 9, 0.45), 0 0 0 1px rgba(201, 169, 97, 0.4);
            opacity: 0;
            transition: all 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 200;
            pointer-events: none;
            max-width: 90%;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(1);
        }

        .toast .check {
            background: linear-gradient(135deg, var(--accent-glow), var(--accent));
            color: var(--primary-dark);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            flex-shrink: 0;
        }

        /* =========================
           ANIMASI
        ========================= */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cart-item:nth-child(1) { animation-delay: 0.03s; }
        .cart-item:nth-child(2) { animation-delay: 0.06s; }
        .cart-item:nth-child(3) { animation-delay: 0.09s; }
        .cart-item:nth-child(4) { animation-delay: 0.12s; }
        .cart-item:nth-child(5) { animation-delay: 0.15s; }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (min-width: 600px) {
            .cart-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }
        }

        @media (min-width: 900px) {
            .cart-list { grid-template-columns: repeat(3, 1fr); }
            .header, .summary-wrap, .section-head, .cart-list, .empty-cart {
                padding-left: 32px;
                padding-right: 32px;
            }
            .empty-cart { margin: 0 32px; }
        }
    </style>
</head>
<body>

<!-- =========================
     HEADER BANNER
========================= -->
<header class="header">
    <div class="batik-overlay"></div>
    <div class="header-inner">
        <div class="header-title">
            <div class="header-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </div>
            <div class="header-text">
                <h1>Keranjang</h1>
                <small>PESANAN ANDA</small>
            </div>
        </div>
        <div class="cart-badge">
            <span class="num"><?= $total_qty; ?></span>
            item
        </div>
    </div>
</header>

<!-- =========================
     SUMMARY CARD MENGAMBANG
========================= -->
<div class="summary-wrap">
    <div class="summary-card">
        <div class="summary-label">Total Belanja</div>
        <div class="summary-total">
            <span class="cur">Rp</span>
            <?= number_format($total, 0, ',', '.'); ?>
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

<?php if (!empty($items)) : ?>

<!-- =========================
     SECTION HEAD
========================= -->
<div class="section-head">
    <h2>Rincian Pesanan</h2>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Kosongkan semua item dari keranjang?')">
        <input type="hidden" name="action" value="kosongkan">
        <button type="submit" class="btn-clear">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
            </svg>
            Kosongkan
        </button>
    </form>
</div>

<!-- =========================
     CART LIST
========================= -->
<div class="cart-list" id="cartList">

    <?php foreach ($items as $it) : ?>
        <div class="cart-item">
            <div class="cart-thumb">
                <img src="../assets/images/menu/<?= htmlspecialchars($it['foto']); ?>"
                     alt="<?= htmlspecialchars($it['nama_menu']); ?>"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/200x200/f2e9d6/3d2817?text=Menu'">
            </div>

            <div class="cart-info">
                <div class="cart-info-top">
                    <div style="flex:1; min-width:0;">
                        <h3><?= htmlspecialchars($it['nama_menu']); ?></h3>
                        <p class="price-small">Rp <?= number_format($it['harga'], 0, ',', '.'); ?> / porsi</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Hapus item ini dari keranjang?')">
                        <input type="hidden" name="action" value="hapus">
                        <input type="hidden" name="id_menu" value="<?= $it['id_menu']; ?>">
                        <button type="submit" class="btn-remove" title="Hapus">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="cart-bottom">
                    <div class="cart-subtotal">
                        <span class="cur">Rp</span>
                        <?= number_format($it['subtotal'], 0, ',', '.'); ?>
                    </div>

                    <div class="qty-control">
                        <form method="POST">
                            <input type="hidden" name="action" value="kurang">
                            <input type="hidden" name="id_menu" value="<?= $it['id_menu']; ?>">
                            <button type="submit" class="qty-btn" aria-label="Kurangi">−</button>
                        </form>
                        <span class="qty-value"><?= $it['qty']; ?></span>
                        <form method="POST">
                            <input type="hidden" name="action" value="tambah">
                            <input type="hidden" name="id_menu" value="<?= $it['id_menu']; ?>">
                            <button type="submit" class="qty-btn" aria-label="Tambah">+</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<!-- =========================
     CHECKOUT BAR
========================= -->
<div class="checkout-bar">
    <div class="info">
        <div class="label">Total Bayar</div>
        <div class="amount">
            <span class="cur">Rp</span>
            <?= number_format($grand_total, 0, ',', '.'); ?>
        </div>
    </div>
    <button class="btn-checkout" onclick="checkout()">
        <span>Checkout</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
        </svg>
    </button>
</div>

<?php else : ?>

<!-- =========================
     EMPTY STATE
========================= -->
<div style="padding: 40px 0;">
    <div class="empty-cart">
        <div class="ic">🛒</div>
        <h3>Keranjang Kosong</h3>
        <p>Belum ada menu yang Anda pilih.<br>Yuk, pilih menu favorit Anda terlebih dahulu!</p>
        <a href="index.php" class="btn-explore">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Lihat Menu
        </a>
    </div>
</div>

<?php endif; ?>

<!-- =========================
     TOAST
========================= -->
<div class="toast" id="toast">
    <span class="check">✓</span>
    <span id="toastMessage">Berhasil!</span>
</div>

<!-- =========================
     BOTTOM NAV
========================= -->
<div class="bottom-nav">
    <div class="batik-overlay"></div>
    <a href="index.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Home
    </a>
    <a href="keranjang.php" class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        Keranjang
        <?php if ($total_qty > 0) : ?>
            <span class="nav-badge"><?= $total_qty > 99 ? '99+' : $total_qty; ?></span>
        <?php endif; ?>
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
    // =========================
    // Toast
    // =========================
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    let toastTimer;

    function showToast(msg) {
        toastMessage.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
    }

    // =========================
    // Checkout
    // =========================
    function checkout() {
        showToast('Pesanan Anda sedang diproses...');
        setTimeout(() => {
            window.location = 'checkout.php';
        }, 900);
    }

    // =========================
    // Animasi Qty Click
    // =========================
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.style.transform = 'scale(0.85)';
            setTimeout(() => { this.style.transform = ''; }, 120);
        });
    });
</script>

</body>
</html>