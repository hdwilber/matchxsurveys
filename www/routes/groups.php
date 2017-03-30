<?php
use App\Group;
use App\Label;
use App\Element;
use App\GroupTransformer;
use App\Question;
use App\QuestionTransformer;
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

$app->post(getenv("API_ROOT"). "/questionaries/{id}/groups/append", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create groups.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $el = $mapper->findById($arguments['id'], ['owned', 'children']);

    if ($el === false) {
        throw NotFoundException("Questionary not found", 404);
    }

    $body = $request->getParsedBody();

    $new = new Element ([
        'data_type' => 'group',
        'user_id' => $this->token->getUser(),
        'code' => $body['code'],
        'parent_id' => $el->id
    ]);
    $mapper->save($new);
    $new->createLabel($mapper, "text", $body['label']);
    $new->createData($mapper, []);

    $new = $mapper->appendIn($el, $new);
    //if ($el->children->count() == 0) {
        //$el->saveData($mapper, ['start_id' => $new->id]);
    //}
    //else {
        //$start = $mapper->findById($el->owned->start_id);
        //$last = $mapper->getLast($start);
        //$mapper->append($last, $new);
    //}

    $response = $this->cache->withEtag($response, $new->etag());
    $response = $this->cache->withLastModified($response, $new->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new GroupTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New group created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/groups/{id}", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read groups.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");

    if (false === $group = $mapper->findById($arguments["id"], ['owned'])) {
        throw new NotFoundException("group not found.", 404);
    };

    $questions = $mapper->listFrom($mapper->findById($group->first_id));

    //$questions = $mapper->findAllByTypeFrom($group, 'question',['owned','children']);
    $logics = $mapper->findAllByTypeFrom($group, 'logic',['owned', 'children']);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $resource = new Item($group, new GroupTransformer);
    $resourceQ = new Collection($questions, new QuestionTransformer);
    $resourceL = new Collection($logics, new LogicTransformer);

    $scopeG = $fractal->createData($resource);
    $scopeQ = $fractal->createData($resourceQ, "questions");
    $scopeL = $fractal->createData($resourceL, "logics");

    $data = $scopeG->toArray();
    $data['data']['questions'] = $scopeQ->toArray()['data'];
    $data['data']['logics'] = $scopeL->toArray()['data'];


    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/groups/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["group.all", "group.update"])) {
        throw new ForbiddenException("Token not allowed to update groups.", 403);
    }

    $mapper = $this->spot->mapper("App\Group");
    /* Load existing group using provided id */
    if (false === $group = $mapper->getById($arguments["id"])) {
        throw new NotFoundException("group not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the group respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $group->etag(), $group->timestamp())) {
        throw new PreconditionFailedException("group has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $group ->data($body);
    $mapper->save($group);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $group->etag());
    $response = $this->cache->withLastModified($response, $group->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($group, new GroupTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "group updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/groups/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["group.all", "group.update"])) {
        throw new ForbiddenException("Token not allowed to update groups.", 403);
    }

    $mapper = $this->spot->mapper("App\Group");

    /* Load existing groups using provided id */
    if (false === $group = $mapper->getById($arguments["id"])){
        throw new NotFoundException("groupnot found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the group respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $group->etag(), $group->timestamp())) {
        throw new PreconditionFailedException("group has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the question object first. */
    $group->clear();
    $group->data($body);
    $mapper->save($group);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $group->etag());
    $response = $this->cache->withLastModified($response, $group->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($group, new GroupTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "group updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/groups/{gid}", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create groups.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $group = $mapper->findById($arguments['gid']);
    if ($group === false) {
        throw NotFoundException("Group not found", 404);
    }

    $parent = $mapper->findById($group->parent_id, ['owned', 'children']);
    if ($parent === false){
        throw NotFoundException("Parent not found", 404);
    }

    switch($parent->data_type) {
    case "questionary": 
        if ($group->id == $parent->first_id && $group->next != null){
            $next = $mapper->findById($group->next_id);
            if ($next === false) {
            } else {
                //$parent->saveData($mapper, ['start_id' => $next->id]);
                $parent->data(['first_id' => $next->id]);
                $mapper->save($parent);
                $next->data(['prev_id'=>null]);
                $mapper->save($next);
            }
        } else {
            if ($group->next_id == null) {
                $prev = $mapper->findById($group->prev_id);
                if ($prev === false) {
                } else {
                    $prev->data(['next_id' => null]);
                    $mapper->save($prev);
                }
            }
        }
        break;
    }

    $mapper->deleteAll($group);

    $data["status"] = "ok";
    $data["message"] = "group deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
