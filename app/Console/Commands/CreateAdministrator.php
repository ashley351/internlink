<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdministrator extends Command
{
    protected $signature = 'app:create-administrator';

    protected $description = 'Create the first InternLink administrator account';

    public function handle(): int
    {
        if (User::query()->where('role', User::ROLE_ADMIN)->exists()) {
            $this->error('An administrator account already exists. This setup command can only be used once.');

            return self::FAILURE;
        }

        $name = trim($this->ask('Administrator name', 'InternLink Administrator'));
        $email = trim($this->ask('Administrator email'));
        $password = $this->secret('Administrator password');
        $passwordConfirmation = $this->secret('Confirm administrator password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'is_approved' => true,
            'approval_status' => 'approved',
        ]);

        $this->info('Administrator account created. Sign in with the email you provided.');

        return self::SUCCESS;
    }
}
