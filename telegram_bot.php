<?php
// ════════════════════════════════════════════════════════════════
//  telegram_bot.php  –  Webhook handler for MailerApp Telegram Bot
//  
//  Commands:
//    /start   – main menu
//    /users   – registered users
//    /mails   – recent sent emails
//    /logs    – recent event logs
//    /stats   – dashboard stats
//    /help    – command list
//
//  Set webhook:
//    https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.com/telegram_bot.php
// ════════════════════════════════════════════════════════════════

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csv_helper.php';

// ── Security: only process POST from Telegram ────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo 'MailerApp Bot OK';
    exit;
}

$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) { http_response_code(200); exit; }

// ── Extract chat / message / callback ────────────────────────────
$chatId   = null;
$text     = '';
$callData = '';
$msgId    = null;

if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text   = trim($update['message']['text'] ?? '');
    $msgId  = $update['message']['message_id'];
} elseif (isset($update['callback_query'])) {
    $chatId   = $update['callback_query']['message']['chat']['id'];
    $callData = $update['callback_query']['data'] ?? '';
    $msgId    = $update['callback_query']['message']['message_id'];
    answerCallback($update['callback_query']['id']);
}

if (!$chatId) { http_response_code(200); exit; }

// ── Authorisation: only allowed chat IDs ─────────────────────────
$allowedChats = [TG_CHAT_ID]; // add more: [TG_CHAT_ID, '123456789']
if (!in_array((string)$chatId, array_map('strval', $allowedChats))) {
    tgSend($chatId, "⛔ Access denied. This bot is private.");
    http_response_code(200);
    exit;
}

// ── Route ─────────────────────────────────────────────────────────
$action = $text ?: $callData;

match(true) {
    str_starts_with($action, '/start') || $action === 'menu'
        => sendMenu($chatId),
    str_starts_with($action, '/stats') || $action === 'stats'
        => sendStats($chatId),
    str_starts_with($action, '/users') || $action === 'users'
        => sendUsers($chatId),
    str_starts_with($action, '/mails') || $action === 'mails'
        => sendMails($chatId, 1),
    str_starts_with($action, 'mails_p_')
        => sendMails($chatId, (int)substr($action, 8)),
    str_starts_with($action, '/logs')  || $action === 'logs'
        => sendLogs($chatId, 'all'),
    str_starts_with($action, 'logs_')
        => sendLogs($chatId, substr($action, 5)),
    str_starts_with($action, '/help')  || $action === 'help'
        => sendHelp($chatId),
    $action === 'export_users'
        => exportUsers($chatId),
    $action === 'export_mails'
        => exportMails($chatId),
    default
        => tgSend($chatId, "❓ Unknown command. Type /help or use the menu.", mainKeyboard()),
};

http_response_code(200);

// ════════════════════════════════════════════════════════════════
//  Bot response functions
// ════════════════════════════════════════════════════════════════

function sendMenu(int|string $chatId): void {
    $text = "✉️ *MailerApp Control Panel*\n\n"
          . "Welcome\\! Choose an action below\\.";
    tgSend($chatId, $text, mainKeyboard(), 'MarkdownV2');
}

function mainKeyboard(): array {
    return [
        [
            ['text' => '📊 Stats',       'callback_data' => 'stats'],
            ['text' => '👥 Users',       'callback_data' => 'users'],
        ],
        [
            ['text' => '📧 Sent Emails', 'callback_data' => 'mails'],
            ['text' => '📝 Event Logs',  'callback_data' => 'logs_all'],
        ],
        [
            ['text' => '🔓 Login Logs',  'callback_data' => 'logs_login'],
            ['text' => '⚠️ Failed Logins','callback_data' => 'logs_failed_login'],
        ],
        [
            ['text' => '📥 Export Users CSV',  'callback_data' => 'export_users'],
            ['text' => '📥 Export Mails CSV',  'callback_data' => 'export_mails'],
        ],
        [
            ['text' => '❓ Help',        'callback_data' => 'help'],
        ],
    ];
}

