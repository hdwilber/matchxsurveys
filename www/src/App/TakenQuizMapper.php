<?php 
namespace App\Mapper;
use Spot\Mapper;

class TakenQuizMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }

    public function findLastModifiedFromUser($uid) {
        return $this->all()->where(['user_id'=>$uid])->order(['updated_at' => 'DESC'])->first();
    }
    public function findAllFromUser($uid) {
        return $this->all()->where(['user_id'=>$uid])->with(['questionary'])->order(['updated_at' => 'DESC']);
    }

    public function getLastModifiedFromUser($uid) {
        return $this->where(['user_id'=>$uid])->order(['updated_at' => 'DESC'])->first();
    }
    public function listAll()
    {
        return $this->all()->order(['updated_at' => 'DESC']);
    }
    public function listAllFromUser($uid)
    {
        return $this->all()->where(['user_id'=>$uid])->with(['questionary'])->order(['updated_at' => 'DESC']);
    }
    public function getById($uid) {
        return $this->where(['uid' => $uid])->first();
    }
    public function getByIdFull($uid) {
        return $this->where(['uid' => $uid])->first();
    }
}
