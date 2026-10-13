<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla RoleMiddleware en la ruta
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/', 'unique:users,whatsapp'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
