<?php
// ════════════════════════════════════════════
//  auth.php  –  Session & auth helpers
// ════════════════════════════════════════════
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: mailer.php');
        exit;
    }
}

function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }
function isAdmin(): bool    { return ($_SESSION['role'] ?? '') === 'admin'; }

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

function logoutUser(): void {
    session_destroy();
}
