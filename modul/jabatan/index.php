<?php
$page_title = 'Data Jabatan';
require_once __DIR__ . '/../../includes/header.php';

$jabatan_list = mysqli_query($conn, "SELECT * FROM jabatan ORDER BY id_jabatan ASC");
?>

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Data Jabatan</h1>
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
                <h3>&#128196; Daftar Jabatan</h3>
                <a href="/pelatihan/sipeka/modul/jabatan/tambah.php" class="btn btn-primary">
                    &#43; Tambah Jabatan
                </a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Nama Jabatan</th>
                                <th>Gaji Pokok</th>
                                <th>Tunjangan Makan</th>
                                <th>Total Pendapatan</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($jabatan_list) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($jabatan_list)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_jabatan']) ?></strong></td>
                                        <td>Rp <?= number_format($row['gapok'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['tunjangan_makan'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['gapok'] + $row['tunjangan_makan'], 0, ',', '.') ?></td>
                                        <td>
                                            <a href="/pelatihan/sipeka/modul/jabatan/edit.php?id=<?= $row['id_jabatan'] ?>"
                                               class="btn btn-warning btn-sm">Edit</a>
                                            <a href="/pelatihan/sipeka/modul/jabatan/hapus.php?id=<?= $row['id_jabatan'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin ingin menghapus jabatan ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:20px;color:#999;">
                                        Belum ada data jabatan.
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
