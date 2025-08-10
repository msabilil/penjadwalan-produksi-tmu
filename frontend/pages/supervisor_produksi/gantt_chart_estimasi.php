<?php
// Auth
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['supervisor produksi']);

// Includes
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/helper_functions.php';

$page_title = 'Gantt Chart Estimasi (1 Bulan)';

// Ambil filter bulan/tahun dari query
$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('n');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
if ($bulan < 1 || $bulan > 12) { $bulan = (int) date('n'); }
if ($tahun < 2000) { $tahun = (int) date('Y'); }

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$firstOfMonth = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $tahun, $bulan));
$lastOfMonth = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $tahun, $bulan, $daysInMonth));
$todayStr = date('Y-m-d');

// Ambil data estimasi untuk Gantt
$result = ambil_estimasi_untuk_gantt($bulan, $tahun);
$data = $result['success'] ? ($result['data'] ?? []) : [];

// Helper bandingkan tanggal (string Y-m-d)
$toDate = function($s) {
    return $s ? DateTime::createFromFormat('Y-m-d', substr($s, 0, 10)) : null;
};

// Terapkan penjadwalan berurutan (single-stream, finish-to-start)
$scheduled = [];
$nextAvailable = null; // DateTime atau null
foreach ($data as $row) {
    $tglPesananStr = $row['tanggal_pesanan'] ?? null;
    $waktuHari = isset($row['waktu_hari']) ? (float)$row['waktu_hari'] : 0.0;

    $dur = (int) ceil($waktuHari);
    if ($dur < 1) { $dur = 1; }

    $orderDate = $toDate($tglPesananStr);
    if (!$orderDate) {
        // Jika tidak ada tanggal pesanan, lewati baris ini
        continue;
    }

    // Mulai terjadwal adalah maksimun dari tanggal pesanan dan nextAvailable
    $startScheduled = clone $orderDate;
    if ($nextAvailable && $orderDate < $nextAvailable) {
        $startScheduled = clone $nextAvailable;
    }

    // Selesai terjadwal = start + durasi - 1 hari (inklusif)
    $endScheduled = clone $startScheduled;
    if ($dur > 1) {
        $endScheduled->modify('+' . ($dur - 1) . ' day');
    }

    // Update nextAvailable = sehari setelah selesai
    $nextAvailable = (clone $endScheduled)->modify('+1 day');

    $scheduled[] = [
        'no_pesanan' => $row['no_pesanan'] ?? '-',
        'nama_pemesan' => $row['nama_pemesan'] ?? '-',
        'jumlah_pesanan' => $row['jumlah_pesanan'] ?? 0,
        'tanggal_pesanan' => $tglPesananStr,
        'waktu_hari' => $waktuHari,
        // Simpan tanggal selesai terjadwal (bukan yang asli dari estimasi)
        'jadwal_mulai' => $startScheduled->format('Y-m-d'),
        'jadwal_selesai' => $endScheduled->format('Y-m-d'),
        // referensi asli bila diperlukan
        'tanggal_estimasi_selesai_asli' => $row['tanggal_estimasi_selesai'] ?? null,
    ];
}

// Opsi daftar bulan
$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

ob_start();
?>
<!-- Include Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                zIndex: {
                    '5': '5',
                    '15': '15'
                }
            }
        }
    }
