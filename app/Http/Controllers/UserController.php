<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HU02 - Gestión de usuarios y roles (solo administrador; la ruta lo protege con RoleMiddleware). */
class UserController extends Controller
{
    public function index(): View
    {
        $usuarios = User::with('role')->orderBy('name')->paginate(10);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create', ['roles' => Role::orderBy('id')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        // Evita que el administrador se quite a sí mismo el rol y se bloquee el acceso
        if ($usuario->is($request->user()) && (int) $datos['role_id'] !== (int) $usuario->role_id) {
            return back()->withInput()->with('error', 'No puedes cambiar tu propio rol.');
        }

        // Si la contraseña viene vacía, se conserva la actual
        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado correctamente.');
    }

    /** Activa o desactiva un usuario (no se elimina, para conservar su historial). */
    public function estado(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.');
    }
}
