# 🚀 MailerApp — Production Deployment Guide

## Prerequisites
- PHP 8.0+ with extensions: `openssl`, `mbstring`, `fileinfo`, `curl`
- Composer
- HTTPS certificate (required for Telegram webhook)
- SMTP server access (Gmail recommended)
- Telegram Bot Token (from @BotFather)

---

## Step 1: Server Setup

### 1.1 Clone/Upload Project
```bash
cd /var/www/html
git clone <your-repo> maileeer
cd maileeer
composer install
```

### 1.2 Directory Permissions
```bash
chmod 755 data/ uploads/ logs/
chmod 644 data/* uploads/* logs/* 2>/dev/null || true
chmod 755 vendor/
```

### 1.3 Nginx Configuration (if using Nginx)
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/html/maileeer;
    index index.php;

    # Protect sensitive files
    location ~ /config\.php$ { deny all; }
    location ~ /auth\.php$ { deny all; }
    location ~ /csv_helper\.php$ { deny all; }
    location ~ /sidebar\.php$ { deny all; }
    
    # Protect directories
    location ~ ^/(data|uploads|logs|vendor|src)/ { deny all; }
    
    # Disable directory listing
    autoindex off;

    # Route PHP requests
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://yourdomain.com$request_uri;
}
```

### 1.4 Apache Configuration (if using Apache)
- The `.htaccess` files are already configured
- Ensure `mod_rewrite` and `mod_headers` are enabled:
```bash
a2enmod rewrite headers
systemctl restart apache2
```

---

## Step 2: Configure Application

### 2.1 Edit config.php
```php
// SMTP settings
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'your-email@gmail.com');
define('SMTP_PASS',      'your-app-password');  // Not your actual password!
define('SMTP_FROM_NAME', 'MailerApp');

// Telegram Bot
define('TG_TOKEN',   'YOUR_BOT_TOKEN');
define('TG_CHAT_ID', 'YOUR_CHAT_ID');
```

### 2.2 Get Gmail App Password
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable 2-Step Verification (if not enabled)
3. Create App Password for "Mail"
4. Copy the 16-character password to `config.php`

### 2.3 Create Telegram Bot
1. Message `@BotFather` on Telegram
2. Type `/newbot` and follow instructions
3. Get your Chat ID: message `@userinfobot`
4. Add both to `config.php`

---

## Step 3: Set Telegram Webhook

```bash
# Replace YOUR_TOKEN and yourdomain.com
curl "https://api.telegram.org/botYOUR_TOKEN/setWebhook?url=https://yourdomain.com/telegram_bot.php"
```

Verify:
```bash
curl "https://api.telegram.org/botYOUR_TOKEN/getWebhookInfo"
```

---

## Step 4: First User Setup

1. Open `https://yourdomain.com/register.php` in browser
2. Create account - **first user automatically becomes admin**
3. Log in and start using the app

---

## Step 5: System Maintenance

### Backups
```bash
# Backup data directory weekly
tar -czf backup_data_$(date +%Y%m%d).tar.gz data/

# Backup everything (excluding vendor)
tar -czf backup_full_$(date +%Y%m%d).tar.gz --exclude=vendor --exclude=composer.lock .
```

### Logs Rotation
```bash
# Create cron job to rotate logs weekly
0 0 * * 0 /var/www/html/maileeer/scripts/rotate_logs.sh
```

### Monitor PHP Errors
```bash
tail -f /var/log/php-fpm/error.log
```

---

## Security Checklist

- ✅ HTTPS is enabled
- ✅ PHP `display_errors` is OFF in production
- ✅ Database/CSV files are not web-accessible (`.htaccess` protects them)
- ✅ `config.php` is protected by `.htaccess`
- ✅ Telegram bot token is kept secret
- ✅ Regular backups are automated
- ✅ Session cookies are secure (HttpOnly, Secure flag)
- ✅ File uploads are sanitized and deleted after sending
- ✅ All user input is validated and escaped

---

## Troubleshooting

### Issue: "SMTP Connection Failed"
- Check SMTP credentials in `config.php`
- Verify 2-Step Verification is enabled (Gmail)
- Check firewall rules (port 587 should be open)

### Issue: "Telegram bot not responding"
- Verify token is correct: `curl https://api.telegram.org/botTOKEN/getMe`
- Check webhook is set: `curl https://api.telegram.org/botTOKEN/getWebhookInfo`
- Review logs: `tail -f logs/*.log`

### Issue: "Permission denied" on data files
```bash
sudo chown -R www-data:www-data /var/www/html/maileeer/data
sudo chmod 755 /var/www/html/maileeer/data
```

### Issue: "Can't upload attachments"
```bash
sudo chmod 777 /var/www/html/maileerer/uploads
```

---

## Performance Tips

1. **Use Redis for Sessions** (instead of files)
   ```php
   // In config.php
   session_save_path('tcp://localhost:6379?database=1');
   ini_set('session.save_handler', 'redis');
   ```

2. **Enable Gzip Compression** (Apache/Nginx)
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript
   </IfModule>
   ```

3. **Optimize Images** in CSS (use WebP)

---

## Support

For issues, check:
- Application logs: `logs/`
- System logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- PHP logs: `/var/log/php-fpm/error.log`
