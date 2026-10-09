<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $role = $request->string('role')->value() ?: null;

        return Inertia::render('users/Index', [
            'users' => User::query()
                ->with(['roles:id,name', 'warehouses:id,code,name'])
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")))
                ->when($role, fn ($q) => $q->role($role))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'roles' => Role::orderBy('id')->pluck('name'),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'code', 'name']),
            'filters' => ['search' => $search, 'role' => $role],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->syncAccess($user, $data);
        $this->toast('Usuario creado.');

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && (! ($data['is_active'] ?? true) || $data['role'] !== 'Administrador') && $user->hasRole('Administrador')) {
            $this->toast('No puedes quitarte el rol de administrador ni desactivarte a ti mismo.', 'error');

            return back();
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $this->syncAccess($user, $data);
        $this->toast('Usuario actualizado.');

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            $this->toast('No puedes eliminar tu propio usuario.', 'error');

            return back();
        }

        if ($user->movements()->exists()) {
            $user->update(['is_active' => false]);
            $this->toast('El usuario tiene movimientos registrados; se desactivó en lugar de eliminarse.', 'warning');

            return back();
        }

        $user->delete();
        $this->toast('Usuario eliminado.');

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncAccess(User $user, array $data): void
    {
        $user->syncRoles([$data['role']]);
        $user->warehouses()->sync($data['warehouses'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'warehouses' => ['array'],
            'warehouses.*' => ['integer', 'exists:warehouses,id'],
            'is_active' => ['boolean'],
        ], attributes: ['role' => 'rol', 'warehouses' => 'bodegas']);
    }
}
