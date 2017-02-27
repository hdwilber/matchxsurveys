<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Spot\Locator;
use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use TakenQuiz;

class MatchLogic extends \Spot\Entity
{
    protected static $table = "match_logics";
    protected static $mapper = "App\Mapper\MatchLogicMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "uid" => ["type" => "string", "length" => 50, "primary" => true, "unique" => true],
            "bool" => ["type" => "string"], 
            "parent_id" => ["type" => "string"],
            "target_id" => ["type" => "string"],
            "target_type" => ["type" => "string"],
            "created_at"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated_at"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->uid = Base62::encode(random_bytes(16));
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->updated_at = new \DateTime();
        });
    }
    public function timestamp()
    {
        return $this->updated_at->getTimestamp();
    }

    public function etag()
    {
        return md5($this->uid . $this->timestamp());
    }
    public function dump(Locator $spot) {
        if ($this->target_type == "match") {
            return $this->bool . " " . $this->uid;
        } else {
            $mls = $spot->mapper("App\MatchLogic")->findAllFromParent($this);
            $ret = "  ". $this->bool . " ( ";
            foreach($mls as $ml) {
                $ret = $ret . "  ". $ml->dump($spot);
            }
            $ret =  $ret . " ) ";
            return $ret;
        }
    }
    public function toString($spot) {
        
    }

    public function buildHierarchy($spot) {
        if ($this->target_type == "match") {
            $match = $spot->mapper("App\Match")->findById($this->target_id);
            $ret =[];
            array_push($ret, ['data' => ['type'=>'match', 'name'=>$match->toString($spot), 'bool'=> null, 'operator' => $match->operator, 'matchType' =>'single', 'uid'=>$match->uid] ]);
            return $ret;
        }
        else {
            return $this->_buildHierarchy($spot);
        }
    }
    public function _buildHierarchy($spot) {
        if ($this->target_type == "match") {
            $match = $spot->mapper("App\Match")->findById($this->target_id);
            if ($match === false) {
                return [];
            } else {
                return ['data' => ['type'=>'match', 'target_question_id' => $match->target_question_id, 'target_option_id' => $match->target_option_id, 'name'=>$match->toString($spot), 'bool'=> null, 'operator' => $match->operator, 'matchType' =>'single', 'uid'=>$match->uid] ];
            }
        } else {
            $mls = $spot->mapper("App\MatchLogic")->findAllFromParent($this);
            $ret = [];
            foreach($mls as $ml) {
                if ($ml->target_type != null)
                    array_push($ret, ['checked'=>false,'expanded' => true, 'data'=>['uid'=>$ml->uid, 'type' => "match-logic", 'name' => 'Match Logic', 'bool' => $ml->bool], 'children' => [$ml->_buildHierarchy($spot)]]);
                else 
                    array_push($ret, ['checked'=>false,'expanded' => true, 'data'=>['uid'=>$ml->uid, 'type' => "match-logic", 'name' => 'Match Logic', 'bool' => $ml->bool], 'children'=>$ml->_buildHierarchy($spot)]);

            }
            return $ret;
        }
    }

    public function removeChildren($spot) {
        if ($this->target_type == "match") {
            $match = $spot->mapper("App\Match")->findById($this->target_id);
            if ($match === false) {
                return;
            } else {
                $spot->mapper("App\Match")->delete($match);
                return;
            }
        }
        else {
            $this->_removeChildren($spot);
            return ;
        }
    }
    public function _removeChildren($spot) {
        if ($this->target_type == "match") {
            $match = $spot->mapper("App\Match")->findById($this->target_id);
            if ($match === false) {
                return;
            } else {
                $spot->mapper("App\Match")->delete($match);
            }
        } else {
            $mls = $spot->mapper("App\MatchLogic")->findAllFromParent($this);
            foreach($mls as $ml ){
                $ml->_removeChildren($spot);
                $spot->mapper("App\MatchLogic")->delete($ml);
            }
            $spot->mapper("App\MatchLogic")->delete($this);
        }
    }

    public function evaluate($spot, $tq) {
        if ($this->target_type == "match") {
            $match = $spot->mapper("App\Match")->findById($this->target_id);
            if ($match === false) {
                return null;
            }
            // When not found, continue valoration
            $question = $spot->mapper("App\Question")->findById($match->target_question_id);
            if ($question === false) {
                return null;
            }
            $selection = $spot->mapper("App\Selection")->findByQuestion($tq, $question);
            if ($selection === false) {
                return null;
            }

            if ($match->operator == "eq") {
                //if ($selection->question_id == $match->target_question_id && $selection->option_id == $match->target_option_id)
                if ($selection->option_id == $match->target_option_id)
                    return true;
                else {
                    return false;
                }
            }
            return null;
        }
        else {
            $mls = $spot->mapper("App\MatchLogic")->findAllFromParent($this);
            $ret = null;
            foreach($mls as $ml) {
                $ev = $ml->evaluate($spot, $tq);
                if ($ml->bool == "and") {
                    $ret = $ret && $ev;
                }
                else if ($ml->bool == "or"){
                    $ret = $ret || $ev;
                }
                else {
                    $ret = $ev;
                }
            }
            return $ret;
        }
    }

    public function clear()
    {
        $this->data([
            "target_id" => null,
            "target_type"=> null
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            //'question' => $mapper->belongsTo($entity, 'App\Question', 'question_id')
        ];
    }
}
