<?php
require_once __DIR__ . '/../../../backend/functions/estimasi_functions.php';
require_once __DIR__ . '/../../../backend/functions/detail_estimasi_functions.php';
require_once __DIR__ . '/../../../backend/functions/pesanan_functions.php';
require_once __DIR__ . '/../../../backend/functions/helper_functions.php';

/**
 * Komponen Gantt Chart untuk Supervisor Produksi
 * Menampilkan timeline produksi berdasarkan estimasi pesanan
 */

// Get filter parameters from global variables (set by parent page)
$filter_bulan = isset($GLOBALS['filter_bulan']) ? intval($GLOBALS['filter_bulan']) : intval(date('n'));
$filter_tahun = isset($GLOBALS['filter_tahun']) ? intval($GLOBALS['filter_tahun']) : intval(date('Y'));

// Ambil data estimasi dengan filter
$estimasi_result = ambil_estimasi_by_filter($filter_bulan, $filter_tahun);
$estimasi_list = $estimasi_result['success'] ? $estimasi_result['data'] : [];

// Determine date range for calendar: always one selected month
$current_month = $filter_bulan;
$current_year = $filter_tahun;
$show_months = [$current_month];

// Fungsi untuk generate hari dalam bulan
function getDaysInMonth($month, $year) {
    $days = [];
    $daysCount = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($i = 1; $i <= $daysCount; $i++) {
        $days[] = $i;
    }
    return $days;
}

// Generate days for display months
$month_days = [];
$month_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

foreach ($show_months as $month) {
    $year_for_month = ($month == 1 && $current_month == 12) ? $current_year + 1 : $current_year;
    $month_days[$month] = [
        'days' => getDaysInMonth($month, $year_for_month),
        'name' => $month_names[$month],
        'year' => $year_for_month
    ];
}

// Fungsi untuk menghitung posisi dan durasi bar pada Gantt chart
function calculateGanttPosition($tanggal_mulai, $durasi_hari, $start_date) {
    $start = new DateTime($start_date);
    $mulai = new DateTime($tanggal_mulai);
    $offset = $start->diff($mulai)->days;
    
    return [
        'offset' => $offset,
        'duration' => $durasi_hari
    ];
}

// Determine start date for calculation
$start_date = $current_year . '-' . sprintf('%02d', $show_months[0]) . '-01';
?>

