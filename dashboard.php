<?php
require_once 'auth.php';
requireAdmin();
require_once 'csv_helper.php';

$users   = allUsers();
$mails   = allMails();
$events  = allEvents(50);

$totalUsers  = count($users);
$totalMails  = count($mails);
$todayMails  = count(array_filter($mails, fn($m) => str_starts_with($m['sent_at'], date('Y-m-d'))));
$todayEvents = count(array_filter($events, fn($e) => str_starts_with($e['created_at'], date('Y-m-d'))));

// Mails per day (last 7 days)
$mailsByDay = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $mailsByDay[$d] = count(array_filter($mails, fn($m) => str_starts_with($m['sent_at'], $d)));
}

// Top senders
$senderMap = [];
foreach ($mails as $m) {
    $uid = $m['user_id'];
    $senderMap[$uid] = ($senderMap[$uid] ?? 0) + 1;
}
arsort($senderMap);
$topSenders = array_slice($senderMap, 0, 5, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title">📊 Dashboard</span>
      <span style="font-size:0.82rem;color:var(--text-muted)"><?= date('l, F j, Y') ?></span>
    </div>
    <div class="page-body wide">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">👥</div>
          <div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Total Users</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">📧</div>
          <div>
            <div class="stat-value"><?= $totalMails ?></div>
            <div class="stat-label">Emails Sent</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">📤</div>
          <div>
            <div class="stat-value"><?= $todayMails ?></div>
            <div class="stat-label">Sent Today</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow">⚡</div>
          <div>
            <div class="stat-value"><?= $todayEvents ?></div>
            <div class="stat-label">Events Today</div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

        <!-- Activity chart (simple bars) -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">📈 Emails – Last 7 Days</span>
          </div>
          <div class="card-body">
            <?php $max = max(array_values($mailsByDay)) ?: 1; ?>
            <div style="display:flex;align-items:flex-end;gap:8px;height:100px">
              <?php foreach ($mailsByDay as $day => $cnt): ?>
              <?php $h = max(4, (int)(($cnt / $max) * 90)); ?>
              <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
                <span style="font-size:0.68rem;color:var(--text-muted);font-weight:700"><?= $cnt ?></span>
                <div style="width:100%;height:<?= $h ?>px;background:var(--primary);border-radius:4px 4px 0 0;transition:height .3s" title="<?= $day ?>: <?= $cnt ?> emails"></div>
                <span style="font-size:0.65rem;color:var(--text-light)"><?= date('D', strtotime($day)) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Top senders -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">🏆 Top Senders</span>
          </div>
          <div class="card-body" style="padding:16px">
            <?php if ($topSenders): ?>
            <?php foreach ($topSenders as $uid => $cnt):
              $u = findUserById($uid);
              $uname = $u ? $u['username'] : $uid;
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
              <div class="user-avatar" style="width:32px;height:32px;font-size:0.8rem"><?= strtoupper(substr($uname,0,1)) ?></div>
              <div style="flex:1;font-size:0.87rem;font-weight:600"><?= htmlspecialchars($uname) ?></div>
              <span class="badge badge-blue"><?= $cnt ?> emails</span>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state" style="padding:20px"><div class="empty-desc">No emails yet</div></div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Recent events -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">⚡ Recent Activity</span>
          <a href="logs.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding:16px 24px">
          <?php if ($events): ?>
          <?php foreach (array_slice($events, 0, 15) as $ev):
            $user = findUserById($ev['user_id']);
            $uname = $user ? $user['username'] : ($ev['user_id'] ?: 'System');
            $dotClass = in_array($ev['type'], ['login','logout','register','mail_sent']) ? $ev['type'] : 'default';
          ?>
          <div class="event-row">
            <div class="event-dot <?= $dotClass ?>"></div>
            <div>
              <div class="event-detail">
                <strong><?= htmlspecialchars($uname) ?></strong>
                — <?= htmlspecialchars($ev['detail']) ?>
              </div>
              <div class="event-meta">
                <?= htmlspecialchars($ev['created_at']) ?> · <?= htmlspecialchars($ev['ip']) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <div class="empty-state" style="padding:20px"><div class="empty-desc">No events yet</div></div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
