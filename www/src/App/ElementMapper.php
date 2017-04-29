<?php 
namespace App\Mapper;
use Spot\Entity\Collection;
use Spot\Mapper;

use App\Match;
use App\Logic;
use App\Element;
use App\MatchLogic;

class ElementMapper extends BaseMapper
{
    public function changeLabelText($e, $t) {
        $l = $e->label->entity();
        $l->data(['data'=> $t]);
        $e->label->mapper()->save($l);
    }
    public function getRoots($t) {
        return $this->all()->where(['parent_id' => null, 'data_type'=>$t])->with(['owned', 'label']);
    }
    public function _deleteRecursive($e) {
        $cnt = $this->where(['parent_id' => $e->id])->count();
        if ($cnt == 0) {
            $this->deleteAll($e);
        } else {
            $children = $this->all()->where(['parent_id' => $e->id]);
            foreach($children as $ch)  {
                $this->_deleteRecursive($ch);
                $this->deleteAll($ch);
            }
        }
    }
    public function deleteRecursive($e) {
        $this->_deleteRecursive($e);
        $this->deleteAll($e);
    }

    public function prependIn($e, $n) {
        if ($e != null) {
            if ($e->first_id == null) {
                $e->data(['first_id'=>$n->id, 'last_id'=>$n->id]);
                $this->save($e);
                return $n;
            } else {
                $first = $this->findById($e->first_id);
                $e->data(['first_id' => $n->id]);
                $this->save($e);
                return $this->_prevTo($first, $n);
            }
        }
        return null;
    }
    public function appendIn($e, $n) {
        if ($e != null) {
            if ($e->last_id == null) {
                $e->data(['first_id'=>$n->id, 'last_id'=>$n->id]);
                $this->save($e);
                return $n;
            } else {
                $last = $this->findById($e->last_id);
                $e->data(['last_id' => $n->id]);
                $this->save($e);
                return $this->_nextTo($last, $n);
            }
        }
        return null;
    }
    public function listRecursive($e) {
    }


    public function findAllLogics($e) {
        if (!isset($e->first_id)) {
            return $this->_findAllLogics($e);
        } else {
            return ['id' => $e->id, 'type'=> $e->data_type, 'code' => $e->code, 'data' => $e->owned, 'label' => ['type' => $e->label->type, 'data'=>$e->label->data], 'children' =>$this->_findAllLogics($e)];
        }
    }

