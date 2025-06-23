<?php
// auth/login.php
// Form login for users with improved design.

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

$error_message = ''; // Variable to store error messages.

// Check for error messages from the login process.
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the error message after display.
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Corner Bites SIA</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        /* Custom styles for input focus */
        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.45); /* Equivalent to focus:ring-blue-500/45 */
            border-color: #6366f1; /* Equivalent to border-blue-500 */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen text-gray-800">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-sm transform transition-all duration-500 ease-in-out hover:scale-105">
        <div class="text-center mb-8">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-2">Selamat Datang!</h2>
            <p class="text-gray-600">Silakan login untuk melanjutkan.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-6 animate-fade-in" role="alert">
                <p class="font-semibold text-sm"><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <form action="/cornerbites-sia/process/login_process.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username:</label>
                <input type="text" id="username" name="username" class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-blue-500 focus:ring-opacity-50 transition duration-300 placeholder-gray-400" placeholder="Masukkan username Anda" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password:</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-blue-500 focus:ring-opacity-50 transition duration-300 placeholder-gray-400" placeholder="Masukkan password Anda" required>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 focus:ring-opacity-75 transition duration-300 transform hover:-translate-y-1">
                Login
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-600 text-sm">Belum punya akun? 
                <a href="/cornerbites-sia/auth/register.php" class="text-blue-600 hover:text-blue-800 font-semibold transition duration-200">Daftar di sini</a>
            </p>
        </div>
    
    </div>
</body>
</html>
