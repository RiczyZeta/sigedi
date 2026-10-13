# Instalación del Sprint 3 (Gestión de incidencias)

El Sprint 3 no agrega migraciones ni librerías: las tablas ya existen desde el Sprint 1.

## Pasos

1. Descomprime `sigedi-sprint3.zip` en una carpeta.
2. En la terminal de Laragon, dentro de `C:\laragon\www\sigedi`, copia el código encima del proyecto
   (cambia la ruta por la de tu carpeta; es una sola línea):
   ```
   xcopy "RUTA\DE\LA\CARPETA\sigedi-sprint3" "C:\laragon\www\sigedi" /E /Y /I
   ```
3. Ejecuta las pruebas:
   ```
   php artisan test
   ```
4. Prueba el flujo completo en `http://sigedi.test` con los tres roles (usa ventanas distintas o cierra sesión entre cada paso):

   | Paso | Entra como | Qué hacer |
   |---|---|---|
   | 1 | `docente@ina54.edu.pe` / `Docente2026*` | Incidencias → Reportar incidencia. Elige un dispositivo, impacto y urgencia. Anota el código INC-AAAA-001. |
   | 2 | `admin@ina54.edu.pe` / `Admin2026*` | Abre la incidencia y **asigna** al técnico de demostración. |
   | 3 | `tecnico@ina54.edu.pe` / `Tecnico2026*` | Cambia el estado a **En atención**, luego a **Resuelta** escribiendo la solución, y finalmente a **Cerrada**. |
   | 4 | `docente@ina54.edu.pe` | Abre **Mis incidencias** y verifica que ve el estado actual. Prueba abrir la incidencia de otro usuario: error 403. |

   Antes del paso 1 necesitas al menos un dispositivo registrado (Sprint 2).

## Capturas para el informe

El formulario de reporte, el listado con prioridades de colores, el detalle de la incidencia en cada estado
(Registrada, Asignada, En atención, Resuelta y Cerrada), el mensaje de error al intentar cerrar sin solución y el resultado de `php artisan test`.

## Subir a GitHub

```
git add .
git commit -m "Sprint 3: gestion de incidencias con ITIL 4 (registro, prioridad, asignacion, estados y consulta)"
git push
```
