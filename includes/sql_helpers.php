<?php

declare(strict_types=1);

function table_exists(mysqli $conn, string $name): bool
{
    $name = trim($name);
    $sql = '
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND LOWER(table_name) = LOWER(?)
    ';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count > 0;
}

function pick_table(mysqli $conn, array $candidates): string
{
    $tables = [];
    $res = $conn->query('SHOW TABLES');
    if ($res) {
        while ($row = $res->fetch_array()) {
            $tables[] = strtolower((string)$row[0]);
        }
    }
    $originalByLower = [];
    $res2 = $conn->query('SHOW TABLES');
    if ($res2) {
        while ($row = $res2->fetch_array()) {
            $originalByLower[strtolower((string)$row[0])] = (string)$row[0];
        }
    }
    foreach ($candidates as $candidate) {
        $candidate = strtolower(trim((string)$candidate));
        foreach ($tables as $table) {
            if ($table === $candidate || str_contains($table, $candidate)) {
                return $originalByLower[$table] ?? $candidate;
            }
        }
    }
    return '';
}

function table_columns(mysqli $conn, string $table): array
{
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) {
        return $cols;
    }
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['Field'])) {
            $cols[$row['Field']] = true;
        }
    }
    $res->free();
    return $cols;
}

/** Tipo MySQL de la columna (p. ej. varchar(40), enum('a','b')). */
function column_mysql_type(mysqli $conn, string $table, string $column): ?string
{
    $safeTable = str_replace('`', '``', $table);
    $esc = str_replace(['\\', "'"], ['\\\\', "\\'"], $column);
    $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$esc}'");
    if (!$res || $res->num_rows === 0) {
        return null;
    }
    $row = $res->fetch_assoc();
    $res->free();
    return isset($row['Type']) ? (string)$row['Type'] : null;
}

/**
 * Valores permitidos de un ENUM según COLUMN_TYPE (SHOW COLUMNS / INFORMATION_SCHEMA).
 *
 * @return list<string>
 */
/** tinyint/int/bigint/bit como almacenamiento numérico (p.ej. estado 0/1). */
function mysql_type_is_integer_like(string $columnType): bool
{
    return (bool)preg_match('/^(tinyint|smallint|mediumint|int|bigint|bit)\b/i', trim($columnType));
}

function mysql_enum_values_from_type(string $columnType): array
{
    $columnType = trim($columnType);
    if (!preg_match('/^enum\s*\((.*)\)\s*$/is', $columnType, $m)) {
        return [];
    }
    $inner = $m[1];
    $vals = [];
    $buf = '';
    $inStr = false;
    $len = strlen($inner);
    for ($i = 0; $i < $len; $i++) {
        $ch = $inner[$i];
        if (!$inStr && $ch === "'") {
            $inStr = true;
            $buf = '';
            continue;
        }
        if ($inStr) {
            if ($ch === "'" && $i + 1 < $len && $inner[$i + 1] === "'") {
                $buf .= "'";
                $i++;
                continue;
            }
            if ($ch === "'") {
                $vals[] = $buf;
                $inStr = false;
                continue;
            }
            $buf .= $ch;
        }
    }
    return $vals;
}

function db_scalar(mysqli $conn, string $sql, int|float|string $default = 0): int|float|string
{
    $res = $conn->query($sql);
    if (!$res) {
        return $default;
    }
    $row = $res->fetch_row();
    $res->free();
    return $row[0] ?? $default;
}

function db_rows(mysqli $conn, string $sql): array
{
    $res = $conn->query($sql);
    if (!$res) {
        return [];
    }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    return $rows;
}

/**
 * Devuelve el nombre real de columna en $cols que coincide con el primer candidato (comparación sin mayúsculas).
 *
 * @param array<string, mixed> $cols
 * @param list<string> $candidates
 */
function repairly_pick_column(array $cols, array $candidates): ?string
{
    foreach ($candidates as $cand) {
        $want = mb_strtolower(trim((string)$cand));
        foreach (array_keys($cols) as $k) {
            if (mb_strtolower((string)$k) === $want) {
                return (string)$k;
            }
        }
    }
    return null;
}
