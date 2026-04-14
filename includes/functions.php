<?php
/**
 * File: barang_functions.php
 * Fungsi-fungsi untuk mengelola data barang, transaksi, dan user
 * Digunakan dalam sistem inventory management
 */

// Memuat file konfigurasi database yang berisi konstanta DB_HOST, DB_USER, DB_PASS, DB_NAME
require_once __DIR__ . '/../config/database.php';

// Membuat koneksi database global (digunakan oleh beberapa fungsi)
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

/**
 * Mengambil semua data barang dari database
 * @return array Array asosiatif berisi semua data barang
 */
function getAllBarang() {
    // Membuat koneksi ke database menggunakan konstanta dari config
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Query SQL untuk mengambil semua barang dengan alias kolom
    // - id_barang dijadikan 'id'
    // - stok_barang dijadikan 'stok'
    // Menambahkan varian_barang ke dalam query
    $query = "SELECT id_barang as id, kode_barang, nama_barang, varian_barang, 
                     stok_barang as stok, harga_satuan, harga_jual, keterangan 
              FROM barang ORDER BY id_barang DESC";
    
    // Eksekusi query
    $result = mysqli_query($conn, $query);
    
    // Array untuk menampung semua data barang
    $barang = [];
    
    // Looping untuk mengambil setiap baris hasil query
    while ($row = mysqli_fetch_assoc($result)) {
        $barang[] = $row; // Tambahkan ke array
    }
    
    // Tutup koneksi database
    mysqli_close($conn);
    
    // Kembalikan data barang
    return $barang;
}

/**
 * Mengambil data barang berdasarkan ID
 * @param int $id ID barang yang akan dicari
 * @return array|null Data barang atau null jika tidak ditemukan
 */
function getBarangById($id) {
    // Membuat koneksi database
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Casting ID ke integer untuk keamanan (mencegah SQL injection)
    $id = (int) $id;

    // Menggunakan prepared statement untuk keamanan (mencegah SQL injection)
    $query = "SELECT * FROM barang WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id); // 'i' berarti integer
    mysqli_stmt_execute($stmt);

    // Ambil hasil query
    $result = mysqli_stmt_get_result($stmt);
    $barang = mysqli_fetch_assoc($result);

    // Tutup statement dan koneksi
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    return $barang;
}

/**
 * Menambahkan data barang baru ke database
 * @param array $data Data barang (kode_barang, nama_barang, varian_barang, stok, keterangan, harga_satuan)
 * @return int|false ID barang yang ditambahkan atau false jika gagal
 */
