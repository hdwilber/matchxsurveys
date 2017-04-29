<?php
use App\Option;
use App\OptionTransformer;
use App\Question;
use App\QuestionTransformer;
use App\Logic;
use App\LogicTransformer;
use App\Element;
use App\ElementTransformer;
use App\AnswerTransformer;


use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/taken-quizzes/{id}/elements/{qid}/check", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create answers to quizzes.", 403);
    }
    $mapper =  $this->spot->mapper("App\Element");
    $tq = $mapper->findById($arguments['id']);

    if($tq === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    }

    $el = $mapper->findById($arguments['qid']);

    $res = $mapper->getMapper("App\Logic")->checkVisibility($this->logger, $el, $tq);
    $data['res'] = $res;
    $data['show'] = $mapper->getMapper("App\Logic")->check($this->logger, $el, 'show', $tq);
    $data['hide'] = $mapper->getMapper("App\Logic")->check($this->logger, $el, 'hide', $tq);
    $data["status"] = "ok";
    $data["message"] = "New taken quiz created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
$app->get(getenv("API_ROOT"). "/taken-quizzes/{id}/elements/{qid}/evaluate", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create answers to quizzes.", 403);
    }
    $mapper =  $this->spot->mapper("App\Element");
    $tq = $mapper->findById($arguments['id']);

    if($tq === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    }

    $el = $mapper->findById($arguments['qid']);
    $logics = $mapper->getMapper("App\Element")->findAllByTypeFrom($el, "logic");

    $res = [];
    foreach($logics as $ll) {
        array_push($res, $mapper->getMapper("App\Logic")->evaluate($this->logger, $tq, $ll));
        //$this->logger->addInfo("EVAL", $mapper->getMapper("App\Logic")->evaluate($this->logger, $tq, $ll));
    }

    $data['res'] = $res;
    //$data['logics'] = $logics->toArray();
    $data["status"] = "ok";
    $data["message"] = "New taken quiz created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});
$app->get(getenv("API_ROOT"). "/taken-quizzes/{id}/questions/{qid}/evaluate", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create answers to quizzes.", 403);
    }
    $mapper =  $this->spot->mapper("App\Element");
    $tq = $mapper->findById($arguments['id']);

    if($tq === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    }

    $question = $mapper->findById($arguments['qid']);
    $logics = $mapper->getMapper("App\Element")->findAllByTypeFrom($question, "logic");

    $res = [];
    foreach($logics as $ll) {
        array_push($res, $mapper->getMapper("App\Logic")->evaluate($this->logger, $tq, $ll));
        //$this->logger->addInfo("EVAL", $mapper->getMapper("App\Logic")->evaluate($this->logger, $tq, $ll));
    }

    $data['res'] = $res;
    //$data['logics'] = $logics->toArray();
    $data["status"] = "ok";
    $data["message"] = "New taken quiz created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});

$app->get(getenv("API_ROOT"). "/taken-quizzes/{id}/getNext", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create answers to quizzes.", 403);
    }
    $mapper =  $this->spot->mapper("App\Element");
    $tq = $mapper->findById($arguments['id']);

    if($tq === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    }
    $answer = null;
    $quest = $mapper->findById($tq->owned->questionary_id);
    if ($quest === false) {
        throw new NotFoundException("Questionary not found", 404);
    }
    $answer = $mapper->getMapper("App\Questionary")->findNextQuestion($this->logger, $quest, $tq, null);
    if ($answer == false) {
        $data["answer"] = $answer;
        $data["status"] = "ok";
        $data["message"] = "New Returning next Question";

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    }

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($answer, new AnswerTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "New taken quiz created";

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

});

