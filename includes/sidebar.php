<?php
// Determine current page for active state
$current_page = $_SERVER['PHP_SELF'];
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>SIPEKA</h2>
        <p>Sistem Informasi Penggajian</p>
    </div>

    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="/pelatihan/sipeka/index.php"
           class="<?= strpos($current_page, '/pelatihan/sipeka/index.php') !== false ? 'active' : '' ?>">
            &#127968; &nbsp;Dashboard
        </a>

        <!-- Master Data -->
        <div class="nav-section-title">Master Data</div>

        <div class="has-submenu <?= (strpos($current_page, '/modul/jabatan') !== false) ? 'open' : '' ?>"
             onclick="toggleMenu(this)">
            <a class="nav-label">&#128196; &nbsp;Master Data <span class="arrow">&#9660;</span></a>
            <div class="sub-menu <?= (strpos($current_page, '/modul/jabatan') !== false || strpos($current_page, '/modul/karyawan') !== false) ? 'open' : '' ?>">
                <a href="/pelatihan/sipeka/modul/jabatan/index.php"
                   class="<?= strpos($current_page, '/modul/jabatan') !== false ? 'active' : '' ?>">
                    &bull; &nbsp;Data Jabatan
                </a>
                <a href="/pelatihan/sipeka/modul/karyawan/index.php"
                   class="<?= strpos($current_page, '/modul/karyawan') !== false ? 'active' : '' ?>">
                    &bull; &nbsp;Data Karyawan
                </a>
            </div>
        </div>

        <!-- Transaksi -->
        <div class="nav-section-title">Transaksi</div>

        <div class="has-submenu <?= (strpos($current_page, '/modul/penggajian') !== false || strpos($current_page, '/modul/pinjaman') !== false) ? 'open' : '' ?>"
             onclick="toggleMenu(this)">
            <a class="nav-label">&#128200; &nbsp;Transaksi <span class="arrow">&#9660;</span></a>
            <div class="sub-menu <?= (strpos($current_page, '/modul/penggajian') !== false || strpos($current_page, '/modul/pinjaman') !== false) ? 'open' : '' ?>">
                <a href="/pelatihan/sipeka/modul/penggajian/index.php"
                   class="<?= strpos($current_page, '/modul/penggajian') !== false ? 'active' : '' ?>">
                    &bull; &nbsp;Penggajian
                </a>
                <a href="/pelatihan/sipeka/modul/pinjaman/index.php"
                   class="<?= strpos($current_page, '/modul/pinjaman') !== false ? 'active' : '' ?>">
                    &bull; &nbsp;Pinjaman
                </a>
            </div>
        </div>

        <!-- Laporan -->
        <div class="nav-section-title">Laporan</div>
        <a href="/pelatihan/sipeka/laporan/index.php"
           class="<?= strpos($current_page, '/laporan/') !== false ? 'active' : '' ?>">
            &#128203; &nbsp;Laporan Gaji
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/pelatihan/sipeka/logout.php">&#128682; &nbsp;Logout</a>
    </div>
</div>

<script>
function toggleMenu(el) {
    el.classList.toggle('open');
    const sub = el.querySelector('.sub-menu');
    if (sub) sub.classList.toggle('open');
}
</script>
