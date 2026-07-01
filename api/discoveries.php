<?php
require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/helpers.php';

header('Content-Type: application/json');
require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$pdo    = db();

if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[]  = 'd.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['org_id'])) {
        $where[]  = 'd.org_id = ?';
        $params[] = (int)$_GET['org_id'];
    }
    if (!empty($_GET['since'])) {
        $where[]  = 'DATE(d.discovered_at) >= ?';
        $params[] = $_GET['since'] === 'yesterday'
            ? date('Y-m-d', strtotime('-1 day'))
            : $_GET['since'];
    }

    $stmt = $pdo->prepare(
        'SELECT d.*, o.name AS org_name
         FROM role_discoveries d
         LEFT JOIN organizations o ON o.id = d.org_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY d.discovered_at DESC
         LIMIT 200'
    );
    $stmt->execute($params);
    json_ok($stmt->fetchAll());
}

// PATCH: update status (seen / applied / dismissed)
if ($method === 'PATCH') {
    if (!$id) json_err('id required');
    $b = request_body();
    $allowed = ['new','seen','applied','dismissed'];
    if (empty($b['status']) || !in_array($b['status'], $allowed)) json_err('invalid status');
    $pdo->prepare('UPDATE role_discoveries SET status = ? WHERE id = ?')->execute([$b['status'], $id]);
    json_ok();
}

json_err('Method not allowed', 405);
