<?php
namespace App;

use App\User;
use League\Fractal;

class UserListTransformer extends Fractal\TransformerAbstract {
    public function transform(User $user)
    {
        return [
            "uid" => (string)$user->uid ?: null,
            "email" => (string)$user->email?: null,
        ];
    }
}
