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
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "uid" => ["type" => "string", "length" => 50, "primary" => true, "unique" => true],
            "target_id" => ["type" => "string", "length" => 50 ],
            "target_type" => ["type" => "string", "length" => 50 ],
            "match_logic_id" => ["type" => "string", "length" => 50 ],
            "action" => ["type" => "string", "length" => 50 ],
            "user_id" => ["type" => "string"],
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

    public function buildHierarchy(Locator $spot) {
        $matchLogic = $spot->mapper("App\MatchLogic")->findById($this->match_logic_id);
        if ($matchLogic === false) {
            throw new NotFoundException("Logic: MatchLogic not found", 404);
        }
        return $matchLogic->buildHierarchy($spot);
    }

    public function evaluate($spot, $tq) {
        $matchLogic = $spot->mapper("App\MatchLogic")->findById($this->match_logic_id);
        
        if ($matchLogic === false) {
            throw new NotFoundException("Logic: MatchLogic not found", 404);
        }
        return $matchLogic->evaluate($spot, $tq);
    }
    public function removeChildren($spot) {
        $matchLogic = $spot->mapper("App\MatchLogic")->findById($this->match_logic_id);
        if ($matchLogic === false) {
            return;
        } else {
            return $matchLogic->removeChildren($spot);
        }
    }

    public function etag()
    {
        return md5($this->uid . $this->timestamp());
    }

    public function clear()
    {
        $this->data([
        ]);
    }


    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
        ];
    }
}
