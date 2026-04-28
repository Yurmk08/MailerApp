<?php
// ════════════════════════════════════════════
//  config.php  –  Centralised configuration
// ════════════════════════════════════════════

// ── SMTP ─────────────────────────────────────
define('SMTP_HOST',      $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com');
define('SMTP_PORT',      (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587));
define('SMTP_USER',      $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?? '');
define('SMTP_PASS',      $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?? 'MailerApp');

// ── Telegram Bot ─────────────────────────────
define('TG_TOKEN',   $_ENV['TG_TOKEN'] ?? getenv('TG_TOKEN') ?? '');
define('TG_CHAT_ID', $_ENV['TG_CHAT_ID'] ?? getenv('TG_CHAT_ID') ?? '');

// ── Paths ─────────────────────────────────────
define('BASE_DIR',    __DIR__ . '/');
define('UPLOAD_DIR',  BASE_DIR . 'uploads/');
define('DATA_DIR',    BASE_DIR . 'data/');
define('LOGS_DIR',    BASE_DIR . 'logs/');

// CSV files
define('USERS_CSV',   DATA_DIR . 'users.csv');
define('MAILS_CSV',   DATA_DIR . 'mails.csv');
define('SESSIONS_CSV',DATA_DIR . 'sessions.csv');
define('EVENTS_CSV',  DATA_DIR . 'events.csv');

// ── Session ───────────────────────────────────
define('SESSION_LIFETIME', 86400);

// Ensure directories exist
foreach ([UPLOAD_DIR, DATA_DIR, LOGS_DIR] as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}
