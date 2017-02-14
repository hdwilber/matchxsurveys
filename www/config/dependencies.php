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

$container = $app->getContainer();

use Spot\Config;
use Spot\Locator;
use Doctrine\DBAL\Logging\MonologSQLLogger;


$container['pdo'] = function ($c) {
    //$pdo = new PDO('mysql:host='.getenv("LOCAL_DB_HOST") . ';dbname=' . getenv("LOCAL_DB_NAME"), getenv("LOCAL_DB_USER"), getenv("LOCAL_DB_PASSWORD") );
    $location = getenv("LOCATION");
    if ($location == "local")
        $pdo = new PDO('mysql:host='.getenv("LOCAL_DB_HOST") . ';dbname=' . getenv("LOCAL_DB_NAME"), getenv("LOCAL_DB_USER"), getenv("LOCAL_DB_PASSWORD") );
    else 
        $pdo = new PDO('mysql:host='.getenv("OS_DB_HOST"). ';port='.getenv("OS_DB_PORT") . ';dbname=' . getenv("OS_DB_NAME"), getenv("OS_DB_USER"), getenv("OS_DB_PASSWORD") );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container["spot"] = function ($container) {

    $location = getenv("LOCATION");
    $config = new Config();
    //$mysql = $config->addConnection("mysql", [
        //"dbname" => getenv("LOCAL_DB_NAME"),
        //"user" => getenv("LOCAL_DB_USER"),
        //"password" => getenv("LOCAL_DB_PASSWORD"),
        //"host" => getenv("LOCAL_DB_HOST"),
        //"driver" => "pdo_mysql",
        //"charset" => "utf8"
    //]);
    if ($location == "local") {
        $mysql = $config->addConnection("mysql", [
            "dbname" => getenv("LOCAL_DB_NAME"),
            "user" => getenv("LOCAL_DB_USER"),
            "password" => getenv("LOCAL_DB_PASSWORD"),
            "host" => getenv("LOCAL_DB_HOST"),
            "driver" => "pdo_mysql",
            "charset" => "utf8"
        ]);
    } else {
        $mysql = $config->addConnection("mysql", [
            "dbname" => getenv("OS_DB_NAME"),
            "user" => getenv("OS_DB_USER"),
            "password" => getenv("OS_DB_PASSWORD"),
            "host" => getenv("OS_DB_HOST").":".getenv("OS_DB_PORT"),
            "driver" => "pdo_mysql",
            "charset" => "utf8"
        ]);

    }

    $spot = new Locator($config);

    $logger = new MonologSQLLogger($container["logger"]);
    $mysql->getConfiguration()->setSQLLogger($logger);

    return $spot;
};

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Monolog\Formatter\LineFormatter;

$container = $app->getContainer();

$container["logger"] = function ($container) {
    $logger = new Logger("slim");

    $formatter = new LineFormatter(
        "[%datetime%] [%level_name%]: %message% %context%\n",
        null,
        true,
        true
    );

    /* Log to timestamped files */
    $rotating = new RotatingFileHandler(__DIR__ . "/../logs/slim.log", 0, Logger::DEBUG);
    $rotating->setFormatter($formatter);
    $logger->pushHandler($rotating);

    return $logger;
};
