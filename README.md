# ✉️ MailerApp — PHP Mailer + Telegram Bot

A professional email sending web app with a full Telegram bot for monitoring and administration.

---

## 📁 File Structure

```
mailerapp/
├── config.php          ← All credentials & constants (edit this first!)
├── auth.php            ← Session & authentication helpers
├── csv_helper.php      ← Data layer: users, mails, events, TG notifications
├── style.css           ← Professional light-theme UI
│
├── login.php           ← Sign in page
├── register.php        ← Create account (first user = admin)
├── logout.php          ← End session
│
├── sidebar.php         ← Shared navigation sidebar
├── mailer.php          ← Main compose page (rich text editor)
├── send.php            ← POST handler – sends via PHPMailer
├── history.php         ← User's own sent mail history
│
├── dashboard.php       ← Admin: stats + charts + recent events
├── users.php           ← Admin: user management (suspend, change role)
├── all_mails.php       ← Admin: all emails with search + pagination
├── logs.php            ← Admin: full event log with filters
│
├── telegram_bot.php    ← Telegram webhook handler
├── composer.json       ← PHPMailer dependency
│
├── data/               ← CSV data files (auto-created)
│   ├── users.csv
│   ├── mails.csv
│   ├── sessions.csv
│   └── events.csv
└── uploads/            ← Temporary attachment storage
```

---

## 🚀 Setup

### 1. Install dependencies
```bash
composer install
```

### 2. Edit `config.php`
```php
define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'your@gmail.com');
define('SMTP_PASS',   'your_app_password');  // Gmail App Password
define('SMTP_FROM_NAME', 'MailerApp');

define('TG_TOKEN',   '1234567890:ABCDEFghijklmnop...');  // Your bot token
define('TG_CHAT_ID', '123456789');                        // Your Telegram user/chat ID
```

### 3. Get Gmail App Password
- Google Account → Security → 2-Step Verification → App Passwords
- Generate for "Mail" → use the 16-char password

### 4. Create Telegram Bot
- Message `@BotFather` → `/newbot`
- Copy the token to `config.php`
- Get your Chat ID: message `@userinfobot`

### 5. Set Webhook
```
https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook?url=https://yourdomain.com/telegram_bot.php
```

### 6. Directory permissions
```bash
chmod 755 data/ uploads/
```

### 7. First user
- Visit `/register.php`
- The **first registered user automatically becomes admin**

---

## 🤖 Telegram Bot Commands

| Command | Action |
|---------|--------|
| `/start` | Main menu with inline buttons |
| `/stats` | System statistics + 7-day chart |
| `/users` | List all users with details |
| `/mails` | Paginated sent emails |
| `/logs`  | Event logs with type filters |
| `/help`  | Command reference |

### Inline Button Menu
- 📊 Stats
- 👥 Users
- 📧 Sent Emails (paginated)
- 📝 Event Logs (filterable by type)
- 🔓 Login Logs
- ⚠️ Failed Logins
- 📥 Export Users CSV
- 📥 Export Emails CSV

### Auto-notifications (sent automatically)
- 👤 New user registered
- 📧 Email sent (with full details)
- 🔓/🔒 User login / logout
- ⚠️ Failed login attempt

---

## 🔒 Security Notes

- Passwords hashed with `password_hash(BCRYPT)`
- Session regenerated on login
- File uploads sanitised and deleted after sending
- Email addresses validated with `FILTER_VALIDATE_EMAIL`
- All HTML output escaped with `htmlspecialchars()`
- TG bot only responds to whitelisted `TG_CHAT_ID`
- Suspended users cannot log in
- `.htaccess` recommended to protect `data/` folder:

```apache
# data/.htaccess
Deny from all
```

---

## 📊 Features

**Web App**
- Rich text email composer (bold, italic, colors, lists, links, etc.)
- Multi-recipient support (comma-separated)
- File attachments
- Priority levels (High / Normal / Low)
- Sent mail history per user
- Admin dashboard with stats and 7-day chart
- User management (suspend, promote to admin)
- Event log with filters and pagination
- Flash messages for all actions

**Telegram Bot**
- Real-time notifications for every key action
- Full user roster with login counts
- Email log with pagination
- Event log with 7 filter types
- CSV export for users and emails
- Inline keyboard navigation

---

## ⚙️ Requirements

- PHP 8.0+
- Composer
- PHP extensions: `openssl`, `mbstring`, `fileinfo`
- SMTP access (Gmail App Password recommended)
- HTTPS required for Telegram webhook
