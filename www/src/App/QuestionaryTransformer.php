<?php
namespace App;

use App\Questionary;
use League\Fractal;
use App\Element;

class QuestionaryTransformer extends Fractal\TransformerAbstract
{
    public function transform(Element $e)
    {
        $groups = [];
        foreach($e->children->entities() as $g) {
            array_push($groups, [
                'id' => $g->id,
                'code' =>  $g->code,
                'label' => [
                    'type' => 'text',
                    'data' => $g->label->data
                ]
            ]);
        }
        return [
            "id" => (int)$e->id ?: null,
            "code" => (string)$e->code ? : null,
            "label" => ['type' => 'text', 'data' =>(string)$e->label->data],
            "groups" => $groups
        ];
    }
}
