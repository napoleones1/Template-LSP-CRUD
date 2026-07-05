<?php
$page_title = 'Proses Penggajian';
require_once __DIR__ . '/../../includes/header.php';

// Ambil daftar karyawan
$karyawan_list = mysqli_query($conn, "
    SELECT k.id_karyawan, k.nik, k.nama_karyawan,
           j.nama_jabatan, j.gapok, j.tunjangan_makan
    FROM karyawan k
    JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    ORDER BY k.nama_karyawan ASC
");

$error       = '';
$preview     = null; // Data preview sebelum simpan

// -------------------------------------------------------
// Langkah 1: Ambil info karyawan (GET atau POST step 1)
// -------------------------------------------------------
$selected_karyawan = null;
$pinjaman_aktif    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $bulan       = (int)($_POST['bulan'] ?? 0);
    $tahun       = (int)($_POST['tahun'] ?? 0);
    $action      = $_POST['action'] ?? '';

    if ($id_karyawan > 0) {
        // Ambil data karyawan + jabatan
        $stmt_k = mysqli_prepare($conn, "
            SELECT k.id_karyawan, k.nik, k.nama_karyawan, k.tgl_masuk,
                   j.nama_jabatan, j.gapok, j.tunjangan_makan
            FROM karyawan k
            JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            WHERE k.id_karyawan = ?
        ");
        mysqli_stmt_bind_param($stmt_k, 'i', $id_karyawan);
        mysqli_stmt_execute($stmt_k);
        $res_k = mysqli_stmt_get_result($stmt_k);
        $selected_karyawan = mysqli_fetch_assoc($res_k);
        mysqli_stmt_close($stmt_k);

        // Ambil pinjaman aktif karyawan
        $stmt_p = mysqli_prepare($conn, "
            SELECT id_pinjaman, jumlah_pinjaman, tenor, cicilan_per_bulan
            FROM pinjaman
            WHERE id_karyawan = ? AND status = 'Aktif'
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt_p, 'i', $id_karyawan);
        mysqli_stmt_execute($stmt_p);
        $res_p = mysqli_stmt_get_result($stmt_p);
        $pinjaman_aktif = mysqli_fetch_assoc($res_p);
        mysqli_stmt_close($stmt_p);

        if ($selected_karyawan && $bulan > 0 && $tahun > 0) {
            $bulan_tahun       = sprintf('%02d', $bulan) . '-' . $tahun;
            $gapok             = (int)$selected_karyawan['gapok'];
            $tunjangan_makan   = (int)$selected_karyawan['tunjangan_makan'];
            $cicilan           = $pinjaman_aktif ? (int)$pinjaman_aktif['cicilan_per_bulan'] : 0;
            $gaji_bersih       = $gapok + $tunjangan_makan - $cicilan;

            $preview = [
                'id_karyawan'       => $id_karyawan,
                'nik'               => $selected_karyawan['nik'],
                'nama_karyawan'     => $selected_karyawan['nama_karyawan'],
                'nama_jabatan'      => $selected_karyawan['nama_jabatan'],
                'bulan_tahun'       => $bulan_tahun,
                'bulan'             => $bulan,
                'tahun'             => $tahun,
                'gapok'             => $gapok,
                'tunjangan_makan'   => $tunjangan_makan,
                'cicilan'           => $cicilan,
                'gaji_bersih'       => $gaji_bersih,
                'id_pinjaman'       => $pinjaman_aktif['id_pinjaman'] ?? null,
            ];

            // -------------------------------------------------------
            // Langkah 2: Simpan penggajian jika tombol Simpan ditekan
            // -------------------------------------------------------
            if ($action === 'simpan') {
                // Cek apakah sudah ada penggajian bulan ini untuk karyawan ini
                $cek_duplikat = mysqli_prepare($conn, "SELECT id_penggajian FROM penggajian WHERE id_karyawan = ? AND bulan_tahun = ?");
                mysqli_stmt_bind_param($cek_duplikat, 'is', $id_karyawan, $bulan_tahun);
                mysqli_stmt_execute($cek_duplikat);
                mysqli_stmt_store_result($cek_duplikat);
                $duplikat = mysqli_stmt_num_rows($cek_duplikat) > 0;
                mysqli_stmt_close($cek_duplikat);

                if ($duplikat) {
                    $error = "Penggajian untuk karyawan ini pada periode $bulan_tahun sudah pernah diproses.";
                } else {
                    // Simpan penggajian
                    $stmt_insert = mysqli_prepare($conn, "
                        INSERT INTO penggajian (id_karyawan, bulan_tahun, potongan_pinjaman, gaji_bersih)
                        VALUES (?, ?, ?, ?)
                    ");
                    mysqli_stmt_bind_param($stmt_insert, 'isii', $id_karyawan, $bulan_tahun, $cicilan, $gaji_bersih);

                    if (mysqli_stmt_execute($stmt_insert)) {
                        mysqli_stmt_close($stmt_insert);

                        // Update status pinjaman jika sudah lunas
                        // Hitung total bulan yang sudah dicicil berdasarkan penggajian
                        if ($pinjaman_aktif) {
                            $stmt_count = mysqli_prepare($conn, "
                                SELECT COUNT(*) as total_cicilan
                                FROM penggajian
                                WHERE id_karyawan = ? AND potongan_pinjaman > 0
                            ");
                            mysqli_stmt_bind_param($stmt_count, 'i', $id_karyawan);
                            mysqli_stmt_execute($stmt_count);
                            $res_count = mysqli_stmt_get_result($stmt_count);
                            $count_row = mysqli_fetch_assoc($res_count);
                            mysqli_stmt_close($stmt_count);

                            $total_cicilan_dibayar = (int)$count_row['total_cicilan'];
                            $tenor_pinjaman        = (int)$pinjaman_aktif['tenor'];

                            if ($total_cicilan_dibayar >= $tenor_pinjaman) {
                                $stmt_lunas = mysqli_prepare($conn, "UPDATE pinjaman SET status = 'Lunas' WHERE id_pinjaman = ?");
                                mysqli_stmt_bind_param($stmt_lunas, 'i', $pinjaman_aktif['id_pinjaman']);
                                mysqli_stmt_execute($stmt_lunas);
                                mysqli_stmt_close($stmt_lunas);
                            }
                        }

                        $_SESSION['msg'] = "Penggajian untuk {$selected_karyawan['nama_karyawan']} periode $bulan_tahun berhasil disimpan.";
                        header("Location: /pelatihan/sipeka/modul/penggajian/index.php");
                        exit;
                    } else {
                        $error = 'Gagal menyimpan penggajian: ' . mysqli_error($conn);
                    }
                }
            }
        } elseif ($selected_karyawan && ($bulan <= 0 || $tahun <= 0)) {
            $error = 'Pilih bulan dan tahun yang valid.';
        }
    }
}

$bulan_list = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
    4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
    10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Proses Penggajian</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h2>Proses Gaji Karyawan</h2>
            <a href="/pelatihan/sipeka/modul/penggajian/index.php" class="btn btn-secondary">&larr; Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Input -->
        <div class="card">
            <div class="card-header">
                <h3>&#128221; Pilih Karyawan &amp; Periode</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formProses">
                    <input type="hidden" name="action" id="form_action" value="preview">

                    <div class="form-group">
                        <label for="id_karyawan">Pilih Karyawan <span style="color:red">*</span></label>
                        <select id="id_karyawan" name="id_karyawan" required>
                            <option value="">-- Pilih Karyawan --</option>
                            <?php
                            mysqli_data_seek($karyawan_list, 0);
                            while ($k = mysqli_fetch_assoc($karyawan_list)):
                                $sel = ($preview['id_karyawan'] ?? $_POST['id_karyawan'] ?? '') == $k['id_karyawan'] ? 'selected' : '';
                            ?>
                            <option value="<?= $k['id_karyawan'] ?>" <?= $sel ?>>
                                <?= htmlspecialchars($k['nik']) ?> - <?= htmlspecialchars($k['nama_karyawan']) ?>
                                (<?= htmlspecialchars($k['nama_jabatan']) ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bulan">Bulan <span style="color:red">*</span></label>
                            <select id="bulan" name="bulan" required>
                                <option value="">-- Pilih Bulan --</option>
                                <?php foreach ($bulan_list as $num => $nama): ?>
                                    <option value="<?= $num ?>"
                                        <?= ($preview['bulan'] ?? $_POST['bulan'] ?? date('n')) == $num ? 'selected' : '' ?>>
                                        <?= $nama ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tahun">Tahun <span style="color:red">*</span></label>
                            <select id="tahun" name="tahun" required>
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>"
                                        <?= ($preview['tahun'] ?? $_POST['tahun'] ?? date('Y')) == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-info"
                                onclick="document.getElementById('form_action').value='preview'">
                            &#128270; Cek Info Gaji
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($preview && !$error): ?>
        <!-- Preview Gaji -->
        <div class="card">
            <div class="card-header">
                <h3>&#128203; Preview Slip Gaji</h3>
                <span class="badge badge-warning">Belum Disimpan</span>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <h4>&#128100; Informasi Karyawan</h4>
                    <table>
                        <tr><td>NIK</td><td>: <?= htmlspecialchars($preview['nik']) ?></td></tr>
                        <tr><td>Nama</td><td>: <?= htmlspecialchars($preview['nama_karyawan']) ?></td></tr>
                        <tr><td>Jabatan</td><td>: <?= htmlspecialchars($preview['nama_jabatan']) ?></td></tr>
                        <tr><td>Periode</td><td>: <?= htmlspecialchars($preview['bulan_tahun']) ?></td></tr>
                    </table>
                </div>

                <table style="width:100%;max-width:500px;border-collapse:collapse;font-size:14px;">
                    <thead>
                        <tr style="background:#2c3e50;color:#fff;">
                            <th style="padding:10px 15px;text-align:left;">Komponen</th>
                            <th style="padding:10px 15px;text-align:right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px 15px;">Gaji Pokok</td>
                            <td style="padding:10px 15px;text-align:right;color:#27ae60;">
                                + Rp <?= number_format($preview['gapok'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px 15px;">Tunjangan Makan</td>
                            <td style="padding:10px 15px;text-align:right;color:#27ae60;">
                                + Rp <?= number_format($preview['tunjangan_makan'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php if ($preview['cicilan'] > 0): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px 15px;">Cicilan Pinjaman</td>
                            <td style="padding:10px 15px;text-align:right;color:#e74c3c;">
                                - Rp <?= number_format($preview['cicilan'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px 15px;">Cicilan Pinjaman</td>
                            <td style="padding:10px 15px;text-align:right;color:#999;">Rp 0</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#2c3e50;color:#fff;">
                            <td style="padding:12px 15px;font-weight:700;">GAJI BERSIH</td>
                            <td style="padding:12px 15px;text-align:right;font-weight:700;font-size:16px;color:#27ae60;">
                                Rp <?= number_format($preview['gaji_bersih'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Tombol Simpan -->
                <form method="POST" action="" style="margin-top:20px;">
                    <input type="hidden" name="action" value="simpan">
                    <input type="hidden" name="id_karyawan" value="<?= $preview['id_karyawan'] ?>">
                    <input type="hidden" name="bulan" value="<?= $preview['bulan'] ?>">
                    <input type="hidden" name="tahun" value="<?= $preview['tahun'] ?>">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Yakin ingin menyimpan data penggajian ini?')">
                        &#10003; Simpan Penggajian
                    </button>
                    <a href="/pelatihan/sipeka/modul/penggajian/proses.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
