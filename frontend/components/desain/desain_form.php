<?php
/**
 * Komponen Form Modal untuk Desain
 * Dapat digunakan untuk tambah dan edit desain
 */

function render_desain_form($modal_id, $title, $action, $options_arrays = []) {
    // Extract option arrays
    $jenis_desain_options = $options_arrays['jenis_desain'] ?? [];
    $jenis_produk_options = $options_arrays['jenis_produk'] ?? [];
    $model_warna_options = $options_arrays['model_warna'] ?? [];
    $jenis_cover_options = $options_arrays['jenis_cover'] ?? [];
    $laminasi_options = $options_arrays['laminasi'] ?? [];
    $jilid_options = $options_arrays['jilid'] ?? [];
    $kualitas_warna_options = $options_arrays['kualitas_warna'] ?? [];
    
    $is_edit = ($action === 'edit');
    $prefix = $is_edit ? 'edit_' : '';
    $close_function = $is_edit ? 'closeEditModal()' : 'closeTambahModal()';
    $submit_text = $is_edit ? 'Update Desain' : 'Simpan Desain';
?>

<div id="<?php echo $modal_id; ?>" class="hidden fixed inset-0 modal-backdrop overflow-y-auto h-full w-full z-50">
    <div class="relative top-5 mx-auto p-6 border w-full max-w-4xl shadow-2xl rounded-2xl bg-white animate-fade-in max-h-screen overflow-y-auto">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900"><?php echo $title; ?></h3>
                <button onclick="<?php echo $close_function; ?>" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($is_edit): ?>
                <input type="hidden" name="id_desain" id="<?php echo $prefix; ?>id_desain">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nama Desain -->
                    <div>
                        <label for="<?php echo $prefix; ?>nama" class="block text-sm font-semibold text-gray-700 mb-2">Nama Desain *</label>
                        <input type="text" id="<?php echo $prefix; ?>nama" name="nama" required 
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Masukkan nama desain">
                    </div>
                    
                    <!-- Jenis Desain -->
                    <div>
                        <label for="<?php echo $prefix; ?>jenis_desain" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Desain *</label>
                        <select id="<?php echo $prefix; ?>jenis_desain" name="jenis_desain" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                onchange="updateEstimasiWaktu('<?php echo $prefix; ?>')">
                            <option value="">Pilih Jenis Desain</option>
                            <?php foreach ($jenis_desain_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Estimasi Waktu Desain - HIDDEN but still submitted -->
                    <input type="hidden" id="<?php echo $prefix; ?>estimasi_waktu_desain" name="estimasi_waktu_desain" value="0">
                    
                    <!-- Jenis Produk -->
                    <div>
                        <label for="<?php echo $prefix; ?>jenis_produk" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Produk *</label>
                        <select id="<?php echo $prefix; ?>jenis_produk" name="jenis_produk" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Jenis Produk</option>
                            <?php foreach ($jenis_produk_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Model Warna -->
                    <div>
                        <label for="<?php echo $prefix; ?>model_warna" class="block text-sm font-semibold text-gray-700 mb-2">Model Warna *</label>
                        <select id="<?php echo $prefix; ?>model_warna" name="model_warna" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                onchange="updateJumlahWarna('<?php echo $prefix; ?>')">
                            <option value="">Pilih Model Warna</option>
                            <?php foreach ($model_warna_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Jumlah Warna - HIDDEN but still submitted -->
                    <input type="hidden" id="<?php echo $prefix; ?>jumlah_warna" name="jumlah_warna" value="1">
                    
                    <!-- Sisi -->
                    <div>
                        <label for="<?php echo $prefix; ?>sisi" class="block text-sm font-semibold text-gray-700 mb-2">Sisi *</label>
                        <select id="<?php echo $prefix; ?>sisi" name="sisi" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Sisi</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                        </select>
                    </div>
                    
                    <!-- Halaman -->
                    <div>
                        <label for="<?php echo $prefix; ?>halaman" class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Halaman *</label>
                        <input type="number" id="<?php echo $prefix; ?>halaman" name="halaman" required min="1" 
                               value="<?php echo $is_edit ? '' : '1'; ?>"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <!-- Jenis Cover -->
                    <div>
                        <label for="<?php echo $prefix; ?>jenis_cover" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Cover *</label>
                        <select id="<?php echo $prefix; ?>jenis_cover" name="jenis_cover" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Jenis Cover</option>
                            <?php foreach ($jenis_cover_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Laminasi -->
                    <div>
                        <label for="<?php echo $prefix; ?>laminasi" class="block text-sm font-semibold text-gray-700 mb-2">Laminasi *</label>
                        <select id="<?php echo $prefix; ?>laminasi" name="laminasi" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Laminasi</option>
                            <?php foreach ($laminasi_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Jilid -->
                    <div>
                        <label for="<?php echo $prefix; ?>jilid" class="block text-sm font-semibold text-gray-700 mb-2">Jilid *</label>
                        <select id="<?php echo $prefix; ?>jilid" name="jilid" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Jilid</option>
                            <?php foreach ($jilid_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Kualitas Warna -->
                    <div>
                        <label for="<?php echo $prefix; ?>kualitas_warna" class="block text-sm font-semibold text-gray-700 mb-2">Kualitas Warna *</label>
                        <select id="<?php echo $prefix; ?>kualitas_warna" name="kualitas_warna" required 
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Pilih Kualitas Warna</option>
                            <?php foreach ($kualitas_warna_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Ukuran -->
                    <div>
                        <label for="<?php echo $prefix; ?>ukuran" class="block text-sm font-semibold text-gray-700 mb-2">Ukuran *</label>
                        <input type="text" id="<?php echo $prefix; ?>ukuran" name="ukuran" required 
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Contoh: A4, A5, 21x29.7cm">
                    </div>
                    
                    <!-- Upload File Desain -->
                    <div>
                        <label for="<?php echo $prefix; ?>file_cetak" class="block text-sm font-semibold text-gray-700 mb-2">Upload File Desain</label>
                        <input type="file" id="<?php echo $prefix; ?>file_cetak" name="file_cetak" accept=".pdf,.ai,.psd,.eps,.jpg,.jpeg,.png"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Format yang didukung: PDF, AI, PSD, EPS, JPG, PNG (Maks. 10MB)</p>
                        <?php if ($is_edit): ?>
                        <div id="current_file_display" class="text-xs text-blue-600 mt-1"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tanggal Upload -->
                    <div>
                        <label for="<?php echo $prefix; ?>tanggal_upload" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Upload File</label>
                        <input type="date" id="<?php echo $prefix; ?>tanggal_upload" name="tanggal_selesai"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Kosongkan jika file belum diupload</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6">
                    <button type="button" onclick="<?php echo $close_function; ?>" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="btn-primary px-6 py-3 text-sm font-medium text-white rounded-xl shadow-lg">
                        <?php echo $submit_text; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-calculate jumlah warna berdasarkan model warna
function updateJumlahWarna(prefix) {
    const modelWarnaSelect = document.getElementById(prefix + 'model_warna');
    const jumlahWarnaInput = document.getElementById(prefix + 'jumlah_warna');
    
    if (modelWarnaSelect && jumlahWarnaInput) {
        const modelWarna = modelWarnaSelect.value;
        let jumlahWarna = 1;
        
        switch(modelWarna) {
            case 'fullcolor':
                jumlahWarna = 4;
                break;
            case 'dua warna':
                jumlahWarna = 2;
                break;
            case 'b/w':
                jumlahWarna = 1;
                break;
            default:
                jumlahWarna = 1;
        }
        
        jumlahWarnaInput.value = jumlahWarna;
    }
}

// Auto-calculate estimasi waktu berdasarkan jenis desain
function updateEstimasiWaktu(prefix) {
    const jenisDesainSelect = document.getElementById(prefix + 'jenis_desain');
    const estimasiWaktuInput = document.getElementById(prefix + 'estimasi_waktu_desain');
    
    if (jenisDesainSelect && estimasiWaktuInput) {
        const jenisDesain = jenisDesainSelect.value;
        let estimasiWaktu = 0;
        
        switch(jenisDesain) {
            case 'desain default':
                estimasiWaktu = 0;
                break;
            case 'desain sederhana':
                estimasiWaktu = 3;
                break;
            case 'desain kompleks':
                estimasiWaktu = 10;
                break;
            case 'desain premium':
                estimasiWaktu = 20;
                break;
            default:
                estimasiWaktu = 0;
        }
        
        estimasiWaktuInput.value = estimasiWaktu;
    }
}

// Initialize auto-calculation when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for both tambah and edit forms
    const modelWarnaTambah = document.getElementById('model_warna');
    const jenisDesainTambah = document.getElementById('jenis_desain');
    const modelWarnaEdit = document.getElementById('edit_model_warna');
    const jenisDesainEdit = document.getElementById('edit_jenis_desain');
    
    if (modelWarnaTambah) {
        modelWarnaTambah.addEventListener('change', function() {
            updateJumlahWarna('');
        });
    }
    
    if (jenisDesainTambah) {
        jenisDesainTambah.addEventListener('change', function() {
            updateEstimasiWaktu('');
        });
    }
    
    if (modelWarnaEdit) {
        modelWarnaEdit.addEventListener('change', function() {
            updateJumlahWarna('edit_');
        });
    }
    
    if (jenisDesainEdit) {
        jenisDesainEdit.addEventListener('change', function() {
            updateEstimasiWaktu('edit_');
        });
    }
});
</script>

<?php
}
?>
