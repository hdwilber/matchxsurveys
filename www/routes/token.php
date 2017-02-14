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

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

// MAIN ERRORS
const TOKEN_GET_FAILED = 0;
const TOKEN_GET_SUCCESSFULLY = 1;

const TOKEN_GET_ERR_NONE = 0;
const TOKEN_GET_ERR_EMAIL_NOT_CONFIRMED = 1;
const TOKEN_GET_ERR_USER_NOT_EXISTS = 2;



function scope_fill($name, $opts) {
    $scope = [];
    foreach($opts as $opt) {
        $ac = "";
        switch($opt) {
            case "C": $ac = "create"; break;
            case "R": $ac = "read"; break;
            case "U": $ac = "update"; break;
            case "D": $ac = "delete"; break;
            case "L": $ac = "list"; break;
            case "A": $ac = "all"; break;
            default:  $ac = ""; break;
        }
        if ($ac != "") 
            array_push($scope, $name .".". $ac );
    }
    return $scope;
};

$app->post(getenv("API_ROOT"). "/token", function ($request, $response, $arguments) {
    $requested_scope = $request->getParsedBody();
    $data = [];
    $data["status"] = "error";
    $data["result"] = TOKEN_GET_FAILED;
    $data["error"] = TOKEN_GET_ERR_NONE;
    $data["uid"] = "";
    $data["email"] = "";

    //print_r($requested_scope);
    $scope = [];
    $server = $request->getServerParams();
    $payload = [];
    $payload["uid"] = "";
    $payload["email"] = "";
    $user = $this->spot->mapper("App\User")->first (["email" => $server["PHP_AUTH_USER"]]);
    if ($user != NULL) {
        if ($user->confirmed) {
            if ($user->type == "admin") {
                $scope = scope_fill ("user", ["A"]);
                $scope = array_merge($scope, scope_fill("step", ["A"]));
                $scope = array_merge($scope, scope_fill("question", ["A"]));
                $scope = array_merge($scope, scope_fill("option", ["A"]));
            } else {
                $scope = scope_fill ("user", ["R", "U", "D"]);
                $scope = array_merge($scope, scope_fill("step", ["R"]));
                $scope = array_merge($scope, scope_fill("question", ["R", "L"]));
                $scope = array_merge($scope, scope_fill("option", ["A"]));
            }
            $payload["uid"] = $user->uid;
            $payload["email"] = $user->email;
            $data["error"] = TOKEN_GET_ERR_NONE;
        } else {
            $data["error"] = TOKEN_GET_ERR_EMAIL_NOT_CONFIRMED;
            $scope = scope_fill ("user", "R", "U", "D");
            $scope = array_merge($scope, scope_fill("step", ["R"]));
            $scope = array_merge($scope, scope_fill("question", ["R", "L"]));
            $scope = array_merge($scope, scope_fill("option", ["R", "L"]));
        }
    }
    else {
        $data["error"] =  TOKEN_GET_ERR_USER_NOT_EXISTS;
        $data["message"] = "Although your user doesnt exists, you can work in our service";
        $scope = ["question.read"];
        $scope = array_merge($scope, scope_fill("step", ["R"]));
        $scope = array_merge($scope, scope_fill("question", ["R"]));
        $scope = array_merge($scope, scope_fill("option", ["R"]));
    }
    $data["result"] = TOKEN_GET_SUCCESSFULLY;

    $now = new DateTime();
    $future = new DateTime("now +2 hours");

    $jti = Base62::encode(random_bytes(16));

    $payload["iat"] = $now->getTimeStamp();
    $payload["exp"] = $future->getTimeStamp();
    $payload["jti"] = $jti;
    $payload["scope"] = $scope;

    $secret = getenv("JWT_SECRET");
    $token = JWT::encode($payload, $secret, "HS256");
    $data["status"] = "ok";
    $data["token"] = $token;
    $data["scope"] = $scope;
    if ($payload["uid"] != "")
        $data["uid"] = $payload["uid"];
    if ($payload["email"] != "")
        $data["email"] = $payload["email"];

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


//$app->post(getenv('API_ROOT')."/token", function ($request, $response, $arguments) {
    //$requested_scopes = $request->getParsedBody();

    //$valid_scopes = [
        //"todo.create",
        //"todo.read",
        //"todo.update",
        //"todo.delete",
        //"todo.list",
        //"todo.all"
    //];

    //$scopes = array_filter($requested_scopes, function ($needle) use ($valid_scopes) {
        //return in_array($needle, $valid_scopes);
    //});

    //$now = new DateTime();
    //$future = new DateTime("now +2 hours");
    //$server = $request->getServerParams();

    //$jti = Base62::encode(random_bytes(16));

    //$payload = [
        //"iat" => $now->getTimeStamp(),
        //"exp" => $future->getTimeStamp(),
        //"jti" => $jti,
        //"sub" => $server["PHP_AUTH_USER"],
        //"scope" => $scopes
    //];

    //$secret = getenv("JWT_SECRET");
    //$token = JWT::encode($payload, $secret, "HS256");
    //$data["status"] = "ok";
    //$data["token"] = $token;

    //return $response->withStatus(201)
        //->withHeader("Content-Type", "application/json")
        //->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
//});

/* This is just for debugging, not usefull in real life. */
$app->get(getenv('API_ROOT')."/dump", function ($request, $response, $arguments) {
    print_r($this->token);
});

$app->post(getenv('API_ROOT')."/dump", function ($request, $response, $arguments) {
    print_r($this->token);
});

/* This is just for debugging, not usefull in real life. */
$app->get(getenv('API_ROOT')."/info", function ($request, $response, $arguments) {
    phpinfo();
});
