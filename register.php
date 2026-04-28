<?php
require_once 'auth.php';
require_once 'csv_helper.php';

if (isLoggedIn()) { header('Location: mailer.php'); exit; }

$flash = ['msg' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username || !$email || !$password) {
        $flash = ['msg' => 'All fields are required.', 'type' => 'error'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash = ['msg' => 'Invalid email address.', 'type' => 'error'];
    } elseif (strlen($password) < 6) {
        $flash = ['msg' => 'Password must be at least 6 characters.', 'type' => 'error'];
    } elseif ($password !== $password2) {
        $flash = ['msg' => 'Passwords do not match.', 'type' => 'error'];
    } elseif (findUserByUsername($username)) {
        $flash = ['msg' => 'Username already taken.', 'type' => 'error'];
    } else {
        // First user becomes admin
        $role = usersCount() === 0 ? 'admin' : 'user';
        $user = createUser($username, $password, $email, $role);
        loginUser($user);
        logSession($user['id'], 'login');
        setFlash('Welcome, ' . $username . '! Account created successfully.', 'success');
        header('Location: mailer.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-center">
  <div class="auth-card animate-in">

    <div class="auth-logo">
      <div class="logo-icon">✉️</div>
      <h1>Create Account</h1>
      <p>Join MailerApp to start sending emails</p>
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
               placeholder="Choose a username" autocomplete="username" autofocus>
      </div>

      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" autocomplete="email">
      </div>

      <div class="field">
        <label>Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw1" placeholder="Min. 6 characters" autocomplete="new-password">
          <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)">Show</button>
        </div>
      </div>

      <div class="field">
        <label>Confirm Password</label>
        <div class="pw-wrap">
          <input type="password" name="password2" id="pw2" placeholder="Repeat password" autocomplete="new-password">
          <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)">Show</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="margin-top:8px">
        Create Account →
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:0.85rem;color:var(--text-muted)">
      Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600">Sign in</a>
    </p>
  </div>
</div>
<script>
function togglePw(id, btn) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
  btn.textContent = f.type === 'password' ? 'Show' : 'Hide';
}
</script>
</body>
</html>
