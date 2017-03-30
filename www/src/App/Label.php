<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use Exception\NotFoundException;

class Label extends \Spot\Entity
{
    protected static $table = "labels";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary" => true],
            "type" => ["type" => "string", "length" => 100],
            "data" => ["type" => "string", "length" => 100],
            "extra" => ["type" => "string", "length" => 100],
            "element_id" => ["type" => "integer"]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->uid = Base62::encode(random_bytes(16));
        });
        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->updated_at = new \DateTime();
        });
    }

    public function toString() {
        switch($this->type) {
            case "text": 
                return $data;
            default:
                return $data;
        }
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
        ];
    }
}
