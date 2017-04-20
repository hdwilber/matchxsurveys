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

date_default_timezone_set("UTC");

require __DIR__ . "/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ]
]);

require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";

require __DIR__ . "/src/App/BaseMapper.php";
require __DIR__ . "/src/App/UserMapper.php";
require __DIR__ . "/src/App/ElementMapper.php";
require __DIR__ . "/src/App/QuestionMapper.php";
require __DIR__ . "/src/App/QuestionaryMapper.php";
require __DIR__ . "/src/App/GroupMapper.php";
require __DIR__ . "/src/App/OptionMapper.php";

//require __DIR__ . "/src/App/QuestionMapper.php";
//require __DIR__ . "/src/App/OptionMapper.php";
//require __DIR__ . "/src/App/StepMapper.php";
require __DIR__ . "/src/App/LogicMapper.php";
//require __DIR__ . "/src/App/SelectionMapper.php";
//require __DIR__ . "/src/App/QuestionayMapper.php";
//require __DIR__ . "/src/App/MatchMapper.php";
require __DIR__ . "/src/App/TakenQuizMapper.php";
require __DIR__ . "/src/App/AnswerMapper.php";


require __DIR__ . "/routes/elements.php";
require __DIR__ . "/routes/token.php";
require __DIR__ . "/routes/users.php";
require __DIR__ . "/routes/questions.php";
require __DIR__ . "/routes/groups.php";
require __DIR__ . "/routes/options.php";
require __DIR__ . "/routes/questionaries.php";
require __DIR__ . "/routes/answers.php";
//require __DIR__ . "/routes/todos.php";
//require __DIR__ . "/routes/questions.php";
//require __DIR__ . "/routes/steps.php";
require __DIR__ . "/routes/logics.php";
//require __DIR__ . "/routes/selections.php";
//require __DIR__ . "/routes/matchs.php";
require __DIR__ . "/routes/taken-quizzes.php";
//require __DIR__ . "/routes/match-logics.php";



$app->run();
