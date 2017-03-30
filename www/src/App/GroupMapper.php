<?php 
namespace App\Mapper;
use Spot\Mapper;
use Spot\Entity;
use Spot\Entity\Collection;

class GroupMapper extends ElementMapper
{
    public function listFromQuestionary($q) {
        $g = $this->findById($q->group_id);
        return $this->listFrom($g);
    }
    //public function findLastModifiedFromStep($step) {
        //return $this->where(['step_id' => $step['uid']])->order(['updated_at'=>"DESC"])->first();
    //}
    //public function findAllFromStep($step) {
        //if (isset($step['uid'])) {
            //return $this->all()->where(['step_id' => $step['uid']])->with(['options']);
        //} else {
            //return false;
        //}
    //}

    //public function findAllSortedFromStep($s) {
        //$next = $this->where(['uid'=>$s->start_id])->with(['options'])->first();;
        //$arr = [];
        //if ($next !== false) {
            //array_push($arr, $next);
            //while($next->next_id != null) {
                //$next = $this->where(['uid'=>$next->next_id])->with(['options'])->first();
                //array_push($arr, $next);
            //}
        //}
        //return new Collection($arr);
    //}
}
