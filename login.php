<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';

if (isset($_POST['username']) && isset($_POST['password'])) {
    if (Auth::login($_POST['username'], $_POST['password'])) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Falscher Benutzername oder Passwort';
    }
}

if (Auth::check()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDo Liste - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1><i class="fas fa-tasks"></i> ToDo Liste</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <input type="text" name="username" placeholder="Benutzername" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Passwort" required>
            </div>
            <button type="submit">Anmelden</button>
        </form>
    </div>

    <button class="theme-toggle" onclick="toggleTheme()" title="Theme wechseln">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        // Theme Management
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateThemeIcon(theme);
        }

        function toggleTheme() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        }

        function updateThemeIcon(theme) {
            const icon = document.querySelector('.theme-toggle i');
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        }

        // Theme initialization
        document.addEventListener('DOMContentLoaded', () => {
            // Check for saved theme preference or system preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                setTheme(savedTheme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                setTheme('dark');
            }

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) { // Only if no manual preference is set
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        });
    </script>
</body>
</html> 