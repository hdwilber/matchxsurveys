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

class MatchLogicTransformer extends Fractal\TransformerAbstract
{

    public function transform(MatchLogic $ml)
    {
        return [
            "uid" => (string)$ml->uid ?: null,
            "bool" => (string)$ml->bool ?: null,
            "parent_id" => (string)$ml->parent_id? : null,
            "target_id" => (string)$ml->target_id ? : null,
            "target_type" => (string)$ml->target_type ?: null,
        ];
    }
}
