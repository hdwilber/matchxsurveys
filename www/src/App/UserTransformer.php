<?php
namespace App;

use App\User;
use League\Fractal;

class UserTransformer extends Fractal\TransformerAbstract
{

    public function transform(User $user)
    {
        return [
            "uid" => (string)$user->uid ?: null,
            "email" => (string)$user->email?: null,
            "type" => $user->type ? : null
        ];
    }
}

