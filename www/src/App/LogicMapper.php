<?php 
namespace App\Mapper;
use Spot\Mapper;
use Spot\Entity;
use Spot\Entity\Collection;
use App\Element;
use App\MatchLogic;
use App\Logic;

class LogicMapper extends ElementMapper
{
    public function findAllChildren($e) {
        return $this->all()->where(['type' => 'logic', 'parent_id' => $e->id])->with(['owned', 'children']);
    }

    
    public function check($logger, $e, $action, $tq) {
        $los = $this->getMapper("App\Element")->findAllByTypeFrom($e, "logic", ['owned']);

        $ret = null;

        $xlos = $los->filter(function ($a) use ($action){
            if($a->owned->action == $action) {
                return true;
            } else {
                return false;
            }
        });

        foreach($xlos as $ll ){ 
            //$logger->addInfo("XXX", $ll->toArray());
            $aux = $this->evaluate($logger, $tq, $ll);
            if (isset($aux)) {
                $ret = $ret || $aux;
            }
        }
        return $ret;
    }

    public function checkVisibility($logger, $e, $tq) {
        $show = $this->check($logger, $e, 'show', $tq);
        $hide = $this->check($logger, $e, 'hide', $tq);

        if (!isset($hide) && !isset($show)) {
            //return true;
            return $e->owned->default_visibility;

        }
        if (isset($hide)) {
            return !$hide;
        }
        else if(isset($show)){
            return $show;
        }
    }

