<?php
session_start();

/* ════════════════════════════════════════════════════════
   index.php · Página principal de AgendaWeb
   Muestra los eventos guardados en la base de datos.
   ════════════════════════════════════════════════════════ */

// registrar.php te manda aquí con ?ok=1 cuando guarda bien
$guardado = isset($_GET['ok']);
$editado = isset($_GET['editado']);
$borrado = isset($_GET['borrado']);
$errorBorrado = isset($_GET['error']) && $_GET['error'] === 'borrar';
$tokenCsrf = $_SESSION['token_csrf'] ?? null;
if (!is_string($tokenCsrf) || $tokenCsrf === '') {
    $tokenCsrf = bin2hex(random_bytes(32));
    $_SESSION['token_csrf'] = $tokenCsrf;
}
$eventos = [];
$eventosPorDia = [];
$errorCarga = false;

$mesSolicitado = $_GET['mes'] ?? date('Y-m');
if (
    !is_string($mesSolicitado)
    || preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/D', $mesSolicitado, $coincidencias) !== 1
    || (int) $coincidencias[1] < 1900
    || (int) $coincidencias[1] > 2100
) {
    $mesSolicitado = date('Y-m');
}

$mesActual = DateTimeImmutable::createFromFormat('!Y-m-d', $mesSolicitado . '-01');
if (!$mesActual) {
    $mesActual = new DateTimeImmutable('first day of this month');
}

$mesAnterior = $mesActual->modify('-1 month')->format('Y-m');
$mesSiguiente = $mesActual->modify('+1 month')->format('Y-m');
$inicioSemana = (int) $mesActual->format('w');
$diasEnMes = (int) $mesActual->format('t');
$cantidadCeldas = (int) ceil(($inicioSemana + $diasEnMes) / 7) * 7;
$hoy = date('Y-m-d');
$meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];
$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

try {
    require 'conexion.php';
    $resultado = $mysqli->query(
        'SELECT id, titulo, fecha, hora, categoria FROM eventos ORDER BY fecha, hora, id'
    );

    while ($evento = $resultado->fetch_assoc()) {
        $eventos[] = $evento;
        $eventosPorDia[$evento['fecha']][] = $evento;
    }

    $resultado->free();
    $mysqli->close();
} catch (mysqli_sql_exception $excepcion) {
    if (isset($resultado) && $resultado instanceof mysqli_result) {
        $resultado->free();
    }
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }

    $errorCarga = true;
}

$nombresCategoria = [
    'trabajo' => 'Trabajo',
    'personal' => 'Personal',
    'estudio' => 'Estudio',
    'ocio' => 'Ocio / Deporte',
];

function formatoHora(?string $hora): string
{
    if ($hora === null) {
        return 'Sin hora';
    }

    $partes = explode(':', $hora);
    $hora24 = (int) $partes[0];
    $minutos = (int) ($partes[1] ?? 0);
    $hora12 = $hora24 % 12 ?: 12;
    $periodo = $hora24 >= 12 ? 'p. m.' : 'a. m.';

    return sprintf('%d:%02d %s', $hora12, $minutos, $periodo);
}

$tituloMes = $meses[(int) $mesActual->format('n')] . ' ' . $mesActual->format('Y');
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
    <title>Agenda · AgendaWeb</title>
