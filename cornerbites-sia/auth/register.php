<?php
// auth/register.php
// Registration form for new users with improved design.

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Set global font for the body */
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom background gradient for the body */
        body {
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
        }
        /* Custom styles for input focus */
        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.45); /* Equivalent to focus:ring-orange-500/45 */
            border-color: #f97316; /* Equivalent to border-orange-500 */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen text-gray-800">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-500 ease-in-out hover:scale-105">
        <div class="text-center mb-8">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-2">Daftar Akun Baru</h2>
            <p class="text-gray-600">Bergabunglah dengan Corner Bites SIA!</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-6 animate-fade-in" role="alert">
                <p class="font-semibold text-sm"><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-6 animate-fade-in" role="alert">
                <p class="font-semibold text-sm"><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <form action="/cornerbites-sia/process/register_process.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username:</label>
                <input type="text" id="username" name="username" class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50 transition duration-300 placeholder-gray-400" placeholder="Pilih username Anda" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password:</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50 transition duration-300 placeholder-gray-400" placeholder="Buat password" required>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:ring-orange-500 focus:ring-opacity-50 transition duration-300 placeholder-gray-400" placeholder="Konfirmasi password Anda" required>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:from-orange-600 hover:to-red-700 focus:outline-none focus:ring-4 focus:ring-orange-300 focus:ring-opacity-75 transition duration-300 transform hover:-translate-y-1">
                Daftar
            </button>
        </form>

        <!-- Link to Login Page -->
        <div class="text-center mt-6">
            <p class="text-gray-600 text-sm">Sudah punya akun? 
                <a href="/cornerbites-sia/auth/login.php" class="text-blue-600 hover:text-blue-800 font-semibold transition duration-200">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
