<?php
// Load .env from tracker root if it exists (not committed — see .gitignore)
$env_file = dirname(__DIR__) . '/.env';
if (is_readable($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\"'");
        if ($key && !getenv($key)) putenv("$key=$val");
    }
}

define('DB_HOST', getenv('DATABASE_SERVER') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME')         ?: '');
define('DB_USER', getenv('DB_USER')         ?: '');
define('DB_PASS', getenv('DB_PASS')         ?: '');

define('TRACKER_TOKEN',  getenv('TRACKER_TOKEN')  ?: '');
define('INGEST_TOKEN',   getenv('INGEST_TOKEN')   ?: '');

define('ADZUNA_APP_ID',  getenv('ADZUNA_APP_ID')  ?: '');
define('ADZUNA_APP_KEY', getenv('ADZUNA_APP_KEY') ?: '');

define('REPORT_EMAIL', getenv('REPORT_EMAIL') ?: 'user@example.com');
define('SITE_URL',     getenv('SITE_URL')     ?: 'https://localhost/tracker');
