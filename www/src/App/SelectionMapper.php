<?php 
namespace App\Mapper;
use Spot\Mapper;

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
            return $this->all()->where(['taken_quiz_id' =>$tq->uid, 'user_id' => $uid])->sort(['updated_at'=>"DESC"])->first();
        } else {
            return false;
        }
    }
    public function findAllFromTakenQuiz($uid, $tq) {
        if (isset($tq->uid)) {
            return $this->all()->where(['taken_quiz_id' =>$tq->uid, 'user_id' => $uid])->order(['updated_at'=>'DESC']);
        } else {
            return false;
        }
    }


    public function findRandomFromTakenQuiz($uid, $tq) {
        if (isset($tq['uid'])) {
            return $this->where(['taken_quiz_id' =>$tq['uid'], 'user_id' => $uid])->order(['updated_at'=>'DESC'])->first();
        } else {
            return false;
        }
    }

    public function getLastModifiedFromTakenQuiz($uid, $quid) {
        return $this->where(['taken_quiz_id'=>$quid, 'user_id'=> $uid])->sort(['updated_at'=>"DESC"])->first();
    }
    public function getLastModified() {
        return $this->where([])->sort(['updated_at'=>"DESC"])->first();
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
