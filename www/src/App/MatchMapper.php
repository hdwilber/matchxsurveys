<?php 
namespace App\Mapper;
use Spot\Mapper;

class MatchMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }
    public function findLastModifiedFromQuestion($question) {
        return $this->where(['question_id' => $question['uid']])->order(['updated_at'=>'DESC'])->first();
    }
    public function findAllFromQuestion($question) {
        return $this->where(['question_id'=>$question['uid']])->all();
    }
    public function getOne($uid)
    {
        return $this->where(['uid' => $uid])
            ->with(["author", "question"]);
    }
    public function listMatches($quid)
    {
        return $this->where(["question_id" => $quid]);
            //->with(["author", "question"])
    }
}
