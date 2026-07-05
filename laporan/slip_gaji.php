<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: /pelatihan/sipeka/login.php"); exit; }
require_once __DIR__ . '/../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo '<p style="padding:30px;font-family:Arial">ID tidak valid. <a href="/pelatihan/sipeka/laporan/index.php">Kembali</a></p>'; exit; }

$stmt = mysqli_prepare($conn, "
    SELECT p.id_penggajian, p.bulan_tahun, p.potongan_pinjaman, p.gaji_bersih,
           k.nik, k.nama_karyawan, k.tgl_masuk, k.id_karyawan,
           j.nama_jabatan, j.gapok, j.tunjangan_makan
    FROM penggajian p
    JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    JOIN jabatan j  ON k.id_jabatan  = j.id_jabatan
    WHERE p.id_penggajian = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$d = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$d) { echo '<p style="padding:30px;font-family:Arial">Data tidak ditemukan. <a href="/pelatihan/sipeka/laporan/index.php">Kembali</a></p>'; exit; }

// Sisa pinjaman
$stmt2 = mysqli_prepare($conn, "SELECT sisa_pinjaman, tenor, cicilan_per_bulan, jumlah_pinjaman FROM pinjaman WHERE id_karyawan=? AND status='Aktif' ORDER BY id_pinjaman ASC LIMIT 1");
mysqli_stmt_bind_param($stmt2, 'i', $d['id_karyawan']);
mysqli_stmt_execute($stmt2);
$pin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
mysqli_stmt_close($stmt2);

$parts   = explode('-', $d['bulan_tahun']);
$bln     = (int)($parts[0] ?? 1);
$thn     = $parts[1] ?? date('Y');
$nm_bln  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$periode = $nm_bln[$bln].' '.$thn;
$total_pendapatan = $d['gapok'] + $d['tunjangan_makan'];

// Cicilan ke berapa
$cicilan_ke = '';
if ($pin && $d['potongan_pinjaman'] > 0) {
    $sdh = (int)round(($pin['jumlah_pinjaman'] - $pin['sisa_pinjaman']) / max(1, $pin['cicilan_per_bulan']));
    $cicilan_ke = 'ke-'.$sdh.' dari '.$pin['tenor'].' bulan | sisa: '.rp($pin['sisa_pinjaman']);
}