    public function findHierarchy($e) {
        if ($e->first_id == null) {
            return [];
        } else {
            return $this->_findHierarchy($e);
        }
    }
    public function _findHierarchy($e) {
        if ($e->first_id == null) {
            if ($e->data_type == "match-logic") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'bool' => $e->owned->bool, 'name' => $this->toString($e)];
            } else if ($e->data_type == "match") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'operator' => $e->owned->operator, 'target_id' => $e->owned->target_id, 'target_option_id' => $e->owned->target_option_id, 'target_value' => $e->owned->target_value, 'name' => $this->toString($e)];
            } else if ($e->data_type == "logic") {
                return [];
            }
        }
        else {
            $ch = $this->getMapper("App\Element")->findById($e->first_id);
            $ret = [];
            if ($ch->first_id == null) {
                array_push($ret, $this->_findHierarchy($ch));
            } else {
                array_push($ret, ['id' => $ch->id, 'type' => $ch->data_type, 'children' => $this->_findHierarchy($ch), 'name'=>$this->toString($ch)]);

            }
            while($ch->next_id != null) {
                $ch = $this->getMapper("App\Element")->findById($ch->next_id);
                if ($ch->first_id == null) {
                    array_push($ret, $this->_findHierarchy($ch));
                } else {
                    array_push($ret,['id' => $ch->id, 'type'=>$ch->data_type, 'name' => $this->toString($ch), 'children'=>$this->_findHierarchy($ch)]);
                }
            }
            return $ret;
        }
    }

    public function findAllLogics($e) {
        if ($e->first_id == null) {
            return $this->_findAllLogics($e);
        } else {
            return ['id' => $e->id, 'type'=> $e->data_type, 'code' => $e->code, 'data' => $e->owned, 'label' => ['type' => $e->label->type, 'data'=>$e->label->data], 'children' =>$this->_findAllLogics($e)];
        }
    }

    public function _findAllLogics($e) {
        if ($e->first_id == null) {
            return ['id' => $e->id, 'type'=> $e->data_type, 'code' => $e->code, 'data' => $e->owned, 'label' => ['type' => "text", 'data'=>$e->data_type], 
            ];
        }
        else {
            $ch = $this->findById($e->first_id, ['owned', 'label']);
            $ret = [];
            if ($ch->first_id == null) {
                array_push($ret, $this->_findAllLogics($ch));
            } else {
                array_push($ret, ['id' => $ch->id, 'type' => $ch->data_type, 'code' => $ch->code, 'data' => $ch->owned, 'label' => ['type' => "text", 'data'=> ""], 'children' => $this->_findAllLogics($ch)
                ]);
            }

            while($ch->next_id != null) {
                $ch = $this->getMapper("App\Element")->findById($ch->next_id, ['owned', 'label']);
                if ($ch->first_id == null) {
                    array_push($ret, $this->_findAllLogics($ch));
                } else {
                    array_push($ret, ['id' => $ch->id, 'type'=>$ch->data_type,
                       'code' => $ch->code, 'data' => $ch->owned, 'label' => ['type' => $ch->label->type, 'data'=>$ch->label->data], 'children'=>$this->_findAllLogics($ch)
                   ]);
                }
            }
            return $ret;
        }
    }


    public function findAllRecursive($e) {
        if ($e->first_id == null) {
            return $this->_findAllRecursive($e);
        } else {
            if ($e->data_type == "match-logic") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'bool' => $e->owned->bool, 'children'=> $_findAllRecursive($e)];
            } else if ($e->data_type == "match") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'operator' => $e->owned->operator, 'target_id' => $e->owned->target_id, 'target_option_id' => $e->owned->target_option_id, 'target_value' => $e->owned->target_value, 'children' => $this->_findAllRecursive($e)];
            } else if ($e->data_type == "logic") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'action' => $e->owned->action, 'children' =>$this->_findAllRecursive($e)];
            }
        }
    }

    public function _findAllRecursive($e) {
        if ($e->first_id == null) {
            if ($e->data_type == "match-logic") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'bool' => $e->owned->bool];
            } else if ($e->data_type == "match") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'operator' => $e->owned->operator, 'target_id' => $e->owned->target_id, 'target_option_id' => $e->owned->target_option_id, 'target_value' => $e->owned->target_value];
            } else if ($e->data_type == "logic") {
                return ['id' => $e->id, 'type'=> $e->data_type, 'action' => $e->owned->action];
            }
        }
        else {
            $ch = $this->getMapper("App\Element")->findById($e->first_id);
            $ret = [];
            if ($ch->first_id == null) {
                array_push($ret, $this->_findAllRecursive($ch));
            } else {
                array_push($ret, ['id' => $ch->id, 'type' => $ch->data_type, 'children' => $this->_findAllRecursive($ch)]);

            }
            while($ch->next_id != null) {
                $ch = $this->getMapper("App\Element")->findById($ch->next_id);
                if ($ch->first_id == null) {
                    array_push($ret, $this->_findAllRecursive($ch));
                } else {
                    array_push($ret, ['id' => $ch->id, 'type'=>$ch->data_type, 'children'=>$this->_findAllRecursive($ch)]);
                }
            }
            return $ret;
        }
    }

    public function toString($e) {
        $res = "";
        switch($e->data_type) {
        case 'match': 
            $target = $this->getMapper("App\Element")->findById($e->owned->target_id);
            if ($e->owned->target_option_id != null) {
                $option = $this->getMapper("App\Element")->findById($e->owned->target_option_id);
            }
            $res = $e->owned->operator . ": " . $target->code ."(".$option->label->data . ")";
            break;

        case 'match-logic':
            $res = $e->owned->bool ? $e->owned->bool . "--" : null;
            if ($e->owned->bool) {
                $res = "+ " .$e->owned->bool;
            } else {
                $res = "*";
            }
            break;
        }
        return $res;
    }

    public function _evaluateSingle($logger, $tq, $e) {
        //$logger->addInfo("Evaluate Single::: " . $e->data_type);
        //$this->getMapper("App\Element")->findById($e);
        if ($e == null) {
            return null;
        } else {
            //$logger->addInfo("checkingmatch");
            if ($e->data_type == "match") {
                $question = $this->getMapper('App\Element')->findById($e->owned->target_id);

                $logger->addInfo("Evaluating Question:  ". $question);
                if ($question === false) {
                    //$logger->addInfo("FALSE checkingmatch");
                    return null;
                }
                //$logger->addInfo("TRUE checkingmatch");
                if ($question->owned->type == "selection" && $question->owned->sub_type == "simple") {
                    $option = $this->getMapper("App\Element")->findById($e->owned->target_option_id);
                    //$logger->addInfo("mother ucker");
                    if ($option->parent_id == $question->id) {
                        $answers = $this->getMapper("App\\Answer")->all()->where(['question_id' => $question->id, 'valid' => true]);

                        //$logger->addInfo("DATA", $answers->toArray());
                        $ansok = [];
                        if ($answers->count() == 0) {
                            return false;
                        }
                        foreach($answers as $ans) {
                            //$logger->addInfo("Counting answer");
                            $aux = $this->getMapper("App\Element")->where([
                                'id' => $ans->element_id,
                                'parent_id' => $tq->id
                            ])->first();
                            if ($aux === false) {
                                //array_push($ansok, null);
                            } else {
                                if ($ans->option_id == $option->id) {
                                    array_push($ansok, true);
                                } else {
                                    array_push($ansok, false);
                                }
                            }
                        }
                        //$logger->addInfo("ALL ANSWERS", $ansok);
                        if (count($ansok) == 1) {
                            return $ansok[0];
                            
                        } else {
                            return null;
                        }
                        //if (count($ansok) == 0) {
                            //$logger->addInfo("Mmmm.... que malaso el cuate");
                            //return null;
                        //} else {
                            //$logger->addInfo("Mmmm.... que capo el cuate");
                            //return true;
                        //}
                    } else {
                    }
                    return null;
                } else {
                    return null;
                }
            }
            return null;
        }
    }
    public function _evaluate($logger, $tq, $e) {
        //$logger->addInfo("Evaluating::: " .$e->data_type);
        if ($e->data_type == "match") {
            return $this->_evaluateSingle($logger, $tq, $e);
        }
        else {
            $mls = $this->getMapper("App\Element")->findByParent($e);
            $ret = null;
            foreach($mls as $ml) {
                $ev = null;
                if ($ml->data_type == "match") {
                    $ret = $this->_evaluate($logger, $tq, $ml);
                } else if($ml->data_type == "match-logic") {
                    $ev = $this->_evaluate($logger, $tq, $ml);
                    $logger->addInfo("Eval result: ", ['ev' => $ev, 'type' => $ml->data_type]);
                    if (isset($ev)) {
                        if ($ml->owned->bool == null) {
                            $ret = $ev;
                        } else if ($ml->owned->bool == "and") {
                            $ret = $ret && $ev;
                        } else if ( $ml->owned->bool ==  "or") {
                            $ret = $ret || $ev;
                        }
                    } else {
                        //if ($ml->owned->bool == null) {
                            //$ret = null;
                        //} else if ($ml->owned->bool == "and") {
                            //$ret = null;
                        //} else if ( $ml->owned->bool ==  "or") {
                            //$ret = $ret || $ev;
                        //}
                        return null;
                    }
                } else {
                    $ret = null;
                }
            }
            return $ret;
        }
    }
    public function evaluate($logger, $tq, $e) {
        //$logic = $this->getMapper("App\Element")->findByParent($e)
        return $this->_evaluate($logger, $tq, $e);
    }

}
