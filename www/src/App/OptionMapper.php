<?php 
namespace App\Mapper;
use Spot\Mapper;

class OptionMapper extends ElementMapper
{
    public function listFromQuestion($q) {
        $start = $this->getMapper('App\Element')->findById($q->owned->start_id);
        return $this->getMapper('App\Element')->listFrom($start);
    }
}
