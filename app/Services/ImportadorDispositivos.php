<?php

namespace App\Services;

use App\Models\Dispositivo;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Exception as ExcelException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * HU06 - Importación del inventario desde Excel (.xlsx).
 * Las filas con errores se informan (con su número de fila) sin detener la importación de las filas válidas.
 */
class ImportadorDispositivos
{
    /** Columnas de la plantilla, en este orden. */
    public const COLUMNAS = [
        'codigo_patrimonial',
        'tipo',
        'marca',
        'modelo',
        'serie',
        'caracteristicas',
        'estado',
        'ubicacion',
        'responsable_correo',
    ];

    private const OBLIGATORIAS = ['codigo_patrimonial', 'tipo'];

    /**
     * @return array{total:int, creados:int, errores:array<int, array{fila:int, mensaje:string}>}
     *
     * @throws InvalidArgumentException si el archivo no es un .xlsx válido o no tiene las columnas obligatorias
     */
    public function importar(string $ruta): array
    {
        try {
            $lector = IOFactory::createReader('Xlsx');
            $lector->setReadDataOnly(true);
            $hoja = $lector->load($ruta)->getActiveSheet();
        } catch (ExcelException $e) {
            throw new InvalidArgumentException('El archivo no es un Excel (.xlsx) válido.');
        }

        $filas = $hoja->toArray(null, true, true, false);
        $encabezado = array_map(fn ($c) => Str::lower(trim((string) $c)), array_shift($filas) ?? []);

        $faltantes = array_diff(self::OBLIGATORIAS, $encabezado);
        if ($faltantes) {
            throw new InvalidArgumentException(
                'Faltan columnas obligatorias: '.implode(', ', $faltantes).'. Descarga la plantilla y usa sus encabezados.'
            );
        }

        // Listas de referencia en minúsculas para comparar sin distinguir mayúsculas
        $ubicaciones = Ubicacion::all()->mapWithKeys(fn ($u) => [Str::lower($u->nombre) => $u->id])->all();
        $usuarios = User::all()->mapWithKeys(fn ($u) => [Str::lower($u->email) => $u->id])->all();
        $codigosExistentes = Dispositivo::pluck('codigo_patrimonial')
            ->mapWithKeys(fn ($c) => [Str::lower($c) => true])->all();
        $codigosVistos = [];   // código => fila donde se creó

        $total = 0;
        $creados = 0;
        $errores = [];

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;   // la fila 1 es el encabezado

            $datos = [];
            foreach ($encabezado as $posicion => $columna) {
                if ($columna !== '') {
                    $datos[$columna] = trim((string) ($fila[$posicion] ?? ''));
                }
            }

            if (implode('', $datos) === '') {
                continue;   // fila vacía
            }

            $total++;

            // "En mantenimiento" -> en_mantenimiento; vacío -> operativo
            $estado = Str::slug($datos['estado'] ?? '', '_');
            $datos['estado'] = $estado === '' ? 'operativo' : $estado;

            $mensajes = Validator::make($datos, [
                'codigo_patrimonial' => ['required', 'max:40'],
                'tipo' => ['required', 'max:40'],
                'marca' => ['nullable', 'max:60'],
                'modelo' => ['nullable', 'max:80'],
                'serie' => ['nullable', 'max:80'],
                'caracteristicas' => ['nullable', 'max:2000'],
                'estado' => [Rule::in(Dispositivo::ESTADOS)],
            ], [
                'codigo_patrimonial.required' => 'Falta el código patrimonial.',
                'tipo.required' => 'Falta el tipo de dispositivo.',
                'max' => 'El campo :attribute es demasiado largo.',
                'estado.in' => 'El estado debe ser: operativo, en mantenimiento, inoperativo o de baja.',
            ])->errors()->all();

            $codigoClave = Str::lower($datos['codigo_patrimonial'] ?? '');
            if ($codigoClave !== '') {
                if (isset($codigosExistentes[$codigoClave])) {
                    $mensajes[] = 'El código patrimonial ya está registrado en el sistema.';
                } elseif (isset($codigosVistos[$codigoClave])) {
                    $mensajes[] = "El código patrimonial está repetido en el archivo (fila {$codigosVistos[$codigoClave]}).";
                }
            }

            $ubicacionId = null;
            $nombreUbicacion = $datos['ubicacion'] ?? '';
            if ($nombreUbicacion !== '') {
                $ubicacionId = $ubicaciones[Str::lower($nombreUbicacion)] ?? null;
                if (! $ubicacionId) {
                    $mensajes[] = "La ubicación «{$nombreUbicacion}» no existe en la lista de ambientes.";
                }
            }

            $responsableId = null;
            $correo = $datos['responsable_correo'] ?? '';
            if ($correo !== '') {
                $responsableId = $usuarios[Str::lower($correo)] ?? null;
                if (! $responsableId) {
                    $mensajes[] = "El responsable «{$correo}» no está registrado como usuario.";
                }
            }

            if ($mensajes) {
                $errores[] = ['fila' => $numeroFila, 'mensaje' => implode(' ', $mensajes)];

                continue;
            }

            Dispositivo::create([
                'codigo_patrimonial' => $datos['codigo_patrimonial'],
                'tipo' => $datos['tipo'],
                'marca' => ($datos['marca'] ?? '') ?: null,
                'modelo' => ($datos['modelo'] ?? '') ?: null,
                'serie' => ($datos['serie'] ?? '') ?: null,
                'caracteristicas' => ($datos['caracteristicas'] ?? '') ?: null,
                'estado' => $datos['estado'],
                'ubicacion_id' => $ubicacionId,
                'responsable_id' => $responsableId,
            ]);

            $codigosVistos[$codigoClave] = $numeroFila;
            $creados++;
        }

        return ['total' => $total, 'creados' => $creados, 'errores' => $errores];
    }
}
