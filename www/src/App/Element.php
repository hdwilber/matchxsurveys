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
        "answer" => "App\\Answer",
        "option" => "App\\Option",
        "taken-quiz" => "App\\TakenQuiz",
        "logic" => "App\\Logic"
    ];

    public static $addingTypes = [
        [
            "name" => "Prev To",
            "code" => "prev-to",
            "description" => "Inserts element before of the..."
        ],
        [
            "name" => "Next To",
            "code" => "next-to",
            "description" => "Inserts element after of the..."
        ],
        [
            "name" => "Prepend",
            "code" => "prepend",
            "description" => "Prepends the element to siblings..."
        ],
        [
            "name" => "Append",
            "code" => "append",
            "description" => "Append the element to siblings ..."
        ],
        [
            "name" => "Append In",
            "code" => "append-in",
            "description" => "Append the element as child"
        ],
        [
            "name" => "Prepend In",
            "code" => "prepend-in",
            "description" => "Prepend the elemnt as child"
        ],
        [
            "name" => "Make Root",
            "code" => "root",
            "description" => "Puts the element in the root"
        ]
    ];
    public static $dataTypes = [
        [
            "name" => "Question",
            "code" => "question",
            "description" => "Main element to play with"
        ],
        [
            "name" => "Questionary",
            "code" => "questionary",
            "description" => "Root element to play with"
        ],
        [
            "name" => "Option",
            "code" => "option",
            "description" => "Some element to play with"
        ],
        [
            "name" => "Group",
            "code" => "group",
            "description" => "Main element to group nd play with"
        ]
    ];

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary"=>true],
            "code" => ["type" => "string", "length" => 100],
            "parent_id" => ["type" => "integer"],
            "next_id" => ['type' => 'integer'],
            "prev_id" => ['type' => 'integer'],
            "first_id" => ['type' => 'integer'],
            "last_id" => ['type' => 'integer'],
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

    public function saveData($mapper, $d) {
        $data_mapper = $mapper->getMapper(static::$typeMappers[$this->data_type]);
        // Group Type
        $owned = $data_mapper->findById($this->owned->id);
        $owned->data($d);
        $data_mapper->save($owned);
        return $owned;
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
            case "logic":
                $data_entity = new Logic($data);
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
            case "option":
                $data_entity = new Option($data);
                break;
            case "taken-quiz":
                $data_entity = new TakenQuiz($data);
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
            'children' => $mapper->hasMany($entity, 'App\Element', 'parent_id', $entity->localKey),
            'start' => $mapper->belongsTo($entity, 'App\Element', 'first_id'),
            'end' => $mapper->belongsTo($entity, 'App\Element', 'last_id'),
            'first' => $mapper->belongsTo($entity, 'App\Element', 'first_id'),
            'last' => $mapper->belongsTo($entity, 'App\Element', 'last_id')
        ];
    }
}
