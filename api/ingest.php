<?php
require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);
require_ingest_auth();

$body = request_body();
$type    = $body['type']    ?? '';
$payload = $body['payload'] ?? [];
$pdo     = db();

switch ($type) {

    case 'discoveries':
        // payload: [{title, company_name, url, location, remote_ok, posted_date, source, raw_snippet, org_id?}, ...]
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO role_discoveries
             (org_id, title, company_name, url, location, remote_ok, posted_date, source, raw_snippet)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $count = 0;
        foreach ((array)$payload as $r) {
            if (empty($r['title']) || empty($r['url'])) continue;
            $stmt->execute([
                $r['org_id']      ?? null,
                $r['title'],
                $r['company_name'] ?? '',
                $r['url'],
                $r['location']    ?? '',
                !empty($r['remote_ok']) ? 1 : 0,
                $r['posted_date'] ?? null,
                $r['source']      ?? 'claude_web',
                $r['raw_snippet'] ?? '',
            ]);
            if ($stmt->rowCount()) $count++;
        }
        json_ok(['inserted' => $count]);

    case 'report':
        // payload: {report_date, new_roles_count, coaching_insight, html_content}
        $stmt = $pdo->prepare(
            'INSERT INTO daily_reports (report_date, new_roles_count, coaching_insight, html_content)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE
               new_roles_count = VALUES(new_roles_count),
               coaching_insight = VALUES(coaching_insight),
               html_content = VALUES(html_content),
               generated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            $payload['report_date']      ?? date('Y-m-d'),
            $payload['new_roles_count']  ?? 0,
            $payload['coaching_insight'] ?? '',
            $payload['html_content']     ?? '',
        ]);
        json_ok();

    case 'coaching_response':
        // payload: {request_id, response_text}
        if (empty($payload['request_id'])) json_err('request_id required');
        $pdo->prepare(
            'UPDATE coaching_sessions
             SET status = "done", response_text = ?, answered_at = NOW()
             WHERE id = ?'
        )->execute([$payload['response_text'] ?? '', (int)$payload['request_id']]);
        json_ok();

    case 'contact_suggestions':
        // payload: {org_id, titles: [...]}
        if (empty($payload['org_id']) || empty($payload['titles'])) json_err('org_id and titles required');
        $pdo->prepare(
            'INSERT INTO contact_suggestions (org_id, titles) VALUES (?,?)
             ON DUPLICATE KEY UPDATE titles = VALUES(titles)'
        )->execute([(int)$payload['org_id'], json_encode($payload['titles'])]);
        json_ok();

    default:
        json_err("Unknown type: $type");
}
