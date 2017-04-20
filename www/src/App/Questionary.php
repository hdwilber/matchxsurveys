<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Questionary extends \Spot\Entity
{
    protected static $table = "questionaries";
    protected static $mapper = "App\Mapper\QuestionaryMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary" => true],
            "element_id" => ['type' => 'integer']
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
        });
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
        ];
    }
}
