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

    // Optional: store sessions in MySQL to make them stable on serverless platforms (Vercel).
    // Enable by setting SESSION_HANDLER=mysql in env vars.
    $handler = strtolower((string)(getenv('SESSION_HANDLER') ?: ''));
    if ($handler === 'mysql') {
        $maybeConn = $GLOBALS['conn'] ?? null;
        if ($maybeConn instanceof mysqli) {
            repairly_enable_mysql_sessions($maybeConn);
        }
    }

    $savePath = getenv('SESSION_SAVE_PATH');
    if (is_string($savePath) && $savePath !== '' && is_dir($savePath)) {
        session_save_path($savePath);
    }

    // Serverless (Vercel) y algunos hosts tienen filesystem de solo lectura.
    // Si la ruta por defecto no es escribible, usa un directorio temporal.
    $currentSavePath = (string)session_save_path();
    if ($currentSavePath === '' || !is_dir($currentSavePath) || !is_writable($currentSavePath)) {
        $tmp = sys_get_temp_dir();
        if (is_string($tmp) && $tmp !== '' && is_dir($tmp) && is_writable($tmp)) {
            $target = rtrim($tmp, "/\\") . DIRECTORY_SEPARATOR . 'repairly_sessions';
            if (!is_dir($target)) {
                @mkdir($target, 0777, true);
            }
            if (is_dir($target) && is_writable($target)) {
                session_save_path($target);
            } else {
                session_save_path($tmp);
            }
        }
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

function repairly_enable_mysql_sessions(mysqli $conn): void
{
    static $enabled = false;
    if ($enabled) {
        return;
    }

    // Best-effort create table; ignore errors if permissions don't allow.
    $conn->query(
        "CREATE TABLE IF NOT EXISTS `php_sessions` (" .
        " `id` VARCHAR(128) NOT NULL," .
        " `data` BLOB NOT NULL," .
        " `timestamp` INT NOT NULL," .
        " PRIMARY KEY (`id`)," .
        " INDEX (`timestamp`)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $lifetime = (int)ini_get('session.gc_maxlifetime');

    $handler = new class($conn, $lifetime) extends SessionHandler {
        public function __construct(private mysqli $conn, private int $lifetime)
        {
        }

        public function open($savePath, $sessionName): bool
        {
            return true;
        }

        public function close(): bool
        {
            return true;
        }

        public function read($id): string
        {
            $sql = "SELECT `data` FROM `php_sessions` WHERE `id` = ? AND `timestamp` >= ? LIMIT 1";
            $min = time() - $this->lifetime;
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return '';
            }
            $stmt->bind_param('si', $id, $min);
            $stmt->execute();
            $stmt->bind_result($data);
            $row = $stmt->fetch();
            $stmt->close();
            if (!$row) {
                return '';
            }
            return is_string($data) ? $data : '';
        }

        public function write($id, $data): bool
        {
            $ts = time();
            $sql = "REPLACE INTO `php_sessions` (`id`, `data`, `timestamp`) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $null = null;
            $stmt->bind_param('sbi', $id, $null, $ts);
            $stmt->send_long_data(1, (string)$data);
            $ok = $stmt->execute();
            $stmt->close();
            return (bool)$ok;
        }

        public function destroy($id): bool
        {
            $sql = "DELETE FROM `php_sessions` WHERE `id` = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return true;
            }
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $stmt->close();
            return true;
        }

        public function gc($max_lifetime): int|false
        {
            $min = time() - (int)$max_lifetime;
            $stmt = $this->conn->prepare("DELETE FROM `php_sessions` WHERE `timestamp` < ?");
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('i', $min);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
        }
    };

    session_set_save_handler($handler, true);
    ini_set('session.save_handler', 'user');
    $enabled = true;
}
