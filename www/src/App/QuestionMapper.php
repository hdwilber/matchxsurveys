<?php 
namespace App\Mapper;
use Spot\Mapper;

class QuestionMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid'=>$uid])->with(['options', 'logics'])->first();
    }
    public function findLastModifiedFromStep($step) {
        if(isset($step['uid'])) {
            return $this->all()->where(['step_id' => $step['uid']])->order(['updated_at'=>"DESC"])->first();
        } else {
            return false;
        }
    }
    public function findAllFromStep($step) {
        if (isset($step['uid'])) {
            return $this->all()->where(['step_id' => $step['uid']])->with(['options', 'logics']);
        } else {
            return false;
        }
    }

    public function getById($uid)
    {
        return $this->where(['uid' => $uid])
            ->with(["options", "logics"])
            ->order(['sort' => 'DESC']);
    }
    public function getByIdFromStep($suid, $uid) {
        return $this->where(['step_id' => $suid, 'uid' => $uid])->first();
    }
    public function getNextFromStep($suid, $uid = null) {
        $qq = $this->where(['step_id' => $suid])->order(['sort'=> 'ASC'])->with(['options', 'logics']);
        $current = $qq->first();
        if ($uid == null) {
            //$current = $qq->where(['uid'=>$uid])->first();
            //print_r($current->toArray());
            return $current;
        } else {
            //$next = $qq->where(['order :gt' => $current->toArray() ])first();
            //print_r($current->toArray());
            return $current;
        }
    }

    public function getFirstFromStep($suid) {
        return $this->where(['step_id' => $suid])->with(['options'])->order(['sort' => 'ASC'])->first();
    }
    public function listQuestions() {
        return $this->all()->with(["options"])->order(['sort' => 'DESC']);
    }
    public function getLastModifiedFromStep($suid) {
        return $this->all()->where(['step_id' => $suid])->order(['updated_at' => 'DESC'])->first();
    }
    public function listByStep($suid) {
        return $this->all()->where(['step_id'=>$suid])->with(['author', 'options']);
    }
}
