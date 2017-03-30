<?php
namespace App;

use App\Logic;
use League\Fractal;

class MatchLogicTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (int)$e->id ?: null,
            "type" => "match-logic",
            "bool" => (string)$e->owned->bool ?: null
        ];
    }
}
