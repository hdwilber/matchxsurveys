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

    public function findNextQuestion($logger, $quest, $tq, $qid) {
        if ($qid == null) {
            if ($quest->first_id == null) {
                return false;
            } else {
                $qqq= $this->getMapper("App\Element")->findById($quest->first_id);
                $qflag = false;

                $vis = $this->getMapper("App\Logic")->checkVisibility($logger, $qqq, $tq);
                while(!$qflag) {
                    $logger->addInfo("Current Visible: ", ['vis' => $vis]);
                    if ($vis) {
                        if ($qqq->data_type == "question") {
                            $qflag = true;
                        } else {
                            if ($qqq->first_id != null) {
                                $qqq = $this->getMapper("App\Element")->findById($qqq->first_id);
                            }
                            else {
                                //If it has not next
                                if($qqq->next_id == null) {
                                    $logger->addInfo("Going back to parent");
                                    $qqq = $this->getMapper("App\Element")->findById($qqq->parent_id);
                                    if($qqq->id == $quest->id) {
                                        $qqq = false;
                                        break;
                                    } else {
                                        $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                                        
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
                    } else {
                        if ($qqq->next_id != null) {
                            $qqq= $this->getMapper("App\Element")->findById($qqq->next_id);
                        } else {
                            //If it has not next
                            if($qqq->next_id == null) {
                                $logger->addInfo("Going back to parent");
                                $qqq = $this->getMapper("App\Element")->findById($qqq->parent_id);
                                if($qqq->id == $quest->id) {
                                    $qqq = false;
                                    break;
                                } else {
                                    $qqq =$this->getMapper("App\Element")->findById($qqq->next_id);
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
                    $vis = $this->getMapper("App\Logic")->checkVisibility($logger, $qqq, $tq);
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
            $qqq = null;
            if ($nQ->next_id != null) {
                $qqq= $this->getMapper("App\Element")->findById($nQ->next_id);
            } else {
                $qqq = $this->getMapper("App\Element")->findById($nQ->parent_id);
                $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
            }
            $qflag = false;
            $vis = $this->getMapper("App\Logic")->checkVisibility($logger, $qqq, $tq);
            while(!$qflag) {
                $logger->addInfo("Current Visible: ", ['vis' => $vis]);
                $logger->addInfo("SECOND PART FROM ORIGIN");
                if ($vis) {
                    $logger->addInfo("2nd. Is Visible");
                    if ($qqq->data_type == "question") {
                        $qflag = true;
                    } else {
                        if ($qqq->first_id != null) {
                            $qqq = $this->getMapper("App\Element")->findById($qqq->first_id);
                            $logger->addInfo("2nd Start");
                        }
                        else {
                            $logger->addInfo("2nd Next");
                            //If it has not next
                            if($qqq->next_id == null) {
                                $logger->addInfo("Going back to parent");
                                $qqq = $this->getMapper("App\Element")->findById($qqq->parent_id);
                                if($qqq->id == $quest->id) {
                                    $qqq = false;
                                    break;
                                } else {
                                    $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                                    
                                }
                            } else {
                                $logger->addInfo("Next element to current parent");
                                $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                            }
                            if ($qqq === false) {
                                break;
                            }
                        }
                        //$vis = $this->getMapper("App\Logic")->checkVisibility($logger, $qqq, $tq);
                    }
                } else {
                    $logger->addInfo("2nd. Is Not Visible");
                    if ($qqq->next_id != null) {
                        $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                    } else {
                        //If it has not next
                        if($qqq->next_id == null) {
                            $logger->addInfo("Going back to parent");
                            $qqq = $this->getMapper("App\Element")->findById($qqq->parent_id);
                            if($qqq->id == $quest->id) {
                                $qqq = false;
                                break;
                            } else {
                                $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                            }
                        } else {
                            $logger->addInfo("Next element");
                            $qqq = $this->getMapper("App\Element")->findById($qqq->next_id);
                        }
                        if ($qqq === false) {
                            break;
                        }
                    }
                }
                $vis = $this->getMapper("App\Logic")->checkVisibility($logger, $qqq, $tq);
            }
            if ($qqq === false) {
                $logger->addInfo("We have reached to the end");
            }
            return $qqq;
        }
    }
}
