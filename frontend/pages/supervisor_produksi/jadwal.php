<?php
/**
 * Supervisor Produksi - Jadwal Page
 * Production schedule management page for supervisor produksi role
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['supervisor produksi']);

// Set page variables
$page_title = 'Jadwal Produksi';

require_once __DIR__ . '/../../../backend/functions/jadwal_functions.php';

$jadwal_list = getJadwalList();
$jadwal_produksi = getJadwalProduksi();

// Function to calculate production dates
function calculateProductionDate($dayNumber, $startDate = null) {
    if ($startDate === null) {
        $startDate = date('Y-m-d'); // Today as default
    }
    $date = new DateTime($startDate);
    $date->add(new DateInterval('P' . ($dayNumber - 1) . 'D'));
    return $date->format('Y-m-d');
}

function formatIndonesianDate($date) {
    if (empty($date)) {
        return '-';
    }
    $months = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Ags',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];
    
    $dateObj = new DateTime($date);
    $day = $dateObj->format('d');
    $month = $months[$dateObj->format('m')];
    $year = $dateObj->format('Y');
    
    return $day . ' ' . $month . ' ' . $year;
}

// Backend function for calculating estimated completion date
function hitung_tanggal_estimasi_selesai($tanggal_pesanan, $waktu_hari) {
    if (empty($tanggal_pesanan) || $waktu_hari === null) {
        return null;
    }
    $hari = (int) ceil((float) $waktu_hari);
    if ($hari < 0) { $hari = 0; }
    $ts = strtotime($tanggal_pesanan . ' +' . $hari . ' day');
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d', $ts);
}

// Start output buffering
ob_start();
?>
<style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #eef0efff 0%, #eef0efff 100%);
        }

        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #065f46;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #6b7280;
            font-size: 1.1rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(248, 250, 252, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(16, 185, 129, 0.25);
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #718096;
            font-size: 0.9rem;
        }

        .section {
            background: rgba(248, 250, 252, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(34, 197, 94, 0.2);
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-left: 10px;
        }

        .section-header i {
            font-size: 1.5rem;
            background: linear-gradient(45deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1400px;
        }

        th {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        th:first-child {
            border-top-left-radius: 10px;
        }

        th:last-child {
            border-top-right-radius: 10px;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(34, 197, 94, 0.1);
            color: #065f46;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        tr:hover {
            background: rgba(16, 185, 129, 0.05);
            transition: all 0.2s ease;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #48bb78;
            color: white;
        }

        .status-pending {
            background: #ed8936;
            color: white;
        }

        .status-completed {
            background: #4299e1;
            color: white;
        }

        .batch-info {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            color: #10b981;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: rgba(34, 197, 94, 0.1);
            color: #065f46;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #10b981, #059669);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .capacity-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
        }

        .capacity-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.7rem;
        }

        .capacity-high { background: #10b981; }
        .capacity-medium { background: #f59e0b; }
        .capacity-low { background: #ef4444; }

        .date-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .date-label {
            font-size: 0.7rem;
            color: #718096;
            text-transform: uppercase;
            font-weight: 600;
        }

        .date-value {
            font-weight: 600;
            color: #065f46;
            font-size: 0.85rem;
        }

        .date-range {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 120px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .section {
                padding: 20px;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.8rem;
                min-width: 800px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>

    <div class="dashboard-container">
        <!-- Production Detail Schedule -->
        <div class="section">
            <div class="section-header">
                <i class="fas fa-industry"></i>
                <h2>Jadwal Produksi Detail</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-barcode"></i> ID Estimasi</th>
                            <th><i class="fas fa-shopping-cart"></i> ID Pesanan</th>
                            <th><i class="fas fa-palette"></i> Nama Desain</th>
                            <th><i class="fas fa-user"></i> Nama Pemesan</th>
                            <th><i class="fas fa-calendar-plus"></i> Tanggal Pesanan</th>
                            <th><i class="fas fa-boxes"></i> Total Jumlah</th>
                            <th><i class="fas fa-paint-brush"></i> Waktu Desain</th>
                            <th><i class="fas fa-calendar-day"></i> Hari Desain</th>
                            <th><i class="fas fa-calendar-alt"></i> Tanggal Produksi</th>
                            <th><i class="fas fa-chart-bar"></i> Produksi Hari Ini</th>
                            <th><i class="fas fa-tachometer-alt"></i> Kapasitas</th>
                            <th><i class="fas fa-battery-half"></i> Sisa Kapasitas</th>
                            <th><i class="fas fa-calendar-week"></i> Hari Ke</th>
                            <th><i class="fas fa-flag-checkered"></i> Estimasi Selesai</th>
                        </tr>
                    </thead>
                    <tbody id="produksi-tbody">
                        <?php foreach($jadwal_produksi as $prod): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($prod['id_estimasi']) ?></strong></td>
                                <td><?= htmlspecialchars($prod['id_pesanan']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-palette" style="color: #10b981;"></i>
                                        <span style="font-weight: 600;"><?= htmlspecialchars($prod['nama']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-circle" style="color: #10b981;"></i>
                                        <span style="font-weight: 600;"><?= htmlspecialchars($prod['nama_pemesan']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-calendar-plus" style="color: #10b981;"></i>
                                        <span style="font-weight: 600; color: #065f46;">
                                            <?= formatIndonesianDate($prod['tanggal_pesanan'] ?? date('Y-m-d')) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-cube" style="color: #10b981;"></i>
                                        <span style="font-weight: 700; color: #065f46; font-size: 0.9rem;"><?= htmlspecialchars($prod['jumlah']) ?> eksemplar</span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($prod['waktu_desain_menit']) ?> menit</td>
                                <td><?= htmlspecialchars($prod['waktu_desain_hari']) ?> hari</td>
                                <td>
                                    <div class="date-range">
                                        <div class="date-info">
                                            <span class="date-label">Mulai</span>
                                            <span class="date-value">
                                                <i class="fas fa-play-circle" style="color: #10b981;"></i>
                                                <?= formatIndonesianDate($prod['tanggal_produksi']) ?>
                                            </span>
                                        </div>
                                        <div class="date-info">
                                            <span class="date-label">Selesai</span>
                                            <span class="date-value">
                                                <i class="fas fa-stop-circle" style="color: #ef4444;"></i>
                                                <?= formatIndonesianDate($prod['estimate']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $jumlah = $prod['jumlah_diproduksi_hari_ini'] ?? 0;
                                    $kapasitas = $prod['kapasitas_perhari'] ?? 1;
                                    $progress = $kapasitas > 0 ? ($jumlah / $kapasitas) * 100 : 0;
                                    $progress = min(100, $progress);
                                    ?>
                                    <div class="capacity-indicator">
                                        <span><?= htmlspecialchars($jumlah) ?> eksemplar</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="capacity-indicator">
                                        <?php 
                                        $capacityClass = 'capacity-high';
                                        if ($kapasitas < 30) $capacityClass = 'capacity-low';
                                        elseif ($kapasitas < 50) $capacityClass = 'capacity-medium';
                                        ?>
                                        <div class="capacity-circle <?= $capacityClass ?>"><?= htmlspecialchars($kapasitas) ?></div>
                                        <span>eksemplar/hari</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="capacity-indicator">
                                        <?php 
                                        $sisa = $prod['sisa_kapasitas_hari_ini'] ?? 0;
                                        $sisaClass = 'capacity-high';
                                        if ($sisa < 10) $sisaClass = 'capacity-low';
                                        elseif ($sisa < 25) $sisaClass = 'capacity-medium';
                                        ?>
                                        <div class="capacity-circle <?= $sisaClass ?>"><?= htmlspecialchars($sisa) ?></div>
                                        <span>eksemplar</span>
                                    </div>
                                </td>
                                <td><i class="fas fa-calendar-check"></i> Hari <?= htmlspecialchars($prod['hari_produksi_ke']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-flag-checkered" style="color: #10b981;"></i>
                                        <span style="font-weight: 600; color: #065f46;">
                                            <?= formatIndonesianDate($prod['tanggal_estimasi']) ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function editProduksi(idEstimasi) {
            console.log('Edit produksi:', idEstimasi);
            alert('Fitur edit untuk ID Estimasi: ' + idEstimasi);
        }

        function detailProduksi(idEstimasi) {
            console.log('Detail produksi:', idEstimasi);
            alert('Menampilkan detail untuk ID Estimasi: ' + idEstimasi);
        }

        // Add some interactivity and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Add click handlers for action buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const action = this.textContent.trim().toLowerCase();
                    
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255,255,255,0.6)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.left = (e.clientX - this.offsetLeft) + 'px';
                    ripple.style.top = (e.clientY - this.offsetTop) + 'px';
                    
                    this.style.position = 'relative';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Update progress bars with animation
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_supervisor_produksi.php';
?>