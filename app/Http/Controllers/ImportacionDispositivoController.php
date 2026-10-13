<?php

namespace App\Http\Controllers;

use App\Services\ImportadorDispositivos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** HU06 - Importar el inventario desde Excel (solo administrador). */
class ImportacionDispositivoController extends Controller
{
    public function create(): View
    {
        return view('dispositivos.importar');
    }

    public function store(Request $request, ImportadorDispositivos $importador): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'extensions:xlsx', 'max:2048'],
        ], [
            'archivo.required' => 'Selecciona el archivo Excel a importar.',
            'archivo.file' => 'El archivo no se pudo subir. Intenta nuevamente.',
            'archivo.extensions' => 'El archivo debe ser un Excel con extensión .xlsx.',
            'archivo.max' => 'El archivo no debe superar 2 MB.',
        ]);

        try {
            $resultado = $importador->importar($request->file('archivo')->getRealPath());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('resultado', $resultado);
    }

    /** Descarga la plantilla .xlsx con los encabezados esperados y una hoja de instrucciones. */
    public function plantilla(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $libro = new Spreadsheet();

            $hoja = $libro->getActiveSheet();
            $hoja->setTitle('Dispositivos');
            $hoja->fromArray([ImportadorDispositivos::COLUMNAS], null, 'A1');
            $hoja->getStyle('A1:I1')->getFont()->setBold(true);
            foreach (range('A', 'I') as $columna) {
                $hoja->getColumnDimension($columna)->setAutoSize(true);
            }

            $ayuda = $libro->createSheet();
            $ayuda->setTitle('Instrucciones');
            $ayuda->fromArray([
                ['Columna', 'Obligatoria', 'Qué escribir'],
                ['codigo_patrimonial', 'Sí', 'Código único del equipo. Ejemplo: PC-001'],
                ['tipo', 'Sí', 'Computadora, Laptop, Impresora, Proyector, etc.'],
                ['marca', 'No', 'Ejemplo: HP'],
                ['modelo', 'No', 'Ejemplo: ProDesk 400'],
                ['serie', 'No', 'Número de serie del fabricante'],
                ['caracteristicas', 'No', 'Procesador, memoria, disco, etc.'],
                ['estado', 'No', 'operativo, en mantenimiento, inoperativo o de baja. Si se deja vacío: operativo'],
                ['ubicacion', 'No', 'Nombre EXACTO de un ambiente ya registrado en el sistema'],
                ['responsable_correo', 'No', 'Correo de un usuario ya registrado en el sistema'],
                [],
                ['Escribe los datos en la hoja «Dispositivos», desde la fila 2. No cambies los encabezados de la fila 1.'],
            ], null, 'A1');
            $ayuda->getStyle('A1:C1')->getFont()->setBold(true);
            foreach (['A', 'B', 'C'] as $columna) {
                $ayuda->getColumnDimension($columna)->setAutoSize(true);
            }

            $libro->setActiveSheetIndex(0);
            (new Xlsx($libro))->save('php://output');
        }, 'plantilla_dispositivos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
