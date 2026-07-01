<?php
// Adzuna job board sweep — called by Hostinger cron daily
// Queries Adzuna for open roles at every tracked organization
// Inserts new discoveries; skips duplicates via URL unique key

require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/helpers.php';

// Allow both cron (CLI) and manual browser trigger (token-gated)
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if (!TRACKER_TOKEN || !hash_equals(TRACKER_TOKEN, $token)) {
        http_response_code(401); exit('Unauthorized');
    }
}

if (!ADZUNA_APP_ID || !ADZUNA_APP_KEY) {
    echo "Adzuna credentials not configured.\n"; exit(1);
}

$pdo  = db();
$orgs = $pdo->query('SELECT id, name FROM organizations')->fetchAll();

$inserted = 0;
$stmt = $pdo->prepare(
    'INSERT IGNORE INTO role_discoveries
     (org_id, title, company_name, url, location, remote_ok, posted_date, source, raw_snippet)
     VALUES (?,?,?,?,?,?,?,?,?)'
);

foreach ($orgs as $org) {
    $params = http_build_query([
        'app_id'          => ADZUNA_APP_ID,
        'app_key'         => ADZUNA_APP_KEY,
        'what_phrase'     => $org['name'],
        'results_per_page'=> 10,
        'sort_by'         => 'date',
        'content-type'    => 'application/json',
    ]);

    $url = "https://api.adzuna.com/v1/api/jobs/us/search/1?$params";
    $raw = @file_get_contents($url);
    if (!$raw) { sleep(1); continue; }

    $data = json_decode($raw, true);
    foreach ($data['results'] ?? [] as $job) {
        $remote = stripos($job['title']??'', 'remote') !== false
               || stripos($job['location']['display_name']??'', 'remote') !== false;
        $stmt->execute([
            $org['id'],
            $job['title'] ?? '',
            $job['company']['display_name'] ?? $org['name'],
            $job['redirect_url'] ?? '',
            $job['location']['display_name'] ?? '',
            $remote ? 1 : 0,
            isset($job['created']) ? date('Y-m-d', strtotime($job['created'])) : null,
            'adzuna',
            substr($job['description'] ?? '', 0, 500),
        ]);
        if ($stmt->rowCount()) $inserted++;
    }
    sleep(1); // respect API rate limit
}

echo "Adzuna sweep complete. $inserted new discoveries.\n";
