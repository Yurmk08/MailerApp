<?php
// ════════════════════════════════════════════
//  logout.php  –  Sign out handler
// ════════════════════════════════════════════
require_once 'auth.php';
require_once 'csv_helper.php';

if (isLoggedIn()) {
    logSession($_SESSION['user_id'], 'logout');
    logoutUser();
}

header('Location: login.php');
exit;
?>
