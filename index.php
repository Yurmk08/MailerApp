<?php
// ════════════════════════════════════════════
//  index.php  –  Main entry point
// ════════════════════════════════════════════
require_once __DIR__ . '/auth.php';

// If not logged in → login page
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// If logged in → check role
if (isAdmin()) {
    // Admins go to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Regular users go to mailer
    header('Location: mailer.php');
    exit;
}
?>
