<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Option extends \Spot\Entity
{
    protected static $table = "options";
    protected static $mapper = "App\Mapper\OptionMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "uid" => ["type" => "string", "length" => 50, "primary" => true, "unique" => true],
            "question_id" => ["type" => "string", "length" => 50 ],
            "user_id" => ["type" => "string", "length" => 50 ],
            "text" => ["type" => "string", "length" => 255, "value"=>null],
            "value" => ["type" => "integer", "value" => -1],
            "sort" => ['type' => 'integer'],
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
            "order" => null,
            "name" => null
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'author' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'question' => $mapper->belongsTo($entity, 'App\Question', 'question_id')
        ];
    }
}
