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
        if ($candidate === '') {
            continue;
        }

        // 1) Prioriza coincidencia exacta para evitar falsos positivos
        //    (p.ej. "pieza" matcheando "orden_pieza").
        foreach ($tables as $table) {
            if ($table === $candidate) {
                return $originalByLower[$table] ?? $candidate;
            }
        }

        // 2) Luego permite coincidencia parcial como fallback.
        foreach ($tables as $table) {
            if (str_contains($table, $candidate)) {
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

/**
 * Si la columna es ENUM, devuelve uno de los literales permitidos (evita "Data truncated").
 * Si no es ENUM, devuelve $value sin cambiar.
 */
/**
 * Si no hay columna "nombre" estándar, intenta la primera columna que parezca etiqueta de pieza.
 *
 * @param array<string, mixed> $cols
 */
function repairly_guess_pieza_nombre_column(array $cols): ?string
{
    $skipNorm = [];
    foreach ([
        'id', 'id_pieza', 'id_articulo', 'id_producto', 'referencia', 'codigo', 'sku', 'ref',
        'stock', 'cantidad', 'existencia', 'unidades', 'minimo', 'maximo',
        'precio_compra', 'precio_venta', 'precio', 'costo', 'precio_costo', 'precio_publico',
        'fecha', 'fecha_alta', 'fecha_mod', 'creado', 'actualizado', 'estado', 'activo',
    ] as $s) {
        $skipNorm[] = (string)preg_replace('/[^a-z0-9]/', '', mb_strtolower($s));
    }
    foreach (array_keys($cols) as $k) {
        $norm = (string)preg_replace('/[^a-z0-9]/', '', mb_strtolower((string)$k));
        if ($norm === '' || in_array($norm, $skipNorm, true)) {
            continue;
        }
        if (str_starts_with($norm, 'id')) {
            continue;
        }
        if (str_contains($norm, 'precio') || str_contains($norm, 'costo')) {
            continue;
        }
        if (str_contains($norm, 'stock') || str_contains($norm, 'cantidad') || str_contains($norm, 'existencia')) {
            continue;
        }
        return (string)$k;
    }
    return null;
}

function repairly_coerce_value_for_enum_column(mysqli $conn, string $table, string $column, string $value): string
{
    $type = column_mysql_type($conn, $table, $column);
    if ($type === null || stripos(trim($type), 'enum(') !== 0) {
        return $value;
    }
    $allowed = mysql_enum_values_from_type($type);
    if ($allowed === []) {
        return $value;
    }
    $norm = static function (string $s): string {
        return mb_strtolower(trim($s));
    };
    $v = trim($value);
    $vn = $norm($v);
    if ($vn === '') {
        foreach (['otro', 'otros', 'general', 'varios', 'mixto', 'n/a', 'sin_clasificar'] as $hint) {
            foreach ($allowed as $a) {
                if ($norm((string)$a) === $hint) {
                    return (string)$a;
                }
            }
        }
        return (string)$allowed[0];
    }
    foreach ($allowed as $a) {
        if ($norm((string)$a) === $vn) {
            return (string)$a;
        }
    }
    foreach ($allowed as $a) {
        $an = $norm((string)$a);
        if ($an !== '' && str_contains($vn, $an)) {
            return (string)$a;
        }
    }
    foreach (['otro', 'otros', 'general', 'varios', 'mixto', 'n/a', 'sin_clasificar'] as $hint) {
        foreach ($allowed as $a) {
            $an = $norm((string)$a);
            if ($an === $hint || str_contains($an, $hint)) {
                return (string)$a;
            }
        }
    }
    return (string)$allowed[0];
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
    $norm = static function (string $s): string {
        return (string)preg_replace('/[^a-z0-9]/', '', mb_strtolower($s));
    };
    foreach ($candidates as $cand) {
        $want = $norm((string)$cand);
        if ($want === '') {
            continue;
        }
        foreach (array_keys($cols) as $k) {
            if ($norm((string)$k) === $want) {
                return (string)$k;
            }
        }
    }
    return null;
}

/**
 * Tipo mysqli y valor listos para bind_param según el tipo SQL de la columna.
 *
 * @return array{0: 'i'|'d'|'s', 1: int|float|string}
 */
function repairly_mysqli_param_for_column(mysqli $conn, string $table, string $column, mixed $value): array
{
    $type = column_mysql_type($conn, $table, $column);
    if ($type === null) {
        if (is_int($value)) {
            return ['i', $value];
        }
        if (is_float($value)) {
            return ['d', $value];
        }
        return ['s', (string)$value];
    }
    $t = trim($type);
    if (mysql_type_is_integer_like($t)) {
        return ['i', (int)$value];
    }
    if ((bool)preg_match('/^(decimal|float|double|numeric)\b/i', $t)) {
        return ['d', (float)$value];
    }
    return ['s', (string)$value];
}

/** Valor fecha/hora acorde a DATE vs DATETIME/TIMESTAMP. */
function repairly_sql_date_value_for_column(mysqli $conn, string $table, string $column, string $fechaYmd): string
{
    $fechaYmd = substr(trim($fechaYmd), 0, 10);
    if ($fechaYmd === '') {
        $fechaYmd = date('Y-m-d');
    }
    $type = column_mysql_type($conn, $table, $column);
    if ($type !== null && (bool)preg_match('/\b(datetime|timestamp)\b/i', $type)) {
        $ts = strtotime($fechaYmd);
        return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    }
    return $fechaYmd;
}
