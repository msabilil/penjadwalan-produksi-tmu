<?php
/**
 * Pesanan Table Component untuk Staf Penjualan
 * Component untuk menampilkan tabel pesanan dengan fitur lengkap
 */

// Set default values jika tidak ada data
$pesanan_list = $pesanan_list ?? [];
$total_records = $total_records ?? count($pesanan_list);
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 10;
$search_keyword = $search_keyword ?? '';
?>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <!-- Table Header dengan Search -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Search -->
            <div class="search-container flex-1 max-w-md">
                <div class="relative">
                    <div class="search-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           id="searchInput"
                           class="search-input" 
                           placeholder="Cari nomor pesanan, nama pemesan..."
                           value="<?= htmlspecialchars($search_keyword) ?>">
                </div>
            </div>
            
            <!-- Filter dan Export -->
            <div class="flex items-center space-x-3">
                <!-- Filter Button -->
                <button type="button" 
                        class="btn-secondary"
                        onclick="showModal('filterModal')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                
                <!-- Export Button -->
                <button type="button" 
                        class="btn-secondary"
                        onclick="exportPesanan()">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </button>
            </div>
        </div>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="pesanan-table">
            <thead>
                <tr>
                    <th class="w-4">
                        <input type="checkbox" 
                               id="selectAll"
                               class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                               onchange="toggleSelectAll(this)">
                    </th>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Nama Pemesan</th>
                    <th>Desain</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="pesananTableBody">
                <?php if (empty($pesanan_list)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-lg font-medium text-gray-900 mb-2">Belum ada pesanan</p>
                                <p class="text-gray-500">Mulai dengan menambahkan pesanan pertama Anda</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pesanan_list as $pesanan): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td>
                                <input type="checkbox" 
                                       class="row-select rounded border-gray-300 text-green-600 focus:ring-green-500"
                                       value="<?= $pesanan['id_pesanan'] ?>">
                            </td>
                            <td class="font-medium text-gray-900">
                                <?= htmlspecialchars($pesanan['no']) ?>
                            </td>
                            <td class="text-gray-600">
                                <?= date('d/m/Y', strtotime($pesanan['tanggal_pesanan'])) ?>
                            </td>
                            <td>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($pesanan['nama_pemesan']) ?>
                                    </div>
                                    <?php if (!empty($pesanan['no_telepon'])): ?>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($pesanan['no_telepon']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($pesanan['nama_desain'] ?? 'N/A') ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($pesanan['jenis_produk'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </td>
                            <td class="font-medium">
                                <?= number_format($pesanan['jumlah']) ?> eksemplar
                            </td>
                            <td>
                                <?php
                                // Tentukan status berdasarkan data yang ada
                                $status = 'pending';
                                $status_label = 'Menunggu';
                                $status_class = 'status-pending';
                                
                                // Logika status bisa disesuaikan berdasarkan kebutuhan
                                if (isset($pesanan['status'])) {
                                    switch ($pesanan['status']) {
                                        case 'completed':
                                            $status_label = 'Selesai';
                                            $status_class = 'status-completed';
                                            break;
                                        case 'cancelled':
                                            $status_label = 'Dibatalkan';
                                            $status_class = 'status-cancelled';
                                            break;
                                        case 'active':
                                            $status_label = 'Aktif';
                                            $status_class = 'status-active';
                                            break;
                                        default:
                                            $status_label = 'Menunggu';
                                            $status_class = 'status-pending';
                                    }
                                }
                                ?>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= $status_label ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- View Button -->
                                    <button type="button" 
                                            class="action-btn action-btn-view"
                                            onclick="viewPesanan(<?= $pesanan['id_pesanan'] ?>)"
                                            title="Lihat Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Edit Button -->
                                    <button type="button" 
                                            class="action-btn action-btn-edit"
                                            onclick="editPesanan(<?= $pesanan['id_pesanan'] ?>)"
                                            title="Edit Pesanan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Download PO Button -->
                                    <button type="button" 
                                            class="action-btn action-btn-view"
                                            onclick="downloadPO(<?= $pesanan['id_pesanan'] ?>)"
                                            title="Download Purchase Order">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <button type="button" 
                                            class="action-btn action-btn-delete"
                                            onclick="deletePesanan(<?= $pesanan['id_pesanan'] ?>)"
                                            title="Hapus Pesanan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_records > $per_page): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Menampilkan 
                    <span class="font-medium"><?= (($current_page - 1) * $per_page) + 1 ?></span>
                    sampai 
                    <span class="font-medium"><?= min($current_page * $per_page, $total_records) ?></span>
                    dari 
                    <span class="font-medium"><?= $total_records ?></span>
                    hasil
                </div>
                
                <div class="flex items-center space-x-2">
                    <?php
                    $total_pages = ceil($total_records / $per_page);
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    ?>
                    
                    <!-- Previous Button -->
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?= $current_page - 1 ?><?= $search_keyword ? '&search=' . urlencode($search_keyword) : '' ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Sebelumnya
                        </a>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search_keyword ? '&search=' . urlencode($search_keyword) : '' ?>" 
                           class="px-3 py-2 text-sm font-medium <?= $i === $current_page ? 'text-green-600 bg-green-50 border-green-500' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' ?> border rounded-md">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Next Button -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?><?= $search_keyword ? '&search=' . urlencode($search_keyword) : '' ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Selanjutnya
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Select All functionality
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.row-select');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

// Export functionality
function exportPesanan() {
    const selectedIds = [];
    document.querySelectorAll('.row-select:checked').forEach(cb => {
        selectedIds.push(cb.value);
    });
    
    if (selectedIds.length === 0) {
        Swal.fire({
            title: 'Pilih Data',
            text: 'Pilih minimal satu pesanan untuk diekspor',
            icon: 'warning',
            confirmButtonColor: '#16a34a'
        });
        return;
    }
    
    // Show export options
    Swal.fire({
        title: 'Format Export',
        text: 'Pilih format file untuk ekspor data',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Excel (.xlsx)',
        cancelButtonText: 'PDF',
        showDenyButton: true,
        denyButtonText: 'CSV'
    }).then((result) => {
        if (result.isConfirmed) {
            exportToExcel(selectedIds);
        } else if (result.isDenied) {
            exportToCSV(selectedIds);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportToPDF(selectedIds);
        }
    });
}

function exportToExcel(ids) {
    window.location.href = `export_pesanan.php?format=excel&ids=${ids.join(',')}`;
}

function exportToCSV(ids) {
    window.location.href = `export_pesanan.php?format=csv&ids=${ids.join(',')}`;
}

function exportToPDF(ids) {
    window.location.href = `export_pesanan.php?format=pdf&ids=${ids.join(',')}`;
}
</script>
