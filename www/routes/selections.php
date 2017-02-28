<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

use App\Question;
use App\QuestionTransformer;
use App\Selection;
use App\SelectionTransformer;
use App\Step;
use App\StepTransformer;
use App\Questionary;
use App\QuestionaryTransformer;
use App\TakenQuiz;
use App\TakenQuizTransformar;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;

$app->get(getenv("API_ROOT"). "/taken-quizzes/{takenQuizId}/selections", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["question.all"])) {
        throw new ForbiddenException("Token not allowed to take selection.", 403);
    }
    $mapper = $this->spot->mapper("App\Selection");
    $tqMapper = $this->spot->mapper("App\TakenQuiz");

    $takenQuiz = $tqMapper->findById($arguments['takenQuizId']);
    if ($takenQuiz === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    } else {
        $first = $mapper->findLastModifiedFromTakenQuiz($this->token->getUser(), $takenQuiz);

        if ($first) {
            $response = $this->cache->withEtag($response, $first->etag());
            $response = $this->cache->withLastModified($response, $first->timestamp());
        }

        if ($this->cache->isNotModified($request, $response)) {
            return $response->withStatus(304);
        }

        $selections = $mapper->findAllSortedFromTakenQuiz($this->token->getUser(), $takenQuiz);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($selections, new SelectionTransformer);
        $data = $fractal->createData($resource)->toArray();

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});
$app->post(getenv("API_ROOT"). "/taken-quizzes/{takenQuizId}/selections", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to create selections.", 403);
    }

    $mapper = $this->spot->mapper('App\Selection');
    $tqMapper = $this->spot->mapper('App\TakenQuiz');
    $quMapper = $this->spot->mapper('App\Questionary');
    $stMapper = $this->spot->mapper('App\Step');
    $qeMapper = $this->spot->mapper('App\Question');
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $takenQuiz = $tqMapper->findById($arguments['takenQuizId']);
    $data = [];
    $question = null;
    $step = null;
    $selection = null;

    if ($takenQuiz === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    } 
    $questionary = $quMapper->findById($takenQuiz->questionary_id);

    $body = $request->getParsedBody();
    if (!isset($body['uid'])) {
        $selections = $mapper->findAllFromTakenQuiz($this->token->getUser(), $takenQuiz);
        if( $selections->count() == 0 ){ 
            // There are no selections, create a new
            $step = $stMapper->findById($questionary->start_id);
            if ($step === false) {
                throw new NotFoundException("Step not found", 404);
            } 
            // Going to question starting question
            $question = $qeMapper->findById($step->start_id);
            if ($question === false) {
                throw new NotFoundException("Question not found", 404);
            } 
            // Preparing the new selection and (step, question)
            $selection = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser()]);
            $mapper->save($selection);
        } else {
            $selection = $selections->first();
            // Find the last selection
            $selection = $selection->findLast($this->spot, $takenQuiz);

            $question = $qeMapper->findById($selection->question_id);
            // Check if selection has option ( *fix for value, too )
            if ($selection->option_id == null && $selection->value == null) {
                $step = $stMapper->findById($question->step_id);
            } else {
                //User has alreasy chosen the option
                // Searching for next allowed question
                $question = $question->findNext($this->spot, $takenQuiz);
                if ($question === false) {
                    throw new NotFoundException("End Reached", 404);
                }
                $selection2 = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser(), 'prev_id' => $selection->uid]);
                $mapper->save($selection2);
                $selection->data(['next_id' => $selection2->uid]);
                $mapper->save($selection);
                // Returning as selection
                $selection = $selection2;
            }
        }
    } else {
        $this->logger->addInfo("-----PARSING BODY DATA ----");
        $selection = $mapper->getById($body['uid']);
        if ($selection === false) {
            throw new NotFountException("Selection not found", 404);
        } else {
            $question = $qeMapper->findById($selection->question_id);
            if ($body['option_id']==null && $body['value']==null) {
                // The user has not chosen an option neither a level
            } else {
                // Option chosen, get next question and selection
                $this->logger->addInfo("** The select has at least (optino or level) chosen");

                // Save data for current Selection
                $auxbody = ['option_id' => isset($body['option_id']) ? $body['option_id']: null, 'value' => isset($body['value']) ? $body['value'] :null];
                $selection->data($auxbody);
                $mapper->save($selection);

                //Looking for next allowed question
                $question = $question->findNext($this->spot, $takenQuiz);
                $this->logger->addInfo("Looking for next question END", $question->toArray());
                if ($question === false) 
                    $this->logger->addInfo("Question end reached. Look for next Step");
                $selection2 = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=>$question->uid, 'user_id'=>$this->token->getUser(), 'prev_id'=>$selection->uid]);
                $mapper->save($selection2);
                $selection->data(['next_id'=>$selection2->uid]);
                $mapper->save($selection);
                $selection = $selection2;
            }
        }
        $step = $stMapper->findById($question->step_id);
    }
    $resourceS = new Item($step, new StepTransformer);
    $resourceQ = new Item($question, new QuestionTransformer);
    $data['step'] = $fractal->createData($resourceS)->toArray();
    $data['question'] = $fractal->createData($resourceQ)->toArray();
    $data['selection'] = $selection->uid;
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});


