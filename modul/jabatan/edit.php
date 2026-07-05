<?php
$page_title = 'Edit Jabatan';
require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
    exit;
}

// Ambil data jabatan
$stmt = mysqli_prepare($conn, "SELECT * FROM jabatan WHERE id_jabatan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$jabatan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$jabatan) {
    $_SESSION['err'] = 'Data jabatan tidak ditemukan.';
    header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jabatan    = trim($_POST['nama_jabatan'] ?? '');
    $gapok           = (int)($_POST['gapok'] ?? 0);
    $tunjangan_makan = (int)($_POST['tunjangan_makan'] ?? 0);

    if (empty($nama_jabatan)) {
        $error = 'Nama jabatan tidak boleh kosong.';
    } elseif ($gapok < 0 || $tunjangan_makan < 0) {
        $error = 'Gaji pokok dan tunjangan tidak boleh bernilai negatif.';
    } else {
        $stmt2 = mysqli_prepare($conn, "UPDATE jabatan SET nama_jabatan=?, gapok=?, tunjangan_makan=? WHERE id_jabatan=?");
        mysqli_stmt_bind_param($stmt2, 'siii', $nama_jabatan, $gapok, $tunjangan_makan, $id);

        if (mysqli_stmt_execute($stmt2)) {
            $_SESSION['msg'] = "Jabatan '$nama_jabatan' berhasil diperbarui.";
            mysqli_stmt_close($stmt2);
            header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
            exit;
        } else {
            $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt2);
    }

    // Update local for re-display
    $jabatan['nama_jabatan']    = $_POST['nama_jabatan'] ?? '';
    $jabatan['gapok']           = $_POST['gapok'] ?? '';
    $jabatan['tunjangan_makan'] = $_POST['tunjangan_makan'] ?? '';
}
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Edit Jabatan</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Edit Data Jabatan</h2>
            <a href="/pelatihan/sipeka/modul/jabatan/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#9998; Form Edit Jabatan</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nama_jabatan">Nama Jabatan <span style="color:red">*</span></label>
                        <input type="text" id="nama_jabatan" name="nama_jabatan"
                               value="<?= htmlspecialchars($jabatan['nama_jabatan']) ?>"
                               required maxlength="50">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gapok">Gaji Pokok (Rp) <span style="color:red">*</span></label>
                            <input type="number" id="gapok" name="gapok"
                                   value="<?= htmlspecialchars($jabatan['gapok']) ?>"
                                   min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="tunjangan_makan">Tunjangan Makan (Rp) <span style="color:red">*</span></label>
                            <input type="number" id="tunjangan_makan" name="tunjangan_makan"
                                   value="<?= htmlspecialchars($jabatan['tunjangan_makan']) ?>"
                                   min="0" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#10003; Simpan Perubahan</button>
                        <a href="/pelatihan/sipeka/modul/jabatan/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
