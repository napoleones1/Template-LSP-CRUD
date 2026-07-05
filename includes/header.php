<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /pelatihan/sipeka/login.php");
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPEKA - <?= htmlspecialchars($page_title ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="/pelatihan/sipeka/assets/style.css">
</head>
<body>
