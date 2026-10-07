<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminAccount extends Command
{
    protected $signature = 'mang-takyu:create-admin
                            {--email=admin@mangtakyu.com}
                            {--password=admin123}';

    protected $description = 'Create the System Admin account (bypasses role hierarchy)';

    public function handle()
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);

        if (User::where('email', $this->option('email'))->exists()) {
            $this->error('A user with this email already exists: ' . $this->option('email'));
            return 1;
        }

        $employee = Employee::create([
            'role_id'    => $adminRole->role_id,
            'first_name' => 'System',
            'last_name'  => 'Admin',
            'status'     => 'Active',
        ]);

        $user = User::create([
            'name'        => 'System Admin',
            'email'       => $this->option('email'),
            'password'    => Hash::make($this->option('password')),
            'employee_id' => $employee->employee_id,
        ]);

        $this->info('✔ Admin account created');
        $this->info('   Email:    ' . $user->email);
        $this->info('   Password: ' . $this->option('password'));
        $this->info('');
        $this->info('Log out and log in with these credentials.');

        return 0;
    }
}