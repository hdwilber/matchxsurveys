<?php 
namespace App\Mapper;
use Spot\Mapper;

class LogicMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid' => $uid])->first();
    }
    public function findLastModifiedFromQuestion($question) {
        return $this->all()->where(['question_id' => $question['uid']])->order(['updated_at'=>"DESC"])->first();
    }
    public function findAllFromQuestion($question) {
        return $this->all()->where(['question_id'=>$question['uid']]);
    }
    public function getOne($uid)
    {
        return $this->where(['uid' => $uid]);
    }
    public function listLogics($quid)
    {
        return $this->where(["question_id" => $quid])
            ->order("type", "DESC");
            //->with(["author", "question"])
    }

    public function fullLoad($uid) {
        $data = $this->where(['uid' => $uid])->first();
    }
}
