<?php
namespace App;

use App\Question;
use League\Fractal;

class QuestionTransformer extends Fractal\TransformerAbstract
{
    public function transform(Question $question)
    {
        return [
            "uid" => (string)$question->uid ?: null,
            "text" => (string)$question->text ?: null,
            "code" => (string)$question->code ? : null,
            "type" => (string)$question-> type ? : null,
            "step_id" => (string)$question->step_id ? : null,
            "next_id" => (string)$question->next_id? : null,
            "mandatory" => (string)$question->mandatory ? : null,
            "options" => $question->options ? : null,
            "logics" => $question->logics? : null,
        ];
    }
}
