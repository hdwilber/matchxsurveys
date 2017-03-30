<?php
use App\Option;
use App\OptionTransformer;
use App\Question;
use App\QuestionTransformer;
use App\Logic;
use App\LogicTransformer;
use App\Element;
use App\ElementTransformer;
use App\MatchLogicTransformer;
use App\MatchLogic;
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


//$app->get(getenv("API_ROOT"). "/logics/{logicId}/hierarchy", function ($request, $response, $arguments) {
    //$mapper = $this->spot->mapper("App\Logic");

    //$logic = $mapper->findById($arguments['logicId']);
    //if ($logic === false) throw new NotFoundException("Logic: not found", 404);
    //$data['logic']['data']['type'] = 'match-logic';
    //$data['logic']['data']['name'] = 'root';
    //$data['logic']['data']['uid'] = $logic->match_logic_id;
    //$data['logic']['expanded'] = true;
    //$data['logic']['children'] = $logic->buildHierarchy($this->spot);
    //return $response->withStatus(200)
        //->withHeader("Content-Type", "application/json")
        //->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
//});
//$app->get(getenv("API_ROOT"). "/steps/{stepId}/logics", function ($request, $response, $arguments) {

    //if (false === $this->token->hasScope(["question.all", "question.list"])) {
        //throw new ForbiddenException("Token not allowed to list logics.", 403);
    //}
    //$mapper = $this->spot->mapper("App\Logic");
    //$stMapper = $this->spot->mapper("App\Step");

    //$step = $stMapper->findById($arguments['stepId']);

    //if ($step === false) {
        //throw new NotFoundException("Logic: Step not found", 404);
    //} else {
        //$first = $mapper->findLastModifiedFromStep($step);
        //if ($first) {
            //$response = $this->cache->withEtag($response, $first->etag());
            //$response = $this->cache->withLastModified($response, $first->timestamp());
        //}

        //if ($this->cache->isNotModified($request, $response)) {
            //return $response->withStatus(304);
        //}

        //$logics = $mapper->findAllFromStep($step);

        //$fractal = new Manager();
        //$fractal->setSerializer(new DataArraySerializer);
        //$resource = new Collection($logics, new LogicTransformer);
        //$data = $fractal->createData($resource)->toArray();

        //return $response->withStatus(200)
            //->withHeader("Content-Type", "application/json")
            //->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    //}
//});
$app->get(getenv("API_ROOT"). "/elements/{id}/logics", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to list logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Logic");
    $e = $mapper->getMapper("App\Element")->findById($arguments['id']);

    if ($e === false) {
        throw new NotFoundException("Logic: Element not found", 404);
    } 
    $logics = $mapper->getMapper("App\Element")->findAllByTypeFrom($e, "logic");

    $result = [];
    foreach($logics as $l) {
        $ret = $mapper->findAllRecursive($l);
        array_push($result, $ret);
    }

    $data['data'] = $result;


    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    //$resource = new Collection($logics, new LogicTransformer);
    //$data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/match-logics/{id}/matchs", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $ml = $mapper->findById($arguments['id'], ['owned', 'children']);
    if ($ml === false) {
        throw new NotFoundException("Logic: Match Logic not found", 404);
    }
    $data = [];

    if ($ml->children->count() == 0) {
        $body = $request->getParsedBody();
        $new = new Element([
            'data_type' => 'match',
            'user_id' => $this->token->getUser(),
            'code' => (isset($body['code'])? $body['code'] : null),
            'parent_id' => $ml->id
        ]);
        $mapper->save($new);
        $new->createLabel($mapper, "text", "");
        $match = $new->createData($mapper, [
            'operator' => isset($body['operator']) ? $body['operator'] : 'eq',
            'target_id' => $body['target_id'],
            'target_option_id' => (isset($body['target_option_id']) ? $body['target_option_id'] : null),
            'target_value' => (isset($body['target_value']) ? $body['target_value'] : null),
        ]);

        $mapper->appendIn($ml, $new);


        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($new, new MatchTransformer);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["message"] = "New match created";
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        $data["status"] = "failed";
        $data["message"] = "MatchLogic has Match";
        return $response->withStatus(403)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /* Serialize the response data. */
});

