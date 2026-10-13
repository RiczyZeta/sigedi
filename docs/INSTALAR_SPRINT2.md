# Instalación del Sprint 2 (Inventario de dispositivos)

El Sprint 2 no agrega migraciones: las tablas ya existen desde el Sprint 1.
Solo agrega código, vistas, pruebas y **una librería** para leer y generar Excel.

## Pasos

1. Descomprime `sigedi-sprint2.zip` en una carpeta.
2. En la terminal de Laragon, dentro de `C:\laragon\www\sigedi`, instala la librería de Excel
   (necesita internet y tarda 1 o 2 minutos):
   ```
   composer require phpoffice/phpspreadsheet
   ```
3. Copia el código encima del proyecto (reemplaza los archivos existentes). Cambia la ruta por la de tu carpeta:
   ```
   xcopy "RUTA\DE\LA\CARPETA\sigedi-sprint2" "C:\laragon\www\sigedi" /E /Y /I
   ```
4. Ejecuta las pruebas:
   ```
   php artisan test
   ```
   Deben pasar las pruebas del Sprint 1 y las nuevas.
5. Prueba el sistema en `http://sigedi.test`:
   - Entra como `admin@ina54.edu.pe`, abre **Dispositivos** y registra uno.
   - Cambia su estado desde el listado y filtra por estado.
   - Abre **Importar desde Excel**, descarga la plantilla, llénala con 3 o 4 equipos (deja una fila con error a propósito) y súbela.
   - Entra como `tecnico@ina54.edu.pe`: puede gestionar dispositivos pero no ve **Importar desde Excel**.
   - Entra como `docente@ina54.edu.pe` y abre `/dispositivos`: debe dar error 403.

## Antes de importar el inventario real (45 equipos)

- Registra primero los ambientes reales de la I.E. (hoy son los de ejemplo de `UbicacionSeeder`)
  y los usuarios que serán responsables.
- En la columna `ubicacion` de la plantilla, escribe el nombre exacto del ambiente.
- En `responsable_correo`, el correo del usuario registrado (opcional).

## Subir a GitHub

```
git add .
git commit -m "Sprint 2: inventario de dispositivos, ubicacion, responsable, estado e importacion desde Excel"
git push
```
