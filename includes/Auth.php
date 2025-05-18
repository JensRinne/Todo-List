<?php
class Auth {
    public static function login($username, $password) {
        if (isset(USERS[$username]) && password_verify($password, USERS[$username]['password'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_name'] = USERS[$username]['name'];
            return true;
        }
        return false;
    }

    public static function logout() {
        unset($_SESSION['authenticated']);
        unset($_SESSION['username']);
        unset($_SESSION['user_name']);
        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    public static function requireAuth() {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function getCurrentUser() {
        return [
            'username' => $_SESSION['username'] ?? null,
            'name' => $_SESSION['user_name'] ?? null
        ];
    }
} 