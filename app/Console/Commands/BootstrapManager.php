<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BootstrapManager extends Command
{
    protected $signature = 'mang-takyu:bootstrap-manager';

    protected $description = 'Create the initial Manager account when deployment credentials are configured';

    public function handle(): int
    {
        if (User::query()
            ->whereHas('employee.role', fn ($query) => $query->where('role_name', 'Manager'))
            ->exists()) {
            $this->info('A Manager account already exists; no changes were made.');

            return self::SUCCESS;
        }

        /** @var array{email: ?string, password: ?string, first_name: ?string, last_name: ?string} $initialManager */
        $initialManager = config('deployment.initial_manager');

        if (collect($initialManager)->every(fn (?string $value): bool => blank($value))) {
            $this->info('Initial Manager credentials are not configured; setup was skipped.');

            return self::SUCCESS;
        }

        $validator = Validator::make($initialManager, [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:16', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($initialManager): void {
            $managerRole = Role::firstOrCreate(['role_name' => 'Manager']);
            $employee = Employee::create([
                'role_id' => $managerRole->role_id,
                'first_name' => $initialManager['first_name'],
                'last_name' => $initialManager['last_name'],
                'status' => 'Active',
            ]);

            User::create([
                'name' => trim($initialManager['first_name'].' '.$initialManager['last_name']),
                'email' => $initialManager['email'],
                'password' => $initialManager['password'],
                'employee_id' => $employee->employee_id,
            ]);
        });

        $this->info('Initial Manager account created.');

        return self::SUCCESS;
    }
}
