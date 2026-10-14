<?php
session_start();
include "../config.php";

$query = mysqli_query($db, "
    SELECT menu.*, kategori.nama_kategori 
    FROM menu 
    LEFT JOIN kategori ON menu.id_kategori = kategori.id_kategori 
    ORDER BY menu.id_menu ASC
");

if (!$query) {
    die("Query error: " . mysqli_error($db));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2b1d0e">
    <title>Menu - RM. Tambaksari</title>

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
            padding-bottom: 110px;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            background-color: var(--bg);
        }

        /* MOTIF BATIK — subtle overlay */
        .batik-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.10;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'><defs><g id='f'><circle cx='0' cy='0' r='2.5' fill='%23f0d9a0'/><ellipse cx='0' cy='-6' rx='3' ry='5.5' fill='%23f0d9a0'/><ellipse cx='0' cy='6' rx='3' ry='5.5' fill='%23f0d9a0'/><ellipse cx='-6' cy='0' rx='5.5' ry='3' fill='%23f0d9a0'/><ellipse cx='6' cy='0' rx='5.5' ry='3' fill='%23f0d9a0'/></g><g id='l'><path d='M 0 0 Q 5 -5 10 0 Q 5 5 0 0 Z' fill='%23f0d9a0'/></g></defs><use href='%23f' x='20' y='20'/><use href='%23l' x='60' y='30' transform='rotate(30 65 30)'/><use href='%23f' x='90' y='70'/><use href='%23l' x='30' y='90' transform='rotate(-40 35 90)'/><use href='%23f' x='70' y='110'/><use href='%23l' x='100' y='20' transform='rotate(60 105 20)'/></svg>");
            background-size: 120px 120px;
        }

        /* HEADER — BANNER MELENGKUNG */
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

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
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

        .brand-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 1.2px;
            line-height: 1.15;
        }

        .brand-text small {
            display: block;
            font-size: 8.5px;
            font-weight: 600;
            letter-spacing: 2.8px;
            color: var(--accent-soft);
            margin-top: 4px;
            opacity: 0.9;
        }

        .table-chip {
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

        .table-chip .live {
            width: 7px;
            height: 7px;
            background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 8px #4ade80;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* HERO CARD — MENGAMBANG */
        .hero-wrap {
            padding: 0 22px;
            margin-top: -42px;
            position: relative;
            z-index: 5;
        }

        .hero-card {
            background: linear-gradient(135deg, #fffdf8 0%, var(--cream-warm) 100%);
            border-radius: 22px;
            padding: 20px 22px;
            box-shadow: var(--shadow-lg), inset 0 0 0 1px rgba(201, 169, 97, 0.25);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(201, 169, 97, 0.3);
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(201, 169, 97, 0.18) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-card .greet {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .hero-card .greet span {
            color: var(--accent);
        }

        .hero-card .desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.55;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary);
            color: var(--accent-soft);
            font-size: 10.5px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            margin-top: 12px;
            letter-spacing: 0.4px;
            position: relative;
            z-index: 1;
        }

        /* SEARCH */
        .search-wrap {
            padding: 20px 22px 0 22px;
        }

        .search-box {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
            border: 1.5px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-box:focus-within {
            box-shadow: 0 8px 24px rgba(61, 40, 23, 0.12), 0 0 0 3px rgba(201, 169, 97, 0.15);
            border-color: var(--accent);
        }

        .search-box svg {
            color: var(--primary);
            flex-shrink: 0;
            opacity: 0.6;
        }

        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
            background: transparent;
            color: var(--text);
        }

        .search-box input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        /* KATEGORI — PILL SVG */
        .kategori-scroll {
            display: flex;
            gap: 9px;
            overflow-x: auto;
            padding: 18px 22px 6px 22px;
            scrollbar-width: none;
        }

        .kategori-scroll::-webkit-scrollbar { display: none; }

        .kat-btn {
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            color: var(--primary);
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 12.5px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            letter-spacing: 0.2px;
        }

        .kat-btn svg {
            width: 15px;
            height: 15px;
            stroke-width: 2.2;
        }

        .kat-btn:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .kat-btn.active {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--accent-soft);
            border-color: var(--primary-dark);
            box-shadow: 0 6px 18px rgba(61, 40, 23, 0.3);
        }

        /* SECTION TITLE */
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 18px 22px 4px 22px;
        }

        .section-head h2 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: var(--primary);
            font-weight: 800;
        }

        .section-head .count {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* MENU LIST — CARD HORIZONTAL */
        .menu-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 12px 22px 30px 22px;
        }

        .menu-item {
            background: var(--card-bg);
            border-radius: 18px;
            display: flex;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(230, 212, 176, 0.7);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) backwards;
        }

        .menu-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(61, 40, 23, 0.14);
            border-color: var(--accent);
        }

        .menu-thumb {
            width: 110px;
            min-width: 110px;
            height: 110px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--cream-warm), #e8dcc2);
            flex-shrink: 0;
        }

        .menu-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .menu-item:hover .menu-thumb img {
            transform: scale(1.08);
        }

        .kat-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(36, 22, 9, 0.85);
            color: var(--accent-soft);
            font-size: 8.5px;
            font-weight: 800;
            padding: 4px 9px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(201, 169, 97, 0.4);
        }

        .menu-info {
            flex: 1;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .menu-info h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.35;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .menu-info .price {
            font-size: 15px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: baseline;
            gap: 2px;
            margin-bottom: 8px;
        }

        .menu-info .price .cur {
            font-size: 10.5px;
            font-weight: 700;
            opacity: 0.65;
        }

        .menu-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .btn-add {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            color: var(--accent-soft);
            border: none;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 10px rgba(61, 40, 23, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn-add::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(201, 169, 97, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-add:hover::after { left: 100%; }

        .btn-add:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(61, 40, 23, 0.35);
        }

        .btn-add:active { transform: scale(0.97); }

        .btn-add svg, .btn-add span { position: relative; z-index: 1; }

        /* BOTTOM NAV */
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

        /* BADGE COUNTER DI NAV */
        .nav-item .nav-badge {
            position: absolute;
            top: 0;
            right: 6px;
            background: linear-gradient(135deg, var(--accent-glow), var(--accent));
            color: var(--primary-dark);
            font-size: 8.5px;
            font-weight: 800;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.35);
            z-index: 2;
        }

        /* TOAST */
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

        /* EMPTY */
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty .ic { font-size: 60px; opacity: 0.4; margin-bottom: 14px; display: block; }
        .empty p { font-size: 15px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
        .empty small { font-size: 12.5px; }

        /* ANIMASI */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .menu-item:nth-child(1) { animation-delay: 0.03s; }
        .menu-item:nth-child(2) { animation-delay: 0.06s; }
        .menu-item:nth-child(3) { animation-delay: 0.09s; }
        .menu-item:nth-child(4) { animation-delay: 0.12s; }
        .menu-item:nth-child(5) { animation-delay: 0.15s; }
        .menu-item:nth-child(6) { animation-delay: 0.18s; }

        /* RESPONSIVE */
        @media (min-width: 600px) {
            .menu-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }

        @media (min-width: 900px) {
            .menu-list { grid-template-columns: repeat(3, 1fr); }
            .header, .hero-wrap, .search-wrap, .kategori-scroll, .section-head, .menu-list {
                padding-left: 32px;
                padding-right: 32px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER BANNER -->
<header class="header">
    <div class="batik-overlay"></div>
    <div class="header-inner">
        <div class="brand">
            <div class="brand-logo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="brand-text">
                <h1>RM. TAMBAKSARI</h1>
                <small>RASA AUTENTIK SEJAK 2024</small>
            </div>
        </div>
        <div class="table-chip">
            <span class="live"></span>
            Meja 01
        </div>
    </div>
</header>

<!-- HERO CARD -->
<div class="hero-wrap">
    <div class="hero-card">
        <div class="greet">Selamat Datang, <span>Tamu</span> 👋</div>
        <div class="desc">Silakan pilih menu favorit Anda. Pesanan akan segera kami siapkan dengan sepenuh hati.</div>
        <div class="hero-badge">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Buka 08.00 – 22.00 WIB
        </div>
    </div>
</div>

<!-- SEARCH -->
<div class="search-wrap">
    <div class="search-box">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="searchInput" placeholder="Cari menu favorit Anda...">
    </div>
</div>

<!-- KATEGORI -->
<div class="kategori-scroll">
    <button class="kat-btn active" data-kategori="semua">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
        </svg>
        Semua
    </button>
    <button class="kat-btn" data-kategori="makanan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path>
            <path d="M7 2v20"></path>
            <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"></path>
        </svg>
        Makanan
    </button>
    <button class="kat-btn" data-kategori="minuman">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 8h1a4 4 0 1 1 0 8h-1"></path>
            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path>
            <line x1="6" y1="2" x2="6" y2="4"></line>
            <line x1="10" y1="2" x2="10" y2="4"></line>
            <line x1="14" y1="2" x2="14" y2="4"></line>
        </svg>
        Minuman
    </button>
</div>

<!-- SECTION TITLE -->
<div class="section-head">
    <h2>Daftar Menu</h2>
    <span class="count" id="menuCount"><?= mysqli_num_rows($query); ?> menu tersedia</span>
</div>

<!-- MENU LIST -->
<div class="menu-list" id="menuContainer">

    <?php if (mysqli_num_rows($query) > 0) : ?>
        <?php while ($menu = mysqli_fetch_assoc($query)) : ?>
            <div class="menu-item"
                 data-kategori="<?= htmlspecialchars(strtolower($menu['nama_kategori'] ?? '')); ?>"
                 data-nama="<?= htmlspecialchars(strtolower($menu['nama_menu'])); ?>">

                <div class="menu-thumb">
                    <?php if (!empty($menu['nama_kategori'])) : ?>
                        <span class="kat-tag"><?= htmlspecialchars($menu['nama_kategori']); ?></span>
                    <?php endif; ?>
                    <img src="../assets/images/menu/<?= htmlspecialchars($menu['foto']); ?>"
                         alt="<?= htmlspecialchars($menu['nama_menu']); ?>"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/300x300/f2e9d6/3d2817?text=Menu'">
                </div>

                <div class="menu-info">
                    <div>
                        <h3><?= htmlspecialchars($menu['nama_menu']); ?></h3>
                        <p class="price"><span class="cur">Rp</span> <?= number_format($menu['harga'], 0, ',', '.'); ?></p>
                    </div>
                    <div class="menu-actions">
                        <button class="btn-add"
                                data-id="<?= $menu['id_menu']; ?>"
                                data-nama="<?= htmlspecialchars($menu['nama_menu']); ?>"
                                onclick="pesanMenu(this)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span>Tambah</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else : ?>
        <div class="empty">
            <span class="ic">🍽️</span>
            <p>Belum ada menu di database</p>
            <small>Silakan tambahkan menu melalui halaman admin.</small>
        </div>
    <?php endif; ?>

</div>

<!-- TOAST -->
<div class="toast" id="toast">
    <span class="check">✓</span>
    <span id="toastMessage">Menu ditambahkan!</span>
</div>

<!-- BOTTOM NAV -->
<div class="bottom-nav">
    <div class="batik-overlay"></div>
    <a href="index.php" class="nav-item active">
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
        <span class="nav-badge" id="cartBadge">0</span>
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
    // Filter & Search
    // =========================
    const searchInput = document.getElementById('searchInput');
    const katBtns = document.querySelectorAll('.kat-btn');
    const menuItems = document.querySelectorAll('.menu-item');
    const menuCount = document.getElementById('menuCount');

    let filterKategori = 'semua';
    let filterSearch = '';

    function applyFilter() {
        let visible = 0;
        menuItems.forEach(item => {
            const kategori = (item.getAttribute('data-kategori') || '').toLowerCase().trim();
            const nama = item.getAttribute('data-nama') || '';

            const cocokKat = (filterKategori === 'semua') || (kategori === filterKategori.toLowerCase());
            const cocokCari = nama.includes(filterSearch.toLowerCase());

            if (cocokKat && cocokCari) {
                item.style.display = 'flex';
                visible++;
            } else {
                item.style.display = 'none';
            }
        });

        menuCount.textContent = visible + ' menu tersedia';

        const oldEmpty = document.querySelector('.empty.dynamic');
        if (oldEmpty) oldEmpty.remove();

        if (visible === 0 && menuItems.length > 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'empty dynamic';
            emptyDiv.innerHTML = `
                <span class="ic">🔍</span>
                <p>Menu tidak ditemukan</p>
                <small>Coba kata kunci atau kategori lain.</small>
            `;
            document.getElementById('menuContainer').appendChild(emptyDiv);
        }
    }

    katBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            katBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterKategori = this.getAttribute('data-kategori');
            applyFilter();
        });
    });

    searchInput.addEventListener('input', function() {
        filterSearch = this.value;
        applyFilter();
    });

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
    // Pesan Menu — Kirim ke Keranjang via AJAX
    // =========================
    function pesanMenu(btn) {
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');

        // Animasi tombol feedback
        const originalText = btn.querySelector('span').textContent;
        btn.querySelector('span').textContent = '✓ Ditambahkan';
        btn.style.background = 'linear-gradient(135deg, #2d6a4f, #40916c)';
        setTimeout(() => {
            btn.querySelector('span').textContent = originalText;
            btn.style.background = '';
        }, 900);

        // Toast
        showToast('"' + nama + '" ditambahkan ke keranjang!');

        // Kirim ke keranjang.php
        const fd = new FormData();
        fd.append('action', 'tambah');
        fd.append('id_menu', id);
        fd.append('ajax', '1');

        fetch('keranjang.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.total_qty);
            }
        })
        .catch(err => console.error('Gagal tambah ke keranjang:', err));
    }

    // =========================
    // Update Badge Counter di Bottom Nav
    // =========================
    function updateCartBadge(qty) {
        const badge = document.getElementById('cartBadge');
        if (!badge) return;

        if (qty > 0) {
            badge.textContent = qty > 99 ? '99+' : qty;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // =========================
    // Load Badge saat halaman dibuka
    // =========================
    fetch('keranjang.php?count=1')
        .then(res => res.json())
        .then(data => {
            if (data.total_qty !== undefined) updateCartBadge(data.total_qty);
        })
        .catch(() => {});
</script>

</body>
</html>