    public function _findAllLogics($e) {
        if (!isset($e->first_id)) {
            return ['id' => $e->id, 'type'=> $e->data_type, 'code' => $e->code, 'data' => $e->owned, 'label' => ['type' => "text", 'data'=>$e->data_type], 
            ];
        }
        else {
            $ch = $this->findById($e->first_id, ['owned', 'label']);
            $ret = [];
            if (!isset($ch->first_id )) {
                array_push($ret, $this->_findAllLogics($ch));
            } else {
                array_push($ret, ['id' => $ch->id,'type' => $ch->data_type, 'code' => $ch->code, 'data' => $ch->owned, 'label' => ['type' => "text", 'data'=> ""], 'children' => $this->_findAllLogics($ch)
                ]);
            }

            while(isset($ch->next_id)) {
                $ch = $this->findById($ch->next_id, ['owned', 'label']);
                if (!isset($ch->first_id)) {
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
        if (!isset($e->first_id)) {
            return $this->_findAllRecursive($e);
        } else {
            $logics = $this->findAllByTypeFrom($e, "logic");
            $rett = [];
            foreach($logics as $l) {
                $retx = $this->findAllLogics($l);
                array_push($rett, $retx);
            }
            return ['id' => (int)$e->id, 'type'=> $e->data_type, 'code' => $e->code, 'data' => $e->owned, 'label' => ['type' => $e->label->type, 'data'=>$e->label->data], 'children' => $this->_findAllRecursive($e),
            'logics' => ['id' => 0, 'type'=> 'root', 'label' => ['type' => 'text', 'data'=>'Logics'], 'children' => $rett]
            ];
        }
    }
    public function _findAllRecursive($e) {
        if (!isset($e->first_id)) {
            $logics = $this->findAllByTypeFrom($e, "logic");
            $rett= [];
            foreach($logics as $l) {
                $retx = $this->findAllLogics($l);
                array_push($rett, $retx);
            }
            return ['id' => (int)$e->id, 'type'=> $e->data_type, 'code' => $e->code, 'data' => $e->owned, 'label' => ['type' => $e->label->type, 'data'=>$e->label->data], 
                'logics' => ['id' => 0, 'type'=> 'root', 'label' => ['type' => 'text', 'data'=>'Logics'], 'children' => $rett ]
            ];
        }
        else {
            $ch = $this->findById($e->first_id, ['owned', 'label']);
            $ret = [];
            if (!isset($ch->first_id)) {
                array_push($ret, $this->_findAllRecursive($ch));

            } else {
                $logics = $this->findAllByTypeFrom($ch, "logic");
                $rett = [];
                foreach($logics as $l) {
                    $retx = $this->findAllLogics($l);
                    array_push($rett, $retx);
                }
                array_push($ret, ['id' => (int)$ch->id, 'type' => $ch->data_type, 'code' => $ch->code, 'data' => $ch->owned, 'label' => ['type' => $ch->label->type, 'data'=> $ch->label->data], 'children' => $this->_findAllRecursive($ch),
                'logics' => ['id' => 0, 'type'=> 'root', 'label' => ['type' => 'text', 'data'=>'Logics'], 'children' => $rett]
                ]);

            }
            while(isset($ch->next_id)) {
                $ch = $this->findById($ch->next_id, ['owned', 'label']);
                if ($ch->first_id == null) {
                    array_push($ret, $this->_findAllRecursive($ch));
                } else {
                    $logics = $this->findAllByTypeFrom($ch, "logic");

                    $rett= [];
                    foreach($logics as $l) {
                        $retx = $this->findAllLogics($l);
                        array_push($rett, $retx);
                    }
                    array_push($ret, ['id' => (int)$ch->id, 'type'=>$ch->data_type,
                       'code' => $ch->code, 'data' => $ch->owned, 'label' => ['type' => $ch->label->type, 'data'=>$ch->label->data], 'children'=>$this->_findAllRecursive($ch),
                       'logics' => ['id' => 0, 'type'=> 'root', 'label' => ['type' => 'text', 'data'=>'Logics'], 'children' => $rett ]
                   ]);
                }
            }
            return $ret;
        }
    }

    public function listFrom($el) {
        if ($el != null) {
            $ret = [$el];
            while($el->next_id != null) {
                $el = $this->findById($el->next_id, ['owned', 'label']);
                array_push($ret, $el);
            }
            return new Collection($ret);
        }
        return false;
    }
    public function findByParent($e) {
        return $this->all()->where(['parent_id' => $e->id]);
    }
    public function findRoot($e) {
        $tp = null;
        if ($e->parent_id != null) {
            $tp = $this->findById($e->parent_id);
            while($tp->parent_id != null) {
                $tp = $this->findById($tp->parent_id);
            }
            return $tp;
        } else {
            return false;
        }
    }
    public function listChildren($el) {
        $ret = [];
        $children = $this->findByParent($el);
    }
    public function getFirst($el) {
        if ($el == null) {
            return null;
        }
        while($el->prev_id != null) {
            $el = $this->findById($el->prev_id);
        }
        return $el;
    }
    public function getLast($el) {
        if ($el == null) {
            return null;
        }
        while($el->next_id != null) {
            $el = $this->findById($el->next_id);
        }
        return $el;
    }
    public function prepend($e, $n) {
        $l = $this->getFirst($e);
        if ($e->parent_id != null) {
            $p = $this->findById($e->parent_id);
            $p->data(['first_id' => $n->id]);
            $this->save($p);
        }
        return $this->_prevTo($l, $n);
    }

    public function prevTo($e, $n) {
        if (isset($e->parent_id)) {
            $p = $this->findById($e->parent_id);
            if ($p->first_id == $e->id) {
                $p->data(['first_id' => $n->id]);
                $this->save($p);
            }
        }
        return $this->_prevTo($e, $n);
    }

    public function append($e, $n) {
        $l = $this->getLast($e);
        if ($e->parent_id != null) {
            $p = $this->findById($e->parent_id);
            $p->data(['last_id' => $n->id]);
            $this->save($p);
        }
        return $this->_nextTo($l, $n);
    }
    public function nextTo($e, $n) {
        if (isset($e->parent_id)) {
            $p = $this->findById($e->parent_id);
            if ($p->last_id == $e->id) {
                $p->data(['last_id' => $n->id]);
                $this->save($p);
            } 
        }
        return $this->_nextTo($e, $n);
    }

    public function _prevTo($e, $n) {
        if ($e != null) {
            if ($e->prev_id == null) {
                $n->data(['next_id' => $e->id]);
                $this->save($n);
                $e->data(['prev_id' => $n->id]);
                $this->save($e);
            } else {
                $p = $this->findById($e->prev_id);
                $n->data(['next_id' => $e->id, 'prev_id' => $p->id ]);
                $this->save($n);
                $e->data(['prev_id'=>$n->id]);
                $this->save($e);
                $p->data(['next_id' => $n->id]);
                $this->save($p);
            }
        }
        return $n;
    }

    public function _nextTo($e, $n) {
        if ($e != null) {
            if ($e->next_id == null) {
                $n->data(['prev_id' => $e->id]);
                $this->save($n);
                $e->data(['next_id' => $n->id]);
                $this->save($e);
            } else {
                $ne = $this->findById($e->next_id);
                $n->data(['prev_id' => $e->id, 'next_id' => $ne->id ]);
                $this->save($n);
                $e->data(['next_id'=>$n->id]);
                $this->save($e);
                $ne->data(['prev_id' => $n->id]);
                $this->save($ne);
            }
        }
        return $n;
    }
    public function findAllByTypeFrom($e, $type, $with=['owned', 'label']) {
        return $this->all()->where(['data_type' => $type, 'parent_id' => $e->id])->with($with)->execute();

    }

    public function findAllByType($type, $with=['owned']) {
        return $this->all()->where(['data_type'=> $type])->with($with);
    }
    public function deleteAll($e) {
        $dataMapper = $this->getMapper(Element::$typeMappers[$e->data_type]);
        $labelMapper = $this->getMapper("App\Label");
        $owned = $e->owned->entity();
        $label = $e->label->entity();
        if ($owned === false) {
        } else {
            $dataMapper->delete($owned);
        }
        if ($label === false) {

        } else {
            $labelMapper->delete($label);
        }

        // Deleting from parent
        if (isset($e->parent_id)) {
            $p = $this->findById($e->parent_id);

            if ($p->first_id == $e->id) {
                $nf = $e->next_id;
                $p->data(['first_id' => $nf]);
                $this->save($p);
            }
            if ($p->last_id == $e->id) {
                $nl = $e->prev_id;
                $p->data(['last_id' => $nl]);
                $this->save($p);
            }
        }

        // Removing index from siblings
        $prev = null; $next = null;
        if ($e->prev_id != null) {
            $prev = $this->findById($e->prev_id);
        }
        if ($e->next_id != null) {
            $next = $this->findById($e->next_id);
        }

        if ($prev != null) {
            $prev->data(['next_id' => ($next ==null)? null: $next->id]);
            $this->save($prev);
        }
        if ($next != null) {
            $next->data(['prev_id' => ($prev == null)?null: $prev->id]);
            $this->save($next);
        }

        $this->delete($e);
    }

    public function move ($d, $e, $as="append-in") {
        // Deleting from parents
        if (isset($e->parent_id)) {
            $parent = $this->findById($e->parent_id);
            if ($parent->first_id == $e->id) {
                $nf = $e->next_id;
                $parent->data(['first_id' => $nf]);
                $this->save($parent);
            }
            if ($parent->last_id == $e->id) {
                $nl = $e->prev_id;
                $parent->data(['last_id' => $nl]);
                $this->save($parent);
            }
        }
       
        // Removing index from siblings
        $prev = null; $next = null;
        if (isset($e->prev_id)){
            $prev = $this->findById($e->prev_id);
        }
        if ($e->next_id != null) {
            $next = $this->findById($e->next_id);
        }

        if (isset($prev)) {
            $prev->data(['next_id' => ($next ==null)? null: $next->id]);
            $this->save($prev);
        }
        if (isset($next)) {
            $next->data(['prev_id' => ($prev == null)?null: $prev->id]);
            $this->save($next);
        }

        $e->data(['prev_id' => null, 'next_id'=> null, 'parent_id' => null]);
        $this->save($e);

        $res = null;
        $parent= null;
        switch ($as) {
            case "append-in": 
                $res = $this->appendIn($d, $e);
                $parent = $d->id;
                break;
            case "prepend-in": 
                $res = $this->prependIn($d, $e);
                $parent = $d->id;
                break;
            case "prepend": 
                $res = $this->appendIn($d, $e);
                $parent = $d->parent_id;
                break;
            case "append": 
                $res = $this->appendIn($d, $e);
                $parent = $d->parent_id;
                break;
            case "prev-to": 
                $res = $this->prevTo($d, $e);
                $parent = $d->parent_id;
                break;
            case "next-to": 
                $res = $this->nextTo($d, $e);
                $parent = $d->parent_id;
                break;
        }

        $res->data(['parent_id' => $parent]);
        $this->save($res);
        
        return ($res != null);
    }

}
