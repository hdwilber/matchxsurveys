<?php
use App\Step;
use App\StepTransformer;
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


$app->get(getenv("API_ROOT"). "/questionaries/{questionaryId}/steps", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["step.all", "question.all", "question.list", "step.list", "step.read"])) {
        throw new ForbiddenException("Token not allowed to list steps.", 403);
    }
    $mapper = $this->spot->mapper("App\Step");
    $quMapper = $this->spot->mapper("App\Questionary");

    $questionary = $quMapper->findById($arguments['questionaryId']);
    if ($questionary === false) {
        throw new NotFoundException("Questionary not found.", 404);
    } else {
        $questionaryx = $questionary->toArray();

        $first = $mapper->findLastModifiedFromQuestionary($questionaryx);
        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }
        $steps = $mapper->findAllSortedFromQuestionary($questionary);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($steps, new StepTransformer);



        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

});

$app->post(getenv("API_ROOT"). "/questionaries/{questionaryId}/steps", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create steps.", 403);
    }

    $mapper = $this->spot->mapper("App\Step");
    $quMapper = $this->spot->mapper("App\Questionary");

    $questionary = $quMapper->findById($arguments['questionaryId']);
    if ($questionary === false) {
        throw new NotFoundException("Questionary not found.", 404);
    } else {
        $questionaryx = $questionary->toArray();
        $body = $request->getParsedBody();
        $body["user_id"] = $this->token->getUser();
        $body["questionary_id"] = $arguments["questionaryId"];
        $this->logger->addInfo($body, $body);
        $step = new Step($body);
        $mapper->save($step);

        if ($questionaryx['start_id'] == null) {
            $q['start_id'] = $step->toArray()['uid'];
            $questionary->data($q);
            $quMapper->save($questionary);
        } else {
            $this->logger->addInfo("Starting to find the end of steps");
            $step_aux = $mapper->findById($questionaryx['start_id']);
            $step_auxx = $step_aux->toArray();

            $this->logger->addInfo("Found this one: STEP", $step_auxx);
            // It needs to be improved
            while($step_auxx['next_id'] != null) {
                $step_aux = $mapper->findById($step_auxx['next_id']);
                $step_auxx = $step_aux->toArray();
                $this->logger->addInfo("This is the stage", $step_auxx);
            }

            $step_aux->data(["next_id" => $step->toArray()['uid']]);
            $mapper->save($step_aux);
        }
        

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $step->etag());
        $response = $this->cache->withLastModified($response, $step->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($step, new StepTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New step created";

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Location", $data["data"]["links"]["self"])
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->get(getenv("API_ROOT"). "/steps/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read steps.", 403);
    }
    $mapper = $this->spot->mapper("App\Step");

    if (false === $step = $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Step not found.", 404);
    };

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $step->etag());
    $response = $this->cache->withLastModified($response, $step->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($step, new StepTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/steps/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["step.all", "step.update"])) {
        throw new ForbiddenException("Token not allowed to update steps.", 403);
    }

    $mapper = $this->spot->mapper("App\Step");
    /* Load existing step using provided uid */
    if (false === $step = $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Step not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the step respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $step->etag(), $step->timestamp())) {
        throw new PreconditionFailedException("step has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $step ->data($body);
    $mapper->save($step);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $step->etag());
    $response = $this->cache->withLastModified($response, $step->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($step, new StepTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Step updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/steps/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["step.all", "step.update"])) {
        throw new ForbiddenException("Token not allowed to update Steps.", 403);
    }

    $mapper = $this->spot->mapper("App\Step");

    /* Load existing Steps using provided uid */
    if (false === $step = $mapper->getById($arguments["uid"])){
        throw new NotFoundException("Stepnot found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the step respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $step->etag(), $step->timestamp())) {
        throw new PreconditionFailedException("Step has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $step->clear();
    $step->data($body);
    $mapper->save($step);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $step->etag());
    $response = $this->cache->withLastModified($response, $step->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($step, new StepTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Step updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/steps/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["step.all", "step.delete"])) {
        throw new ForbiddenException("Token not allowed to delete Step.", 403);
    }

    $mapper = $this->spot->mapper("App\Step");
    /* Load existing step using provided uid */
    if (false === $step = $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Step not found.", 404);
    };

    $mapper->delete($step);

    $data["status"] = "ok";
    $data["message"] = "Step deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
