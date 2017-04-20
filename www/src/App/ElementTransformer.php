<?php
namespace App;

use App\Option;
use League\Fractal;

class ElementTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (integer)$e->id ?: null,
            "type" => (string)$e->data_type,
            "code" => (string)$e->code,
            "data" => (object) $e->owned,
            "label" => [
                'type' => $e->label->type,
                'data' => $e->label->data
            ]
        ];
    }
}
