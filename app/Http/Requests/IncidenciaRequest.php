<?php

namespace App\Http\Requests;

use App\Models\Incidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** HU09 - Registro de una incidencia. */
class IncidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // cualquier usuario autenticado y activo puede reportar
    }

    public function rules(): array
    {
        return [
            // No se pueden reportar fallas de equipos dados de baja
            'dispositivo_id' => [
                'required',
                Rule::exists('dispositivos', 'id')->where(fn ($q) => $q->where('estado', '!=', 'de_baja')),
            ],
            'categoria' => ['required', Rule::in(Incidencia::CATEGORIAS)],
            'descripcion' => ['required', 'string', 'min:10', 'max:2000'],
            'impacto' => ['required', Rule::in(array_keys(Incidencia::IMPACTOS))],
            'urgencia' => ['required', Rule::in(array_keys(Incidencia::URGENCIAS))],
        ];
    }

    public function messages(): array
    {
        return [
            'dispositivo_id.required' => 'Selecciona el dispositivo con la falla.',
            'dispositivo_id.exists' => 'El dispositivo seleccionado no existe o está dado de baja.',
            'categoria.required' => 'Selecciona la categoría de la falla.',
            'categoria.in' => 'La categoría seleccionada no es válida.',
            'descripcion.required' => 'Describe la falla.',
            'descripcion.min' => 'Describe la falla con al menos 10 caracteres.',
            'descripcion.max' => 'La descripción no debe superar 2000 caracteres.',
            'impacto.required' => 'Selecciona el impacto.',
            'impacto.in' => 'El impacto seleccionado no es válido.',
            'urgencia.required' => 'Selecciona la urgencia.',
            'urgencia.in' => 'La urgencia seleccionada no es válida.',
        ];
    }
}
