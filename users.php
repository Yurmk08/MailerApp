<?php
require_once 'auth.php';
requireAdmin();
require_once 'csv_helper.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = $_POST['uid'] ?? '';

    if ($action === 'toggle_status' && $uid) {
        $rows    = allUsers();
        $headers = ['id','username','email','password','role','created_at','last_login','login_count','status'];
        foreach ($rows as &$r) {
            if ($r['id'] === $uid && $r['id'] !== $_SESSION['user_id']) {
                $r['status'] = $r['status'] === 'active' ? 'suspended' : 'active';
                logEvent('admin_toggle', $_SESSION['user_id'], "Toggled user {$r['username']} to {$r['status']}");
            }
        }
        csvWrite(USERS_CSV, $rows, $headers);
        setFlash('User status updated.', 'success');
    } elseif ($action === 'change_role' && $uid) {
        $newRole = $_POST['role'] ?? 'user';
        $rows    = allUsers();
        $headers = ['id','username','email','password','role','created_at','last_login','login_count','status'];
        foreach ($rows as &$r) {
            if ($r['id'] === $uid) { $r['role'] = $newRole; }
        }
        csvWrite(USERS_CSV, $rows, $headers);
        logEvent('admin_role', $_SESSION['user_id'], "Changed role of {$uid} to {$newRole}");
        setFlash('Role updated.', 'success');
    }
    header('Location: users.php'); exit;
}

$flash  = getFlash();
$users  = allUsers();
$mails  = allMails();

$mailCountByUser = [];
foreach ($mails as $m) $mailCountByUser[$m['user_id']] = ($mailCountByUser[$m['user_id']] ?? 0) + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title">👥 User Management</span>
      <span class="badge badge-blue"><?= count($users) ?> total</span>
    </div>
    <div class="page-body wide">

      <?php if ($flash['msg']): ?>
      <div class="flash <?= $flash['type'] ?> animate-in">
        <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠️' : '✅' ?></span>
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
      <?php endif; ?>

      <div class="table-wrapper animate-in">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Logins</th>
              <th>Emails Sent</th>
              <th>Last Login</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr>
              <td class="mono"><?= $i + 1 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="user-avatar" style="width:30px;height:30px;font-size:0.75rem"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                  <strong><?= htmlspecialchars($u['username']) ?></strong>
                  <?php if ($u['id'] === $_SESSION['user_id']): ?><span class="badge badge-green" style="margin-left:4px">You</span><?php endif; ?>
                </div>
              </td>
              <td class="mono"><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span class="badge badge-red">⭐ Admin</span>
                <?php else: ?>
                  <span class="badge badge-gray">User</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($u['status'] === 'active'): ?>
                  <span class="badge badge-green">● Active</span>
                <?php else: ?>
                  <span class="badge badge-yellow">⏸ Suspended</span>
                <?php endif; ?>
              </td>
              <td class="mono"><?= htmlspecialchars($u['login_count'] ?? '0') ?></td>
              <td class="mono"><?= $mailCountByUser[$u['id']] ?? 0 ?></td>
              <td class="mono" style="font-size:0.78rem"><?= $u['last_login'] ?: '—' ?></td>
              <td class="mono" style="font-size:0.78rem"><?= htmlspecialchars($u['created_at']) ?></td>
              <td>
                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                <div style="display:flex;gap:6px;align-items:center">
                  <!-- Toggle status -->
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="uid" value="<?= htmlspecialchars($u['id']) ?>">
                    <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-outline' ?>">
                      <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                    </button>
                  </form>
                  <!-- Change role -->
                  <form method="POST" style="display:inline;display:flex;align-items:center;gap:4px">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="uid" value="<?= htmlspecialchars($u['id']) ?>">
                    <select name="role" style="height:30px;padding:0 24px 0 8px;font-size:0.78rem;margin:0;width:auto">
                      <option value="user"  <?= $u['role']==='user'  ? 'selected':'' ?>>User</option>
                      <option value="admin" <?= $u['role']==='admin' ? 'selected':'' ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Set</button>
                  </form>
                </div>
                <?php else: ?>
                <span style="font-size:0.78rem;color:var(--text-light)">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body>
</html>
