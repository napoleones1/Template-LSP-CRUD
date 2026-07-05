<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /pelatihan/sipeka/login.php");
    exit;
}
require_once __DIR__ . '/../../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
    exit;
}

// Cek apakah karyawan memiliki data pinjaman
$cek_pinjaman = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM pinjaman WHERE id_karyawan = ?");
mysqli_stmt_bind_param($cek_pinjaman, 'i', $id);
mysqli_stmt_execute($cek_pinjaman);
$res1 = mysqli_stmt_get_result($cek_pinjaman);
$pinjaman_count = mysqli_fetch_assoc($res1)['total'];
mysqli_stmt_close($cek_pinjaman);

// Cek apakah karyawan memiliki data penggajian
$cek_gaji = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM penggajian WHERE id_karyawan = ?");
mysqli_stmt_bind_param($cek_gaji, 'i', $id);
mysqli_stmt_execute($cek_gaji);
$res2 = mysqli_stmt_get_result($cek_gaji);
$gaji_count = mysqli_fetch_assoc($res2)['total'];
mysqli_stmt_close($cek_gaji);

if ($pinjaman_count > 0 || $gaji_count > 0) {
    $_SESSION['err'] = 'Karyawan tidak dapat dihapus karena masih memiliki data pinjaman atau penggajian.';
    header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM karyawan WHERE id_karyawan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['msg'] = 'Data karyawan berhasil dihapus.';
} else {
    $_SESSION['err'] = 'Gagal menghapus data karyawan: ' . mysqli_error($conn);
}
mysqli_stmt_close($stmt);

header("Location: /pelatihan/sipeka/modul/karyawan/index.php");
exit;