function backButton(): array {
    return [[['text' => '⬅️ Main Menu', 'callback_data' => 'menu']]];
}

function sendStats(int|string $chatId): void {
    $users  = allUsers();
    $mails  = allMails();
    $events = csvRead(EVENTS_CSV);

    $today     = date('Y-m-d');
    $todayMails= count(array_filter($mails,  fn($m) => str_starts_with($m['sent_at'],   $today)));
    $todayLogins=count(array_filter($events, fn($e) => str_starts_with($e['created_at'],$today) && $e['type'] === 'login'));
    $todayRegs = count(array_filter($events, fn($e) => str_starts_with($e['created_at'],$today) && $e['type'] === 'register'));
    $activeUsers= count(array_filter($users, fn($u) => $u['status'] === 'active'));
    $adminCount = count(array_filter($users, fn($u) => $u['role']   === 'admin'));

    // Last 7 days emails
    $week = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('d/m', strtotime("-{$i} days"));
        $cnt = count(array_filter($mails, fn($m) => str_starts_with($m['sent_at'], date('Y-m-d', strtotime("-{$i} days")))));
        $bar = str_repeat('█', min($cnt, 10)) ?: '░';
        $week[] = "`{$d}` {$bar} {$cnt}";
    }

    $text = "📊 *Dashboard Statistics*\n"
          . "🗓 " . date('Y-m-d H:i') . "\n\n"
          . "👥 *Users*\n"
          . "  Total: `" . count($users) . "`\n"
          . "  Active: `{$activeUsers}`\n"
          . "  Admins: `{$adminCount}`\n"
          . "  New today: `{$todayRegs}`\n\n"
          . "📧 *Emails*\n"
          . "  Total sent: `" . count($mails) . "`\n"
          . "  Sent today: `{$todayMails}`\n\n"
          . "⚡ *Activity Today*\n"
          . "  Logins: `{$todayLogins}`\n"
          . "  Events: `" . count(array_filter($events, fn($e) => str_starts_with($e['created_at'], $today))) . "`\n\n"
          . "📈 *Emails — Last 7 Days*\n"
          . implode("\n", $week);

    tgSend($chatId, $text, backButton());
}

function sendUsers(int|string $chatId): void {
    $users = allUsers();
    $mails = allMails();

    $mailCount = [];
    foreach ($mails as $m) $mailCount[$m['user_id']] = ($mailCount[$m['user_id']] ?? 0) + 1;

    $text = "👥 *Registered Users* (" . count($users) . " total)\n\n";

    foreach ($users as $i => $u) {
        $status  = $u['status'] === 'active' ? '🟢' : '🔴';
        $role    = $u['role'] === 'admin'    ? '⭐' : '👤';
        $cnt     = $mailCount[$u['id']] ?? 0;
        $login   = $u['last_login'] ?: 'never';
        $text   .= "{$role} {$status} *" . escMd($u['username']) . "*\n"
                .  "  📧 " . escMd($u['email']) . "\n"
                .  "  📤 Sent: `{$cnt}` · Logins: `" . ($u['login_count'] ?? 0) . "`\n"
                .  "  🕐 Last login: `" . escMd($login) . "`\n"
                .  "  📅 Registered: `" . escMd($u['created_at']) . "`\n\n";
    }

    $keyboard = array_merge(
        [[['text' => '📥 Export CSV', 'callback_data' => 'export_users']]],
        backButton()
    );

    tgSend($chatId, $text, $keyboard);
}

