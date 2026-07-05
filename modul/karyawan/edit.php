<?php
$page_title = 'Edit Karyawan';
require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
    exit;
}

// Ambil data karyawan
$stmt = mysqli_prepare($conn, "SELECT * FROM karyawan WHERE id_karyawan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$karyawan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$karyawan) {
    $_SESSION['err'] = 'Data karyawan tidak ditemukan.';
    header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
    exit;
}

// Ambil daftar jabatan
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
        // Cek NIK unik (selain diri sendiri)
        $cek = mysqli_prepare($conn, "SELECT id_karyawan FROM karyawan WHERE nik = ? AND id_karyawan != ?");
        mysqli_stmt_bind_param($cek, 'si', $nik, $id);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        $nik_exists = mysqli_stmt_num_rows($cek) > 0;
        mysqli_stmt_close($cek);

        if ($nik_exists) {
            $error = "NIK '$nik' sudah digunakan oleh karyawan lain.";
        } else {
            $stmt2 = mysqli_prepare($conn, "UPDATE karyawan SET nik=?, nama_karyawan=?, id_jabatan=?, tgl_masuk=? WHERE id_karyawan=?");
            mysqli_stmt_bind_param($stmt2, 'ssisi', $nik, $nama_karyawan, $id_jabatan, $tgl_masuk, $id);

            if (mysqli_stmt_execute($stmt2)) {
                $_SESSION['msg'] = "Data karyawan '$nama_karyawan' berhasil diperbarui.";
                mysqli_stmt_close($stmt2);
                header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
                exit;
            } else {
                $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt2);
        }
    }

    // Update local display
    $karyawan['nik']           = $_POST['nik'] ?? '';
    $karyawan['nama_karyawan'] = $_POST['nama_karyawan'] ?? '';
    $karyawan['id_jabatan']    = $_POST['id_jabatan'] ?? '';
    $karyawan['tgl_masuk']     = $_POST['tgl_masuk'] ?? '';
}
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Edit Karyawan</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Edit Data Karyawan</h2>
            <a href="/pelatihan/sipeka/modul/karyawan/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#9998; Form Edit Karyawan</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nik">NIK <span style="color:red">*</span></label>
                            <input type="text" id="nik" name="nik"
                                   value="<?= htmlspecialchars($karyawan['nik']) ?>"
                                   required maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="nama_karyawan">Nama Karyawan <span style="color:red">*</span></label>
                            <input type="text" id="nama_karyawan" name="nama_karyawan"
                                   value="<?= htmlspecialchars($karyawan['nama_karyawan']) ?>"
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
                                    $selected = $karyawan['id_jabatan'] == $j['id_jabatan'] ? 'selected' : '';
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
                                   value="<?= htmlspecialchars($karyawan['tgl_masuk']) ?>"
                                   required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#10003; Simpan Perubahan</button>
                        <a href="/pelatihan/sipeka/modul/karyawan/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
