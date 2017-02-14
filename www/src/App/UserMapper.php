<?php 
namespace App\Mapper;
use Spot\Mapper;

class UserMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid' => $uid])->first();
    }

    public function findLastChanged() {
        return $this->all()->order(["updated_at"=>"DESC"])->first();
    }

    public function findAll() {
        return $this->all()->order(['name' => 'DESC']);
    }

    public function getLastChanged() {
        return $this->all()->order(["updated_at"=>"DESC"])->first();
    }

    public function getById($uid)
    {
        return $this->where(['uid' => $uid])->first();
    }
    public function listAll() {
        return $this->all()->order(["email"=>"DESC"]);
    }
}