function sendMails(int|string $chatId, int $page = 1): void {
    $perPage = 5;
    $mails   = array_reverse(allMails());
    $total   = count($mails);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = min($page, $pages);
    $slice   = array_slice($mails, ($page - 1) * $perPage, $perPage);

    $text = "📧 *Sent Emails* (page {$page}/{$pages}, total {$total})\n\n";

    foreach ($slice as $m) {
        $user  = findUserById($m['user_id']);
        $uname = $user ? $user['username'] : '?';
        $pri   = ['1'=>'🔴','3'=>'🟡','5'=>'🟢'][$m['priority']??'3'] ?? '🟡';
        $text .= $pri . " *" . escMd($m['subject']) . "*\n"
               . "  👤 " . escMd($uname) . " → " . escMd($m['to']) . "\n"
               . "  📝 " . escMd(mb_substr(strip_tags($m['body']), 0, 80)) . "…\n"
               . "  🕐 `" . escMd($m['sent_at']) . "`\n"
               . ($m['attachments'] ? "  📎 " . escMd($m['attachments']) . "\n" : "")
               . "\n";
    }

    // Pagination keyboard
    $pager = [];
    if ($page > 1) $pager[] = ['text' => '⬅️ Prev', 'callback_data' => 'mails_p_' . ($page - 1)];
    $pager[] = ['text' => "· {$page}/{$pages} ·", 'callback_data' => 'mails'];
    if ($page < $pages) $pager[] = ['text' => 'Next ➡️', 'callback_data' => 'mails_p_' . ($page + 1)];

    $keyboard = array_merge(
        [$pager],
        [[['text' => '📥 Export CSV', 'callback_data' => 'export_mails']]],
        backButton()
    );

    tgSend($chatId, $text, $keyboard);
}

function sendLogs(int|string $chatId, string $filter = 'all'): void {
    $events = array_slice(array_reverse(csvRead(EVENTS_CSV)), 0, 200);

    if ($filter !== 'all') {
        $events = array_values(array_filter($events, fn($e) => $e['type'] === $filter));
    }

    $slice = array_slice($events, 0, 15);
    $total = count($events);

    $labels = [
        'all'          => 'All Events',
        'login'        => 'Logins',
        'logout'       => 'Logouts',
        'register'     => 'Registrations',
        'mail_sent'    => 'Emails Sent',
        'failed_login' => 'Failed Logins',
        'mail_error'   => 'Mail Errors',
    ];

    $text = "📝 *Event Logs — " . ($labels[$filter] ?? $filter) . "* (showing " . count($slice) . " of {$total})\n\n";

    $typeEmoji = [
        'login'        => '🔓', 'logout' => '🔒',
        'register'     => '👤', 'mail_sent' => '📧',
        'mail_error'   => '❌', 'failed_login' => '⚠️',
        'admin_toggle' => '⚙️', 'admin_role' => '🔑',
    ];

    foreach ($slice as $ev) {
        $user  = $ev['user_id'] ? findUserById($ev['user_id']) : null;
        $uname = $user ? $user['username'] : ($ev['user_id'] ?: 'system');
        $emoji = $typeEmoji[$ev['type']] ?? '•';
        $text .= "{$emoji} `" . escMd($ev['created_at']) . "` — *" . escMd($uname) . "*\n"
               . "  " . escMd($ev['detail']) . "\n"
               . "  🌐 `" . escMd($ev['ip']) . "`\n\n";
    }

    $filterKeyboard = [
        [
            ['text' => '📋 All',      'callback_data' => 'logs_all'],
            ['text' => '🔓 Logins',   'callback_data' => 'logs_login'],
            ['text' => '🔒 Logouts',  'callback_data' => 'logs_logout'],
        ],
        [
            ['text' => '👤 Registers','callback_data' => 'logs_register'],
            ['text' => '📧 Emails',   'callback_data' => 'logs_mail_sent'],
            ['text' => '⚠️ Failed',   'callback_data' => 'logs_failed_login'],
        ],
    ];

    $keyboard = array_merge($filterKeyboard, backButton());
    tgSend($chatId, $text, $keyboard);
}

