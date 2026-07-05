<?php
$page_title = 'Edit Pinjaman';
require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /pelatihan/sipeka/modul/pinjaman/index.php");
    exit;
}

// Ambil data pinjaman
$stmt = mysqli_prepare($conn, "SELECT * FROM pinjaman WHERE id_pinjaman = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pinjaman = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pinjaman) {
    $_SESSION['err'] = 'Data pinjaman tidak ditemukan.';
    header("Location: /pelatihan/sipeka/modul/pinjaman/index.php");
    exit;
}

// Ambil daftar karyawan
$karyawan_list = mysqli_query($conn, "SELECT id_karyawan, nik, nama_karyawan FROM karyawan ORDER BY nama_karyawan ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan       = (int)($_POST['id_karyawan'] ?? 0);
    $jumlah_pinjaman   = (int)($_POST['jumlah_pinjaman'] ?? 0);
    $tenor             = (int)($_POST['tenor'] ?? 0);
    $status            = in_array($_POST['status'] ?? '', ['Aktif','Lunas']) ? $_POST['status'] : 'Aktif';

    if ($id_karyawan <= 0 || $jumlah_pinjaman <= 0 || $tenor <= 0) {
        $error = 'Semua field wajib diisi dengan nilai yang valid.';
    } else {
        $cicilan_per_bulan = (int)round($jumlah_pinjaman / $tenor);

        $stmt2 = mysqli_prepare($conn, "UPDATE pinjaman SET id_karyawan=?, jumlah_pinjaman=?, tenor=?, cicilan_per_bulan=?, status=? WHERE id_pinjaman=?");
        mysqli_stmt_bind_param($stmt2, 'iiiisi', $id_karyawan, $jumlah_pinjaman, $tenor, $cicilan_per_bulan, $status, $id);

        if (mysqli_stmt_execute($stmt2)) {
            $_SESSION['msg'] = 'Data pinjaman berhasil diperbarui.';
            mysqli_stmt_close($stmt2);
            header("Location: /pelatihan/sipeka/modul/pinjaman/index.php");
            exit;
        } else {
            $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt2);
    }

    // Update local display
    $pinjaman['id_karyawan']      = $_POST['id_karyawan'] ?? '';
    $pinjaman['jumlah_pinjaman']  = $_POST['jumlah_pinjaman'] ?? '';
    $pinjaman['tenor']            = $_POST['tenor'] ?? '';
    $pinjaman['status']           = $_POST['status'] ?? 'Aktif';
}
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Edit Pinjaman</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Edit Data Pinjaman</h2>
            <a href="/pelatihan/sipeka/modul/pinjaman/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#9998; Form Edit Pinjaman</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="id_karyawan">Karyawan <span style="color:red">*</span></label>
                        <select id="id_karyawan" name="id_karyawan" required>
                            <option value="">-- Pilih Karyawan --</option>
                            <?php
                            mysqli_data_seek($karyawan_list, 0);
                            while ($k = mysqli_fetch_assoc($karyawan_list)):
                                $selected = $pinjaman['id_karyawan'] == $k['id_karyawan'] ? 'selected' : '';
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
                                   value="<?= htmlspecialchars($pinjaman['jumlah_pinjaman']) ?>"
                                   min="1" required oninput="hitungCicilan()">
                        </div>
                        <div class="form-group">
                            <label for="tenor">Tenor (Bulan) <span style="color:red">*</span></label>
                            <input type="number" id="tenor" name="tenor"
                                   value="<?= htmlspecialchars($pinjaman['tenor']) ?>"
                                   min="1" max="60" required oninput="hitungCicilan()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cicilan_per_bulan">Cicilan per Bulan (Rp)</label>
                        <input type="number" id="cicilan_per_bulan" name="cicilan_per_bulan"
                               value="<?= htmlspecialchars($pinjaman['cicilan_per_bulan']) ?>"
                               readonly>
                        <small style="color:#7f8c8d;font-size:12px;">Dihitung otomatis: Jumlah Pinjaman &divide; Tenor</small>
                    </div>
                    <div class="form-group">
                        <label for="status">Status <span style="color:red">*</span></label>
                        <select id="status" name="status" required>
                            <option value="Aktif" <?= $pinjaman['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Lunas" <?= $pinjaman['status'] === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#10003; Simpan Perubahan</button>
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
window.onload = function() { hitungCicilan(); };
</script>
</body>
</html>
