<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /pelatihan/sipeka/login.php"); exit;
}
require_once __DIR__ . '/../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<p style="text-align:center;padding:40px;">ID tidak valid. <a href="/pelatihan/sipeka/laporan/index.php">Kembali</a></p>';
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT p.id_penggajian, p.bulan_tahun, p.potongan_pinjaman, p.gaji_bersih,
           k.nik, k.nama_karyawan, k.tgl_masuk,
           j.nama_jabatan, j.gapok, j.tunjangan_makan
    FROM penggajian p
    JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    JOIN jabatan j  ON k.id_jabatan  = j.id_jabatan
    WHERE p.id_penggajian = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    echo '<p style="text-align:center;padding:40px;">Data tidak ditemukan. <a href="/pelatihan/sipeka/laporan/index.php">Kembali</a></p>';
    exit;
}

$parts   = explode('-', $data['bulan_tahun']);
$bln     = (int)($parts[0] ?? date('m'));
$thn     = $parts[1] ?? date('Y');
$nm      = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$periode       = ($nm[$bln] ?? '-').' '.$thn;
$total_pendapatan = $data['gapok'] + $data['tunjangan_makan'];

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Slip Gaji - <?= htmlspecialchars($data['nama_karyawan']) ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #e8ecf0;
    color: #2c3e50;
    font-size: 13px;
}

