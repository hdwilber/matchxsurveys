<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use Exception\NotFoundException;

class Element extends \Spot\Entity
{
    protected static $mapper = "App\Mapper\ElementMapper";
    protected static $table = "elements";

    public $extra = false;

    public function setExtra($d) {
        $extra = $d;
    }

    public static $typeMappers= [
    "question" => "App\\Question",
    "match-logic" => "App\\MatchLogic",
    "group" => "App\\Group",
    "questionary" => "App\\Questionary",
    "match" => "App\\Match",
    "answer" => "App\\Answer"
    ];



    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary"=>true],
            "code" => ["type" => "string", "length" => 100],
            "parent_id" => ["type" => "integer"],
            "next_id" => ['type' => 'integer'],
            "prev_id" => ['type' => 'integer'],
            "data_type" => ['type'=>'string'],
            "user_id" => ['type' => 'integer'],
            "created_at" => ["type" => "datetime", "value" => new \DateTime()],
            "updated_at" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
        });
        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->updated_at = new \DateTime();
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

    //public function getLabel() {
        //$l = $this->relation('label');
        //if ($l === false) {
            //return "No label";
        //}
        //return $l->data;
    //}

    public function getNext() {
    }

    public function saveData($mapper, $d) {
        $data_mapper = $mapper->getMapper(static::$typeMappers[$this->data_type]);
        $ret['d'] = $d;
        // Group Type
        $owned = $data_mapper->findById($this->owned->id);
        $ret['ownoed'] = $owned;
        $owned->data($d);
        $data_mapper->save($owned);
        $ret['ownoedSaved'] = $owned;
        return $ret;
    }

    public function createData(MapperInterface $mapper, $data) {
        $data_entity = null;
        $data_mapper = $mapper->getMapper(static::$typeMappers[$this->data_type]);
        $data = array_merge($data, ['element_id' => $this->id]);
        switch($this->data_type) {
            case "question": 
                $data_entity = new Question($data);
                break;
            case "group": 
                $data_entity= new Group($data);
                break;
            case "questionary": 
                $data_entity= new Questionary($data);
                break;
            case "match-logic": 
                $data_entity= new MatchLogic($data);
                break;
            case "match": 
                $data_entity = new Match($data);
                break;
            case "answer":
                $data_entity = new Answer($data);
                break;

        }
        $data_mapper->save($data_entity);

        return $data_entity;
    }
    public function createLabel(MapperInterface $mapper, $type, $data) {
        $label = null;
        $data_mapper = $mapper->getMapper("App\Label");
        $label = new Label(["element_id" => $this->id, "type"=>$type, 'data'=>$data]);
        $data_mapper->save($label);
        return $label;
    }


    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'owned' => $mapper->hasOne($entity, static::$typeMappers[$entity->data_type], 'element_id'),
            'label' => $mapper->hasOne($entity, 'App\Label', 'element_id'),
            'logics' => $mapper->hasMany($entity, 'App\Logic', 'element_id', $entity->localKey),
            'next' => $mapper->belongsTo($entity, 'App\Element', 'next_id'),
            'prev' => $mapper->belongsTo($entity, 'App\Element', 'prev_id'),
            'parent' => $mapper->belongsTo($entity, 'App\Element', 'parent_id'),
            'children' => $mapper->hasMany($entity, 'App\Element', 'parent_id', $entity->localKey)
        ];
    }
}
