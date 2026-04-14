<?php
/**
 * File: tambah.php (Halaman Tambah Barang)
 * Fungsi: Menampilkan form dan memproses penambahan data barang baru
 * Akses: Hanya user yang sudah login (requireLogin)
 */

// Memulai session untuk menggunakan fitur session PHP
session_start();

// Memuat file session.php untuk fungsi autentikasi (isLoggedIn, requireLogin, dll)
require_once '../includes/session.php';

// Memuat file functions.php untuk fungsi helper (cleanInput, formatRupiah, dll)
require_once '../includes/functions.php';

// Memastikan user sudah login, jika belum akan redirect ke halaman login
requireLogin();

// ========== KONEKSI DATABASE ==========
// Menggunakan MySQLi (MySQL Improved Extension) untuk koneksi database
require_once __DIR__ . '/../config/database.php'; // Memuat konstanta database

// Membuat koneksi ke database menggunakan konstanta dari config
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ========== GENERATE KODE BARANG OTOMATIS ==========
/**
 * Membuat kode barang otomatis dengan format ROTIxxx
 * Contoh: ROTI001, ROTI002, ROTI003, dst
 */

// Query untuk mengambil kode barang terakhir (urutan DESC, limit 1)
$queryKode = "SELECT kode_barang FROM barang ORDER BY kode_barang DESC LIMIT 1";
$resultKode = mysqli_query($conn, $queryKode);

// Jika ada data barang sebelumnya
if (mysqli_num_rows($resultKode) > 0) {
    // Ambil baris data terakhir
    $row = mysqli_fetch_assoc($resultKode);
    $lastKode = $row['kode_barang']; // Contoh: "ROTI005"
    
    // Ambil angka dari kode terakhir (mulai dari karakter ke-4, karena "ROTI" = 4 karakter)
    // substr($lastKode, 4) akan mengambil "005"
    $angka = (int) substr($lastKode, 4); // Konversi ke integer -> 5
    
    // Increment angka (tambah 1)
    $angka++;
    
    // Format ulang kode: ROTI + angka 3 digit dengan leading zero
    // str_pad($angka, 3, '0', STR_PAD_LEFT) -> 5 menjadi "005"
    $kode_barang_otomatis = 'ROTI' . str_pad($angka, 3, '0', STR_PAD_LEFT);
} else {
    // Jika belum ada data barang, mulai dari ROTI001
    $kode_barang_otomatis = 'ROTI001';
}

// ========== PROSES FORM (METHOD POST) ==========
// Memeriksa apakah form telah disubmit (method POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ========== SANITASI INPUT ==========
    // cleanInput() dari functions.php untuk membersihkan input dari karakter berbahaya
    $kode_barang = cleanInput($_POST['kode_barang']);     // Kode barang (bisa dari generate otomatis)
    $nama_barang = cleanInput($_POST['nama_barang']);     // Nama barang (wajib)
    $varian_barang = cleanInput($_POST['varian_barang']); // Varian barang (optional)
    $stok_barang = (int)$_POST['stok_barang'];            // Stok awal (integer, min 0)
    $keterangan = cleanInput($_POST['keterangan']);       // Keterangan (optional)
    $harga_satuan = (int)$_POST['harga_satuan'];          // Harga beli (integer, min 0)
    
    // ========== HITUNG HARGA JUAL OTOMATIS ==========
    // Harga jual = harga satuan + 50% (markup 50%)
    // Contoh: harga satuan 10.000, maka harga jual = 10.000 * 1.5 = 15.000
    $harga_jual = $harga_satuan * 1.5;
    
    // ========== PREPARED STATEMENT (MENCEGAH SQL INJECTION) ==========
    // Query INSERT dengan placeholder (?) untuk keamanan
    $query = "INSERT INTO barang (kode_barang, nama_barang, varian_barang, 
              stok_barang, keterangan, harga_satuan, harga_jual) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    // Mempersiapkan statement
    $stmt = mysqli_prepare($conn, $query);
    
    // Mengikat parameter ke statement
    // 'sssisii' = tipe data: string, string, string, integer, string, integer, integer
    // s = string, i = integer
    mysqli_stmt_bind_param($stmt, 'sssisii', 
        $kode_barang, $nama_barang, $varian_barang, 
        $stok_barang, $keterangan, $harga_satuan, $harga_jual);
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman index dengan parameter success
        // Parameter digunakan untuk menampilkan notifikasi Sweet Alert
        header('Location: index.php?status=success&message=Barang berhasil ditambahkan');
        exit(); // Hentikan eksekusi setelah redirect
    } else {
        // Jika gagal, simpan pesan error
        $error = "Gagal menambahkan barang! Error: " . mysqli_error($conn);
    }
    
    // Tutup statement untuk membebaskan resource
    mysqli_stmt_close($stmt);
}

// ========== KONFIGURASI HALAMAN ==========
$page_title = 'Tambah Barang'; // Judul halaman untuk header

