# Instalación del diseño institucional (sin cambios de lógica)

Incluye: escudo de la I.E. INA 54, colores del escudo, login con SIGEDI en grande, barra superior, panel con contadores y favicon.
No hay migraciones ni librerías nuevas. Las pruebas existentes no cambian.

## Pasos

1. Descomprime `sigedi-diseno.zip`.
2. En la terminal de Laragon, dentro de `C:\laragon\www\sigedi` (una sola línea; cambia la ruta):
   ```
   xcopy "RUTA\DE\LA\CARPETA\sigedi-diseno" "C:\laragon\www\sigedi" /E /Y /I
   ```
3. `php artisan test` (deben seguir pasando todas) y abre `http://sigedi.test` con Ctrl+F5.

Necesita internet para cargar Bootstrap, los íconos y la tipografía Inter (CDN).

## Archivos

| Ruta | Qué es |
|---|---|
| `public/img/insignia.png` | Escudo en alta resolución con fondo transparente (login) |
| `public/img/insignia-256.png` | Escudo mediano (barra, panel, pie) |
| `public/img/favicon.png`, `favicon.ico` | Ícono de la pestaña |
| `public/css/sigedi.css` | Tema: colores del escudo (verde, amarillo, rojo) |
| `resources/views/layouts/app.blade.php`, `auth/login.blade.php`, `panel.blade.php` | Vistas rediseñadas |
| `app/Http/Controllers/DashboardController.php` | Contadores del panel según el rol |

## Capturas para el informe

Login (escritorio y celular), panel del administrador, panel del usuario y barra superior con el rol.

## Subir a GitHub

```
git add .
git commit -m "Diseno institucional: insignia, tema de colores, login y panel con contadores"
git push
```
