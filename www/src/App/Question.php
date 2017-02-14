<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Question extends \Spot\Entity
{
    protected static $table = "questions";
    protected static $mapper = "App\Mapper\QuestionMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],

            "uid" => ["type" => "string", "length" => 50, "primary"=> true,  "unique"=> true],
            "code" => ["type" => "string", "length" => 100],
            "text" => ["type" => "string", "length" => 255],
            "type" => ["type" => "string"],
            "user_id" => ['type' => 'string'],
            "step_id" => ['type' => 'string'],
            "next_id" => ['type' => 'string'],
            "mandatory" => ['type' => 'boolean', 'value'=>true],

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

    public function clear()
    {
        $this->data([
            "text" => null,
            "code" => null,
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'options' => $mapper->hasMany($entity, 'App\Option', 'question_id', $entity->localKey),
            'logics' => $mapper->hasMany($entity, 'App\Logic', 'question_id', $entity->localKey)
        ];
    }
}
