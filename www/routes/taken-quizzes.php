<?php
use App\Question;
use App\Logic;
use App\MatchLogic;
use App\Match;
use App\QuestionTransformer;
use App\TakenQuiz;
use App\TakenQuizTransformer;
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

$app->get(getenv("API_ROOT"). "/taken-quizzes/{takenQuizId}/questions/{questionId}/evaluate", function ($request, $response, $arguments) {
    $mapper = $this->spot->mapper("App\TakenQuiz");
    $quMapper = $this->spot->mapper("App\Question");
    
    $takenQuiz = $mapper->findById($arguments['takenQuizId']);
    if ($takenQuiz === false) throw new NotFoundException("Taken Quiz not found", 404);
    $question = $quMapper->findById($arguments['questionId']);
    if ($question === false) throw new NotFoundException("Taken Quiz: Question not found", 404);

    $data = [];
    $this->logger->addInfo("Logics Array", $question->logics->toArray());
    foreach($question->logics as $logic) {
        $data[$logic->action] = $logic->evaluate($this->spot, $takenQuiz );
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));


});

$app->get(getenv("API_ROOT"). "/taken-quizzes", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list Taken Quizzes.", 403);
    }
    $mapper = $this->spot->mapper("App\TakenQuiz");

    $first = $mapper->findLastModifiedFromUser($this->token->getUser());
    if ($first === false) {
        throw new NotFoundException("TakenQuiz not found", 404);
    } else {
        $response = $this->cache->withEtag($response, $first->etag());
        $response = $this->cache->withLastModified($response, $first->timestamp());
        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $tqs = $mapper->findAllFromUser($this->token->getUser());

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($tqs, new TakenQuizTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    }
});

$app->post(getenv("API_ROOT"). "/taken-quizzes", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create taken quizzes.", 403);
    }

    $mapper = $this->spot->mapper("App\TakenQuiz");
    $quMapper = $this->spot->mapper("App\Questionary");

    $body = $request->getParsedBody();

    $questionary = $quMapper->findById($body['questionary_id']);
    if ($questionary === false) {
        throw new NotFoundException("Questionary not found", 404);
    } else {
        $body["user_id"] = $this->token->getUser();

        $tq = new TakenQuiz($body);
        $mapper->save($tq);

        /* Add Last-Modified and ETag headers to response. */
        $response = $this->cache->withEtag($response, $tq->etag());
        $response = $this->cache->withLastModified($response, $tq->timestamp());
        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($tq, new TakenQuizTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New taken quiz created";

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Location", $data["data"]["links"]["self"])
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    }
});

$app->get(getenv("API_ROOT"). "/taken-quizzes/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read taken quizzes.", 403);
    }
    $mapper = $this->spot->mapper("App\TakenQuiz");

    if (false === $tq= $mapper->findById($arguments["uid"]))
    {
        throw new NotFoundException("Taken Quiz not found.", 404);
    };


    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $tq->etag());
    $response = $this->cache->withLastModified($response, $tq->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($tq, new TakenQuizTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/taken-quizzes/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update Taken Quizzes.", 403);
    }

    $mapper = $this->spot->mapper("App\TakenQuiz");
    /* Load existing question using provided uid */
    if (false === $tq= $mapper->getById($arguments["uid"])){
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
    $tq->data($body);
    $mapper->save($tq);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $tq->etag());
    $response = $this->cache->withLastModified($response, $tq->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($tq, new TakenQuizTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "TakenQuiz updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/taken-quizzes/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update taken quizzes.", 403);
    }
    $mapper = $this->spot->mapper("App\TakenQuiz");

    /* Load existing question using provided uid */
    if (false === $tq= $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("TakenQuiz not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the question respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $tq->etag(), $tq->timestamp())) {
        throw new PreconditionFailedException("Taken Quiz has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $tq->clear();
    $tq->data($body);
    $mapper->save($tq);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $tq->etag());
    $response = $this->cache->withLastModified($response, $tq->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($tq, new TakenQuizTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "TakenQuiz updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/taken-quizzes/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete Taen Quizz.", 403);
    }
    $mapper = $this->spot->mapper("App\TakenQuiz");

    /* Load existing wquestion using provided uid */
    if (false === $tq= $mapper->getById($arguments["uid"])) {
        throw new NotFoundException("Taken Quiz not found.", 404);
    };

    $mapper->delete($tq);

    $data["status"] = "ok";
    $data["message"] = "Taken Quiz deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
