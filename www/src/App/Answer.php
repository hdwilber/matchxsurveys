<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Answer extends \Spot\Entity
{
    protected static $table = "answers";
    protected static $mapper = "App\Mapper\AnswerMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "question_id" => ["type" => "integer"],
            "option_id" => ["type" => "integer"],
            "value" => ["type" => "integer"],
            "data" => ["type" => "string"],
            "valid" => ["type" => "boolean", "vaue" => false],
            "element_id" => ["type" => "integer"]
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
            'option' => $mapper->belongsTo($entity, 'App\Element', 'option_id'),
            'question' => $mapper->belongsTo($entity, 'App\Element', 'question_id')
        ];
    }
}
