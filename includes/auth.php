<?php

declare(strict_types=1);

function repairly_panel_roles(): array
{
    return ['administrador', 'tecnico', 'supervisor', 'operador'];
}

function repairly_normalize_role(?string $rol): string
{
    return mb_strtolower(trim((string)$rol));
}

/** Normaliza sinónimos de BD (ENUM antiguos, inglés, mayúsculas) a nombres usados por la app. */
function repairly_role_canonical(?string $rol): string
{
    $r = repairly_normalize_role($rol);
    if ($r === '') {
        return '';
    }
    static $map = [
        'admin' => 'administrador',
        'administrator' => 'administrador',
        'root' => 'administrador',
        'técnico' => 'tecnico',
        'technician' => 'tecnico',
        'tech' => 'tecnico',
        'empleado' => 'tecnico',
        'employee' => 'tecnico',
    ];
    return $map[$r] ?? $r;
}

function repairly_role_can_panel(?string $rol): bool
{
    $r = repairly_role_canonical($rol);
    return $r !== '' && in_array($r, repairly_panel_roles(), true);
}

function repairly_is_admin(?string $rol): bool
{
    return repairly_role_canonical($rol) === 'administrador';
}

/**
 * Ajusta el valor de rol al tipo real de la columna (ENUM con otros literales, mayúsculas, etc.).
 * Evita "Data truncated for column 'rol'" cuando la BD no coincide con schema_usuario.sql.
 */
function repairly_resolve_rol_for_insert(mysqli $conn, string $table, string $rolColumn, string $logical): string
{
    $type = column_mysql_type($conn, $table, $rolColumn);
    if ($type === null || stripos(trim($type), 'enum(') !== 0) {
        return $logical;
    }
    $allowed = mysql_enum_values_from_type($type);
    if ($allowed === []) {
        return $logical;
    }
    $want = repairly_normalize_role($logical);
    foreach ($allowed as $a) {
        if (repairly_normalize_role($a) === $want) {
            return $a;
        }
    }
    foreach ($allowed as $a) {
        $n = repairly_normalize_role($a);
        if ($want === 'administrador' && str_contains($n, 'admin')) {
            return $a;
        }
    }
    foreach ($allowed as $a) {
        $n = repairly_normalize_role($a);
        if (
            $want === 'tecnico'
            && (str_contains($n, 'tecnic') || str_contains($n, 'emplead')
                || $n === 'operador' || str_contains($n, 'operad'))
        ) {
            return $a;
        }
    }
    foreach ($allowed as $a) {
        if (repairly_normalize_role($a) === 'cliente') {
            return $a;
        }
    }
    return $allowed[0];
}

/**
 * Valor y tipo mysqli para INSERT en `estado` según el tipo de columna (INT 0/1 vs VARCHAR/ENUM).
 *
 * @return array{0: string|int, 1: 'i'|'s'}
 */
function repairly_resolve_estado_for_insert(mysqli $conn, string $table, string $estadoColumn, string $logical = 'activo'): array
{
    $type = column_mysql_type($conn, $table, $estadoColumn);
    if ($type === null) {
        return [$logical, 's'];
    }
    $typeTrim = trim($type);
    if (mysql_type_is_integer_like($typeTrim)) {
        $want = repairly_normalize_role($logical);
        $active = ($want === '' || $want === 'activo' || $want === 'active' || $want === '1');
        return [$active ? 1 : 0, 'i'];
    }
    if (stripos($typeTrim, 'enum(') === 0) {
        $allowed = mysql_enum_values_from_type($typeTrim);
        if ($allowed === []) {
            return [$logical, 's'];
        }
        $want = repairly_normalize_role($logical);
        foreach ($allowed as $a) {
            if (repairly_normalize_role($a) === $want) {
                return [$a, 's'];
            }
        }
        foreach ($allowed as $a) {
            $n = repairly_normalize_role($a);
            if (str_contains($n, 'activ') || $n === '1' || str_contains($n, 'habil')) {
                return [$a, 's'];
            }
        }
        foreach ($allowed as $a) {
            if (repairly_normalize_role($a) === 'inactivo' || repairly_normalize_role($a) === 'bloqueado') {
                continue;
            }
            return [$a, 's'];
        }
        return [$allowed[0], 's'];
    }
    return [$logical, 's'];
}

function repairly_iniciales(string $text): string
{
    $t = trim($text);
    if ($t === '') {
        return '?';
    }
    if (preg_match_all('/\p{L}/u', $t, $m) && !empty($m[0])) {
        $letters = $m[0];
        $one = strtoupper($letters[0]);
        $two = isset($letters[1]) ? strtoupper($letters[1]) : '';
        return $two !== '' ? $one . $two : $one;
    }
    return strtoupper(mb_substr($t, 0, 2));
}

function repairly_usuario_table(mysqli $conn): string
{
    return pick_table($conn, ['usuario', 'Usuario', 'USUARIO']);
}

/** Clave primaria de la tabla de usuarios (esquemas con id_usuario o solo id). */
function repairly_usuario_id_field(array $cols): string
{
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, 'id_usuario') === 0) {
            return (string)$k;
        }
    }
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, 'id') === 0) {
            return (string)$k;
        }
    }
    return 'id_usuario';
}

/** Columna de rol en tabla Usuario (`rol`, `ROL`, `role`). */
function repairly_usuario_rol_column(array $cols): ?string
{
    foreach (['rol', 'ROL', 'role'] as $rk) {
        if (isset($cols[$rk])) {
            return $rk;
        }
    }
    return null;
}

/**
 * Actualiza rol de usuario (solo administradores). Compatible con ENUM/VARCHAR vía repairly_resolve_rol_for_insert.
 *
 * @param array $actorUsuarioRow Fila de repairly_load_usuario del usuario en sesión.
 * @return array{ok:bool, msg:string}
 */
