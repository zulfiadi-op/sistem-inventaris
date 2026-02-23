<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<div class="col-md-3 col-lg-2 sidebar d-md-block bg-dark text-white">
    <div class="position-sticky pt-3" style="min-height: 100vh;">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($current_dir == 'dashboard' || $current_page == 'dashboard.php') ? 'bg-primary rounded' : ''; ?>" href="../dashboard/">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_dir == 'barang' ? 'bg-primary rounded' : ''; ?>" href="../barang/">
                    <i class="bi bi-box me-2"></i> Data Barang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_dir == 'transaksi' ? 'bg-primary rounded' : ''; ?>" href="../transaksi/">
                    <i class="bi bi-arrow-left-right me-2"></i> Transaksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_dir == 'laporan' ? 'bg-primary rounded' : ''; ?>" href="../laporan/">
                    <i class="bi bi-file-text me-2"></i> Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_dir == 'supplier' ? 'bg-primary rounded' : ''; ?>" href="../supplier/">
                    <i class="bi bi-truck me-2"></i> Supplier
                </a>
            </li>
        </ul>
    </div>
</div>