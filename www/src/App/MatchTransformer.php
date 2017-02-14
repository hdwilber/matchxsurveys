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

    public function transform(Match $match)
    {
        return [
            "uid" => (string)$match->uid ?: null,
            "question_id" => $match->question_id ?: null,
            "operator" => (string)$match->operator? : null,
            "target_question_id" => $match->target_question_id? : null,
            "target_option_id" => $match->target_option_id? : null,
            "target_value" => $match->target_value ?: null,
            "user_id" => $match->user_id?: null,
            "type" => $match->type ?: null
        ];
    }
}
