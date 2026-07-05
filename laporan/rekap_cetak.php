<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: /pelatihan/sipeka/login.php"); exit; }
require_once __DIR__ . '/../config/koneksi.php';

$filter_bulan = (int)($_GET['bulan'] ?? 0);
$filter_tahun = trim($_GET['tahun'] ?? '');

$nm_bln = ['','Januari','Februari','Maret','April','Mei','Juni',
           'Juli','Agustus','September','Oktober','November','Desember'];

// Harus ada filter periode
if ($filter_bulan < 1 || $filter_bulan > 12 || $filter_tahun === '') {
    echo '<p style="font-family:Arial;padding:30px;">
          Pilih periode terlebih dahulu.
          <a href="/pelatihan/sipeka/laporan/index.php">Kembali</a></p>';
    exit;
}

$bulan_tahun = sprintf('%02d', $filter_bulan).'-'.$filter_tahun;
$periode     = $nm_bln[$filter_bulan].' '.$filter_tahun;

// Ambil semua data penggajian periode ini
$stmt = mysqli_prepare($conn, "
    SELECT p.id_penggajian, p.potongan_pinjaman, p.gaji_bersih,
           k.nik, k.nama_karyawan,
           j.nama_jabatan, j.gapok, j.tunjangan_makan
    FROM penggajian p
    JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    JOIN jabatan j  ON k.id_jabatan  = j.id_jabatan
    WHERE p.bulan_tahun = ?
    ORDER BY k.nama_karyawan ASC
");
mysqli_stmt_bind_param($stmt, 's', $bulan_tahun);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$rows = [];
while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
mysqli_stmt_close($stmt);

// Hitung total agregat
$total_gapok     = array_sum(array_column($rows, 'gapok'));
$total_tunjangan = array_sum(array_column($rows, 'tunjangan_makan'));
$total_potongan  = array_sum(array_column($rows, 'potongan_pinjaman'));
$total_bersih    = array_sum(array_column($rows, 'gaji_bersih'));
$jml_karyawan    = count($rows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Gaji — <?= $periode ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 12px; background: #ddd; color: #000; }

/* toolbar */
.toolbar {
    background: #333; color: #fff;
    padding: 8px 18px;
    display: flex; align-items: center; justify-content: space-between;
}
.toolbar button {
    background: #555; color: #fff; border: 1px solid #888;
    padding: 5px 14px; cursor: pointer; font-size: 12px; border-radius: 3px;
}
.toolbar button:hover { background: #111; }
.toolbar a { color: #ccc; text-decoration: none; margin-right: 12px; font-size: 12px; }

/* paper */
.paper {
    width: 297mm;           /* A4 landscape */
    min-height: 210mm;
    background: #fff;
    margin: 14px auto 20px;
    padding: 14mm 16mm 12mm;
    box-shadow: 0 1px 8px rgba(0,0,0,.25);
    display: flex;
    flex-direction: column;
}

/* header */
.hd { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px;
      display: flex; justify-content: space-between; align-items: flex-start; }
.co-name { font-size: 17px; font-weight: bold; }
.co-sub  { font-size: 10px; color: #444; margin-top: 3px; line-height: 1.6; }
.doc-box { border: 1.5px solid #000; padding: 5px 14px; text-align: center; }
.doc-title { font-size: 13px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.doc-info  { font-size: 10px; color: #333; margin-top: 3px; }

/* tabel rekap */
table.rekap {
    width: 100%; border-collapse: collapse; font-size: 11.5px; margin-bottom: 0;
}
table.rekap th {
    border: 1px solid #666; padding: 5px 8px;
    background: #f0f0f0; text-align: center; font-weight: bold;
}
table.rekap td {
    border: 1px solid #ccc; padding: 5px 8px; vertical-align: middle;
}
table.rekap td.r  { text-align: right; white-space: nowrap; }
table.rekap td.c  { text-align: center; }
table.rekap tr:nth-child(even) td { background: #fafafa; }
table.rekap tr.total-row td {
    border-top: 2px solid #000; font-weight: bold;
    background: #f0f0f0 !important;
}

/* summary boxes */
.summary {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 10px;
    margin-bottom: 12px;
}
.s-box {
    border: 1px solid #ccc; padding: 8px 10px; background: #fafafa;
}
.s-box .s-label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: .5px; }
.s-box .s-val   { font-size: 14px; font-weight: bold; margin-top: 3px; }

/* ttd */
.ttd {
    display: flex; justify-content: space-between;
    margin-top: auto; padding-top: 12px;
    border-top: 1px dashed #aaa;
}
.ttd-col { text-align: center; width: 22%; }
.ttd-place { font-size: 10.5px; color: #555; margin-bottom: 10px; }
.ttd-judul { font-size: 11px; font-weight: bold; }
.ttd-space { height: 44px; }
.ttd-line  { border-top: 1px solid #000; margin: 0 8px; }
.ttd-nama  { font-size: 11px; font-weight: bold; margin-top: 4px; }
.ttd-pos   { font-size: 10px; color: #555; }

/* footer */
.foot {
    margin-top: 10px; padding-top: 6px;
    border-top: 1px solid #ddd;
    font-size: 9.5px; color: #888;
    display: flex; justify-content: space-between;
}

/* ── PRINT ── */
@media print {
    @page { size: A4 landscape; margin: 0; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body { background: #fff !important; }
    .toolbar { display: none !important; }
    .paper {
        width: 100% !important;
        height: 210mm !important;
        min-height: unset !important;
        margin: 0 !important;
        padding: 10mm 12mm 8mm !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>
</head>
<body>

<div class="toolbar">
    <span>
        <a href="/pelatihan/sipeka/laporan/index.php">&#8592; Kembali ke Laporan</a>
        Rekapitulasi Gaji &mdash; <strong><?= $periode ?></strong>
        (<?= $jml_karyawan ?> karyawan)
    </span>
    <button onclick="window.print()">&#128424; Cetak / Simpan PDF</button>
</div>

<div class="paper">

    <!-- HEADER -->
    <div class="hd">
        <div>
            <div class="co-name">PT. SIPEKA INDONESIA</div>
            <div class="co-sub">
                Jl. Jend. Sudirman Kav. 52-53, Jakarta Pusat 10220<br>
                Telp: (021) 5551234 &nbsp;|&nbsp; hrd@sipeka.co.id
            </div>
        </div>
        <div class="doc-box">
            <div class="doc-title">Rekapitulasi Penggajian Karyawan</div>
            <div class="doc-info">
                Periode &nbsp;: <strong><?= $periode ?></strong><br>
                No. Dok &nbsp;: RKP/<?= sprintf('%02d',$filter_bulan) ?>/<?= $filter_tahun ?><br>
                Tgl. Cetak : <?= date('d F Y') ?>
            </div>
        </div>
    </div>

    <!-- SUMMARY BOXES -->
    <div class="summary">
        <div class="s-box">
            <div class="s-label">Jumlah Karyawan</div>
            <div class="s-val"><?= $jml_karyawan ?> Orang</div>
        </div>
        <div class="s-box">
            <div class="s-label">Total Gaji Pokok</div>
            <div class="s-val">Rp <?= number_format($total_gapok,0,',','.') ?></div>
        </div>
        <div class="s-box">
            <div class="s-label">Total Potongan</div>
            <div class="s-val">Rp <?= number_format($total_potongan,0,',','.') ?></div>
        </div>
        <div class="s-box">
            <div class="s-label">Total Gaji Bersih</div>
            <div class="s-val">Rp <?= number_format($total_bersih,0,',','.') ?></div>
        </div>
    </div>

    <!-- TABEL REKAP -->
    <?php if (count($rows) > 0): ?>
    <table class="rekap">
        <thead>
            <tr>
                <th style="width:35px;">No</th>
                <th style="width:90px;">NIK</th>
                <th>Nama Karyawan</th>
                <th>Jabatan</th>
                <th style="width:120px;">Gaji Pokok</th>
                <th style="width:110px;">Tunjangan Makan</th>
                <th style="width:110px;">Total Pendapatan</th>
                <th style="width:110px;">Potongan Pinjaman</th>
                <th style="width:120px;">Gaji Bersih</th>
                <th style="width:60px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td class="c"><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['nik']) ?></td>
                <td><?= htmlspecialchars($r['nama_karyawan']) ?></td>
                <td><?= htmlspecialchars($r['nama_jabatan']) ?></td>
                <td class="r">Rp <?= number_format($r['gapok'],0,',','.') ?></td>
                <td class="r">Rp <?= number_format($r['tunjangan_makan'],0,',','.') ?></td>
                <td class="r">Rp <?= number_format($r['gapok']+$r['tunjangan_makan'],0,',','.') ?></td>
                <td class="r">
                    <?= $r['potongan_pinjaman'] > 0
                        ? 'Rp '.number_format($r['potongan_pinjaman'],0,',','.')
                        : '<span style="color:#aaa;">-</span>' ?>
                </td>
                <td class="r"><strong>Rp <?= number_format($r['gaji_bersih'],0,',','.') ?></strong></td>
                <td style="height:28px;"></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">TOTAL (<?= $jml_karyawan ?> Karyawan)</td>
                <td class="r">Rp <?= number_format($total_gapok,0,',','.') ?></td>
                <td class="r">Rp <?= number_format($total_tunjangan,0,',','.') ?></td>
                <td class="r">Rp <?= number_format($total_gapok+$total_tunjangan,0,',','.') ?></td>
                <td class="r">Rp <?= number_format($total_potongan,0,',','.') ?></td>
                <td class="r">Rp <?= number_format($total_bersih,0,',','.') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
        <div style="text-align:center;padding:30px;color:#999;border:1px solid #ddd;">
            Tidak ada data penggajian untuk periode <strong><?= $periode ?></strong>.
        </div>
    <?php endif; ?>

    <!-- TTD -->
    <div class="ttd">
        <div class="ttd-col">
            <div class="ttd-place">Jakarta, <?= date('d F Y') ?></div>
            <div class="ttd-judul">Dibuat Oleh,<br>Staff Penggajian</div>
            <div class="ttd-space"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
            <div class="ttd-pos">Bagian HRD</div>
        </div>
        <div class="ttd-col">
            <div class="ttd-place">&nbsp;</div>
            <div class="ttd-judul">Diperiksa Oleh,<br>Manager HRD</div>
            <div class="ttd-space"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
            <div class="ttd-pos">Human Resources Dept.</div>
        </div>
        <div class="ttd-col">
            <div class="ttd-place">&nbsp;</div>
            <div class="ttd-judul">Disetujui Oleh,<br>Direktur Utama</div>
            <div class="ttd-space"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
            <div class="ttd-pos">PT. SIPEKA INDONESIA</div>
        </div>
        <div class="ttd-col">
            <div class="ttd-place">&nbsp;</div>
            <div class="ttd-judul">Mengetahui,<br>Direktur Keuangan</div>
            <div class="ttd-space"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
            <div class="ttd-pos">Finance &amp; Accounting</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="foot">
        <span>Dicetak oleh sistem SIPEKA pada <?= date('d/m/Y H:i:s') ?> &mdash; <?= htmlspecialchars($_SESSION['user']) ?></span>
        <span>Dokumen ini merupakan laporan resmi penggajian periode <?= $periode ?>. Harap disimpan sebagai arsip.</span>
    </div>

</div><!-- /paper -->
</body>
</html>
