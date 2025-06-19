<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$database = "bearfruitsstudios";

$conn = new mysqli($servername, $db_username, $db_password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $_SESSION['loggedin'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login | BearFruits Studios</title>
    <link rel="icon" href="image/img1.jpg" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8ffe7 0%, #b8ffe6 100%);
        }
        .glass {
            background: rgba(255,255,255,0.82);
            box-shadow: 0 8px 40px rgba(44, 62, 80, 0.12);
            border-radius: 1.2rem;
            backdrop-filter: blur(4px);
        }
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            gap: 4px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md glass p-8">
        <div class="logo">
            <span style="font-size:2.1rem;color:#234b3a;"><i class="fa-solid fa-leaf"></i></span>
            <span style="font-weight:bold;letter-spacing:1px;">The BearFruits<br>Studio Admin</br></span>
        </div>
        <h2 class="text-xl font-bold text-center text-gray-700 mb-6 tracking-wide">Sign in to your admin account</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded mb-4 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form id="loginForm" method="POST" action="admin.php" autocomplete="off">
            <div class="mb-5">
                <label class="block font-semibold text-gray-700 mb-1" for="username">Username</label>
                <input type="text" name="username" id="username" required maxlength="40"
                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500 transition" />
            </div>
            <div class="mb-6 relative">
                <label class="block font-semibold text-gray-700 mb-1" for="password">Password</label>
                <input type="password" name="password" id="password" required maxlength="40"
                    class="w-full p-2 pr-10 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500 transition" />
                <button type="button" id="togglePassword" class="absolute top-8 right-2 text-sm text-gray-600 focus:outline-none">
                    <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eyeClosed" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 012.64-4.362m3.132-2.515A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7-.285.91-.702 1.765-1.233 2.543M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 3l18 18"/>
                    </svg>
                </button>
            </div>
            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded transition duration-300 shadow-md">
                Login
            </button>
        </form>
        <div class="mt-8 text-center text-gray-400 text-xs">Copyright © <?= date('Y') ?> BearFruits Studios</div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const togglePassword = document.getElementById("togglePassword");
            const passwordField = document.getElementById("password");
            const eyeOpen = document.getElementById("eyeOpen");
            const eyeClosed = document.getElementById("eyeClosed");
            togglePassword.addEventListener("click", () => {
                const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
                passwordField.setAttribute("type", type);
                eyeOpen.classList.toggle("hidden");
                eyeClosed.classList.toggle("hidden");
            });
            document.getElementById("username").focus();
        });
    </script>
</body>
</html>