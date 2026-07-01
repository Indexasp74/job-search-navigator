# Architecture — Job Search Navigator

System design, database schema, and API reference.

## System Overview

```
┌─────────────────────────────────────────────────────────────┐
│ Browser (SPA)                                               │
│ - index.php (auth gate + tab interface)                     │
│ - Vanilla JS (fetch-based API calls)                        │
│ - Dark theme CSS                                            │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP/JSON
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ PHP API Layer                                               │
│ - api/applications.php (CRUD + filters)                     │
│ - api/organizations.php (list + counts)                     │
│ - api/contacts.php (HR contacts)                            │
│ - api/discoveries.php (discovered roles)                    │
│ - api/report.php (daily coaching brief)                     │
│ - api/coaching.php (coaching queue)                         │
│ - api/ingest.php (write endpoint for agents)                │
└──────────────────────┬──────────────────────────────────────┘
                       │ PDO
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ MySQL Database                                              │
│ - organizations (target companies)                          │
│ - applications (job applications)                           │
│ - contacts (HR/hiring manager info)                         │
│ - role_discoveries (found open roles)                       │
│ - daily_reports (coaching insights)                         │
│ - coaching_sessions (Q&A history)                           │
│ - contact_suggestions (LinkedIn titles)                     │
└─────────────────────────────────────────────────────────────┘

Optional: Claude Code Agents (run on subscription, write via ingest.php)
├── job-tracker-sweep — nightly career page search
├── job-tracker-report — daily coaching generation
├── job-tracker-coaching — on-demand Q&A
└── job-tracker-contacts — weekly contact suggestions
```

---

## Database Schema

### organizations

Tracks companies you're targeting.

```sql
CREATE TABLE organizations (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(255) NOT NULL UNIQUE,
  domain          VARCHAR(255),
  size_range      VARCHAR(50),              -- e.g. '50-200', '1000+'
  industry        VARCHAR(100),
  hq_location     VARCHAR(150),
  glassdoor_url   VARCHAR(500),
  linkedin_url    VARCHAR(500),
  careers_url     VARCHAR(500),             -- used by discovery sweep
  fit_rating      ENUM('high','med','low') DEFAULT 'med',
  notes           TEXT,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_name (name)
);
```

**Indices**: PRIMARY KEY (id), UNIQUE (name), fit_rating

### applications

Each job application with status and notes.

```sql
CREATE TABLE applications (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id          INT UNSIGNED,
  role_title      VARCHAR(255) NOT NULL,
  date_applied    DATE,
  status          ENUM('active','screen','interview','offer','no','canceled','hold') DEFAULT 'active',
  fit             ENUM('high','med','low') DEFAULT 'med',
  resume_file     VARCHAR(255),             -- e.g. 'resume_acme.pdf'
  source_url      VARCHAR(1000),            -- link to job posting
  job_description TEXT,
  salary_min      INT UNSIGNED,
  salary_max      INT UNSIGNED,
  location        VARCHAR(150),
  remote_ok       TINYINT(1) DEFAULT 0,
  notes           TEXT,
  is_local        TINYINT(1) DEFAULT 0,     -- local opportunity?
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
);
```

**Indices**: PRIMARY KEY (id), org_id, status, fit, date_applied, created_at

### contacts

HR/hiring manager records for organizations.

```sql
CREATE TABLE contacts (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id            INT UNSIGNED NOT NULL,
  name              VARCHAR(255) NOT NULL,
  title             VARCHAR(255),           -- 'Recruiting Manager', etc.
  email             VARCHAR(255),
  linkedin_url      VARCHAR(500),
  is_hiring_manager TINYINT(1) DEFAULT 0,   -- vs generic HR
  source            VARCHAR(100),           -- 'linkedin', 'referral', etc.
  notes             TEXT,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);
```

**Indices**: PRIMARY KEY (id), org_id, is_hiring_manager

### role_discoveries

Open roles found via Adzuna + Claude web search.

```sql
CREATE TABLE role_discoveries (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id       INT UNSIGNED,
  title        VARCHAR(255) NOT NULL,
  company_name VARCHAR(255),
  url          VARCHAR(1000),
  location     VARCHAR(150),
  remote_ok    TINYINT(1) DEFAULT 0,
  posted_date  DATE,
  source       ENUM('adzuna','claude_web') DEFAULT 'adzuna',
  status       ENUM('new','seen','applied','dismissed') DEFAULT 'new',
  raw_snippet  TEXT,                       -- job description excerpt
  discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_url (url(500)),
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
);
```

**Indices**: PRIMARY KEY (id), org_id, status, source, discovered_at, UNIQUE (url)

### daily_reports

AI-generated coaching insights and role discovery summary.

