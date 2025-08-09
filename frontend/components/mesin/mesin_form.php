<?php
/**
 * Komponen Form untuk Tambah/Edit Mesin
 */

function render_mesin_form($urutan_proses_options) {
?>

<!-- Overlay Form (Pop-up style) -->
<div id="formOverlay" class="form-overlay">
    <div class="fixed inset-0 bg-black bg-opacity-50 z-40" onclick="tutupForm()"></div>
    
    <div class="form-container fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl">
        <div class="p-6">
            <!-- Header Form -->
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <div>
                    <h2 id="formTitle" class="text-2xl font-bold text-gray-900">Tambah Mesin</h2>
                    <p class="text-gray-600 mt-1">Lengkapi informasi mesin produksi</p>
                </div>
                <button type="button" onclick="tutupForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form Content -->
            <form id="mesinForm" method="POST" class="space-y-6">
                <input type="hidden" id="formAction" name="action" value="tambah">
                <input type="hidden" id="editIdMesin" name="id_mesin" value="">

                <!-- Row 1: Nama Mesin -->
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="tambah_nama_mesin" class="block text-sm font-semibold text-gray-700 mb-2">Nama Mesin <span class="text-red-500">*</span></label>
                        <input type="text" 
                               id="tambah_nama_mesin" 
                               name="nama_mesin" 
                               class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 focus:shadow-lg w-full"
                               placeholder="Contoh: Mesin Offset Heidelberg XL106"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Masukkan nama lengkap mesin produksi</p>
                    </div>
                </div>

                <!-- Row 2: Urutan Proses & Kapasitas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tambah_urutan_proses" class="block text-sm font-semibold text-gray-700 mb-2">Urutan Proses <span class="text-red-500">*</span></label>
                        <select id="tambah_urutan_proses" 
                                name="urutan_proses" 
                                class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-white w-full"
                                required>
                            <option value="">Pilih urutan proses</option>
                            <?php foreach ($urutan_proses_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $value; ?>. <?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Posisi mesin dalam alur produksi</p>
                    </div>

                    <div>
                        <label for="tambah_kapasitas" class="block text-sm font-semibold text-gray-700 mb-2">Kapasitas per Hari <span class="text-red-500">*</span></label>
                        <input type="number" 
                               id="tambah_kapasitas" 
                               name="kapasitas" 
                               class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 focus:shadow-lg w-full"
                               placeholder="20000"
                               min="1"
                               step="1"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Kapasitas produksi maksimal per hari</p>
                    </div>
                </div>

                <!-- Row 3: Waktu Setup & Waktu per Eksemplar -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tambah_waktu_setup" class="block text-sm font-semibold text-gray-700 mb-2">Waktu Setup (menit) <span class="text-red-500">*</span></label>
                        <input type="number" 
                               id="tambah_waktu_setup" 
                               name="waktu_setup" 
                               class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 focus:shadow-lg w-full"
                               placeholder="45"
                               min="0"
                               step="1"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Waktu persiapan sebelum produksi</p>
                    </div>

                    <div>
                        <label for="tambah_waktu_mesin_per_eks" class="block text-sm font-semibold text-gray-700 mb-2">Waktu per Eksemplar (menit) <span class="text-red-500">*</span></label>
                        <input type="number" 
                               id="tambah_waktu_mesin_per_eks" 
                               name="waktu_mesin_per_eks" 
                               class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 focus:shadow-lg w-full"
                               placeholder="0.024"
                               min="0"
                               step="0.000001"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Waktu pemrosesan per unit produk</p>
                    </div>
                </div>

                <!-- Row 4: Menit Operasional -->
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="tambah_menit_operasional" class="block text-sm font-semibold text-gray-700 mb-2">Menit Operasional per Hari <span class="text-red-500">*</span></label>
                        <input type="number" 
                               id="tambah_menit_operasional" 
                               name="menit_operasional" 
                               class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 focus:shadow-lg w-full"
                               placeholder="480"
                               min="1"
                               step="1"
                               value="480"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Total menit operasional dalam sehari (default: 480 menit = 8 jam)</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <button type="button" onclick="tutupForm()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold transition-all duration-200 hover:shadow-lg inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Batal
                    </button>
                    <button type="submit" id="submitBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 hover:shadow-lg inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span id="submitText">Simpan Mesin</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-overlay {
    display: none;
}

.form-overlay.active {
    display: block;
}
</style>

<?php
}
?>