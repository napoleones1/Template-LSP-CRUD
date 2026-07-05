<?php
$page_title = 'Data Karyawan';
require_once __DIR__ . '/../../includes/header.php';

$karyawan_list = mysqli_query($conn, "
    SELECT k.id_karyawan, k.nik, k.nama_karyawan, k.tgl_masuk,
           j.nama_jabatan, j.gapok, j.tunjangan_makan
    FROM karyawan k
    JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    ORDER BY k.id_karyawan ASC
");
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Data Karyawan</h1>
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
                <h3>&#128100; Daftar Karyawan</h3>
                <a href="/pelatihan/sipeka/modul/karyawan/tambah.php" class="btn btn-primary">&#43; Tambah Karyawan</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Gaji Pokok</th>
                                <th>Tgl Masuk</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($karyawan_list) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($karyawan_list)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nik']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_karyawan']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                                        <td>Rp <?= number_format($row['gapok'], 0, ',', '.') ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?></td>
                                        <td>
                                            <a href="/pelatihan/sipeka/modul/karyawan/edit.php?id=<?= $row['id_karyawan'] ?>"
                                               class="btn btn-warning btn-sm">Edit</a>
                                            <a href="/pelatihan/sipeka/modul/karyawan/hapus.php?id=<?= $row['id_karyawan'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin ingin menghapus karyawan ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:20px;color:#999;">
                                        Belum ada data karyawan.
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
