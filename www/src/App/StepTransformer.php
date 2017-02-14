<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

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

