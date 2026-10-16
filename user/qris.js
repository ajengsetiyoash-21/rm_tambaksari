document.addEventListener('DOMContentLoaded', function() {

    var btn = document.getElementById('btnSubmit');
    if (!btn) return;

    btn.addEventListener('click', function() {
        var radio = document.querySelector('input[name="metode_bayar"]:checked');
        if (!radio) return;

        if (radio.value === 'QRIS') {
            bukaModalQris();
        } else {
            btn.textContent = 'Memproses...';
            btn.disabled = true;
            document.getElementById('checkoutForm').submit();
        }
    });
});

function bukaModalQris() {
    var modal = document.getElementById('modalQris');
    var wrap  = document.getElementById('qrWrap');
    var totalEl = document.getElementById('qrTotal');

    if (!modal) {
        alert('ERROR: elemen #modalQris tidak ditemukan!');
        return;
    }

    modal.classList.add('active');
    wrap.innerHTML = '<div class="qr-loading">Memuat QR Code...</div>';

    fetch('proses_qris.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                wrap.innerHTML = '<div class="qr-loading">Gagal: ' + data.error + '</div>';
                return;
            }
            totalEl.textContent = data.total_format;
            wrap.innerHTML = '<img src="' + data.qr_url + '" alt="QRIS">';
        })
        .catch(function(err) {
            console.error(err);
            wrap.innerHTML = '<div class="qr-loading">Koneksi error</div>';
        });
}

function tutupModalQris() {
    document.getElementById('modalQris').classList.remove('active');
}

function konfirmasiQRIS() {
    tutupModalQris();
    var btn = document.getElementById('btnSubmit');
    if (btn) {
        btn.textContent = 'Memproses...';
        btn.disabled = true;
    }
    document.getElementById('checkoutForm').submit();
}

window.bukaModalQris = bukaModalQris;
window.tutupModalQris = tutupModalQris;
window.konfirmasiQRIS = konfirmasiQRIS;