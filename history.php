<?php
require_once 'auth.php';
requireLogin();
require_once 'csv_helper.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$search  = trim($_GET['q'] ?? '');

$mails = array_reverse(array_values(mailsForUser($_SESSION['user_id'])));

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

function priLabel2(string $p): string {
    return ['1'=>'<span class="priority-high">High</span>','3'=>'<span class="priority-normal">Normal</span>','5'=>'<span class="priority-low">Low</span>'][$p] ?? 'Normal';
}
function ava(string $e): string { return strtoupper(substr(explode('@',$e)[0],0,1)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sent Mail – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title">📋 Sent Mail</span>
      <span class="badge badge-blue"><?= $total ?></span>
    </div>
    <div class="page-body">

      <form method="GET" style="margin-bottom:20px;display:flex;gap:10px" class="animate-in">
        <input type="search" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search sent emails…" style="margin:0">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search): ?><a href="history.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
      </form>

      <div class="card animate-in">
        <?php if ($mails): ?>
        <?php foreach ($mails as $m): ?>
        <div class="mail-item">
          <div class="mail-avatar"><?= ava($m['to']) ?></div>
          <div class="mail-content">
            <div class="mail-row1">
              <span class="mail-to"><?= htmlspecialchars($m['to']) ?></span>
              <span class="mail-time"><?= htmlspecialchars($m['sent_at']) ?></span>
            </div>
            <div class="mail-subject"><?= htmlspecialchars($m['subject']) ?></div>
            <div class="mail-preview"><?= htmlspecialchars(mb_substr($m['body'], 0, 120)) ?>…</div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;margin-left:10px">
            <?= priLabel2($m['priority'] ?? '3') ?>
            <?php if ($m['attachments']): ?>
            <span style="font-size:0.72rem;color:var(--text-muted)">📎</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <div class="empty-title">No emails found</div>
          <div class="empty-desc">Your sent emails will appear here.</div>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($pages > 1): ?>
      <div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="history.php?q=<?= urlencode($search) ?>&page=<?= $p ?>"
           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
