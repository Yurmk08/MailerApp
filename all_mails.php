<?php
require_once 'auth.php';
requireAdmin();
require_once 'csv_helper.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$search  = trim($_GET['q'] ?? '');

$mails = array_reverse(allMails());

if ($search) {
    $mails = array_values(array_filter($mails, fn($m) =>
        str_contains(strtolower($m['to']), strtolower($search)) ||
        str_contains(strtolower($m['subject']), strtolower($search))
    ));
}

$total = count($mails);
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$mails = array_slice($mails, ($page - 1) * $perPage, $perPage);

function priLabel(string $p): string {
    return ['1'=>'<span class="priority-high">High</span>','3'=>'<span class="priority-normal">Normal</span>','5'=>'<span class="priority-low">Low</span>'][$p] ?? 'Normal';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Emails – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title">📨 All Emails</span>
      <span class="badge badge-blue"><?= $total ?> total</span>
    </div>
    <div class="page-body wide">

      <form method="GET" style="margin-bottom:20px;display:flex;gap:10px" class="animate-in">
        <input type="search" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by recipient or subject…" style="max-width:360px;margin:0">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search): ?><a href="all_mails.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
      </form>

      <div class="table-wrapper animate-in">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Sent By</th>
              <th>From</th>
              <th>To</th>
              <th>Subject</th>
              <th>Priority</th>
              <th>Attachments</th>
              <th>Sent At</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($mails): ?>
            <?php foreach ($mails as $i => $m):
              $user  = findUserById($m['user_id']);
              $uname = $user ? $user['username'] : '—';
            ?>
            <tr>
              <td class="mono"><?= ($page - 1) * $perPage + $i + 1 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:6px">
                  <div class="user-avatar" style="width:26px;height:26px;font-size:0.72rem"><?= strtoupper(substr($uname,0,1)) ?></div>
                  <?= htmlspecialchars($uname) ?>
                </div>
              </td>
              <td class="mono" style="font-size:0.78rem"><?= htmlspecialchars($m['from_email']) ?></td>
              <td style="font-weight:600;font-size:0.87rem"><?= htmlspecialchars($m['to']) ?></td>
              <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($m['subject']) ?>
              </td>
              <td><?= priLabel($m['priority'] ?? '3') ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted)">
                <?= $m['attachments'] ? '📎 ' . htmlspecialchars($m['attachments']) : '—' ?>
              </td>
              <td class="mono" style="font-size:0.78rem;white-space:nowrap"><?= htmlspecialchars($m['sent_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="8">
              <div class="empty-state"><div class="empty-icon">📭</div>
              <div class="empty-title">No emails found</div></div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="all_mails.php?q=<?= urlencode($search) ?>&page=<?= $p ?>"
           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
