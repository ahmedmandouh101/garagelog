<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create a new administrator using interactive hidden password input';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run interactively; passwords must not be supplied as command arguments.');

            return self::FAILURE;
        }
        $data = ['name' => $this->ask('Administrator name'), 'email' => $this->ask('Email'),
            'password' => $this->secret('Password (at least 12 characters)'), 'password_confirmation' => $this->secret('Confirm password')];
        $validator = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12|max:255|confirmed']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'admin']);
        $this->info('Administrator created. Sign in and confirm the email through the verification flow.');

        return self::SUCCESS;
    }
}
