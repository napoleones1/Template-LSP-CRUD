<?php
$page_title = 'Proses Penggajian';
require_once __DIR__ . '/../../includes/header.php';

$karyawan_list = mysqli_query($conn, "
    SELECT k.id_karyawan, k.nik, k.nama_karyawan, j.nama_jabatan, j.gapok, j.tunjangan_makan
    FROM karyawan k JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    ORDER BY k.nama_karyawan ASC
");

$error   = '';
$preview = null;

$bulan_list = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $bulan       = (int)($_POST['bulan'] ?? 0);
    $tahun       = (int)($_POST['tahun'] ?? 0);
    $action      = $_POST['action'] ?? '';

    if ($id_karyawan > 0) {
        // Ambil data karyawan + jabatan
        $stmt_k = mysqli_prepare($conn, "
            SELECT k.id_karyawan, k.nik, k.nama_karyawan, k.tgl_masuk,
                   j.nama_jabatan, j.gapok, j.tunjangan_makan
            FROM karyawan k JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            WHERE k.id_karyawan = ?
        ");
        mysqli_stmt_bind_param($stmt_k, 'i', $id_karyawan);
        mysqli_stmt_execute($stmt_k);
        $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_k));
        mysqli_stmt_close($stmt_k);

        // Ambil pinjaman AKTIF (urut terlama dulu)
        $stmt_p = mysqli_prepare($conn, "
            SELECT id_pinjaman, jumlah_pinjaman, tenor, cicilan_per_bulan, sisa_pinjaman
            FROM pinjaman
            WHERE id_karyawan = ? AND status = 'Aktif'
            ORDER BY id_pinjaman ASC LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt_p, 'i', $id_karyawan);
        mysqli_stmt_execute($stmt_p);
        $pinjaman = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p));
        mysqli_stmt_close($stmt_p);

        if ($karyawan && $bulan > 0 && $tahun > 0) {
            $bulan_tahun = sprintf('%02d', $bulan).'-'.$tahun;
            $gapok       = (int)$karyawan['gapok'];
            $tunjangan   = (int)$karyawan['tunjangan_makan'];

            // Cicilan bulan ini: ambil yang lebih kecil antara cicilan_per_bulan dan sisa
            $cicilan  = 0;
            $sisa_skg = 0;
            if ($pinjaman) {
                $sisa_skg       = (int)$pinjaman['sisa_pinjaman'];
                $cicilan_normal = (int)$pinjaman['cicilan_per_bulan'];
                $cicilan        = min($cicilan_normal, $sisa_skg); // bulan terakhir mungkin < cicilan normal
            }

            $gaji_bersih = $gapok + $tunjangan - $cicilan;

            // Hitung cicilan ke berapa (sudah berapa bulan dibayar)
            $cicilan_ke = 0;
            if ($pinjaman) {
                $cicilan_ke = (int)$pinjaman['tenor'] - (int)ceil($sisa_skg / max(1, (int)$pinjaman['cicilan_per_bulan'])) + 1;
                $cicilan_ke = max(1, $cicilan_ke);
            }

            $preview = [
                'id_karyawan'     => $id_karyawan,
                'nik'             => $karyawan['nik'],
                'nama_karyawan'   => $karyawan['nama_karyawan'],
                'nama_jabatan'    => $karyawan['nama_jabatan'],
                'bulan_tahun'     => $bulan_tahun,
                'bulan'           => $bulan,
                'tahun'           => $tahun,
                'gapok'           => $gapok,
                'tunjangan'       => $tunjangan,
                'cicilan'         => $cicilan,
                'gaji_bersih'     => $gaji_bersih,
                'id_pinjaman'     => $pinjaman['id_pinjaman'] ?? null,
                'sisa_pinjaman'   => $sisa_skg,
                'sisa_setelah'    => max(0, $sisa_skg - $cicilan),
                'jumlah_pinjaman' => $pinjaman['jumlah_pinjaman'] ?? 0,
                'tenor'           => $pinjaman['tenor'] ?? 0,
                'cicilan_ke'      => $cicilan_ke,
            ];

            // ── SIMPAN ──
            if ($action === 'simpan') {
                // Cek duplikat periode
                $cek = mysqli_prepare($conn, "SELECT id_penggajian FROM penggajian WHERE id_karyawan=? AND bulan_tahun=?");
                mysqli_stmt_bind_param($cek, 'is', $id_karyawan, $bulan_tahun);
                mysqli_stmt_execute($cek);
                mysqli_stmt_store_result($cek);
                $duplikat = mysqli_stmt_num_rows($cek) > 0;
                mysqli_stmt_close($cek);

                if ($duplikat) {
                    $error = "Penggajian periode $bulan_tahun untuk karyawan ini sudah pernah diproses.";
                } else {
                    // INSERT penggajian
                    $ins = mysqli_prepare($conn,
                        "INSERT INTO penggajian (id_karyawan, bulan_tahun, potongan_pinjaman, gaji_bersih) VALUES (?,?,?,?)"
                    );
                    mysqli_stmt_bind_param($ins, 'isii', $id_karyawan, $bulan_tahun, $cicilan, $gaji_bersih);

                    if (mysqli_stmt_execute($ins)) {
                        mysqli_stmt_close($ins);

                        // UPDATE sisa_pinjaman & status
                        if ($pinjaman && $cicilan > 0) {
                            $sisa_baru = max(0, $sisa_skg - $cicilan);
                            if ($sisa_baru <= 0) {
                                $upd = mysqli_prepare($conn,
                                    "UPDATE pinjaman SET sisa_pinjaman=0, status='Lunas' WHERE id_pinjaman=?"
                                );
                                mysqli_stmt_bind_param($upd, 'i', $pinjaman['id_pinjaman']);
                                mysqli_stmt_execute($upd);
                                mysqli_stmt_close($upd);
                                $_SESSION['msg'] = "Gaji {$karyawan['nama_karyawan']} periode $bulan_tahun tersimpan. Pinjaman LUNAS!";
                            } else {
                                $upd = mysqli_prepare($conn,
                                    "UPDATE pinjaman SET sisa_pinjaman=? WHERE id_pinjaman=?"
                                );
                                mysqli_stmt_bind_param($upd, 'ii', $sisa_baru, $pinjaman['id_pinjaman']);
                                mysqli_stmt_execute($upd);
                                mysqli_stmt_close($upd);
                                $_SESSION['msg'] = "Gaji {$karyawan['nama_karyawan']} periode $bulan_tahun tersimpan. Sisa pinjaman: Rp ".number_format($sisa_baru,0,',','.');
                            }
                        } else {
                            $_SESSION['msg'] = "Gaji {$karyawan['nama_karyawan']} periode $bulan_tahun berhasil disimpan.";
                        }
                        header("Location: /pelatihan/sipeka/modul/penggajian/index.php"); exit;
                    } else {
                        $error = 'Gagal menyimpan: '.mysqli_error($conn);
                    }
                }
            }
        } elseif ($karyawan) {
            $error = 'Pilih bulan dan tahun yang valid.';
        }
    }
}
?>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <h1>Proses Penggajian</h1>
    <div class="user-info">Login sebagai: <span><?= htmlspecialchars($_SESSION['user']) ?></span></div>
  </div>
  <div class="content-area">
    <div class="page-header">
      <h2>Proses Gaji Karyawan</h2>
      <a href="/pelatihan/sipeka/modul/penggajian/index.php" class="btn btn-secondary">&larr; Kembali</a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form pilih karyawan & periode -->
    <div class="card">
      <div class="card-header"><h3>&#128221; Pilih Karyawan &amp; Periode</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" id="form_action" value="preview">
          <div class="form-group">
            <label>Pilih Karyawan <span style="color:red">*</span></label>
            <select name="id_karyawan" required>
              <option value="">-- Pilih Karyawan --</option>
              <?php mysqli_data_seek($karyawan_list,0); while ($k=mysqli_fetch_assoc($karyawan_list)):
                $sel = ($preview['id_karyawan'] ?? '') == $k['id_karyawan'] ? 'selected' : ''; ?>
              <option value="<?= $k['id_karyawan'] ?>" <?= $sel ?>>
                <?= htmlspecialchars($k['nik']) ?> - <?= htmlspecialchars($k['nama_karyawan']) ?>
                (<?= htmlspecialchars($k['nama_jabatan']) ?>)
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Bulan <span style="color:red">*</span></label>
              <select name="bulan" required>
                <option value="">-- Pilih Bulan --</option>
                <?php foreach ($bulan_list as $n => $nm): ?>
                <option value="<?= $n ?>" <?= ($preview['bulan'] ?? date('n')) == $n ? 'selected' : '' ?>><?= $nm ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Tahun <span style="color:red">*</span></label>
              <select name="tahun" required>
                <?php for ($y=date('Y'); $y>=date('Y')-5; $y--): ?>
                <option value="<?= $y ?>" <?= ($preview['tahun'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-info" onclick="document.getElementById('form_action').value='preview'">
              &#128270; Cek Info Gaji
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($preview && !$error): ?>
    <!-- Preview Gaji -->
    <div class="card">
      <div class="card-header">
        <h3>&#128203; Preview Penggajian</h3>
        <span class="badge badge-warning">Belum Disimpan</span>
      </div>
      <div class="card-body">

        <!-- Info Karyawan -->
        <div class="info-box" style="margin-bottom:16px;">
          <h4>&#128100; Informasi Karyawan</h4>
          <table><tbody>
            <tr><td style="width:120px;">NIK</td><td>: <?= htmlspecialchars($preview['nik']) ?></td></tr>
            <tr><td>Nama</td><td>: <strong><?= htmlspecialchars($preview['nama_karyawan']) ?></strong></td></tr>
            <tr><td>Jabatan</td><td>: <?= htmlspecialchars($preview['nama_jabatan']) ?></td></tr>
            <tr><td>Periode</td><td>: <strong><?= htmlspecialchars($preview['bulan_tahun']) ?></strong></td></tr>
          </tbody></table>
        </div>

        <?php if ($preview['id_pinjaman']): ?>
        <!-- Info Pinjaman -->
        <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:14px 16px;margin-bottom:16px;">
          <div style="font-weight:700;font-size:13px;color:#e65100;margin-bottom:8px;">&#128197; Status Pinjaman Aktif</div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;font-size:13px;">
            <div>
              <div style="color:#888;font-size:11px;">Total Pinjaman</div>
              <div style="font-weight:700;">Rp <?= number_format($preview['jumlah_pinjaman'],0,',','.') ?></div>
            </div>
            <div>
              <div style="color:#888;font-size:11px;">Cicilan/Bulan</div>
              <div style="font-weight:700;color:#e74c3c;">Rp <?= number_format($preview['cicilan'],0,',','.') ?></div>
            </div>
            <div>
              <div style="color:#888;font-size:11px;">Sisa Sebelum Potong</div>
              <div style="font-weight:700;">Rp <?= number_format($preview['sisa_pinjaman'],0,',','.') ?></div>
            </div>
          </div>
          <!-- Progress bar sisa pinjaman -->
          <?php
          $pct_lunas = $preview['jumlah_pinjaman'] > 0
            ? round((($preview['jumlah_pinjaman'] - $preview['sisa_pinjaman']) / $preview['jumlah_pinjaman']) * 100)
            : 100;
          ?>
          <div style="margin-top:10px;">
            <div style="font-size:11px;color:#888;margin-bottom:4px;">
              Progress Pelunasan: <?= $pct_lunas ?>%
              &nbsp;|&nbsp; Sisa setelah bulan ini:
              <strong style="color:<?= $preview['sisa_setelah']<=0 ? '#27ae60' : '#e74c3c' ?>">
                <?= $preview['sisa_setelah'] <= 0 ? 'LUNAS &#10003;' : 'Rp '.number_format($preview['sisa_setelah'],0,',','.') ?>
              </strong>
            </div>
            <div style="background:#eee;border-radius:10px;height:10px;overflow:hidden;">
              <div style="height:100%;border-radius:10px;width:<?= $pct_lunas ?>%;
                          background:<?= $pct_lunas>=100 ? '#27ae60' : '#f39c12' ?>;transition:width .3s;"></div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Kalkulasi Gaji -->
        <table style="width:100%;max-width:480px;border-collapse:collapse;font-size:14px;">
          <thead>
            <tr style="background:#2c3e50;color:#fff;">
              <th style="padding:10px 15px;text-align:left;">Komponen</th>
              <th style="padding:10px 15px;text-align:right;">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:10px 15px;">Gaji Pokok</td>
              <td style="padding:10px 15px;text-align:right;color:#27ae60;">+ Rp <?= number_format($preview['gapok'],0,',','.') ?></td>
            </tr>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:10px 15px;">Tunjangan Makan</td>
              <td style="padding:10px 15px;text-align:right;color:#27ae60;">+ Rp <?= number_format($preview['tunjangan'],0,',','.') ?></td>
            </tr>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:10px 15px;">
                Cicilan Pinjaman
                <?php if ($preview['cicilan'] > 0): ?>
                  <small style="color:#888;">(Sisa: Rp <?= number_format($preview['sisa_pinjaman'],0,',','.') ?>)</small>
                <?php endif; ?>
              </td>
              <td style="padding:10px 15px;text-align:right;color:<?= $preview['cicilan']>0 ? '#e74c3c' : '#999' ?>;">
                <?= $preview['cicilan']>0 ? '- Rp '.number_format($preview['cicilan'],0,',','.') : 'Rp 0' ?>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr style="background:#2c3e50;color:#fff;">
              <td style="padding:12px 15px;font-weight:700;">GAJI BERSIH</td>
              <td style="padding:12px 15px;text-align:right;font-weight:700;font-size:16px;color:#2ecc71;">
                Rp <?= number_format($preview['gaji_bersih'],0,',','.') ?>
              </td>
            </tr>
          </tfoot>
        </table>

        <!-- Tombol Simpan -->
        <form method="POST" style="margin-top:20px;">
          <input type="hidden" name="action" value="simpan">
          <input type="hidden" name="id_karyawan" value="<?= $preview['id_karyawan'] ?>">
          <input type="hidden" name="bulan" value="<?= $preview['bulan'] ?>">
          <input type="hidden" name="tahun" value="<?= $preview['tahun'] ?>">
          <button type="submit" class="btn btn-primary"
                  onclick="return confirm('Simpan penggajian ini? Sisa pinjaman akan dikurangi secara otomatis.')">
            &#10003; Simpan Penggajian
          </button>
          <a href="/pelatihan/sipeka/modul/penggajian/proses.php" class="btn btn-secondary">Reset</a>
        </form>

      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
