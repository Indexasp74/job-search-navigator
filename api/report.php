<?php
require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/helpers.php';

header('Content-Type: application/json');
require_auth();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_err('Method not allowed', 405);

if (!empty($_GET['history'])) {
    $rows = $pdo->query(
        'SELECT id, report_date, new_roles_count, coaching_insight, email_sent, generated_at
         FROM daily_reports ORDER BY report_date DESC LIMIT 30'
    )->fetchAll();
    json_ok($rows);
}

// Latest report
$row = $pdo->query(
    'SELECT * FROM daily_reports ORDER BY report_date DESC LIMIT 1'
)->fetch();
json_ok($row ?: null);