function repairly_try_update_usuario_rol_admin(mysqli $conn, bool $auth_is_admin, array $actorUsuarioRow, int $targetUid, string $newRolInput): array
{
    if (!$auth_is_admin) {
        return ['ok' => false, 'msg' => 'Sin permiso'];
    }
    if ($targetUid <= 0) {
        return ['ok' => false, 'msg' => 'Usuario inválido'];
    }
    $allowed = ['administrador', 'tecnico', 'supervisor', 'operador', 'cliente', 'pendiente'];
    $normIn = repairly_normalize_role(trim($newRolInput));
    $logical = '';
    foreach ($allowed as $a) {
        if ($normIn === repairly_normalize_role($a)) {
            $logical = $a;
            break;
        }
    }
    if ($logical === '') {
        return ['ok' => false, 'msg' => 'Rol inválido'];
    }
    $actorId = (int)($actorUsuarioRow['id_usuario'] ?? 0);
    if ($targetUid === $actorId && repairly_normalize_role($logical) !== 'administrador') {
        return ['ok' => false, 'msg' => 'No puedes quitarte el rol administrador'];
    }
    $table = repairly_usuario_table($conn);
    if ($table === '') {
        return ['ok' => false, 'msg' => 'Tabla de usuarios no encontrada'];
    }
    $cols = table_columns($conn, $table);
    $rolCol = repairly_usuario_rol_column($cols);
    if ($rolCol === null) {
        return ['ok' => false, 'msg' => 'La tabla no tiene columna de rol'];
    }
    $idCol = repairly_usuario_id_field($cols);
    $storedRol = repairly_resolve_rol_for_insert($conn, $table, $rolCol, $logical);

    $sql = "UPDATE `{$table}` SET `{$rolCol}` = ? WHERE `{$idCol}` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['ok' => false, 'msg' => 'No se pudo guardar'];
    }
    $stmt->bind_param('si', $storedRol, $targetUid);
    $ok = $stmt->execute();
    if (!$ok) {
        $stmt->close();
        return ['ok' => false, 'msg' => 'No se pudo guardar el rol'];
    }
    $stmt->close();
    return ['ok' => true, 'msg' => 'Rol actualizado'];
}

/** @return array{id_usuario:int, username:string, rol:string, estado:string, id_tecnico:?int}|null */
function repairly_load_usuario(mysqli $conn, int $id): ?array
{
    $table = repairly_usuario_table($conn);
    if ($table === '') {
        return null;
    }
    $cols = table_columns($conn, $table);
    $idField = repairly_usuario_id_field($cols);
    $sql = "SELECT * FROM `{$table}` WHERE `{$idField}` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row || !isset($row[$idField])) {
        return null;
    }
    $uid = (int)$row[$idField];
    $username = '';
    foreach (['username', 'USERNAME', 'user', 'correo'] as $uk) {
        if (isset($row[$uk]) && $row[$uk] !== '') {
            $username = (string)$row[$uk];
            break;
        }
    }
    $rol = '';
    foreach (['rol', 'ROL', 'role'] as $rk) {
        if (isset($row[$rk])) {
            $rol = (string)$row[$rk];
            break;
        }
    }
    $estado = 'activo';
    foreach (['estado', 'ESTADO'] as $ek) {
        if (isset($row[$ek]) && $row[$ek] !== '') {
            $estado = (string)$row[$ek];
            break;
        }
    }
    $idTecnico = null;
    foreach (['id_tecnico', 'ID_TECNICO'] as $tk) {
        if (isset($row[$tk]) && $row[$tk] !== null && $row[$tk] !== '') {
            $idTecnico = (int)$row[$tk];
            break;
        }
    }
    return [
        'id_usuario' => $uid,
        'username' => $username,
        'rol' => $rol,
        'estado' => $estado,
        'id_tecnico' => $idTecnico,
    ];
}

function repairly_usuario_esta_activo(string|int|float|bool $estado): bool
{
    if ($estado === true) {
        return true;
    }
    if ($estado === false) {
        return false;
    }
    if (is_int($estado) || is_float($estado)) {
        return ((int)$estado) > 0;
    }
    $s = trim((string)$estado);
    if ($s === '' || $s === '0') {
        return false;
    }
    if (ctype_digit($s)) {
        return (int)$s > 0;
    }
    $e = repairly_normalize_role($s);
    if ($e === 'activo' || $e === 'active' || $e === 'habilitado' || $e === 'enabled' || $e === 'si' || $e === 'sí' || $e === 'ok') {
        return true;
    }
    if ($e === 'inactivo' || $e === 'inactive' || $e === 'bloqueado' || $e === 'deshabilitado' || $e === 'suspendido' || $e === 'no') {
        return false;
    }
    return false;
}

function repairly_usuario_count(mysqli $conn): int
{
    $table = repairly_usuario_table($conn);
    if ($table === '') {
        return 0;
    }
    return (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$table}`");
}

/**
 * Redirección HTTP segura: vacía buffers para que Location funcione (Railway, PHP -S).
 * Si algo imprimió antes y headers_sent(), muestra HTML de respaldo (evita pantalla en blanco).
 */
function repairly_redirect(string $url): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Location: ' . $url, true, 302);
        exit;
    }
    $jsonUrl = json_encode($url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $esc = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . $esc . '"><title>Redirigiendo</title></head><body style="font-family:system-ui;padding:2rem;"><p>Redirigiendo… Si no cambia la página, <a href="' . $esc . '">pulse aquí</a>.</p><script>location.replace(' . $jsonUrl . ');</script></body></html>';
    exit;
}
