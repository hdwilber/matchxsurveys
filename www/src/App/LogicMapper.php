<?php 
namespace App\Mapper;
use Spot\Mapper;

class LogicMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid' => $uid])->first();
    }
    public function findLastModifiedFromStep($step) {
        return $this->all()->where(['target_type' => 'step', 'target_id' => $step->uid])->order(['updated_at'=>"DESC"])->first();
    }
    public function findAllFromStep($step) {
        return $this->all()->where(['target_type'=>'step', 'target_id'=>$step->uid]);
    }
    public function findLastModifiedFromQuestion($question) {
        return $this->all()->where(['target_type' => 'question', 'target_id' => $question->uid])->order(['updated_at'=>"DESC"])->first();
    }
    public function findAllFromQuestion($question) {
        return $this->all()->where(['target_type'=>'question', 'target_id'=>$question->uid]);
    }
    public function getOne($uid)
    {
        return $this->where(['uid' => $uid]);
    }
    public function listLogics($quid)
    {
        return $this->where(['target_type'=>'question',"target_id" => $quid])
            ->order("type", "DESC");
            //->with(["author", "question"])
    }

    public function fullLoad($uid) {
        $data = $this->where(['uid' => $uid])->first();
    }
}
