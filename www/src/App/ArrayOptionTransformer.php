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

class ArrayOptionTransformer extends Fractal\TransformerAbstract
{

    public function transform(ArrayOption $aop)
    {
        return [
            "uid" => (string)$aop->uid ?: null,
            "match_id" => $aop->match_id ?: null,
            "option_id" => $aop->operator ?: null,
            "value" => $aop->value ?: null,
        ];
    }
}
