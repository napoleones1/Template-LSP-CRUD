<?php
$page_title = 'Data Pinjaman';
require_once __DIR__ . '/../../includes/header.php';

$pinjaman_list = mysqli_query($conn, "
    SELECT p.id_pinjaman, p.jumlah_pinjaman, p.tenor, p.cicilan_per_bulan, p.status,
           k.nama_karyawan, k.nik
    FROM pinjaman p
    JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    ORDER BY p.id_pinjaman DESC
");
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Data Pinjaman</h1>
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
                <h3>&#128197; Daftar Pinjaman Karyawan</h3>
                <a href="/pelatihan/sipeka/modul/pinjaman/tambah.php" class="btn btn-primary">&#43; Tambah Pinjaman</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Jumlah Pinjaman</th>
                                <th>Tenor</th>
                                <th>Cicilan/Bulan</th>
                                <th>Status</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($pinjaman_list) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($pinjaman_list)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nik']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_karyawan']) ?></strong></td>
                                        <td>Rp <?= number_format($row['jumlah_pinjaman'], 0, ',', '.') ?></td>
                                        <td><?= $row['tenor'] ?> bulan</td>
                                        <td>Rp <?= number_format($row['cicilan_per_bulan'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'Aktif'): ?>
                                                <span class="badge badge-danger">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Lunas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/pelatihan/sipeka/modul/pinjaman/edit.php?id=<?= $row['id_pinjaman'] ?>"
                                               class="btn btn-warning btn-sm">Edit</a>
                                            <a href="/pelatihan/sipeka/modul/pinjaman/hapus.php?id=<?= $row['id_pinjaman'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin ingin menghapus data pinjaman ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:20px;color:#999;">
                                        Belum ada data pinjaman.
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
