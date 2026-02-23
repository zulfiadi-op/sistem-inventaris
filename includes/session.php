<?php
// session.php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect ke login jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Silakan login terlebih dahulu!';
        header('Location: ../auth/login.php');
        exit();
    }
}

// Redirect ke dashboard jika sudah login
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: ../dashboard.php');
        exit();
    }
}

// Set session user setelah login berhasil
function setUserSession($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['nama_lengkap'] = $userData['nama_lengkap'];
    $_SESSION['role'] = $userData['role'] ?? 'admin';
    $_SESSION['login_time'] = time();
}

// Hapus session (logout)
function destroySession() {
    // Hapus semua session variables
    $_SESSION = array();
    
    // Hapus session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hancurkan session
    session_destroy();
}

// Cek apakah session sudah expired (8 jam)
function isSessionExpired() {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $session_lifetime = 8 * 60 * 60; // 8 jam dalam detik
    return (time() - $_SESSION['login_time']) > $session_lifetime;
}

// Refresh session waktu jika masih aktif
function refreshSession() {
    if (isLoggedIn()) {
        $_SESSION['login_time'] = time();
    }
}

// Cek dan handle session expiration
function checkSession() {
    if (isLoggedIn() && isSessionExpired()) {
        $_SESSION['error'] = 'Sesi Anda telah berakhir. Silakan login kembali.';
        destroySession();
        header('Location: ../auth/login.php');
        exit();
    }
    
    // Refresh session jika masih aktif
    refreshSession();
}

// Set flash message untuk ditampilkan sekali
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Get dan hapus flash message
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Display flash message jika ada
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        
        $colors = [
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info'
        ];
        
        $icons = [
            'success' => 'check-circle',
            'error' => 'exclamation-triangle',
            'warning' => 'exclamation-circle',
            'info' => 'info-circle'
        ];
        
        if (isset($colors[$type]) && isset($icons[$type])) {
            $color = $colors[$type];
            $icon = $icons[$type];
            
            return '<div class="alert alert-' . $color . ' alert-custom d-flex align-items-center" role="alert">
                        <i class="bi bi-' . $icon . ' me-2" style="font-size: 1.2rem;"></i>
                        <div>' . htmlspecialchars($message) . '</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }
    }
    return '';
}

// Cek role user (default semua admin karena sistem sederhana)
function isAdmin() {
    return true; // Karena sistem sederhana, semua user dianggap admin
}

// Require admin (sebenarnya semua user bisa akses)
function requireAdmin() {
    requireLogin();
    return true;
}

// Initialize session check on every page
checkSession();
?>      