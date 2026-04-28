<?php
require_once 'auth.php';
requireLogin();
require_once 'csv_helper.php';

$flash   = getFlash();
$history = array_slice(array_reverse(array_values(mailsForUser($_SESSION['user_id']))), 0, 20);
$totalSent = count(mailsForUser($_SESSION['user_id']));

function priorityLabel(string $p): string {
    return ['1' => '<span class="priority-high">High</span>',
            '3' => '<span class="priority-normal">Normal</span>',
            '5' => '<span class="priority-low">Low</span>'][$p] ?? 'Normal';
}
function avatarChar(string $email): string {
    return strtoupper(substr(explode('@', $email)[0], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compose – MailerApp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-layout">

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title">✉️ Compose Email</span>
      <div class="topbar-actions">
        <span style="font-size:0.82rem;color:var(--text-muted)"><?= $totalSent ?> emails sent</span>
      </div>
    </div>

    <div class="page-body">

      <?php if ($flash['msg']): ?>
      <div class="flash <?= $flash['type'] ?> animate-in">
        <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠️' : '✅' ?></span>
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
      <?php endif; ?>

      <!-- Compose card -->
      <div class="card animate-in" style="margin-bottom:28px">
        <div class="card-header">
          <span class="card-title">New Message</span>
          <span style="font-size:0.78rem;color:var(--text-muted)">From: <strong><?= htmlspecialchars(SMTP_USER) ?></strong></span>
        </div>
        <div class="card-body">

          <form method="POST" action="send.php" enctype="multipart/form-data" id="mailForm" novalidate>

            <div class="form-row">
              <div class="field">
                <label>To</label>
                <input type="text" name="to" id="toInput"
                       placeholder="user@example.com, other@example.com" autocomplete="off">
              </div>
              <div class="field" style="max-width:160px">
                <label>Priority</label>
                <select name="priority">
                  <option value="3">Normal</option>
                  <option value="1">High</option>
                  <option value="5">Low</option>
                </select>
              </div>
            </div>

            <div class="field">
              <label>Subject</label>
              <input type="text" name="subject" id="subjectInput" placeholder="Email subject...">
            </div>

            <div class="field">
              <label>Message Body</label>
              <div class="composer-wrap">
                <div class="toolbar" id="toolbar">

                  <!-- Font -->
                  <select class="tb-select" style="min-width:90px" onchange="setFont(this.value)" title="Font family">
                    <option value="Arial">Arial</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Helvetica">Helvetica</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Courier New">Courier New</option>
                    <option value="Verdana">Verdana</option>
                  </select>

                  <!-- Size -->
                  <select class="tb-select" style="min-width:56px" onchange="setFontSize(this.value)" title="Font size">
                    <option value="12">12</option>
                    <option value="14" selected>14</option>
                    <option value="16">16</option>
                    <option value="18">18</option>
                    <option value="20">20</option>
                    <option value="24">24</option>
                    <option value="28">28</option>
                    <option value="32">32</option>
                  </select>

                  <div class="tb-sep"></div>

                  <button type="button" class="tb-btn" id="btnB" onclick="exec('bold')"        title="Bold">
                    <svg viewBox="0 0 24 24"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" id="btnI" onclick="exec('italic')"      title="Italic">
                    <svg viewBox="0 0 24 24"><path d="M10 5v3h2.2l-3.4 8H6v3h8v-3h-2.2l3.4-8H18V5z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" id="btnU" onclick="exec('underline')"   title="Underline">
                    <svg viewBox="0 0 24 24"><path d="M12 17a6 6 0 006-6V3h-2.5v8a3.5 3.5 0 01-7 0V3H6v8a6 6 0 006 6zm-7 2v2h14v-2H5z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" id="btnS" onclick="exec('strikeThrough')" title="Strikethrough">
                    <svg viewBox="0 0 24 24"><path d="M7.24 8.75A4.22 4.22 0 017 7a5 5 0 0110 0h-2a3 3 0 00-6 0c0 .28.04.56.12.82L7.24 8.75zM16.98 11A5 5 0 0117 11.5 5 5 0 0112 16.5a5 5 0 01-4.88-4H5v-1.5h14V13h-2.02z"/></svg>
                  </button>

                  <div class="tb-sep"></div>

                  <button type="button" class="tb-color" title="Text color">
                    <span style="font-size:11px;font-weight:700;line-height:1">A</span>
                    <div class="cbar" id="fgBar" style="background:#0f172a"></div>
                    <input type="color" value="#0f172a"
                           oninput="document.getElementById('fgBar').style.background=this.value"
                           onchange="exec('foreColor',this.value)">
                  </button>

                  <button type="button" class="tb-color" title="Highlight color">
                    <svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor;pointer-events:none">
                      <path d="M16.56 8.94L7.62 0 6.21 1.41l2.38 2.38-5.15 5.15a1.49 1.49 0 000 2.12l5.5 5.5c.29.29.68.44 1.06.44s.77-.15 1.06-.44l5.5-5.5c.59-.58.59-1.53 0-2.12zM5.21 10L10 5.21 14.79 10H5.21zM19 11.5s-2 2.17-2 3.5c0 1.1.9 2 2 2s2-.9 2-2c0-1.33-2-3.5-2-3.5z"/>
                    </svg>
                    <div class="cbar" id="bgBar" style="background:#fef08a"></div>
                    <input type="color" value="#fef08a"
                           oninput="document.getElementById('bgBar').style.background=this.value"
                           onchange="exec('hiliteColor',this.value)">
                  </button>

                  <div class="tb-sep"></div>

                  <button type="button" class="tb-btn" onclick="exec('justifyLeft')"   title="Align left">
                    <svg viewBox="0 0 24 24"><path d="M3 3h18v2H3zm0 4h12v2H3zm0 4h18v2H3zm0 4h12v2H3zm0 4h18v2H3z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" onclick="exec('justifyCenter')" title="Center">
                    <svg viewBox="0 0 24 24"><path d="M3 3h18v2H3zm3 4h12v2H6zm-3 4h18v2H3zm3 4h12v2H6zm-3 4h18v2H3z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" onclick="exec('justifyRight')"  title="Align right">
                    <svg viewBox="0 0 24 24"><path d="M3 3h18v2H3zm6 4h12v2H9zm-6 4h18v2H3zm6 4h12v2H9zm-6 4h18v2H3z"/></svg>
                  </button>

                  <div class="tb-sep"></div>

                  <button type="button" class="tb-btn" onclick="exec('insertUnorderedList')" title="Bullet list">
                    <svg viewBox="0 0 24 24"><circle cx="4" cy="6" r="1.5"/><rect x="7" y="5" width="13" height="2" rx="1"/><circle cx="4" cy="12" r="1.5"/><rect x="7" y="11" width="13" height="2" rx="1"/><circle cx="4" cy="18" r="1.5"/><rect x="7" y="17" width="13" height="2" rx="1"/></svg>
                  </button>
                  <button type="button" class="tb-btn" onclick="exec('insertOrderedList')" title="Numbered list">
                    <svg viewBox="0 0 24 24"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-7v2h13V4H7zm0 14h13v-2H7v2zm0-6h13v-2H7v2z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" onclick="exec('formatBlock','blockquote')" title="Blockquote">
                    <svg viewBox="0 0 24 24"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
                  </button>

                  <div class="tb-sep"></div>

                  <button type="button" class="tb-btn" onclick="insertLink()" title="Insert link">
                    <svg viewBox="0 0 24 24"><path d="M3.9 12a4.1 4.1 0 014.1-4.1h4V6H8A6 6 0 008 18h4v-1.9H8A4.1 4.1 0 013.9 12zM8 13h8v-2H8v2zm8-7h-4v1.9h4a4.1 4.1 0 010 8.2h-4V18h4A6 6 0 0016 6z"/></svg>
                  </button>
                  <button type="button" class="tb-btn" onclick="exec('removeFormat')" title="Clear formatting">
                    <svg viewBox="0 0 24 24"><path d="M6 5v.18L8.82 8h2.4l-.72 1.68 2.1 2.1L14.21 8H20V5H6zm8 9.88c.04.3.06.62.06.97v.15H4v-3h2.95L14 14.88zM3.27 5L2 6.27l6.97 6.97L6.5 19h3l1.57-3.66L16.73 21 18 19.73 3.27 5z"/></svg>
                  </button>

                </div><!-- .toolbar -->

                <div id="editor" contenteditable="true"
                     data-placeholder="Write your email message here..."></div>
              </div>
              <textarea name="body" id="bodyHidden" style="display:none"></textarea>
            </div>

            <div class="field">
              <label>Attachments</label>
              <input type="file" name="attachments[]" multiple id="attachInput">
              <div class="file-tags" id="tagsAttach"></div>
            </div>

            <div class="action-row">
              <button type="submit" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                Send Email
              </button>
              <button type="button" class="btn btn-outline" id="discardBtn">Discard</button>
            </div>

          </form>

        </div>
      </div>

      <!-- History -->
      <?php if ($history): ?>
      <div class="card animate-in">
        <div class="card-header">
          <span class="card-title">Sent Emails</span>
          <span class="badge badge-blue"><?= $totalSent ?></span>
        </div>
        <div>
          <?php foreach ($history as $m):
            $initials = avatarChar($m['to']);
          ?>
          <div class="mail-item">
            <div class="mail-avatar"><?= $initials ?></div>
            <div class="mail-content">
              <div class="mail-row1">
                <span class="mail-to"><?= htmlspecialchars($m['to']) ?></span>
                <span class="mail-time"><?= htmlspecialchars($m['sent_at']) ?></span>
              </div>
              <div class="mail-subject"><?= htmlspecialchars($m['subject']) ?></div>
              <div class="mail-preview"><?= htmlspecialchars(mb_substr($m['body'], 0, 100)) ?>…</div>
            </div>
            <div style="margin-left:12px"><?= priorityLabel($m['priority'] ?? '3') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="card animate-in">
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <div class="empty-title">No emails sent yet</div>
          <div class="empty-desc">Your sent emails will appear here.</div>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- .page-body -->
  </div><!-- .main-content -->
</div><!-- .app-layout -->

<script>
const editor = document.getElementById('editor');

function exec(cmd, val) {
  document.execCommand(cmd, false, val ?? null);
  editor.focus(); syncActive();
}

function setFont(font) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  const span = document.createElement('span');
  span.style.fontFamily = font;
  try { range.surroundContents(span); } catch(e) { exec('fontName', font); }
}

function setFontSize(size) {
  exec('fontSize', '7');
  editor.querySelectorAll('font[size="7"]').forEach(el => {
    el.removeAttribute('size');
    el.style.fontSize = size + 'px';
  });
}

function insertLink() {
  const url = prompt('URL:', 'https://');
  if (url) exec('createLink', url);
}

function syncActive() {
  [['btnB','bold'],['btnI','italic'],['btnU','underline'],['btnS','strikeThrough']].forEach(([id,cmd]) => {
    const b = document.getElementById(id);
    if (b) b.classList.toggle('active', document.queryCommandState(cmd));
  });
}

editor.addEventListener('keyup', syncActive);
editor.addEventListener('mouseup', syncActive);
document.addEventListener('selectionchange', syncActive);

document.getElementById('mailForm').addEventListener('submit', function(e) {
  const to      = document.getElementById('toInput').value.trim();
  const subject = document.getElementById('subjectInput').value.trim();
  if (!to)             { e.preventDefault(); alert('Please enter recipient email.'); return; }
  if (!subject)        { e.preventDefault(); alert('Please enter a subject.'); return; }
  if (!editor.innerText.trim()) { e.preventDefault(); alert('Please write a message.'); return; }
  document.getElementById('bodyHidden').value = editor.innerHTML.trim();
});

document.getElementById('discardBtn').addEventListener('click', function() {
  if (confirm('Discard this message?')) {
    document.getElementById('mailForm').reset();
    editor.innerHTML = '';
    document.getElementById('tagsAttach').innerHTML = '';
  }
});

document.getElementById('attachInput').addEventListener('change', function() {
  const c = document.getElementById('tagsAttach');
  c.innerHTML = '';
  Array.from(this.files).forEach(f => {
    const t = document.createElement('span');
    t.className = 'file-tag';
    t.textContent = '📎 ' + f.name;
    c.appendChild(t);
  });
});

const fl = document.querySelector('.flash.success');
if (fl) setTimeout(() => { fl.style.transition = 'opacity .5s'; fl.style.opacity = '0'; }, 4000);
</script>
</body>
</html>
