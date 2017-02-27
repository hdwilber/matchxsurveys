<?php
namespace App;

use App\Step;
use League\Fractal;
use App\QuestionTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

class StepTransformer extends Fractal\TransformerAbstract
{

    public function transform(Step $step)
    {
        return [
            "uid" => (string)$step->uid ?: null,
            "code" => (string)$step->code ?: null,
            "text" => (string)$step->text ?: null,
            "next" => (string)$step->next_id ?: null,
            "user_id" => (string)$step->user_id ?: null,
        ];
    }
}

