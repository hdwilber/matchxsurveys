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

use App\Question;
use App\QuestionTransformer;
use App\MatchLogic;
use App\MatchLogicTransformer;
use App\Match;
use App\MatchTransformer;
use App\Logic;


use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;


$app->get(getenv("API_ROOT"). "/logics/{logicId}/match-logics", function ($request, $response, $arguments) {
    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list matchs.", 403);
    }
    $mapper = $this->spot->mapper("App\MatchLogic");
    $loMapper = $this->spot->mapper("App\Logic");


    $logic = $loMapper->findById($arguments['logicId']);
    if ($logic === false) {
        return new NotFoundException("Match Logics: Logic not found", 404);
    } else {
        $logicx = $logic->toArray();
        $first = $mapper->findLastModifiedFromLogic($logicx);
        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $matchLogics = $mapper->findAllFromLogic($logicx);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($matchLogics, new MatchTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});
$app->post(getenv("API_ROOT"). "/match-logics", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create matchs.", 403);
    }

    $mapper = $this->spot->mapper('App\MatchLogic');
    $loMapper = $this->spot->mapper("App\Logic");

    $body = $request->getParsedBody();
    if (!isset($body['parent_id'])) {
        throw new PreconditionRequiredException("parent_id is required different to null.", 428);
    }
    if ($body['parent_id'] == null) {
        throw new PreconditionRequiredException("parent_id is required different to null.", 428);
    } 

    $parent = $mapper->findById($body['parent_id']);
    if ($parent === false) {
        throw new NotFoundException("MatchLogic: Parent not Found", 404);
    } else {
        $matchLogic = new MatchLogic($body);
        $mapper->save($matchLogic);

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $matchLogic->etag());
        $response = $this->cache->withLastModified($response, $matchLogic->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($matchLogic, new MatchLogicTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New match Logic created";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});


$app->post(getenv("API_ROOT"). "/logics/{logicId}/match-logics", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create matchs.", 403);
    }
    $mapper = $this->spot->mapper('App\MatchLogic');
    $loMapper = $this->spot->mapper("App\Logic");

    $logic = $loMapper->findById($arguments['logicId']);
    if ($logic === false) {
        throw new NotFoundException("MatchLogic: Logic not Found", 404);
    } else {
        $logicx = $logic->toArray();
        $body = $request->getParsedBody();
        $body["parent_id"] = null;
        $matchLogic = new MatchLogic($body);
        $mapper->save($matchLogic);

        $logic->data(['match_logic_id' => $matchLogic->uid]);
        $loMapper->save($logic);


        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $matchLogic->etag());
        $response = $this->cache->withLastModified($response, $matchLogic->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($matchLogic, new MatchLogicTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New match Logic created";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Location", $data["data"]["links"]["self"])
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});


$app->get(getenv("API_ROOT"). "/match-logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read matchs.", 403);
    }
    $mapper = $this->spot->mapper("App\MatchLogic");

    $matchLogic = $mapper->findById($arguments['uid']);
    if ($match === false) 
        throw new NotFoundException("Match Logic not found.", 404);
    else {
        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $matchLogic->etag());
        $response = $this->cache->withLastModified($response, $matchLogic->timestamp());

        /* If-Modified-Since and If-None-Match request header handling. */
        /* Heads up! Apache removes previously set Last-Modified header */
        /* from 304 Not Modified responses. */
        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($matchLogic, new MatchLogicTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->patch(getenv("API_ROOT"). "/match-logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update match logics.", 403);
    }

    $mapper = $this->spot->mapper("App\MatchLogic");
    /* Load existing match using provided uid */
    if (false === $matchLogic = $mapper->findById($arguments['uid'])) {
        throw new NotFoundException("MatchLogic not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the match respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $matchLogic->etag(), $matchLogic->timestamp())) {
        throw new PreconditionFailedException("Match has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $matchLogic->data($body);
    $mapper("App\MatchLogic")->save($matchLogic);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $matchLogic->etag());
    $response = $this->cache->withLastModified($response, $matchLogic->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($matchLogic, new MatchLogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Match Logic updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/match-logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update Match.", 403);
    }

    $mapper = $this->spot->mapper("App\MatchLogic");
    /* Load existing match using provided uid */
    if (false === $matchLogic = $mapper->findById($arguments['uid'])) {
        throw new NotFoundException("Match not found.", 404);
    }

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the match respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $matchLogic->etag(), $matchLogic->timestamp())) {
        throw new PreconditionFailedException("Match Logic has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $matchLogic->clear();
    $matchLogic->data($body);
    $mapper->save($matchLogic);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $matchLogic->etag());
    $response = $this->cache->withLastModified($response, $matchLogic->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($matchLogic, new MatchLogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Match Logic updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/match-logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete match logic.", 403);
    }

    /* Load existing match using provided uid */
    if (false === $match = $this->spot->mapper("App\MatchLogic")->getById($arguments['uid'])) {
        throw new NotFoundException("Match Logic not found.", 404);
    };

    $this->spot->mapper("App\MatchLogic")->delete($matchLogic);

    $data["status"] = "ok";
    $data["message"] = "Match Logic deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
