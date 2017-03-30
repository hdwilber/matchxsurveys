<?php
namespace App;

use App\User;
use League\Fractal;

class UserTransformer extends Fractal\TransformerAbstract
{

    public function transform(User $user)
    {
        return [
            "id" => (string)$user->id ?: null,
            "name" => (string)$user->name ?: null,
            "email" => (string)$user->email?: null,
            "type" => $user->type ? : null
        ];
    }
}

