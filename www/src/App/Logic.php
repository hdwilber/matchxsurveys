<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;
use Spot\Mapper;

use Spot\Locator;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use App\Question;

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;

class Logic extends \Spot\Entity
{
    protected static $table = "logics";
    protected static $mapper = "App\Mapper\LogicMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, 'primary'=>true],
            "start_id" => ["type" => "integer"],
            "action" => ["type" => "string", "length" => 50 ],
            "questionary_id" => ['type' => 'integer'],
            "element_id" => ["type" => "integer", "length" => 50 ]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
        });
    }

    public function buildHierarchy(Locator $spot) {
        //$matchLogic = $spot->mapper("App\MatchLogic")->findById($this->match_logic_id);
        //if ($matchLogic === false) {
            //throw new NotFoundException("Logic: MatchLogic not found", 404);
        //}
        //return $matchLogic->buildHierarchy($spot);
    }

    public function evaluate($spot, $tq) {
        //$matchLogic = $spot->mapper("App\MatchLogic")->findById($this->match_logic_id);
        
        //if ($matchLogic === false) {
            //throw new NotFoundException("Logic: MatchLogic not found", 404);
        //}
        //return $matchLogic->evaluate($spot, $tq);
    }
    public function removeChildren($spot) {
        //$matchLogic = $spot->mapper("App\MatchLogic")->findById($this->match_logic_id);
        //if ($matchLogic === false) {
            //return;
        //} else {
            //return $matchLogic->removeChildren($spot);
        //}
    }

    public function clear()
    {
        $this->data([
        ]);
    }


    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            "start" => $mapper->belongsTo($entity, "App\Element", "start_id"),
                "questionary" => $mapper->belongsTo($entity, "App\Element", "questionary_id")
        ];
    }
}
