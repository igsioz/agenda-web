<?php
session_start();

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET' && $metodo !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido.');
}

$idRecibido = $metodo === 'POST'
    ? ($_POST['id'] ?? null)
    : ($_GET['id'] ?? null);
$id = filter_var($idRecibido, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('El identificador del evento no es válido.');
}

$tokenCsrf = $_SESSION['token_csrf'] ?? null;
if (!is_string($tokenCsrf) || $tokenCsrf === '') {
    $tokenCsrf = bin2hex(random_bytes(32));
    $_SESSION['token_csrf'] = $tokenCsrf;
}

$mes = $metodo === 'POST'
    ? ($_POST['mes'] ?? date('Y-m'))
    : ($_GET['mes'] ?? date('Y-m'));
if (!is_string($mes) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) !== 1) {
    $mes = date('Y-m');
}

$errores = [];
$titulo = '';
$fecha = '';
$hora = '';
$categoria = '';
$eventoEncontrado = false;
$errorGeneral = false;

try {
    require 'conexion.php';

    $consulta = $mysqli->prepare(
        'SELECT titulo, fecha, hora, categoria FROM eventos WHERE id = ?'
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $consulta->bind_result($tituloGuardado, $fechaGuardada, $horaGuardada, $categoriaGuardada);
    $eventoEncontrado = $consulta->fetch();
    $consulta->close();

    if (!$eventoEncontrado) {
        $mysqli->close();
        http_response_code(404);
        exit('No se encontró el evento solicitado.');
    }

    $titulo = $tituloGuardado;
    $fecha = $fechaGuardada;
    $hora = $horaGuardada === null ? '' : substr($horaGuardada, 0, 5);
    $categoria = $categoriaGuardada;

    if ($metodo === 'POST') {
        $tokenEnviado = $_POST['token_csrf'] ?? '';
        if (
            !is_string($tokenEnviado)
            || !hash_equals($tokenCsrf, $tokenEnviado)
        ) {
            http_response_code(400);
            $errores['general'] = 'La solicitud expiró. Recarga la página e inténtalo de nuevo.';
        } else {
            $tituloEnviado = $_POST['titulo'] ?? '';
            $fechaEnviada = $_POST['fecha'] ?? '';
            $horaEnviada = $_POST['hora'] ?? '';
            $categoriaEnviada = $_POST['categoria'] ?? '';

            $titulo = is_string($tituloEnviado) ? trim($tituloEnviado) : '';
            $fecha = is_string($fechaEnviada) ? trim($fechaEnviada) : '';
            $hora = is_string($horaEnviada) ? trim($horaEnviada) : '';
            $categoria = is_string($categoriaEnviada) ? trim($categoriaEnviada) : '';

            if ($titulo === '') {
                $errores['titulo'] = 'Escribe el título del evento.';
            } elseif (preg_match('//u', $titulo) !== 1) {
                $errores['titulo'] = 'El título debe usar texto UTF-8 válido.';
            } elseif (preg_match_all('/./us', $titulo) > 120) {
                $errores['titulo'] = 'El título no puede tener más de 120 caracteres.';
            }

            $fechaValidada = DateTime::createFromFormat('!Y-m-d', $fecha);
            if ($fecha === '') {
                $errores['fecha'] = 'Elige una fecha.';
            } elseif (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) {
                $errores['fecha'] = 'Escribe una fecha válida.';
            }

            $horaValidada = DateTime::createFromFormat('!H:i', $hora);
            if ($hora === '') {
                $errores['hora'] = 'Elige una hora.';
            } elseif (!$horaValidada || $horaValidada->format('H:i') !== $hora) {
                $errores['hora'] = 'Escribe una hora válida.';
            }

            $categoriasPermitidas = ['trabajo', 'personal', 'estudio', 'ocio'];
            if (!in_array($categoria, $categoriasPermitidas, true)) {
                $errores['categoria'] = 'Elige una categoría válida.';
            }

            if ($errores === []) {
                $consulta = $mysqli->prepare(
                    'UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria = ? WHERE id = ?'
                );
                $consulta->bind_param('ssssi', $titulo, $fecha, $hora, $categoria, $id);
                $consulta->execute();
                $consulta->close();
                $mysqli->close();

                header('Location: index.php?editado=1&mes=' . rawurlencode($mes));
                exit;
            }
        }
    }

    $mysqli->close();
} catch (mysqli_sql_exception $excepcion) {
    if (isset($consulta) && $consulta instanceof mysqli_stmt) {
        $consulta->close();
    }
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }

    error_log('Error al cargar o editar evento: ' . $excepcion->getMessage());
    $errorGeneral = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Doto:wght@100..900&display=swap" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">
    <title>Editar evento · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title">Editar evento</h1>
    <p class="card__meta">Actualiza los datos del evento.</p>

    <?php if ($errorGeneral): ?>
        <div class="alert alert--error" role="alert">No se pudo cargar o actualizar el evento. Inténtalo de nuevo.</div>
    <?php endif; ?>

    <?php if (isset($errores['general'])): ?>
        <div class="alert alert--error" role="alert"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <?php if (!$errorGeneral && $eventoEncontrado): ?>
        <form method="post" action="">
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <input type="hidden" name="token_csrf" value="<?= e($tokenCsrf) ?>">
            <input type="hidden" name="mes" value="<?= e($mes) ?>">

            <div class="form-group">
                <label class="form-label" for="titulo">Título</label>
                <input type="text" name="titulo" id="titulo" class="form-input<?= isset($errores['titulo']) ? ' is-invalid' : '' ?>" value="<?= e($titulo) ?>" required maxlength="120">
                <?php if (isset($errores['titulo'])): ?>
                    <p class="form-error"><?= e($errores['titulo']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-input<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>" value="<?= e($fecha) ?>" required>
                    <?php if (isset($errores['fecha'])): ?>
                        <p class="form-error"><?= e($errores['fecha']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="hora">Hora</label>
                    <input type="time" name="hora" id="hora" class="form-input<?= isset($errores['hora']) ? ' is-invalid' : '' ?>" value="<?= e($hora) ?>" required step="60">
                    <?php if (isset($errores['hora'])): ?>
                        <p class="form-error"><?= e($errores['hora']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="categoria">Categoría</label>
                <select name="categoria" id="categoria" class="form-select<?= isset($errores['categoria']) ? ' is-invalid' : '' ?>" required>
                    <option value=""<?= $categoria === '' ? ' selected' : '' ?>>— Elige una —</option>
                    <option value="trabajo"<?= $categoria === 'trabajo' ? ' selected' : '' ?>>Trabajo</option>
                    <option value="personal"<?= $categoria === 'personal' ? ' selected' : '' ?>>Personal</option>
                    <option value="estudio"<?= $categoria === 'estudio' ? ' selected' : '' ?>>Estudio</option>
                    <option value="ocio"<?= $categoria === 'ocio' ? ' selected' : '' ?>>Ocio / Deporte</option>
                </select>
                <?php if (isset($errores['categoria'])): ?>
                    <p class="form-error"><?= e($errores['categoria']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Guardar cambios</button>
        </form>
    <?php endif; ?>

    <p class="card__link"><a href="index.php?mes=<?= rawurlencode($mes) ?>">Volver a mis eventos</a></p>
</div>
</body>
</html>