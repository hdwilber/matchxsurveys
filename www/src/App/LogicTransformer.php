<?php
namespace App;

use App\Logic;
use League\Fractal;

class LogicTransformer extends Fractal\TransformerAbstract
{

    public function transform(Logic $logic)
    {
        return [
            "uid" => (string)$logic->uid ?: null,
            "action" => (string)$logic->action?: null,
            "question_id" => $logic->question_id? : null,
            "match_logic_id" => $logic->match_logic_id ? : null
        ];
    }
}
