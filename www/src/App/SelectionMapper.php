<?php 
namespace App\Mapper;
use Spot\Mapper;
use Spot\Entity\Collection;

class SelectionMapper extends Mapper
{
    public function findByQuestion($takenQuiz, $question) {
        return $this->where(['taken_quiz_id'=>$takenQuiz->uid, 'question_id'=>$question->uid])->first();
    }
    public function findById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }
    public function findLastModifiedFromTakenQuiz($uid, $tq) {
        if (isset($tq->uid)) {
            return $this->where(['taken_quiz_id' =>$tq->uid, 'user_id' => $uid])->order(['updated_at'=>"DESC"])->first();
        } else {
            return false;
        }
    }
    public function findAllFromTakenQuiz($uid, $tq) {
        if (isset($tq->uid)) {
            return $this->all()->where(['taken_quiz_id' =>$tq->uid, 'user_id' => $uid])->order(['updated_at'=>'DESC'])->with(['question', 'option']);
        } else {
            return false;
        }
    }

    public function findAllSortedFromTakenQuiz($uid, $tq) {
        $next = $this->where(['taken_quiz_id' => $tq->uid, 'user_id' => $uid, 'prev_id' => null])->with(['question', 'option'])->first();

        $arr =[];
        if ($next !== false) {
            array_push($arr, $next);
            while($next->next_id != null) {
                $next = $this->where(['uid'=>$next->next_id])->with(['question', 'option'])->first();
                array_push($arr, $next);
            }
        }
        return new Collection($arr);
    }


    public function findRandomFromTakenQuiz($uid, $tq) {
        if (isset($tq['uid'])) {
            return $this->where(['taken_quiz_id' =>$tq['uid'], 'user_id' => $uid])->order(['updated_at'=>'DESC'])->first();
        } else {
            return false;
        }
    }

    public function getLastModifiedFromTakenQuiz($uid, $quid) {
        return $this->where(['taken_quiz_id'=>$quid, 'user_id'=> $uid])->order(['updated_at'=>"DESC"])->first();
    }
    public function getLastModified() {
        return $this->where([])->order(['updated_at'=>"DESC"])->first();
    }
    public function getById($uid) {
        return $this->where(['uid'=>$uid])->first();
    }
    public function getByIdFromTakenQuiz($uid, $quid) {
        return $this->where(['taken_quiz_id' => $quid, 'uid'=>$uid])->first();
    }
    public function getSelection($uid)
    {
        return $this->where(['uid' => $uid])
            ->with(["option", "author", "question"]);
    }
    public function listAllFromTakenQuiz($user_id, $quid) {
        return $this->all()->where(['user_id' => $user_id, 'taken_quiz_id'=>$quid])->with(["option", "question"]);
    }
    public function getAllFromTakenQuiz($takenQuiz) {
        return $this->all()->where(['user_id' => $takenQuiz['user_id'], 'taken_quiz_id'=>$takenQuiz['uid']])->with(["option", "question"]);
    }
    public function getLastFromTakenQuiz($takenQuiz) {
        return $this->all()->where(['user_id' => $takenQuiz['user_id'], 'taken_quiz_id' => $takenQuiz['uid']])->order(['updated_at'=>'DESC'])->first();
    }
}
