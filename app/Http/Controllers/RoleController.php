<?php

namespace App\Http\Controllers;

use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('roles/Index', [
            'roles' => Role::with('permissions:id,name')->withCount('users')->orderBy('id')->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name'),
                    'locked' => $role->name === Permissions::SUPER_ADMIN,
                ]),
            'groups' => Permissions::groups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
        ], attributes: ['name' => 'nombre']);

        Role::create(['name' => $data['name']])->syncPermissions($data['permissions'] ?? []);
        $this->toast('Rol creado.');

        return back();
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === Permissions::SUPER_ADMIN, 403, 'El rol administrador no se puede modificar.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles')->ignore($role)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
        ], attributes: ['name' => 'nombre']);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        $this->toast('Rol actualizado.');

        return back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === Permissions::SUPER_ADMIN || $role->users()->exists()) {
            $this->toast('No se puede eliminar un rol protegido o con usuarios asignados.', 'error');

            return back();
        }

        $role->delete();
        $this->toast('Rol eliminado.');

        return back();
    }
}
