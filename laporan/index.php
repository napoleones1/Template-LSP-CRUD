<?php
$page_title = 'Laporan Gaji';
require_once __DIR__ . '/../includes/header.php';

$nm_bln = ['','Januari','Februari','Maret','April','Mei','Juni',
           'Juli','Agustus','September','Oktober','November','Desember'];

// Filter bulan & tahun terpisah (sesuai Modul 3)
$filter_bulan = (int)($_GET['bulan'] ?? 0);
$filter_tahun = trim($_GET['tahun'] ?? '');
$filter       = ($filter_bulan > 0 && $filter_tahun !== '')
                ? sprintf('%02d', $filter_bulan).'-'.$filter_tahun
                : '';

// Ambil daftar tahun unik untuk dropdown
$res_tahun = mysqli_query($conn,
    "SELECT DISTINCT SUBSTRING(bulan_tahun,4) AS tahun FROM penggajian ORDER BY tahun DESC");

// Query utama
if ($filter !== '') {
    $stmt = mysqli_prepare($conn, "
        SELECT p.id_penggajian, p.bulan_tahun, p.potongan_pinjaman, p.gaji_bersih,
               k.nik, k.nama_karyawan, j.nama_jabatan
        FROM penggajian p
        JOIN karyawan k ON p.id_karyawan = k.id_karyawan
        JOIN jabatan j  ON k.id_jabatan  = j.id_jabatan
        WHERE p.bulan_tahun = ?
        ORDER BY k.nama_karyawan ASC
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
        ORDER BY p.bulan_tahun DESC, k.nama_karyawan ASC
    ");
}

// Kumpulkan baris + hitung total (fungsi agregat SUM)
$rows           = [];
$total_gaji     = 0;
$total_potongan = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $total_gaji     += $row['gaji_bersih'];
    $total_potongan += $row['potongan_pinjaman'];
    $rows[]          = $row;
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

        <!-- ── FILTER BULAN & TAHUN ── -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:15px 20px;">
                <form method="GET" action=""
                      style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label style="font-weight:600;font-size:13px;color:#555;">Filter Periode:</label>

                    <!-- Bulan -->
                    <select name="bulan"
                            style="padding:8px 12px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                        <option value="">-- Pilih Bulan --</option>
                        <?php for ($b = 1; $b <= 12; $b++): ?>
                        <option value="<?= $b ?>"
                                <?= $filter_bulan === $b ? 'selected' : '' ?>>
                            <?= $nm_bln[$b] ?>
                        </option>
                        <?php endfor; ?>
                    </select>

                    <!-- Tahun -->
                    <select name="tahun"
                            style="padding:8px 12px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                        <option value="">-- Pilih Tahun --</option>
                        <?php while ($ty = mysqli_fetch_assoc($res_tahun)): ?>
                        <option value="<?= htmlspecialchars($ty['tahun']) ?>"
                                <?= $filter_tahun === $ty['tahun'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ty['tahun']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">&#128269; Tampilkan</button>
                    <?php if ($filter !== ''): ?>
                        <a href="/pelatihan/sipeka/laporan/index.php"
                           class="btn btn-secondary btn-sm">&#10005; Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- ── SUMMARY CARDS ── -->
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
                    <div class="stat-value" style="font-size:15px;">
                        Rp <?= number_format($total_gaji,0,',','.') ?>
                    </div>
                    <div class="stat-sub">
                        <?= $filter !== '' ? ($nm_bln[$filter_bulan].' '.$filter_tahun) : 'semua periode' ?>
                    </div>
                </div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">&#9888;</div>
                <div class="stat-info">
                    <h4>Total Potongan</h4>
                    <div class="stat-value" style="font-size:15px;">
                        Rp <?= number_format($total_potongan,0,',','.') ?>
                    </div>
                    <div class="stat-sub">cicilan pinjaman</div>
                </div>
            </div>
        </div>

        <!-- ── TABEL REKAPITULASI ── -->
        <div class="card">
            <div class="card-header">
                <h3>&#128203; Laporan Rekapitulasi Penggajian
                    <?php if ($filter !== ''): ?>
                        &mdash; <?= $nm_bln[$filter_bulan] ?> <?= htmlspecialchars($filter_tahun) ?>
                    <?php endif; ?>
                </h3>
                <?php if ($filter !== '' && count($rows) > 0): ?>
                <a href="/pelatihan/sipeka/laporan/rekap_cetak.php?bulan=<?= $filter_bulan ?>&tahun=<?= urlencode($filter_tahun) ?>"
                   target="_blank" class="btn btn-primary btn-sm">
                    &#128424; Cetak Rekap Periode Ini
                </a>
                <?php endif; ?>
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
                                <?php
                                    $p2  = explode('-', $row['bulan_tahun']);
                                    $b2  = (int)($p2[0] ?? 0);
                                    $t2  = $p2[1] ?? '';
                                    $nm2 = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                                            7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
                                ?>
                                <tr>
                                    <td><?= $no + 1 ?></td>
                                    <td><?= htmlspecialchars($row['nik']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama_karyawan']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                                    <td><?= ($nm2[$b2] ?? '-').' '.$t2 ?></td>
                                    <td style="text-align:right;color:#e74c3c;">
                                        Rp <?= number_format($row['potongan_pinjaman'],0,',','.') ?>
                                    </td>
                                    <td style="text-align:right;font-weight:700;color:#27ae60;">
                                        Rp <?= number_format($row['gaji_bersih'],0,',','.') ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="/pelatihan/sipeka/laporan/slip_gaji.php?id=<?= $row['id_penggajian'] ?>"
                                           target="_blank" class="btn btn-info btn-sm">
                                            &#128424; Slip
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <!-- Baris Total (SUM) -->
                                <tr style="background:#f0f4f8;font-weight:700;border-top:2px solid #2c3e50;">
                                    <td colspan="5"
                                        style="text-align:right;padding:12px 15px;">
                                        TOTAL (<?= count($rows) ?> karyawan)
                                    </td>
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
                                    <td colspan="8"
                                        style="text-align:center;padding:30px;color:#999;">
                                        <?= $filter !== ''
                                            ? 'Belum ada data penggajian untuk periode ini.'
                                            : 'Belum ada data penggajian.' ?>
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
