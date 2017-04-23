<?php
use App\Question;
use App\Label;
use App\QuestionTransformer;
use App\QuestionaryTransformer;
use App\TakenQuizTransformer;
use App\OptionTransformer;
use App\LogicTransformer;
use App\Element;
use App\ElementTransformer;
use App\MatchLogicTransformer;
use App\GroupTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/elements/{id}/test", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\Element");
    $el = $mapper->findById($arguments['id'], ['owned','label']);
    if ($el === false) {
        throw new NotFoundException("Element not found", 404);
    }

    $data['data'] = $el->toArray();
    $data["status"] = "ok";
    $data["message"] = "List of Adding Places retrieved";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/elements/addingTypes", function ($request, $response, $arguments) {

    $data['data'] = Element::$addingTypes;
    $data["status"] = "ok";
    $data["message"] = "List of Adding Places retrieved";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT") . "/elements/types", function ($request, $response, $arguments) {

    $data['data'] = Element::$dataTypes;
    $data["status"] = "ok";
    $data["message"] = "Listed children created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->get(getenv("API_ROOT"). "/elements/{id}", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $el = $mapper->findById($arguments['id'], ['owned','label']);
    if ($el === false) {
        throw new NotFoundException("Element not found", 404);
    }

    $data['data'] = $mapper->findAllRecursive($el);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $data["status"] = "ok";
    $data["message"] = "Listed children created recursvie";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});

$app->get(getenv("API_ROOT"). "/elements/{id}/children", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $data = [];
    $mapper = $this->spot->mapper("App\Element");

    if ($arguments['id'] == '0' ) {
        $mapper = $this->spot->mapper("App\Element");
        $qs = $mapper->getRoots("questionary");

        $tq = $mapper->getRoots("taken-quiz");

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);

        $dqs = $fractal->createData( new Collection ($qs, new ElementTransformer) )->toArray();
        $dtq = $fractal->createData( new Collection ($tq, new ElementTransformer) )->toArray();

        $data['data']['questionaries'] = ['id' => 'Q0', 'code' => 'Q', 'type'=> 'root', 'label' => ['type' => 'text', 'data' => 'Questionaries'], 'children' => $dqs['data']];
        $data['data']['taken-quizzes'] = ['id'=>'TQ0', 'code'=>'TQ', 'type'=> 'root', 'label' => ['type' => 'text', 'data' => 'Taken Quizzes'], 'children' => $dtq['data']];
    } else {
        $el = $mapper->findById($arguments['id'], ['owned','label']);
        if ($el === false) {
            throw new NotFoundException("Element not found", 404);
        }

        if ($el->first_id) {
            $first = $mapper->findById($el->first_id, ['owned', 'label']);

            $children = $mapper->listFrom($first);
            $list = $fractal->createData( new Collection ($children, new ElementTransformer) )->toArray();
            $data['data'] = $list;
        }
    }

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);


    $data["status"] = "ok";
    $data["message"] = "Listed children created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});

$app->get(getenv("API_ROOT"). "/elements/roots/all", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $qs = $mapper->getRoots("questionary");
    $tq = $mapper->getRoots("taken-quiz");

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $dqs = $fractal->createData( new Collection ($qs, new ElementTransformer) )->toArray();
    $dtq = $fractal->createData( new Collection ($tq, new ElementTransformer) )->toArray();

    $data['data']['questionaries'] = $dqs['data'];
    $data['data']['taken-quizzes'] = $dtq['data'];

    $data["status"] = "ok";
    $data["message"] = "New Element created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT") . "/elements/{id}/move", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $el = $mapper->findById($arguments['id'], ['owned','label']);
    if ($el === false) {
        throw new NotFoundException("Element not found", 404);
    }

    $body = $request->getParsedBody();
    $eRef = $mapper->findById($body['eRef'], ['owned','label']);
    if ($eRef === false) {
        throw new NotFoundException("Element EREF not found", 404);
    }

    $body['addingType'] = isset($body['addingType']) ? $body['addingType'] : 'append-in';

    $result = $mapper->move ($eRef, $el, $body['addingType']);

    $data['data'] = isset($result);
    $data["status"] = "ok";
    $data["message"] = "Moved Element";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    

});

/*
 *POST DATA
 *  eRef: Reference from parent or null
 *  type: Type of data
 *  addingType:
 *  code: Code
 *  labelType: When null, text
 *  labelData: Label data
 *  FOR QUESTION
 *  qType: 
 *  qSubType:
 *  Visibility: default true;
 */

