<?php
use App\Option;
use App\OptionTransformer;
use App\Question;
use App\QuestionTransformer;
use App\Match;
use App\MatchLogic;
use App\Logic;
use App\LogicTransformer;


use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;


$app->get(getenv("API_ROOT"). "/logics/{logicId}/hierarchy", function ($request, $response, $arguments) {
    $mapper = $this->spot->mapper("App\Logic");

    $logic = $mapper->findById($arguments['logicId']);
    if ($logic === false) throw new NotFoundException("Logic: not found", 404);
    $data['logic']['data']['type'] = 'match-logic';
    $data['logic']['data']['name'] = 'root';
    $data['logic']['data']['uid'] = $logic->match_logic_id;
    $data['logic']['expanded'] = true;
    $data['logic']['children'] = $logic->buildHierarchy($this->spot);
    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get(getenv("API_ROOT"). "/steps/{stepId}/logics", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Logic");
    $stMapper = $this->spot->mapper("App\Step");

    $step = $stMapper->findById($arguments['stepId']);

    if ($step === false) {
        throw new NotFoundException("Logic: Step not found", 404);
    } else {
        $first = $mapper->findLastModifiedFromStep($step);
        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $logics = $mapper->findAllFromStep($step);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($logics, new LogicTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});
$app->get(getenv("API_ROOT"). "/questions/{questionId}/logics", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Logic");
    $quMapper = $this->spot->mapper("App\Question");

    $question = $quMapper->findById($arguments['questionId']);

    if ($question === false) {
        throw new NotFoundException("Logic: Question not found", 404);
    } else {
        $first = $mapper->findLastModifiedFromQuestion($question);
        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $logics = $mapper->findAllFromQuestion($question);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($logics, new LogicTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->post(getenv("API_ROOT"). "/steps/{stepId}/logics", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }

    $data = [];
    $stMapper = $this->spot->mapper("App\Step");
    $mapper = $this->spot->mapper("App\Logic");
    $mlMapper = $this->spot->mapper("App\MatchLogic");

    $step = $stMapper->findById($arguments['stepId']);

    if ($step === false) {
        throw new NotFoundException("Logic: Step not found", 404);
    } else {
        $matchLogic = new MatchLogic();
        $mlMapper->save($matchLogic);

        $body = $request->getParsedBody();
        $body["user_id"] = $this->token->getUser();
        $body["target_id"] = $step->uid;
        $body["target_type"] = "step";
        $body['match_logic_id'] = $matchLogic->uid;

        $logic = new Logic($body);
        $mapper->save($logic);

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $logic->etag());
        $response = $this->cache->withLastModified($response, $logic->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($logic, new LogicTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New logic created";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->post(getenv("API_ROOT"). "/questions/{questionId}/logics", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }

    $data = [];
    $quMapper = $this->spot->mapper("App\Question");
    $mapper = $this->spot->mapper("App\Logic");
    $mlMapper = $this->spot->mapper("App\MatchLogic");

    $question = $quMapper->findById($arguments['questionId']);

    if ($question === false) {
        throw new NotFoundException("Logic: Question not found", 404);
    } else {
        $questionx = $question->toArray();

        $matchLogic = new MatchLogic();
        $mlMapper->save($matchLogic);

        $body = $request->getParsedBody();
        $body["user_id"] = $this->token->getUser();
        $body["target_id"] = $questionx["uid"];
        $body["target_type"] = "question";
        $body['match_logic_id'] = $matchLogic->uid;

        $logic = new Logic($body);
        $mapper->save($logic);

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $logic->etag());
        $response = $this->cache->withLastModified($response, $logic->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($logic, new LogicTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New logic created";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->get(getenv("API_ROOT"). "/logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read logics.", 403);
    }

    if (false === $logic = $this->spot->mapper("App\Logic")->getOne($arguments["uid"])->first()) {
        throw new NotFoundException("Logic not found.", 404);
    };

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $logic->etag());
    $response = $this->cache->withLastModified($response, $logic->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($logic, new LogicTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.update"])) {
        throw new ForbiddenException("Token not allowed to update logic.", 403);
    }

    /* Load existing logic using provided uid */
    if (false === $logic = $this->spot->mapper("App\Logic")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Logic not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the logic respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $logic->etag(), $logic->timestamp())) {
        throw new PreconditionFailedException("Logic has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $logic->data($body);
    $this->spot->mapper("App\Logic")->save($logic);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $logic->etag());
    $response = $this->cache->withLastModified($response, $logic->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($logic, new LogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Logic updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.update"])) {
        throw new ForbiddenException("Token not allowed to update Logic.", 403);
    }

    /* Load existing logic using provided uid */
    if (false === $logic= $this->spot->mapper("App\Logic")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Logic not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the logic respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $logic->etag(), $logic->timestamp())) {
        throw new PreconditionFailedException("Logic has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $logic->clear();
    $logic->data($body);
    $this->spot->mapper("App\Logic")->save($logic);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $logic->etag());
    $response = $this->cache->withLastModified($response, $logic->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($logic, new LogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Logic updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/logics/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.delete"])) {
        throw new ForbiddenException("Token not allowed to delete logic.", 403);
    }

    /* Load existing logic using provided uid */
    if (false === $logic = $this->spot->mapper("App\Logic")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Logic not found.", 404);
    };

    $logic->removeChildren($this->spot);
    $this->spot->mapper("App\Logic")->delete($logic);
    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    $data["status"] = "ok";
    $data["message"] = "Logic deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
