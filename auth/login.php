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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login - Inventaris Roti</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        /* Ocean wave animation */
        @keyframes gradientWave {
            0% { background-position: 0% 50%; }
            25% { background-position: 50% 75%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 50% 25%; }
            100% { background-position: 0% 50%; }
        }

        /* Animated background bubbles - responsive */
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(2px);
            animation: bubbleFloat 8s infinite;
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

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

        /* Login wrapper - fully responsive */
        .login-wrapper {
            width: 100%;
            max-width: min(480px, 95%);
            margin: 0 auto;
            position: relative;
            z-index: 20;
            animation: floatIn 1.2s cubic-bezier(0.23, 1, 0.32, 1);
        }

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

        /* Login card - responsive */
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

        .login-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 30px 60px rgba(11, 47, 158, 0.3), 0 0 40px rgba(59, 130, 246, 0.4);
            border-color: var(--light-blue);
        }

        /* Responsive hover effect for touch devices */
        @media (hover: none) {
            .login-card:hover {
                transform: none;
            }
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(96, 165, 250, 0.2), 
                rgba(37, 99, 235, 0.2), 
                rgba(11, 47, 158, 0.2), 
                transparent
            );
            animation: shimmer 8s infinite;
            transform: skewX(-15deg);
        }

        @keyframes shimmer {
            0% { left: -100%; }
            20% { left: 100%; }
            100% { left: 100%; }
        }

        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #0B2F9E, #2563EB, #60A5FA, #2563EB, #0B2F9E);
            background-size: 200% 200%;
            animation: gradientMove 4s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Icon box - responsive */
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

        .icon-box i {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            background: linear-gradient(145deg, #0B2F9E, #2563EB, #60A5FA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: rotate 20s linear infinite;
            transform: rotate(-45deg);
        }

        @keyframes morphIn {
            0% {
                opacity: 0;
                border-radius: 50%;
                transform: rotate(0deg) scale(0.5);
            }
            50% {
                border-radius: min(3rem, 40px);
                transform: rotate(25deg) scale(1.1);
            }
            100% {
                opacity: 1;
                border-radius: min(2.5rem, 30px) min(2.5rem, 30px) min(1rem, 15px) min(2.5rem, 30px);
                transform: rotate(45deg) scale(1);
            }
        }

        @keyframes rotate {
            from { transform: rotate(-45deg) rotate(0deg); }
            to { transform: rotate(-45deg) rotate(360deg); }
        }

        .icon-box::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(145deg, #0B2F9E, #60A5FA);
            border-radius: inherit;
            z-index: -1;
            animation: borderPulse 2s ease-in-out infinite;
            opacity: 0.3;
        }

        @keyframes borderPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }

        /* Typography - responsive */
        h1 {
            font-weight: 800;
            font-size: clamp(1.5rem, 6vw, 2.5rem);
            margin-bottom: 0.25rem;
            background: linear-gradient(145deg, #0B2F9E, #2563EB, #3B82F6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: slideUpText 0.8s ease 0.2s both;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .subtitle {
            color: var(--secondary);
            font-weight: 600;
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            margin-bottom: 0.5rem;
            animation: slideUpText 0.8s ease 0.3s both;
            text-shadow: 0 2px 5px rgba(37, 99, 235, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
        }

        .text-muted {
            color: #4B5563 !important;
            font-size: clamp(0.8rem, 2.5vw, 0.95rem);
            margin-bottom: clamp(1.5rem, 4vw, 2.5rem);
            animation: slideUpText 0.8s ease 0.4s both;
        }

        @keyframes slideUpText {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form group - responsive */
        .form-group {
            margin-bottom: clamp(1.5rem, 3vw, 2rem);
            animation: slideInRight 0.8s ease;
            position: relative;
        }

        .form-group:nth-child(1) { animation-delay: 0.4s; }
        .form-group:nth-child(2) { animation-delay: 0.5s; }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-label {
            font-weight: 600;
            font-size: clamp(0.8rem, 2.5vw, 0.9rem);
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .form-label i {
            color: var(--secondary);
            margin-right: 8px;
            transition: transform 0.3s ease;
            font-size: clamp(0.9rem, 3vw, 1rem);
        }

        .form-group:hover .form-label i {
            transform: translateX(5px);
            color: var(--light-blue);
        }

        /* Input group - responsive */
        .input-group {
            transition: all 0.3s ease;
            border-radius: clamp(1rem, 3vw, 1.2rem);
            overflow: hidden;
            border: 2px solid transparent;
            box-shadow: 0 4px 10px rgba(11, 47, 158, 0.1);
            flex-wrap: nowrap;
        }

        .input-group:focus-within {
            transform: scale(1.02) translateY(-2px);
            border-color: var(--accent);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .input-group-text {
            background: linear-gradient(145deg, #F0F9FF, #E0F2FE);
            border: none;
            color: var(--secondary);
            padding: clamp(0.6rem, 2vw, 0.875rem) clamp(0.8rem, 3vw, 1.5rem);
            transition: all 0.3s ease;
            font-size: clamp(0.9rem, 3vw, 1.1rem);
        }

        .input-group:focus-within .input-group-text {
            color: var(--primary);
            background: linear-gradient(145deg, #E0F2FE, #DBEAFE);
        }

        .form-control {
            border: none;
            padding: clamp(0.6rem, 2vw, 0.875rem) clamp(0.8rem, 3vw, 1.5rem);
            font-size: clamp(0.9rem, 3vw, 1rem);
            background: linear-gradient(145deg, #F0F9FF, #E0F2FE);
            transition: all 0.3s ease;
            min-width: 0; /* Prevents overflow on small screens */
        }

        .form-control:focus {
            background: linear-gradient(145deg, #FFFFFF, #F0F9FF);
            outline: none;
        }

        .form-control::placeholder {
            color: #94A3B8;
            font-weight: 400;
            font-size: clamp(0.8rem, 2.5vw, 0.9rem);
        }

        /* Alert - responsive */
        .alert {
            border-radius: clamp(1rem, 3vw, 1.2rem);
            font-size: clamp(0.8rem, 2.5vw, 0.9rem);
            font-weight: 500;
            margin-bottom: clamp(1.5rem, 4vw, 2rem);
            border: none;
            background: linear-gradient(145deg, rgba(37, 99, 235, 0.1), rgba(96, 165, 250, 0.1));
            color: var(--secondary);
            backdrop-filter: blur(10px);
            animation: slideInShake 0.6s ease;
            padding: clamp(0.8rem, 2vw, 1rem) clamp(1rem, 3vw, 1.5rem);
        }

        @keyframes slideInShake {
            0% {
                opacity: 0;
                transform: translateX(-30px);
            }
            60% {
                transform: translateX(5px);
            }
            80% {
                transform: translateX(-3px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Button - responsive */
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

        /* Responsive button text */
        @media (max-width: 360px) {
            .btn-primary {
                white-space: normal;
                line-height: 1.4;
            }
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
            z-index: -1;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 35px rgba(11, 47, 158, 0.4), 0 0 25px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px) scale(0.98);
        }

        /* Particles - responsive */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(145deg, rgba(96, 165, 250, 0.4), rgba(37, 99, 235, 0.3));
            animation: particleRise 20s infinite;
            backdrop-filter: blur(2px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        @keyframes particleRise {
            0% {
                transform: translateY(100vh) rotate(0deg) scale(0.5);
                opacity: 0;
            }
            20% {
                opacity: 0.8;
                transform: translateY(80vh) rotate(90deg) scale(1);
            }
            80% {
                opacity: 0.8;
                transform: translateY(20vh) rotate(270deg) scale(1);
            }
            100% {
                transform: translateY(-20vh) rotate(360deg) scale(0.5);
                opacity: 0;
            }
        }

        /* Copyright - responsive */
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

        .copyright i {
            color: var(--light-blue);
            animation: heartBeat 1.5s ease infinite;
        }

        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.1); }
            50% { transform: scale(1); }
            75% { transform: scale(1.1); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading animation */
        .btn-primary.loading {
            pointer-events: none;
            opacity: 0.9;
            background: linear-gradient(145deg, #0B2F9E, #2563EB);
        }

        .btn-primary.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Bread icon - responsive */
        .bread-icon {
            font-size: clamp(1.2rem, 5vw, 2rem);
            margin-right: 0.5rem;
            color: var(--light-blue);
            animation: bounce 2s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Responsive breakpoints adjustments */
        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            
            .login-card {
                border-radius: 28px;
            }
            
            .input-group-text {
                padding: 0.6rem 1rem;
            }
            
            .copyright {
                line-height: 1.5;
            }
        }

        @media (max-width: 360px) {
            .input-group {
                flex-wrap: wrap;
            }
            
            .input-group > :first-child {
                border-radius: 1rem 0 0 0;
            }
            
            .input-group > :last-child {
                border-radius: 0 0 1rem 1rem;
            }
            
            .form-control {
                text-align: left;
            }
        }

        @media (min-width: 768px) {
            .login-wrapper {
                max-width: 500px;
            }
        }

        /* Landscape mode optimization */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            
            .login-wrapper {
                max-width: min(480px, 90%);
            }
            
            .login-card {
                padding: 1.2rem;
            }
            
            .icon-box {
                width: 60px;
                height: 60px;
                margin-bottom: 1rem;
            }
            
            .icon-box i {
                font-size: 2rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .copyright {
                margin-top: 1rem;
            }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn-primary:hover {
                transform: none;
            }
            
            .input-group:hover {
                transform: none;
            }
            
            .form-control, 
            .btn-primary {
                font-size: 16px; /* Prevents zoom on focus in iOS */
            }
        }

        /* High-resolution screens */
        @media (min-width: 1440px) {
            .login-wrapper {
                max-width: 520px;
            }
            
            .login-card {
                padding: 3.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating bubbles background -->
    <div class="bubble bubble-1"></div>
    <div class="bubble bubble-2"></div>
    <div class="bubble bubble-3"></div>
    <div class="bubble bubble-4"></div>
    
    <!-- Particles -->
    <div class="particles" id="particles"></div>
    
    <div class="login-wrapper">
        <div class="login-card">
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
            
            <?php if ($error): ?>
            <div class="alert alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <div><?php echo $error; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
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
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="bi bi-shield-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               value="admin" required placeholder="Masukkan password">
                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" id="loginButton">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Sekarang
                </button>
            </form>
        </div>
        
        <div class="copyright">
            <i class="bi bi-c-circle me-1"></i> <?php echo date('Y'); ?> Inventaris Roti • Developed with Zulfiadi Nggolo Soro
            <i class="bi bi-droplet"></i> <i class="bi bi-droplet"></i> <i class="bi bi-droplet"></i>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
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
        
        // Create particles - responsive count based on screen size
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const screenWidth = window.innerWidth;
            
            // Adjust particle count based on screen size
            let particleCount = 40;
            if (screenWidth < 480) {
                particleCount = 20;
            } else if (screenWidth < 768) {
                particleCount = 30;
            }
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size between 3px and 10px, adjusted for mobile
                const minSize = screenWidth < 480 ? 2 : 3;
                const maxSize = screenWidth < 480 ? 6 : 10;
                const size = Math.random() * (maxSize - minSize) + minSize;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.bottom = '-10px';
                
                // Random animation delay and duration
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Button loading animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            button.classList.add('loading');
            button.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Memproses...';
        });
        
        // Initialize particles
        createParticles();
        
        // Input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Smooth scroll reveal for card
        window.addEventListener('load', function() {
            document.querySelector('.login-card').style.opacity = '1';
        });

        // Handle resize events to adjust particles if needed (optional)
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Optional: regenerate particles on resize if needed
                const particlesContainer = document.getElementById('particles');
                if (window.innerWidth < 480 && particlesContainer.children.length > 20) {
                    // Could implement particle regeneration here if desired
                }
            }, 250);
        });

        // Prevent zoom on double tap for iOS
        document.addEventListener('touchstart', (e) => {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });

        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            // Force repaint for any layout adjustments
            document.body.style.display = 'none';
            document.body.offsetHeight; // Trigger reflow
            document.body.style.display = '';
        });
    </script>
</body>
</html>