<?php 
namespace App\Mapper;
use Spot\Mapper;
use App\Logic;

class MatchLogicMapper extends Mapper
{

    public function findAllFromParent($parent) {
        return $this->all()->where(['parent_id' => $parent->uid]);
    }

    public function findById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }
    public function findLastModifiedFromLogic($logic) {
        return $this->where(['logic_id'=>$logic['uid']]->first());
    }
    public function findAllFromLogic($logic){ 
        return $this->where(['logic_id'=>$logic[uid]])->all();
    }
}
