<?php
/**
 * Pesanan Form Component untuk Staf Penjualan
 * Component form untuk tambah/edit pesanan
 */

// Set default values
$form_action = $form_action ?? '';
$form_method = $form_method ?? 'POST';
$pesanan_data = $pesanan_data ?? [];
$desain_list = $desain_list ?? [];
$is_edit = isset($pesanan_data['id_pesanan']);
?>

<form method="<?= $form_method ?>" 
      action="<?= htmlspecialchars($form_action) ?>" 
      class="space-y-6"
      data-validate="true">
    
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_pesanan" value="<?= $pesanan_data['id_pesanan'] ?>">
        <input type="hidden" name="action" value="update_pesanan">
    <?php else: ?>
        <input type="hidden" name="action" value="tambah_pesanan">
    <?php endif; ?>
    
    <!-- Informasi Pesanan -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pesanan</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nomor Pesanan -->
            <div class="form-group">
                <label for="no" class="form-label">
                    Nomor Pesanan <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="no" 
                       name="no" 
                       class="form-input"
                       value="<?= htmlspecialchars($pesanan_data['no'] ?? '') ?>"
                       <?= $is_edit ? 'readonly' : '' ?>
                       <?= !$is_edit ? 'placeholder="Otomatis jika dikosongkan"' : '' ?>
                       required>
            </div>
            
            <!-- Tanggal Pesanan -->
            <div class="form-group">
                <label for="tanggal_pesanan" class="form-label">
                    Tanggal Pesanan <span class="text-red-500">*</span>
                </label>
                <input type="date" 
                       id="tanggal_pesanan" 
                       name="tanggal_pesanan" 
                       class="form-input"
                       value="<?= $pesanan_data['tanggal_pesanan'] ?? date('Y-m-d') ?>"
                       required>
            </div>
        </div>
    </div>
    
    <!-- Informasi Pemesan -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pemesan</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nama Pemesan -->
            <div class="form-group">
                <label for="nama_pemesan" class="form-label">
                    Nama Pemesan <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="nama_pemesan" 
                       name="nama_pemesan" 
                       class="form-input"
                       value="<?= htmlspecialchars($pesanan_data['nama_pemesan'] ?? '') ?>"
                       placeholder="Masukkan nama lengkap pemesan"
                       required>
            </div>
            
            <!-- Nomor Telepon -->
            <div class="form-group">
                <label for="no_telepon" class="form-label">
                    Nomor Telepon
                </label>
                <input type="tel" 
                       id="no_telepon" 
                       name="no_telepon" 
                       class="form-input"
                       value="<?= htmlspecialchars($pesanan_data['no_telepon'] ?? '') ?>"
                       placeholder="Contoh: 08123456789">
            </div>
        </div>
        
        <!-- Alamat -->
        <div class="form-group">
            <label for="alamat" class="form-label">
                Alamat
            </label>
            <textarea id="alamat" 
                      name="alamat" 
                      class="form-textarea"
                      rows="3"
                      placeholder="Masukkan alamat lengkap pemesan"><?= htmlspecialchars($pesanan_data['alamat'] ?? '') ?></textarea>
        </div>
    </div>
    
    <!-- Detail Pesanan -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detail Pesanan</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Pilih Desain -->
            <div class="form-group">
                <label for="id_desain" class="form-label">
                    Pilih Desain <span class="text-red-500">*</span>
                </label>
                <select id="id_desain" 
                        name="id_desain" 
                        class="form-select"
                        onchange="updateDesainInfo(this.value)"
                        required>
                    <option value="">-- Pilih Desain --</option>
                    <?php foreach ($desain_list as $desain): ?>
                        <option value="<?= $desain['id_desain'] ?>"
                                data-nama="<?= htmlspecialchars($desain['nama']) ?>"
                                data-jenis="<?= htmlspecialchars($desain['jenis_desain']) ?>"
                                data-produk="<?= htmlspecialchars($desain['jenis_produk']) ?>"
                                data-halaman="<?= $desain['halaman'] ?>"
                                data-ukuran="<?= htmlspecialchars($desain['ukuran']) ?>"
                                <?= (isset($pesanan_data['id_desain']) && $pesanan_data['id_desain'] == $desain['id_desain']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($desain['nama']) ?> 
                            (<?= htmlspecialchars($desain['jenis_produk']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Jumlah -->
            <div class="form-group">
                <label for="jumlah" class="form-label">
                    Jumlah <span class="text-red-500">*</span>
                </label>
                <input type="number" 
                       id="jumlah" 
                       name="jumlah" 
                       class="form-input"
                       value="<?= $pesanan_data['jumlah'] ?? '' ?>"
                       min="1"
                       placeholder="Masukkan jumlah eksemplar"
                       required>
                <p class="text-sm text-gray-500 mt-1">Dalam satuan eksemplar</p>
            </div>
        </div>
        
        <!-- Informasi Desain Terpilih -->
        <div id="desainInfo" class="mt-6 p-4 bg-blue-50 rounded-lg" style="display: none;">
            <h4 class="font-semibold text-blue-900 mb-2">Informasi Desain</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-blue-700 font-medium">Jenis:</span>
                    <span id="desainJenis" class="block text-blue-900"></span>
                </div>
                <div>
                    <span class="text-blue-700 font-medium">Produk:</span>
                    <span id="desainProduk" class="block text-blue-900"></span>
                </div>
                <div>
                    <span class="text-blue-700 font-medium">Halaman:</span>
                    <span id="desainHalaman" class="block text-blue-900"></span>
                </div>
                <div>
                    <span class="text-blue-700 font-medium">Ukuran:</span>
                    <span id="desainUkuran" class="block text-blue-900"></span>
                </div>
            </div>
        </div>
        
        <!-- Deskripsi -->
        <div class="form-group mt-6">
            <label for="deskripsi" class="form-label">
                Deskripsi / Catatan
            </label>
            <textarea id="deskripsi" 
                      name="deskripsi" 
                      class="form-textarea"
                      rows="4"
                      placeholder="Masukkan deskripsi atau catatan khusus untuk pesanan ini"><?= htmlspecialchars($pesanan_data['deskripsi'] ?? '') ?></textarea>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
        <button type="button" 
                class="btn-secondary"
                onclick="hideModal('<?= $is_edit ? 'editPesananModal' : 'addPesananModal' ?>')">
            Batal
        </button>
        
        <button type="submit" 
                class="btn-primary"
                id="submitBtn">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?= $is_edit ? 'Update Pesanan' : 'Simpan Pesanan' ?>
        </button>
    </div>
</form>

<script>
// Update informasi desain ketika desain dipilih
function updateDesainInfo(desainId) {
    const select = document.getElementById('id_desain');
    const selectedOption = select.querySelector(`option[value="${desainId}"]`);
    const infoDiv = document.getElementById('desainInfo');
    
    if (selectedOption && desainId) {
        // Tampilkan informasi desain
        document.getElementById('desainJenis').textContent = selectedOption.dataset.jenis || '-';
        document.getElementById('desainProduk').textContent = selectedOption.dataset.produk || '-';
        document.getElementById('desainHalaman').textContent = selectedOption.dataset.halaman || '-';
        document.getElementById('desainUkuran').textContent = selectedOption.dataset.ukuran || '-';
        
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const desainSelect = document.getElementById('id_desain');
    if (desainSelect.value) {
        updateDesainInfo(desainSelect.value);
    }
    
    // Format nomor telepon input
    const phoneInput = document.getElementById('no_telepon');
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Remove non-digits
        
        // Add country code if needed
        if (value.length > 0 && !value.startsWith('0') && !value.startsWith('62')) {
            value = '0' + value;
        }
        
        this.value = value;
    });
    
    // Auto-generate nomor pesanan jika kosong
    const nomorInput = document.getElementById('no');
    if (!nomorInput.value && !<?= $is_edit ? 'true' : 'false' ?>) {
        const today = new Date();
        const year = today.getFullYear().toString().substr(-2);
        const month = (today.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        nomorInput.value = `PSN-${year}${month}${random}`;
    }
});

// Form validation
document.querySelector('form[data-validate]').addEventListener('submit', function(e) {
    const jumlah = parseInt(document.getElementById('jumlah').value);
    
    if (jumlah < 1) {
        e.preventDefault();
        Swal.fire({
            title: 'Validasi Error',
            text: 'Jumlah pesanan harus minimal 1 eksemplar',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        return false;
    }
    
    if (jumlah > 100000) {
        e.preventDefault();
        Swal.fire({
            title: 'Konfirmasi',
            text: `Jumlah pesanan sangat besar (${jumlah.toLocaleString()} eksemplar). Apakah Anda yakin?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Periksa Kembali'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
        return false;
    }
    
    // Show loading
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Menyimpan...
    `;
});
</script>
