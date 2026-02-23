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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Inventaris</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 250px;
            background-color: #f5f7fb;
            min-height: 100vh;
        }
        
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 1rem 1.5rem;
        }

        .logo-container {
            padding: 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-link-custom {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
        }
        
        .nav-link-custom:hover,
        .nav-link-custom.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link-custom i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
        }

        .user-info {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .main-content { margin-left: 0; }
            .sidebar.active { width: 250px; }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar d-flex flex-column">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> Inventaris Roti</h4>
                <small class="text-light">Management System</small>
            </div>
            
              <nav class="nav flex-column mt-3">
                <a href="../dashboard.php" class="nav-link-custom">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="../barang/" class="nav-link-custom">
                    <i class="bi bi-box"></i> Data Barang
                </a>
                <a href="../transaksi/" class="nav-link-custom">
                    <i class="bi bi-arrow-left-right"></i> Transaksi
                </a>
                <a href="/laporan/laporan.php" class="nav-link-custom active">
                    <i class="bi bi-file-text"></i> Laporan
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
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="main-content flex-grow-1">
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="d-flex align-items-center w-100">
                        <h4 class="mb-0 me-auto d-none d-md-block">
                            <i class="bi bi-box text-primary"></i> Data Barang
                        </h4>
                        
                        <div class="d-flex align-items-center">
                            <span class="me-3 d-none d-md-block">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                            <a href="create.php" class="btn btn-primary rounded-pill">
                                <i class="bi bi-plus-circle me-2"></i> Tambah Barang
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
                <div class="card">      
                    <div class="card-body">
                        <?php if (empty($barang)): ?>
                            <div class="alert alert-info border-0 shadow-sm">
                                <i class="bi bi-info-circle"></i> Belum ada data barang. 
                                <a href="create.php" class="alert-link">Tambah barang pertama</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
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
                                            <td><?php echo $no++; ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['kode_barang']); ?></span></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                            <td><?php echo htmlspecialchars($item['varian_barang']); ?></td>
                                            <td>
                                                <?php if ($item['stok'] < 10): ?>
                                                    <span class="badge bg-danger"><?php echo $item['stok']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo $item['stok']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatRupiah($item['harga_satuan']); ?></td>
                                            <td class="text-primary fw-bold"><?php echo formatRupiah($item['harga_jual']); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm">
                                                    <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger delete-btn"
                                                       data-name="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Delete Confirmation
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const name = this.getAttribute('data-name');
                const url = this.getAttribute('href');
                
                Swal.fire({
                    title: 'Hapus Barang?',
                    html: `Anda akan menghapus: <b>${name}</b>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus'
                }).then((result) => {
                    if (result.isConfirmed) window.location.href = url;
                });
            });
        });
    </script>
</body>
</html>