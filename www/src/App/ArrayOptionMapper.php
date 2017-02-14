<?php 
namespace App\Mapper;
use Spot\Mapper;

class ArrayOptionMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }

    public function getOne($uid)
    {
        return $this->where(['uid' => $uid]);
    }
}