$app->post(getenv("API_ROOT"). "/taken-quizzes/{takenQuizId}/selections2", function ($request, $response, $arguments) {

    if (false === $this->token->hasScope(["question.all", "question.list"])) {
        throw new ForbiddenException("Token not allowed to create selections.", 403);
    }

    $mapper = $this->spot->mapper('App\Selection');
    $tqMapper = $this->spot->mapper('App\TakenQuiz');
    $quMapper = $this->spot->mapper('App\Questionary');
    $stMapper = $this->spot->mapper('App\Step');
    $qeMapper = $this->spot->mapper('App\Question');
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);

    $takenQuiz = $tqMapper->findById($arguments['takenQuizId']);
    $data = [];
    $question = null;
    $step = null;
    $selectionId = null;

    if ($takenQuiz === false) {
        throw new NotFoundException("Taken Quiz not found", 404);
    } else {
        $questionary = $quMapper->findById($takenQuiz->questionary_id);

        $body = $request->getParsedBody();
        if (!isset($body['uid'])) {
            $selections = $mapper->findAllFromTakenQuiz($this->token->getUser(), $takenQuiz);
            if( $selections->count() == 0 ){ 
                // There are no selections, create a new
                $step = $stMapper->findById($questionary->start_id);
                if ($step === false) {
                    throw new NotFoundException("Step not found", 404);
                } else {
                    $question = $qeMapper->findById($step->start_id);
                    if ($question === false) {
                        throw new NotFoundException("Question not found", 404);
                    } else {
                        // Preparing the new selection and (step, question)
                        $sele = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser()]);
                        $mapper->save($sele);
                        $selectionId = $sele->uid;
                    }
                }
            } else {
                $sele = $selections->first();
                // Find the last selection 
                while($sele->next_id != null) {
                    $sele = $mapper->findById($sele->next_id);
                }
                $question = $qeMapper->findById($sele->question_id);
                // Check if selection has option ( *fix for value, too )
                if ($sele->option_id == null && $sele->value == null) {
                    $step = $stMapper->findById($question->step_id);
                    $selectionId = $sele->uid;
                } else {
                    //User has alreasy chosen the option
                    if ($question->next_id == null) {
                        // We have reach to end of group;
                        $this->logger->addInfo("Need to implement this");
                    } else {
                        // We have a next question. Then, create a new selection
                        $question = $qeMapper->findById($question->next_id);
                        $step = $stMapper->findById($question->step_id);
                        $newSele = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser(), 'prev_id' => $sele->uid]);
                        $mapper->save($newSele);
                        $sele->data(['next_id' => $newSele->uid]);
                        $mapper->save($sele);
                        $selectionId = $newSele->uid;
                    }
                }
            }
        } else {
            $this->logger->addInfo("-----PARSING BODY DATA ----");
            $sele = $mapper->getById($body['uid']);
            if ($sele === false) {
                throw new NotFountException("Selection not found", 404);
            } else {
                $question = $qeMapper->findById($sele->question_id);
                $step = $stMapper->findById($question->step_id);
                $this->logger->addInfo("-----CHECKING OPTION_ID and VALUE----");
                $this->logger->addInfo("The body", $body);
                if ($body['option_id']==null && $body['value']==null) {
                    $this->logger->addInfo("** The select has not an chosen option neither a chosen level");
                    // If not chosen an option
                    $selectionId = $sele->uid;
                } else {
                    // Option chosen, get next question and selection
                    $this->logger->addInfo("** The select has at least (optino or level) chosen");
                    if ($question->next_id == null) {
                        // No more quetions, End reached
                        $this->logger->addInfo("Question end reached. Look for next Step");

                        if ($step->next_id == null) {
                            $this->logger->addInfo("Step has not a next id. I think we have reached at end of Steps");
                        } else {
                            $nextStep = $stMapper->findById($step->next_id);
                            if ($nextStep === false) {
                                $this->logger->addInfo('Next Step not found. Exception');
                                throw new NotFoundException('Step not found', 404);
                            } else {
                                if ($nextStep->start_id == null) {
                                    $this->logger->addInfo("This has not a start. Looking for next steps");

                                    while($nextStep->start_id != null ) {
                                        $nextStep = $stMapper->findById($nextStep->next_id);
                                    }

                                    if ($nextStep->start_id == null) {
                                        $this->logger->addInfo("REACHED THE END OF Steps");
                                    } else {
                                        $question = $qeMapper->findById($nextStep->start_id);
                                        $newSele = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser(), 'prev_id' => $sele->uid]);
                                        $mapper->save($newSele);
                                        $auxbody = ['next_id' => $newSele->uid, 'option_id' => isset($body['option_id']) ? $body['option_id']: null, 'value' => isset($body['value']) ? $body['value'] :null];
                                        $sele->data($auxbody);
                                        $mapper->save($sele);
                                        $selectionId = $newSele->uid;
                                    }
                                } else {
                                    $question = $qeMapper->findById($nextStep->start_id);
                                    $newSele = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser(), 'prev_id' => $sele->uid]);
                                    $mapper->save($newSele);
                                    $auxbody = ['next_id' => $newSele->uid, 'option_id' => isset($body['option_id']) ? $body['option_id']: null, 'value' => isset($body['value']) ? $body['value'] :null];
                                    $sele->data($auxbody);
                                    $mapper->save($sele);
                                    $selectionId = $newSele->uid;
                                }
                            }
                        }
                    } else {
                        $question = $qeMapper->findById($question->next_id);
                        $step = $stMapper->findById($question->step_id);
                        $newSele = new Selection(['taken_quiz_id' => $takenQuiz->uid, 'question_id'=> $question->uid, 'user_id'=>$this->token->getUser(), 'prev_id' => $sele->uid]);
                        $mapper->save($newSele);
                        $body = ['next_id' => $newSele->uid, 'option_id' => isset($body['option_id']) ? $body['option_id']: null, 'value' => isset($body['value']) ? $body['value'] :null];
                        $sele->data($body);
                        $this->logger->addInfo("This is the data",$body );
                        $mapper->save($sele);
                        $selectionId = $newSele->uid;
                    }
                }
            }
        }
    }
    $resourceS = new Item($step, new StepTransformer);
    $resourceQ = new Item($question, new QuestionTransformer);
    $data['step'] = $fractal->createData($resourceS)->toArray();
    $data['question'] = $fractal->createData($resourceQ)->toArray();
    $data['selection'] = $selectionId;
    return $response->withStatus(201)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->get(getenv("API_ROOT"). "/selections/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["", ""])) {
        throw new ForbiddenException("Token not allowed to read selections.", 403);
    }
    $mapper  = $this->sport->mapper("App\Selection");

    if (false === $selection = $mapper->getById($arguments["uid"]))
    {
        throw new NotFoundException("Selection not found.", 404);
    };


    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $selection->etag());
    $response = $this->cache->withLastModified($response, $selection->timestamp());

    /* If-Modified-Since and If-None-Match request header handling. */
    /* Heads up! Apache removes previously set Last-Modified header */
    /* from 304 Not Modified responses. */
    if ($this->cache->isNotModified($request, $response)) {
        return $response->withStatus(304);
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($selection, new SelectionTransformer);
    $data = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->patch(getenv("API_ROOT"). "/selections/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["", ""])) {
        throw new ForbiddenException("Token not allowed to update selections.", 403);
    }

    /* Load existing selection using provided uid */
    if (false === $selection = $this->spot->mapper("App\Selection")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Selection not found.", 404);
    };

    /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PATCH request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the selection respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $selection->etag(), $selection->timestamp())) {
        throw new PreconditionFailedException("Selection has been modified.", 412);
    }

    $body = $request->getParsedBody();
    $selection->data($body);
    $this->spot->mapper("App\Selection")->save($selection);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $selection->etag());
    $response = $this->cache->withLastModified($response, $selection->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($selection, new SelectionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Selection updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->put(getenv("API_ROOT"). "/selections/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["", ""])) {
        throw new ForbiddenException("Token not allowed to update Selection.", 403);
    }

    /* Load existing selection using provided uid */
    if (false === $selection = $this->spot->mapper("App\Selection")->first([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Selection not found.", 404);
    };

    /* PUT requires If-Unmodified-Since or If-Match request header to be present. */
    if (false === $this->cache->hasStateValidator($request)) {
        throw new PreconditionRequiredException("PUT request is required to be conditional.", 428);
    }

    /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
    /* someone has modified the selection respond with 412 Precondition Failed. */
    if (false === $this->cache->hasCurrentState($request, $selection->etag(), $selection->timestamp())) {
        throw new PreconditionFailedException("Selection has been modified.", 412);
    }

    $body = $request->getParsedBody();

    /* PUT request assumes full representation. If any of the properties is */
    /* missing set them to default values by clearing the selection object first. */
    $selection->clear();
    $selection->data($body);
    $this->spot->mapper("App\Selection")->save($selection);

    /* Add Last-Modified and ETag headers to response. */
    $response = $this->cache->withEtag($response, $selection->etag());
    $response = $this->cache->withLastModified($response, $selection->timestamp());

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($selection, new SelectionTransformer);
    $data = $fractal->createData($resource)->toArray();
    $data["status"] = "ok";
    $data["message"] = "Selection updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

$app->delete(getenv("API_ROOT"). "/selections/{uid}", function ($request, $response, $arguments) {

    /* Check if token has needed scope. */
    if (false === $this->token->hasScope(["", ""])) {
        throw new ForbiddenException("Token not allowed to delete selections.", 403);
    }

    /* Load existing selection provided uid */
    if (false === $selection = $this->spot->mapper("App\Selection")->getSelection([
        "uid" => $arguments["uid"]
    ])) {
        throw new NotFoundException("Selection not found.", 404);
    };

    $this->spot->mapper("App\Selection")->delete($selection);

    $data["status"] = "ok";
    $data["message"] = "Selection deleted";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
