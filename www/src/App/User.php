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
            "uid" => ["type" => "string", "length" => 50, "unique" => true, "primary" => true],
            "email" => ["type" => "string", "length" => 100, "unique" => true],
            "password" => ["type" => "string", "length" => 255 ],
            "type" => ["type" => "string", "length" => "100"],
            "created_at"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated_at"   => ["type" => "datetime", "value" => new \DateTime()],
            "confirmed" => ["type" => "boolean", "value"=> false]
        ];
    }

    public static function events(EventEmitter $emitter) {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->uid = Base62::encode(random_bytes(16));
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
        return md5($this->uid . $this->timestamp());
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
