<?php
require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/auth.php';

// Token-gated: only run via browser with correct token
$token = $_GET['token'] ?? '';
if (!TRACKER_TOKEN || !hash_equals(TRACKER_TOKEN, $token)) {
    http_response_code(401);
    exit('Unauthorized');
}

require_once dirname(__DIR__) . '/inc/db.php';

$sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/schema.sql'));
$statements = array_filter(array_map('trim', explode(';', $sql)));

$results = [];
foreach ($statements as $stmt) {
    if (!$stmt) continue;
    try {
        db()->exec($stmt);
        $results[] = ['ok' => true, 'sql' => substr($stmt, 0, 60) . '...'];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'sql' => substr($stmt, 0, 60), 'error' => $e->getMessage()];
    }
}

header('Content-Type: application/json');
echo json_encode(['migrate' => 'done', 'statements' => count($results), 'results' => $results], JSON_PRETTY_PRINT);
