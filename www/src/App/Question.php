<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use Exception\NotFoundException;

use App\Option;
use App\Element;

class Question extends \Spot\Entity
{
    protected static $table = "questions";
    protected static $mapper = "App\Mapper\QuestionMapper";

    public static function fields()
    {
        return [ 
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary"=>true],
            "type" => ["type" => "string"],
            "sub_type" => ["type" => "string"],
            "start_id" => ["type" => "integer"],
            "element_id" => ["type" => "integer"]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->uid = Base62::encode(random_bytes(16));
        });

        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->updated_at = new \DateTime();
        });

        $emitter->on("beforeDelete", function (EntityInterface $entity, MapperInterface $mapper) {
        });
    }

    public function addOption($option) {
        $o = new Option(['data' => $option['label'], 'type' => 'text', 'question_id' => $this->id]);
        $this->relation('options')->mapper()->getMapper('App\Option')->save($o);
        //$this->relation('options')->save($o);
        return $o;
    }

    public function check($spot, $action, $tq) {
        //$ls = $spot->mapper('App\Logic')->all()->where(['target_type'=>'question', 'target_id'=>$this->uid, 'action'=>$action]);
            //$ret = null;
            //foreach($ls as $l) {
                //$aux = $l->evaluate($spot, $tq);
                //if (isset($aux)) {
                    //$ret = $ret || $aux;
                //}
            //}
            //return $ret;
    }
    public function checkVisibility($spot, $tq) {
        //$show = $this->check($spot, 'show', $tq);
        //$hide = $this->check($spot, 'hide', $tq);

        //if (!isset($hide) && !isset($show)) {
            //return true;
        //}
        //if (isset($hide)) {
            //return !$hide;
        //}
        //else if(isset($show)){
            //return $show;
        //}
    }


    public function findNext($spot, $tq) {
        //if ($this->next_id == null) {
            //$step = $spot->mapper('App\Step')->findById($this->step_id);

            //$nextStep = $spot->mapper('App\Step')->findById($step->next_id);
            //while(!$nextStep->checkVisibility($spot, $tq)) {
                //if ($nextStep->next_id != null) {
                    //$nextStep = $spot->mapper('App\Step')->findById($nextStep->next_id);
                    //if ($nextStep->start_id == null) {
                        //$nextStep = $spot->mapper('App\Step')->findById($nextStep->next_id);
                    //}
                //}
                //else {
                    //$nextStep = false;
                    //break;
                //}
            //}
            //if ($nextStep === false) {
                //throw NotFoundException("End of Steps Reached", 404);
            //}
            //$question = $spot->mapper('App\Question')->findById($nextStep->start_id);
            //if (!$question->checkVisibility($spot, $tq)) {
                //$nextQuestion= $spot->mapper('App\Question')->findById($question->next_id);
                //while(!$nextQuestion->checkVisibility($spot, $tq)) {
                    //if ($nextQuestion->next_id != null) {
                        //$nextQuestion = $spot->mapper('App\Question')->findById($nextQuestion->next_id);
                    //} else {
                        //$nextQuestion = false;
                        //break;
                    //}
                //}
                //if ($nextQuestion === false) throw new NotFoundException("End of Questions reached");
                //return $nextQuestion;
            //}
            //return $question;
        //} else {
            //$nextQuestion = $spot->mapper('App\Question')->findById($this->next_id);
            //if ($nextQuestion === false) 
                //throw new NotFoundException("FindNext Question: next Question not found" ,404);
            //while(!$nextQuestion->checkVisibility($spot, $tq)) {
                //if ($nextQuestion->next_id != null) {
                    //$nextQuestion = $spot->mapper('App\Question')->findById($nextQuestion->next_id);
                //} else {
                    //$nextQuestion = $nextQuestion->findNext($spot, $tq);
                    //break;
                //}
            //}
            //if ($nextQuestion === false) throw new NotFoundException("End of Questions reached");
            //return $nextQuestion;
        //}
    }

    public function clear()
    {
        $this->data([
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'options' => $mapper->hasMany($entity, 'App\Option', 'question_id', $entity->localKey)
            //'logics' => $mapper->hasMany($entity, 'App\Logic', 'question_id', $entity->localKey)
        ];
    }
}
