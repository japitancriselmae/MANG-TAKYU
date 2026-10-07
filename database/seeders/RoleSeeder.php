<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Manager',
            'Cashier',
            'Kitchen Staff',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'role_name' => $roleName,
            ]);
        }
    }
}