// ========== INCLUDE HEADER DAN NAVBAR ==========
// Memuat file header.php (berisi meta tags, CSS, opening HTML tags)
include '../includes/header.php';

// Memuat file navbar.php (berisi menu navigasi)
include '../includes/navbar.php';
?>

<!-- ========== STYLE DAN SCRIPT TAMBAHAN ========== -->
<!-- Tambahkan Sweet Alert CSS dan JS untuk notifikasi yang menarik -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- ========== KONTEN UTAMA ========== -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Barang Baru</h1>
    <!-- Tombol kembali ke halaman index -->
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- ========== FORM TAMBAH BARANG ========== -->
<div class="card">
    <div class="card-body">
        
        <!-- ========== MENAMPILKAN ERROR (JIKA ADA) ========== -->
        <?php if (isset($error)): ?>
            <script>
                // Sweet Alert untuk menampilkan pesan error
                Swal.fire({
                    icon: 'error',           // Tipe icon error
                    title: 'Oops...',        // Judul alert
                    text: '<?php echo $error; ?>', // Pesan error
                    showConfirmButton: true, // Tampilkan tombol OK
                    timer: 3000              // Auto close setelah 3 detik
                });
            </script>
        <?php endif; ?>
        
        <!-- Form dengan method POST -->
        <form method="POST" id="formTambahBarang">
            
            <!-- ========== BARIS 1: Kode Barang & Nama Barang ========== -->
            <div class="row mb-3">
                <!-- Kolom Kode Barang (readonly, otomatis) -->
                <div class="col-md-6">
                    <label for="kode_barang" class="form-label">Kode Barang</label>
                    <input type="text" class="form-control" id="kode_barang" 
                           name="kode_barang" value="<?= $kode_barang_otomatis; ?>" readonly>
                    <small class="text-muted">Kode barang dibuat otomatis</small>
                </div>
                
                <!-- Kolom Nama Barang (wajib diisi) -->
                <div class="col-md-6">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" 
                           name="nama_barang" required>
                </div>
            </div>
            
            <!-- ========== BARIS 2: Varian & Stok ========== -->
            <div class="row mb-3">
                <!-- Kolom Varian Barang (optional) -->
                <div class="col-md-6">
                    <label for="varian_barang" class="form-label">Varian</label>
                    <input type="text" class="form-control" id="varian_barang" 
                           name="varian_barang" placeholder="Contoh: Original, Coklat, Keju">
                </div>
                
                <!-- Kolom Stok Awal (wajib, min 0) -->
                <div class="col-md-6">
                    <label for="stok_barang" class="form-label">Stok Awal</label>
                    <input type="number" class="form-control" id="stok_barang" 
                           name="stok_barang" required min="0">
                </div>
            </div>
            
            <!-- ========== BARIS 3: Harga Satuan & Harga Jual ========== -->
            <div class="row mb-3">
                <!-- Kolom Harga Satuan (Harga Beli) -->
                <div class="col-md-6">
                    <label for="harga_satuan" class="form-label">Harga Satuan (Harga Beli)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_satuan" 
                               name="harga_satuan" required min="0" 
                               onchange="calculateHargaJual()"> <!-- Event saat nilai berubah -->
                    </div>
                </div>
                
                <!-- Kolom Harga Jual (Preview, otomatis, readonly) -->
                <div class="col-md-6">
                    <label for="harga_jual_preview" class="form-label">Harga Jual (Otomatis)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <!-- Input readonly untuk preview dengan format Rupiah -->
                        <input type="text" class="form-control" id="harga_jual_preview" readonly 
                               style="background-color: #e9ecef;">
                        <!-- Hidden input untuk menyimpan nilai asli (tanpa format) -->
                        <input type="hidden" id="harga_jual" name="harga_jual">
                    </div>
                    <small class="text-muted">Harga jual dihitung otomatis: Harga satuan + 50%</small>
                </div>
            </div>
            
            <!-- ========== BARIS 4: Keterangan ========== -->
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" 
                          rows="2" placeholder="Catatan tambahan tentang barang (opsional)"></textarea>
            </div>
            
            <!-- Tombol Submit dengan konfirmasi -->
            <button type="button" class="btn btn-primary" onclick="confirmSubmit()">
                <i class="bi bi-save"></i> Simpan Barang
            </button>
        </form>
    </div>
</div>

<!-- ========== JAVASCRIPT UNTUK FORM ========== -->
<script>
/**
 * Menghitung harga jual otomatis berdasarkan harga satuan
 * Rumus: Harga Jual = Harga Satuan × 1.5 (markup 50%)
 * Hasil dibulatkan ke integer terdekat
 */
