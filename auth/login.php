<?php
// Memulai session untuk menyimpan data login pengguna
session_start();
// Memuat file konfigurasi database
require_once __DIR__ . '/../config/database.php';

// Inisialisasi variabel error
$error = '';

// Memeriksa apakah form telah disubmit dengan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Membersihkan input nama pengguna dari karakter berbahaya
    $nama_user = cleanInput($_POST['nama_user']);
    // Mengambil password dari form
    $password = $_POST['password'];
    
    // Memuat file fungsi untuk verifikasi login
    require_once __DIR__ . '/../includes/functions.php';
    // Memverifikasi kredensial pengguna
    $user = verifyLogin($nama_user, $password);
    
    // Jika verifikasi berhasil, set session dan redirect
    if ($user) {
        // Menyimpan data user ke dalam session
        $_SESSION['user_id'] = $user['id'];          // ID pengguna dari database
        $_SESSION['nama_user'] = $user['nama_user']; // Nama pengguna untuk login
        $_SESSION['nama_lengkap'] = $user['nama_lengkap']; // Nama lengkap pengguna
        $_SESSION['role'] = $user['role'];           // Peran/role pengguna (admin/user)
        $_SESSION['login_time'] = time();             // Waktu login untuk keamanan
        
        // Redirect ke halaman dashboard
        header('Location: ../dashboard.php');
        exit(); // Menghentikan eksekusi script setelah redirect
    } else {
        // Set pesan error jika login gagal
        $error = 'Nama pengguna atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login - Inventaris Roti</title>
    
    <!-- Bootstrap CSS untuk styling dasar -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter untuk tipografi modern -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons untuk ikon-ikon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        /* Reset CSS untuk konsistensi antar browser */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Variabel CSS untuk warna dan efek yang konsisten */
        :root {
            --primary: #0B2F9E;        /* Biru tua */
            --secondary: #2563EB;       /* Biru terang */
            --accent: #3B82F6;          /* Biru medium */
            --light-blue: #60A5FA;       /* Biru muda */
            --extra-light: #DBEAFE;      /* Biru sangat muda */
            --dark: #1E293B;             /* Dark blue */
            --gradient-1: linear-gradient(145deg, #0B2F9E 0%, #2563EB 50%, #60A5FA 100%);
            --gradient-2: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            --gradient-3: linear-gradient(125deg, #0B2F9E 0%, #3B82F6 100%);
            --gradient-4: linear-gradient(165deg, #1E293B 0%, #2563EB 100%);
            --shadow-1: 0 20px 40px rgba(11, 47, 158, 0.15);
            --shadow-2: 0 8px 20px rgba(37, 99, 235, 0.2);
            --shadow-3: 0 15px 35px rgba(11, 47, 158, 0.25);
            --glow: 0 0 30px rgba(59, 130, 246, 0.5);
        }

        /* Styling body dengan background gradien animasi */
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: linear-gradient(145deg, #0B2F9E 0%, #2563EB 50%, #3B82F6 100%);
            background-size: 300% 300%;
            animation: gradientWave 15s ease infinite;
            position: relative;
            overflow-x: hidden;
            padding: 16px;
        }

        /* Animasi gelombang gradien background */
        @keyframes gradientWave {
            0% { background-position: 0% 50%; }
            25% { background-position: 50% 75%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 50% 25%; }
            100% { background-position: 0% 50%; }
        }

        /* Styling untuk gelembung latar belakang animasi */
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(2px);
            animation: bubbleFloat 8s infinite;
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Ukuran dan posisi masing-masing gelembung */
        .bubble-1 {
            width: min(200px, 30vw);
            height: min(200px, 30vw);
            top: -50px;
            left: -50px;
            animation: bubbleFloat 12s infinite;
        }

        .bubble-2 {
            width: min(300px, 40vw);
            height: min(300px, 40vw);
            bottom: -100px;
            right: -100px;
            animation: bubbleFloat 15s infinite reverse;
        }

        .bubble-3 {
            width: min(150px, 25vw);
            height: min(150px, 25vw);
            top: 30%;
            right: 10%;
            animation: bubbleFloat 10s infinite 2s;
        }

        .bubble-4 {
            width: min(100px, 20vw);
            height: min(100px, 20vw);
            bottom: 20%;
            left: 15%;
            animation: bubbleFloat 9s infinite 1s;
        }

        /* Animasi gelembung mengapung */
        @keyframes bubbleFloat {
            0%, 100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.3;
            }
            25% {
                transform: translate(20px, -20px) scale(1.1);
                opacity: 0.5;
            }
            50% {
                transform: translate(40px, 20px) scale(1.2);
                opacity: 0.4;
            }
            75% {
                transform: translate(10px, 30px) scale(1.05);
                opacity: 0.6;
            }
        }

        /* Wrapper login - container utama form */
        .login-wrapper {
            width: 100%;
            max-width: min(480px, 95%);
            margin: 0 auto;
            position: relative;
            z-index: 20;
            animation: floatIn 1.2s cubic-bezier(0.23, 1, 0.32, 1);
        }

        /* Animasi masuk dari bawah */
        @keyframes floatIn {
            0% {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            50% {
                opacity: 0.8;
                transform: translateY(-10px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Kartu login dengan efek glassmorphism */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: min(2.5rem, 40px);
            box-shadow: var(--shadow-3), var(--glow);
            padding: clamp(1.5rem, 5vw, 3rem) clamp(1.5rem, 4vw, 2.5rem);
            border: 1px solid rgba(96, 165, 250, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            width: 100%;
        }

        /* Efek hover pada kartu login */
        .login-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 30px 60px rgba(11, 47, 158, 0.3), 0 0 40px rgba(59, 130, 246, 0.4);
            border-color: var(--light-blue);
        }

        /* Kotak ikon dengan efek putar */
        .icon-box {
            width: clamp(80px, 20vw, 120px);
            height: clamp(80px, 20vw, 120px);
            background: linear-gradient(145deg, rgba(11, 47, 158, 0.1), rgba(37, 99, 235, 0.2), rgba(96, 165, 250, 0.1));
            border-radius: min(2.5rem, 30px) min(2.5rem, 30px) min(1rem, 15px) min(2.5rem, 30px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto clamp(1rem, 3vw, 2rem);
            position: relative;
            animation: morphIn 1.2s ease;
            border: 2px solid rgba(96, 165, 250, 0.4);
            transform: rotate(45deg);
        }

        /* Ikon di dalam kotak */
        .icon-box i {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            background: linear-gradient(145deg, #0B2F9E, #2563EB, #60A5FA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: rotate 20s linear infinite;
            transform: rotate(-45deg);
        }

        /* Tombol login utama */
        .btn-primary {
            background: linear-gradient(145deg, #0B2F9E, #2563EB, #3B82F6);
            background-size: 200% 200%;
            border: none;
            padding: clamp(0.8rem, 3vw, 1.2rem) clamp(1rem, 4vw, 1.5rem);
            font-weight: 700;
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            border-radius: clamp(1rem, 4vw, 1.5rem);
            transition: all 0.4s ease;
            margin-top: clamp(1.5rem, 4vw, 2rem);
            position: relative;
            overflow: hidden;
            animation: slideUpText 0.8s ease 0.6s both;
            z-index: 1;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        /* Copyright di bagian bawah */
        .copyright {
            margin-top: clamp(1.5rem, 4vw, 2rem);
            font-size: clamp(0.7rem, 2.5vw, 0.85rem);
            color: rgba(255,255,255,0.95);
            text-align: center;
            animation: fadeInUp 1s ease 0.8s both;
            text-shadow: 0 2px 5px rgba(11, 47, 158, 0.3);
            letter-spacing: 0.5px;
            padding: 0 8px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <!-- Elemen gelembung latar belakang -->
    <div class="bubble bubble-1"></div>
    <div class="bubble bubble-2"></div>
    <div class="bubble bubble-3"></div>
    <div class="bubble bubble-4"></div>
    
    <!-- Container untuk partikel animasi -->
    <div class="particles" id="particles"></div>
    
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Bagian header dengan ikon dan judul -->
            <div class="text-center">
                <div class="icon-box">
                    <i class="bi bi-shop"></i>
                </div>
                <h1>Inventaris Roti</h1>
                <div class="subtitle">
                    <i class="bi bi-droplet bread-icon"></i> Management System
                </div>
                <p class="text-muted">Silakan masuk ke akun Anda</p>
            </div>
            
            <!-- Menampilkan pesan error jika login gagal -->
            <?php if ($error): ?>
            <div class="alert alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <div><?php echo $error; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Form login -->
            <form method="POST" action="" id="loginForm">
                <!-- Input Nama Pengguna -->
                <div class="form-group">
                    <label for="nama_user" class="form-label">
                        <i class="bi bi-person-circle"></i> Nama Pengguna
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="nama_user" name="nama_user" 
                               value="admin" required placeholder="Masukkan username">
                    </div>
                </div>
                
                <!-- Input Password -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="bi bi-shield-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               value="admin" required placeholder="Masukkan password">
                        <!-- Tombol toggle untuk menampilkan/menyembunyikan password -->
                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Tombol submit login -->
                <button type="submit" class="btn btn-primary w-100" id="loginButton">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Sekarang
                </button>
            </form>
        </div>
        
        <!-- Footer copyright -->
        <div class="copyright">
            <i class="bi bi-c-circle me-1"></i> <?php echo date('Y'); ?> Inventaris Roti • Developed with Zulfiadi Nggolo Soro
            <i class="bi bi-droplet"></i> <i class="bi bi-droplet"></i> <i class="bi bi-droplet"></i>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk toggle visibility password (menampilkan/menyembunyikan password)
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Fungsi untuk membuat partikel animasi (jumlahnya menyesuaikan ukuran layar)
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const screenWidth = window.innerWidth;
            
            // Menyesuaikan jumlah partikel berdasarkan ukuran layar
            let particleCount = 40;
            if (screenWidth < 480) {
                particleCount = 20;
            } else if (screenWidth < 768) {
                particleCount = 30;
            }
            
            // Membuat partikel-partikel
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Ukuran partikel random
                const minSize = screenWidth < 480 ? 2 : 3;
                const maxSize = screenWidth < 480 ? 6 : 10;
                const size = Math.random() * (maxSize - minSize) + minSize;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Posisi random
                particle.style.left = Math.random() * 100 + '%';
                particle.style.bottom = '-10px';
                
                // Delay dan durasi animasi random
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Animasi loading pada tombol saat form disubmit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            button.classList.add('loading');
            button.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Memproses...';
        });
        
        // Memanggil fungsi untuk membuat partikel
        createParticles();
        
        // Efek focus pada input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Menampilkan kartu login saat halaman selesai dimuat
        window.addEventListener('load', function() {
            document.querySelector('.login-card').style.opacity = '1';
        });

        // Menangani resize window (debounced)
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Logika opsional untuk menyesuaikan partikel saat resize
                const particlesContainer = document.getElementById('particles');
                if (window.innerWidth < 480 && particlesContainer.children.length > 20) {
                    // Bisa diimplementasikan regenerasi partikel di sini jika diperlukan
                }
            }, 250);
        });

        // Mencegah zoom pada double tap untuk iOS
        document.addEventListener('touchstart', (e) => {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });

        // Menangani perubahan orientasi layar
        window.addEventListener('orientationchange', function() {
            // Memaksa repaint untuk penyesuaian layout
            document.body.style.display = 'none';
            document.body.offsetHeight; // Trigger reflow
            document.body.style.display = '';
        });
    </script>
</body>
</html>