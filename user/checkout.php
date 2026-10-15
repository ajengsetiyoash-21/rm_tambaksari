<?php
session_start();
include "../config.php";

if (empty($_SESSION['keranjang'])) {
    header('Location: keranjang.php');
    exit;
}

$id_meja = $_SESSION['id_meja'] ?? 1;
$id_user = $_SESSION['id_user'] ?? null;

$items = [];
$total = 0;
$total_qty = 0;

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

$tax = round($total * 0.10);
$service = $total > 0 ? 2000 : 0;
$grand_total = $total + $tax + $service;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_bayar = trim($_POST['metode_bayar'] ?? 'Cash');
    $catatan      = trim($_POST['catatan'] ?? '');

    if (!in_array($metode_bayar, ['Cash', 'QRIS'], true)) {
        $metode_bayar = 'Cash';
    }

    $stmt = mysqli_prepare($db, "
        INSERT INTO pesanan
        (id_user, id_meja, total_harga, status_bayar, metode_bayar, catatan, created_at)
        VALUES (?, ?, ?, 'belum_bayar', ?, ?, NOW())
    ");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iidss', $id_user, $id_meja, $grand_total, $metode_bayar, $catatan);

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
                        $stmt2, 'iiiii',
                        $id_pesanan, $it['id_menu'], $it['qty'],
                        $it['harga'], $it['subtotal']
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

$metode_terpilih = $_POST['metode_bayar'] ?? 'Cash';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#241609">
    <title>Checkout - RM. Tambaksari</title>

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
            --danger: #c0392b;
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
        .topbar h1 { font-size: 17px; font-weight: 700; }

        .card {
            background: var(--card);
            margin-top: 8px;
            padding: 14px 16px;
        }

        .card-title { font-size: 14px; font-weight: 700; margin-bottom: 4px; }

        .resto { display: flex; align-items: center; gap: 12px; }

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

        .error-box {
            margin: 8px 0 0;
            background: #fdecea;
            color: var(--danger);
            padding: 12px 16px;
            font-size: 12.5px;
            font-weight: 600;
        }

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
            width: 64px;
            height: 64px;
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

        .item-unit { font-size: 11.5px; color: var(--muted); margin-top: 2px; }

        .item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }

        .item-price { font-size: 14px; font-weight: 700; }

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
        .step-btn:disabled { opacity: 0.5; }

        .step-minus { background: #fff; border: 1.5px solid var(--orange); color: var(--orange); }
        .step-plus  { background: var(--orange); border: 1.5px solid var(--orange); color: #fff; }

        .step-qty { min-width: 16px; text-align: center; font-size: 14px; font-weight: 700; }

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

        .pay-list { margin-top: 6px; }

        .pay-option { display: block; cursor: pointer; }

        .pay-option input { position: absolute; opacity: 0; pointer-events: none; }

        .pay-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid var(--line);
        }

        .pay-option:last-child .pay-box { border-bottom: none; }

        .pay-ic {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f5f5f5;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pay-text { flex: 1; }
        .pay-text b { display: block; font-size: 14px; font-weight: 600; }
        .pay-text small { font-size: 11.5px; color: var(--muted); }

        .radio {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .radio::after {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--orange);
            transform: scale(0);
            transition: transform 0.15s;
        }

        .pay-option input:checked + .pay-box .radio { border-color: var(--orange); }
        .pay-option input:checked + .pay-box .radio::after { transform: scale(1); }
        .pay-option input:checked + .pay-box .pay-ic { background: var(--orange-soft); color: var(--orange); }
        .pay-option input:focus-visible + .pay-box { outline: 2px solid var(--orange); outline-offset: 2px; }

        .form-textarea {
            width: 100%;
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px solid #e2e2e2;
            border-radius: 10px;
            font-size: 13.5px;
            color: var(--text);
            background: #fafafa;
            outline: none;
            resize: vertical;
            min-height: 72px;
        }

        .form-textarea:focus { border-color: var(--orange); background: #fff; }
        .form-textarea::placeholder { color: #b5b5b5; }

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
            white-space: nowrap;
        }

        .btn-primary:active { background: var(--orange-dark); }
        .btn-primary:disabled { opacity: 0.7; }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 360px;
            padding: 22px 20px;
            text-align: center;
        }
        .modal-box h3 {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .modal-box .qr-total {
            font-size: 22px;
            font-weight: 800;
            color: var(--orange);
            margin: 8px 0 4px;
        }
        .modal-box .qr-sub {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 14px;
        }
        .qr-wrap {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            display: inline-block;
            margin-bottom: 14px;
        }
        .qr-wrap img { width: 240px; height: 240px; display: block; }
        .qr-loading {
            width: 240px; height: 240px;
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); font-size: 13px;
        }
        .modal-actions { display: flex; gap: 8px; }
        .btn-cancel, .btn-confirm {
            flex: 1; padding: 12px; border-radius: 10px;
            font-size: 13.5px; font-weight: 700; cursor: pointer;
            border: none; font-family: inherit;
        }
        .btn-cancel { background: #f2f2f2; color: #555; }
        .btn-confirm { background: var(--orange); color: #fff; }
        .btn-confirm:active { background: var(--orange-dark); }
        .qr-badge {
            display: inline-block;
            background: #fff1ed; color: var(--orange);
            font-size: 11px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="keranjang.php" class="btn-back">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1>Checkout</h1>
    </div>
</header>

<div class="wrap">

    <?php if ($error) : ?>
        <div class="error-box"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

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

    <form method="POST" id="checkoutForm">

        <div class="card">
            <div class="card-title">Ringkasan pesanan</div>

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
                            <div class="item-unit">Rp<?= number_format($it['harga'], 0, ',', '.'); ?> / porsi</div>
                        </div>
                        <div class="item-row">
                            <div class="item-price">Rp<span class="js-sub"><?= number_format($it['subtotal'], 0, ',', '.'); ?></span></div>
                            <div class="stepper">
                                <button type="button" class="step-btn step-minus" data-act="kurang">−</button>
                                <span class="step-qty"><?= $it['qty']; ?></span>
                                <button type="button" class="step-btn step-plus" data-act="tambah">+</button>
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
            <div class="card-title">Metode pembayaran</div>
            <div class="pay-list">
                <label class="pay-option">
                    <input type="radio" name="metode_bayar" value="Cash" <?= $metode_terpilih !== 'QRIS' ? 'checked' : ''; ?>>
                    <div class="pay-box">
                        <div class="pay-ic">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                        </div>
                        <div class="pay-text"><b>Cash</b><small>Bayar di kasir</small></div>
                        <span class="radio"></span>
                    </div>
                </label>
                <label class="pay-option">
                    <input type="radio" name="metode_bayar" value="QRIS" <?= $metode_terpilih === 'QRIS' ? 'checked' : ''; ?>>
                    <div class="pay-box">
                        <div class="pay-ic">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"/></svg>
                        </div>
                        <div class="pay-text"><b>QRIS</b><small>Scan kode QR</small></div>
                        <span class="radio"></span>
                    </div>
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Catatan untuk dapur</div>
            <textarea class="form-textarea" id="catatan" name="catatan"
                      placeholder="Contoh: tidak pedas, tanpa bawang"><?= htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
        </div>

        <div class="card">
            <div class="card-title">Rincian pembayaran</div>
            <div class="cost-row"><span>Subtotal (<b id="cQty"><?= $total_qty; ?></b> porsi)</span><span>Rp<span id="cSub"><?= number_format($total, 0, ',', '.'); ?></span></span></div>
            <div class="cost-row"><span>PPN 10%</span><span>Rp<span id="cTax"><?= number_format($tax, 0, ',', '.'); ?></span></span></div>
            <div class="cost-row"><span>Biaya layanan</span><span>Rp<span id="cSvc"><?= number_format($service, 0, ',', '.'); ?></span></span></div>
            <div class="cost-row total"><span>Total bayar</span><span>Rp<span id="cTotal"><?= number_format($grand_total, 0, ',', '.'); ?></span></span></div>
        </div>
    </form>
</div>

<!-- MODAL QRIS -->
<div class="modal-overlay" id="modalQris">
    <div class="modal-box">
        <span class="qr-badge">QRIS</span>
        <h3>Scan untuk Bayar</h3>
        <div class="qr-sub">Total yang harus dibayar</div>
        <div class="qr-total" id="qrTotal">Rp 0</div>
        <div class="qr-wrap" id="qrWrap">
            <div class="qr-loading">Memuat QR Code...</div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="tutupModalQris()">Batal</button>
            <button type="button" class="btn-confirm" onclick="konfirmasiQRIS()">Saya sudah bayar</button>
        </div>
    </div>
</div>

<div class="bar">
    <div class="bar-inner">
        <div class="bar-total">
            <small>Total bayar</small>
            <strong>Rp<span id="barTotal"><?= number_format($grand_total, 0, ',', '.'); ?></span></strong>
        </div>
        <button type="button" class="btn-primary" id="btnSubmit">Buat pesanan</button>
    </div>
</div>

<!-- script utama -->
<script>
    const rupiah = n => n.toLocaleString('id-ID');
    const $ = id => document.getElementById(id);

    function post(action, id) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('id_menu', id);
        fd.append('ajax', '1');
        return fetch('keranjang.php', { method: 'POST', body: fd }).then(r => r.json());
    }

    function recalc() {
        let sub = 0, qty = 0;
        document.querySelectorAll('.item').forEach(el => {
            sub += (+el.dataset.qty) * (+el.dataset.harga);
            qty += +el.dataset.qty;
        });

        if (qty === 0) {
            window.location = 'keranjang.php';
            return;
        }

        const tax = Math.round(sub * 0.10), svc = 2000, total = sub + tax + svc;
        $('cQty').textContent = qty;
        $('cSub').textContent = rupiah(sub);
        $('cTax').textContent = rupiah(tax);
        $('cSvc').textContent = rupiah(svc);
        $('cTotal').textContent = rupiah(total);
        $('barTotal').textContent = rupiah(total);
    }

    $('itemList').addEventListener('click', async e => {
        const btn = e.target.closest('.step-btn');
        if (!btn) return;

        const row = btn.closest('.item');
        const act = btn.dataset.act;
        const qty = +row.dataset.qty + (act === 'tambah' ? 1 : -1);

        row.querySelectorAll('.step-btn').forEach(b => b.disabled = true);
        try {
            await post(act, row.dataset.id);
        } catch (err) {
            row.querySelectorAll('.step-btn').forEach(b => b.disabled = false);
            return;
        }
        row.querySelectorAll('.step-btn').forEach(b => b.disabled = false);

        if (qty <= 0) {
            row.dataset.qty = 0;
            row.classList.add('removing');
            setTimeout(() => { row.remove(); recalc(); }, 250);
            return;
        }

        row.dataset.qty = qty;
        row.querySelector('.step-qty').textContent = qty;
        row.querySelector('.js-sub').textContent = rupiah(qty * (+row.dataset.harga));
        recalc();
    });
</script>

<!-- script modal qris -->
<script src="qris.js"></script>

</body>
</html>