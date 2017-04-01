<?php
use App\Question;
use App\Label;
use App\QuestionTransformer;
use App\OptionTransformer;
use App\LogicTransformer;
use App\Element;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->post(getenv("API_ROOT"). "/groups/{id}/questions/append", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $group = $mapper->findById($arguments['id'], ['owned', 'children']);
    if ($group === false) {
        throw new NotFoundException("Group not found", 404);
    }
    $body = $request->getParsedBody();

    $new = new Element([
        'data_type' => 'question',
        'user_id'=> $this->token->getUser(),
        'code' => $body['code'],
        'parent_id' => $group->id
    ]);

    $mapper->save($new);
    $new->createLabel($mapper, "text", $body['label']);
    $question = $new->createData($mapper, [
        'type'=>$body['type'], 
        'sub_type' => (isset($body['sub_type'])? $body['sub_type'] : null),
        'default_visibility' => (isset($body['default_visibility']) ? $body['default_visibility'] : null)
    ]);

    $mapper->appendIn($group, $new);

    //if($group->children->count() == 0){
        //$group->saveData($mapper, ['start_id' => $new->id]);
    //} else {
        //$start = $mapper->findById($group->owned->start_id);
        //$mapper->append($start, $new);
    //}

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New question created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/groups/{id}/questions", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");

    if (false === $group = $mapper->findById($arguments["id"], ['owned', 'children'])){
        throw new NotFoundException("Group not found.", 404);
    }





    //$questions = $mapper->listFrom($mapper->findById($group->owned->start_id));
    $questions = $mapper->listFrom($mapper->findById($group->first_id));
    
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($questions, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get(getenv("API_ROOT"). "/questions/{id}/list", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Question");

    if (false === $question = $mapper->findById($arguments["id"])){
        throw new NotFoundException("Question not found Da Fuaq.", 404);
    }

    $data['list'] = $mapper->list($question);

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/questions", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");

    $first = $mapper->findLastModified();

    if ($first) {
        $response = $this->cache->withEtag($response, $first->etag());
        $response = $this->cache->withLastModified($response, $first->timestamp());
    }

    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    $questions = $mapper->findAllByType("question");

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($questions, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/questions", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $body = $request->getParsedBody();
    $body["user_id"] = $this->token->getUser();

    $element = new Element([
        'data_type' => 'question',
        'user_id'=> $this->token->getUser(),
        'code' => $body['code']
    ]);
    $mapper->save($element);
    $element->createLabel($mapper, "text", $body['label']);
    $question = $element->createData($mapper, [
        'type'=>$body['type'], 
        'sub_type' => (isset($body['sub_type'])? $body['sub_type'] : null)
    ]);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($element, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New question created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->get(getenv("API_ROOT"). "/questions/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");

    if (false === $question= $mapper->findById($arguments["id"]))
    {
        throw new NotFoundException("Question not found .", 404);
    };

    $logics = $mapper->findAllByTypeFrom($question, 'logic', ['owned', 'children']);
    $options = $mapper->findAllByTypeFrom($question, 'option', ['owned', 'children']);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $resource = new Item($question, new QuestionTransformer);
    $resourceL = new Collection($logics, new LogicTransformer);
    $resourceO = new Collection($options, new OptionTransformer);

    $data = $fractal->createData($resource)->toArray();
    $scopeL = $fractal->createData($resourceL, 'logics');
    $scopeO = $fractal->createData($resourceO, 'options');

    $data['data']['logics'] = $scopeL->toArray()['data'];
    $data['data']['options'] = $scopeO->toArray()['data'];

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/questions/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    /* Load existing question using provided id */
    if (false === $element = $mapper->findById($arguments["id"])){
        throw new NotFoundException("Question not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }
    if (false === $this->cache->hasCurrentState($request, $question->etag(), $question->timestamp())) {
        throw new PreconditionFailedException("Question has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $element->question->data($body);
    $mapper->getMapper('App\Question')->save($element->question);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $element->question->etag());
    $response = $this->cache->withLastModified($response, $element->question->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($element, new QuestionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Question updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/questions/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.update"])) {
        throw new ForbiddenException("Token not allowed to update question.", 403);
    }
    $mapper = $this->spot->mapper("App\Question");

    /* Load existing question using provided id */
    if (false === $question= $mapper->findById($arguments["id"])) {
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

$app->delete(getenv("API_ROOT"). "/questions/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $question = $mapper->findById($arguments['id']);
    if ($question === false) {
        throw new NotFoundException("Question not found", 404);
    }

    $parent = $mapper->findById($question->parent_id);
    if ($parent === false) {
        throw NotFoundException("Parent not found", 404);
    }

    switch($parent->data_type) {
    case "group": 
        if ($question->id == $parent->first_id && $question->next != null){
            $next = $mapper->findById($question->next_id);
            if ($next === false) {
            } else {
                //$parent->saveData($mapper, ['start_id' => $next->id]);
                $parent->data(['first_id'=> $next->id]);
                $mapper->save($parent);
                $next->data(['prev_id'=>null]);
                $mapper->save($next);
            }
        } else {
            if ($question->next_id == null) {
                $prev = $mapper->findById($question->prev_id);
                if ($prev === false) {
                } else {
                    $prev->data(['next_id' => null]);
                    $mapper->save($prev);
                }
            }
        }
        break;
    }
    $mapper->deleteAll($question);

    $data["status"] = "ok";
    $data["message"] = "Question deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});
