<?php
/**
 * Pesanan Table Component untuk Supervisor Produksi
 * Component untuk menampilkan tabel pesanan dengan fokus estimasi dan jadwal
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
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           id="searchInput"
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                           placeholder="Cari nomor pesanan, nama pemesan..."
                           value="<?= htmlspecialchars($search_keyword) ?>">
                </div>
            </div>
            
            <!-- Filter dan Export -->
            <div class="flex items-center space-x-3">
                <!-- Filter Button -->
                <button type="button" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        onclick="showFilterModal()">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
            </div>
        </div>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Pesanan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pemesan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Desain</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Estimasi</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="pesananTableBody" class="bg-white divide-y divide-gray-200">
                <?php if (empty($pesanan_list)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-lg font-medium text-gray-900 mb-2">Belum ada pesanan</p>
                                <p class="text-gray-500">Belum ada pesanan yang perlu diestimasi</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pesanan_list as $pesanan): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                <?= htmlspecialchars($pesanan['no'] ?? 'PO-' . $pesanan['id_pesanan']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                <?= date('d/m/Y', strtotime($pesanan['tanggal_pesanan'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($pesanan['nama_desain'] ?? 'Desain Baru') ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($pesanan['jenis_produk'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">
                                <?= number_format($pesanan['jumlah']) ?> eksemplar
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                // Cek status berdasarkan variabel yang sudah disiapkan
                                $estimasi_status = $pesanan['estimasi_status'] ?? 'belum';
                                $jadwal_status = $pesanan['jadwal_status'] ?? 'belum';
                                
                                if ($jadwal_status === 'ada') {
                                    $status_label = 'Terjadwal';
                                    $status_class = 'bg-green-100 text-green-800';
                                    $has_jadwal = true;
                                    $has_estimasi = true;
                                } elseif ($estimasi_status === 'ada') {
                                    $status_label = 'Terestimasi';
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $has_jadwal = false;
                                    $has_estimasi = true;
                                } else {
                                    $status_label = 'Perlu Estimasi';
                                    $status_class = 'bg-red-100 text-red-800';
                                    $has_jadwal = false;
                                    $has_estimasi = false;
                                }
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                                    <?= $status_label ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- View Button -->
                                    <button type="button" 
                                            class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            onclick="viewPesanan(<?= $pesanan['id_pesanan'] ?>)"
                                            title="Lihat Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Estimasi Button -->
                                    <?php if (!$has_estimasi): ?>
                                        <button type="button" 
                                                class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                                onclick="buatEstimasi(<?= $pesanan['id_pesanan'] ?>)"
                                                title="Buat Estimasi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3-3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                                onclick="editEstimasi(<?= $pesanan['id_pesanan'] ?>)"
                                                title="Edit Estimasi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Jadwal Button -->
                                    <?php if ($has_estimasi && !$has_jadwal): ?>
                                        <button type="button" 
                                                class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                onclick="buatJadwal(<?= $pesanan['id_pesanan'] ?>)"
                                                title="Buat Jadwal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    <?php elseif ($has_jadwal): ?>
                                        <button type="button" 
                                                class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                onclick="lihatJadwal(<?= $pesanan['id_pesanan'] ?>)"
                                                title="Lihat Jadwal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
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
// View Pesanan Detail
function viewPesanan(id) {
    // Redirect to detail pesanan page
    window.location.href = `detail_pesanan.php?id=${id}`;
}

// Buat Estimasi Baru
function buatEstimasi(idPesanan) {
    window.location.href = `estimasi.php?action=create&id_pesanan=${idPesanan}`;
}

// Edit Estimasi yang sudah ada
function editEstimasi(idPesanan) {
    window.location.href = `estimasi.php?action=edit&id_pesanan=${idPesanan}`;
}

// Buat Jadwal Produksi
function buatJadwal(idPesanan) {
    window.location.href = `jadwal.php?action=create&id_pesanan=${idPesanan}`;
}

// Lihat Jadwal yang sudah ada
function lihatJadwal(idPesanan) {
    window.location.href = `jadwal.php?action=view&id_pesanan=${idPesanan}`;
}

// Filter functionality
function showFilterModal() {
    Swal.fire({
        title: 'Filter Pesanan',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Estimasi</label>
                    <select id="filter_estimasi" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Semua Status</option>
                        <option value="belum">Belum Estimasi</option>
                        <option value="sudah">Sudah Estimasi</option>
                        <option value="terjadwal">Terjadwal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                    <input type="date" id="filter_start_date" class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                    <input type="date" id="filter_end_date" class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Terapkan Filter',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#16a34a',
        preConfirm: () => {
            const estimasi = document.getElementById('filter_estimasi').value;
            const startDate = document.getElementById('filter_start_date').value;
            const endDate = document.getElementById('filter_end_date').value;
            
            return { estimasi, startDate, endDate };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            applyFilter(result.value);
        }
    });
}

function applyFilter(filters) {
    const params = new URLSearchParams();
    if (filters.estimasi) params.append('filter_estimasi', filters.estimasi);
    if (filters.startDate) params.append('filter_start_date', filters.startDate);
    if (filters.endDate) params.append('filter_end_date', filters.endDate);
    
    window.location.href = `?${params.toString()}`;
}

// Search functionality
document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        const searchValue = this.value;
        const params = new URLSearchParams(window.location.search);
        if (searchValue) {
            params.set('search', searchValue);
        } else {
            params.delete('search');
        }
        window.location.href = `?${params.toString()}`;
    }
});
</script>
