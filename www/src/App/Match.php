<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Match extends \Spot\Entity
{
    protected static $table = "matchs";
    protected static $mapper = "App\Mapper\BaseMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, 'primary'=>true],
            "operator" => ["type" => "string"],
            "target_id" => ["type" => "integer"],
            "target_option_id" => ['type' => "integer"],
            "target_value" => ['type' => "integer"],
            "element_id" => ['type' => "integer"]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
        });
    }


    public function clear()
    {
        $this->data([
            "operator" => null,
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
        ];
    }
}
