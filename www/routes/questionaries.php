<?php
use App\Questionary;
use App\QuestionaryTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/questionaries", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list questionaries.", 403);
    }

    $mapper = $this->spot->mapper("App\Questionary");
    $first = $mapper->findLastModified();
    if ($first) {
        $response = $this->cache->withEtag($response, $first->etag());
        $response = $this->cache->withLastModified($response, $first->timestamp());
    }

    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }
    $questionaries = $mapper->findAll();

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($questionaries, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/questionaries", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questionaries.", 403);
    }

    $mapper = $this->spot->mapper("App\Questionary");
    $body = $request->getParsedBody();
    $body["user_id"] = $this->token->getUser();

    $questionary = new Questionary($body);
    $mapper->save($questionary);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $questionary->etag());
    $response = $this->cache->withLastModified($response, $questionary->timestamp());

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($questionary, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New questionary created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/questionaries/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read questionaries.", 403);
    }
    $mapper = $this->spot->mapper("App\Questionary");

    if (false === $questionary = $mapper->findById($arguments["uid"]))
    {
        throw new NotFoundException("Questionary not found.", 404);
    };

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $questionary->etag());
    $response = $this->cache->withLastModified($response, $questionary->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }


    $questionary->steps = $this->spot->mapper("App\Step")->findAllSortedFromQuestionary($questionary);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($questionary, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/questionary/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update questionaries.", 403);
    }

    $mapper = $this->spot->mapper("App\Questionary");
    /* Load existing question using provided uid */
    if (false === $questionary = $mapper->getById($arguments["uid"])){
        throw new NotFoundException("Questionary not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the question respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $questionary->etag(), $questionary->timestamp())) {
        throw new PreconditionFailedException("Questionary has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $questionary->data($body);
    $mapper->save($questionary);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $questionary->etag());
    $response = $this->cache->withLastModified($response, $questionary->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($questionary, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Questionary updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/questionaries/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update questionary.", 403);
    }
    $mapper = $this->spot->mapper("App\Questionary");

    /* Load existing question using provided uid */
    if (false === $questionary = $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Questionary not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the question respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $questionary->etag(), $questionary->timestamp())) {
        throw new PreconditionFailedException("Questionary has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $questionary->clear();
    $questionary->data($body);
    $mapper->save($questionary);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $questionary->etag());
    $response = $this->cache->withLastModified($response, $questionary->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($questionary, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Questionary updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/questionaries/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete questionaries.", 403);
    }
    $mapper = $this->spot->mapper("App\Questionary");

    /* Load existing wquestion using provided uid */
    if (false === $questionary = $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Questionary not found.", 404);
    };

    $mapper->delete($questionary);

    $data["status"] = "ok";
    $data["message"] = "Questionary deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
