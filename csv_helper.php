<?php
// ════════════════════════════════════════════
//  csv_helper.php  –  Data layer (CSV-based)
// ════════════════════════════════════════════
require_once __DIR__ . '/boot.php';
require_once __DIR__ . '/config.php';

// ── Generic CSV helpers ───────────────────────

function csvRead(string $file): array {
    if (!file_exists($file)) return [];
    $rows = [];
    if (($fh = fopen($file, 'r')) === false) return [];
    $headers = fgetcsv($fh);
    if (!$headers) { fclose($fh); return []; }
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) === count($headers))
            $rows[] = array_combine($headers, $row);
    }
    fclose($fh);
    return $rows;
}

function csvWrite(string $file, array $rows, array $headers): void {
    $fh = fopen($file, 'w');
    fputcsv($fh, $headers);
    foreach ($rows as $r) fputcsv($fh, array_values($r));
    fclose($fh);
}

function csvAppend(string $file, array $row, array $headers): void {
    $new = !file_exists($file) || filesize($file) === 0;
    $fh  = fopen($file, 'a');
    if ($new) fputcsv($fh, $headers);
    fputcsv($fh, array_values($row));
    fclose($fh);
}

// ── Users ─────────────────────────────────────

function allUsers(): array { return csvRead(USERS_CSV); }

function findUserByUsername(string $u): ?array {
    foreach (allUsers() as $r) if ($r['username'] === $u) return $r;
    return null;
}

function findUserById(string $id): ?array {
    foreach (allUsers() as $r) if ($r['id'] === $id) return $r;
    return null;
}

function createUser(string $username, string $password, string $email, string $role = 'user'): array {
    $user = [
        'id'         => uniqid('u_', true),
        'username'   => $username,
        'email'      => $email,
        'password'   => password_hash($password, PASSWORD_BCRYPT),
        'role'       => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => '',
        'login_count'=> '0',
        'status'     => 'active',
    ];
    $headers = ['id','username','email','password','role','created_at','last_login','login_count','status'];
    csvAppend(USERS_CSV, $user, $headers);
    tgNotify("👤 *Новый пользователь зарегистрирован*\n\nИмя: `{$username}`\nEmail: `{$email}`\nРоль: `{$role}`\nВремя: `" . date('Y-m-d H:i:s') . "`");
    logEvent('register', $user['id'], "Registered: {$username} ({$email})");
    return $user;
}

function updateUserLastLogin(string $id): void {
    $rows = allUsers();
    $headers = ['id','username','email','password','role','created_at','last_login','login_count','status'];
    foreach ($rows as &$r) {
        if ($r['id'] === $id) {
            $r['last_login']  = date('Y-m-d H:i:s');
            $r['login_count'] = (int)($r['login_count'] ?? 0) + 1;
        }
    }
    csvWrite(USERS_CSV, $rows, $headers);
}

function usersCount(): int { return count(allUsers()); }

// ── Mails ─────────────────────────────────────

$MAIL_HEADERS = ['id','user_id','from_email','to','subject','body','priority','attachments','sent_at','status'];

function mailLog(string $userId, string $to, string $subject, string $body, string $priority = '3', string $attachments = ''): void {
    global $MAIL_HEADERS;
    $row = [
        'id'          => uniqid('m_', true),
        'user_id'     => $userId,
        'from_email'  => SMTP_USER,
        'to'          => $to,
        'subject'     => $subject,
        'body'        => mb_substr($body, 0, 500),
        'priority'    => $priority,
        'attachments' => $attachments,
        'sent_at'     => date('Y-m-d H:i:s'),
        'status'      => 'sent',
    ];
    csvAppend(MAILS_CSV, $row, $MAIL_HEADERS);

    $user = findUserById($userId);
    $uname = $user ? $user['username'] : $userId;
    $priLabel = ['1'=>'🔴 Высокий','3'=>'🟡 Обычный','5'=>'🟢 Низкий'][$priority] ?? '🟡 Обычный';
    tgNotify(
        "📧 *Письмо отправлено*\n\n" .
        "👤 От: `{$uname}`\n" .
        "📬 Кому: `{$to}`\n" .
        "📌 Тема: `{$subject}`\n" .
        "⚡ Приоритет: {$priLabel}\n" .
        "📝 Текст: `" . mb_substr(strip_tags($body), 0, 120) . "…`\n" .
        "🕐 Время: `" . date('Y-m-d H:i:s') . "`"
    );
    logEvent('mail_sent', $userId, "To: {$to} | Subject: {$subject}");
}

function mailsForUser(string $userId): array {
    return array_filter(csvRead(MAILS_CSV), fn($r) => $r['user_id'] === $userId);
}

function allMails(): array { return csvRead(MAILS_CSV); }

function mailsCount(): int { return count(allMails()); }

// ── Events log ───────────────────────────────

function logEvent(string $type, string $userId, string $detail, string $ip = ''): void {
    $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    csvAppend(EVENTS_CSV, [
        'id'        => uniqid('e_', true),
        'type'      => $type,
        'user_id'   => $userId,
        'detail'    => $detail,
        'ip'        => $ip,
        'ua'        => mb_substr($ua, 0, 200),
        'created_at'=> date('Y-m-d H:i:s'),
    ], ['id','type','user_id','detail','ip','ua','created_at']);
}

function allEvents(int $limit = 100): array {
    $rows = csvRead(EVENTS_CSV);
    return array_slice(array_reverse($rows), 0, $limit);
}

// ── Sessions log ─────────────────────────────

function logSession(string $userId, string $action): void {
    csvAppend(SESSIONS_CSV, [
        'id'         => uniqid('s_', true),
        'user_id'    => $userId,
        'action'     => $action,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua'         => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
        'created_at' => date('Y-m-d H:i:s'),
    ], ['id','user_id','action','ip','ua','created_at']);

    $user = findUserById($userId);
    $uname = $user ? $user['username'] : $userId;
    $emoji = $action === 'login' ? '🔓' : '🔒';
    tgNotify("{$emoji} *Сессия: {$action}*\n\nПользователь: `{$uname}`\nIP: `" . ($_SERVER['REMOTE_ADDR'] ?? 'n/a') . "`\nВремя: `" . date('Y-m-d H:i:s') . "`");
    logEvent($action, $userId, "Session {$action}");
}

// ── Flash messages ────────────────────────────

function setFlash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): array {
    $f = $_SESSION['flash'] ?? ['msg' => '', 'type' => ''];
    unset($_SESSION['flash']);
    return $f;
}

// ── Telegram ──────────────────────────────────

function tgNotify(string $text): void {
    if (!TG_TOKEN || !TG_CHAT_ID || TG_TOKEN === 'YOUR_BOT_TOKEN') return;
    $url  = "https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage";
    $data = ['chat_id' => TG_CHAT_ID, 'text' => $text, 'parse_mode' => 'Markdown'];
    @file_get_contents($url . '?' . http_build_query($data));
}

function tgSend(string $chatId, string $text, array $keyboard = [], string $parseMode = 'Markdown'): void {
    if (!TG_TOKEN) return;
    $url  = "https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage";
    $payload = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => $parseMode,
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
    }
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => json_encode($payload),
    ]];
    @file_get_contents($url, false, stream_context_create($opts));
}
