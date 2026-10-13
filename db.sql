-- ════════════════════════════════════════════════════════
-- db.sql · AgendaWeb · base y tabla de eventos
-- En MySQL Workbench: pega todo en una pestaña de consulta y ejecuta con el rayo ⚡
-- (o en la terminal: mysql -u root -p  y pégalo en mysql>)
-- ════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS agenda2 CHARACTER SET utf8mb4;
USE agenda2;

CREATE TABLE IF NOT EXISTS eventos (
  id          INT AUTO_INCREMENT PRIMARY KEY,   -- número único de cada evento
  titulo      VARCHAR(120) NOT NULL,            -- obligatorio
  fecha       DATE         NOT NULL,            -- obligatorio
  hora        TIME         NULL,                -- permite eventos antiguos sin hora
  categoria   VARCHAR(20)  NOT NULL,            -- obligatorio
  descripcion VARCHAR(500) NULL,                -- opcional (reto extra)
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Agrega la columna en bases existentes creadas antes del campo de hora.
SET @agendawed_add_hora = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE eventos ADD COLUMN hora TIME NULL AFTER fecha',
    'SELECT 1'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'eventos'
    AND COLUMN_NAME = 'hora'
);
PREPARE agendawed_stmt FROM @agendawed_add_hora;
EXECUTE agendawed_stmt;
DEALLOCATE PREPARE agendawed_stmt;