function calculateHargaJual() {
    // Ambil nilai harga satuan dari input
    const hargaSatuan = document.getElementById('harga_satuan').value;
    
    // Jika harga satuan diisi
    if (hargaSatuan) {
        const markup = 50; // Persentase markup 50%
        
        // Hitung harga jual: hargaSatuan × (1 + markup/100)
        // Contoh: 10000 × (1 + 0.5) = 10000 × 1.5 = 15000
        const hargaJual = Math.round(parseInt(hargaSatuan) * (1 + (markup/100)));
        
        // Tampilkan harga jual dengan format Rupiah (titik sebagai pemisah ribuan)
        document.getElementById('harga_jual_preview').value = hargaJual.toLocaleString('id-ID');
        
        // Simpan nilai asli (integer) ke hidden input untuk dikirim ke server
        document.getElementById('harga_jual').value = hargaJual;
    }
}

/**
 * Menampilkan konfirmasi sebelum menyimpan data
 * Melakukan validasi form terlebih dahulu
 */
function confirmSubmit() {
    // ========== VALIDASI FORM ==========
    // Ambil nilai dari input yang wajib diisi
    const namaBarang = document.getElementById('nama_barang').value;
    const stokBarang = document.getElementById('stok_barang').value;
    const hargaSatuan = document.getElementById('harga_satuan').value;
    
    // Cek apakah field wajib sudah diisi
    if (!namaBarang || !stokBarang || !hargaSatuan) {
        // Tampilkan Sweet Alert warning jika ada field kosong
        Swal.fire({
            icon: 'warning',              // Icon peringatan
            title: 'Form Belum Lengkap',  // Judul
            text: 'Harap isi semua field yang wajib diisi!', // Pesan
            showConfirmButton: true,      // Tampilkan tombol OK
            timer: 3000                   // Auto close setelah 3 detik
        });
        return; // Hentikan eksekusi, jangan lanjutkan submit
    }
    
    // ========== KONFIRMASI DENGAN SWEET ALERT ==========
    // Tampilkan dialog konfirmasi sebelum submit
    Swal.fire({
        title: 'Konfirmasi Simpan',                    // Judul dialog
        text: "Apakah Anda yakin ingin menyimpan data barang ini?", // Pesan konfirmasi
        icon: 'question',                              // Icon tanda tanya
        showCancelButton: true,                        // Tampilkan tombol batal
        confirmButtonColor: '#3085d6',                 // Warna tombol konfirmasi (biru)
        cancelButtonColor: '#d33',                     // Warna tombol batal (merah)
        confirmButtonText: 'Ya, Simpan!',              // Teks tombol konfirmasi
        cancelButtonText: 'Batal',                     // Teks tombol batal
        showLoaderOnConfirm: true,                     // Tampilkan loading saat submit
        preConfirm: () => {
            return new Promise((resolve) => {
                // Submit form secara programatis
                document.getElementById('formTambahBarang').submit();
                resolve();
            });
        }
    });
}

/**
 * Inisialisasi: Hitung harga jual saat halaman pertama kali dimuat
 * Event listener untuk DOMContentLoaded (setelah seluruh HTML selesai dimuat)
 */
document.addEventListener('DOMContentLoaded', function() {
    calculateHargaJual(); // Panggil fungsi perhitungan harga jual
});

/**
 * ========== NOTIFIKASI SUKSES (DARI PARAMETER URL) ==========
 * Jika ada parameter status=success di URL, tampilkan notifikasi sukses
 * Contoh URL: index.php?status=success&message=Barang+berhasil+ditambahkan
 */
<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan Sweet Alert sukses
    Swal.fire({
        icon: 'success',                               // Icon centang hijau
        title: 'Berhasil!',                            // Judul
        text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'Data barang berhasil ditambahkan'; ?>', // Pesan
        showConfirmButton: false,                      // Sembunyikan tombol OK
        timer: 2000                                    // Auto close setelah 2 detik
    }).then(() => {
        // Setelah notifikasi selesai, redirect ke halaman index
        window.location.href = 'index.php';
    });
});
<?php endif; ?>
</script>

<?php
/**
 * ========== PENUTUP ==========
 * Catatan: File ini biasanya dilanjutkan dengan include footer.php
 * Namun dalam kode yang diberikan tidak ada, pastikan untuk menambahkan
 * include '../includes/footer.php'; jika diperlukan
 */
?>

<!--
CATATAN PENTING UNTUK PENGEMBANGAN:
1. Koneksi database ($conn) belum ditutup dengan mysqli_close($conn)
   Sebaiknya tambahkan mysqli_close($conn) di akhir file atau gunakan destructor

2. File ini menggunakan Sweet Alert untuk notifikasi yang lebih menarik
   Pastikan koneksi internet tersedia untuk mengakses CDN

3. Harga jual dihitung dengan markup 50%, bisa disesuaikan dengan kebutuhan

4. Validasi sisi client (JavaScript) sudah ada, tapi tetap perlu validasi sisi server
   untuk keamanan (sudah menggunakan prepared statement)

5. Kode barang otomatis dengan format ROTIxxx, bisa disesuaikan dengan kebutuhan

6. Fungsi cleanInput() diasumsikan ada di functions.php untuk sanitasi input