# Instalación del Sprint 4 (Búsqueda, ficha del dispositivo y bitácora digital)

Historias: **HU07** (búsqueda), **HU13** (bitácora automática), **HU14** (mantenimientos manuales) y **HU15** (ficha del dispositivo).
Agrega **una migración** (`fecha` en `bitacoras`) y ninguna librería nueva.

## Pasos

1. En la terminal de Laragon, dentro de `C:\laragon\www\sigedi`:
   ```
   php artisan migrate
   php artisan optimize:clear
   ```
2. Verifica la zona horaria en `config/app.php` → `'timezone' => 'America/Lima'`
   (la bitácora y la validación «la fecha no puede ser futura» usan la hora de la aplicación).
3. Ejecuta las pruebas:
   ```
   php artisan test
   ```
4. Recorre el incremento en `http://sigedi.test`:

   | Paso | Entra como | Qué hacer |
   |---|---|---|
   | 1 | `tecnico@ina54.edu.pe` | **Dispositivos** → busca `PC` (coincidencia parcial) y combina tipo + ubicación + estado. Pasa a la página 2: los filtros se conservan. |
   | 2 | `tecnico@ina54.edu.pe` | Abre la **Ficha** de un equipo → registra un mantenimiento **preventivo** con fecha de ayer. |
   | 3 | `tecnico@ina54.edu.pe` | Cambia el estado del equipo desde el listado → vuelve a la ficha: aparece «Cambio de estado» en la bitácora. |
   | 4 | `docente@ina54.edu.pe` | Reporta una incidencia de ese equipo. |
   | 5 | `admin@ina54.edu.pe` | Asigna la incidencia; el técnico la atiende, resuelve y cierra. En el detalle de la incidencia revisa el **Historial**. |
   | 6 | `admin@ina54.edu.pe` | Menú **Bitácora** → filtra por tipo «Manual», por acción «Cierre» y por rango de fechas. |
   | 7 | `docente@ina54.edu.pe` | Intenta abrir `/bitacora` o `/dispositivos/1`: error 403. En **Mis incidencias** usa el buscador. |

## Capturas para el informe

Buscador de dispositivos con filtros combinados, buscador de incidencias, ficha del dispositivo (datos, incidencias y bitácora),
formulario de mantenimiento con un error de validación, módulo Bitácora filtrado, historial de una incidencia y resultado de `php artisan test`.

## Subir a GitHub

```
git add .
git commit -m "Sprint 4: busqueda de dispositivos e incidencias, ficha del dispositivo y bitacora digital"
git push
```
