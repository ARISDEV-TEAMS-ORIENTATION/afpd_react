<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // $this->call(RoleSeeder::class);

        $roleId = Role::where('nom_role', 'Presidente')->value('id')
            ?? Role::first()?->id
            ?? Role::create(['nom_role' => 'Presidente'])->id;

        User::factory()->create([
            'nom' => 'Admin',
            'password' => 'password',
            'email' => 'test@example.com',
            'role_id' => $roleId,
        ]);
    }
}