```sql
CREATE TABLE daily_reports (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  report_date     DATE NOT NULL UNIQUE,
  new_roles_count INT UNSIGNED DEFAULT 0,   -- roles found in last 24h
  coaching_insight TEXT,                    -- 3-8 sentence analysis
  html_content    MEDIUMTEXT,               -- formatted email version
  email_sent      TINYINT(1) DEFAULT 0,
  generated_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Indices**: PRIMARY KEY (id), UNIQUE (report_date)

### coaching_sessions

User-submitted coaching questions and AI responses.

```sql
CREATE TABLE coaching_sessions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  trigger_type   ENUM('manual','daily') DEFAULT 'manual',
  status         ENUM('pending','done') DEFAULT 'pending',
  prompt_summary TEXT,                     -- user's question
  response_text  TEXT,                     -- AI's answer
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  answered_at    DATETIME
);
```

**Indices**: PRIMARY KEY (id), status, created_at

### contact_suggestions

AI-generated LinkedIn search titles for reaching out.

```sql
CREATE TABLE contact_suggestions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id     INT UNSIGNED NOT NULL,
  titles     JSON NOT NULL,                -- ["Title 1", "Title 2", ...]
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);
```

**Indices**: PRIMARY KEY (id), org_id

---

## API Endpoints

All endpoints accept `?token=TRACKER_TOKEN` as query parameter. Some also accept POST/PUT/PATCH.

### Applications

**GET** `/api/applications.php`
- **Params**: `status=active|screen|interview|offer|no|canceled|hold`, `fit=high|med|low`, `org_id=N`
- **Returns**: `{ok: true, data: [...]}`

**POST** `/api/applications.php`
- **Body**: `{org_id, role_title, date_applied, status, fit, resume_file, notes, is_local}`
- **Returns**: `{ok: true, id: N}`

**PUT** `/api/applications.php?id=N`
- **Body**: Any subset of fields to update
- **Returns**: `{ok: true}`

**DELETE** `/api/applications.php?id=N`
- **Returns**: `{ok: true}`

### Organizations

**GET** `/api/organizations.php`
- **Params**: `fit=high|med|low` (filter by fit_rating)
- **Returns**: `{ok: true, data: [{...org, app_count, active_count, rejected_count}, ...]}`

**POST** `/api/organizations.php`
- **Body**: `{name, domain, industry, careers_url, ...}`
- **Returns**: `{ok: true, id: N}`

**PUT** `/api/organizations.php?id=N`
- **Body**: Any subset of fields
- **Returns**: `{ok: true}`

### Contacts

**GET** `/api/contacts.php`
- **Params**: `org_id=N`, `suggestions=1` (orgs with no contacts)
- **Returns**: `{ok: true, data: [...]}`

**POST** `/api/contacts.php`
- **Body**: `{org_id, name, title, email, linkedin_url, is_hiring_manager, source}`
- **Returns**: `{ok: true, id: N}`

**DELETE** `/api/contacts.php?id=N`
- **Returns**: `{ok: true}`

### Discoveries

**GET** `/api/discoveries.php`
- **Params**: `org_id=N`, `status=new|seen|applied|dismissed`, `since=yesterday|7days` (filter by age)
- **Returns**: `{ok: true, data: [...]}`

**PATCH** `/api/discoveries.php?id=N`
- **Body**: `{status: 'seen'|'applied'|'dismissed'}`
- **Returns**: `{ok: true}`

### Reports

**GET** `/api/report.php`
- **Params**: `history=1` (get all reports, not just latest)
- **Returns**: `{ok: true, data: {...report} or [...]}`

### Coaching

**GET** `/api/coaching.php`
- **Params**: `status=pending|done`
- **Returns**: `{ok: true, data: [...]}`

**POST** `/api/coaching.php`
- **Body**: `{prompt_summary: "Your question here"}`
- **Returns**: `{ok: true, id: N, status: 'pending'}`

### Ingest (Agent Write Endpoint)

**POST** `/api/ingest.php`
- **Header**: `Authorization: Bearer INGEST_TOKEN`
- **Body**: `{type: 'discoveries'|'report'|'coaching_response'|'contact_suggestions', payload: {...}}`

**Types**:
- `discoveries`: Array of role objects
- `report`: `{report_date, new_roles_count, coaching_insight}`
- `coaching_response`: `{request_id, response_text}`
- `contact_suggestions`: `{org_id, titles: [...]}`

---

## Authentication

### Session Token (Browser)

- **Token**: `TRACKER_TOKEN` from `.env`
- **Storage**: HTTP-only cookie `tracker_session`
- **Expires**: 30 days
- **Usage**: Set on successful password entry; required for all API calls

### Bearer Token (Agents)

- **Token**: `INGEST_TOKEN` from `.env`
- **Usage**: `Authorization: Bearer INGEST_TOKEN` header on POST to `ingest.php`
- **Scope**: Write-only access, cannot read data

### Query Parameter

All endpoints accept `?token=TRACKER_TOKEN` for convenience (e.g., in CLI/cron calls).

---

## File Structure

```
tracker/
├── index.php                  # SPA shell + auth gate
├── .env.example              # Configuration template
├── .htaccess                 # Block .env from web access
│
├── inc/                      # Core utilities
│   ├── config.php           # Load .env, define constants
│   ├── db.php               # PDO singleton: db()
│   ├── auth.php             # check_session(), set_session_cookie()
│   └── helpers.php          # esc(), json_ok(), json_err()
│
├── api/                      # JSON API endpoints
│   ├── applications.php     # GET/POST/PUT/DELETE applications
│   ├── organizations.php    # GET/POST/PUT organizations
│   ├── contacts.php         # GET/POST/DELETE contacts
│   ├── discoveries.php      # GET/PATCH discoveries
│   ├── report.php           # GET daily_reports
│   ├── coaching.php         # GET/POST coaching_sessions
│   └── ingest.php           # POST (agent write endpoint)
│
├── cron/                     # Scheduled tasks
│   ├── sweep-adzuna.php     # Daily: query Adzuna, insert to role_discoveries
│   └── send-report.php      # Daily: email latest daily_report
│
├── setup/                    # Installation
│   ├── schema.sql           # CREATE TABLE statements
│   ├── migrate.php          # Token-gated: run schema.sql
│   └── seed.php             # Token-gated: load example data
│
└── css/                      # Styling
    └── tracker-dark.css     # Minotaur dark theme + components
