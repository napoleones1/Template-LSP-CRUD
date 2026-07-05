<?php
$page_title = 'Tambah Karyawan';
require_once __DIR__ . '/../../includes/header.php';

// Ambil daftar jabatan untuk dropdown
$jabatan_list = mysqli_query($conn, "SELECT id_jabatan, nama_jabatan, gapok FROM jabatan ORDER BY nama_jabatan ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik           = trim($_POST['nik'] ?? '');
    $nama_karyawan = trim($_POST['nama_karyawan'] ?? '');
    $id_jabatan    = (int)($_POST['id_jabatan'] ?? 0);
    $tgl_masuk     = $_POST['tgl_masuk'] ?? '';

    if (empty($nik) || empty($nama_karyawan) || $id_jabatan <= 0 || empty($tgl_masuk)) {
        $error = 'Semua field wajib diisi.';
    } else {
        // Cek NIK unik
        $cek = mysqli_prepare($conn, "SELECT id_karyawan FROM karyawan WHERE nik = ?");
        mysqli_stmt_bind_param($cek, 's', $nik);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        $nik_exists = mysqli_stmt_num_rows($cek) > 0;
        mysqli_stmt_close($cek);

        if ($nik_exists) {
            $error = "NIK '$nik' sudah terdaftar. Gunakan NIK yang berbeda.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO karyawan (nik, nama_karyawan, id_jabatan, tgl_masuk) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssis', $nik, $nama_karyawan, $id_jabatan, $tgl_masuk);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = "Karyawan '$nama_karyawan' berhasil ditambahkan.";
                mysqli_stmt_close($stmt);
                header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
                exit;
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Tambah Karyawan</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Tambah Data Karyawan</h2>
            <a href="/pelatihan/sipeka/modul/karyawan/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#128221; Form Tambah Karyawan</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nik">NIK <span style="color:red">*</span></label>
                            <input type="text" id="nik" name="nik"
                                   placeholder="Contoh: EMP-007"
                                   value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                                   required maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="nama_karyawan">Nama Karyawan <span style="color:red">*</span></label>
                            <input type="text" id="nama_karyawan" name="nama_karyawan"
                                   placeholder="Nama lengkap"
                                   value="<?= htmlspecialchars($_POST['nama_karyawan'] ?? '') ?>"
                                   required maxlength="100">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_jabatan">Jabatan <span style="color:red">*</span></label>
                            <select id="id_jabatan" name="id_jabatan" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <?php
                                mysqli_data_seek($jabatan_list, 0);
                                while ($j = mysqli_fetch_assoc($jabatan_list)):
                                    $selected = ($_POST['id_jabatan'] ?? '') == $j['id_jabatan'] ? 'selected' : '';
                                ?>
                                <option value="<?= $j['id_jabatan'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($j['nama_jabatan']) ?>
                                    (Rp <?= number_format($j['gapok'], 0, ',', '.') ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tgl_masuk">Tanggal Masuk <span style="color:red">*</span></label>
                            <input type="date" id="tgl_masuk" name="tgl_masuk"
                                   value="<?= htmlspecialchars($_POST['tgl_masuk'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#10003; Simpan</button>
                        <a href="/pelatihan/sipeka/modul/karyawan/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
