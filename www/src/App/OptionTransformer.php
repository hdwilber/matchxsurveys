<?php
namespace App;

use App\Option;
use League\Fractal;

class OptionTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (string)$e->id ?: null,
            "type" => (string)$e->owned->type? : null,
            "label" => ['type' => 'text', 'data' => (string)$e->label->data ?: null],
            "value" => (integer)$e->owned->value ? : null,
            "data" => (string)$e->owned->data? : null,
            "extra" => (string)$e->owned->extra? : null
        ];
    }
}
