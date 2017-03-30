<?php
use App\Option;
use App\Question;
use App\Element;
use App\QuestionTransformer;
use App\OptionTransformer;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/questions/{id}/options", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.read"])) {
        throw new ForbiddenException("Token not allowed to read options.", 403);
    }
    $mapper = $this->spot->mapper("App\Element");

    if (false === $question = $mapper->findById($arguments["id"], ['owned', 'children'])){
        throw new NotFoundException("Question not found.", 404);
    }

    $options = $mapper->getMapper('App\Option')->listFromQuestion($question);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($options, new OptionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->post(getenv("API_ROOT"). "/questions/{id}/options/append", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create options.", 403);
    }

    $mapper = $this->spot->mapper("App\Element");
    $question = $mapper->findById($arguments['id'], ['owned', 'children']);
    if ($question === false) {
        throw new NotFoundException("Question not found", 404);
    }

    $body = $request->getParsedBody();
    $data = [];

    $type = $question->owned->type;
    if ($type == "selection") {
        $sub_type = $question->owned->sub_type;
        if ($sub_type == "simple") {
            $new = new Element([
                'data_type' => 'option',
                'user_id' => $this->token->getUser(),
                'code' => (isset($body['code']) ? $body['code'] : null),
                'parent_id' => $question->id
            ]);
            $mapper->save($new);
            $new->createLabel($mapper, "text", $body['label']);

            $option = $new->createData($mapper, [
                'type' => $body['type'],
                'value' => (isset($body['value'])? $body['value'] : null),
                'data' => (isset($body['data'])? $body['data'] : null),
                'extra' => (isset($body['extra'])? $body['extra'] : null),
            ]);
            if ($question->children->count() == 0) {
                $question->saveData($mapper, ['start_id' => $new->id]);
            } else {
                $start = $mapper->findById($question->owned->start_id);
                $mapper->append($start, $new);
            }

            /* Serialize the response data. */
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($new, new OptionTransformer);
            $data = $fractal->createData($resource)->toArray();
            $data["status"] = "ok";
            $data["message"] = "New option created";
        } else if ($sub_type == "level") {
            $st = isset($body['type'])? $body['type'] : null;
            $children = $question->children->entities();

            if ($st != null) {
                $exists = false;
                foreach($children as $child) {
                    if ($st == $child->code) {
                        $exists = true;
                    }
                }
                if (($st == "min" || $st == "max") && !$exists) {
                    $new = new Element([
                        'data_type' => 'option',
                        'user_id' => $this->token->getUser(),
                        'code' => $st,
                        'parent_id' => $question->id
                    ]);
                    $mapper->save($new);
                    //$new ->createLabel($mapper, "text", ($st == "min") ? "Minimum" : "Maximum");
                    $new->createLabel($mapper, "text", isset($body['label']) ? $body['label'] : ($st == "min" ? "Minimum":"Maximum"));
                    $option = $new->createData($mapper, [
                        'type' => $st,
                        'value' => (isset($body['value'])? $body['value'] : null),
                        'data' => (isset($body['data'])? $body['data'] : null),
                        'extra' => (isset($body['extra'])? $body['extra'] : null),
                    ]);
                    if ($question->children->count() == 0) {
                        $question->saveData($mapper, ['start_id' => $new->id]);
                    }
                    else {
                        $mapper->append($mapper->findById($question->owned->start_id), $new);
                    }
                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($new, new OptionTransformer);
                    $data = $fractal->createData($resource)->toArray();
                    $data["status"] = "ok";
                    $data["message"] = "New option " . $st . " created";
                }
            }
        }
    } else if ($type == "input") {
        $sub_type = $question->owned->sub_type;
        if ($sub_type == "text") {
            $new = new Element([
                'data_type' => 'option',
                'user_id' => $this->token->getUser(),
                'code' => (isset($body['code']) ? $body['code'] : null),
                'parent_id' => $question->id
            ]);
            $mapper->save($new);
            $new->createLabel($mapper, "text", $body['label']);

            $option = $new->createData($mapper, [
                'type' => $body['type'],
                'value' => (isset($body['value'])? $body['value'] : null),
                'data' => (isset($body['data'])? $body['data'] : null),
                'extra' => (isset($body['extra'])? $body['extra'] : null),
            ]);
            if ($question->children->count() == 0) {
                $question->saveData($mapper, ['start_id' => $new->id]);
            } else {
                $start = $mapper->findById($question->owned->start_id);
                $mapper->append($start, $new);
            }

            /* Serialize the response data. */
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($new, new OptionTransformer);
            $data = $fractal->createData($resource)->toArray();
            $data["status"] = "ok";
            $data["message"] = "New option created";
        } 

    }
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/options/{id}", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.delete"])) {
        throw new ForbiddenException("Token not allowed to delete questions.", 403);
    }
    $mapper = $this->spot->mapper("App\Option");
    if (false === $option = $mapper->findById($arguments["id"])) {
        throw new NotFoundException("Option: Option not found.", 404);
    };


    $parent = $mapper($option->owned->start_id, ["owned"]);
    if ($parent->data_type == "question") {
        switch($parent->owned->type ) {
            case "selection": 
                switch($parent->owned->sub_type) {
                    case "single": 
                        $mapper->delete($option);
                    break;
                    case "level": 
                    break;
                }
            break;
        }
    }


    $data["status"] = "ok";
    $data["message"] = "Option deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
