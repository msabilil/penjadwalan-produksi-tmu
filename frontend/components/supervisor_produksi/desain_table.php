<?php
/**
 * Komponen Tabel untuk menampilkan daftar desain
 */

function render_desain_table($desains = []) {
?>

<div class="bg-white rounded-xl shadow-lg overflow-hidden border">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Desain</h3>
        <p class="text-sm text-gray-600 mt-1">Total: <?php echo count($desains); ?> desain</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis Desain</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis Produk</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Model Warna</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kualitas Warna</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sisi</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ukuran</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Halaman</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cover</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Laminasi</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jilid</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estimasi Waktu</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">File Status</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($desains)): ?>
                    <?php render_empty_state(); ?>
                <?php else: ?>
                    <?php foreach ($desains as $index => $desain): ?>
                        <?php render_desain_table_row($desain, $index); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
}

function render_empty_state() {
?>
<tr>
    <td colspan="15" class="px-6 py-12 text-center">
        <div class="flex flex-col items-center">
            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada data desain</h3>
            <p class="text-gray-500 mb-4">Klik tombol "Tambah Desain" untuk menambah data desain baru</p>
            <button onclick="showTambahModal()" class="btn-primary text-white px-4 py-2 rounded-lg">
                Tambah Desain Pertama
            </button>
        </div>
    </td>
</tr>
<?php
}

function render_desain_table_row($desain, $index) {
    // Define color classes for jenis desain
    $jenis_colors = [
        'desain default' => 'bg-gray-100 text-gray-800 border-gray-200',
        'desain sederhana' => 'bg-blue-100 text-blue-800 border-blue-200',
        'desain kompleks' => 'bg-purple-100 text-purple-800 border-purple-200',
        'desain premium' => 'bg-green-100 text-green-800 border-green-200'
    ];
    $color_class = $jenis_colors[$desain['jenis_desain']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
?>
<tr class="table-row hover:bg-gray-50">
    <!-- No -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm font-bold text-gray-900"><?php echo $index + 1; ?></span>
    </td>

    <!-- Nama -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($desain['nama']); ?></div>
    </td>

    <!-- Jenis Desain -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color_class; ?> border">
            <?php echo ucwords(str_replace('_', ' ', $desain['jenis_desain'])); ?>
        </span>
    </td>

    <!-- Jenis Produk -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo ucwords($desain['jenis_produk']); ?></div>
    </td>

    <!-- Model Warna -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo ucwords($desain['model_warna']); ?></div>
    </td>

    <!-- Kualitas Warna -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo ucwords($desain['kualitas_warna']); ?></div>
    </td>

    <!-- Sisi -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo $desain['sisi']; ?></div>
    </td>

    <!-- Ukuran -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($desain['ukuran']); ?></div>
    </td>

    <!-- Halaman -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo number_format($desain['halaman']); ?></div>
    </td>

    <!-- Cover -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo ucwords($desain['jenis_cover']); ?></div>
    </td>

    <!-- Laminasi -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo ucwords($desain['laminasi']); ?></div>
    </td>

    <!-- Jilid -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo ucwords($desain['jilid']); ?></div>
    </td>

    <!-- Estimasi Waktu -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo $desain['estimasi_waktu_desain']; ?> hari</div>
    </td>

    <!-- File Status -->
    <td class="px-6 py-4 whitespace-nowrap">
        <?php if (!empty($desain['file_cetak'])): ?>
            <div class="text-xs text-green-600">✓ <?php echo htmlspecialchars($desain['file_cetak']); ?></div>
        <?php else: ?>
            <div class="text-xs text-red-600">Belum upload</div>
        <?php endif; ?>
    </td>

    <!-- Aksi -->
    <td class="px-6 py-4 whitespace-nowrap text-center">
        <div class="flex justify-center space-x-2">
            <?php if (!empty($desain['file_cetak'])): ?>
                <a href="download_file.php?id=<?php echo $desain['id_desain']; ?>" 
                   class="bg-green-100 hover:bg-green-200 text-green-700 hover:text-green-900 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download
                </a>
            <?php endif; ?>
            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($desain)); ?>)" 
                    class="bg-blue-100 hover:bg-blue-200 text-blue-700 hover:text-blue-900 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-lg">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </button>
            <button onclick="confirmDelete(<?php echo $desain['id_desain']; ?>, '<?php echo htmlspecialchars($desain['nama']); ?>')" 
                    class="bg-red-100 hover:bg-red-200 text-red-700 hover:text-red-900 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-lg">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus
            </button>
        </div>
    </td>
</tr>
<?php
}
?>
