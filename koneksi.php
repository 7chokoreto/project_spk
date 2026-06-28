<?php
// ===== koneksi.php =====
$koneksi = new mysqli("localhost", "root", "", "spk_zonasi");
if ($koneksi->connect_error) {
    die("koneksi gagal: " . $koneksi->connect_error);
}
session_start();
?>