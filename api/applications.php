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
    $where = ['1=1'];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[] = 'a.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['fit'])) {
        $where[] = 'a.fit = ?';
        $params[] = $_GET['fit'];
    }
    if (!empty($_GET['org_id'])) {
        $where[] = 'a.org_id = ?';
        $params[] = (int)$_GET['org_id'];
    }
    if ($id) {
        $where[] = 'a.id = ?';
        $params[] = $id;
    }

    $sql = 'SELECT a.*, o.name AS company_name, o.fit_rating AS org_fit
            FROM applications a
            LEFT JOIN organizations o ON o.id = a.org_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY a.date_applied DESC, a.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    json_ok($id ? ($rows[0] ?? null) : $rows);
}

if ($method === 'POST') {
    $b = request_body();
    if (empty($b['role_title'])) json_err('role_title required');

    $stmt = $pdo->prepare(
        'INSERT INTO applications
         (org_id, role_title, date_applied, status, fit, resume_file, source_url,
          job_description, salary_min, salary_max, location, remote_ok, notes, is_local)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $b['org_id']          ?? null,
        $b['role_title'],
        $b['date_applied']    ?: null,
        $b['status']          ?? 'active',
        $b['fit']             ?? 'med',
        $b['resume_file']     ?? '',
        $b['source_url']      ?? '',
        $b['job_description'] ?? '',
        $b['salary_min']      ?: null,
        $b['salary_max']      ?: null,
        $b['location']        ?? '',
        !empty($b['remote_ok']) ? 1 : 0,
        $b['notes']           ?? '',
        !empty($b['is_local']) ? 1 : 0,
    ]);
    json_ok(['id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'PUT') {
    if (!$id) json_err('id required');
    $b = request_body();

    $fields = [];
    $params = [];
    $allowed = ['org_id','role_title','date_applied','status','fit','resume_file',
                'source_url','job_description','salary_min','salary_max',
                'location','remote_ok','notes','is_local'];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $b)) {
            $fields[] = "$f = ?";
            $val = $b[$f];
            if (in_array($f, ['date_applied','salary_min','salary_max']) && $val === '') $val = null;
            $params[] = $val;
        }
    }
    if (!$fields) json_err('nothing to update');
    $params[] = $id;
    $pdo->prepare('UPDATE applications SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    json_ok();
}

if ($method === 'DELETE') {
    if (!$id) json_err('id required');
    $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
    json_ok();
}

json_err('Method not allowed', 405);
