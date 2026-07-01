-- Job Search Tracker — Database Schema
-- Run via setup/migrate.php (idempotent)

CREATE TABLE IF NOT EXISTS organizations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    domain      VARCHAR(255),
    size_range  VARCHAR(50),            -- e.g. '50-200', '1000+'
    industry    VARCHAR(100),
    hq_location VARCHAR(150),
    glassdoor_url VARCHAR(500),
    linkedin_url  VARCHAR(500),
    careers_url   VARCHAR(500),
    fit_rating  ENUM('high','med','low') DEFAULT 'med',
    notes       TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS applications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id          INT UNSIGNED,
    role_title      VARCHAR(255) NOT NULL,
    date_applied    DATE,
    status          ENUM('active','screen','interview','offer','no','canceled','hold') DEFAULT 'active',
    fit             ENUM('high','med','low') DEFAULT 'med',
    resume_file     VARCHAR(255),
    source_url      VARCHAR(1000),
    job_description TEXT,
    salary_min      INT UNSIGNED,
    salary_max      INT UNSIGNED,
    location        VARCHAR(150),
    remote_ok       TINYINT(1) DEFAULT 0,
    notes           TEXT,
    is_local        TINYINT(1) DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contacts (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id            INT UNSIGNED NOT NULL,
    name              VARCHAR(255) NOT NULL,
    title             VARCHAR(255),
    email             VARCHAR(255),
    linkedin_url      VARCHAR(500),
    is_hiring_manager TINYINT(1) DEFAULT 0,
    source            VARCHAR(100),
    notes             TEXT,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_suggestions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id     INT UNSIGNED NOT NULL,
    titles     JSON NOT NULL,           -- ["Recruiting Manager", "Head of Talent"]
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_discoveries (
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
    raw_snippet  TEXT,
    discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_url (url(500)),
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS daily_reports (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date     DATE NOT NULL UNIQUE,
    new_roles_count INT UNSIGNED DEFAULT 0,
    coaching_insight TEXT,
    html_content    MEDIUMTEXT,
    email_sent      TINYINT(1) DEFAULT 0,
    generated_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coaching_sessions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trigger_type   ENUM('manual','daily') DEFAULT 'manual',
    status         ENUM('pending','done') DEFAULT 'pending',
    prompt_summary TEXT,
    response_text  TEXT,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    answered_at    DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
