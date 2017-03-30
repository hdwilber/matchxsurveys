<?php
namespace App;

use App\Step;
use App\Element;
use League\Fractal;
use App\QuestionTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;


class LogicTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (int)$e->id ?: null,
            "code"=> (string)$e->code ? : null,
            "action" => (string)$e->owned->action?: null,
            "type" => "logic",
            "questionary" => [ 
                'id' => $e->owned->questionary_id,
                'code' => $e->owned->questionary->code,
                'label'  => [
                    'type' => "text",
                    "data" => (string)$e->owned->questionary->label->data
                ]
            ]
        ];
    }
}

