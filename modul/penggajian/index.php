<?php
$page_title = 'Data Penggajian';
require_once __DIR__ . '/../../includes/header.php';

$penggajian_list = mysqli_query($conn, "
    SELECT p.id_penggajian, p.bulan_tahun, p.potongan_pinjaman, p.gaji_bersih,
           k.nama_karyawan, k.nik
    FROM penggajian p
    JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    ORDER BY p.id_penggajian DESC
");
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Data Penggajian</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['msg']) ?></div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['err'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['err']) ?></div>
            <?php unset($_SESSION['err']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#128200; Riwayat Penggajian</h3>
                <a href="/pelatihan/sipeka/modul/penggajian/proses.php" class="btn btn-primary">&#43; Proses Gaji Baru</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Periode (Bulan/Tahun)</th>
                                <th>Potongan Pinjaman</th>
                                <th>Gaji Bersih</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($penggajian_list) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($penggajian_list)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nik']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_karyawan']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['bulan_tahun']) ?></td>
                                        <td>
                                            <?php if ($row['potongan_pinjaman'] > 0): ?>
                                                <span style="color:#e74c3c;">- Rp <?= number_format($row['potongan_pinjaman'], 0, ',', '.') ?></span>
                                            <?php else: ?>
                                                <span style="color:#999;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="color:#27ae60;">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <a href="/pelatihan/sipeka/laporan/slip_gaji.php?id=<?= $row['id_penggajian'] ?>"
                                               class="btn btn-info btn-sm">&#128203; Slip</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:20px;color:#999;">
                                        Belum ada data penggajian. <a href="/pelatihan/sipeka/modul/penggajian/proses.php">Proses gaji sekarang</a>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
