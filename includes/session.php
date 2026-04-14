<?php
/**
 * File: session.php
 * Fungsi-fungsi untuk mengelola session user, autentikasi, dan keamanan
 * Digunakan di seluruh halaman untuk mengontrol akses dan session management
 */

// ========== INISIALISASI SESSION ==========
/**
 * Memulai session jika belum dimulai
 * PHP_SESSION_NONE = session belum dimulai
 * PHP_SESSION_ACTIVE = session sudah berjalan
 * PHP_SESSION_DISABLED = session dinonaktifkan
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Memulai session untuk menyimpan data user
}

// ========== FUNGSI AUTENTIKASI ==========

/**
 * Memeriksa apakah user sudah login atau belum
 * @return bool True jika user sudah login (session user_id ada dan tidak kosong), false jika belum
 */
function isLoggedIn() {
    // Cek apakah session 'user_id' sudah diset dan nilainya tidak kosong
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Memaksa user untuk login jika belum login
 * Digunakan di halaman yang membutuhkan autentikasi (dashboard, transaksi, dll)
 * @return void (redirect ke halaman login jika belum login)
 */
function requireLogin() {
    // Jika user belum login
    if (!isLoggedIn()) {
        // Simpan pesan error di session flash
        $_SESSION['error'] = 'Silakan login terlebih dahulu!';
        // Redirect ke halaman login (ke folder auth)
        header('Location: ../auth/login.php');
        exit(); // Hentikan eksekusi script setelah redirect
    }
}

/**
 * Redirect ke dashboard jika user sudah login
 * Digunakan di halaman login/register untuk mencegah user yang sudah login mengakses lagi
 * @return void (redirect ke dashboard jika sudah login)
 */
function redirectIfLoggedIn() {
    // Jika user sudah login
    if (isLoggedIn()) {
        // Redirect ke halaman dashboard utama
        header('Location: ../dashboard.php');
        exit(); // Hentikan eksekusi script
    }
}

// ========== FUNGSI MANAJEMEN SESSION ==========

/**
 * Menyimpan data user ke dalam session setelah login berhasil
 * @param array $userData Array berisi data user (id, username, nama_lengkap, role)
 * @return void (mengisi variabel $_SESSION)
 */
function setUserSession($userData) {
    $_SESSION['user_id'] = $userData['id'];           // ID unik user
    $_SESSION['username'] = $userData['username'];     // Username untuk login
    $_SESSION['nama_lengkap'] = $userData['nama_lengkap']; // Nama lengkap user
    $_SESSION['role'] = $userData['role'] ?? 'admin';  // Role user (default 'admin')
    $_SESSION['login_time'] = time();                  // Waktu login (UNIX timestamp)
}

/**
 * Menghancurkan semua data session (logout)
 * Menghapus session variables, session cookie, dan mengakhiri session
 * @return void
 */
function destroySession() {
    // Hapus semua session variables (mengosongkan array $_SESSION)
    $_SESSION = array();
    
    // Hapus session cookie jika session menggunakan cookie
    if (ini_get("session.use_cookies")) {
        // Ambil parameter cookie session yang aktif
        $params = session_get_cookie_params();
        
        // Set cookie session dengan waktu kadaluarsa di masa lalu (delete cookie)
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hancurkan session sepenuhnya
    session_destroy();
}

// ========== FUNGSI SESSION EXPIRATION ==========

/**
 * Memeriksa apakah session sudah kadaluarsa (expired)
 * Session diatur kadaluarsa setelah 8 jam
 * @return bool True jika session expired, false jika masih aktif
 */
function isSessionExpired() {
    // Jika tidak ada waktu login dalam session, anggap expired
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    // Definisikan lifetime session = 8 jam dalam detik
    // 8 jam × 60 menit × 60 detik = 28.800 detik
    $session_lifetime = 8 * 60 * 60;
    
    // Hitung selisih waktu sekarang dengan waktu login
    // Jika selisih > lifetime, session expired
    return (time() - $_SESSION['login_time']) > $session_lifetime;
}

/**
 * Memperbarui waktu session (refresh) jika user masih aktif
 * Digunakan untuk memperpanjang masa aktif session
 * @return void
 */
function refreshSession() {
    // Hanya refresh jika user sudah login
    if (isLoggedIn()) {
        // Set ulang waktu login ke waktu sekarang
        $_SESSION['login_time'] = time();
    }
}

/**
 * Memeriksa dan menangani session expiration
 * Memanggil isSessionExpired() dan refreshSession()
 * Sebaiknya dipanggil di setiap halaman yang membutuhkan session
 * @return void (redirect ke login jika expired)
 */
function checkSession() {
    // Jika user login tapi session sudah expired
    if (isLoggedIn() && isSessionExpired()) {
        // Simpan pesan error
        $_SESSION['error'] = 'Sesi Anda telah berakhir. Silakan login kembali.';
        // Hancurkan session yang expired
        destroySession();
        // Redirect ke halaman login
        header('Location: ../auth/login.php');
        exit();
    }
    
    // Refresh session waktu jika masih aktif (perpanjang masa aktif)
    refreshSession();
}

// ========== FUNGSI FLASH MESSAGE ==========

/**
 * Menyimpan pesan flash (pesan sementara yang hanya muncul sekali)
 * Digunakan untuk menampilkan notifikasi setelah redirect
 * @param string $type Tipe pesan (success, error, warning, info)
 * @param string $message Isi pesan yang akan ditampilkan
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,      // success/error/warning/info
        'message' => $message // Teks pesan
    ];
}

/**
 * Mengambil dan menghapus pesan flash dari session
 * Pesan hanya tersedia sekali setelah diset
 * @return array|null Array pesan flash atau null jika tidak ada
 */
function getFlashMessage() {
    // Jika ada pesan flash di session
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];    // Ambil pesan
        unset($_SESSION['flash']);      // Hapus dari session (sekali pakai)
        return $flash;                  // Kembalikan pesan
    }
    return null; // Tidak ada pesan flash
}

