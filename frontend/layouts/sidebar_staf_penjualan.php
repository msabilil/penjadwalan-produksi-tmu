<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - Staf Penjualan' : 'Staf Penjualan' ?> | Penjadwalan Produksi TMU</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .sidebar {
            width: 256px;
            transition: transform 0.3s ease;
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        .main-content {
            margin-left: 256px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 50;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #6b7280;
            text-decoration: none;
            border-radius: 8px;
            margin: 4px 0;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background-color: #f3f4f6;
            color: #16a34a;
        }
        
        .nav-link.active {
            background-color: #dcfce7;
            color: #16a34a;
            font-weight: 500;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 12px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile menu overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
    
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 h-full bg-white shadow-lg z-50">
        <div class="flex flex-col h-full">
            <!-- Logo and Brand -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="w-full h-16 flex items-center justify-center bg-gray-50 rounded-lg">
                    <img src="../../../frontend/assets/images/logo tmu.png" 
                         alt="TMU Logo" 
                         class="max-w-full max-h-full object-contain filter drop-shadow-sm"
                         style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjUwIiB2aWV3Qm94PSIwIDAgMTAwIDUwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iNTAiIGZpbGw9IiMxNmEzNGEiLz48dGV4dCB4PSI1MCIgeT0iMzAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiIGZvbnQtd2VpZ2h0PSJib2xkIj5UTVUgTE9HTzwvdGV4dD48L3N2Zz4='">
                </div>
                <button id="closeSidebar" class="md:hidden text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2">
                <a href="beranda.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'beranda.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Beranda</span>
                </a>
                
                <a href="pesanan.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pesanan</span>
                </a>

                <a href="desain.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'desain.php' ? 'active' : '' ?>">
                    <i class="fas fa-palette"></i>
                    <span>Desain</span>
                </a>

                <a href="estimasi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'estimasi.php' ? 'active' : '' ?>">
                    <i class="fas fa-calculator"></i>
                    <span>Estimasi</span>
                </a>
            </nav>
            
            <!-- User Info and Logout -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                        </p>
                        <p class="text-xs text-gray-500 truncate">Staf Penjualan</p>
                    </div>
                </div>
                
                <button onclick="confirmLogout()" 
                        class="w-full flex items-center space-x-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content min-h-screen">
        <!-- Top bar for mobile -->
        <div class="md:hidden bg-white shadow-sm border-b border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <button id="openSidebar" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="font-semibold text-gray-900">
                    <?= isset($page_title) ? $page_title : 'Staf Penjualan' ?>
                </h1>
                <div></div>
            </div>
        </div>
        
        <!-- Page Content -->
        <?php if (isset($page_content)): ?>
            <?= $page_content ?>
        <?php elseif (isset($content)): ?>
            <?= $content ?>
        <?php else: ?>
            <div class="p-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <p class="text-gray-500">Konten halaman akan dimuat di sini.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script>
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        const mobileOverlay = document.getElementById('mobile-overlay');
        const openSidebar = document.getElementById('openSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        
        // Mobile sidebar controls
        openSidebar?.addEventListener('click', () => {
            sidebar.classList.add('show');
            mobileOverlay.classList.remove('hidden');
        });
        
        closeSidebar?.addEventListener('click', () => {
            sidebar.classList.remove('show');
            mobileOverlay.classList.add('hidden');
        });
        
        mobileOverlay?.addEventListener('click', () => {
            sidebar.classList.remove('show');
            mobileOverlay.classList.add('hidden');
        });
        
        // Logout confirmation function
        function confirmLogout() {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar dari sistem?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../../backend/utils/logout.php';
                }
            });
        }

        // Show SweetAlert messages if set
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($swal_success) && !empty($swal_success)): ?>
            Swal.fire({
                title: 'Berhasil!',
                text: '<?= addslashes($swal_success) ?>',
                icon: 'success',
                confirmButtonColor: '#16a34a'
            });
            <?php endif; ?>
            
            <?php if (isset($swal_error) && !empty($swal_error)): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?= addslashes($swal_error) ?>',
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
