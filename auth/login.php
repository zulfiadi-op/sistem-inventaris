<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_user = cleanInput($_POST['nama_user']);
    $password = $_POST['password'];
    
    require_once __DIR__ . '/../includes/functions.php';
    $user = verifyLogin($nama_user, $password);
    
    if ($user) {
        // Set session dengan benar
        $_SESSION['user_id'] = $user['id'];          // ini id_user
        $_SESSION['nama_user'] = $user['nama_user']; // nama_user dari db
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        header('Location: ../dashboard.php');
        exit();
    } else {
        $error = 'Nama pengguna atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventaris</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --accent-color: #667eea;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            padding: 3rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .icon-box {
            width: 70px;
            height: 70px;
            background: #f0f3ff;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-box i {
            font-size: 2.5rem;
            color: var(--accent-color);
        }

        h1 {
            font-weight: 700;
            color: #2d3748;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .text-muted {
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: #f8fafc;
            border-right: none;
            color: #a0aec0;
            padding-left: 1.25rem;
        }

        .form-control {
            border-left: none;
            padding: 0.75rem 1.25rem 0.75rem 0.5rem;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            background-color: #f8fafc;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
            background-color: #fff;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--accent-color);
            background-color: #fff;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(102, 126, 234, 0.4);
            opacity: 0.9;
        }

        .alert {
            border-radius: 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .copyright {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #a0aec0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center">
            <div class="icon-box">
                <i class="bi bi-box-seam"></i>
            </div>
            <h1>Inventaris Gudang</h1>
            <p class="text-muted">Selamat datang, silakan masuk ke akun Anda</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <div><?php echo $error; ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="nama_user" class="form-label">Nama Pengguna</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="nama_user" name="nama_user" 
                           value="admin" required placeholder="Masukkan username">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           value="admin" required placeholder="Masukkan password">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Sekarang
            </button>
        </form>

        <div class="copyright">
            &copy; <?php echo date('Y'); ?> IT Department • Version 1.2
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>