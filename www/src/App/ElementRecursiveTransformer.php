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


class ElementRecursiveTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (int)$e->id ?: null,
            "action" => (string)$e->owned->action?: null
            //"group_id" => (int)$l->root_id?: null
        ];
    }
}

