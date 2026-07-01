<?php
require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/helpers.php';

header('Content-Type: application/json');
require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = db();

if ($method === 'GET') {
    $status = $_GET['status'] ?? null;
    $where  = $status ? 'WHERE status = ?' : '';
    $params = $status ? [$status] : [];

    $stmt = $pdo->prepare(
        'SELECT * FROM coaching_sessions ' . $where . '
         ORDER BY created_at DESC LIMIT 50'
    );
    $stmt->execute($params);
    json_ok($stmt->fetchAll());
}

// POST: queue a new coaching request from the web UI
if ($method === 'POST') {
    $b = request_body();
    if (empty($b['prompt'])) json_err('prompt required');

    $stmt = $pdo->prepare(
        'INSERT INTO coaching_sessions (trigger_type, status, prompt_summary)
         VALUES (?, "pending", ?)'
    );
    $stmt->execute([
        $b['trigger_type'] ?? 'manual',
        mb_substr($b['prompt'], 0, 1000),
    ]);
    json_ok(['id' => (int)$pdo->lastInsertId(), 'status' => 'pending']);
}

json_err('Method not allowed', 405);
