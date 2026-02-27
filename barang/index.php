<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
requireLogin();

$barang = getAllBarang();
$page_title = 'Data Barang';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> - Sistem Inventaris</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --sidebar-width: 250px;
            --sidebar-width-collapsed: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            overflow-x: hidden;
            background-color: #f5f7fb;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            position: fixed;
            width: var(--sidebar-width);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }
        
        .sidebar.collapsed .logo-container h4,
        .sidebar.collapsed .logo-container small,
        .sidebar.collapsed .nav-link-custom span,
        .sidebar.collapsed .user-info .flex-grow-1,
        .sidebar.collapsed .user-info .btn span {
            display: none;
        }
        
        .sidebar.collapsed .nav-link-custom {
            text-align: center;
            padding: 12px 0;
            margin: 5px;
        }
        
        .sidebar.collapsed .nav-link-custom i {
            margin-right: 0;
            font-size: 1.3rem;
        }
        
        .sidebar.collapsed .user-info .d-flex {
            justify-content: center;
        }
        
        .sidebar.collapsed .user-info .ms-3 {
            margin-left: 0 !important;
        }
        
        .sidebar.collapsed .btn i {
            margin-right: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            background-color: #f5f7fb;
            min-height: 100vh;
            transition: all 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-width-collapsed);
            width: calc(100% - var(--sidebar-width-collapsed));
        }
        
        /* Logo Container */
        .logo-container {
            padding: 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .logo-container h4 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }
        
        .logo-container small {
            font-size: 0.75rem;
            opacity: 0.9;
            white-space: nowrap;
        }
        
        /* Navigation Links */
        .nav-link-custom {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .nav-link-custom:hover,
        .nav-link-custom.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link-custom i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .nav-link-custom span {
            font-size: 0.95rem;
        }
        
        /* User Info */
        .user-info {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: auto;
        }
        
        .user-info h6 {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }
        
        .user-info small {
            font-size: 0.75rem;
            opacity: 0.8;
            white-space: nowrap;
        }
        
        .user-info .btn {
            transition: all 0.3s ease;
            border-color: rgba(255,255,255,0.3);
            color: white;
            white-space: nowrap;
        }
        
        .user-info .btn:hover {
            background-color: white;
            color: var(--primary-color);
            border-color: white;
        }
        
        /* Navbar Custom */
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .navbar-custom h4 {
            font-size: 1.5rem;
            margin-bottom: 0;
            color: var(--dark-color);
        }
        
        .navbar-custom h4 i {
            color: var(--primary-color);
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.03);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Table Styles */
        .table {
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        
        .table thead th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #eee;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }
        
        /* Badge Styles */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        
        /* Button Group */
        .btn-group {
            gap: 5px;
            box-shadow: none !important;
        }
        
        .btn-group .btn {
            border-radius: 8px !important;
            padding: 0.4rem 0.8rem;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        .btn-group .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-group .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-group .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-group .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }
        
        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .alert-info {
            background-color: #e7f1ff;
            color: #004085;
        }
        
        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .table {
                font-size: 0.85rem;
            }
            
            .btn-group .btn {
                padding: 0.3rem 0.6rem;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-custom h4 {
                font-size: 1.25rem;
            }
            
            .table td {
                padding: 0.75rem 0.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            .sidebar.active {
                transform: translateX(0);
                width: var(--sidebar-width);
                box-shadow: 2px 0 20px rgba(0,0,0,0.2);
            }
            
            .sidebar.active .logo-container h4,
            .sidebar.active .logo-container small,
            .sidebar.active .nav-link-custom span,
            .sidebar.active .user-info .flex-grow-1,
            .sidebar.active .user-info .btn span {
                display: block;
            }
            
            .sidebar.active .nav-link-custom {
                text-align: left;
                padding: 12px 20px;
                margin: 5px 10px;
            }
            
            .sidebar.active .nav-link-custom i {
                margin-right: 10px;
                font-size: 1.1rem;
            }
            
            .sidebar.active .user-info .d-flex {
                justify-content: flex-start;
            }
            
            .sidebar.active .user-info .ms-3 {
                margin-left: 1rem !important;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            .navbar-custom {
                padding: 0.75rem 1rem;
            }
            
            .navbar-custom h4 {
                font-size: 1.1rem;
            }
            
            .container-fluid {
                padding: 1rem !important;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .table thead {
                display: none;
            }
            
            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }
            
            .table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 10px;
                background-color: white;
                padding: 1rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            
            .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 0;
                border: none;
                border-bottom: 1px solid #eee;
            }
            
            .table td:last-child {
                border-bottom: none;
            }
            
            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
                font-size: 0.85rem;
                margin-right: 1rem;
                min-width: 100px;
            }
            
            .table td[data-label="Aksi"]::before {
                content: "Aksi";
            }
            
            .btn-group {
                justify-content: flex-end;
            }
            
            .btn-group .btn {
                padding: 0.5rem 1rem;
            }
            
            .btn i {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-custom {
                padding: 0.5rem 0.75rem;
            }
            
            .navbar-custom .btn {
                padding: 0.4rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .navbar-custom h4 {
                font-size: 1rem;
            }
            
            .container-fluid {
                padding: 0.75rem !important;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .table td::before {
                min-width: 80px;
                font-size: 0.8rem;
            }
            
            .badge {
                font-size: 0.7rem;
            }
            
            .btn-group .btn {
                padding: 0.4rem 0.6rem;
            }
            
            .btn-group .btn i {
                font-size: 0.9rem;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .navbar-custom, .btn-group, .user-info .btn {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
        
        /* Loading Animation */
        .swal2-loading {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
        
        /* Additional Utilities */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .cursor-pointer {
            cursor: pointer;
        }
        
        .hover-scale {
            transition: transform 0.2s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column" id="sidebar">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> <span>Inventaris Roti</span></h4>
                <small class="text-light"><span>Management System</span></small>
            </div>
            
            <nav class="nav flex-column mt-3">
                <a href="../dashboard.php" class="nav-link-custom">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../barang/" class="nav-link-custom active">
                    <i class="bi bi-box"></i>
                    <span>Data Barang</span>
                </a>
                <a href="../transaksi/" class="nav-link-custom">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transaksi</span>
                </a>
                <a href="../laporan/laporan.php" class="nav-link-custom">
                    <i class="bi bi-file-text"></i>
                    <span>Laporan</span>
                </a>
            </nav>
            
            <div class="user-info mt-auto">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-person-circle" style="font-size: 2rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0"><?php echo $_SESSION['username'] ?? 'User'; ?></h6>
                        <small>Administrator</small>
                    </div>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1" id="mainContent">
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <div class="d-flex align-items-center w-100">
                        <!-- Sidebar Toggle Buttons -->
                        <button class="btn btn-outline-primary me-3 d-none d-md-block" id="sidebarCollapse">
                            <i class="bi bi-layout-sidebar"></i>
                        </button>
                        <button class="btn btn-outline-primary d-md-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        
                        <h4 class="mb-0 me-auto">
                            <i class="bi bi-box text-primary"></i> Data Barang
                        </h4>
                        
                        <div class="d-flex align-items-center gap-2">
                            <span class="me-3 d-none d-lg-block text-muted">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                            <a href="create.php" class="btn btn-primary rounded-pill">
                                <i class="bi bi-plus-circle me-2"></i> 
                                <span class="d-none d-sm-inline">Tambah Barang</span>
                                <span class="d-sm-none">Tambah</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
                <div class="card">      
                    <div class="card-body">
                        <?php if (empty($barang)): ?>
                            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                                <i class="bi bi-info-circle fs-4 me-3"></i>
                                <div>
                                    Belum ada data barang. 
                                    <a href="create.php" class="alert-link fw-bold">Tambah barang pertama</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Nama Barang</th>
                                            <th>Varian</th>
                                            <th>Stok</th>
                                            <th>Harga Satuan</th>
                                            <th>Harga Jual</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($barang as $item): ?>
                                        <tr>
                                            <td data-label="No"><?php echo $no++; ?></td>
                                            <td data-label="Kode">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['kode_barang']); ?></span>
                                            </td>
                                            <td data-label="Nama Barang" class="fw-bold">
                                                <?php echo htmlspecialchars($item['nama_barang']); ?>
                                            </td>
                                            <td data-label="Varian">
                                                <?php echo htmlspecialchars($item['varian_barang']); ?>
                                            </td>
                                            <td data-label="Stok">
                                                <?php if ($item['stok'] < 10): ?>
                                                    <span class="badge bg-danger"><?php echo $item['stok']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo $item['stok']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Harga Satuan">
                                                <?php echo formatRupiah($item['harga_satuan']); ?>
                                            </td>
                                            <td data-label="Harga Jual" class="text-primary fw-bold">
                                                <?php echo formatRupiah($item['harga_jual']); ?>
                                            </td>
                                            <td data-label="Aksi" class="text-center">
                                                <div class="btn-group shadow-sm">
                                                    <a href="edit.php?id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Edit <?php echo htmlspecialchars($item['nama_barang']); ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger delete-btn"
                                                       data-name="<?php echo htmlspecialchars($item['nama_barang']); ?>"
                                                       title="Hapus <?php echo htmlspecialchars($item['nama_barang']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Info total data -->
                            <div class="mt-3 d-flex justify-content-between align-items-center text-muted">
                                <small>
                                    <i class="bi bi-database"></i> Total <?php echo count($barang); ?> barang
                                </small>
                                <small class="d-none d-md-block">
                                    <i class="bi bi-arrow-repeat"></i> Terakhir diperbarui: <?php echo date('H:i'); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar Collapse untuk Desktop
        document.getElementById('sidebarCollapse')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
            
            // Simpan status sidebar di localStorage
            const isCollapsed = document.querySelector('.sidebar').classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Tutup sidebar saat klik di luar untuk mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Cek status sidebar dari localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && window.innerWidth > 768) {
                document.querySelector('.sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }

            // Handle parameter URL untuk SweetAlert
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            
            const alertConfig = {
                icon: 'success',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            };
            
            if (message === 'update_success') {
                Swal.fire({
                    ...alertConfig,
                    title: 'Berhasil!',
                    text: 'Data barang telah diperbarui.'
                }).then(cleanUrl);
            }
            
            if (message === 'create_success') {
                Swal.fire({
                    ...alertConfig,
                    title: 'Berhasil!',
                    text: 'Data barang telah ditambahkan.'
                }).then(cleanUrl);
            }
            
            if (message === 'delete_success') {
                Swal.fire({
                    ...alertConfig,
                    title: 'Berhasil!',
                    text: 'Data barang telah dihapus.'
                }).then(cleanUrl);
            }
        });

        // Bersihkan parameter URL
        function cleanUrl() {
            const url = new URL(window.location);
            url.searchParams.delete('message');
            window.history.replaceState({}, document.title, url);
        }

        // Delete Confirmation dengan SweetAlert
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const name = this.getAttribute('data-name');
                const url = this.getAttribute('href');
                
                Swal.fire({
                    title: 'Hapus Barang?',
                    html: `Anda akan menghapus: <strong>${name}</strong><br><br>Data yang dihapus tidak dapat dikembalikan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return new Promise((resolve) => {
                            setTimeout(() => {
                                window.location.href = url;
                            }, 500);
                        });
                    }
                });
            });
        });

        // Auto hide alerts setelah 5 detik
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Inisialisasi tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle resize window
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const sidebar = document.querySelector('.sidebar');
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    sidebar.classList.remove('collapsed');
                    document.getElementById('mainContent').classList.remove('expanded');
                } else {
                    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (isCollapsed) {
                        sidebar.classList.add('collapsed');
                        document.getElementById('mainContent').classList.add('expanded');
                    }
                }
            }, 250);
        });

        // Fungsi untuk refresh data
        function refreshData() {
            Swal.fire({
                title: 'Memuat ulang...',
                text: 'Harap tunggu sebentar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            });
        }

        // Keyboard shortcut untuk refresh (Ctrl+R)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshData();
            }
        });
    </script>
</body>
</html>