$app->post(getenv("API_ROOT"). "/taken-quizzes/{id}/answers", function ($request, $response, $arguments) {
    if (false === $this->token->hasScope(["question.all", "question.create"])) {
        throw new ForbiddenException("Token not allowed to create answers to quizzes.", 403);
    }
    $mapper =  $this->spot->mapper("App\Element");
    $tq = $mapper->findById($arguments['id']);

    if($tq === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    }
    $answer = null;
    $quest = $mapper->findById($tq->owned->questionary_id);
    if ($quest === false) {
        throw new NotFoundException("Questionary not found", 404);
    }

    $body = $request->getParsedBody();
    $question = false;
    if(!isset($body['id'])) {
        if ($tq->first_id == null) {
            $question = $mapper->getMapper("App\Questionary")->findNextQuestion($this->logger, $quest, $tq, null);

            if ($question === false) {
            } else {
                $answer = new Element([
                    'user_id' => $this->token->getUser(),
                    'data_type' => 'answer',
                    'code' => null,
                    'parent_id' => $tq->id
                    ]);
                $mapper->save($answer);
                $answer->createLabel($mapper, "text", "");
                $answer->createData($mapper, [
                    'question_id' => $question->id
                ]);
                $mapper->appendIn($tq, $answer);
            }
        } else {
            $answer = $mapper->findById($tq->last_id);
            $question = $mapper->findById($answer->owned->question_id, ['owned', 'children']);
        }
    } else {
        $this->logger->addInfo("BODY", $body);
        $answer = $mapper->findById($body['id'], ['owned']);
        if ($answer === false) {
            if ($tq->last_id == null) {
                // Fix this 
                $answer = $mapper->getMapper("App\Questionary")->findNextQuestion($logger, $quest, $tq, null);
            } else {
                $answer = $mapper->findById($tq->last_id);
            }
        } else {
            if (!$answer->owned->valid) {
                $answer->saveData($mapper, [
                    'question_id' => $body['question_id'],
                    'option_id' => isset($body['option_id']) ? $body['option_id']: null,
                    'value' => isset($body['value']) ? $body['value']: null,
                    'data' => isset($body['data']) ? $body['data']: null,
                    'valid' => true
                ]);
            }
            $question = $answer->owned->question_id;
            //$this->logger->addInfo("Answer data", $answer->owned->toArray());
            $nextQ = $mapper->getMapper("App\Questionary")->findNextQuestion($this->logger, $quest, $tq, $question);
            if ($nextQ === false) {
                //$this->logger->addInfo("TODO FALSO CARAJO");
            } else {
                //$this->logger->addInfo("Next Question ", $question);
                $new = new Element([
                    'user_id' => $this->token->getUser(),
                    'data_type' => 'answer',
                    'code' => null,
                    'parent_id' => $tq->id
                    ]);
                $mapper->save($new);

                $new->createLabel($mapper, "text", "");
                $new->createData($mapper, [
                    'question_id' => $nextQ->id
                ]);
                $answer = $new;
                $mapper->appendIn($tq, $new);
            }
            $question = $nextQ;
        }
    }

    if ($answer === false) {
        $data['result'] = "Sorry,all false";
    } else {
        $options = $mapper->getMapper("App\Option")->listFromQuestion($question);
        $qops = [];
        foreach($options as $op) {
            array_push($qops, [
                "id" => (string)$op->id ?: null,
                "type" => (string)$op->owned->type? : null,
                "label" => ['type' => 'text', 'data' => (string)$op->label->data ?: null]
            ]);
        }

        $parent = $mapper->findById($question->parent_id, ['owned', 'children']);

        $data['data'] = [
            'id' => $answer->id,
            'group' => [
                'id' => $parent->id,
                'code' => $parent->code,
                'label' => ['type' => 'text', 'data' => $parent->label->data]
            ],
            'question' => [
                'id' => $question->id,
                'code' => $question->code,
                'type' => (string)$question->owned->type,
                'subType' => (string)$question->owned->sub_type,
                'label' => ['type' => 'text', 'data' => (string)$question->label->data],
                'options' => $qops
            ],
            'questionary' => [
                'id' => $quest->id,
                'code' => $quest->code,
                'label' => ['type' => 'text', 'data' => (string)$quest->label->data]
            ]
            ];
        
        $data['status'] = "ok";
        
        //$fractal = new Manager();
        //$fractal->setSerializer(new DataArraySerializer);
        //$resource = new Item($answer, new AnswerTransformer);
        //$data = $fractal->createData($resource)->toArray();
        //$data["status"] = "ok";
        //$data["message"] = "New taken quiz created";
    }

    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
