<?php
$page_title = 'Tambah Jabatan';
require_once __DIR__ . '/../../includes/header.php';

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
        $stmt = mysqli_prepare($conn, "INSERT INTO jabatan (nama_jabatan, gapok, tunjangan_makan) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sii', $nama_jabatan, $gapok, $tunjangan_makan);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['msg'] = "Jabatan '$nama_jabatan' berhasil ditambahkan.";
            mysqli_stmt_close($stmt);
            header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
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
        <h1>Tambah Jabatan</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Tambah Data Jabatan</h2>
            <a href="/pelatihan/sipeka/modul/jabatan/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#128221; Form Tambah Jabatan</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nama_jabatan">Nama Jabatan <span style="color:red">*</span></label>
                        <input type="text" id="nama_jabatan" name="nama_jabatan"
                               placeholder="Contoh: Staff Admin"
                               value="<?= htmlspecialchars($_POST['nama_jabatan'] ?? '') ?>"
                               required maxlength="50">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gapok">Gaji Pokok (Rp) <span style="color:red">*</span></label>
                            <input type="number" id="gapok" name="gapok"
                                   placeholder="Contoh: 5000000"
                                   value="<?= htmlspecialchars($_POST['gapok'] ?? '') ?>"
                                   min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="tunjangan_makan">Tunjangan Makan (Rp) <span style="color:red">*</span></label>
                            <input type="number" id="tunjangan_makan" name="tunjangan_makan"
                                   placeholder="Contoh: 750000"
                                   value="<?= htmlspecialchars($_POST['tunjangan_makan'] ?? '') ?>"
                                   min="0" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#10003; Simpan</button>
                        <a href="/pelatihan/sipeka/modul/jabatan/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
