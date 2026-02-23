<?php
require_once __DIR__ . '/../config/database.php';
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);


function getAllBarang() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    // Tambah varian_barang
    $query = "SELECT id_barang as id, kode_barang, nama_barang, varian_barang, 
                     stok_barang as stok, harga_satuan, harga_jual, keterangan 
              FROM barang ORDER BY id_barang DESC";
    $result = mysqli_query($conn, $query);
    
    $barang = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $barang[] = $row;
    }
    
    mysqli_close($conn);
    return $barang;
}

function getBarangById($id) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $id = (int) $id;

    $query = "SELECT * FROM barang WHERE id_barang = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $barang = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    return $barang;
    // $conn = getConnection();
    // $id = mysqli_real_escape_string($conn, $id);
    // $query = "SELECT id_barang as id, kode_barang, nama_barang, varian_barang, 
    //                  stok_barang as stok, harga_satuan, harga_jual, keterangan 
    //           FROM barang WHERE id_barang = '$id'";
    // $result = mysqli_query($conn, $query);
    // $barang = mysqli_fetch_assoc($result);
    // mysqli_close($conn);
    // return $barang;
}

function addBarang($data) {
    $conn = getConnection();
    
    $kode_barang = mysqli_real_escape_string($conn, $data['kode_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $data['nama_barang']);
    $varian_raw = isset($data['varian_barang']) ? $data['varian_barang'] : '';
    $varian_barang = mysqli_real_escape_string($conn, $varian_raw);
    $stok_barang = intval($data['stok']);
    $keterangan = mysqli_real_escape_string($conn, $data['keterangan']);
    $harga_satuan = intval($data['harga_satuan']);
    //hitungan harga jual 50%
    $harga_jual = calculateHargaJual($harga_satuan, 50);
    
    $query = "INSERT INTO barang (kode_barang, nama_barang, varian_barang, stok_barang, harga_satuan, harga_jual keterangan) 
              VALUES ('$kode_barang', '$nama_barang', '$varian_barang', $stok_barang, $harga_satuan, $harga_jual '$keterangan')";
    
    $result = mysqli_query($conn, $query);
    $success = $result ? mysqli_insert_id($conn) : false;
    mysqli_close($conn);
    
    return $success;
}

function updateBarang($id, $data) {
    $conn = getConnection();
    
    $id = mysqli_real_escape_string($conn, $id);
    $nama_barang = mysqli_real_escape_string($conn, $data['nama_barang']);
    $varian_raw = isset($data['varian_barang']) ? $data['varian_barang'] : '';
    $varian_barang = mysqli_real_escape_string($conn, $varian_raw);
    $stok_barang = intval($data['stok']);
    $keterangan = mysqli_real_escape_string($conn, $data['keterangan']);
    $harga_satuan = intval($data['harga_satuan']);
    $harga_jual = intval($data['harga_jual']);
    
    $query = "UPDATE barang SET 
              nama_barang = '$nama_barang',
              varian_barang = '$varian_barang',
              stok_barang = $stok_barang,
              keterangan = '$keterangan',
              harga_satuan = $harga_satuan,
              harga_jual = $harga_jual
              WHERE id_barang = '$id'";
    
    $result = mysqli_query($conn, $query);
    mysqli_close($conn);
    
    return $result;
}

function deleteBarang($id) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $id = mysqli_real_escape_string($conn, $id);

    $query = "DELETE FROM barang WHERE id_barang = '$id'";
    $result = mysqli_query($conn, $query);
    mysqli_close($conn);
    return $result; 
}

//meghitung harga jual
function calculateHargaJual($hargaSatuan, $persenMarkup = 50) {
    if (!is_numeric($hargaSatuan) || $hargaSatuan <= 0) {
        return 0;
    }
    $markup = ($hargaSatuan * $persenMarkup) / 100;
    return round($hargaSatuan + $markup);
}

// ========== FUNGSI TRANSAKSI ==========
function getAllTransaksi() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $query = "SELECT t.*, b.nama_barang, u.nama_user 
              FROM transaksi t
              JOIN barang b ON t.barang_id = b.id_barang  -- PERBAIKI: barang.id_barang
              JOIN user u ON t.user_id = u.id_user
              ORDER BY t.tanggal_transaksi DESC";
    
    $result = mysqli_query($conn, $query);
    $transaksi = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transaksi[] = $row;
    }
    
    mysqli_close($conn);
    return $transaksi;
}

