<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/26
 * Time: 15:49
 */

namespace app\controller;


use app\model\Special;
use core\Controller;
use core\Type;

class Jincai extends Controller
{

    private function _special_new()
    {
        $start = rand(1,10);
        $end = $start + rand(1,7);
        $data = [
            'title'=> randStr(10),
            'description'  => randStr(20),
            'out_banner'   => '/pic/'.randStr(10).'.jpg',
            'inner_banner' => '/pic/'.randStr(10).'.jpg',
            'start'  => date('y-m-d h:i:s',strtotime("+$start day")),
            'end'    => date("Y-m-d h:i:s",strtotime("+$end day")),
            'status' => 1,
            'create_time' => date('y-m-d h:i:s',time()),
        ];
        return $data;
    }

    public function tt()
    {
        $this->ajaxReturn(['1'=>12]);
    }

    /**
     * 新增专题
     */
    public function addSpecial()
    {
        $data = $this->_special_new();

        $special = new Special();
        $id = $special->add($data);
        $this->ajaxReturn(['status'=>Type::SUCCESS,'id'=>$id]);
    }

    /**
     * 删除专题
     * @param  int $id
     * @return array
     */
    public function delSpecial($id)
    {
        $special = new Special();
        $r = $special->delete($id);
        $result = ['status'=>Type::SUCCESS,'result'=>$r];
        return $result;
    }

    /**
     * 修改专题信息
     * @return array
     */
    public function updateSpecial()
    {
        $data = $this->_special_new();
        $conditions = ['id'=>8];

        $special = new Special();
        $r = $special->update($data,$conditions);
        $result = ['status'=>Type::SUCCESS,'result'=>$r];
        return $result;
    }

    /**
     * 专题列表：模糊查询
     * @return mixed
     */
    public function specials()
    {
        $conditions = [
          'title' => ['like','%15%'],
          'start' => ['>=',date('y-m-d h:i:s',strtotime("+1 day"))],
          'end'   => ['<=',date('y-m-d h:i:s',strtotime("+9 day"))],
          'status'=> ['=',1],
        ];
        $special = new Special();
        $specials = $special->queryAll('*', $conditions);
        $result = ['status'=>Type::SUCCESS,'result'=>$specials];
        $this->ajaxReturn($result);
    }




}