function addBarang($data) {
    // Gunakan fungsi getConnection() untuk koneksi (asumsikan sudah didefinisikan di config)
    $conn = getConnection();
    
    // Escape string untuk keamanan (mencegah SQL injection)
    $kode_barang = mysqli_real_escape_string($conn, $data['kode_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $data['nama_barang']);
    $varian_raw = isset($data['varian_barang']) ? $data['varian_barang'] : '';
    $varian_barang = mysqli_real_escape_string($conn, $varian_raw);
    $stok_barang = intval($data['stok']); // Konversi ke integer
    $keterangan = mysqli_real_escape_string($conn, $data['keterangan']);
    $harga_satuan = intval($data['harga_satuan']); // Konversi ke integer
    
    // Hitung harga jual dengan markup 50% dari harga satuan
    $harga_jual = calculateHargaJual($harga_satuan, 50);
    
    // Query INSERT (CATATAN: ada kesalahan sintaks, koma sebelum keterangan)
    $query = "INSERT INTO barang (kode_barang, nama_barang, varian_barang, stok_barang, harga_satuan, harga_jual, keterangan) 
              VALUES ('$kode_barang', '$nama_barang', '$varian_barang', $stok_barang, $harga_satuan, $harga_jual, '$keterangan')";
    
    // Eksekusi query
    $result = mysqli_query($conn, $query);
    
    // Jika berhasil, ambil ID terakhir yang diinsert, jika gagal return false
    $success = $result ? mysqli_insert_id($conn) : false;
    
    // Tutup koneksi
    mysqli_close($conn);
    
    return $success;
}

/**
 * Mengupdate data barang yang sudah ada
 * @param int $id ID barang yang akan diupdate
 * @param array $data Data barang baru
 * @return bool True jika berhasil, false jika gagal
 */
function updateBarang($id, $data) {
    // Gunakan fungsi getConnection() untuk koneksi
    $conn = getConnection();
    
    // Escape semua input untuk keamanan
    $id = mysqli_real_escape_string($conn, $id);
    $nama_barang = mysqli_real_escape_string($conn, $data['nama_barang']);
    $varian_raw = isset($data['varian_barang']) ? $data['varian_barang'] : '';
    $varian_barang = mysqli_real_escape_string($conn, $varian_raw);
    $stok_barang = intval($data['stok']);
    $keterangan = mysqli_real_escape_string($conn, $data['keterangan']);
    $harga_satuan = intval($data['harga_satuan']);
    $harga_jual = intval($data['harga_jual']);
    
    // Query UPDATE
    $query = "UPDATE barang SET 
              nama_barang = '$nama_barang',
              varian_barang = '$varian_barang',
              stok_barang = $stok_barang,
              keterangan = '$keterangan',
              harga_satuan = $harga_satuan,
              harga_jual = $harga_jual
              WHERE id_barang = '$id'";
    
    // Eksekusi query
    $result = mysqli_query($conn, $query);
    
    // Tutup koneksi
    mysqli_close($conn);
    
    return $result;
}

/**
 * Menghapus data barang berdasarkan ID
 * @param int $id ID barang yang akan dihapus
 * @return bool True jika berhasil, false jika gagal
 */
function deleteBarang($id) {
    // Membuat koneksi database
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Escape ID untuk keamanan
    $id = mysqli_real_escape_string($conn, $id);

    // Query DELETE
    $query = "DELETE FROM barang WHERE id_barang = '$id'";
    $result = mysqli_query($conn, $query);
    
    // Tutup koneksi
    mysqli_close($conn);
    
    return $result; 
}

/**
 * Menghitung harga jual berdasarkan harga satuan dan persentase markup
 * @param float $hargaSatuan Harga modal barang
 * @param int $persenMarkup Persentase keuntungan (default 50%)
 * @return float Harga jual setelah ditambah markup
 */
function calculateHargaJual($hargaSatuan, $persenMarkup = 50) {
    // Validasi input: harus angka positif
    if (!is_numeric($hargaSatuan) || $hargaSatuan <= 0) {
        return 0;
    }
    
    // Hitung markup: (hargaSatuan * persen) / 100
    $markup = ($hargaSatuan * $persenMarkup) / 100;
    
    // Kembalikan harga satuan + markup, dibulatkan
    return round($hargaSatuan + $markup);
}

// ========== FUNGSI TRANSAKSI ==========

/**
 * Mengambil semua data transaksi dengan join ke tabel barang dan user
 * @return array Array asosiatif berisi semua transaksi
 */
function getAllTransaksi() {
    // Membuat koneksi database
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Query dengan JOIN untuk mendapatkan nama barang dan nama user
    $query = "SELECT t.*, b.nama_barang, u.nama_user 
              FROM transaksi t
              JOIN barang b ON t.barang_id = b.id_barang  -- Join ke tabel barang
              JOIN user u ON t.user_id = u.id_user       -- Join ke tabel user
              ORDER BY t.tanggal_transaksi DESC";     
    
    // Eksekusi query
    $result = mysqli_query($conn, $query);
    
    // Array untuk menampung data transaksi
    $transaksi = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transaksi[] = $row;
    }
    
    // Tutup koneksi
    mysqli_close($conn);
    
    return $transaksi;
}

/**
 * Menambahkan transaksi baru (barang masuk atau keluar)
 * @param array $data Data transaksi (jenis, barang_id, jumlah, keterangan)
 * @return bool True jika berhasil, false jika gagal
 */
function addTransaksi($data) {
    // Gunakan fungsi getConnection() untuk koneksi
    $conn = getConnection();
    
    // Generate kode transaksi unik
    $prefix = ($data['jenis'] == 'masuk') ? 'IN' : 'OUT'; // IN untuk masuk, OUT untuk keluar
    $kode_transaksi = $prefix . '-' . date('YmdHis') . '-' . rand(100, 999); // Format: IN-20240101120000-123
    
    // Ambil dan sanitasi data
    $barang_id = intval($data['barang_id']);
    $user_id = intval($_SESSION['user_id']); // Ambil user ID dari session
    $jenis = mysqli_real_escape_string($conn, $data['jenis']);
    $jumlah = intval($data['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $data['keterangan']);
    
    // Cek stok jika transaksi keluar
    if ($jenis == 'keluar') {
        // Ambil stok barang saat ini
        $query = "SELECT stok_barang, harga_satuan, harga_jual FROM barang WHERE id_barang = $barang_id";
        $result = mysqli_query($conn, $query);
        $barang = mysqli_fetch_assoc($result);
        
        // Validasi: stok harus cukup untuk transaksi keluar
        if ($barang['stok_barang'] < $jumlah) {
            mysqli_close($conn);
            return false; // Stok tidak cukup, transaksi gagal
        }
    } else {
        // Untuk transaksi masuk, ambil data barang juga
        $query = "SELECT harga_satuan, harga_jual FROM barang WHERE id_barang = $barang_id";
        $result = mysqli_query($conn, $query);
        $barang = mysqli_fetch_assoc($result);
    }

    // Hitung nilai transaksi berdasarkan jenis transaksi
    if ($jenis == 'masuk') {
        // Barang masuk: pakai harga satuan (harga modal)
        $nilai_transaksi = $barang['harga_satuan'] * $jumlah;
    } else {
        // Barang keluar: pakai harga jual
        $nilai_transaksi = $barang['harga_jual'] * $jumlah;
    }
    
    // Insert data transaksi ke database
    $query = "INSERT INTO transaksi (kode_transaksi, barang_id, user_id, jenis, jumlah, keterangan, nilai_transaksi) 
              VALUES ('$kode_transaksi', $barang_id, $user_id, '$jenis', $jumlah, '$keterangan', $nilai_transaksi)";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Update stok barang sesuai jenis transaksi
        // Jika masuk: stok + jumlah, Jika keluar: stok - jumlah
        $operator = ($jenis == 'masuk') ? '+' : '-';
        $update_query = "UPDATE barang SET stok_barang = stok_barang $operator $jumlah WHERE id_barang = $barang_id";
        mysqli_query($conn, $update_query);
    }
    
    // Tutup koneksi
    mysqli_close($conn);
    
    return $result;
}

// ========== FUNGSI USER/AUTH ==========

/**
 * Verifikasi login user (menggunakan MD5 untuk password)
 * @param string $nama_user Username
 * @param string $password Password plain text
 * @return array|false Data user jika berhasil, false jika gagal
 */
function verifyLogin($nama_user, $password) {
    // Membuat koneksi database
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Escape username
    $nama_user = mysqli_real_escape_string($conn, $nama_user);
    
    // Cari user berdasarkan username
    $query = "SELECT * FROM user WHERE nama_user = '$nama_user'";
    $result = mysqli_query($conn, $query);
    
    // Jika user ditemukan
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Password di database disimpan dalam bentuk MD5 hash
        $hashed_password = md5($password);
        
        // Bandingkan password
        if ($hashed_password === $user['password']) {
            mysqli_close($conn);
            
            // Kembalikan data user untuk session
            return [
                'id' => $user['id_user'],
                'nama_user' => $user['nama_user'],
                'nama_lengkap' => $user['nama_user'],
                'role' => 'admin' // Default role
            ];
        }
    }
    
    // Tutup koneksi dan return false jika gagal
    mysqli_close($conn);
    return false;
}

// ========== FUNGSI HELPER ==========

/**
 * Memformat angka menjadi format Rupiah
 * @param float|int $angka Angka yang akan diformat
 * @return string Format Rupiah (contoh: Rp 1.000.000)
 */
function formatRupiah($angka) {
    // Validasi input
    if (empty($angka) || !is_numeric($angka)) {
        return 'Rp 0';
    }
    
    // Format: Rp + number_format (tanpa desimal, pemisah ribuan titik)
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Membuat alert/bootstrap notification
 * @param string $type Tipe alert (success, error, warning, info)
 * @param string $message Pesan yang akan ditampilkan
 * @return string HTML alert
 */
function showAlert($type, $message) {
    // Mapping tipe ke class Bootstrap
    $class = '';
    switch($type) {
        case 'success': $class = 'alert-success'; break;
        case 'error': $class = 'alert-danger'; break;
        case 'warning': $class = 'alert-warning'; break;
        case 'info': $class = 'alert-info'; break;
        default: $class = 'alert-secondary';
    }
    
    // Return HTML alert dengan Bootstrap
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '  <!-- htmlspecialchars untuk mencegah XSS -->
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Mendapatkan supply (barang masuk) hari ini
 * CATATAN: Fungsi ini menggunakan variabel global $conn
 * @return array Data transaksi masuk hari ini
 */
function getSupplyHariIni() {
    global $conn; // Gunakan koneksi global yang sudah didefinisikan di awal file
    
    // Query: transaksi status 'masuk' (CATATAN: kolom status mungkin seharusnya 'jenis')
    $query = "SELECT t.*, b.nama_barang, b.kode_barang 
              FROM transaksi t 
              JOIN barang b ON t.id_barang = b.id  -- CATATAN: pastikan kolom join benar
              WHERE DATE(t.tanggal_transaksi) = CURDATE()  -- Filter tanggal hari ini
              AND t.status = 'masuk'  -- CATATAN: mungkin seharusnya 'jenis' bukan 'status'
              ORDER BY t.tanggal_transaksi DESC";
    
    $result = mysqli_query($conn, $query);
    $supply = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $supply[] = $row;
    }
    return $supply;
}

/**
 * Mendapatkan total stok semua barang
 * CATATAN: Fungsi ini menggunakan variabel global $conn
 * @return int Total stok
 */
function getTotalStok() {
    global $conn; // Gunakan koneksi global
    
    // Query jumlahkan semua stok
    $query = "SELECT SUM(stok) AS total_stok FROM barang"; // CATATAN: kolom 'stok' mungkin seharusnya 'stok_barang'
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    return $data['total_stok'] ?? 0; // Return 0 jika null
}

/**
 * Mendapatkan data untuk chart 7 hari terakhir
 * CATATAN: Fungsi ini menggunakan variabel global $conn
 * @return array Data chart (tanggal dan total)
 */
function getChartData7Hari() {
    global $conn; // Gunakan koneksi global
    
    // Query: total jumlah transaksi masuk per hari dalam 7 hari terakhir
    $query = "SELECT DATE(tanggal_transaksi) AS tanggal, SUM(jumlah) AS total 
              FROM transaksi 
              WHERE status = 'masuk'  -- CATATAN: mungkin seharusnya 'jenis' bukan 'status'
              AND tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)  -- 7 hari kebelakang
              GROUP BY DATE(tanggal_transaksi)  -- Group by tanggal
              ORDER BY tanggal";  //Urutkan dari tanggal lama ke baru
    
    $result = mysqli_query($conn, $query);
    $chart_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $chart_data[] = $row;
    }
    return $chart_data;
}

// CATATAN PENTING UNTUK DIPERBAIKI:
// 1. Fungsi getSupplyHariIni(), getTotalStok(), getChartData7Hari() menggunakan variabel global $conn
//    yang didefinisikan di awal file, tapi tidak ada pengecekan apakah koneksi masih aktif.
// 
// 2. Pada query getSupplyHariIni() dan getChartData7Hari() menggunakan kolom 'status',
//    namun pada fungsi getAllTransaksi() dan addTransaksi() menggunakan kolom 'jenis'.
//    Harus konsisten: periksa struktur tabel transaksi yang benar.
//
// 3. Fungsi getConnection() diasumsikan sudah didefinisikan di config/database.php
//
// 4. Pada query addBarang() ada kesalahan sintaks (koma sebelum keterangan) yang sudah diperbaiki
//
// 5. Pada query addTransaksi() perlu menambahkan kolom nilai_transaksi dalam INSERT
?>