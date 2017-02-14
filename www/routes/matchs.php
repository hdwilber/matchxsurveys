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

use App\Option;
use App\OptionTransformer;
use App\Question;
use App\QuestionTransformer;
use App\Match;
use App\MatchTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/questions/{questionId}/matchs", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list matchs.", 403);
    }
    $mapper = $this->spot->mapper("App\Match");
    $quMapper = $this->spot->mapper("App\Question");

    $question = $quMapper->findById($arguments['questionId']);

    if ($question === false) {
        throw new NotFoundException("Question not found", 404);
    } else {
        $questionx = $question->toArray();
        $first = $mapper->findLastModifiedFromQuestion($questionx);
        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $matchs = $mapper->findAllFromQuestion($questionx);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($matchs, new MatchTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->post(getenv("API_ROOT"). "/questions/{questionId}/matchs", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create matchs.", 403);
    }
    $mapper = $this->spot->mapper('App\Match');
    $quMapper = $this->spot->mapper("App\Question");

    $question = $quMapper->findById($arguments['questionId']);
    if ($question === false) {
        throw new NotFoundException("Match: Question not Found", 404);
    } else {
        $questionx = $question->toArray();
        $body = $request->getParsedBody();
        $body["user_id"] = $this->token->getUser();
        $body["question_id"] = $questionx['uid'];
        $match= new Match($body);
        $mapper->save($match);

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $match->etag());
        $response = $this->cache->withLastModified($response, $match->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($match, new MatchTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New match created";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Location", $data["data"]["links"]["self"])
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});


$app->get(getenv("API_ROOT"). "/matchs/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read matchs.", 403);
    }
    $mapper = $this->spot->mapper("App\Match");

    $match = $mapper->findById($arguments['uid']);
    if ($match === false) 
        throw new NotFoundException("Match not found.", 404);
    else {
        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $match->etag());
        $response = $this->cache->withLastModified($response, $match->timestamp());

        /* If-Modified-Since and If-None-Match request header handling. */
        /* Heads up! Apache removes previously set Last-Modified header */
        /* from 304 Not Modified responses. */
        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($match, new MatchTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    }

});

$app->patch(getenv("API_ROOT"). "/matchs/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.update"])) {
        throw new ForbiddenException("Token not allowed to update match.", 403);
    }

    /* Load existing match using provided uid */
    if (false === $match = $this->spot->mapper("App\Match")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Match not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the match respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $match->etag(), $match->timestamp())) {
        throw new PreconditionFailedException("Match has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $match->data($body);
    $this->spot->mapper("App\Match")->save($match);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $match->etag());
    $response = $this->cache->withLastModified($response, $match->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($match, new MatchTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Match updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/matchs/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.update"])) {
        throw new ForbiddenException("Token not allowed to update Match.", 403);
    }

    /* Load existing match using provided uid */
    if (false === $match = $this->spot->mapper("App\Match")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Match not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the match respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $match->etag(), $match->timestamp())) {
        throw new PreconditionFailedException("Match has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $match->clear();
    $match->data($body);
    $this->spot->mapper("App\Match")->save($match);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $match->etag());
    $response = $this->cache->withLastModified($response, $match->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($match, new MatchTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Match updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/matchs/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.delete"])) {
        throw new ForbiddenException("Token not allowed to delete match.", 403);
    }

    /* Load existing match using provided uid */
    if (false === $match = $this->spot->mapper("App\Match")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("mAtch not found.", 404);
    };

    $this->spot->mapper("App\Match")->delete($match);

    $data["status"] = "ok";
    $data["message"] = "Match deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
