<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class User extends \Spot\Entity
{
    protected static $table = "users";
    protected static $mapper = "App\Mapper\UserMapper";

    public static function fields() {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "email" => ["type" => "string", "length" => 100, "unique" => true],
            "name" => ["type" => "string", "length" => 100],
            "password" => ["type" => "string", "length" => 255],
            "type" => ["type" => "string", "length" => 100],
            "confirmed" => ["type" => "boolean", "value"=> false],
            "confirm_code" => ["type" => "boolean", "value"=> false],
            "confirm_created_at" => ["type" => "datetime", "value" => new \DateTime()],
            "created_at"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated_at"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function events(EventEmitter $emitter) {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->uid = Base62::encode(random_bytes(16));
            $entity->confirm_code = Base62::encode(random_bytes(32));
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->updated_at = new \DateTime();
        });
    }

    public function timestamp() {
        return $this->updated_at->getTimestamp();
    }

    public function created_at() {
        return $this->created_at->getTimestamp();
    }
    
    public function etag()
    {
        return md5($this->id . $this->timestamp());
    }

    public function clear()
    {
        $this->data([
            "email" => null,
            "type" => null
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [

        ];
    }
}
