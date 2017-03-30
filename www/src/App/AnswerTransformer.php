<?php
namespace App;

use App\Option;
use League\Fractal;

class AnswerTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (string)$e->id ?: null,
            "question" => ['id'=>(integer)$e->owned->question_id]
        ];
    }
}
