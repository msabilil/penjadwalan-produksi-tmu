<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    // Redirect berdasarkan role
    switch ($_SESSION['user_role']) {
        case 'administrator':
            header('Location: frontend/pages/administrator/beranda.php');
            break;
        case 'staf penjualan':
            header('Location: frontend/pages/staf_penjualan/beranda.php');
            break;
        case 'manager penerbit':
            header('Location: frontend/pages/manager_penerbit/beranda.php');
            break;
        case 'supervisor produksi':
            header('Location: frontend/pages/supervisor_produksi/beranda.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}

require_once 'backend/functions/user_functions.php';
require_once 'backend/functions/helper_functions.php';

$error_message = '';
$success_message = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password tidak boleh kosong';
    } else {
        $login_result = verifikasi_login($username, $password);
        
        if ($login_result['success']) {
            $user = $login_result['data'];
            
            // Set session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Redirect berdasarkan role
            switch ($user['role']) {
                case 'administrator':
                    $redirect_url = 'frontend/pages/administrator/beranda.php';
                    break;
                case 'staf penjualan':
                    $redirect_url = 'frontend/pages/staf_penjualan/beranda.php';
                    break;
                case 'manager penerbit':
                    $redirect_url = 'frontend/pages/manager_penerbit/beranda.php';
                    break;
                case 'supervisor produksi':
                    $redirect_url = 'frontend/pages/supervisor_produksi/beranda.php';
                    break;
                default:
                    $redirect_url = 'login.php';
            }
            
            $success_message = 'Login berhasil! Mengalihkan ke beranda...';
            echo "<script>
                setTimeout(function() {
                    window.location.href = '$redirect_url';
                }, 1500);
            </script>";
        } else {
            $error_message = $login_result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Penjadwalan Produksi TMU</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Login Page JavaScript -->
    <script src="frontend/assets/js/login.js"></script>
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981', // Green theme
                        secondary: '#f3f4f6'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br min-h-screen flex items-center justify-center">
    
    <div class="w-full max-w-md items-center">
        <!-- Logo Perusahaan -->
        <div class="text-center mb-8">
            <div class="mx-auto w-32 h-32 flex items-center justify-center mb-4">
                <img src="frontend/assets/images/tmu logo.png" 
                     alt="TMU Logo" 
                     class="w-full h-full object-contain filter"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >
        </div>

        <!-- Login Form Container -->
        <div class="rounded-2xl shadow-xl p-8 border border-gray-600">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Masuk ke Sistem</h2>
                <p class="text-gray-600">Silakan masukkan kredensial Anda</p>
            </div>

            <form method="POST" class="space-y-6" id="loginForm">
                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2 text-left">
                        Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2 text-left">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan password"
                        >
                        <button 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            onclick="togglePassword()"
                        >
                            <svg id="eyeIcon" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Login Button -->
                <div>
                    <button 
                        type="submit" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        id="loginButton"
                    >
                        <span id="loginText">Masuk</span>
                        <svg id="loadingSpinner" class="hidden animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Demo Users Info -->
            <!-- <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-800 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Demo Users:
                </h3>
                <div class="grid grid-cols-1 gap-2 text-xs text-gray-600">
                    <div class="flex justify-between items-center p-2 rounded cursor-pointer hover:bg-gray-100 transition-colors" onclick="fillCredentials('admin', 'admin123')">
                        <span class="font-medium">Administrator:</span>
                        <span class="font-mono text-green-600">admin / admin123</span>
                    </div>
                    <div class="flex justify-between items-center p-2 rounded cursor-pointer hover:bg-gray-100 transition-colors" onclick="fillCredentials('staff', 'staff123')">
                        <span class="font-medium">Staf Penjualan:</span>
                        <span class="font-mono text-green-600">staff / staff123</span>
                    </div>
                    <div class="flex justify-between items-center p-2 rounded cursor-pointer hover:bg-gray-100 transition-colors" onclick="fillCredentials('manager', 'manager123')">
                        <span class="font-medium">Manager Penerbit:</span>
                        <span class="font-mono text-green-600">manager / manager123</span>
                    </div>
                    <div class="flex justify-between items-center p-2 rounded cursor-pointer hover:bg-gray-100 transition-colors" onclick="fillCredentials('supervisor', 'supervisor123')">
                        <span class="font-medium">Supervisor Produksi:</span>
                        <span class="font-mono text-green-600">supervisor / supervisor123</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2 italic">
                    💡 Tip: Klik pada kredensial untuk mengisi form secara otomatis
                </p>
            </div> -->
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; 2025 TMU Printing. All rights reserved.</p>
        </div>
    </div>

    <!-- JavaScript untuk notifikasi PHP -->
    <script>
        // Handle PHP notifications
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success_message): ?>
            showPhpNotifications('<?php echo addslashes($success_message); ?>', null);
            <?php endif; ?>

            <?php if ($error_message): ?>
            showPhpNotifications(null, '<?php echo addslashes($error_message); ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>