$app->patch(getenv("API_ROOT"). "/elements/{id}", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $body = $request->getParsedBody();


    $el = $mapper->findById($body['id'], ['label', 'owned', 'children']);
    if ($el === false) {
        throw new NotFoundException("Reference not found", 404);
    }

    $body['code'] = isset($body['code']) ? $body['code']: $el->code;
    // Element 
    $el->data([
        // Not implemented yet
        'data_type' => $el->data_type,
        'code' => $body['code']
    ]);

    $mapper->save($el);

    // Label Stuff

    $body['labelType'] = isset($body['labelType']) ? $body['labelType'] : "text";
    $body['labelData'] = isset($body['labelData']) ? $body['labelData'] : $el->label->data;

    $el->label->data([
        'type' => $body['labelType'],
        'data' => $body['labelData']
    ]);
    $mapper->getMapper('App\Label')->save($el->label);

    // Data
    $data = [];

    switch($el->data_type){
        case 'question': 
            $data = [
                'type'=>isset($body['qType']) ? $body['qType']: $el->owned->type, 
                'sub_type' => (isset($body['qSubType'])? $body['qSubType'] : $el->owned->sub_type),
                'linked' => (isset($body['qLinked'])? $body['qLinked'] : $el->owned->linked),
                'value' => (isset($body['qValue'])? $body['qValue'] : $el->owned->value),
                'default_visibility' => (isset($body['visibility']) ? $body['visibility'] : $el->owned->default_visibility)
            ];
            break;
        case 'group':
            $data = [
                'default_visibility' => (isset($body['visibility']) ? $body['visibility'] : $el->owned->default_visibility)
            ];
            break;
        case 'option': 
            $data = [
                'type' => isset($body['oType']) ? $body['oType']: $el->owned->type,
                'value' => (isset($body['oValue'])? $body['oValue'] : $el->owned->value),
                'data' => (isset($body['oData'])? $body['oData'] : $el->owned->data),
                'extra' => (isset($body['oExtra'])? $body['oExtra'] : $el->owned->extra),
            ];
            break;
        case 'questionary':
            $data = [];
            break;
            //Logics Elements
        case 'match-logic': 
            if ($el->children->count() == 0) {
                $bool = null;
            } else {
                $bool = isset($body['bool'])? $body['bool'] : $el->owned->bool;
            }
            $data = [
                'bool' => $bool
            ];
            break;
        case 'logic': 
            $root = $mapper->findRoot($el);
            $data = [
                'action' => isset($body['action']) ? $body['action']: $el->owned->action
            ];
            break;
        case 'match': 
            if ($el->children->count() == 0 ) {
                $data = [
                    'operator' => isset($body['operator']) ? $body['operator'] : 'eq',
                    'target_id' => $body['target_id'],
                    'target_option_id' => (isset($body['target_option_id']) ? $body['target_option_id'] : null),
                    'target_value' => (isset($body['target_value']) ? $body['target_value'] : null),
                ];
            }
            break;
    }

    $idata = $el->saveData($mapper, $data);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $trans = null;
    $resource = new Item($el, new ElementTransformer());
    $data = $fractal->createData($resource)->toArray();
    $data['data']['data'] = $idata;
    $data["status"] = "ok";
    $data["message"] = "Element Updated";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/elements", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $body = $request->getParsedBody();

    if (isset($body['eRef'])) {
        $eRef = $mapper->findById($body['eRef'], ['owned', 'children','label']);
        if ($eRef === false) {
            throw new NotFoundException("Reference not found", 404);
        }
    }

    // Element 
    $new = new Element([
        'data_type' => $body['type'],
        'user_id'=> $this->token->getUser(),
        'code' => isset($body['code']) ? $body['code'] :''
    ]);

    $mapper->save($new);

    // Label Stuff

    $body['labelType'] = isset($body['labelType']) ? $body['labelType'] : "text";
    $body['labelData'] = isset($body['labelData']) ? $body['labelData'] : "";
    $new->createLabel($mapper, $body['labelType'], $body['labelData']);

    // Data
    $data = [];

    switch($body['type']) {
        case 'question': 
            $data = [
                'type'=>$body['qType'], 
                'sub_type' => (isset($body['qSubType'])? $body['qSubType'] : null),
                'linked' => (isset($body['qLinked'])? $body['qLinked'] : false),
                'value' => (isset($body['qValue'])? $body['qValue'] : null),
                'default_visibility' => (isset($body['visibility']) ? $body['visibility'] : null)
            ];
            break;
        case 'group':
            $data = [
                'default_visibility' => (isset($body['visibility']) ? $body['visibility'] : null)
            ];
            break;
        case 'option': 
            $data = [
                'type' => $body['oType'],
                'value' => (isset($body['oValue'])? $body['oValue'] : null),
                'data' => (isset($body['oData'])? $body['oData'] : null),
                'extra' => (isset($body['oExtra'])? $body['oExtra'] : null),
            ];
            break;
        case 'questionary':
            $data = [];
            break;
            //Logics Elements
        case 'match-logic': 
            if ($eRef->children->count() == 0) {
                $bool = null;
            } else {
                $bool = isset($body['mlBool'])? $body['mlBool'] : 'and';
            }
            $data = [
                'bool' => $bool
            ];
            break;
        case 'logic': 
            $data = [
                'action' => $body['lAction'],
                'questionary_id' => $body['lQuest']
            ];
            break;
        case 'match': 
            if ($eRef->children->count() == 0 ) {
                $data = [
                    'operator' => isset($body['mOperator']) ? $body['mOperator'] : 'eq',
                    'target_id' => $body['mTarget'],
                    'target_option_id' => (isset($body['mOption']) ? $body['mOption'] : null),
                    'target_value' => (isset($body['mTargetValue']) ? $body['mTargetValue'] : null)
                ];
            }
            break;
    }

    $idata = $new->createData($mapper, $data);

    $parent = null;
    $body['addingType'] = isset($body['addingType']) ? $body['addingType'] : null;

    if ($body['type'] == "logic") {
        $parent = $eRef->id;
    } else {
        switch($body['addingType']) {
            case 'prepend':
                $mapper->prepend($eRef, $new);
                $parent = $eRef->parent_id;
                break;
            case 'append':
                $mapper->append($eRef, $new);
                $parent = $eRef->parent_id;
                break;
            case 'next-to':
                $mapper->nextTo($eRef, $new);
                $parent = $eRef->parent_id;
                break;
            case 'prev-to':
                $mapper->prevTo($eRef, $new);
                $parent = $eRef->parent_id;
                break;
            case 'append-in':
                $mapper->appendIn($eRef, $new);
                $parent = $eRef->id;
                break;
            case 'prepend-in':
                $mapper->prependIn($eRef, $new);
                $parent = $eRef->id;
                break;
        }
    }

    $this->logger->addInfo("Parent ID : " .$parent);
    if (isset($parent)) {
        $new->data(['parent_id' => $parent]);
        $mapper->save($new);
    }

    //$fractal = new Manager();
    //$fractal->setSerializer(new DataArraySerializer);

    //$trans = null;
    //switch($new->data_type) {
        //case 'question': 
            //$trans = new QuestionTransformer();
            //break;
        //case 'group':
            //$trans = new GroupTransformer();
            //break;
        //case 'questionary':
            //$trans = new QuestionaryTransformer();
            //break;
        //case 'match-logic': 
            //$trans = new MatchLogicTransformer();
            //break;
        //case 'logic': 
            //$trans = new LogicTransformer();
            //break;
        //case 'match': 
            //$trans = new MatchTransformer();
            //break;
    //}
    //$resource = new Item($new, new ElementTransformer());
    //$data = $fractal->createData($resource)->toArray();

    $new->owned = $idata;

    $data['data'] = $mapper->findAllRecursive($new);
    $data["status"] = "ok";
    $data["message"] = "New Element created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->post(getenv("API_ROOT"). "/elements/{id}/appendIn", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create questions.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    if ($argumens['id'] == "root") {
        $el = $mapper->findById($arguments['id'], ['owned', 'children']);
        if ($el === false) {
            throw new NotFoundException("Element not found", 404);
        }
    }

    $body = $request->getParsedBody();

    $new = new Element([
        'data_type' => $body['dataType'],
        'user_id'=> $this->token->getUser(),
        'code' => $body['code'] ? $body['code'] :'',
        'parent_id' => isset($el) ? $el->id: null
    ]);

    $mapper->save($new);
    $new->createLabel($mapper, "text", $body['label']);

    $data = [];

    switch($body['dataType']) {
        case 'question': 
            $data = [
                'type'=>$body['type'], 
                'sub_type' => (isset($body['sub_type'])? $body['sub_type'] : null),
                'default_visibility' => (isset($body['default_visibility']) ? $body['default_visibility'] : null)
            ];
            break;
        case 'group':
            $data = [];
        case 'questionary':
            $data = [];

            //Logics Elements
        case 'match-logic': 
            if ($el->children->count() == 0) {
                $bool = null;
            } else {
                $bool = isset($body['bool'])? $body['bool'] : 'and';
            }
            $data = [
                'bool' => $bool
            ];
            break;
        case 'logic': 
            $root = $mapper->findRoot($el);
            $data = [
                'action' => $body['action'],
                'questionary_id' => $root->id
            ];
            break;
        case 'match': 
            if ($el->children->count() == 0 ) {
                $data = [
                    'operator' => isset($body['operator']) ? $body['operator'] : 'eq',
                    'target_id' => $body['target_id'],
                    'target_option_id' => (isset($body['target_option_id']) ? $body['target_option_id'] : null),
                    'target_value' => (isset($body['target_value']) ? $body['target_value'] : null),
                ];
            }
            break;
    }

    $idata = $new->createData($mapper, $data);
    if (isset($el)) {
        $mapper->appendIn($el, $new);
    }

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $trans = null;
    switch($new->data_type) {
        case 'question': 
            $trans = new QuestionTransformer();
            break;
        case 'group':
            $trans = new GroupTransformer();
        case 'questionary':
            $trans = new QuestionaryTransformer();
        case 'match-logic': 
            $trans = new MathLogicTransformer();
            break;
        case 'logic': 
            $trans = new LogicTransformer();
            break;
        case 'match': 
            $trans = new MatchTransformer();
            break;
    }
    $resource = new Item($new, $trans);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New Element created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/elements/{id}", function ($request, $response, $arguments) {
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
