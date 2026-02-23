// JavaScript untuk Sistem Inventaris

// Validasi form
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    for (let input of inputs) {
        if (!input.value.trim()) {
            alert(`Field ${input.name} harus diisi!`);
            input.focus();
            return false;
        }
    }
    
    return true;
}

// Validasi angka tidak negatif
function validateNonNegative(inputId) {
    const input = document.getElementById(inputId);
    if (input.value < 0) {
        alert('Nilai tidak boleh minus!');
        input.value = 0;
        input.focus();
        return false;
    }
    return true;
}

// Format Rupiah untuk input
function formatRupiahInput(inputId) {
    const input = document.getElementById(inputId);
    let value = input.value.replace(/[^0-9]/g, '');
    
    if (value) {
        value = parseInt(value);
        input.value = value.toLocaleString('id-ID');
    }
}

// Konfirmasi sebelum hapus
function confirmDelete(itemName) {
    return confirm(`Yakin ingin menghapus ${itemName}? Tindakan ini tidak dapat dibatalkan.`);
}

// Auto-hide alert setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });
    
    // Search functionality
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('.table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Update stok info saat pilih barang di transaksi
    const barangSelect = document.getElementById('barang_id');
    if (barangSelect) {
        barangSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stokInfo = document.getElementById('stok-info');
            
            if (stokInfo && selectedOption.dataset.stok) {
                stokInfo.textContent = `Stok tersedia: ${selectedOption.dataset.stok}`;
                
                // Warning jika stok rendah
                if (parseInt(selectedOption.dataset.stok) < 10) {
                    stokInfo.className = 'text-danger fw-bold';
                } else {
                    stokInfo.className = 'text-success';
                }
            }
        });
    }
});

// Export to Excel (simplified)
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const html = table.outerHTML;
    
    // Create a blob and download link
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'data.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Print table
function printTable(tableId) {
    const printContent = document.getElementById(tableId).outerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <html>
            <head>
                <title>Print</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                ${printContent}
                <script>
                    window.print();
                    window.close();
                <\/script>
            </body>
        </html>
    `;
    
    // Kembalikan konten asli setelah print
    setTimeout(() => {
        document.body.innerHTML = originalContent;
    }, 1000);
}

// Auto-generate kode barang
function generateKodeBarang() {
    const kategori = document.getElementById('kategori').value;
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    
    if (kategori) {
        const prefix = kategori.substring(0, 3).toUpperCase();
        document.getElementById('kode_barang').value = `${prefix}${random}`;
    }
}