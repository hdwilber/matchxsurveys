<?php 
namespace App\Mapper;
use Spot\Mapper;

use App\Questionary;
use Spot\Entity;
use Spot\Entity\Collection;

class StepMapper extends Mapper
{
    public function findById($uid) {
        return $this->where(['uid'=> $uid])->first();
    }

    public function findLastModified() {
        return $this->all()->order(['updated_at' => 'DESC'])->first();
    }

    public function findLastModifiedFromQuestionary($questionary) {
        if (isset($questionary['uid'])) {
            return $this->where(['questionary_id' => $questionary['uid']])->order(['updated_at' => 'DESC'])->first();
        } else {
            return false;
        }
    }
    public function findAllFromQuestionary($questionary)
    {
        if (isset($questionary['uid'])) {
            return $this->all()->where(['questionary_id'=>$questionary['uid']]);
        } else {
            return false;
        } 
    } 

    public function findAllSortedFromQuestionary($questionary) {
        $next = $this->where(['questionary_id'=>$questionary->uid, 'uid'=>$questionary->start_id])->first();;
        $arr =[];
        if ($next !== false) {
            array_push($arr, $next);
            while($next->next_id != null) {
                $next = $this->where(['uid'=>$next->next_id])->first();
                array_push($arr, $next);
            }
        }
        return new Collection($arr);
    }

    public function getStartFromQuestionary($questionary) {
        if (isset($questionary['start_id'])) {
            return $this->where(['uid' => $questionary['start_id'], 'questionary_id' => $questionary['uid']])
                ->with(["author", "questions"])->first();
        } else {
            return false;
        }
    }

    public function getLastModified() {
        return $this->all()->order(['updated_at' => 'DESC'])->first();
    }
    public function getLastModifiedFromQuestionary($quid) {
        return $this->where(['questionary_id' => $quid])->order(['updated_at' => 'DESC'])->first();
    }
    public function listAll()
    {
        return $this->all()->order(['sort' => 'DESC']);
    }
    public function getFirstFromQuestionary($quid) {
        return $this->where(['questionary_id'=>$quid])->order(['sort' => 'DESC'])->first();
    }
    public function listAllFromQuestionary($quid)
    {
        return $this->all()->where(['questionary_id'=>$quid])->order(['sort' => 'DESC']);
    }
}