$app->post(getenv("API_ROOT"). "/elements/{id}/match-logics", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $parent = $mapper->findById($arguments['id'], ['owned', 'children']);
    if ($parent === false) {
        throw new NotFoundException("Element not found", 404);
    }

    $body = $request->getParsedBody();
    if ($parent->children->count() == 0) {
        $bool = null;
    } else {
        $bool = isset($body['bool'])? $body['bool'] : 'and';
    }
    $new = new Element([
        'data_type' => 'match-logic',
        'user_id' => $this->token->getUser(),
        'code' => ($bool != null ? $bool: null),
        'parent_id' => $parent->id
    ]);
    $mapper->save($new);
    $new->createLabel($mapper, "text", $bool);
    $mml = $new->createData($mapper, [
        'bool' => $bool
    ]);

    $new = $mapper->appendIn($parent, $new);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new MatchLogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New match logic appended";
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->post(getenv("API_ROOT"). "/match-logics/{id}/append", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $ml = $mapper->findById($arguments['id'], ['owned', 'children']);
    if ($ml=== false) {
        throw new NotFoundException("Match Logic: Match Logic not found", 404);
    }

    $body = $request->getParsedBody();
    if ($ml->children->count() == 0) {
        $bool = null;
    } else {
        $bool = isset($body['bool'])? $body['bool'] : 'and';
    }
    $new = new Element([
        'data_type' => 'match-logic',
        'user_id' => $this->token->getUser(),
        'code' => ($bool != null ? $bool: null),
        'parent_id' => $ml->id
    ]);
    $mapper->save($new);
    $new->createLabel($mapper, "text", $bool);
    $mml = $new->createData($mapper, [
        'bool' => $bool
    ]);

    $new = $mapper->appendIn($ml, $new);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new MatchLogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New match logic created";
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/logics/{id}/match-logics/append", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $logic = $mapper->findById($arguments['id']);
    if ($logic === false) {
        throw new NotFoundException("Logic: Logic not found", 404);
    }

    $body = $request->getParsedBody();
    if ($logic->children->count() == 0) {
        $bool = null;
    } else {
        $bool = isset($body['bool'])? $body['bool'] : 'and';
    }
    $new = new Element([
        'data_type' => 'match-logic',
        'user_id' => $this->token->getUser(),
        'code' => ($bool != null ? $bool: null),
        'parent_id' => $logic->id
    ]);
    $mapper->save($new);
    $new->createLabel($mapper, "text", $bool);
    $ml = $new->createData($mapper, [
        'bool' => $bool
    ]);

    $new = $mapper->appendIn($logic, $new);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new MatchLogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New match logic created";
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/elements/{id}/logics", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $element = $mapper->findById($arguments['id']);
    if ($element === false) {
        throw new NotFoundException("Logic: Element not found", 404);
    }

    $body = $request->getParsedBody();
    $new = new Element([
        'data_type' => 'logic',
        'user_id' => $this->token->getUser(),
        'code' => $body['code'] ? : null,
        'parent_id' => $element->id
    ]);
    $root = $mapper->findRoot($element);
    if ($root === false) {
        throw new NotFoundException("Root: Unable to find ROot", 404);
    }

    $mapper->save($new);
    $new->createLabel($mapper, "text", $body['action']);
    $logic = $new->createData($mapper, [
        'action' => $body['action'],
        'questionary_id' => $root->id
    ]);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new LogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New logic created";
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/questions/{id}/logics", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create logics.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");
    $question = $mapper->findById($arguments['id']);
    if ($question === false) {
        throw new NotFoundException("Logic: Question not found", 404);
    }

    $body = $request->getParsedBody();
    $new = new Element([
        'data_type' => 'logic',
        'user_id' => $this->token->getUser(),
        'code' => null,
        'parent_id' => $question->id
    ]);


    $root = $mapper->findRoot($question);
    if ($root === false) {
        throw new NotFoundException("Root: Unable to find ROot", 404);
    }

    $mapper->save($new);
    $new->createLabel($mapper, "text", $body['action']);
    $logic = $new->createData($mapper, [
        'action' => $body['action'],
        'questionary_id' => $root->id
    ]);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($new, new LogicTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New logic created";
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/logics/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read logics.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $logic = $mapper->findById($arguments['id']);
    if (false === $logic){
        throw new NotFoundException("Logic not found.", 404);
    }

    $hierarchy = $mapper->getMapper("App\Logic")->findHierarchy($logic);

    //$data['data'] = $all;

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($logic, new LogicTransformer);
    $data = $fractal->createData($resource)->toArray();

    $data['data']['children'] = $hierarchy;
    $data["status"] = "ok";
    $data["message"] = "Logic Retrieved";

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

$app->delete(getenv("API_ROOT"). "/logics/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.delete"])) {
        throw new ForbiddenException("Token not allowed to delete logic.", 403);
    }

    /* Load existing logic using provided uid */
    $mapper = $this->spot->mapper("App\Element");
    $logic = $mapper->findById($arguments['id']);
    if ($logic === false){
        throw new NotFoundException("Logic not found.", 404);
    };

    $mapper->deleteRecursive($logic);
    $mapper->deleteAll($logic);

    $data["status"] = "ok";
    $data["message"] = "Logic deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->delete(getenv("API_ROOT"). "/match-logics/{id}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["option.all", "option.delete"])) {
        throw new ForbiddenException("Token not allowed to delete match logic.", 403);
    }

    /* Load existing logic using provided uid */
    $mapper = $this->spot->mapper("App\Element");
    $ml = $mapper->findById($arguments['id']);
    if ($ml === false){
        throw new NotFoundException("MatchLogic not found.", 404);
    };

    $mapper->deleteRecursive($ml);
    $mapper->deleteAll($ml);

    $data["status"] = "ok";
    $data["message"] = "Logic deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
