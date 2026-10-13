<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla RoleMiddleware en la ruta
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'role_id' => ['required', 'exists:roles,id'],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/', Rule::unique('users', 'whatsapp')->ignore($usuario)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'role_id.required' => 'Selecciona un rol.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'whatsapp.regex' => 'El WhatsApp debe tener entre 9 y 15 dígitos (puede iniciar con +).',
            'whatsapp.unique' => 'Ese número de WhatsApp ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
