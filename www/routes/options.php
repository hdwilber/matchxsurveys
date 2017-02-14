<?php
use App\Option;
use App\OptionTransformer;
use App\Question;
use App\QuestionTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/questions/{questionId}/options", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list options.", 403);
    }

    $first = $this->spot->mapper("App\Option")
        ->listOptions($arguments['questionId'])->first();
    if ($first) {
        $response = $this->cache->withEtag($response, $first->etag());
        $response = $this->cache->withLastModified($response, $first->timestamp());
    }

    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    $options = $this->spot->mapper("App\Option")
        ->all()
        ->where(['question_id' => $arguments["questionId"]])
        ->order(["sort" => "DESC"]);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($options, new OptionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/questions/{questionId}/options", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create options.", 403);
    }

    $mapper = $this->spot->mapper("App\Option");
    $quMapper = $this->spot->mapper("App\Question");

    $question = $quMapper->findById($arguments["questionId"]);
    if ($question === false) {
        throw new NotFoundException("Question not found", 404);
    }
    $questionx = $question->toArray();
    $body = $request->getParsedBody();
    $body["user_id"] = $this->token->getUser();
    $body["question_id"] = $questionx["uid"];
    $body['sort'] = (integer)($mapper->countFromQuestion($questionx)) + 1;

    $option = new Option($body);
    $mapper->save($option);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $option->etag());
    $response = $this->cache->withLastModified($response, $option->timestamp());

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($option, new OptionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New option created";
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/options/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read options.", 403);
    }

    if (false === $option = $this->spot->mapper("App\Option")->getOne($arguments["uid"])->first()) {
        throw new NotFoundException("Option not found.", 404);
    };

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $option->etag());
    $response = $this->cache->withLastModified($response, $option->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($option, new OptionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/options/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.update"])) {
        throw new ForbiddenException("Token not allowed to update options.", 403);
    }

    /* Load existing option using provided uid */
    if (false === $option = $this->spot->mapper("App\Option")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Option not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the option respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $option->etag(), $option->timestamp())) {
        throw new PreconditionFailedException("Option has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $option ->data($body);
    $this->spot->mapper("App\Option")->save($option);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $option->etag());
    $response = $this->cache->withLastModified($response, $option->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($option, new OptionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Option updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/options/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.update"])) {
        throw new ForbiddenException("Token not allowed to update Option.", 403);
    }

    /* Load existing option using provided uid */
    if (false === $option= $this->spot->mapper("App\Option")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Option not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the option respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $option->etag(), $option->timestamp())) {
        throw new PreconditionFailedException("Option has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $option->clear();
    $option->data($body);
    $this->spot->mapper("App\Option")->save($option);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $option->etag());
    $response = $this->cache->withLastModified($response, $option->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($option, new OptionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Option updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/options/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.delete"])) {
        throw new ForbiddenException("Token not allowed to delete option.", 403);
    }

    /* Load existing option using provided uid */
    if (false === $option = $this->spot->mapper("App\Option")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Option not found.", 404);
    };

    $this->spot->mapper("App\Option")->delete($option);

    $data["status"] = "ok";
    $data["message"] = "Option deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
