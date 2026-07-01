<?php
// Email the latest daily report — called by the job-tracker-report agent
// or manually via browser (token-gated)

require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/helpers.php';

if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if (!TRACKER_TOKEN || !hash_equals(TRACKER_TOKEN, $token)) {
        http_response_code(401); exit('Unauthorized');
    }
}

$pdo    = db();
$report = $pdo->query('SELECT * FROM daily_reports ORDER BY report_date DESC LIMIT 1')->fetch();

if (!$report) { echo "No report found.\n"; exit; }
if ($report['email_sent']) { echo "Report already sent for {$report['report_date']}.\n"; exit; }

$date    = $report['report_date'];
$count   = $report['new_roles_count'];
$insight = $report['coaching_insight'];
$html    = $report['html_content'];

// Build email HTML if not pre-built
if (!$html) {
    $html = <<<EOT
<!DOCTYPE html>
<html>
<head><style>
body { font-family: Georgia, serif; background: #1c110b; color: #F7EAD2; padding: 32px; }
h1   { font-size: 24px; margin-bottom: 4px; }
.sub { font-family: monospace; font-size: 11px; color: rgba(247,234,210,.5); margin-bottom: 24px; letter-spacing:.15em; text-transform:uppercase; }
.stat { font-size: 48px; font-weight: 700; color: #D4913A; line-height:1; }
.label { font-family:monospace; font-size:11px; color:rgba(247,234,210,.5); text-transform:uppercase; letter-spacing:.1em; }
.insight { margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(247,234,210,.12); font-size:15px; line-height:1.7; color:rgba(247,234,210,.8); }
.cta { margin-top: 24px; }
.cta a { color: #D4913A; }
</style></head>
<body>
<h1>Daily Brief</h1>
<div class="sub">{$date}</div>
<div class="stat">{$count}</div>
<div class="label">New roles found</div>
<div class="insight">{$insight}</div>
<div class="cta"><a href="https://minotaurdesign.com/tracker/">View tracker →</a></div>
</body></html>
EOT;
}

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: tracker@minotaurdesign.com\r\n";

$sent = mail(REPORT_EMAIL, "Job Tracker · Daily Brief · $date", $html, $headers);

if ($sent) {
    $pdo->prepare('UPDATE daily_reports SET email_sent = 1 WHERE id = ?')->execute([$report['id']]);
    echo "Email sent to " . REPORT_EMAIL . " for $date.\n";
} else {
    echo "mail() failed.\n"; exit(1);
}
