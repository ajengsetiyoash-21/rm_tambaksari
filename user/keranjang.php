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
// AJAX: GET COUNT (badge di index.php)
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
        $_SESSION['keranjang'][$id_menu] = ($_SESSION['keranjang'][$id_menu] ?? 0) + 1;
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

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'total_qty' => array_sum($_SESSION['keranjang'])
        ]);
        exit;
    }

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
    $ids_str = implode(',', array_map('intval', array_keys($_SESSION['keranjang'])));

    $q = mysqli_query($db, "
        SELECT menu.*, kategori.nama_kategori
        FROM menu
        LEFT JOIN kategori ON menu.id_kategori = kategori.id_kategori
        WHERE menu.id_menu IN ($ids_str)
    ");

    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $qty = $_SESSION['keranjang'][$row['id_menu']];
            $row['qty'] = $qty;
            $row['subtotal'] = $row['harga'] * $qty;
            $total += $row['subtotal'];
            $total_qty += $qty;
            $items[] = $row;
        }
    }
}

$tax = round($total * 0.10);
$service = $total > 0 ? 2000 : 0;
$grand_total = $total + $tax + $service;
$id_meja = $_SESSION['id_meja'] ?? 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#241609">
    <title>Keranjang - RM. Tambaksari</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --orange: #3d2817;
            --orange-dark: #241609;
            --orange-soft: #fff1ed;
            --bg: #faf6ee;
            --card: #ffffff;
            --text: #222222;
            --muted: #8c8c8c;
            --line: #efefef;
        }

        html, body { width: 100%; overflow-x: hidden; }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: 96px;
            -webkit-font-smoothing: antialiased;
        }

        .wrap { max-width: 560px; margin: 0 auto; }

        /* ===== HEADER ===== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--card);
            border-bottom: 1px solid var(--line);
        }

        .topbar-inner {
            max-width: 560px;
            margin: 0 auto;
            height: 54px;
            padding: 0 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back {
            width: 36px;
            height: 36px;
            margin-left: -8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--orange);
            text-decoration: none;
            border-radius: 50%;
        }

        .btn-back:active { background: var(--orange-soft); }

        .topbar h1 {
            flex: 1;
            font-size: 17px;
            font-weight: 700;
        }

        .btn-clear {
            background: none;
            border: none;
            color: var(--orange);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 4px;
        }

        /* ===== CARD ===== */
        .card {
            background: var(--card);
            margin-top: 8px;
            padding: 14px 16px;
        }

        .resto {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .resto-ic {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--orange-soft);
            color: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .resto-name { font-size: 14.5px; font-weight: 700; }
        .resto-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .meja-chip {
            margin-left: auto;
            background: var(--orange-soft);
            color: var(--orange);
            font-size: 12px;
            font-weight: 700;
            padding: 5px 11px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .card-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        /* ===== ITEM ===== */
        .item {
            display: flex;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid var(--line);
        }

        .item.removing {
            opacity: 0;
            transform: translateX(24px);
            transition: all 0.25s;
        }

        .thumb {
            width: 72px;
            height: 72px;
            border-radius: 10px;
            overflow: hidden;
            background: #eee;
            flex-shrink: 0;
        }

        .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .item-body {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-name {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-cat { font-size: 11.5px; color: var(--muted); margin-top: 2px; }

        .item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }

        .item-price { font-size: 14px; font-weight: 700; }

        /* ===== STEPPER ===== */
        .stepper { display: flex; align-items: center; gap: 12px; }

        .step-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.12s;
        }

        .step-btn:active { transform: scale(0.88); }

        .step-minus {
            background: #fff;
            border: 1.5px solid var(--orange);
            color: var(--orange);
        }

        .step-plus {
            background: var(--orange);
            border: 1.5px solid var(--orange);
            color: #fff;
        }

        .step-qty {
            min-width: 16px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
        }

        .btn-more {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 14px 0 2px;
            color: var(--orange);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        /* ===== COST ===== */
        .cost-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #555;
        }

        .cost-row.total {
            margin-top: 6px;
            padding-top: 12px;
            border-top: 1px dashed #ddd;
            font-size: 15px;
            font-weight: 800;
            color: var(--text);
        }

        .cost-row.total span:last-child { color: var(--orange); }

        /* ===== BOTTOM BAR ===== */
        .bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 60;
            background: var(--card);
            border-top: 1px solid var(--line);
            box-shadow: 0 -4px 16px rgba(0,0,0,0.05);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        .bar-inner {
            max-width: 560px;
            margin: 0 auto;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .bar-total { flex: 1; }
        .bar-total small { display: block; font-size: 11.5px; color: var(--muted); }
        .bar-total strong { font-size: 18px; font-weight: 800; color: var(--orange); }

        .btn-primary {
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px 26px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary:active { background: var(--orange-dark); }

        /* ===== EMPTY ===== */
        .empty { text-align: center; padding: 80px 30px; }

        .empty-ic {
            width: 96px;
            height: 96px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: var(--orange-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
        }

        .empty h3 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
        .empty p { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 22px; }

        .hidden { display: none !important; }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="btn-back" aria-label="Kembali">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1>Keranjang</h1>
        <?php if (!empty($items)) : ?>
            <button type="button" class="btn-clear" id="btnClear">Kosongkan</button>
        <?php endif; ?>
    </div>
</header>

<div class="wrap">

<?php if (!empty($items)) : ?>

    <div id="filled">
        <div class="card">
            <div class="resto">
                <div class="resto-ic">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>
                </div>
                <div>
                    <div class="resto-name">RM. Tambaksari</div>
                    <div class="resto-sub">Makan di tempat</div>
                </div>
                <div class="meja-chip">Meja <?= htmlspecialchars($id_meja); ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Pesanan kamu</div>

            <div id="itemList">
            <?php foreach ($items as $it) : ?>
                <div class="item" data-id="<?= $it['id_menu']; ?>" data-harga="<?= $it['harga']; ?>" data-qty="<?= $it['qty']; ?>">
                    <div class="thumb">
                        <img src="../assets/images/menu/<?= htmlspecialchars($it['foto']); ?>"
                             alt="<?= htmlspecialchars($it['nama_menu']); ?>"
                             loading="lazy"
                             onerror="this.src='https://via.placeholder.com/200x200/eeeeee/999999?text=Menu'">
                    </div>
                    <div class="item-body">
                        <div>
                            <div class="item-name"><?= htmlspecialchars($it['nama_menu']); ?></div>
                            <?php if (!empty($it['nama_kategori'])) : ?>
                                <div class="item-cat"><?= htmlspecialchars($it['nama_kategori']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="item-row">
                            <div class="item-price">Rp<span class="js-sub"><?= number_format($it['subtotal'], 0, ',', '.'); ?></span></div>
                            <div class="stepper">
                                <button type="button" class="step-btn step-minus" data-act="kurang" aria-label="Kurangi">−</button>
                                <span class="step-qty"><?= $it['qty']; ?></span>
                                <button type="button" class="step-btn step-plus" data-act="tambah" aria-label="Tambah">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <a href="index.php" class="btn-more">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah menu lagi
            </a>
        </div>

        <div class="card">
            <div class="card-title">Rincian pembayaran</div>
            <div class="cost-row"><span>Subtotal (<b id="cQty"><?= $total_qty; ?></b> porsi)</span><span>Rp<span id="cSub"><?= number_format($total, 0, ',', '.'); ?></span></span></div>
            <div class="cost-row"><span>PPN 10%</span><span>Rp<span id="cTax"><?= number_format($tax, 0, ',', '.'); ?></span></span></div>
            <div class="cost-row"><span>Biaya layanan</span><span>Rp<span id="cSvc"><?= number_format($service, 0, ',', '.'); ?></span></span></div>
            <div class="cost-row total"><span>Total bayar</span><span>Rp<span id="cTotal"><?= number_format($grand_total, 0, ',', '.'); ?></span></span></div>
        </div>
    </div>

    <div class="bar" id="bar">
        <div class="bar-inner">
            <div class="bar-total">
                <small>Total bayar</small>
                <strong>Rp<span id="barTotal"><?= number_format($grand_total, 0, ',', '.'); ?></span></strong>
            </div>
            <a href="checkout.php" class="btn-primary">Checkout (<span id="barQty"><?= $total_qty; ?></span>)</a>
        </div>
    </div>

<?php endif; ?>

    <div class="empty <?= !empty($items) ? 'hidden' : ''; ?>" id="empty">
        <div class="empty-ic">🛒</div>
        <h3>Keranjang masih kosong</h3>
        <p>Pilih menu favoritmu dulu,<br>lalu kembali ke sini untuk checkout.</p>
        <a href="index.php" class="btn-primary" style="display:inline-block;">Lihat menu</a>
    </div>

</div>

<script>
    const rupiah = n => n.toLocaleString('id-ID');
    const $ = id => document.getElementById(id);

    function post(action, id) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('ajax', '1');
        if (id) fd.append('id_menu', id);
        return fetch('keranjang.php', { method: 'POST', body: fd }).then(r => r.json());
    }

    function recalc() {
        let sub = 0, qty = 0;
        document.querySelectorAll('.item').forEach(el => {
            const q = +el.dataset.qty, h = +el.dataset.harga;
            sub += q * h;
            qty += q;
        });

        if (qty === 0) {
            ['filled', 'bar', 'btnClear'].forEach(id => { const e = $(id); if (e) e.classList.add('hidden'); });
            $('empty').classList.remove('hidden');
            return;
        }

        const tax = Math.round(sub * 0.10), svc = 2000, total = sub + tax + svc;
        $('cQty').textContent = qty;
        $('cSub').textContent = rupiah(sub);
        $('cTax').textContent = rupiah(tax);
        $('cSvc').textContent = rupiah(svc);
        $('cTotal').textContent = rupiah(total);
        $('barTotal').textContent = rupiah(total);
        $('barQty').textContent = qty;
    }

    const list = $('itemList');
    if (list) {
        list.addEventListener('click', async e => {
            const btn = e.target.closest('.step-btn');
            if (!btn) return;

            const row = btn.closest('.item');
            const act = btn.dataset.act;
            const qty = +row.dataset.qty + (act === 'tambah' ? 1 : -1);

            btn.disabled = true;
            try {
                await post(act, row.dataset.id);
            } catch (err) {
                btn.disabled = false;
                return;
            }
            btn.disabled = false;

            if (qty <= 0) {
                row.dataset.qty = 0;
                row.classList.add('removing');
                setTimeout(() => { row.remove(); recalc(); }, 250);
                return;
            }

            row.dataset.qty = qty;
            row.querySelector('.step-qty').textContent = qty;
            row.querySelector('.js-sub').textContent = rupiah(qty * +row.dataset.harga);
            recalc();
        });
    }

    const clr = $('btnClear');
    if (clr) {
        clr.addEventListener('click', async () => {
            if (!confirm('Kosongkan semua item dari keranjang?')) return;
            await post('kosongkan');
            document.querySelectorAll('.item').forEach(el => el.dataset.qty = 0);
            recalc();
        });
    }
</script>

</body>
</html>