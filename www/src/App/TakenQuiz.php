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
            "uid" => ["type" => "string", "length" => 50, "primary"=> true,  "unique"=> true],
            "questionary_id" => ["type" => "string"],
            "user_id" => ['type' => 'string'],
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
            "sort" => null,
            "name" => null
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'taker' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'questionary' => $mapper->belongsTo($entity, 'App\Questionary', 'questionary_id'),
            'selections' => $mapper->hasMany($entity, 'App\Selection', 'taken_quiz_id', $entity->localKey)
        ];
    }
}
