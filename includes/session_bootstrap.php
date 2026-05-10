<?php

declare(strict_types=1);

/**
 * HTTPS visto por el cliente (Railway, nginx, Cloudflare envían X-Forwarded-Proto).
 * Sin esto, session.cookie_secure puede quedar mal y el navegador no guarda la cookie en HTTPS.
 */
function repairly_request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && (string)$_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if (is_string($proto) && $proto !== '') {
        $first = strtolower(trim(explode(',', $proto)[0]));
        if ($first === 'https') {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && (string)$_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    if (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strcasecmp((string)$_SERVER['HTTP_FRONT_END_HTTPS'], 'on') === 0) {
        return true;
    }
    return false;
}

function repairly_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $savePath = getenv('SESSION_SAVE_PATH');
    if (is_string($savePath) && $savePath !== '' && is_dir($savePath)) {
        session_save_path($savePath);
    }

    $secure = repairly_request_is_https();
    $forceSecure = getenv('SESSION_COOKIE_SECURE');
    if ($forceSecure === '1' || strtolower((string)$forceSecure) === 'true') {
        $secure = true;
    }

    $lifetime = (int)(getenv('SESSION_COOKIE_LIFETIME') ?: 0);

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    if ($secure) {
        ini_set('session.cookie_secure', '1');
    }

    ini_set('session.save_handler', 'files');



    session_start();
}
