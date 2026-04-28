<?php
require_once 'auth.php';
require_once 'csv_helper.php';

if (isLoggedIn()) { header('Location: mailer.php'); exit; }

$flash = ['msg' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $flash = ['msg' => 'Please fill in all fields.', 'type' => 'error'];
    } else {
        $user = findUserByUsername($username);
        if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
            loginUser($user);
            updateUserLastLogin($user['id']);
            logSession($user['id'], 'login');
            header('Location: mailer.php');
            exit;
        } else {
            $flash = ['msg' => 'Invalid credentials or account suspended.', 'type' => 'error'];
            logEvent('failed_login', '', "Attempt: {$username}");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-center">
  <div class="auth-card animate-in">

    <div class="auth-logo">
      <div class="logo-icon">✉️</div>
      <h1>MailerApp</h1>
      <p>Sign in to your account</p>
    </div>

    <?php if ($flash['msg']): ?>
    <div class="flash <?= $flash['type'] ?>">
      <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠️' : '✅' ?></span>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Enter your username" autocomplete="username" autofocus>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwField" placeholder="••••••••" autocomplete="current-password">
          <button type="button" class="pw-toggle" onclick="togglePw()">Show</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="margin-top:8px">
        Sign In →
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:0.85rem;color:var(--text-muted)">
      No account? <a href="register.php" style="color:var(--primary);font-weight:600">Create one</a>
    </p>
  </div>
</div>
<script>
function togglePw() {
  const f = document.getElementById('pwField');
  const b = f.nextElementSibling;
  f.type = f.type === 'password' ? 'text' : 'password';
  b.textContent = f.type === 'password' ? 'Show' : 'Hide';
}
</script>
</body>
</html>
