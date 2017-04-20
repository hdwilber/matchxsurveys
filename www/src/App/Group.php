<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use Exception\NotFoundException;

class Group extends \Spot\Entity
{
    protected static $table = "groups";
    protected static $mapper = "App\Mapper\GroupMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary"=>true],
            "start_id" => ["type" => "integer"],
            "default_visibility" => ["type" => "boolean", 'value' => true],
            "element_id" => ["type" => "integer"]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        parent::events($emitter);
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->uid = Base62::encode(random_bytes(16));
        });
    }

    public function findNext($spot, $tq) {
    }

    public function clear()
    {
        $this->data([
            "code" => null,
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            //'label' => $mapper->belongsTo($entity, 'App\Label', 'label_id')
            //'options' => $mapper->hasMany($entity, 'App\Option', 'question_id', $entity->localKey),
            //'logics' => $mapper->hasMany($entity, 'App\Logic', 'question_id', $entity->localKey)
        ];
    }
}
