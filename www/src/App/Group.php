<?php
namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

use Tuupola\Base62;

use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

use Exception\NotFoundException;

class Group extends \Spot\Entity
{
    protected static $table = "groups";
    protected static $mapper = "App\Mapper\GroupMapper";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "autoincrement" => true, "primary"=>true],
            "start_id" => ["type" => "integer"],
            "element_id" => ["type" => "integer"]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        parent::events($emitter);
        $emitter->on("beforeInsert", function (EntityInterface $entity, MapperInterface $mapper) {
            //$entity->uid = Base62::encode(random_bytes(16));
        });
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
            "code" => null,
        ]);
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'label' => $mapper->belongsTo($entity, 'App\Label', 'label_id')
            //'options' => $mapper->hasMany($entity, 'App\Option', 'question_id', $entity->localKey),
            //'logics' => $mapper->hasMany($entity, 'App\Logic', 'question_id', $entity->localKey)
        ];
    }
}
