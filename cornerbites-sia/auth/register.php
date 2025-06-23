<?php
// auth/register.php
// Registration form for new users with improved modern design.

// Start the session if it hasn't been started yet.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect based on role.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: /cornerbites-sia/admin/dashboard.php");
    } else {
        header("Location: /cornerbites-sia/pages/dashboard.php");
    }
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_SESSION['error_message_register'])) {
    $error_message = $_SESSION['error_message_register'];
    unset($_SESSION['error_message_register']);
}
if (isset($_SESSION['success_message_register'])) {
    $success_message = $_SESSION['success_message_register'];
    unset($_SESSION['success_message_register']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Corner Bites SIA</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Inter from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 50%, #ff6b6b 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .input-focus:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.1);
            border-color: #f97316;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #ff6b47 0%, #fd9853 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 25s infinite linear;
        }
        
        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            left: 15%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            left: 75%;
            animation-delay: 7s;
        }
        
        .shape:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 60%;
            animation-delay: 14s;
        }
        
        .shape:nth-child(4) {
            width: 120px;
            height: 120px;
            left: 30%;
            animation-delay: 21s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #ef4444; width: 25%; }
        .strength-fair { background-color: #f59e0b; width: 50%; }
        .strength-good { background-color: #10b981; width: 75%; }
        .strength-strong { background-color: #059669; width: 100%; }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4 relative">
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="glass-effect rounded-3xl shadow-2xl w-full max-w-md p-8 relative z-10 fade-in">
        <!-- Logo/Brand Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-2xl mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Corner Bites SIA</h1>
            <p class="text-white text-opacity-80 text-sm">Sistem Informasi Akuntansi UMKM</p>
        </div>

        <!-- Welcome Message -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-semibold text-white mb-2">Bergabung dengan Kami!</h2>
            <p class="text-white text-opacity-70 text-sm">Buat akun baru untuk memulai perjalanan bisnis Anda</p>
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30 text-green-100 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="bg-red-500 bg-opacity-20 border border-red-400 border-opacity-30 text-red-100 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="/cornerbites-sia/process/register_process.php" method="POST" class="space-y-6" id="registerForm">
            <!-- Username Field -->
            <div class="space-y-2">
                <label for="username" class="block text-sm font-medium text-white text-opacity-90">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" id="username" name="username" 
                           class="input-focus w-full pl-10 pr-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-xl text-white placeholder-white placeholder-opacity-60 transition duration-300" 
                           placeholder="Pilih username unik Anda" required>
                </div>
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <label for="password" class="block text-sm font-medium text-white text-opacity-90">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" id="password" name="password" 
                           class="input-focus w-full pl-10 pr-12 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-xl text-white placeholder-white placeholder-opacity-60 transition duration-300" 
                           placeholder="Buat password yang kuat" required
                           oninput="checkPasswordStrength()">
                    <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg id="eye-icon-password" class="h-5 w-5 text-gray-400 hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                <!-- Password Strength Indicator -->
                <div class="mt-2">
                    <div class="bg-white bg-opacity-20 rounded-full h-1">
                        <div id="password-strength" class="password-strength bg-gray-400"></div>
                    </div>
                    <p id="password-text" class="text-xs text-white text-opacity-60 mt-1">Minimal 6 karakter</p>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="space-y-2">
                <label for="confirm_password" class="block text-sm font-medium text-white text-opacity-90">Konfirmasi Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="input-focus w-full pl-10 pr-12 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-xl text-white placeholder-white placeholder-opacity-60 transition duration-300" 
                           placeholder="Ulangi password Anda" required
                           oninput="checkPasswordMatch()">
                    <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg id="eye-icon-confirm" class="h-5 w-5 text-gray-400 hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                <p id="password-match" class="text-xs text-white text-opacity-60 mt-1"></p>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start">
                <input type="checkbox" id="terms" required class="w-4 h-4 mt-1 text-orange-600 bg-white bg-opacity-20 border-white border-opacity-30 rounded focus:ring-orange-500 focus:ring-2">
                <label for="terms" class="ml-3 text-sm text-white text-opacity-80">
                    Saya setuju dengan <a href="#" class="text-white font-semibold hover:text-opacity-80 transition-colors">Syarat dan Ketentuan</a> serta <a href="#" class="text-white font-semibold hover:text-opacity-80 transition-colors">Kebijakan Privasi</a>
                </label>
            </div>

            <!-- Register Button -->
            <button type="submit" id="submitBtn" class="btn-gradient w-full py-3 px-6 text-white font-semibold rounded-xl shadow-lg focus:outline-none focus:ring-4 focus:ring-orange-300 focus:ring-opacity-50 transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Buat Akun Baru
                </span>
            </button>
        </form>

        <!-- Login Link -->
        <div class="text-center mt-8 pt-6 border-t border-white border-opacity-20">
            <p class="text-white text-opacity-70 text-sm">
                Sudah punya akun? 
                <a href="/cornerbites-sia/auth/login.php" class="text-white font-semibold hover:text-opacity-80 transition-colors ml-1">
                    Masuk di sini
                </a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(`eye-icon-${fieldId === 'password' ? 'password' : 'confirm'}`);
            
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

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('password-strength');
            const strengthText = document.getElementById('password-text');
            
            let strength = 0;
            let text = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.classList.add('strength-weak');
                    text = 'Password lemah';
                    break;
                case 2:
                    strengthBar.classList.add('strength-fair');
                    text = 'Password cukup';
                    break;
                case 3:
                case 4:
                    strengthBar.classList.add('strength-good');
                    text = 'Password baik';
                    break;
                case 5:
                    strengthBar.classList.add('strength-strong');
                    text = 'Password sangat kuat';
                    break;
            }
            
            strengthText.textContent = text;
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            const submitBtn = document.getElementById('submitBtn');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
                submitBtn.disabled = false;
                return;
            }
            
            if (password === confirmPassword) {
                matchText.textContent = '✓ Password cocok';
                matchText.className = 'text-xs text-green-300 mt-1';
                submitBtn.disabled = false;
            } else {
                matchText.textContent = '✗ Password tidak cocok';
                matchText.className = 'text-xs text-red-300 mt-1';
                submitBtn.disabled = true;
            }
        }

        // Add floating animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach((input, index) => {
                input.style.animationDelay = `${index * 0.1}s`;
                input.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>