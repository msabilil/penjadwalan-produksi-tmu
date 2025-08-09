<?php
/**
 * Komponen Tabel untuk menampilkan daftar mesin
 */

function render_mesin_table($mesins = []) {
?>

<div class="table-container overflow-hidden border">
    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Mesin</h3>
        <p class="text-sm text-gray-600 mt-1">Total: <?php echo count($mesins); ?> mesin</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Mesin</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Urutan Proses</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Setup</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu per Eksemplar</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Menit Operasional</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($mesins)): ?>
                    <?php render_empty_state_mesin(); ?>
                <?php else: ?>
                    <?php foreach ($mesins as $index => $mesin): ?>
                        <?php render_mesin_table_row($mesin, $index); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
}

function render_empty_state_mesin() {
?>
<tr>
    <td colspan="8" class="px-6 py-12 text-center">
        <div class="empty-state flex flex-col items-center">
            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada data mesin</h3>
            <p class="text-gray-500 mb-4">Klik tombol "Tambah Mesin" untuk menambah data mesin baru</p>
            <button onclick="bukaFormTambah()" class="btn-primary text-white px-4 py-2 rounded-lg">
                Tambah Mesin Pertama
            </button>
        </div>
    </td>
</tr>
<?php
}

function render_mesin_table_row($mesin, $index) {
    // Define color classes for urutan proses
    $urutan_colors = [
        1 => 'bg-purple-100 text-purple-800 border-purple-200',
        2 => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        3 => 'bg-blue-100 text-blue-800 border-blue-200',
        4 => 'bg-green-100 text-green-800 border-green-200',
        5 => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        6 => 'bg-orange-100 text-orange-800 border-orange-200',
        7 => 'bg-red-100 text-red-800 border-red-200',
        8 => 'bg-pink-100 text-pink-800 border-pink-200'
    ];
    $color_class = $urutan_colors[$mesin['urutan_proses']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    
    $proses_names = [
        1 => 'Desain',
        2 => 'Plat',
        3 => 'Setup',
        4 => 'Cetak',
        5 => 'Laminasi',
        6 => 'Jilid',
        7 => 'QC',
        8 => 'Packing'
    ];
    $proses_name = $proses_names[$mesin['urutan_proses']] ?? 'Unknown';
?>
<tr class="table-row hover:bg-gray-50">
    <!-- No -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm font-bold text-gray-900"><?php echo $index + 1; ?></span>
    </td>

    <!-- Nama Mesin -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($mesin['nama_mesin']); ?></div>
    </td>

    <!-- Urutan Proses -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="urutan-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color_class; ?> border">
            <?php echo $mesin['urutan_proses']; ?>. <?php echo $proses_name; ?>
        </span>
    </td>

    <!-- Kapasitas -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo number_format($mesin['kapasitas']); ?> /hari</div>
    </td>

    <!-- Waktu Setup -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo $mesin['waktu_setup']; ?> menit</div>
    </td>

    <!-- Waktu per Eksemplar -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo number_format($mesin['waktu_mesin_per_eks'], 6); ?> menit</div>
    </td>

    <!-- Menit Operasional -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?php echo $mesin['menit_operasional']; ?> menit</div>
        <div class="text-xs text-gray-500"><?php echo number_format($mesin['menit_operasional'] / 60, 1); ?> jam</div>
    </td>

    <!-- Aksi -->
    <td class="px-6 py-4 whitespace-nowrap text-center">
        <div class="flex justify-center space-x-2">
            <button onclick="bukaFormEdit(<?php echo htmlspecialchars(json_encode($mesin)); ?>)" 
                    class="action-btn action-btn-edit text-blue-700 hover:text-blue-900 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </button>
            <button onclick="konfirmasiHapus(<?php echo $mesin['id_mesin']; ?>, '<?php echo htmlspecialchars($mesin['nama_mesin']); ?>')" 
                    class="action-btn action-btn-delete text-red-700 hover:text-red-900 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
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
