<?php



/**
 * Sesiones en MySQL para varias instancias (Railway) cuando SESSION_STORE=mysql.
 * Tabla creada automáticamente en el primer open().
 */
final class RepairlyMysqliSessionHandler implements SessionHandlerInterface
{
    private bool $tableReady = false;

    public function __construct(private mysqli $conn)
    {
    }

    private function ensureTable(): void
    {
        if ($this->tableReady) {
            return;
        }
        $this->conn->query(
            'CREATE TABLE IF NOT EXISTS repairly_sessions (
                session_id VARCHAR(128) NOT NULL PRIMARY KEY,
                session_data MEDIUMBLOB NOT NULL,
                session_expiry INT UNSIGNED NOT NULL,
                INDEX idx_expiry (session_expiry)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->tableReady = true;
    }

    public function open(string $path, string $name): bool
    {
        $this->ensureTable();
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $this->ensureTable();
        $stmt = $this->conn->prepare(
            'SELECT session_data FROM repairly_sessions WHERE session_id = ? AND session_expiry >= ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $now = time();
        $stmt->bind_param('si', $id, $now);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row || !isset($row['session_data'])) {
            return '';
        }
        $blob = $row['session_data'];
        return is_string($blob) ? $blob : '';
    }

    public function write(string $id, string $data): bool
    {
        $this->ensureTable();
        $life = (int)ini_get('session.gc_maxlifetime');
        if ($life <= 0) {
            $life = 1440;
        }
        $exp = time() + $life;
        $stmt = $this->conn->prepare(
            'REPLACE INTO repairly_sessions (session_id, session_data, session_expiry) VALUES (?, ?, ?)'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ssi', $id, $data, $exp);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function destroy(string $id): bool
    {
        $this->ensureTable();
        $stmt = $this->conn->prepare('DELETE FROM repairly_sessions WHERE session_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $this->ensureTable();
        $now = time();
        $this->conn->query('DELETE FROM repairly_sessions WHERE session_expiry < ' . (int)$now);
        $n = (int)$this->conn->affected_rows;
        return $n >= 0 ? $n : false;
    }
}
