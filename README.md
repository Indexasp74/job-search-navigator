# Job Search Navigator

A full-stack web application for tracking job applications, discovering open roles, and getting AI-driven coaching on your job search strategy.

## Features

- **Application Tracker** — Organize all your job applications by status, fit rating, company, and role type
- **Organization Management** — Maintain records of companies you're targeting with links to career pages
- **Contact Research** — Track hiring managers and recruiters; get AI suggestions for LinkedIn search titles
- **Role Discovery** — Automatic sweep of career pages for matching roles at target organizations
- **Daily Briefing** — AI-generated coaching insights delivered to your inbox each morning
- **Interactive Coaching** — Ask data-backed questions about your pipeline, rejection patterns, and market signals
- **Dark Theme** — Purpose-built dark interface for focused work sessions

## Tech Stack

- **Backend**: PHP 8.0+ with PDO (no framework)
- **Database**: MySQL 5.7+
- **Frontend**: Vanilla JavaScript, dark-themed CSS
- **Design System**: Minotaur brand tokens (included)
- **AI Integration**: Claude API via scheduled agents (optional)

## Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer (optional, not required for this project)
- A web server (Apache, Nginx, etc.)

### 1. Clone and Setup

```bash
git clone https://github.com/yourusername/job-search-navigator.git
cd job-search-navigator/tracker
cp .env.example .env
```

### 2. Configure Database

Create a MySQL database and user:

```sql
CREATE DATABASE job_tracker;
CREATE USER 'tracker_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON job_tracker.* TO 'tracker_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configure .env

Edit `.env` with your settings:

```bash
DB_NAME=job_tracker
DB_USER=tracker_user
DB_PASS=strong_password
TRACKER_TOKEN=generate_a_random_string_here
INGEST_TOKEN=generate_another_random_string_here
REPORT_EMAIL=your-email@example.com
SITE_URL=https://yourdomain.com/tracker
```

### 4. Initialize Database

Visit `https://yourdomain.com/tracker/setup/migrate.php?token=YOUR_TRACKER_TOKEN` to create tables.

### 5. Seed Sample Data

Visit `https://yourdomain.com/tracker/setup/seed.php?token=YOUR_TRACKER_TOKEN` to load example applications.

### 6. Access the App

Navigate to `https://yourdomain.com/tracker/` and log in with `TRACKER_TOKEN`.

## Documentation

- **[SETUP.md](SETUP.md)** — Detailed installation and configuration guide
- **[ARCHITECTURE.md](ARCHITECTURE.md)** — System design, database schema, API reference
- **[AGENTS.md](AGENTS.md)** — Setting up Claude agents for automated coaching and discovery

## API Endpoints

All endpoints require `?token=TRACKER_TOKEN` in the query string.

### Applications
- `GET /api/applications.php` — List all applications
- `POST /api/applications.php` — Add new application
- `PUT /api/applications.php?id=N` — Update application
- `DELETE /api/applications.php?id=N` — Remove application

### Organizations
- `GET /api/organizations.php` — List organizations with counts
- `POST /api/organizations.php` — Add organization
- `PUT /api/organizations.php?id=N` — Update organization

### Contacts
- `GET /api/contacts.php` — List HR contacts
- `POST /api/contacts.php` — Add contact
- `DELETE /api/contacts.php?id=N` — Remove contact

### Discoveries
- `GET /api/discoveries.php` — List discovered roles
- `PATCH /api/discoveries.php?id=N` — Update role status

### Reports & Coaching
- `GET /api/report.php` — Get latest daily report
- `GET /api/coaching.php` — Get coaching responses
- `POST /api/coaching.php` — Submit coaching question

See [ARCHITECTURE.md](ARCHITECTURE.md) for full API documentation.

## Optional: AI-Driven Features

To enable automated role discovery, daily coaching, and contact suggestions:

1. Get an [Adzuna API key](https://developer.adzuna.com) (free tier: 1000 calls/day)
2. Set up Claude Code agents (requires Claude subscription)
3. Configure Hostinger cron job or your server's scheduler

See [AGENTS.md](AGENTS.md) for details.

## Project Structure

```
tracker/
├── index.php              # SPA shell + auth gate
├── inc/                   # Core utilities
│   ├── config.php         # Environment & constants
│   ├── db.php             # Database singleton
│   ├── auth.php           # Session & token validation
│   └── helpers.php        # Utility functions (esc, json_*)
├── api/                   # JSON API endpoints
│   ├── applications.php
│   ├── organizations.php
│   ├── contacts.php
│   ├── discoveries.php
│   ├── report.php
│   ├── coaching.php
│   └── ingest.php         # Authenticated write endpoint for agents
├── cron/                  # Scheduled tasks
│   ├── sweep-adzuna.php   # Daily job discovery sweep
│   └── send-report.php    # Email daily report
├── setup/                 # Installation scripts
│   ├── schema.sql         # Database schema
│   ├── migrate.php        # Run migrations
│   ├── seed.php           # Load example data
├── css/                   # Styling
│   └── tracker-dark.css   # Dark theme + components
└── .env.example           # Configuration template
```

## License

MIT License — See LICENSE file for details.

## Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/something`)
3. Commit with clear messages
4. Push and open a pull request

## Support

- **Issues**: Report bugs or request features via GitHub Issues
- **Docs**: Check SETUP.md and ARCHITECTURE.md first
- **Security**: Report security issues privately (don't use Issues)

---

Built with focus on data privacy — your job search stays on your server. No tracking, no analytics, no third parties beyond the APIs you explicitly configure.
