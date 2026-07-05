<?php
$page_title = 'Tambah Pinjaman';
require_once __DIR__ . '/../../includes/header.php';

// Ambil daftar karyawan
$karyawan_list = mysqli_query($conn, "SELECT id_karyawan, nik, nama_karyawan FROM karyawan ORDER BY nama_karyawan ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan      = (int)($_POST['id_karyawan'] ?? 0);
    $jumlah_pinjaman  = (int)($_POST['jumlah_pinjaman'] ?? 0);
    $tenor            = (int)($_POST['tenor'] ?? 0);
    $cicilan_per_bulan = (int)($_POST['cicilan_per_bulan'] ?? 0);

    if ($id_karyawan <= 0 || $jumlah_pinjaman <= 0 || $tenor <= 0) {
        $error = 'Semua field wajib diisi dengan nilai yang valid.';
    } else {
        // Hitung ulang cicilan di server
        $cicilan_per_bulan = (int)round($jumlah_pinjaman / $tenor);

        $stmt = mysqli_prepare($conn, "INSERT INTO pinjaman (id_karyawan, jumlah_pinjaman, tenor, cicilan_per_bulan, status) VALUES (?, ?, ?, ?, 'Aktif')");
        mysqli_stmt_bind_param($stmt, 'iiii', $id_karyawan, $jumlah_pinjaman, $tenor, $cicilan_per_bulan);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['msg'] = 'Data pinjaman berhasil ditambahkan.';
            mysqli_stmt_close($stmt);
            header("Location: /pelatihan/sipeka/modul/pinjaman/index.php");
            exit;
        } else {
            $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Tambah Pinjaman</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Tambah Data Pinjaman</h2>
            <a href="/pelatihan/sipeka/modul/pinjaman/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#128221; Form Tambah Pinjaman</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formPinjaman">
                    <div class="form-group">
                        <label for="id_karyawan">Karyawan <span style="color:red">*</span></label>
                        <select id="id_karyawan" name="id_karyawan" required>
                            <option value="">-- Pilih Karyawan --</option>
                            <?php
                            mysqli_data_seek($karyawan_list, 0);
                            while ($k = mysqli_fetch_assoc($karyawan_list)):
                                $selected = ($_POST['id_karyawan'] ?? '') == $k['id_karyawan'] ? 'selected' : '';
                            ?>
                            <option value="<?= $k['id_karyawan'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($k['nik']) ?> - <?= htmlspecialchars($k['nama_karyawan']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="jumlah_pinjaman">Jumlah Pinjaman (Rp) <span style="color:red">*</span></label>
                            <input type="number" id="jumlah_pinjaman" name="jumlah_pinjaman"
                                   placeholder="Contoh: 5000000"
                                   value="<?= htmlspecialchars($_POST['jumlah_pinjaman'] ?? '') ?>"
                                   min="1" required oninput="hitungCicilan()">
                        </div>
                        <div class="form-group">
                            <label for="tenor">Tenor (Bulan) <span style="color:red">*</span></label>
                            <input type="number" id="tenor" name="tenor"
                                   placeholder="Contoh: 12"
                                   value="<?= htmlspecialchars($_POST['tenor'] ?? '') ?>"
                                   min="1" max="60" required oninput="hitungCicilan()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cicilan_per_bulan">Cicilan per Bulan (Rp)</label>
                        <input type="number" id="cicilan_per_bulan" name="cicilan_per_bulan"
                               readonly placeholder="Otomatis dihitung"
                               value="<?= htmlspecialchars($_POST['cicilan_per_bulan'] ?? '') ?>">
                        <small style="color:#7f8c8d;font-size:12px;">Dihitung otomatis: Jumlah Pinjaman &divide; Tenor</small>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" value="Aktif" readonly style="background:#f5f5f5;color:#27ae60;font-weight:600;">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#10003; Simpan</button>
                        <a href="/pelatihan/sipeka/modul/pinjaman/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function hitungCicilan() {
    var jumlah = parseFloat(document.getElementById('jumlah_pinjaman').value) || 0;
    var tenor  = parseFloat(document.getElementById('tenor').value) || 0;
    var cicilan = 0;
    if (jumlah > 0 && tenor > 0) {
        cicilan = Math.round(jumlah / tenor);
    }
    document.getElementById('cicilan_per_bulan').value = cicilan > 0 ? cicilan : '';
}
// Run on page load if values exist
window.onload = function() { hitungCicilan(); };
</script>
</body>
</html>