function sendHelp(int|string $chatId): void {
    $text = "❓ *MailerApp Bot — Commands*\n\n"
          . "`/start` — Main menu\n"
          . "`/stats` — System statistics\n"
          . "`/users` — List all users\n"
          . "`/mails` — Recent sent emails\n"
          . "`/logs`  — Event log (all types)\n"
          . "`/help`  — This help message\n\n"
          . "You can also use the *inline buttons* in the menu\\.\n\n"
          . "📌 *Notifications*\n"
          . "This bot automatically sends alerts for:\n"
          . "  • New user registrations 👤\n"
          . "  • Email sent 📧\n"
          . "  • Login / logout events 🔓\n";

    tgSend($chatId, $text, backButton(), 'MarkdownV2');
}

function exportUsers(int|string $chatId): void {
    $users = allUsers();
    if (!$users) {
        tgSend($chatId, "📭 No users to export.", backButton());
        return;
    }

    $tmp = sys_get_temp_dir() . '/mailerapp_users_' . time() . '.csv';
    $fh  = fopen($tmp, 'w');
    fputcsv($fh, ['id','username','email','role','status','created_at','last_login','login_count']);
    foreach ($users as $u) {
        fputcsv($fh, [$u['id'],$u['username'],$u['email'],$u['role'],$u['status'],$u['created_at'],$u['last_login'],$u['login_count']??0]);
    }
    fclose($fh);

    sendDocument($chatId, $tmp, 'users_export_' . date('Ymd_Hi') . '.csv', '📥 Users CSV export');
    unlink($tmp);
}

function exportMails(int|string $chatId): void {
    $mails = allMails();
    if (!$mails) {
        tgSend($chatId, "📭 No emails to export.", backButton());
        return;
    }

    $tmp = sys_get_temp_dir() . '/mailerapp_mails_' . time() . '.csv';
    $fh  = fopen($tmp, 'w');
    fputcsv($fh, ['id','user_id','from','to','subject','body','priority','attachments','sent_at','status']);
    foreach ($mails as $m) {
        fputcsv($fh, [$m['id'],$m['user_id'],$m['from_email'],$m['to'],$m['subject'],mb_substr($m['body'],0,200),$m['priority']??'3',$m['attachments']??'',$m['sent_at'],$m['status']]);
    }
    fclose($fh);

    sendDocument($chatId, $tmp, 'emails_export_' . date('Ymd_Hi') . '.csv', '📥 Emails CSV export');
    unlink($tmp);
}

// ── Telegram API helpers ──────────────────────────────────────────

function answerCallback(string $callbackId): void {
    $url = "https://api.telegram.org/bot" . TG_TOKEN . "/answerCallbackQuery";
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => json_encode(['callback_query_id' => $callbackId]),
    ]];
    @file_get_contents($url, false, stream_context_create($opts));
}

function sendDocument(int|string $chatId, string $filePath, string $fileName, string $caption = ''): void {
    $url     = "https://api.telegram.org/bot" . TG_TOKEN . "/sendDocument";
    $boundary = '----BotBoundary' . uniqid();
    $body    = "--{$boundary}\r\n"
             . "Content-Disposition: form-data; name=\"chat_id\"\r\n\r\n{$chatId}\r\n"
             . "--{$boundary}\r\n"
             . "Content-Disposition: form-data; name=\"caption\"\r\n\r\n{$caption}\r\n"
             . "--{$boundary}\r\n"
             . "Content-Disposition: form-data; name=\"document\"; filename=\"{$fileName}\"\r\n"
             . "Content-Type: text/csv\r\n\r\n"
             . file_get_contents($filePath) . "\r\n"
             . "--{$boundary}--\r\n";

    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: multipart/form-data; boundary={$boundary}",
        'content' => $body,
    ]];
    @file_get_contents($url, false, stream_context_create($opts));
}

/** Escape MarkdownV2 special chars */
function escMd(string $s): string {
    return preg_replace('/([_*\[\]()~`>#+\-=|{}.!\\\\])/', '\\\\$1', $s);
}
