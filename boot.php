<?php
/**
 * boot.php - Initialize app on Railway
 * Runs before every request to ensure config exists
 */

// If config doesn't exist, create it from environment variables
if (!file_exists(__DIR__ . '/config.php')) {
    $config = <<<'PHP'
<?php
// Auto-generated from environment variables
define('SMTP_HOST',      $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT',      (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER',      $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS',      $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'MailerApp');

define('TG_TOKEN',   $_ENV['TG_TOKEN'] ?? '');
define('TG_CHAT_ID', $_ENV['TG_CHAT_ID'] ?? '');

define('BASE_DIR',    __DIR__ . '/');
define('UPLOAD_DIR',  BASE_DIR . 'uploads/');
define('DATA_DIR',    BASE_DIR . 'data/');
define('LOGS_DIR',    BASE_DIR . 'logs/');

define('USERS_CSV',   DATA_DIR . 'users.csv');
define('MAILS_CSV',   DATA_DIR . 'mails.csv');
define('SESSIONS_CSV',DATA_DIR . 'sessions.csv');
define('EVENTS_CSV',  DATA_DIR . 'events.csv');

define('SESSION_LIFETIME', 86400);

foreach ([UPLOAD_DIR, DATA_DIR, LOGS_DIR] as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}
PHP;

    file_put_contents(__DIR__ . '/config.php', $config);
}

// Load .env if it exists (for local development)
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '\'"');
        }
    }
}
?>
