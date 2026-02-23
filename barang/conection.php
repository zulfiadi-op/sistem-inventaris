<?php


// Membuat koneksi
function getConnection() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }
    
    return $conn;
}