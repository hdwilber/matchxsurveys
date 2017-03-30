<?php 
namespace App\Mapper;
use Spot\Mapper;
use Spot\Entity\Collection;
use App\Element;


class QuestionaryMapper extends ElementMapper
{
    public function findAllSpecific() {
        $qs = $this->getMapper('App\Element')->all()->where(['data_type'=> 'questionary'])->with(['owned']);
        $wq = new Collection();
        foreach($qs as $key=>$q) {
            $r = $this->getMapper('App\Element')->findById($q->owned->start_id, ['children']);
            $q->extra = $r->children->entities();
            $wq->add($q);
        }
        return $wq;
    }
    public function findOneSpecific($id) {
        $q = $this->getMapper('App\Element')->findById($id, ['label','owned']);
        $r = $this->getMapper('App\Element')->findById($q->owned->group_id, ['children']);
        $q->extra = $r->children->entities();
        return $q;
    }

    public function findNextQuestion($logger, $quest, $qid) {
        if ($qid == null) {
            if ($quest->first_id == null) {
                return false;
            } else {
                $qqq= $this->getMapper("App\Element")->findById($quest->first_id);
                //$logger->addInfo("This is the first", $qqq->toArray());

                while($qqq->data_type != "question") {
                    if ($qqq->first_id != null) {
                        $qqq= $this->getMapper("App\Element")->findById($qqq->first_id);
                    }
                    else {
                        //If it has not next
                        if($qqq->next_id == null) {
                            $logger->addInfo("Going back to parent");
                            $qqq = $this->getMapper("App\Element")->findById($qqq->parent_id);
                            if($qqq->id == $quest->id) {
                                $qqq = false;
                                break;
                            }
                        } else {
                            $logger->addInfo("Next element to current parent");
                            $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                        }
                        if ($qqq === false) {
                            break;
                        }
                    }
                }
                if ($qqq === false) {
                    $logger->addInfo("We have reached to end");
                    return $qqq;
                }
                return $qqq;
            }
        }
        else {
            $nQ = $this->getMapper("App\Element")->findById($qid);
            $qqq= $this->getMapper("App\Element")->findById($nQ->next_id);
            $logger->addInfo($qqq);
            while($qqq->data_type != "question") {
                if($qqq->next_id == null) {
                    $qqq = $this->getMapper("App\Element")->findById($qqq->parent_id);
                    if($qqq->id == $quest->id) {
                        $qqq = false;
                        break;
                    }
                } else {
                    $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                }
                if ($qqq === false) {
                    break;
                }
            }
            if ($qqq === false) {
                $logger->addInfo("We have reached to the end");
            }
            return $qqq;
        }
    }
}
