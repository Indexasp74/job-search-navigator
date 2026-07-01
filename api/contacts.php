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

    if (!empty($_GET['org_id'])) {
        $where[]  = 'c.org_id = ?';
        $params[] = (int)$_GET['org_id'];
    }

    $sql = 'SELECT c.*, o.name AS org_name,
                   cs.titles AS suggested_titles
            FROM contacts c
            JOIN organizations o ON o.id = c.org_id
            LEFT JOIN (
                SELECT org_id, titles
                FROM contact_suggestions
                WHERE id IN (SELECT MAX(id) FROM contact_suggestions GROUP BY org_id)
            ) cs ON cs.org_id = c.org_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY o.name, c.is_hiring_manager DESC, c.name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_ok($stmt->fetchAll());
}

// GET suggestions (orgs with no contacts)
if ($method === 'GET' && !empty($_GET['suggestions'])) {
    $rows = $pdo->query(
        'SELECT o.id, o.name, o.industry, o.size_range, cs.titles
         FROM organizations o
         LEFT JOIN contact_suggestions cs ON cs.org_id = o.id
         LEFT JOIN contacts c ON c.org_id = o.id
         WHERE c.id IS NULL
         ORDER BY o.fit_rating, o.name'
    )->fetchAll();
    json_ok($rows);
}

if ($method === 'POST') {
    $b = request_body();
    if (empty($b['org_id']) || empty($b['name'])) json_err('org_id and name required');

    $stmt = $pdo->prepare(
        'INSERT INTO contacts (org_id, name, title, email, linkedin_url, is_hiring_manager, source, notes)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        (int)$b['org_id'],
        $b['name'],
        $b['title']             ?? '',
        $b['email']             ?? '',
        $b['linkedin_url']      ?? '',
        !empty($b['is_hiring_manager']) ? 1 : 0,
        $b['source']            ?? 'manual',
        $b['notes']             ?? '',
    ]);
    json_ok(['id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'DELETE') {
    if (!$id) json_err('id required');
    $pdo->prepare('DELETE FROM contacts WHERE id = ?')->execute([$id]);
    json_ok();
}

json_err('Method not allowed', 405);
