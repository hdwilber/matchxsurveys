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

use App\User;
use App\UserTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

const USER_FAILED = 0;
const USER_SUCCESS = 1;

const USER_REGISTER_SUCCESS = 1;
const USER_REGISTER_FAILED = 2;
const USER_REGISTER_ERR_EXISTING_EMAIL = 3;
const USER_REGISTER_ERR_PASSWORD_NOT_MATCH = 4;
const USER_REGISTER_ERR_WRONG_EMAIL = 5;

$app->get(getenv("API_ROOT"). "/users", function ($request, $response, $arguments) {
    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["user.all", "user.list"])) {
        throw new ForbiddenException("Token not allowed to list users.", 403);
    }

    $mapper = $this->spot->mapper("App\User");
    /* Use ETag and date from Todo with most recent update. */
    $first = $mapper->findLastChanged();
    /* Add Last-Modified and ETag headers to response when atleast on todo exists. */
    if ($first) {
        $response = $this->cache->withEtag($response, $first->etag());
        $response = $this->cache->withLastModified($response, $first->timestamp());
    }
    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    $users = $mapper->findAll();

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($users, new UserTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/users/{id}/confirm/{code}", function ($request, $response, $arguments) {
    $mapper = $this->spot->mapper("App\User");
    $user = $mapper->findById($arguments['id']);
    if ($user === false) {
        throw new NotFoundException("User not found", 404);
    }

    if ($user->confirm_code == $arguments['code']) {
        $user->data(['confirm_code' => null, 'confirm_created_at' => null, 'confirmed' => true]);
        $mapper->save($user);
    } else {
        throw new ForbiddenException("This code has already used", 403);
    }
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($user, new UserTransformer);
    $data = $fractal->createData($resource)->toArray();

    $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $response;
});
$app->post(getenv("API_ROOT"). "/users", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\User");
    $data = [];
    $data["status"] = "error";
    $data['data'] = [];

    if (false === $this->token->hasScope(["user.all", "user.create"])) {
        throw new ForbiddenException("Token not allowed to create Users.", 403);
    }

    $body = $request->getParsedBody();

    if (strlen($body['email']) > 0) {
        if (false === $user = $mapper->first (
            ["email" => $body["email"]]
        )) {
            // There is not register user with the same email
            $body['password'] = trim($body['password']);
            if (strlen($body['password']) > 0 ){
                $body['password'] = password_hash ($body['password'], PASSWORD_DEFAULT);

                $user = new User($body);
                $mapper->save($user);

                /* Serialize the response data. */
                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new UserTransformer);
                $data = $fractal->createData($resource)->toArray();
                $data["status"] = "ok";
                $data["message"] = "New User created";
            }
            else {
                $data['status'] = "error";
                $data['message'] = "Password wrong";
            }
        }
        else {
            $data['status'] = "error";
            $data['message'] = "This email is already registered.";
        }
    }
    else {
        $data['status'] = "error";
        $data['message'] = "wrong Email.";
    }
    $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $response;
});

$app->post(getenv("API_ROOT"). "/register", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\User");
    $data = [];
    $data["status"] = "error";
    $data['data'] = [];

    $body = $request->getParsedBody();

    if (strlen($body['email']) > 0) {
        if (false === $user = $mapper->first (
            ["email" => $body["email"]]
        )) {
            // There is not register user with the same email
            $body['password'] = trim($body['password']);
            if (strlen($body['password']) > 0 ){
                $body['password'] = password_hash ($body['password'], PASSWORD_DEFAULT);
                $user = new User($body);
                $mapper->save($user);

                /* Serialize the response data. */
                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new UserTransformer);
                $data = $fractal->createData($resource)->toArray();
                $data["status"] = "ok";
                $data["message"] = "New User created";
            }
            else {
                $data['status'] = "error";
                $data['message'] = "Password wrong";
            }
        }
        else {
            $data['status'] = "error";
            $data['message'] = "This email is already registered.";
        }
    }
    else {
        $data['status'] = "error";
        $data['message'] = "wrong Email.";
    }
    $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $response;
});


$app->get(getenv("API_ROOT"). "/users/{id}", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\User");
    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["user.all", "user.read"])) {
        throw new ForbiddenException("Token not allowed to list users.", 403);
    }

    /* Load existing User using provided id */
    if (false === $user = $mapper->getById($arguments["id"])) {
        throw new NotFoundException("User not found.", 404);
    };

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($user, new UserTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/users/{id}", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\User");
    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["user.all", "user.update"])) {
        throw new ForbiddenException("Token not allowed to update users.", 403);
    }

    /* Load existing user using provided id */
    if (false === $user = $mapper->getById($arguments["id"])) {
        throw new NotFoundException("User not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the todo respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $user->etag(), $user->timestamp())) {
        throw new PreconditionFailedException("User has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $user->data($body);
    $mapper->save($user);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($user, new UserTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "User updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/users/{id}", function ($request, $response, $arguments) {
    $mapper = $this->spot->mapper("App\User");

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["user.all", "user.update"])) {
        throw new ForbiddenException("Token not allowed to update Users.", 403);
    }

    /* Load existing user using provided id */
    if (false === $user = $mapper($arguments["id"])) {
        throw new NotFoundException("User not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the todo object first. */
    $user->clear();
    $user->data($body);
    $mapper->save($user);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $user->etag());
    $response = $this->cache->withLastModified($response, $user->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($user, new UserTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "User updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/users/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["user.all", "user.delete"])) {
        throw new ForbiddenException("Token not allowed to delete Users.", 403);
    }
    $mapper = $this->spot->mapper("App\User");

    /* Load existing todo using provided id */
    if (false === $user= $mapper->getById($arguments["id"])) {
        throw new NotFoundException("User not found.", 404);
    };

    $mapper->delete($user);

    $data["status"] = "ok";
    $data["message"] = "User deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