/**
 * Menampilkan pesan flash dalam format HTML dengan Bootstrap
 * @return string HTML alert atau string kosong jika tidak ada pesan
 */
function displayFlashMessage() {
    $flash = getFlashMessage(); // Ambil pesan flash
    
    // Jika ada pesan flash
    if ($flash) {
        $type = $flash['type'];      // Tipe pesan
        $message = $flash['message']; // Isi pesan
        
        // Mapping tipe pesan ke class Bootstrap
        $colors = [
            'success' => 'success',  // Hijau untuk sukses
            'error' => 'danger',     // Merah untuk error
            'warning' => 'warning',  // Kuning untuk peringatan
            'info' => 'info'         // Biru untuk informasi
        ];
        
        // Mapping tipe pesan ke icon Bootstrap Icons
        $icons = [
            'success' => 'check-circle',      // Icon centang lingkaran
            'error' => 'exclamation-triangle', // Icon segitiga peringatan
            'warning' => 'exclamation-circle', // Icon lingkaran seru
            'info' => 'info-circle'            // Icon lingkaran info
        ];
        
        // Jika tipe pesan valid
        if (isset($colors[$type]) && isset($icons[$type])) {
            $color = $colors[$type]; // Warna Bootstrap
            $icon = $icons[$type];   // Nama icon
            
            // Return HTML alert dengan Bootstrap dan icon
            return '<div class="alert alert-' . $color . ' alert-custom d-flex align-items-center" role="alert">
                        <i class="bi bi-' . $icon . ' me-2" style="font-size: 1.2rem;"></i>
                        <div>' . htmlspecialchars($message) . '</div> <!-- htmlspecialchars mencegah XSS -->
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }
    }
    return ''; // Tidak ada pesan flash
}

// ========== FUNGSI OTORISASI (ROLE) ==========

/**
 * Memeriksa apakah user adalah admin
 * Catatan: Dalam sistem sederhana ini, semua user dianggap admin
 * @return bool Selalu true (karena sistem sederhana)
 */
function isAdmin() {
    // Sistem sederhana: semua user yang login dianggap admin
    // Untuk sistem yang lebih kompleks, bisa cek $_SESSION['role'] == 'admin'
    return true;
}

/**
 * Memastikan user adalah admin
 * Sebenarnya semua user bisa akses karena isAdmin() selalu true
 * @return bool True jika user adalah admin (selalu true dalam sistem ini)
 */
function requireAdmin() {
    requireLogin(); // Pastikan user sudah login
    return true;    // Semua user dianggap admin
}

// ========== INISIALISASI OTOMATIS ==========

/**
 * Memeriksa session expiration di setiap halaman yang memuat file ini
 * Fungsi ini dipanggil otomatis setiap kali session.php di-include
 */
checkSession(); // Cek apakah session expired, refresh jika masih aktif

// ========== CATATAN PENTING UNTUK PENGEMBANGAN ==========
// 1. Sistem saat ini menganggap semua user adalah admin (isAdmin() selalu true)
//    Untuk keamanan lebih baik, implementasikan role-based access control (RBAC) yang sebenarnya.
//
// 2. Session lifetime diatur 8 jam (28.800 detik) - sesuaikan dengan kebutuhan.
//
// 3. Flash message menggunakan session, pastikan session sudah dimulai sebelum menggunakan.
//
// 4. Fungsi checkSession() dipanggil otomatis di akhir file, sehingga setiap halaman
//    yang meng-include file ini akan otomatis melakukan pengecekan session.
//
// 5. Pastikan folder auth memiliki file login.php untuk redirect yang benar.
//
// 6. Untuk logout, cukup panggil destroySession() lalu redirect ke login.
//
// 7. Penggunaan htmlspecialchars() pada displayFlashMessage() mencegah serangan XSS.
?>