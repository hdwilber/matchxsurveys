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
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],

            "uid" => ["type" => "string", "length" => 50, "primary"=> true,  "unique"=> true],
            "code" => ["type" => "string", "length" => 100],
            "text" => ["type" => "string", "length" => 255],
            "user_id" => ['type' => 'string'],
            "start_id" => ['type' => 'string'],
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
            "code" => null,
            "text" => null
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'steps' => $mapper->hasMany($entity, 'App\Step', 'questionary_id', $entity->localKey),
        ];
    }
}
