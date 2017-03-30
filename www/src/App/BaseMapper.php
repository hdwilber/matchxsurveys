<?php 
namespace App\Mapper;
use Spot\Mapper;

class BaseMapper extends Mapper
{
    public function findById($id, $with = []) {
        return $this->where(['id' => $id])->with($with)->first();
    }
    public function findAll($where=[], $with = []) {
        return $this->all()->where($where)->with($with);
    }
    public function findLastModified() {
        return $this->all()->order(["updated_at"=>"DESC"])->first();
    }
}
