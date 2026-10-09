<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@bodegas.test',
        ])->assignRole(Permissions::SUPER_ADMIN);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
