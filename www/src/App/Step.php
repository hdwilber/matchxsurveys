<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Step extends \Spot\Entity
{
    protected static $table = "steps";
    protected static $mapper = "App\Mapper\StepMapper";

    public static function fields()
    {
        return [
            "uid" => ["type" => "string", "primary"=> true, "length" => 50, "unique" => true],
            "code" => ["type" => "string", "length" => 255],
            "text" => ["type" => "string", "length" => 255],
            "user_id" => ["type" => "string", "length" => 255],
            "questionary_id" => ["type" => "string", "length" => 255],
            "start_id" => ["type" => "string"],
            "next_id" => ["type" => "string"],
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
            'questions' => $mapper->hasMany($entity, 'App\Question', 'step_id', $entity->localKey),
            'questionary' => $mapper->belongsTo($entity, 'App\Questionary', 'questionary_id')
        ];
    }

}
