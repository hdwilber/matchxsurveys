<?php
namespace App;

use App\Selection;
use League\Fractal;

class TakenQuizTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (string)$e->id ?: null,
            "questionary" => [ 
                'id' => $e->owned->questionary_id, 
                'code' => $e->owned->questionary->code,
                "label"  => [
                    'type' => "text",
                    "data" => (string)$e->owned->questionary->label->data
                ]
            ]
        ];
    }
}
