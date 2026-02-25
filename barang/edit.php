<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/session.php';
require_once '../includes/functions.php';
requireLogin();

// Gunakan mysqli dari config
require_once __DIR__ . '/../config/database.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Ambil ID dari URL
$id = $_GET['id'] ?? 0;

// Ambil data barang dari database
$barang = getBarangById($id);

// Jika barang tidak ditemukan, redirect ke halaman index
if (!$barang) {
    header('Location: index.php');
    exit();
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = cleanInput($_POST['kode_barang']);
    $nama_barang = cleanInput($_POST['nama_barang']);
    $varian_barang = cleanInput($_POST['varian_barang']);
    $stok_barang = (int)$_POST['stok_barang'];
    $keterangan = cleanInput($_POST['keterangan']);
    $harga_satuan = (int)$_POST['harga_satuan'];
    $harga_jual = (int)$_POST['harga_jual'];
    
    // Jika harga_jual kosong, hitung otomatis
    if (empty($harga_jual) && !empty($harga_satuan)) {
        $harga_jual = calculateHargaJual($harga_satuan, 50);
    }
    
    // Gunakan mysqli untuk update
    $query = "UPDATE barang SET 
              kode_barang = ?, 
              nama_barang = ?, 
              varian_barang = ?, 
              stok_barang = ?, 
              keterangan = ?, 
              harga_satuan = ?, 
              harga_jual = ?
              WHERE id_barang = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    
    mysqli_stmt_bind_param($stmt, 'sssisiii', 
        $kode_barang, 
        $nama_barang, 
        $varian_barang, 
        $stok_barang, 
        $keterangan, 
        $harga_satuan, 
        $harga_jual, 
        $id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        // Redirect ke halaman index dengan parameter sukses
        header('Location: index.php?message=update_success');
        exit();
    } else {
        $error = "Gagal mengupdate barang! Error: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

$page_title = 'Edit Barang';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Barang</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?php echo addslashes($error); ?>',
                showConfirmButton: true,
                timer: 5000
            });
            </script>
        <?php endif; ?>
        
        <form method="POST" action="" id="editForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="kode_barang" class="form-label">Kode Barang</label>
                    <input type="text" class="form-control" id="kode_barang" name="kode_barang" 
                           value="<?php echo htmlspecialchars($barang['kode_barang'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                           value="<?php echo htmlspecialchars($barang['nama_barang'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="varian_barang" class="form-label">Varian</label>
                    <input type="text" class="form-control" id="varian_barang" name="varian_barang" 
                           value="<?php echo htmlspecialchars($barang['varian_barang'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="stok_barang" class="form-label">Stok</label>
                    <input type="number" class="form-control" id="stok_barang" name="stok_barang" 
                           value="<?php echo htmlspecialchars($barang['stok_barang'] ?? 0); ?>" required min="0">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?php echo htmlspecialchars($barang['keterangan'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="harga_satuan" class="form-label">Harga Satuan (Harga Beli)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" 
                               value="<?php echo htmlspecialchars($barang['harga_satuan'] ?? 0); ?>" 
                               required min="0" oninput="calculateHargaJual()">
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="harga_jual" class="form-label">Harga Jual</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                               value="<?php echo htmlspecialchars($barang['harga_jual'] ?? 0); ?>" 
                               required min="0" oninput="calculateHargaJual()">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mt-4">
                        <div class="alert alert-info py-2 px-3 mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Margin: <strong id="margin_text">
                                    <?php 
                                    $harga_beli = $barang['harga_satuan'] ?? 0;
                                    $harga_jual_val = $barang['harga_jual'] ?? 0;
                                    $margin = $harga_jual_val - $harga_beli;
                                    $persen = ($harga_beli > 0) ? round(($margin/$harga_beli)*100, 1) : 0;
                                    echo 'Rp ' . number_format($margin, 0, ',', '.') . " ($persen%)";
                                    ?>
                                </strong>
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" onclick="applyDefaultMarkup()">
                            <i class="bi bi-calculator"></i> Hitung dengan Markup 50%
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <button type="submit" class="btn btn-primary" id="btnUpdate">
                    <i class="bi bi-save"></i> Update Barang
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="confirmCancel()">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi untuk inisialisasi halaman
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi margin display
    calculateHargaJual();
    
    // Tambahkan event listener untuk form submit
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        confirmUpdate();
    });
});

function calculateHargaJual() {
    const hargaSatuan = parseFloat(document.getElementById('harga_satuan').value) || 0;
    const hargaJualInput = parseFloat(document.getElementById('harga_jual').value) || 0;
    
    if (hargaSatuan > 0 && hargaJualInput > 0) {
        const margin = hargaJualInput - hargaSatuan;
        const persen = ((margin / hargaSatuan) * 100).toFixed(1);
        document.getElementById('margin_text').innerHTML = 
            `Rp ${margin.toLocaleString('id-ID')} (${persen}%)`;
        
        // Beri warna berdasarkan margin
        const marginElement = document.getElementById('margin_text');
        if (persen < 20) {
            marginElement.className = 'text-danger';
        } else if (persen < 40) {
            marginElement.className = 'text-warning';
        } else {
            marginElement.className = 'text-success';
        }
    } else {
        document.getElementById('margin_text').innerHTML = 'Rp 0 (0%)';
        document.getElementById('margin_text').className = '';
    }
}

function applyDefaultMarkup() {
    const hargaSatuan = document.getElementById('harga_satuan').value;
    if (hargaSatuan && parseFloat(hargaSatuan) > 0) {
        const hargaJual = Math.round(parseFloat(hargaSatuan) * 1.5);
        document.getElementById('harga_jual').value = hargaJual;
        calculateHargaJual();
        
        // Tampilkan notifikasi kecil
        Swal.fire({
            icon: 'info',
            title: 'Harga Jual Dihitung',
            text: `Harga jual dengan markup 50%: Rp ${hargaJual.toLocaleString('id-ID')}`,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Masukkan harga satuan terlebih dahulu!',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
}

function confirmUpdate() {
    // Validasi form
    const form = document.getElementById('editForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    Swal.fire({
        title: 'Konfirmasi Update',
        text: "Apakah Anda yakin ingin menyimpan perubahan data barang?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Update!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan loading
            Swal.fire({
                title: 'Memproses...',
                text: 'Harap tunggu sebentar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                    // Submit form
                    form.submit();
                }
            });
        }
    });
}

function confirmCancel() {
    Swal.fire({
        title: 'Batalkan Edit?',
        text: "Perubahan yang belum disimpan akan hilang!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Kembali',
        cancelButtonText: 'Tetap di Sini'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php';
        }
    });
}

// Peringatan jika ada perubahan sebelum meninggalkan halaman
let formChanged = false;
document.querySelectorAll('#editForm input, #editForm textarea').forEach(element => {
    element.addEventListener('change', () => {
        formChanged = true;
    });
    
    element.addEventListener('input', () => {
        formChanged = true;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<style>
/* Style tambahan untuk SweetAlert */
.swal2-popup {
    font-size: 0.9rem;
}

.swal2-toast {
    font-size: 0.85rem;
}

/* Style untuk margin text */
#margin_text {
    font-weight: bold;
    transition: color 0.3s ease;
}

.text-danger {
    color: #dc3545 !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-success {
    color: #28a745 !important;
}

/* Hover effect untuk button */
.btn-outline-primary:hover {
    transform: translateY(-2px);
    transition: transform 0.2s;
}

/* Animasi loading */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.btn-primary:disabled {
    animation: pulse 1.5s infinite;
}
</style>

<?php 
$footer_path = '../includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo '</div></div></body></html>';
}
?>