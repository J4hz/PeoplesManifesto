<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'manifesto:create-admin {email} {name}';

    protected $description = 'Create a platform admin and email them a link to set their password';

    public function handle(): int
    {
        $data = ['email' => Str::lower((string) $this->argument('email')), 'name' => (string) $this->argument('name')];

        $validator = Validator::make($data, [
            'email' => ['required', 'email:rfc', 'unique:users,email'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        if ($validator->fails()) {
            $this->error(implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Str::password(40),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        Password::sendResetLink(['email' => $data['email']]);

        $this->info("Admin created. A password link has gone to {$data['email']}. They must add an authenticator app on first sign in.");

        return self::SUCCESS;
    }
}
