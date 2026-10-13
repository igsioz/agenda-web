<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$tokenEnviado = $_POST['token_csrf'] ?? '';
$tokenSesion = $_SESSION['token_csrf'] ?? '';
$mes = $_POST['mes'] ?? date('Y-m');

if (
    $id === false
    || $id === null
    || !is_string($tokenEnviado)
    || !is_string($tokenSesion)
    || $tokenSesion === ''
    || !hash_equals($tokenSesion, $tokenEnviado)
) {
    http_response_code(400);
    exit('Solicitud inválida.');
}

if (
    !is_string($mes)
    || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) !== 1
) {
    $mes = date('Y-m');
}

try {
    require 'conexion.php';
    $consulta = $mysqli->prepare('DELETE FROM eventos WHERE id = ?');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $eliminado = $consulta->affected_rows === 1;
    $consulta->close();
    $mysqli->close();

    if (!$eliminado) {
        header('Location: index.php?error=borrar&mes=' . rawurlencode($mes));
        exit;
    }

    header('Location: index.php?borrado=1&mes=' . rawurlencode($mes));
    exit;
} catch (mysqli_sql_exception $excepcion) {
    if (isset($consulta) && $consulta instanceof mysqli_stmt) {
        $consulta->close();
    }
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }

    error_log('Error al borrar evento: ' . $excepcion->getMessage());
    header('Location: index.php?error=borrar&mes=' . rawurlencode($mes));
    exit;
}
