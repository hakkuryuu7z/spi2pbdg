
document.addEventListener('DOMContentLoaded', function () {
    // Cari semua elemen dengan class 'confirm-link'
    const confirmLinks = document.querySelectorAll('.confirm-link');

    confirmLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Mencegah link berpindah halaman secara langsung
            e.preventDefault();

            // Ambil URL tujuan dari atribut 'data-url'
            const url = this.getAttribute('data-url');
            // Ambil nama menu dari tag <p> di dalamnya
            const menuName = this.querySelector('p').innerText;

            Swal.fire({
                title: 'Konfirmasi',
                text: `Anda akan membuka halaman ${menuName}. Lanjutkan?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff', // Biru
                cancelButtonColor: '#dc3545', // Merah
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika pengguna mengklik tombol "Ya"
                if (result.isConfirmed) {
                    // Arahkan ke URL tujuan
                    window.location.href = url;
                }
            });
        });
    });
});
