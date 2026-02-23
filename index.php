<?php
header('Location: auth/login.php');
exit();
?>
<?php
require_once '/includes/session.php';
require_once '/includes/functions.php';
requireLogin();

$barang = getAllBarang();

// Pesan sukses/hapus
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'add_success':
            $message = showAlert('success', 'Barang berhasil ditambahkan!');
            break;
        case 'update_success':
            $message = showAlert('success', 'Barang berhasil diperbarui!');
            break;
        case 'delete_success':
            $message = showAlert('success', 'Barang berhasil dihapus!');
            break;
        case 'delete_error':
            $message = showAlert('error', 'Gagal menghapus barang!');
            break;
    }
}

$page_title = 'Data Barang';
?>
<?php include '/includes/header.php'; ?>
<?php include '/includes/navbar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Barang</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Barang
        </a>
    </div>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($barang)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Belum ada data barang. 
                <a href="create.php" class="alert-link">Tambah barang pertama</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Varian</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Harga Beli</th>
                            <th>Harga Jual</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($barang as $item): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['kode_barang']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                            <td><?php echo htmlspecialchars($item['varian_barang']); ?></td>
                            <td>
                                <?php if ($item['stok_barang'] < 10): ?>
                                    <span class="badge bg-warning text-dark"><?php echo $item['stok_barang']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?php echo $item['stok_barang']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['satuan']); ?></td>
                            <td><?php echo formatRupiah($item['harga_beli']); ?></td>
                            <td><?php echo formatRupiah($item['harga_jual']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Yakin ingin menghapus barang ini?')">
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
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<!-- Custom CSS -->
<link rel="stylesheet" href="../css/style-new.css">
<?php include '/includes/footer.php'; ?>