/* ── TOOLBAR (screen only) ── */
.toolbar {
    background: #2c3e50;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
    font-size: 13px;
}
.toolbar a, .toolbar button {
    background: #27ae60; color: #fff;
    border: none; padding: 7px 16px;
    border-radius: 5px; cursor: pointer;
    font-size: 13px; text-decoration: none;
    margin-left: 8px;
}
.toolbar a { background: #7f8c8d; }

/* ── SLIP WRAPPER ── */
.slip {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 20px auto;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
}
</style>
</head>
<body>

<!-- toolbar hanya di layar -->
<div class="toolbar">
    <span>&#128203; Slip Gaji &mdash; <?= htmlspecialchars($data['nama_karyawan']) ?> &mdash; <?= $periode ?></span>
    <div>
        <button onclick="window.print()">&#128424; Cetak / Simpan PDF</button>
        <a href="/pelatihan/sipeka/laporan/index.php">&#8592; Kembali</a>
    </div>
</div>

<div class="slip">

  <!-- ── HEADER ── -->
  <div style="background:linear-gradient(135deg,#1a2741 0%,#2d3e55 55%,#1a5c32 100%);
              padding:18px 24px;display:flex;align-items:center;
              justify-content:space-between;color:#fff;">
    <div style="display:flex;align-items:center;gap:14px;">
      <div style="width:48px;height:48px;background:rgba(255,255,255,.15);
                  border-radius:10px;display:flex;align-items:center;
                  justify-content:center;font-size:22px;border:1px solid rgba(255,255,255,.2);">
        &#127970;
      </div>
      <div>
        <div style="font-size:18px;font-weight:700;">PT. SIPEKA INDONESIA</div>
        <div style="font-size:10px;color:rgba(255,255,255,.6);margin-top:2px;">Jl. Sudirman Kav. 52-53, Jakarta Pusat 10220</div>
        <div style="font-size:10px;color:rgba(255,255,255,.6);">Telp: (021) 5551234 &nbsp;|&nbsp; hrd@sipeka.co.id</div>
      </div>
    </div>
    <div style="text-align:right;">
      <div style="display:inline-block;background:#27ae60;color:#fff;
                  font-size:9px;font-weight:700;letter-spacing:2px;
                  padding:3px 10px;border-radius:20px;margin-bottom:4px;">SLIP GAJI KARYAWAN</div>
      <div style="font-size:17px;font-weight:700;"><?= htmlspecialchars($periode) ?></div>
      <div style="font-size:10px;color:rgba(255,255,255,.5);margin-top:2px;">
        No: SGJ/<?= str_pad($data['id_penggajian'],4,'0',STR_PAD_LEFT) ?>/<?= $thn ?>
      </div>
      <div style="font-size:10px;color:rgba(255,255,255,.5);">Cetak: <?= date('d/m/Y H:i') ?></div>
    </div>
  </div>
  <!-- stripe -->
  <div style="height:4px;background:linear-gradient(90deg,#27ae60,#3498db,#1a2741);"></div>

  <!-- ── INFO KARYAWAN ── -->
  <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
              background:#eef2f7;padding:6px 24px;border-bottom:1px solid #d8e0ec;
              border-top:1px solid #d8e0ec;color:#2c3e50;">
    &#128100;&nbsp; Informasi Karyawan
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;padding:12px 24px 14px;
              border-bottom:1px solid #d8e0ec;gap:1px 20px;">
    <?php
    $emp = [
        ['NIK',           $data['nik']],
        ['Jabatan',       $data['nama_jabatan']],
        ['Nama Karyawan', '<strong>'.$data['nama_karyawan'].'</strong>'],
        ['Tgl. Masuk',    date('d F Y', strtotime($data['tgl_masuk']))],
        ['Periode Gaji',  '<strong>'.$periode.'</strong>'],
        ['Departemen',    'Human Resources Dept.'],
    ];
    foreach ($emp as $e):
    ?>
    <div style="display:flex;align-items:baseline;padding:3px 0;">
      <span style="width:125px;font-size:11px;color:#7f8c8d;flex-shrink:0;"><?= $e[0] ?></span>
      <span style="margin:0 8px;color:#bdc3c7;">:</span>
      <span style="font-size:11.5px;"><?= $e[1] ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── TABEL PENDAPATAN & POTONGAN ── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;border-bottom:2px solid #d8e0ec;flex:1;">

    <!-- Pendapatan -->
    <div style="border-right:1px solid #d8e0ec;">
      <div style="background:#27ae60;color:#fff;font-size:10px;font-weight:700;
                  letter-spacing:1.5px;padding:7px 20px;">&#9650;&nbsp; PENDAPATAN</div>
      <table style="width:100%;border-collapse:collapse;font-size:11.5px;">
        <tbody>
          <?php
          $pendapatan = [
            ['Gaji Pokok',           'Rp '.number_format($data['gapok'],0,',','.')],
            ['Tunjangan Makan',      'Rp '.number_format($data['tunjangan_makan'],0,',','.')],
            ['Tunjangan Transportasi','<span style="color:#c8cfd8">Rp 0</span>'],
            ['Tunjangan Kesehatan',  '<span style="color:#c8cfd8">Rp 0</span>'],
            ['Tunjangan Jabatan',    '<span style="color:#c8cfd8">Rp 0</span>'],
            ['Bonus / Insentif',     '<span style="color:#c8cfd8">Rp 0</span>'],
            ['Uang Lembur',          '<span style="color:#c8cfd8">Rp 0</span>'],
          ];
          foreach ($pendapatan as $i => $r):
          ?>
          <tr style="border-bottom:1px solid #f0f2f5;<?= ($i%2==1)?'background:#fafbfd':'' ?>">
            <td style="padding:8px 20px;"><?= $r[0] ?></td>
            <td style="padding:8px 20px;text-align:right;"><?= $r[1] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:#eef2f7;border-top:2px solid #d8e0ec;">
            <td style="padding:10px 20px;font-weight:700;color:#1a5c32;">Total Pendapatan</td>
            <td style="padding:10px 20px;text-align:right;font-weight:700;color:#1a5c32;">
              Rp <?= number_format($total_pendapatan,0,',','.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Potongan -->
    <div>
      <div style="background:#e74c3c;color:#fff;font-size:10px;font-weight:700;
                  letter-spacing:1.5px;padding:7px 20px;">&#9660;&nbsp; POTONGAN</div>
      <table style="width:100%;border-collapse:collapse;font-size:11.5px;">
        <tbody>
          <?php
          $potongan = [
            ['Cicilan Pinjaman',       $data['potongan_pinjaman']>0
              ? '<span style="color:#e74c3c">Rp '.number_format($data['potongan_pinjaman'],0,',','.').'</span>'
              : '<span style="color:#c8cfd8">Rp 0</span>'],
            ['BPJS Kesehatan (1%)',    '<span style="color:#c8cfd8">Rp 0</span>'],
            ['BPJS Ketenagakerjaan',   '<span style="color:#c8cfd8">Rp 0</span>'],
            ['PPh 21',                 '<span style="color:#c8cfd8">Rp 0</span>'],
            ['Absensi / Terlambat',    '<span style="color:#c8cfd8">Rp 0</span>'],
            ['Iuran Koperasi',         '<span style="color:#c8cfd8">Rp 0</span>'],
            ['Potongan Lainnya',       '<span style="color:#c8cfd8">Rp 0</span>'],
          ];
          foreach ($potongan as $i => $r):
          ?>
          <tr style="border-bottom:1px solid #f0f2f5;<?= ($i%2==1)?'background:#fafbfd':'' ?>">
            <td style="padding:8px 20px;"><?= $r[0] ?></td>
            <td style="padding:8px 20px;text-align:right;"><?= $r[1] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:#eef2f7;border-top:2px solid #d8e0ec;">
            <td style="padding:10px 20px;font-weight:700;color:#922b21;">Total Potongan</td>
            <td style="padding:10px 20px;text-align:right;font-weight:700;color:#922b21;">
              Rp <?= number_format($data['potongan_pinjaman'],0,',','.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div><!-- /tabel -->

  <!-- ── TOTAL BERSIH ── -->
  <div style="display:flex;align-items:center;justify-content:space-between;
              background:linear-gradient(135deg,#1a2741,#2d3e55);color:#fff;padding:15px 24px;">
    <div style="display:flex;align-items:center;gap:10px;font-size:13px;font-weight:700;letter-spacing:1px;">
      <span style="font-size:22px;">&#128176;</span> GAJI BERSIH DITERIMA
    </div>
    <div style="font-size:26px;font-weight:800;color:#2ecc71;">
      Rp <?= number_format($data['gaji_bersih'],0,',','.') ?>
    </div>
  </div>

  <!-- ── TERBILANG ── -->
  <div style="background:#eafaf1;border-bottom:1px solid #a9dfbf;
              padding:9px 24px;font-size:11.5px;color:#1a5c32;">
    <strong>Terbilang:</strong> &ldquo;<?= ucwords(terbilang($data['gaji_bersih'])) ?> Rupiah&rdquo;
  </div>

  <!-- ── REKAP 3 KOLOM ── -->
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;
              border-bottom:1px solid #d8e0ec;flex:1;">
    <?php
    $rekap_cols = [
      ['&#128197; Rekap Kehadiran', [
        ['Hari Kerja', '22 Hari'],
        ['Hadir', '22 Hari'],
        ['Tidak Hadir', '0 Hari'],
        ['Sakit / Izin', '0 Hari'],
        ['Lembur', '0 Jam'],
      ]],
      ['&#128203; Info Pinjaman', [
        ['Status', $data['potongan_pinjaman']>0 ? 'Aktif' : 'Tidak Ada'],
        ['Cicilan Bulan Ini', 'Rp '.number_format($data['potongan_pinjaman'],0,',','.')],
        ['Sisa Tenor', '-'],
        ['Metode Bayar', 'Transfer Bank'],
        ['Tgl Transfer', '25 '.$periode],
      ]],
      ['&#128184; Ringkasan', [
        ['Total Pendapatan', 'Rp '.number_format($total_pendapatan,0,',','.')],
        ['Total Potongan', 'Rp '.number_format($data['potongan_pinjaman'],0,',','.')],
        ['Gaji Bersih', 'Rp '.number_format($data['gaji_bersih'],0,',','.')],
        ['Periode', $periode],
        ['Status', 'Sudah Dibayar'],
      ]],
    ];
    foreach ($rekap_cols as $ci => $col):
    ?>
    <div style="padding:12px 18px;<?= $ci<2 ? 'border-right:1px solid #d8e0ec;' : '' ?>">
      <div style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
                  color:#2c3e50;margin-bottom:8px;padding-bottom:5px;
                  border-bottom:2px solid #27ae60;"><?= $col[0] ?></div>
      <table style="width:100%;border-collapse:collapse;font-size:10.5px;">
        <?php foreach ($col[1] as $r): ?>
        <tr>
          <td style="padding:3px 3px;color:#7f8c8d;width:52%;"><?= $r[0] ?></td>
          <td style="padding:3px 2px;color:#bdc3c7;width:6px;">:</td>
          <td style="padding:3px 3px;color:#2c3e50;font-weight:600;"><?= $r[1] ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── TTD ── -->
  <div style="display:flex;justify-content:space-around;padding:16px 24px 12px;
              border-bottom:1px solid #d8e0ec;">
    <?php
    $ttd = [
      ['Karyawan',      $data['nama_karyawan'],    $data['nama_jabatan']],
      ['Manager HRD',   '( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )', 'Human Resources Dept.'],
      ['Direktur Utama','( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )', 'PT. SIPEKA INDONESIA'],
    ];
    foreach ($ttd as $t):
    ?>
    <div style="text-align:center;width:185px;">
      <div style="font-size:11px;color:#888;margin-bottom:3px;">Jakarta, <?= date('d F Y') ?></div>
      <div style="font-size:12px;font-weight:700;color:#2c3e50;"><?= $t[0] ?></div>
      <div style="height:52px;"></div>
      <div style="border-top:1.5px solid #2c3e50;margin:0 10px;"></div>
      <div style="font-size:11.5px;font-weight:600;color:#2c3e50;margin-top:5px;"><?= $t[1] ?></div>
      <div style="font-size:10px;color:#888;margin-top:2px;"><?= $t[2] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── FOOTER ── -->
  <div style="margin-top:auto;background:#1a2741;color:rgba(255,255,255,.5);
              padding:10px 24px;font-size:10px;
              display:flex;justify-content:space-between;align-items:center;">
    <span>&#9888; Dicetak otomatis oleh sistem SIPEKA &mdash; <?= date('d/m/Y H:i:s') ?></span>
    <span>Slip ini sah tanpa tanda tangan basah bila dicetak dari sistem resmi.</span>
  </div>

</div><!-- /slip -->

<style>
@media print {
  @page { size: A4 portrait; margin: 0; }
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  html, body { margin: 0; padding: 0; background: #fff; }
  .toolbar    { display: none !important; }
  .slip {
    width: 210mm !important;
    height: 297mm !important;
    min-height: unset !important;
    margin: 0 !important;
    box-shadow: none !important;
    page-break-inside: avoid;
  }
}
</style>
</body>
</html>
