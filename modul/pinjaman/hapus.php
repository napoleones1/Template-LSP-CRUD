<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /pelatihan/sipeka/login.php");
    exit;
}
require_once __DIR__ . '/../../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /pelatihan/sipeka/modul/pinjaman/index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM pinjaman WHERE id_pinjaman = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['msg'] = 'Data pinjaman berhasil dihapus.';
} else {
    $_SESSION['err'] = 'Gagal menghapus data pinjaman: ' . mysqli_error($conn);
}
mysqli_stmt_close($stmt);

header("Location: /pelatihan/sipeka/modul/pinjaman/index.php");
exit;
