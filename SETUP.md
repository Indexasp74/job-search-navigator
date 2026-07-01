# Setup Guide — Job Search Navigator

Detailed step-by-step instructions for installing and configuring the application.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation](#installation)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Initialization](#initialization)
6. [Optional: Email & Cron](#optional-email--cron)
7. [Optional: AI Features](#optional-ai-features)
8. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum
- PHP 8.0 or higher
- MySQL 5.7 or PostgreSQL 10+ (we use MySQL in examples)
- Web server (Apache, Nginx, etc.) with URL rewriting (optional but recommended)
- 10 MB disk space

### Recommended
- PHP 8.2+
- MySQL 8.0+
- HTTPS (Let's Encrypt)
- Dedicated database user with minimal privileges

### Check Your Environment

```bash
php -v
mysql --version
```

---

## Installation

### 1. Download/Clone

If you have git:
```bash
git clone https://github.com/yourusername/job-search-navigator.git
cd job-search-navigator/tracker
```

Otherwise, download the ZIP and extract.

### 2. File Permissions

Ensure the web server can read files:

```bash
chmod 755 tracker/
chmod 644 tracker/*.php
chmod 755 tracker/inc/ tracker/api/ tracker/setup/ tracker/cron/
```

### 3. Copy Configuration Template

```bash
cp .env.example .env
```

**IMPORTANT**: `.env` is in `.gitignore` — it will never be committed. This is where secrets live.

---

## Database Setup

### Create Database & User

**Via command line:**
```bash
mysql -u root -p
```

Then in MySQL:
```sql
CREATE DATABASE job_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tracker_user'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE ON job_tracker.* TO 'tracker_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Via phpMyAdmin** (if available on your host):
1. Create database: `job_tracker`
2. Create user: `tracker_user` with password
3. Grant all privileges on `job_tracker` to `tracker_user@localhost`

### Verify Connection

Test the connection locally (if you have MySQL client):
```bash
mysql -u tracker_user -p job_tracker -h localhost
```

---

## Configuration

### Edit .env

Open `.env` and fill in all values:

```bash
# Database credentials
DB_NAME=job_tracker
DB_USER=tracker_user
DB_PASS=YourSecurePassword123!
DATABASE_SERVER=localhost

# Generate strong random tokens (at least 40 characters)
# Use: openssl rand -base64 32
TRACKER_TOKEN=YourRandomTrackerToken123ABC...
INGEST_TOKEN=YourRandomIngestToken456DEF...

# Optional: Adzuna API (for role discovery)
ADZUNA_APP_ID=
ADZUNA_APP_KEY=

# Email for daily reports
REPORT_EMAIL=your-email@gmail.com

# Your app's public URL (used in email links)
SITE_URL=https://yourdomain.com/tracker
```

### Generate Strong Tokens

Use one of these to generate random strings:

**Linux/Mac:**
```bash
openssl rand -base64 32
```

**PHP (if SSH access available):**
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

**Online Generator**: [Random.org String Generator](https://www.random.org/strings/)

---

## Initialization

### 1. Run Database Migration

Visit this URL in your browser (replace TOKEN):
```
https://yourdomain.com/tracker/setup/migrate.php?token=YOUR_TRACKER_TOKEN
```

You should see:
```json
{
  "migrate": "done",
  "statements": 7,
  "results": [
    {"ok": true, "sql": "CREATE TABLE IF NOT EXISTS organizations..."},
    ...
  ]
}
```

If you get errors, check:
- Database credentials in `.env`
- Database user has CREATE TABLE privilege
- `.env` file is readable by the web server

### 2. Seed Example Data

Visit:
```
https://yourdomain.com/tracker/setup/seed.php?token=YOUR_TRACKER_TOKEN
```

You should see:
```json
{
  "seed": "done",
  "reset": false,
  "orgs": 5,
  "apps": 5
}
```

This loads 5 example applications to get you started. You can delete and replace them with your own data.

### 3. Access the Application

Navigate to:
```
https://yourdomain.com/tracker/
```

Enter your `TRACKER_TOKEN` and log in. You should see the 5 example applications.

---

## Optional: Email & Cron

### Email Setup

The app sends daily reports via `mail()`. Make sure your server's mail is configured:

**Test email sending** via SSH:
```bash
echo "Test" | mail -s "Test Subject" your-email@gmail.com
```

Check your spam/promotions folder — server-sent emails often end up there. You may need to whitelist the sender domain.

### Daily Cron Job

To automatically run the daily report generator, set up a scheduled task:

**On Hostinger (cPanel/hPanel):**
1. Go to Hosting → Cron Jobs
2. Command: 
   ```
   curl -s "https://yourdomain.com/tracker/cron/send-report.php?token=YOUR_TRACKER_TOKEN" > /dev/null
   ```
3. Schedule: Daily at 9 AM (or your preferred time)

**On Linux/VPS:**
```bash
# Edit crontab
crontab -e

# Add this line (9 AM daily):
0 9 * * * curl -s "https://yourdomain.com/tracker/cron/send-report.php?token=YOUR_TRACKER_TOKEN" > /dev/null
```

---

## Optional: AI Features

### Overview

The app can integrate with Claude AI for:
- **Discovery sweep** — Find new roles at target companies
- **Daily coaching** — Get insights on rejection patterns, market signals
- **Contact suggestions** — Find relevant LinkedIn titles to search

This requires:
1. Claude API key (paid)
2. Adzuna API key (free, 1000 calls/day)
3. Either Claude Code agents (subscription) or custom scripts

### Setup with Claude Code Agents (Recommended)

1. **Get Claude API access** at https://console.anthropic.com
2. **Get Adzuna API key** at https://developer.adzuna.com (free)
3. **Create agent definitions** in your local `~/.claude/agents/`:
   - `job-tracker-sweep.md` — Daily role discovery
   - `job-tracker-report.md` — Daily coaching report
   - `job-tracker-coaching.md` — On-demand coaching
   - `job-tracker-contacts.md` — Weekly contact suggestions

4. **Register cron triggers** in Claude Code CLI:
   ```bash
   /cron create --name "job-sweep" --schedule "0 20 * * *" --prompt "Run sweep agent..."
   ```

See `AGENTS.md` for detailed agent definitions and setup.

### Setup with Manual API Calls

If you prefer direct API calls instead of agents:

1. Set `ANTHROPIC_API_KEY` on your server (security risk — not recommended)
2. Modify `cron/sweep-adzuna.php` to also call Claude API
3. Handle token rotation and rate limiting yourself

**Not recommended** — agents are safer and cheaper.

---

## Troubleshooting

### "Access denied for user 'tracker_user'@'localhost'"

**Problem**: Database user doesn't have permission.

**Solution**:
```sql
GRANT ALL PRIVILEGES ON job_tracker.* TO 'tracker_user'@'localhost';
FLUSH PRIVILEGES;
```

### "Table already exists" during migrate

**Problem**: Schema already created, trying to create again.

**Solution**: This is fine! `CREATE TABLE IF NOT EXISTS` is idempotent. Run migrate again — it will succeed silently.

### Email not arriving

**Problem**: Daily reports aren't being emailed.

**Causes**:
- Server's `sendmail`/postfix not configured
- Email address is incorrect
- Emails going to spam folder
- Cron job not running

**Solutions**:
1. Check server mail log: `tail -50 /var/log/mail.log`
2. Test mail: `echo "Test" | mail -s "Test" your-email@example.com`
3. Check `REPORT_EMAIL` in `.env`
4. Whitelist sender domain in your email client
5. Verify cron job is running: `grep CRON /var/log/syslog`

### "Undefined function 'db()'" in error logs

**Problem**: Static PHP analysis showing false positive.

**Solution**: Ignore it. The function is defined in `inc/db.php` which is required at runtime. IDEs can't always follow include paths.

### Application is slow

**Problem**: Database queries taking time.

**Solutions**:
1. Check MySQL is running: `mysql -u root -p -e "SELECT 1;"`
2. Add indexes (see schema.sql for key definitions)
3. Ensure database user isn't rate-limited
4. Monitor disk space: `df -h`

### "Cannot connect to database" but credentials are correct

**Problem**: Database server unreachable.

**Troubleshooting**:
1. Verify hostname: Is it `localhost` or an IP address?
2. Check if MySQL is running: `systemctl status mysql`
3. Verify firewall isn't blocking: `telnet localhost 3306`
4. Check `.env` for typos

### Seed script says "reset: true" but I didn't ask for reset

**Problem**: You visited `seed.php?token=X&reset=1` accidentally.

**Solution**: This is safe — it just truncated the old data and reseeded. To keep your own data, manually INSERT into the database or modify `seed.php`.

---

## Next Steps

- **Add your applications** — Use the Applications tab to add your own job applications
- **Configure organizations** — Add target companies with career URLs
- **Set up AI features** — Follow [AGENTS.md](AGENTS.md) for coaching and discovery
- **Read [ARCHITECTURE.md](ARCHITECTURE.md)** — Understand the database schema and API

---

## Support

- Check README.md for quick reference
- Review ARCHITECTURE.md for technical details
- Search closed issues on GitHub
- Report bugs: GitHub Issues (include `.env` relevant parts, error logs, etc.)
