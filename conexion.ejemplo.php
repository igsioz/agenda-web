<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(
        'localhost',
        'TU_USUARIO_MYSQL',
        'TU_CONTRASENA_MYSQL',
        'agenda2'
    );
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $excepcion) {
    http_response_code(500);
    error_log('No se pudo conectar a la base de datos: ' . $excepcion->getMessage());
    exit('No se pudo conectar a la base de datos. Revisa la configuración local.');
}
