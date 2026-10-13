# Instalación del Sprint 1 (Windows + Laragon)

## Requisitos
Laragon Full (PHP 8.2 o superior, MySQL, Composer, Node.js), Git, Visual Studio Code.

## Pasos

1. Abre **Laragon** → clic en **Iniciar todo**.
2. Abre la terminal de Laragon (botón **Terminal**) y ejecuta:
   ```
   cd C:\laragon\www
   composer create-project laravel/laravel sigedi "^12.0"
   ```
3. Copia el contenido de este paquete **dentro** de `C:\laragon\www\sigedi`, aceptando **reemplazar** los archivos existentes
   (`routes/web.php`, `database/seeders/DatabaseSeeder.php`, `tests/Feature/ExampleTest.php`
   y `database/migrations/0001_01_01_000000_create_users_table.php`).
4. Crea la base de datos:
   ```
   mysql -u root -e "CREATE DATABASE sigedi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```
5. Edita el archivo `.env` del proyecto:
   ```
   APP_NAME=SIGEDI
   APP_LOCALE=es
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sigedi
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   (En Laragon, `root` no tiene contraseña por defecto.)
6. Crea las tablas y los datos iniciales:
   ```
   cd sigedi
   php artisan migrate:fresh --seed
   ```
7. Ejecuta las pruebas automáticas:
   ```
   php artisan test
   ```
8. Abre el sistema en `http://sigedi.test` (Laragon crea el dominio solo) o ejecuta `php artisan serve` y entra a `http://127.0.0.1:8000`.

## Usuarios iniciales

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@ina54.edu.pe | Admin2026* |
| Técnico (demo) | tecnico@ina54.edu.pe | Tecnico2026* |
| Usuario (demo) | docente@ina54.edu.pe | Docente2026* |

Cambia la contraseña del administrador y elimina los usuarios de demostración antes de entregar el sistema.

## Subir a GitHub (HT01)

1. Crea un repositorio vacío en GitHub llamado `sigedi` (sin README).
2. En la terminal, dentro de `C:\laragon\www\sigedi`:
   ```
   git init
   git add .
   git commit -m "Sprint 1: arquitectura MVC, base de datos, login, usuarios y roles"
   git branch -M main
   git remote add origin https://github.com/TU_USUARIO/sigedi.git
   git push -u origin main
   ```
   El archivo `.env` no se sube (ya está en `.gitignore`).
