<?php
/**
 * Login Form Component
 * Komponen form login yang dapat digunakan kembali
 */

function render_login_form($username_value = '', $show_demo_users = true) {
    ?>
    <form method="POST" class="space-y-6" id="loginForm">
        <!-- Username Field -->
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
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
                    value="<?php echo htmlspecialchars($username_value); ?>"
                >
            </div>
        </div>

        <!-- Password Field -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
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

    <?php if ($show_demo_users): ?>
    <!-- Demo Users Info -->
    <div class="mt-8 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-sm font-medium text-gray-800 mb-3">Demo Users:</h3>
        <div class="grid grid-cols-1 gap-2 text-xs text-gray-600">
            <div class="flex justify-between">
                <span>Administrator:</span>
                <span class="font-mono">admin / admin123</span>
            </div>
            <div class="flex justify-between">
                <span>Staf Penjualan:</span>
                <span class="font-mono">staff / staff123</span>
            </div>
            <div class="flex justify-between">
                <span>Manager Penerbit:</span>
                <span class="font-mono">manager / manager123</span>
            </div>
            <div class="flex justify-between">
                <span>Supervisor Produksi:</span>
                <span class="font-mono">supervisor / supervisor123</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php
}

function render_company_logo() {
    ?>
    <div class="text-center mb-8">
        <div class="mx-auto w-24 h-24 bg-green-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">TMU Printing</h1>
        <p class="text-gray-600">Sistem Penjadwalan Produksi</p>
    </div>
    <?php
}

function render_login_scripts() {
    ?>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginButton = document.getElementById('loginButton');
            const loginText = document.getElementById('loginText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            // Show loading state
            loginButton.disabled = true;
            loginText.textContent = 'Memproses...';
            loadingSpinner.classList.remove('hidden');
        });

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Enter key navigation
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });
    </script>
    <?php
}
?>
