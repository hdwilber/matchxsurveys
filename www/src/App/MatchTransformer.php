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

use App\Logic;
use League\Fractal;

class MatchTransformer extends Fractal\TransformerAbstract
{

    public function transform(Element $e)
    {
        return [
            "id" => (int)$e->id ?: null,
            "type" => "match",
            "operator" => (string)$e->owned->operator ? : null,
            "targetId" => (int)$e->owned->target_id ? : null,
            "targetOptionId" => (int)$e->owned->target_option_id? : null,
            "targetValue" => $e->owned->target_value ?: null
        ];
    }
}
