<?php
require_once 'auth.php';
requireAdmin();
require_once 'csv_helper.php';

$filter = $_GET['type'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

$events = csvRead(EVENTS_CSV);
$events = array_reverse($events);

if ($filter) {
    $events = array_values(array_filter($events, fn($e) => $e['type'] === $filter));
}

$total = count($events);
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$events = array_slice($events, ($page - 1) * $perPage, $perPage);

$types = ['login','logout','register','mail_sent','mail_error','failed_login','admin_toggle','admin_role'];

$typeEmoji = [
    'login'        => '🔓',
    'logout'       => '🔒',
    'register'     => '👤',
    'mail_sent'    => '📧',
    'mail_error'   => '❌',
    'failed_login' => '⚠️',
    'admin_toggle' => '⚙️',
    'admin_role'   => '🔑',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Logs – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title">📝 Event Logs</span>
      <span class="badge badge-gray"><?= $total ?> events</span>
    </div>
    <div class="page-body wide">

      <!-- Filters -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px" class="animate-in">
        <a href="logs.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline' ?>">All</a>
        <?php foreach ($types as $t): ?>
        <a href="logs.php?type=<?= $t ?>" class="btn btn-sm <?= $filter === $t ? 'btn-primary' : 'btn-outline' ?>">
          <?= ($typeEmoji[$t] ?? '•') . ' ' . $t ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="table-wrapper animate-in">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Type</th>
              <th>User</th>
              <th>Detail</th>
              <th>IP Address</th>
              <th>User Agent</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($events): ?>
            <?php foreach ($events as $ev):
              $user  = $ev['user_id'] ? findUserById($ev['user_id']) : null;
              $uname = $user ? $user['username'] : ($ev['user_id'] ?: '—');
            ?>
            <tr>
              <td class="mono" style="font-size:0.78rem;white-space:nowrap"><?= htmlspecialchars($ev['created_at']) ?></td>
              <td>
                <span class="badge <?= match($ev['type']) {
                  'login','register' => 'badge-green',
                  'mail_sent'        => 'badge-blue',
                  'mail_error','failed_login' => 'badge-red',
                  'logout'           => 'badge-gray',
                  default            => 'badge-yellow',
                } ?>">
                  <?= ($typeEmoji[$ev['type']] ?? '•') . ' ' . htmlspecialchars($ev['type']) ?>
                </span>
              </td>
              <td>
                <?php if ($user): ?>
                <div style="display:flex;align-items:center;gap:6px">
                  <div class="user-avatar" style="width:24px;height:24px;font-size:0.68rem"><?= strtoupper(substr($uname,0,1)) ?></div>
                  <?= htmlspecialchars($uname) ?>
                </div>
                <?php else: ?>
                <span class="mono" style="color:var(--text-light)">—</span>
                <?php endif; ?>
              </td>
              <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.87rem">
                <?= htmlspecialchars($ev['detail']) ?>
              </td>
              <td class="mono"><?= htmlspecialchars($ev['ip']) ?></td>
              <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.75rem;color:var(--text-light)">
                <?= htmlspecialchars(mb_substr($ev['ua'] ?? '', 0, 80)) ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6">
              <div class="empty-state"><div class="empty-icon">📭</div><div class="empty-title">No events found</div></div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="logs.php?type=<?= $filter ?>&page=<?= $p ?>"
           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
