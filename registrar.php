<?php
/* ════════════════════════════════════════════════════════
   registrar.php · AgendaWeb (kit de rescate P2–P3)
   Formulario (P2) + procesamiento (P3) en un solo archivo.
   Cada TODO te dice en qué paso de la guía se completa.
   ════════════════════════════════════════════════════════ */

// Función de ayuda: limpia un texto antes de mostrarlo en la página.
// Evita que alguien "inyecte" HTML o JavaScript (ataque XSS).
// Ejemplo:  echo e($titulo);
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$titulo = '';
$fecha = '';
$hora = '';
$categoria = '';
$errores = [];

// Lee y valida los datos solo cuando se envió el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    if ($titulo === '') {
        $errores['titulo'] = 'Escribe el título del evento.';
    } elseif (preg_match('//u', $titulo) !== 1) {
        $errores['titulo'] = 'El título debe usar texto UTF-8 válido.';
    } elseif (preg_match_all('/./us', $titulo) > 120) {
        $errores['titulo'] = 'El título no puede tener más de 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'Elige una fecha.';
    } else {
        $fechaValidada = DateTime::createFromFormat('!Y-m-d', $fecha);
        if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'Escribe una fecha válida.';
        }
    }

    if ($hora === '') {
        $errores['hora'] = 'Elige una hora.';
    } else {
        $horaValidada = DateTime::createFromFormat('!H:i', $hora);
        if (!$horaValidada || $horaValidada->format('H:i') !== $hora) {
            $errores['hora'] = 'Escribe una hora válida.';
        }
    }

    $categoriasPermitidas = ['trabajo', 'personal', 'estudio', 'ocio'];
    if (!in_array($categoria, $categoriasPermitidas, true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errores === []) {
    $mysqli = null;
    $consulta = null;

    try {
        require 'conexion.php';

        $consulta = $mysqli->prepare(
            'INSERT INTO eventos (titulo, fecha, hora, categoria) VALUES (?, ?, ?, ?)'
        );
        $consulta->bind_param('ssss', $titulo, $fecha, $hora, $categoria);
        $consulta->execute();
        $consulta->close();
        $mysqli->close();

        header('Location: index.php?ok=1');
        exit;
    } catch (mysqli_sql_exception $excepcion) {
        if ($consulta instanceof mysqli_stmt) {
            $consulta->close();
        }
        if ($mysqli instanceof mysqli) {
            $mysqli->close();
        }

        $errores['general'] = 'No se pudo guardar.';
    }
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
    <title>Nuevo evento · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title">Nuevo evento</h1>
    <p class="card__meta">AgendaWeb de Ignacio Emanuel Dávila Gutiérrez</p>

    <?php if (isset($errores['general'])): ?>
        <div class="alert alert--error"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="">
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

        <button type="submit" class="btn-primary">Guardar evento</button>
    </form>

    <p class="card__link"><a href="index.php">Ver mis eventos</a></p>
</div>
</body>
</html>
