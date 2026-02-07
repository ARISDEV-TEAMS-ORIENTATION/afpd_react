<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;

class RoleSeeder extends Seeder {
    public function run() {
        $roles = [
            ['nom_role'=>'Presidente'],
            ['nom_role'=>'Secretaire'],
            ['nom_role'=>'Tresoriere'],
            ['nom_role'=>'Responsable'],
            ['nom_role'=>'CommunityManager'],
            ['nom_role'=>'Adherente'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

