<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Match extends \Spot\Entity
{
    protected static $table = "matchs";
    protected static $mapper = "App\Mapper\MatchMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true],
            "uid" => ["type" => "string", "length" => 50, "primary" => true, "unique" => true],
            "question_id" => ["type" => "string"],
            "user_id" => ["type" => "string", "length" => 50 ],
            // Operator to compare EQ for Options, GT, GTE, LT, LTE, BT for Values
            "operator" => ["type" => "string"],
            "type" => ['type' => 'string'],
            "target_option_id" => ["type" => "string"],
            "target_question_id" => ["type" => "string"],
            "target_value" => ['type' => 'integer'],
            // Some extra data
            "detail" => ['type' => "string"],
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
            "operator" => null,
        ]);
    }
    public function toString($spot) {
        $quMapper = $spot->mapper("App\Question");
        $opMapper = $spot->mapper('App\Option');
        $question = $quMapper->findById($this->target_question_id);
        $option = $opMapper->findById($this->target_option_id);

        return $question->code . ": " . $option->text;
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            //'question' => $mapper->belongsTo($entity, 'App\Question', 'question_id')
        ];
    }
}
