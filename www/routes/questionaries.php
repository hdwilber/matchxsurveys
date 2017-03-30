<?php
use App\Questionary;
use App\Element;
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

    $mapper = $this->spot->mapper("App\Element");

    $questionaries = $mapper->findAllByType('questionary', ['owned', 'children']);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($questionaries, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();
    //$data['child'] = $paMapper->findById($questionaries[0]->owned->group_id, ['children']);

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/questionaries/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list questionaries.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $questionary = $mapper->findById($arguments['id'], ['owned', 'children']);

    if ($questionary === false) {
        throw new  NotFoundException("Questionary not found", 404);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($questionary, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/questionaries", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questionaries.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $body = $request->getParsedBody();

    $element = new Element ( [
        'data_type' => 'questionary',
        'user_id' => $this->token->getUser(),
        'code' => $body['code'],
    ]);

    $mapper->save($element);
    $element->createLabel($mapper, "text", $body['label']);
    $questionary = $element->createData($mapper, []);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $element->etag());
    $response = $this->cache->withLastModified($response, $element->timestamp());

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($element, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New questionary created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/questionaries/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update Questionarties.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    /* Load existing question using provided uid */
    if (false === $element = $mapper->getById($arguments["id"])){
        throw new NotFoundException("TakenQuiz not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the question respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $tq->etag(), $tq->timestamp())) {
        throw new PreconditionFailedException("Taken Quiz has been modified.", 412);
    }

    $body = $request->getParsedBody();
    if (isset($body['label'])) {
        $mapper->changeLabelText($element, $body['label']);
    }
    if (isset($body['code'])){
        $element->data(['code' => $body['code']]);
        $mapper->save($element);
    }

    $response = $this->cache->withEtag($response, $element->etag());
    $response = $this->cache->withLastModified($response, $element->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($element, new QuestionaryTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Questionary has been updated";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});

$app->delete(getenv("API_ROOT"). "/questionaries/{id}", function ($request, $response, $arguments) {
    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");

    /* Load existing wquestion using provided id */
    if (false === $element = $mapper->findById($arguments["id"])) {
        throw new NotFoundException("Questionary not found.", 404);
    };

    $mapper->deleteAll($element);

    $data["status"] = "ok";
    $data["message"] = "Questionary deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});
