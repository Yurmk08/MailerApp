# 📋 MailerApp — Admin Checklist

## 🔧 Development Setup

- [ ] Clone repository
- [ ] Run `composer install`
- [ ] Copy `.env.example` to `.env` (local development)
- [ ] Update `config.php` with local SMTP and Telegram credentials
- [ ] Create `/data`, `/uploads`, `/logs` directories
- [ ] Set correct permissions: `chmod 755 data/ uploads/ logs/`
- [ ] Access app at `http://localhost/maileerer/index.php`
- [ ] Register first user (automatically becomes admin)

---

## 📦 Deployment Checklist

### Pre-Deployment
- [ ] Review all changes in git
- [ ] Run tests (if available)
- [ ] Check `.htaccess` files are in place
- [ ] Verify `.env` and `config.php` are in `.gitignore`
- [ ] Update `DEPLOYMENT.md` with actual domain name

### Server Setup
- [ ] PHP 8.0+ is installed with required extensions
- [ ] Apache/Nginx is configured (see `DEPLOYMENT.md`)
- [ ] SSL certificate is valid (HTTPS required)
- [ ] SSH key pairs are configured

### Application Setup
- [ ] Upload files to server (exclude `vendor/`, `composer.lock`, `.env`)
- [ ] Run `composer install` on server
- [ ] Create directories: `data/`, `uploads/`, `logs/`
- [ ] Set permissions correctly
- [ ] Update `config.php` with production credentials
- [ ] Test file uploads work
- [ ] Test email sending works

### Telegram Bot Setup
- [ ] Bot token added to `config.php`
- [ ] Chat ID verified with `@userinfobot`
- [ ] Webhook URL set via API
- [ ] Webhook verified with `getWebhookInfo`
- [ ] Test notifications sent to Telegram

### Security Verification
- [ ] All `.htaccess` files present and correct
- [ ] `data/` folder is NOT accessible via HTTP
- [ ] `config.php` is NOT accessible via HTTP
- [ ] HTTPS is enforced (HTTP redirects to HTTPS)
- [ ] Security headers are set (X-Frame-Options, etc.)
- [ ] PHP `display_errors` is OFF
- [ ] PHP `error_reporting` is configured
- [ ] Backup system is tested

### Performance Optimization
- [ ] Gzip compression enabled
- [ ] CSS/JS minified
- [ ] Database queries optimized
- [ ] Slow query logging enabled

---

## 🚨 Ongoing Maintenance

### Daily
- [ ] Monitor error logs
- [ ] Check Telegram notifications for failed emails
- [ ] Verify database backups completed

### Weekly
- [ ] Review event logs for suspicious activity
- [ ] Check disk usage (`data/`, `logs/` directories)
- [ ] Rotate old logs
- [ ] Test data export functionality

### Monthly
- [ ] Review user accounts (suspend inactive users)
- [ ] Check for failed login attempts
- [ ] Test disaster recovery procedure
- [ ] Update dependencies: `composer update`
- [ ] Review security advisories

### Quarterly
- [ ] Full security audit
- [ ] Performance analysis
- [ ] Capacity planning (disk, RAM, CPU)
- [ ] Update documentation

---

## 🔐 Security Hardening

- [ ] Update PHP to latest stable version
- [ ] Update all Composer packages
- [ ] Configure firewall rules (block non-HTTPS)
- [ ] Set up DDoS protection (Cloudflare, etc.)
- [ ] Enable rate limiting on login attempts
- [ ] Monitor failed login attempts
- [ ] Review user session logs
- [ ] Rotate SMTP password periodically
- [ ] Backup and securely store encryption keys
- [ ] Review file permissions regularly

---

## 📊 Monitoring & Alerts

### Set Up Alerts For:
- [ ] SMTP connection failures
- [ ] Telegram webhook failures
- [ ] Failed login attempts (5+ in 10 min)
- [ ] Disk space low (<10%)
- [ ] High error rate in logs
- [ ] New user registration (optional)

### Monitoring Tools:
- [ ] Check PHP error logs: `tail -f /var/log/php-fpm/error.log`
- [ ] Check web server logs: `tail -f /var/log/apache2/error.log`
- [ ] Monitor CPU/RAM: `htop` or `top`
- [ ] Check disk usage: `df -h`

---

## 🆘 Incident Response

### Email Not Sending
1. Check SMTP credentials in `config.php`
2. Check error logs: `logs/` directory
3. Test SMTP connection manually
4. Verify Gmail App Password is correct (not your regular password)
5. Check firewall: port 587 should be open

### Telegram Bot Not Responding
1. Verify bot token: `curl https://api.telegram.org/botTOKEN/getMe`
2. Check webhook: `curl https://api.telegram.org/botTOKEN/getWebhookInfo`
3. Review logs for webhook delivery errors
4. Test webhook manually with curl

### Users Can't Log In
1. Check if account is suspended: review `data/users.csv`
2. Check session management: clear `data/sessions.csv` if corrupted
3. Verify `.htaccess` is not blocking login page
4. Check PHP session configuration

### File Upload Issues
1. Check `/uploads` directory permissions: `chmod 777 uploads/`
2. Check available disk space: `df -h`
3. Check PHP upload limits in `php.ini`
4. Review error logs

---

## 🔄 Backup & Recovery

### Regular Backups
```bash
# Daily backup script
0 2 * * * /opt/backup_maileerer.sh
```

### Backup Structure
```
backup_maileerer_YYYYMMDD.tar.gz
├── data/         (CSV files - critical!)
├── logs/         (Event logs)
└── config.php    (Configuration)
```

### Recovery Procedure
1. Stop application
2. Extract backup: `tar -xzf backup_maileerer_YYYYMMDD.tar.gz`
3. Restore files to correct location
4. Set permissions: `chmod 755 data/ uploads/ logs/`
5. Restart application
6. Verify data integrity

---

## 📝 Change Log

| Date | Change | Status |
|------|--------|--------|
| 2026-04-29 | Initial deployment | ✅ Done |
| | | |

