<?php
// ════════════════════════════════════════════
//  telegram_bot_polling.php  –  Local testing
//
//  Запусти вручную: php telegram_bot_polling.php
//  Бот будет проверять сообщения каждую секунду
// ════════════════════════════════════════════

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csv_helper.php';

echo "[" . date('H:i:s') . "] 🤖 Telegram Bot started (polling mode)...\n";

$lastUpdateId = 0;

while (true) {
    try {
        $url = "https://api.telegram.org/bot" . TG_TOKEN . "/getUpdates";
        $params = ['offset' => $lastUpdateId + 1, 'timeout' => 30];

        $opts = ['http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/json',
            'content' => json_encode($params),
        ]];

        $response = @file_get_contents($url, false, stream_context_create($opts));
        $data = json_decode($response, true);

        if ($data['ok'] && !empty($data['result'])) {
            foreach ($data['result'] as $update) {
                $lastUpdateId = $update['update_id'];
                handleUpdate($update);
            }
        }

    } catch (Exception $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
    }

    sleep(1);
}

function handleUpdate(array $update): void {
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

    if (!$chatId) return;

    // ── Authorisation ─────────────────────────────
    $allowedChats = [TG_CHAT_ID];
    if (!in_array((string)$chatId, array_map('strval', $allowedChats))) {
        tgSend($chatId, "⛔ Access denied. This bot is private.");
        return;
    }

    // ── Route ─────────────────────────────────────
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

    echo "[" . date('H:i:s') . "] ✅ Action: $action\n";
}

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

// Импортируй все функции из telegram_bot.php
require_once __DIR__ . '/telegram_bot.php';
?>
