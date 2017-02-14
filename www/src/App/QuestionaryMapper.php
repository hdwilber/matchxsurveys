<?php 
namespace App\Mapper;
use Spot\Mapper;

class QuestionaryMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid' => $uid])->with(['steps'])->first();
    }

    public function findLastModified() {
        return $this->all()->order(['updated_at' => 'DESC'])->first();
    }
    public function findLastModifiedFromQuestionary($questionary) {
        return $this->where(['questionary_id' => $questionary['uid']])->order(['updated_at' => 'DESC'])->first();
    }

    public function findAll() {
        return $this->all()->order(['created_at' => 'DESC'])->with(['steps']);
    }

    public function getById($uid)
    {
        return $this->where(['uid' => $uid])
            ->with(["steps"])->first();
    }
    public function getLastModified() {
        return $this->all()->order(['updated_at' => 'DESC'])->first();
    }
    public function listAll() {
        return $this->all()->order(['code' => 'DESC']);
    }
    public function getFirstFromStep($suid) {
        return $this->where(['step_id'=>$suid])->order(['sort'=>'DESC'])->first();
    }
}
