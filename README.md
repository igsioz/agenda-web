# AgendaWeb

Agenda web sencilla en PHP y MySQL para registrar, consultar, editar y borrar eventos. La página principal muestra los eventos en un calendario mensual.

## Requisitos

- PHP con las extensiones `mysqli` y `mysqlnd`.
- MySQL.

## Configuración local

1. Ejecuta [`db.sql`](db.sql) en MySQL Workbench para crear la base `agenda2` y la tabla `eventos`.
2. Copia `conexion.ejemplo.php` como `conexion.php`.
3. En `conexion.php`, reemplaza `TU_USUARIO_MYSQL` y `TU_CONTRASENA_MYSQL` por los datos de tu MySQL local. No publiques ese archivo ni compartas sus credenciales.
4. Desde esta carpeta, inicia el servidor local:

   ```bash
   php -S localhost:8000
   ```

5. Abre http://localhost:8000 en el navegador.

## Funciones

- Registrar eventos con título, fecha, hora y categoría.
- Verlos en un calendario mensual y en una lista.
- Editar o borrar eventos.
- Distinguir categorías mediante colores.

`conexion.php` contiene credenciales locales y está excluido por `.gitignore`. Usa la plantilla de ejemplo para configurar tu propia conexión.