```

---

## Key Design Decisions

### No Framework
- **Why**: Simpler for small teams, fewer dependencies, easy to fork/customize
- **Tradeoff**: Manual validation, simpler routing

### PDO Only
- **Why**: SQL injection protection, prepared statements, modern standard
- **Tradeoff**: No ORM convenience, but more transparency

### Single-Page App (Vanilla JS)
- **Why**: No build step, no npm, instant load
- **Tradeoff**: No components, direct DOM manipulation

### Token-Based API
- **Why**: Simple, stateless, works with agents and cron
- **Tradeoff**: No session-based CSRF protection, but tokens are per-app

### Adzuna + Claude
- **Why**: Separate concerns; Adzuna for broad sweep, Claude for deep insight
- **Tradeoff**: Requires two API keys, but faster and cheaper than Claude alone

---

## Performance Considerations

### Database Queries
- Most queries filter by `status`, `fit`, or `org_id` — these have indices
- `role_discoveries.url` is UNIQUE (de-duplicate on insert)
- `organizations.name` is UNIQUE (prevent duplicate orgs)

### Pagination
- Not implemented yet — assumes <1000 records per user
- Add LIMIT/OFFSET if you exceed this

### Caching
- No caching layer — queries are fast enough for typical load
- Consider Redis/Memcached if you integrate with Adzuna sweep (1000 roles/day)

---

## Security

### Private Data
- `.env` blocked from web access via `.htaccess`
- All user input escaped with `esc()` before rendering
- All SQL queries use prepared statements (no string interpolation)
- TRACKER_TOKEN and INGEST_TOKEN are 40+ character random strings

### HTTPS
- Always use HTTPS in production (Let's Encrypt)
- Set `Secure` flag on session cookie (auto in config)

### CORS
- Single-origin app (hosted on same domain)
- No CORS headers exposed

---

## Extensibility

### Adding a New Endpoint

1. Create `api/newfeature.php`
2. Require `inc/config.php`, `inc/auth.php`, `inc/db.php`
3. Call `check_session()` or `require_ingest_auth()`
4. Write logic, return `json_ok()` or `json_err()`

### Adding a Database Table

1. Add to `setup/schema.sql`
2. Run `setup/migrate.php` (idempotent)
3. Access via `db()` singleton

### Adding a UI Tab

1. Add nav button and tab-panel in `index.php`
2. Add CSS to `css/tracker-dark.css`
3. Add JavaScript to fetch/render from API

---

## Debugging

### Enable Error Logging

Edit `php.ini` or `.user.ini`:
```
error_reporting = E_ALL
display_errors = 0
log_errors = 1
error_log = /path/to/error.log
```

### Test Database Connection

```bash
php -r "require 'inc/config.php'; require 'inc/db.php'; var_dump(db()->query('SELECT 1')->fetch());"
```

### Test API Endpoint

```bash
curl "https://yourdomain.com/tracker/api/applications.php?token=YOUR_TOKEN"
```

### Check Cron Execution

Linux: `grep CRON /var/log/syslog | tail -20`

Hostinger: Check cron job output in hPanel → Cron Jobs

---

## Future Enhancements

- [ ] Pagination (LIMIT/OFFSET)
- [ ] Full-text search across role discoveries
- [ ] Salary analytics (min/max trends)
- [ ] Cover letter templates per company
- [ ] Interview prep (common questions by company)
- [ ] Network graph (connections between contacts)
- [ ] Mobile app (React Native)
- [ ] Email digest customization (daily, weekly, off)
- [ ] Adzuna webhook integration (real-time role updates)
- [ ] Two-factor auth for TRACKER_TOKEN

