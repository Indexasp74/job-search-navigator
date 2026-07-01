<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// For browser sessions: check cookie
function check_session(): bool {
    return isset($_COOKIE['tracker_session'])
        && hash_equals(TRACKER_TOKEN, $_COOKIE['tracker_session']);
}

// For API calls from browser: cookie or query param token
function require_auth(): void {
    $token = $_GET['token'] ?? ($_COOKIE['tracker_session'] ?? '');
    if (!TRACKER_TOKEN || !hash_equals(TRACKER_TOKEN, $token)) {
        json_err('Unauthorized', 401);
    }
}

// For ingest endpoint called by Claude Code agents
function require_ingest_auth(): void {
    $token = bearer_token();
    if (!INGEST_TOKEN || !hash_equals(INGEST_TOKEN, $token)) {
        json_err('Unauthorized', 401);
    }
}

function set_session_cookie(): void {
    setcookie('tracker_session', TRACKER_TOKEN, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/tracker',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
