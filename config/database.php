<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gudang');

// Koneksi Database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

// Fungsi bersihkan input
function cleanInput($data) {
    return htmlspecialchars(trim($data));
}