<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /pelatihan/sipeka/login.php");
    exit;
}
require_once __DIR__ . '/../../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
    exit;
}

// Cek apakah jabatan digunakan oleh karyawan
$cek = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM karyawan WHERE id_jabatan = ?");
mysqli_stmt_bind_param($cek, 'i', $id);
mysqli_stmt_execute($cek);
$res = mysqli_stmt_get_result($cek);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($cek);

if ($row['total'] > 0) {
    $_SESSION['err'] = 'Jabatan tidak dapat dihapus karena masih digunakan oleh ' . $row['total'] . ' karyawan.';
    header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM jabatan WHERE id_jabatan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['msg'] = 'Data jabatan berhasil dihapus.';
} else {
    $_SESSION['err'] = 'Gagal menghapus data jabatan: ' . mysqli_error($conn);
}
mysqli_stmt_close($stmt);

header("Location: /pelatihan/sipeka/modul/jabatan/index.php");
exit;
