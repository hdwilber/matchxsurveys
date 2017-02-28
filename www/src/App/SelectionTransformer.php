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

use App\Selection;
use League\Fractal;

class SelectionTransformer extends Fractal\TransformerAbstract
{

    public function transform(Selection $selection)
    {
        return [
            "uid" => (string)$selection->uid ?: null,
            "option" => $selection->option ? : null,
            "question" => $selection->question ? : null,
            "value" => $selection->value ? : null,
        ];
    }
}
