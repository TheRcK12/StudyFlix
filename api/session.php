<?php
declare(strict_types=1);

function studyflix_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $httpsDirect = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $runningOnRailway = (bool) (getenv('RAILWAY_ENVIRONMENT_ID') ?: getenv('RAILWAY_SERVICE_ID'));
    $secure = $forwardedProto === 'https' || $httpsDirect || $runningOnRailway;

    $secureOverride = getenv('SESSION_COOKIE_SECURE');
    if ($secureOverride !== false && $secureOverride !== '') {
        $secure = filter_var($secureOverride, FILTER_VALIDATE_BOOLEAN);
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('STUDYFLIXSESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function studyflix_destroy_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        studyflix_start_session();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $options = [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ];

        if (!empty($params['domain'])) {
            $options['domain'] = $params['domain'];
        }

        setcookie(session_name(), '', $options);
    }

    session_destroy();
}
