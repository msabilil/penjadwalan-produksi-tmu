<?php
/**
 * Administrator - Data User Page
 * User management page for administrator role
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/user_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['administrator']);

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah':
                $data = [
                    'nama' => sanitize_input($_POST['nama']),
                    'username' => sanitize_input($_POST['username']),
                    'password' => $_POST['password'],
                    'role' => sanitize_input($_POST['role']),
                    'no_telepon' => sanitize_input($_POST['no_telepon'])
                ];
                
                $result = tambah_user($data);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: data_user.php?action=added');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'hapus':
                $id_user = intval($_POST['id_user']);
                $result = hapus_user($id_user);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: data_user.php?action=deleted');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
        }
    }
}

// Handle GET redirects with success messages
if (isset($_GET['action']) && isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear after displaying
}

// Get all users
$users_result = ambil_semua_user();
$users = $users_result['success'] ? $users_result['data'] : [];

// Get role options based on database schema
$role_options = [
    'administrator' => 'Administrator',
    'staf penjualan' => 'Staf Penjualan',
    'manager penerbit' => 'Manager Penerbit',
    'supervisor produksi' => 'Supervisor Produksi'
];

$page_title = 'Data User';

// Add JavaScript file for this page
$additional_js = ['assets/js/pages/administrator/data_user.js'];

// Set SweetAlert messages using the layout system
if ($success_message) {
    $swal_success = $success_message;
}
if ($error_message) {
    $swal_error = $error_message;
}

ob_start();
?>

<!-- Custom Styles for Data User -->
<style>
    .card-hover {
        transition: all 0.3s ease-in-out;
    }
    
    .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        transition: all 0.3s ease-in-out;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #15803d 0%, #166534 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(22, 163, 74, 0.4);
    }
    
    .table-row {
        transition: all 0.2s ease-in-out;
    }
    
    .table-row:hover {
        background-color: #f1f5f9;
        transform: scale(1.01);
    }
    
    .avatar-gradient {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    }
    
    .modal-backdrop {
        backdrop-filter: blur(8px);
        background-color: rgba(0, 0, 0, 0.3);
    }
    
    .animate-fade-in {
        animation: fadeIn 0.4s ease-in-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-input {
        transition: all 0.3s ease-in-out;
    }
    
    .form-input:focus {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
    }
    
    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<!-- Page Content -->
<div class="p-6">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Data User</h1>
                <p class="text-gray-600">Kelola data pengguna sistem penjadwalan produksi</p>
            </div>
            <button onclick="showTambahModal()" class="btn-primary text-white px-6 py-3 rounded-xl flex items-center space-x-3 shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="font-medium">Tambah User</span>
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Daftar User</h3>
            <p class="text-sm text-gray-600 mt-1">Total: <?php echo count($users); ?> user</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">User Info</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No Telepon</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 9a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada data user</h3>
                                    <p class="text-gray-500 mb-4">Klik tombol "Tambah User" untuk menambah data user baru</p>
                                    <button onclick="showTambahModal()" class="btn-primary text-white px-4 py-2 rounded-lg">
                                        Tambah User Pertama
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $index => $user): ?>
                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900"><?php echo $index + 1; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <div class="h-12 w-12 rounded-full avatar-gradient flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-sm">
                                                    <?php echo strtoupper(substr($user['nama'], 0, 2)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user['nama']); ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo $user['id_user']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $role_colors = [
                                        'administrator' => 'bg-green-100 text-green-800 border-green-200',
                                        'staf penjualan' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'manager penerbit' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'supervisor produksi' => 'bg-orange-100 text-orange-800 border-orange-200'
                                    ];
                                    $color_class = $role_colors[$user['role']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                    ?>
                                    <span class="badge <?php echo $color_class; ?> border">
                                        <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($user['no_telepon']) ? htmlspecialchars($user['no_telepon']) : '-'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($user['id_user'] != $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete(<?php echo $user['id_user']; ?>, '<?php echo htmlspecialchars($user['nama'], ENT_QUOTES); ?>')" 
                                                class="bg-red-100 hover:bg-red-200 text-red-700 hover:text-red-900 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-lg">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Hapus
                                        </button>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium">
                                            Current User
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div id="tambahModal" class="hidden fixed inset-0 modal-backdrop overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white animate-fade-in">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Tambah User Baru</h3>
                <button onclick="closeTambahModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="tambah">
                
                <div>
                    <label for="nama" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" required 
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Masukkan nama lengkap">
                </div>
                
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                    <input type="text" id="username" name="username" required 
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Masukkan username">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Masukkan password">
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                    <select id="role" name="role" required 
                            class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Pilih Role</option>
                        <?php foreach ($role_options as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="no_telepon" class="block text-sm font-semibold text-gray-700 mb-2">No Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon"
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Masukkan nomor telepon (opsional)">
                </div>
                
                <div class="flex justify-end space-x-3 pt-6">
                    <button type="button" onclick="closeTambahModal()" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="btn-primary px-6 py-3 text-sm font-medium text-white rounded-xl shadow-lg">
                        Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for delete -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id_user" id="deleteUserId">
</form>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_administrator.php';
?>
