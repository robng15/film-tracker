<?php
function auth_start(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function require_login(): void {
    auth_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function attempt_login(string $username, string $password): bool {
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        auth_start();
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $username;
        return true;
    }
    return false;
}

function do_logout(): void {
    auth_start();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
