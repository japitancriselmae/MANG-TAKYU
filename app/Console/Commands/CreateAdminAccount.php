<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdminAccount extends Command
{
    protected $signature = 'mang-takyu:create-admin
                            {--email=admin@mangtakyu.com}';

    protected $description = 'Create the System Admin account (bypasses role hierarchy)';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->option('email')));

        $emailValidator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255']],
        );

        if ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first('email'));

            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists: '.$email);

            return 1;
        }

        $password = $this->secret('Enter an admin password (at least 12 characters)');
        $passwordConfirmation = $this->secret('Confirm the password');

        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->error('The password must be at least 12 characters.');

            return 1;
        }

        if (! is_string($passwordConfirmation) || ! hash_equals($password, $passwordConfirmation)) {
            $this->error('The password confirmation does not match.');

            return 1;
        }

        $user = DB::transaction(function () use ($email, $password): User {
            $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);

            $employee = Employee::create([
                'role_id' => $adminRole->role_id,
                'first_name' => 'System',
                'last_name' => 'Admin',
                'status' => 'Active',
            ]);

            return User::create([
                'name' => 'System Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'employee_id' => $employee->employee_id,
            ]);
        });

        $this->info('Admin account created.');
        $this->info('Email: '.$user->email);
        $this->info('Log in using the email and password you entered.');

        return self::SUCCESS;
    }
}
