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

use App\Option;
use League\Fractal;

class OptionTransformer extends Fractal\TransformerAbstract
{

    public function transform(Option $option)
    {
        return [
            "uid" => (string)$option->uid ?: null,
            "text" => (string)$option->text ?: null,
            "value" => (integer)$option->value? : null,
            "sort" => (integer)$option->sort?: -1,
            "author" => $option->author ? : null,
            "question" => $option->question ?: null
        ];
    }
}
