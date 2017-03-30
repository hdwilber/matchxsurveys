<?php
namespace App;

use App\Step;
use League\Fractal;
use App\QuestionTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

class GroupTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $g)
    {
        return [
            "id" => (string)$g->id ?: null,
            "code" => (string)$g->code ?: null,
            "label" => ['type' => 'text', 'data'=>(string)$g->label->data],
        ];
    }
}