<div class="gantt-chart-container">
    <!-- Header Gantt Chart -->
    <div class="gantt-header">
        <h3 class="text-lg font-semibold">Gantt Chart Estimasi Produksi</h3>
        <p class="text-green-100 text-sm">
            Timeline estimasi produksi berdasarkan perhitungan sistem
            <?php if ($filter_bulan > 0 || $filter_tahun != date('Y')): ?>
                - <?php if ($filter_bulan > 0): ?>
                    <?= $month_names[$filter_bulan] ?> <?= $filter_tahun ?>
                <?php else: ?>
                    Tahun <?= $filter_tahun ?>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Gantt Chart Container -->
    <div class="gantt-table-container">
        <table class="gantt-table">
            <thead>
                <!-- Header Utama -->
                <tr>
                    <th rowspan="2" class="gantt-data-cell center">No</th>
                    <th rowspan="2" class="gantt-data-cell">Nama Pemesan/Agen</th>
                    <th rowspan="2" class="gantt-data-cell">Judul Produk</th>
                    <th rowspan="2" class="gantt-data-cell center">Jumlah Pesanan</th>
                    <th rowspan="2" class="gantt-data-cell center">Tanggal Pesanan</th>
                    <th rowspan="2" class="gantt-data-cell center">Estimasi Pesanan Selesai (Hari)</th>
                    
                    <!-- Header Bulan - Dynamic based on filter -->
                    <?php foreach ($month_days as $month_num => $month_info): ?>
                        <th colspan="<?= count($month_info['days']) ?>" class="gantt-month-header">
                            <?= $month_info['name'] ?> <?= $month_info['year'] ?>
                        </th>
                    <?php endforeach; ?>
                    
                    <th rowspan="2" class="gantt-data-cell center">Tanggal Estimasi Pesanan Selesai</th>
                </tr>
                
                <!-- Header Tanggal -->
                <tr>
                    <!-- Days for each month -->
                    <?php foreach ($month_days as $month_num => $month_info): ?>
                        <?php foreach ($month_info['days'] as $day): ?>
                            <th class="gantt-day-cell"><?= $day ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
                <tbody>
                    <?php if (empty($estimasi_list)): ?>
                        <tr>
                            <?php 
                            // Calculate total columns dynamically
                            $total_days = 0;
                            foreach ($month_days as $month_info) {
                                $total_days += count($month_info['days']);
                            }
                            $total_columns = 6 + $total_days + 1; // 6 data columns + days + 1 completion date column
                            ?>
                            <td colspan="<?= $total_columns ?>" class="gantt-empty-state">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-sm">
                                        <?php if ($filter_bulan > 0 || $filter_tahun != date('Y')): ?>
                                            Tidak ada data estimasi untuk filter yang dipilih
                                        <?php else: ?>
                                            Belum ada data estimasi tersedia
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estimasi_list as $index => $estimasi): ?>
                            <?php
                            // Hitung posisi gantt chart
                            $tanggal_mulai = $estimasi['tanggal_pesanan'] ?? date('Y-m-d');
                            $durasi_estimasi = (int)($estimasi['waktu_hari'] ?? 1);
                            
                            // Hitung tanggal selesai estimasi
                            $tanggal_selesai = date('d/m/Y', strtotime($tanggal_mulai . ' +' . $durasi_estimasi . ' days'));
                            
                            // Calculate gantt bar position relative to start date
                            $start_date_obj = new DateTime($start_date);
                            $pesanan_date = new DateTime($tanggal_mulai);
                            $offset_days = max(0, $start_date_obj->diff($pesanan_date)->days);
                            
                            // Calculate total days available in calendar
                            $total_calendar_days = 0;
                            foreach ($month_days as $month_info) {
                                $total_calendar_days += count($month_info['days']);
                            }
                            
                            // Ensure bar doesn't exceed calendar bounds
                            $bar_start = min($offset_days, $total_calendar_days - 1);
                            $bar_duration = min($durasi_estimasi, $total_calendar_days - $bar_start);
                            ?>
                            
                            <tr class="gantt-row">
                                <!-- Data Pesanan -->
                                <td class="gantt-data-cell center"><?= $index + 1 ?></td>
                                <td class="gantt-data-cell">
                                    <div class="gantt-company-name"><?= htmlspecialchars($estimasi['nama_pemesan'] ?? '-') ?></div>
                                    <?php if (!empty($estimasi['nama_agen'])): ?>
                                        <div class="gantt-agent-name"><?= htmlspecialchars($estimasi['nama_agen']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="gantt-data-cell">
                                    <div class="gantt-product-title"><?= htmlspecialchars($estimasi['judul_produk'] ?? $estimasi['nama_desain'] ?? '-') ?></div>
                                    <?php if (!empty($estimasi['jenis_produk'])): ?>
                                        <div class="gantt-product-type"><?= htmlspecialchars($estimasi['jenis_produk']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="gantt-data-cell center gantt-quantity"><?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?></td>
                                <td class="gantt-data-cell center"><?= format_tanggal($tanggal_mulai) ?></td>
                                <td class="gantt-data-cell center gantt-duration"><?= $durasi_estimasi ?></td>
                                
                                <!-- Timeline Gantt Chart - Dynamic based on months -->
                                <?php
                                $day_counter = 0;
                                foreach ($month_days as $month_num => $month_info):
                                    foreach ($month_info['days'] as $day):
                                        $is_in_range = ($day_counter >= $bar_start && $day_counter < $bar_start + $bar_duration);
                                        
                                        // Check if this is today - more accurate calculation
                                        $current_date = $month_info['year'] . '-' . sprintf('%02d', $month_num) . '-' . sprintf('%02d', $day);
                                        $is_today = ($current_date == date('Y-m-d'));
                                ?>
                                    <td class="gantt-day-cell <?= $is_today ? 'today-column' : '' ?>">
                                        <?= $is_in_range ? '<div class="gantt-bar" title="Hari ke-' . ($day_counter - $bar_start + 1) . ' dari ' . $durasi_estimasi . ' hari estimasi - ' . htmlspecialchars($estimasi['nama_desain'] ?? $estimasi['nama_pemesan']) . '"></div>' : '' ?>
                                    </td>
                                <?php 
                                        $day_counter++;
                                    endforeach;
                                endforeach;
                                ?>
                                
                                <!-- Tanggal Estimasi Selesai -->
                                <td class="gantt-data-cell center gantt-completion-date"><?= $tanggal_selesai ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Footer Legend -->
    <div class="gantt-legend">
        <div class="flex items-center justify-between flex-wrap">
            <div class="flex items-center space-x-6">
                <div class="gantt-legend-item">
                    <div class="gantt-legend-color" style="background: linear-gradient(90deg, #fcd34d, #f59e0b);"></div>
                    <span class="gantt-legend-text">Periode Produksi</span>
                </div>
                <div class="gantt-legend-item">
                    <div class="gantt-legend-color" style="background: #fef3c7; border: 1px solid #fed7aa;"></div>
                    <span class="gantt-legend-text">Hari Ini</span>
                </div>
                <div class="gantt-legend-item">
                    <div class="gantt-legend-color" style="background: #f8fafc; border: 1px solid #e2e8f0;"></div>
                    <span class="gantt-legend-text">Hari Kosong</span>
                </div>
            </div>
            <div class="gantt-legend-text">
                Total Estimasi: <span class="font-medium text-gray-900"><?= count($estimasi_list) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript untuk interaktivitas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Highlight row on hover - sudah ditangani oleh CSS
    
    // Tooltip untuk detail estimasi
    const ganttBars = document.querySelectorAll('.gantt-bar');
    ganttBars.forEach(bar => {
        bar.style.cursor = 'pointer';
        
        bar.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'gantt-tooltip show';
            tooltip.textContent = this.title || 'Detail estimasi produksi';
            document.body.appendChild(tooltip);
            
            const rect = e.target.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            
            // Auto-remove tooltip after 3 seconds
            setTimeout(() => {
                if (tooltip.parentNode) {
                    tooltip.remove();
                }
            }, 3000);
        });
        
        bar.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.gantt-tooltip');
            if (tooltip) {
                setTimeout(() => tooltip.remove(), 200);
            }
        });
        
        // Click handler untuk detail estimasi
        bar.addEventListener('click', function() {
            const estimasiInfo = this.title;
            Swal.fire({
                title: 'Detail Estimasi',
                text: estimasiInfo,
                icon: 'info',
                confirmButtonColor: '#16a34a'
            });
        });
    });
    
    // Smooth horizontal scroll
    const tableContainer = document.querySelector('.gantt-table-container');
    if (tableContainer) {
        // Add scroll indicators
        const scrollIndicator = document.createElement('div');
        scrollIndicator.className = 'scroll-indicator';
        scrollIndicator.innerHTML = '← Scroll horizontal untuk melihat lebih banyak →';
        scrollIndicator.style.cssText = `
            position: sticky;
            top: 0;
            background: #fef3c7;
            text-align: center;
            padding: 8px;
            font-size: 12px;
            color: #92400e;
            z-index: 10;
            display: none;
        `;
        
        tableContainer.insertBefore(scrollIndicator, tableContainer.firstChild);
        
        // Show scroll indicator on small screens
        if (window.innerWidth < 1200) {
            scrollIndicator.style.display = 'block';
        }
    }
    
    // Auto-highlight today column
    const today = new Date();
    const currentDay = today.getDate();
    const currentMonth = today.getMonth() + 1;
    
    if ((currentMonth === 4 || currentMonth === 5) && today.getFullYear() === 2024) {
        const todayColumns = document.querySelectorAll('.today-column');
        todayColumns.forEach(col => {
            col.style.background = '#fef3c7';
        });
    }
});
</script>
