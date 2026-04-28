<?php
// sidebar.php - shared sidebar navigation
$current = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin = isAdmin();
$uname   = $_SESSION['username'] ?? '';
$role    = $_SESSION['role'] ?? 'user';
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="mailer.php" class="logo-mark">
      <div class="logo-icon">✉️</div>
      <span class="logo-text">MailerApp</span>
    </a>
  </div>

  <div class="sidebar-user">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($uname, 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($uname) ?></div>
        <div class="user-role"><?= $role === 'admin' ? '⭐ Admin' : 'User' ?></div>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Mail</div>
    <a href="mailer.php" class="nav-item <?= $current === 'mailer' ? 'active' : '' ?>">
      <span class="nav-icon">✉️</span> Compose
    </a>
    <a href="history.php" class="nav-item <?= $current === 'history' ? 'active' : '' ?>">
      <span class="nav-icon">📋</span> All Sent Mail
    </a>

    <?php if ($isAdmin): ?>
    <div class="nav-label" style="margin-top:8px">Admin</div>
    <a href="dashboard.php" class="nav-item <?= $current === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <a href="users.php" class="nav-item <?= $current === 'users' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Users
    </a>
    <a href="logs.php" class="nav-item <?= $current === 'logs' ? 'active' : '' ?>">
      <span class="nav-icon">📝</span> Event Logs
    </a>
    <a href="all_mails.php" class="nav-item <?= $current === 'all_mails' ? 'active' : '' ?>">
      <span class="nav-icon">📨</span> All Emails
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="nav-item">
      <span class="nav-icon">🚪</span> Sign Out
    </a>
  </div>
</aside>
