<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// --- Statistik ---
// Total Karyawan
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM karyawan");
$total_karyawan = mysqli_fetch_assoc($res)['total'];

// Total Gaji Bersih Bulan Ini
$bulan_ini = date('m-Y');
$stmt = mysqli_prepare($conn, "SELECT SUM(gaji_bersih) as total_gaji FROM penggajian WHERE bulan_tahun = ?");
mysqli_stmt_bind_param($stmt, 's', $bulan_ini);
mysqli_stmt_execute($stmt);
$res2 = mysqli_stmt_get_result($stmt);
$total_gaji = mysqli_fetch_assoc($res2)['total_gaji'] ?? 0;
mysqli_stmt_close($stmt);

// Pinjaman Aktif
$res3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM pinjaman WHERE status = 'Aktif'");
$pinjaman_aktif = mysqli_fetch_assoc($res3)['total'];

// 5 Penggajian Terbaru
$res4 = mysqli_query($conn, "
    SELECT p.id_penggajian, k.nama_karyawan, k.nik, p.bulan_tahun,
           p.potongan_pinjaman, p.gaji_bersih
    FROM penggajian p
    JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    ORDER BY p.id_penggajian DESC
    LIMIT 5
");
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Dashboard</h1>
        <div class="user-info">Selamat datang, <span><?= htmlspecialchars($_SESSION['user']) ?></span> &mdash; <?= date('l, d F Y') ?></div>
    </div>

    <div class="content-area">

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['msg']) ?></div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">&#128100;</div>
                <div class="stat-info">
                    <h4>Total Karyawan</h4>
                    <div class="stat-value"><?= $total_karyawan ?></div>
                    <div class="stat-sub">karyawan terdaftar</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">&#128176;</div>
                <div class="stat-info">
                    <h4>Penggajian Bulan Ini</h4>
                    <div class="stat-value" style="font-size:18px;">Rp <?= number_format($total_gaji, 0, ',', '.') ?></div>
                    <div class="stat-sub">periode <?= date('F Y') ?></div>
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">&#128197;</div>
                <div class="stat-info">
                    <h4>Pinjaman Aktif</h4>
                    <div class="stat-value"><?= $pinjaman_aktif ?></div>
                    <div class="stat-sub">pinjaman berjalan</div>
                </div>
            </div>
        </div>

        <!-- Tabel Penggajian Terbaru -->
        <div class="card">
            <div class="card-header">
                <h3>&#128203; Penggajian Terbaru</h3>
                <a href="/pelatihan/sipeka/modul/penggajian/index.php" class="btn btn-primary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Periode</th>
                                <th>Potongan</th>
                                <th>Gaji Bersih</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($res4) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($res4)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nik']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                                        <td><?= htmlspecialchars($row['bulan_tahun']) ?></td>
                                        <td>Rp <?= number_format($row['potongan_pinjaman'], 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <a href="/pelatihan/sipeka/laporan/slip_gaji.php?id=<?= $row['id_penggajian'] ?>"
                                               class="btn btn-info btn-sm">Slip Gaji</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:20px;color:#999;">
                                        Belum ada data penggajian.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /content-area -->
</div><!-- /main-content -->
</body>
</html>
