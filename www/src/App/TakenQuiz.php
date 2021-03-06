<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class TakenQuiz extends \Spot\Entity
{
    protected static $table = "taken_quizzes";
    protected static $mapper = "App\Mapper\TakenQuizMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "questionary_id" => ["type" => "integer"],
            "first_id" => ['type' => 'integer'],
            "last_id" => ['type' => 'integer'],
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
            "questionary" => $mapper->belongsTo($entity, "App\\Element", "questionary_id")
        ];
    }
}
