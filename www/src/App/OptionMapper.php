<?php 
namespace App\Mapper;
use Spot\Mapper;

class OptionMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }
    public function findAllFromQuestion($question) {
        return $this->all()->where(['question_id'=>$question['uid']])->order(['sort'=>'DESC']);
    }
    public function countFromQuestion($question) {
        return $this->where(['question_id'=>$question['uid']])->count();
    }

    public function getOne($uid)
    {
        return $this->where(['uid' => $uid])
            ->order(['sort' => 'DESC'])
            ->with(["author", "question"]);
    }
    public function listOptions($quid)
    {
        return $this->where(["question_id" => $quid])
            //->with(["author", "question"])
            ->order(['sort' => 'ASC']);
    }
}
