<?php
namespace App;

use App\Question;
use League\Fractal;

class QuestionTransformer extends Fractal\TransformerAbstract
{
    public function transform(Element $e)
    {
        return [
            "id" => (int)$e->id ?: null,
            "code" => (string)$e->code ? : null,
            "label" => ['type'=>'text', 'data'=>(string)$e->label->data?: null],
            "type" => (string)$e->owned->type ? : null,
            "subType" => (string)$e->owned->sub_type? : null,
            //"options" => $question->options ? : null,
        ];
    }
}
