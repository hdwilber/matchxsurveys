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

class TakenQuizTransformer extends Fractal\TransformerAbstract
{

    public function transform(TakenQuiz $tq)
    {
        return [
            "uid" => (string)$tq->uid ?: null,
            "questionary" => $tq->questionary? : null,
            "questionary_id"=> $tq->questionary_id ? : null,
            "questionary" => $tq->questionary ?: null,
        ];
    }
}
