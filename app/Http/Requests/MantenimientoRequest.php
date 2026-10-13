<?php

namespace App\Http\Requests;

use App\Models\Bitacora;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Sprint 4 - HU14: validación del registro manual de mantenimientos. */
class MantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso por rol (administrador y técnico) lo controla RoleMiddleware en la ruta.
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_mantenimiento' => ['required', Rule::in(array_keys(Bitacora::MANTENIMIENTOS))],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'descripcion' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_mantenimiento.required' => 'Selecciona el tipo de mantenimiento.',
            'tipo_mantenimiento.in' => 'El tipo de mantenimiento debe ser preventivo o correctivo.',
            'fecha.required' => 'Indica la fecha en que se realizó el mantenimiento.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.before_or_equal' => 'La fecha del mantenimiento no puede ser futura.',
            'descripcion.required' => 'Describe el trabajo realizado.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            'descripcion.max' => 'La descripción no debe superar 2000 caracteres.',
        ];
    }
}
