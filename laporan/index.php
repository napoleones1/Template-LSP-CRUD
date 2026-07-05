<?php
$page_title = 'Laporan Gaji';
require_once __DIR__ . '/../includes/header.php';

// Filter bulan & tahun (terpisah sesuai Modul 3)
$filter_bulan = trim($_GET['bulan'] ?? '');
$filter_tahun = trim($_GET['tahun'] ?? '');
$filter       = '';
if ($filter_bulan !== '' && $filter_tahun !== '') {
    $filter = sprintf('%02d', $filter_bulan).'-'.$filter_tahun;
}

// Ambil daftar tahun unik untuk dropdown
$res_tahun = mysqli_query($conn, "SELECT DISTINCT SUBSTRING(bulan_tahun,4) as tahun FROM penggajian ORDER BY tahun DESC");

// Query utama
if ($filter !== '') {
    $stmt = mysqli_prepare($conn, "
        SELECT p.id_penggajian, p.bulan_tahun, p.potongan_pinjaman, p.gaji_bersih,
               k.nik, k.nama_karyawan, j.nama_jabatan
        FROM penggajian p
        JOIN karyawan k ON p.id_karyawan = k.id_karyawan
        JOIN jabatan j  ON k.id_jabatan  = j.id_jabatan
        WHERE p.bulan_tahun = ?
        ORDER BY p.id_penggajian DESC
    ");
    mysqli_stmt_bind_param($stmt, 's', $filter);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, "
        SELECT p.id_penggajian, p.bulan_tahun, p.potongan_pinjaman, p.gaji_bersih,
               k.nik, k.nama_karyawan, j.nama_jabatan
        FROM penggajian p
        JOIN karyawan k ON p.id_karyawan = k.id_karyawan
        JOIN jabatan j  ON k.id_jabatan  = j.id_jabatan
        ORDER BY p.id_penggajian DESC
    ");
}

// Hitung total
$total_gaji     = 0;
$total_potongan = 0;
$rows = [];
while ($row = mysqli_fetch_assoc($res)) {
    $total_gaji     += $row['gaji_bersih'];
    $total_potongan += $row['potongan_pinjaman'];
    $rows[] = $row;
}
?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Laporan Gaji</h1>
        <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
    </div>

    <div class="content-area">

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['msg']) ?></div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>

        <!-- Filter -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:15px 20px;">
                <form method="GET" action="" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label style="font-weight:600;font-size:13px;color:#555;">Filter Periode:</label>

                    <!-- Dropdown Bulan -->
                    <select name="bulan" style="padding:8px 12px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                        <option value="">-- Pilih Bulan --</option>
                        <?php
                        $nm_bln = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                                   7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                        foreach ($nm_bln as $num => $nama):
                        ?>
                        <option value="<?= $num ?>" <?= $filter_bulan == $num ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Dropdown Tahun -->
                    <select name="tahun" style="padding:8px 12px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                        <option value="">-- Pilih Tahun --</option>
                        <?php while ($ty = mysqli_fetch_assoc($res_tahun)): ?>
                        <option value="<?= $ty['tahun'] ?>" <?= $filter_tahun == $ty['tahun'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ty['tahun']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">&#128269; Tampilkan</button>
                    <?php if ($filter): ?>
                        <a href="/pelatihan/sipeka/laporan/index.php" class="btn btn-secondary btn-sm">&#10005; Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
            <div class="stat-card blue">
                <div class="stat-icon">&#128203;</div>
                <div class="stat-info">
                    <h4>Total Slip</h4>
                    <div class="stat-value"><?= count($rows) ?></div>
                    <div class="stat-sub">data penggajian</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">&#128176;</div>
                <div class="stat-info">
                    <h4>Total Gaji Bersih</h4>
                    <div class="stat-value" style="font-size:16px;">Rp <?= number_format($total_gaji,0,',','.') ?></div>
                    <div class="stat-sub"><?= $filter ? 'periode ini' : 'semua periode' ?></div>
                </div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">&#9888;</div>
                <div class="stat-info">
                    <h4>Total Potongan</h4>
                    <div class="stat-value" style="font-size:16px;">Rp <?= number_format($total_potongan,0,',','.') ?></div>
                    <div class="stat-sub">cicilan pinjaman</div>
                </div>
            </div>
        </div>

        <!-- Tabel -->
        <div class="card">
            <div class="card-header">
                <h3>&#128203; Laporan Rekapitulasi Penggajian
                    <?php if ($filter_bulan && $filter_tahun): ?>
                        &mdash; <?= $nm_bln[(int)$filter_bulan] ?> <?= htmlspecialchars($filter_tahun) ?>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Periode</th>
                                <th style="text-align:right;">Potongan</th>
                                <th style="text-align:right;">Gaji Bersih</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) > 0): ?>
                                <?php foreach ($rows as $no => $row): ?>
                                <tr>
                                    <td><?= $no + 1 ?></td>
                                    <td><?= htmlspecialchars($row['nik']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama_karyawan']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                                    <td>
                                        <?php
                                        $p2 = explode('-', $row['bulan_tahun']);
                                        $b2 = (int)($p2[0] ?? 0);
                                        $t2 = $p2[1] ?? '';
                                        $nm2 = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                                                7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
                                        echo ($nm2[$b2] ?? $row['bulan_tahun']) . ' ' . $t2;
                                        ?>
                                    </td>
                                    <td style="text-align:right;color:#e74c3c;">
                                        Rp <?= number_format($row['potongan_pinjaman'],0,',','.') ?>
                                    </td>
                                    <td style="text-align:right;font-weight:700;color:#27ae60;">
                                        Rp <?= number_format($row['gaji_bersih'],0,',','.') ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="/pelatihan/sipeka/laporan/slip_gaji.php?id=<?= $row['id_penggajian'] ?>"
                                           class="btn btn-info btn-sm" target="_blank">
                                            &#128424; Cetak Slip
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <!-- Total row -->
                                <tr style="background:#f0f4f8;font-weight:700;">
                                    <td colspan="5" style="text-align:right;padding:12px 15px;">TOTAL</td>
                                    <td style="text-align:right;color:#e74c3c;padding:12px 15px;">
                                        Rp <?= number_format($total_potongan,0,',','.') ?>
                                    </td>
                                    <td style="text-align:right;color:#27ae60;padding:12px 15px;">
                                        Rp <?= number_format($total_gaji,0,',','.') ?>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:30px;color:#999;">
                                        Belum ada data penggajian<?= $filter ? ' untuk periode ini' : '' ?>.
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
