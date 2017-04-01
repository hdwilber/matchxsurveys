<?php
namespace App;

use App\Option;
use League\Fractal;

class AnswerTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        $question = $e->owned->question->execute();
        $option = $e->owned->option->execute();
        return [
            "id" => (string)$e->id ?: null,
            "question" => [
                'id'=>(integer)$question->id,
                'code' => (string)$question->code

            ],
            "option" => [
                'id'=>(integer)$option->id,
                'label' => [
                    'data' => (string)$option->label->data,
                    'type' => (string)$option->label->type
                ]
            ],
        ];
    }
}
