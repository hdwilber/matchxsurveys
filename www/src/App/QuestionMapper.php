<?php 
namespace App\Mapper;
use Spot\Mapper;
use Spot\Entity;
use Spot\Entity\Collection;

class QuestionMapper extends ElementMapper
{
    public function listFromGroup($g) {
        $start = $this->getMapper('App\Element')->findById($g->owned->start_id);
        return $this->getMapper('App\Element')->listFrom($start);
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