function addTransaksi($data) {
    $conn = getConnection();
    
    // Generate kode transaksi
    $prefix = ($data['jenis'] == 'masuk') ? 'IN' : 'OUT';
    $kode_transaksi = $prefix . '-' . date('YmdHis') . '-' . rand(100, 999);
    
    $barang_id = intval($data['barang_id']);
    $user_id = intval($_SESSION['user_id']);
    $jenis = mysqli_real_escape_string($conn, $data['jenis']);
    $jumlah = intval($data['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $data['keterangan']);
    
    // Cek stok jika transaksi keluar
    if ($jenis == 'keluar') {
        $query = "SELECT stok_barang FROM barang WHERE id_barang = $barang_id";
        $result = mysqli_query($conn, $query);
        $barang = mysqli_fetch_assoc($result);
        
        if ($barang['stok_barang'] < $jumlah) {
            mysqli_close($conn);
            return false; // Stok tidak cukup
        }
    }

    //hitung nilai transaksi berdasarkan jenis transaksi
    if ($jenis == 'masuk') {
        $nilai_transaksi = $barang ['harga_satuan'] * $jumlah;
    } else {
        $nilai_transaksi  = $barang ['harga_jual'] * $jumlah;
    }
    
    // Insert transaksi
    $query = "INSERT INTO transaksi (kode_transaksi, barang_id, user_id, jenis, jumlah, keterangan) 
              VALUES ('$kode_transaksi', $barang_id, $user_id, '$jenis', $jumlah, '$keterangan')";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Update stok barang - gunakan stok_barang
        $operator = ($jenis == 'masuk') ? '+' : '-';
        $update_query = "UPDATE barang SET stok_barang = stok_barang $operator $jumlah WHERE id_barang = $barang_id";
        mysqli_query($conn, $update_query);
    }
    
    mysqli_close($conn);
    return $result;
}

// ========== FUNGSI USER/AUTH ==========
function verifyLogin($nama_user, $password) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $nama_user = mysqli_real_escape_string($conn, $nama_user);
    
    $query = "SELECT * FROM user WHERE nama_user = '$nama_user'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Password di database adalah MD5 hash
        $hashed_password = md5($password);
        
        if ($hashed_password === $user['password']) {
            mysqli_close($conn);
            return [
                'id' => $user['id_user'],
                'nama_user' => $user['nama_user'],
                'nama_lengkap' => $user['nama_user'],
                'role' => 'admin'
            ];
        }
    }
    
    mysqli_close($conn);
    return false;
}

// ========== FUNGSI HELPER ==========
function formatRupiah($angka) {
    if (empty($angka) || !is_numeric($angka)) {
        return 'Rp 0';
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function showAlert($type, $message) {
    $class = '';
    switch($type) {
        case 'success': $class = 'alert-success'; break;
        case 'error': $class = 'alert-danger'; break;
        case 'warning': $class = 'alert-warning'; break;
        case 'info': $class = 'alert-info'; break;
    }
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}
function getSupplyHariIni() {
    global $conn;
    $query = "SELECT t.*, b.nama_barang, b.kode_barang 
              FROM transaksi t 
              JOIN barang b ON t.id_barang = b.id 
              WHERE DATE(t.tanggal_transaksi) = CURDATE() 
              AND t.status = 'masuk' 
              ORDER BY t.tanggal_transaksi DESC";
    $result = mysqli_query($conn, $query);
    $supply = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $supply[] = $row;
    }
    return $supply;
}

function getTotalStok() {
    global $conn;
    $query = "SELECT SUM(stok) AS total_stok FROM barang";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['total_stok'] ?? 0;
}

function getChartData7Hari() {
    global $conn;
    $query = "SELECT DATE(tanggal_transaksi) AS tanggal, SUM(jumlah) AS total 
              FROM transaksi 
              WHERE status = 'masuk' 
              AND tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
              GROUP BY DATE(tanggal_transaksi) 
              ORDER BY tanggal";
    $result = mysqli_query($conn, $query);
    $chart_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $chart_data[] = $row;
    }
    return $chart_data;
}
?>