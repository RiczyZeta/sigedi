<?php

namespace App\Http\Requests;

use App\Models\Dispositivo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación para crear (HU03) y editar (HU03, HU04, HU05) un dispositivo. */
class DispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla RoleMiddleware en la ruta
    }

    public function rules(): array
    {
        // En "crear" la ruta no trae dispositivo, por lo que ignore(null) no excluye nada.
        $dispositivo = $this->route('dispositivo');

        return [
            'codigo_patrimonial' => [
                'required', 'string', 'max:40',
                Rule::unique('dispositivos', 'codigo_patrimonial')->ignore($dispositivo),
            ],
            'tipo' => ['required', 'string', 'max:40'],
            'marca' => ['nullable', 'string', 'max:60'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'serie' => ['nullable', 'string', 'max:80'],
            'caracteristicas' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(Dispositivo::ESTADOS)],
            'ubicacion_id' => ['required', 'exists:ubicaciones,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_patrimonial.required' => 'El código patrimonial es obligatorio.',
            'codigo_patrimonial.unique' => 'Ya existe un dispositivo con ese código patrimonial.',
            'codigo_patrimonial.max' => 'El código patrimonial no debe superar 40 caracteres.',
            'tipo.required' => 'El tipo de dispositivo es obligatorio.',
            'estado.required' => 'Selecciona el estado del dispositivo.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'ubicacion_id.required' => 'Selecciona la ubicación del dispositivo.',
            'ubicacion_id.exists' => 'La ubicación seleccionada no es válida.',
            'responsable_id.exists' => 'El responsable seleccionado no es un usuario registrado.',
        ];
    }
}
