<?php

namespace App\Features\Auth\Actions;

use App\Models\User;

class RegisterUserAction
{
    /** Pendaftaran publik selalu menghasilkan role member. */
    public function execute(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_MEMBER,
        ]);
    }
}
