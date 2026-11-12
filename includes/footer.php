</main>

<!-- ══════════════════════════════════════════════════════════════════
     GLOBAL POP-UP TOAST NOTIFICATION CONTAINER
     Komponen melayang (fixed) di tengah atas layar.
     Dipanggil via window.tampilkanToast(pesan, tipe, durasi) dari halaman manapun.
     z-index: 1095 (di atas modal Bootstrap 1055, di bawah tooltip 1070+)
     ══════════════════════════════════════════════════════════════════ -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1095; pointer-events: none;">
    <div id="globalAppToast" class="toast align-items-center border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true" style="pointer-events: auto; min-width: 280px; max-width: 420px;">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 fw-semibold py-3 px-3" id="globalAppToastBody">
                <!-- Pesan diinjeksikan via JS -->
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup" id="globalAppToastClose"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ═══════════════════════════════════════════════════════════════════════
   GLOBAL TOAST HELPER — window.tampilkanToast(pesan, tipe, durasi)
   ───────────────────────────────────────────────────────────────────────
   Paramter:
     pesan   : string   — Teks yang ditampilkan di dalam toast.
     tipe    : string   — 'success' | 'danger' | 'warning' | 'info'
                          Menentukan warna latar & warna teks.
     durasi  : number   — Milidetik sebelum toast auto-dismiss (default 3500).

   Cara Pakai (dari halaman mana pun setelah footer.php di-include):
     tampilkanToast('✅ Data berhasil disimpan.', 'success');
     tampilkanToast('❌ Terjadi kesalahan.', 'danger');
     tampilkanToast('⚠️ Perhatikan pesan ini.', 'warning', 5000);
   ═══════════════════════════════════════════════════════════════════════ */
window.tampilkanToast = function(pesan, tipe, durasi) {
    tipe   = tipe   || 'success';
    durasi = durasi || 3500;

    var toastEl  = document.getElementById('globalAppToast');
    var toastBody = document.getElementById('globalAppToastBody');
    var closeBtn  = document.getElementById('globalAppToastClose');

    if (!toastEl || !toastBody) return;

    // Pemetaan tipe → class warna Bootstrap
    var peta_warna = {
        'success': { bg: 'text-bg-success', close: 'btn-close-white' },
        'danger' : { bg: 'text-bg-danger',  close: 'btn-close-white' },
        'warning': { bg: 'text-bg-warning', close: ''               },
        'info'   : { bg: 'text-bg-info',    close: ''               }
    };
    var config = peta_warna[tipe] || peta_warna['success'];

    // Reset semua kelas warna lama
    toastEl.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-info');
    if (closeBtn) {
        closeBtn.classList.remove('btn-close-white');
    }

    // Terapkan kelas warna baru
    toastEl.classList.add(config.bg);
    if (closeBtn && config.close) {
        closeBtn.classList.add(config.close);
    }

    // Injeksi pesan
    toastBody.textContent = pesan;

    // Inisialisasi & tampilkan via Bootstrap Toast API
    var bsToast = bootstrap.Toast.getOrCreateInstance(toastEl, {
        autohide : true,
        delay    : durasi
    });
    bsToast.show();
};

/* ═══════════════════════════════════════════════════════════════════════
   AUTO-SHOW TOAST dari parameter URL (?pesan=... atau ?gagal=...)
   Dibaca saat DOMContentLoaded agar bisa merender notifikasi dari
   redirect server-side tanpa perlu mencetak <div class="alert"> statis.
   ═══════════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    var pesan  = params.get('pesan');
    var gagal  = params.get('gagal');

    // Pemetaan nilai parameter → pesan teks & tipe
    var peta_pesan = {
        'tambah'       : { teks: '✅ Menu berhasil ditambahkan.',                     tipe: 'success' },
        'edit'         : { teks: '✅ Menu berhasil diperbarui.',                       tipe: 'success' },
        'hapus'        : { teks: '🗑️ Menu berhasil dihapus.',                         tipe: 'warning' },
        'biaya'        : { teks: '✅ Pengaturan pajak & biaya layanan berhasil disimpan.', tipe: 'success' },
        'banner_tambah': { teks: '✅ Banner berhasil ditambahkan.',                   tipe: 'success' },
        'banner_hapus' : { teks: '🗑️ Banner berhasil dihapus.',                       tipe: 'warning' },
        'banner_toggle': { teks: 'ℹ️ Status banner diperbarui.',                       tipe: 'info'    },
        'banner_urutan': { teks: 'ℹ️ Urutan banner diperbarui.',                       tipe: 'info'    },
        'update'       : { teks: '✅ Status pesanan berhasil diperbarui.',             tipe: 'success' },
    };

    if (pesan && peta_pesan[pesan]) {
        setTimeout(function() {
            tampilkanToast(peta_pesan[pesan].teks, peta_pesan[pesan].tipe);
        }, 300); // Sedikit delay agar DOM benar-benar siap
    }

    if (gagal) {
        var detail = params.get('detail') || 'Terjadi kesalahan. Silakan coba lagi.';
        setTimeout(function() {
            tampilkanToast('❌ ' + detail, 'danger', 5000);
        }, 300);
    }
});
</script>
</body>
</html>