</script>
<style>
    /* Minimal custom styles for Gantt specific features */
    .gantt-table { border-collapse: collapse; table-layout: fixed; }
    .gantt-header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
    .month-header-bg { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
    .legend-box { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .gantt-bar { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .gantt-bar::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.2); border-radius: 4px; }
    
    /* Sticky Column Positions */
    .sticky-col-1 { position: sticky; left: 0; width: 120px; z-index: 5; }
    .sticky-col-2 { position: sticky; left: 120px; width: 150px; z-index: 5; }
    .sticky-col-3 { position: sticky; left: 270px; width: 100px; z-index: 5; }
    .sticky-col-4 { position: sticky; left: 370px; width: 130px; z-index: 5; }
    .sticky-header-1 { position: sticky; left: 0; width: 120px; z-index: 15; }
    .sticky-header-2 { position: sticky; left: 120px; width: 150px; z-index: 15; }
    .sticky-header-3 { position: sticky; left: 270px; width: 100px; z-index: 15; }
    .sticky-header-4 { position: sticky; left: 370px; width: 130px; z-index: 15; }
    
    .day-col { width: 35px; min-width: 35px; }
    .cell-height { height: 40px; }
    .bar-height { height: 28px; margin: 6px 2px; }
    .scrollbar-custom::-webkit-scrollbar { height: 8px; }
    .scrollbar-custom::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .scrollbar-custom::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .scrollbar-custom::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="mb-6 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gantt Chart Estimasi Produksi</h1>
                <p class="text-gray-600">Periode: <span class="font-semibold text-blue-600"><?= htmlspecialchars($namaBulan[$bulan]) ?> <?= htmlspecialchars((string)$tahun) ?></span></p>
            </div>
            <form method="get" class="flex items-center gap-3 bg-gray-50 p-4 rounded-lg border">
                <label class="text-sm font-medium text-gray-700">Bulan:</label>
                <select name="bulan" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <?php foreach ($namaBulan as $i=>$n): ?>
                        <option value="<?= $i ?>" <?= $i===$bulan? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="text-sm font-medium text-gray-700">Tahun:</label>
                <input type="number" name="tahun" class="border border-gray-300 rounded-md px-3 py-2 w-24 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?= $tahun ?>" min="2000" max="2100" />
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Terapkan
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- Legend Section -->
    <div class="mb-6">
        <div class="inline-flex items-center gap-2 text-sm text-gray-600 px-4 py-2 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-4 h-4 legend-box rounded-sm shadow-sm"></div>
            <span>Periode Estimasi Produksi (dari tanggal pesanan hingga estimasi selesai)</span>
        </div>
    </div>

    <?php if (!$result['success']): ?>
        <div class="mb-6 p-3 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <span>Gagal mengambil data: <?= htmlspecialchars($result['message'] ?? 'Unknown error') ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($data)): ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="text-center py-16 px-5">
                <div class="text-5xl mb-4 text-gray-300">📊</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Data</h3>
                <p class="text-gray-600">Tidak ada data estimasi produksi untuk periode ini.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="month-header-bg text-white text-center font-bold text-sm py-4 tracking-wide">
                <?= strtoupper($namaBulan[$bulan]) ?> <?= $tahun ?>
            </div>
            
            <div class="overflow-x-auto max-h-screen overflow-y-auto scrollbar-custom">
                <table class="gantt-table w-full text-xs">
                    <thead>
                        <tr>
                            <th class="gantt-header text-white py-3 px-2 font-semibold text-center border-r border-white/20 sticky top-0 sticky-header-1">Produk</th>
                            <th class="gantt-header text-white py-3 px-2 font-semibold text-center border-r border-white/20 sticky top-0 sticky-header-2">Nama Pemesan</th>
                            <th class="gantt-header text-white py-3 px-2 font-semibold text-center border-r border-white/20 sticky top-0 sticky-header-3">Jumlah</th>
                            <th class="gantt-header text-white py-3 px-2 font-semibold text-center border-r border-white/20 sticky top-0 sticky-header-4">Tgl Pesanan</th>
                            <?php for ($d=1; $d<=$daysInMonth; $d++): ?>
                                <th class="gantt-header text-white py-3 px-2 font-medium text-center text-xs day-col sticky top-0 z-10"><?= $d ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($scheduled as $row): ?>
                        <?php
                            $no = $row['no_pesanan'] ?? '-';
                            $nama = $row['nama_pemesan'] ?? '-';
                            $jumlah = isset($row['jumlah_pesanan']) ? (int)$row['jumlah_pesanan'] : 0;
                            $tglPesananStr = $row['tanggal_pesanan'] ?? null;
                            $waktuHari = isset($row['waktu_hari']) ? (float)$row['waktu_hari'] : 0.0;
                            $tglSelesaiStr = $row['jadwal_selesai'] ?? null;

                            $start = $toDate($row['jadwal_mulai'] ?? null);
                            $end = $toDate($row['jadwal_selesai'] ?? null);
                            $rowHasBar = $start && $end;

                            // Clamp ke bulan tampilan
                            $clampStart = $rowHasBar ? max($start, clone $firstOfMonth) : null;
                            $clampEnd = $rowHasBar ? min($end, clone $lastOfMonth) : null;
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors duration-150">
                            <td class="py-2.5 px-2 border-b border-gray-100 border-r-2 border-r-gray-200 align-middle font-semibold text-gray-800 bg-white sticky-col-1">
                                <?= htmlspecialchars((string)$no) ?>
                            </td>
                            <td class="py-2.5 px-2 border-b border-gray-100 border-r-2 border-r-gray-200 align-middle text-gray-600 bg-white sticky-col-2">
                                <?= htmlspecialchars((string)$nama) ?>
                            </td>
                            <td class="py-2.5 px-2 border-b border-gray-100 border-r-2 border-r-gray-200 align-middle font-semibold text-gray-900 text-center bg-white sticky-col-3">
                                <?= number_format($jumlah) ?>
                            </td>
                            <td class="py-2.5 px-2 border-b border-gray-100 border-r-2 border-r-gray-200 align-middle text-gray-500 text-center text-xs bg-white sticky-col-4">
                                <?= $tglPesananStr ? format_tanggal($tglPesananStr) : '-' ?>
                            </td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++): ?>
                                <?php
                                    $thisDateStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                                    $isToday = ($thisDateStr === $todayStr);
                                    $classes = 'cell-height bg-gray-50 relative border-r border-gray-100 transition-all duration-200 hover:bg-gray-100';
                                    $isInBar = false; $isStart=false; $isEnd=false;
                                    if ($rowHasBar && $clampStart && $clampEnd) {
                                        $cmp = DateTime::createFromFormat('Y-m-d', $thisDateStr);
                                        if ($cmp >= $clampStart && $cmp <= $clampEnd) {
                                            $isInBar = true;
                                            if ($cmp->format('Y-m-d') === $clampStart->format('Y-m-d')) { $isStart = true; }
                                            if ($cmp->format('Y-m-d') === $clampEnd->format('Y-m-d')) { $isEnd = true; }
                                        }
                                    }
                                    if ($isInBar) { 
                                        $classes = 'cell-height gantt-bar rounded relative bar-height'; 
                                        if ($isStart) { $classes .= ' border-l-2 border-l-emerald-800 rounded-l-md'; }
                                        if ($isEnd) { $classes .= ' border-r-2 border-r-emerald-800 rounded-r-md'; }
                                    }
                                    if ($isToday && !$isInBar) { $classes .= ' bg-amber-100 border-l-2 border-r-2 border-l-amber-500 border-r-amber-500'; }
                                ?>
                                <td class="<?= $classes ?>" title="<?= htmlspecialchars($nama) ?> | <?= htmlspecialchars((string)$no) ?>"></td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$page_content = ob_get_clean();
include '../../layouts/sidebar_supervisor_produksi.php';
