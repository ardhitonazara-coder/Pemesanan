<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_pemesanan";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

// Fungsi helper untuk format rupiah
function rupiah($angka){
    return "Rp " . number_format($angka,0,',','.');
}

// Fungsi cek login
function cek_login(){
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
        exit;
    }
}
?>