function terbilang($n) {
    $n = abs((int)$n);
    $k = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    if ($n < 12)         return $k[$n];
    if ($n < 20)         return terbilang($n-10).' belas';
    if ($n < 100)        return terbilang((int)($n/10)).' puluh '.terbilang($n%10);
    if ($n < 200)        return 'seratus '.terbilang($n-100);
    if ($n < 1000)       return terbilang((int)($n/100)).' ratus '.terbilang($n%100);
    if ($n < 2000)       return 'seribu '.terbilang($n-1000);
    if ($n < 1000000)    return terbilang((int)($n/1000)).' ribu '.terbilang($n%1000);
    if ($n < 1000000000) return terbilang((int)($n/1000000)).' juta '.terbilang($n%1000000);
    return terbilang((int)($n/1000000000)).' miliar '.terbilang($n%1000000000);
}
function rp($n) { return 'Rp '.number_format($n,0,',','.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Slip Gaji — <?= htmlspecialchars($d['nama_karyawan']) ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, sans-serif;
    font-size: 12px;
    background: #e0e0e0;
    color: #000;
}

/* toolbar — screen only */
.toolbar {
    background: #333; color: #fff;
    padding: 8px 18px;
    display: flex; align-items: center; justify-content: space-between;
}
.toolbar button {
    background: #555; color: #fff; border: 1px solid #888;
    padding: 5px 14px; cursor: pointer; font-size: 12px; border-radius: 3px;
}
.toolbar button:hover { background: #222; }
.toolbar a { color: #ccc; text-decoration: none; margin-right: 12px; font-size: 12px; }
.toolbar a:hover { color: #fff; }

/* paper */
.paper {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 14px auto 20px;
    padding: 16mm 16mm 12mm;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 8px rgba(0,0,0,.25);
}

/* header */
.hd { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
.hd-top { display: flex; justify-content: space-between; align-items: flex-start; }
.co-name { font-size: 17px; font-weight: bold; }
.co-sub  { font-size: 10px; color: #444; margin-top: 3px; line-height: 1.6; }
.doc-box { border: 1.5px solid #000; padding: 5px 12px; text-align: center; }
.doc-box .doc-title { font-size: 13px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.doc-box .doc-info  { font-size: 10px; color: #333; margin-top: 3px; }

/* info karyawan */
.info-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 2px 20px;
    border: 1px solid #999; padding: 8px 12px;
    margin-bottom: 12px; font-size: 11.5px;
    background: #fafafa;
}
.ir { display: flex; }
.il { width: 115px; color: #444; flex-shrink: 0; }
.is { margin: 0 6px; }
.iv { color: #000; }

/* tabel gaji */
.tbl-title {
    font-size: 11px; font-weight: bold; letter-spacing: .5px;
    text-transform: uppercase; border: 1px solid #999;
    border-bottom: none; padding: 4px 10px; background: #f0f0f0;
}
table.gaji {
    width: 100%; border-collapse: collapse;
    font-size: 11.5px; margin-bottom: 0;
}
table.gaji th {
    border: 1px solid #999; padding: 4px 10px;
    background: #f0f0f0; text-align: left; font-weight: bold;
}
table.gaji th.r, table.gaji td.r { text-align: right; }
table.gaji td {
    border: 1px solid #ccc; padding: 5px 10px;
}
table.gaji tr.sub td {
    border-top: 1.5px solid #999; font-weight: bold; background: #f5f5f5;
}
table.gaji td.zero { color: #aaa; }

/* total */
.total-row {
    border: 1.5px solid #000; border-top: none;
    padding: 8px 12px;
    display: flex; justify-content: space-between; align-items: center;
    background: #f5f5f5;
}
.total-row .tl { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
.total-row .tv { font-size: 16px; font-weight: bold; }

/* terbilang */
.terb {
    border: 1px solid #999; border-top: none;
    padding: 6px 12px; font-size: 11px; font-style: italic;
    background: #fafafa; margin-bottom: 16px;
}

/* ttd */
.ttd {
    display: flex; justify-content: space-between;
    margin-top: auto; padding-top: 14px;
    border-top: 1px dashed #aaa;
}
.ttd-col { text-align: center; width: 30%; }
.ttd-place { font-size: 10.5px; color: #444; margin-bottom: 10px; }
.ttd-judul { font-size: 11px; font-weight: bold; }
.ttd-space { height: 48px; }
.ttd-line  { border-top: 1px solid #000; margin: 0 8px; }
.ttd-nama  { font-size: 11px; font-weight: bold; margin-top: 4px; }
.ttd-pos   { font-size: 10px; color: #555; }

/* footer */
.foot {
    margin-top: 14px; padding-top: 6px;
    border-top: 1px solid #ddd;
    font-size: 9.5px; color: #888;
    display: flex; justify-content: space-between;
}

/* ── PRINT ── */
@media print {
    @page { size: A4 portrait; margin: 0; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body { background: #fff !important; }
    .toolbar { display: none !important; }
    .paper {
        width: 100% !important;
        height: 297mm !important;
        min-height: unset !important;
        margin: 0 !important;
        padding: 12mm 14mm 10mm !important;
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
        Slip Gaji &mdash; <?= htmlspecialchars($d['nama_karyawan']) ?> &mdash; <?= $periode ?>
    </span>
    <button onclick="window.print()">&#128424; Cetak / Simpan PDF</button>
</div>

<div class="paper">

  <!-- HEADER -->
  <div class="hd">
    <div class="hd-top">
      <div>
        <div class="co-name">PT. SIPEKA INDONESIA</div>
        <div class="co-sub">
          Jl. Jend. Sudirman Kav. 52-53, Jakarta Pusat 10220<br>
          Telp: (021) 5551234 &nbsp;|&nbsp; Fax: (021) 5551235 &nbsp;|&nbsp; hrd@sipeka.co.id
        </div>
      </div>
      <div class="doc-box">
        <div class="doc-title">Slip Gaji Karyawan</div>
        <div class="doc-info">
          Periode : <?= $periode ?><br>
          No. : SGJ/<?= str_pad($d['id_penggajian'],4,'0',STR_PAD_LEFT) ?>/<?= $thn ?>
        </div>
      </div>
    </div>
  </div>

  <!-- INFO KARYAWAN -->
  <div class="info-grid">
    <div class="ir">
      <span class="il">NIK</span><span class="is">:</span>
      <span class="iv"><?= htmlspecialchars($d['nik']) ?></span>
    </div>
    <div class="ir">
      <span class="il">Jabatan</span><span class="is">:</span>
      <span class="iv"><?= htmlspecialchars($d['nama_jabatan']) ?></span>
    </div>
    <div class="ir">
      <span class="il">Nama Karyawan</span><span class="is">:</span>
      <span class="iv"><b><?= htmlspecialchars($d['nama_karyawan']) ?></b></span>
    </div>
    <div class="ir">
      <span class="il">Tgl. Masuk</span><span class="is">:</span>
      <span class="iv"><?= date('d F Y', strtotime($d['tgl_masuk'])) ?></span>
    </div>
    <div class="ir">
      <span class="il">Periode Gaji</span><span class="is">:</span>
      <span class="iv"><b><?= $periode ?></b></span>
    </div>
    <div class="ir">
      <span class="il">Tgl. Cetak</span><span class="is">:</span>
      <span class="iv"><?= date('d F Y') ?></span>
    </div>
  </div>

  <!-- TABEL RINCIAN -->
  <div class="tbl-title">Rincian Penghasilan dan Potongan</div>
  <table class="gaji">
    <thead>
      <tr>
        <th style="width:45%">Komponen Penghasilan</th>
        <th class="r" style="width:20%">Jumlah</th>
        <th style="width:35%">Komponen Potongan</th>
        <th class="r" style="width:20%">Jumlah</th>
      </tr>
    </thead>
    <tbody>
      <!-- Baris 1 -->
      <tr>
        <td>Gaji Pokok</td>
        <td class="r"><?= rp($d['gapok']) ?></td>
        <td>
          Cicilan Pinjaman
          <?php if ($cicilan_ke): ?>
            <br><span style="font-size:10px;color:#555;"><?= htmlspecialchars($cicilan_ke) ?></span>
          <?php endif; ?>
        </td>
        <td class="r <?= $d['potongan_pinjaman'] == 0 ? 'zero' : '' ?>">
          <?= rp($d['potongan_pinjaman']) ?>
        </td>
      </tr>
      <!-- Baris 2 -->
      <tr>
        <td>Tunjangan Makan</td>
        <td class="r"><?= rp($d['tunjangan_makan']) ?></td>
        <td></td>
        <td></td>
      </tr>
      <!-- Subtotal -->
      <tr class="sub">
        <td><b>Total Penghasilan</b></td>
        <td class="r"><b><?= rp($total_pendapatan) ?></b></td>
        <td><b>Total Potongan</b></td>
        <td class="r"><b><?= rp($d['potongan_pinjaman']) ?></b></td>
      </tr>
    </tbody>
  </table>

  <!-- TOTAL BERSIH -->
  <div class="total-row">
    <span class="tl">Gaji Bersih yang Diterima</span>
    <span class="tv"><?= rp($d['gaji_bersih']) ?></span>
  </div>

  <!-- TERBILANG -->
  <div class="terb">
    Terbilang : <i>&ldquo;<?= ucwords(terbilang($d['gaji_bersih'])) ?> Rupiah&rdquo;</i>
  </div>

  <!-- TANDA TANGAN -->
  <div class="ttd">
    <div class="ttd-col">
      <div class="ttd-place">Jakarta, <?= date('d F Y') ?></div>
      <div class="ttd-judul">Karyawan,</div>
      <div class="ttd-space"></div>
      <div class="ttd-line"></div>
      <div class="ttd-nama"><?= htmlspecialchars($d['nama_karyawan']) ?></div>
      <div class="ttd-pos"><?= htmlspecialchars($d['nama_jabatan']) ?></div>
    </div>
    <div class="ttd-col">
      <div class="ttd-place">&nbsp;</div>
      <div class="ttd-judul">Mengetahui,<br>Manager HRD</div>
      <div class="ttd-space"></div>
      <div class="ttd-line"></div>
      <div class="ttd-nama">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
      <div class="ttd-pos">Human Resources Dept.</div>
    </div>
    <div class="ttd-col">
      <div class="ttd-place">&nbsp;</div>
      <div class="ttd-judul">Menyetujui,<br>Direktur Utama</div>
      <div class="ttd-space"></div>
      <div class="ttd-line"></div>
      <div class="ttd-nama">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
      <div class="ttd-pos">PT. SIPEKA INDONESIA</div>
    </div>
  </div>

  <!-- FOOTER DOKUMEN -->
  <div class="foot">
    <span>Dicetak oleh sistem SIPEKA pada <?= date('d/m/Y H:i:s') ?></span>
    <span>Dokumen ini sah tanpa tanda tangan basah bila dicetak dari sistem resmi perusahaan.</span>
  </div>

</div><!-- /paper -->
</body>
</html>
