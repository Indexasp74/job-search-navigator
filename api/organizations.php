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

    if (!empty($_GET['fit'])) {
        $where[]  = 'o.fit_rating = ?';
        $params[] = $_GET['fit'];
    }
    if ($id) {
        $where[]  = 'o.id = ?';
        $params[] = $id;
    }

    $sql = 'SELECT o.*,
                COUNT(a.id)                                   AS app_count,
                SUM(a.status IN ("active","screen","interview")) AS active_count,
                SUM(a.status = "no")                          AS rejected_count
            FROM organizations o
            LEFT JOIN applications a ON a.org_id = o.id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY o.id
            ORDER BY o.fit_rating, o.name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    json_ok($id ? ($rows[0] ?? null) : $rows);
}

if ($method === 'POST') {
    $b = request_body();
    if (empty($b['name'])) json_err('name required');

    $stmt = $pdo->prepare(
        'INSERT INTO organizations
         (name, domain, size_range, industry, hq_location, glassdoor_url,
          linkedin_url, careers_url, fit_rating, notes)
         VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $b['name'],
        $b['domain']       ?? '',
        $b['size_range']   ?? '',
        $b['industry']     ?? '',
        $b['hq_location']  ?? '',
        $b['glassdoor_url'] ?? '',
        $b['linkedin_url'] ?? '',
        $b['careers_url']  ?? '',
        $b['fit_rating']   ?? 'med',
        $b['notes']        ?? '',
    ]);
    json_ok(['id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'PUT') {
    if (!$id) json_err('id required');
    $b = request_body();

    $fields = [];
    $params = [];
    $allowed = ['name','domain','size_range','industry','hq_location',
                'glassdoor_url','linkedin_url','careers_url','fit_rating','notes'];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $b)) {
            $fields[] = "$f = ?";
            $params[] = $b[$f];
        }
    }
    if (!$fields) json_err('nothing to update');
    $params[] = $id;
    $pdo->prepare('UPDATE organizations SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    json_ok();
}

json_err('Method not allowed', 405);
