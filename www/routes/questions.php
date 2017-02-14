<?php
use App\Question;
use App\QuestionTransformer;
use App\Step;
use App\StepTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/steps/{stepId}/questions", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Question");
    $stMapper = $this->spot->mapper("App\Step");

    $step = $stMapper->findById($arguments['stepId']);
    if ($step === false) {
        throw new NotFoundException("Step not found", 404);
    } else {
        $stepx = $step->toArray();
        $first = $mapper->findLastModifiedFromStep($stepx);

        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $questions = $mapper->findAllFromStep($stepx);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($questions, new QuestionTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->post(getenv("API_ROOT"). "/steps/{stepId}/questions", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Question");
    $stMapper = $this->spot->mapper("App\Step");

    $step = $stMapper->findById($arguments['stepId']);
    if ($step === false) {
        throw new NotFoundException("Step not found", 404);
    } else {
        $stepx = $step->toArray();
        $body = $request->getParsedBody();
        $body["user_id"] = $this->token->getUser();
        $body["step_id"] = $stepx['uid'];

        $question = new Question($body);
        $mapper->save($question);

        if ($stepx['start_id'] == null) {
            $step->data(['start_id' => $question->toArray()['uid']]);
            $stMapper->save($step);
        } else {
            $question_aux = $mapper->findById($stepx['start_id']);
            $question_auxx = $question_aux->toArray();

            $this->logger->addInfo("Starting to cycle to find last Question");

            while($question_auxx['next_id'] != null) {
                $question_aux = $mapper->findById($question_auxx['next_id']);
                $question_auxx = $question_aux->toArray();
            }

            $question_aux->data(['next_id' => $question->toArray()['uid']]);
            $mapper->save($question_aux);

        }

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $question->etag());
        $response = $this->cache->withLastModified($response, $question->timestamp());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($question, new QuestionTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New question created";

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});

$app->get(getenv("API_ROOT"). "/questions/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Question");

    if (false === $question= $mapper->getById($arguments["uid"]))
    {
        throw new NotFoundException("Question not found.", 404);
    };


    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $question->etag());
    $response = $this->cache->withLastModified($response, $question->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($question, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/questions/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Question");
    /* Load existing question using provided uid */
    if (false === $question = $mapper->getById($arguments["uid"])){
        throw new NotFoundException("Question not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the question respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $question->etag(), $question->timestamp())) {
        throw new PreconditionFailedException("Question has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $question->data($body);
    $mapper->save($question);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $question->etag());
    $response = $this->cache->withLastModified($response, $question->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($question, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Question updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/questions/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update question.", 403);
    }
    $mapper = $this->spot->mapper("App\Question");

    /* Load existing question using provided uid */
    if (false === $question= $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Question not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the question respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $question->etag(), $question->timestamp())) {
        throw new PreconditionFailedException("Question has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $question->clear();
    $question->data($body);
    $mapper->save($question);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $question->etag());
    $response = $this->cache->withLastModified($response, $question->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($question, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Question updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/questions/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Question");

    /* Load existing wquestion using provided uid */
    if (false === $question = $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Question not found.", 404);
    };

    $mapper->delete($question);

    $data["status"] = "ok";
    $data["message"] = "Question deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