</head>
<body>
<div class="card card--events">
    <header class="calendar-header">
        <div>
            <p class="calendar-eyebrow">AgendaWeb</p>
            <h1 class="card__title">Mis eventos</h1>
        </div>
        <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </header>

    <?php if ($guardado): ?>
        <div class="alert alert--ok" role="status">&#9989; Evento guardado correctamente.</div>
    <?php endif; ?>
    <?php if ($editado): ?>
        <div class="alert alert--ok" role="status">Evento actualizado correctamente.</div>
    <?php endif; ?>
    <?php if ($borrado): ?>
        <div class="alert alert--ok" role="status">Evento eliminado correctamente.</div>
    <?php endif; ?>
    <?php if ($errorBorrado): ?>
        <div class="alert alert--error" role="alert">No se pudo eliminar el evento. Inténtalo de nuevo.</div>
    <?php endif; ?>

    <?php if ($errorCarga): ?>
        <div class="alert alert--error" role="alert">No se pudieron cargar los eventos. Revisa la conexión y la tabla de la base de datos.</div>
    <?php else: ?>
        <section class="calendar" aria-labelledby="calendar-title">
            <div class="calendar__toolbar">
                <h2 class="calendar__month" id="calendar-title"><?= htmlspecialchars($tituloMes, ENT_QUOTES, 'UTF-8') ?></h2>
                <nav class="calendar__navigation" aria-label="Navegación del calendario">
                    <a class="calendar__nav-button" href="?mes=<?= htmlspecialchars($mesAnterior, ENT_QUOTES, 'UTF-8') ?>" aria-label="Mes anterior">&lsaquo;</a>
                    <a class="calendar__today" href="?mes=<?= date('Y-m') ?>">Hoy</a>
                    <a class="calendar__nav-button" href="?mes=<?= htmlspecialchars($mesSiguiente, ENT_QUOTES, 'UTF-8') ?>" aria-label="Mes siguiente">&rsaquo;</a>
                </nav>
            </div>

            <div class="calendar__grid" aria-label="<?= htmlspecialchars('Calendario de ' . $tituloMes, ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($diasSemana as $diaSemana): ?>
                    <div class="calendar__weekday"><?= htmlspecialchars($diaSemana, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>

                <?php for ($celda = 0; $celda < $cantidadCeldas; $celda++): ?>
                    <?php
                    $numeroDia = $celda - $inicioSemana + 1;
                    $esDiaDelMes = $numeroDia >= 1 && $numeroDia <= $diasEnMes;
                    $fechaCelda = $esDiaDelMes
                        ? $mesActual->format('Y-m-') . str_pad((string) $numeroDia, 2, '0', STR_PAD_LEFT)
                        : '';
                    $claseDia = 'calendar__day';
                    if (!$esDiaDelMes) {
                        $claseDia .= ' calendar__day--outside';
                    } elseif ($fechaCelda === $hoy) {
                        $claseDia .= ' calendar__day--today';
                    }
                    ?>
                    <div class="<?= $claseDia ?>"<?= $esDiaDelMes ? ' aria-label="' . htmlspecialchars($numeroDia . ' de ' . $tituloMes, ENT_QUOTES, 'UTF-8') . '"' : ' aria-hidden="true"' ?>>
                        <?php if ($esDiaDelMes): ?>
                            <span class="calendar__day-number"><?= $numeroDia ?></span>
                            <?php foreach ($eventosPorDia[$fechaCelda] ?? [] as $evento): ?>
                                <?php $claseEvento = isset($nombresCategoria[$evento['categoria']]) ? ' calendar-event--' . $evento['categoria'] : ''; ?>
                                <div class="calendar-event<?= $claseEvento ?>" title="<?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($evento['hora'] !== null): ?>
                                        <span class="calendar-event__time"><?= htmlspecialchars(formatoHora($evento['hora']), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <span class="calendar-event__title"><?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <?php if ($eventos === []): ?>
                <p class="calendar__empty">Todavía no hay eventos. Usa “Nuevo evento” para agregar uno.</p>
            <?php endif; ?>
        </section>
        <p class="calendar__hint">Los eventos se muestran en el día correspondiente a su fecha.</p>

        <section class="saved-events" aria-labelledby="saved-events-title">
            <div class="saved-events__header">
                <h2 class="saved-events__heading" id="saved-events-title">Todos los eventos</h2>
                <span class="saved-events__count"><?= count($eventos) ?></span>
            </div>

            <?php if ($eventos === []): ?>
                <p class="saved-events__empty">Cuando guardes un evento, aparecerá aquí.</p>
            <?php else: ?>
                <div class="saved-events__list">
                    <?php foreach ($eventos as $evento): ?>
                        <?php $claseEventoGuardado = isset($nombresCategoria[$evento['categoria']]) ? ' saved-event--' . $evento['categoria'] : ''; ?>
                        <article class="saved-event<?= $claseEventoGuardado ?>" aria-labelledby="saved-event-title-<?= (int) $evento['id'] ?>">
                            <div class="saved-event__main">
                                <h3 class="saved-event__title" id="saved-event-title-<?= (int) $evento['id'] ?>"><?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="saved-event__details">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($evento['fecha'])), ENT_QUOTES, 'UTF-8') ?>
                                    <span aria-hidden="true">·</span>
                                    <?= htmlspecialchars(formatoHora($evento['hora']), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <span class="saved-event__category"><?= htmlspecialchars($nombresCategoria[$evento['categoria']] ?? $evento['categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="saved-event__actions">
                                <a class="saved-event__edit" href="editar.php?id=<?= (int) $evento['id'] ?>&amp;mes=<?= htmlspecialchars($mesActual->format('Y-m'), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                <form class="saved-event__delete-form" action="borrar.php" method="post" onsubmit="return confirm('¿Seguro que quieres borrar este evento?');">
                                    <input type="hidden" name="id" value="<?= (int) $evento['id'] ?>">
                                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="mes" value="<?= htmlspecialchars($mesActual->format('Y-m'), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="saved-event__delete" type="submit" aria-label="Borrar <?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?>">Borrar</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
