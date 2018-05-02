<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/26
 * Time: 15:49
 */

namespace app\controller;


use core\Cache;
use core\Controller;
use core\DB;
use core\Exception;
use core\Log;
use core\Type;

class Jincai extends Controller
{

    private function _special_new()
    {
        $start = rand(1,10);
        $end = $start + rand(1,7);
        $status = rand(0,1);
        $data = [
            'title'=> randStr(10),
            'description'  => randStr(20),
            'out_banner'   => '/pic/'.randStr(10).'.jpg',
            'inner_banner' => '/pic/'.randStr(10).'.jpg',
            'start'  => date('y-m-d h:i:s',strtotime("+$start day")),
            'end'    => date("Y-m-d h:i:s",strtotime("+$end day")),
            'status' => $status,
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

        $id = $this->SpecialModel->add($data);
        $this->ajaxReturn(['status'=>Type::SUCCESS,'id'=>$id]);
    }

    /**
     * 删除专题
     * @param  int $id
     * @return array
     */
    public function delSpecial($id)
    {
        $r = $this->SpecialModel->delete($id);
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
        $r = $this->SpecialModel->update($data,$conditions);
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
         // 'title' => ['like','%15%'],
         // 'start' => ['>=',date('y-m-d h:i:s',strtotime("+1 day"))],
          //'end'   => ['<=',date('y-m-d h:i:s',strtotime("+9 day"))],
          //'status'=> ['=',1],
        ];
        $specials = $this->SpecialModel->queryAll('*', $conditions);
        $result = ['status'=>Type::SUCCESS,'result'=>$specials];
        $this->ajaxReturn($result);
    }

    private function _project_new()
    {
        $start = rand(1,10);
        $end = $start + rand(1,7);
        $sid = rand(1,20);
        $status = rand(0,1);
        $data = [
            'title'     => randStr(10),
            'addition'  => randStr(20),
            'start'     => date('y-m-d h:i:s',strtotime("+$start day")),
            'end'       => date("Y-m-d h:i:s",strtotime("+$end day")),
            'sid'       => $sid,
            'f_title'   => randStr(20),
            'f_content' => randStr(30),
            'status'    => $status,
            'create_time' => date('y-m-d h:i:s',time()),
        ];
        return $data;
    }

    public function addProject()
    {
        $data = $this->_project_new();
        $id = $this->ProjectModel->add($data);
        $this->ajaxReturn(['status'=>Type::SUCCESS,'id'=>$id]);
    }

    private function _user_item_new()
    {
        $start = rand(1,10);
        $uid = rand(1,10);
        $tid = rand(1,10);
        $total = rand(10,100);
        $end = $start + rand(1,7);
        $data = [
            'uid'=>1,
            'tid'=>1,
            'value' => 30,
        ];
        return $data;
    }

    public function touzhu()
    {
        $data = $this->_user_item_new();
        //单用户总限额
        $single_limit = 100;
        if($data['value'] > $single_limit)
            $this->ajaxReturn(['status'=>Type::FAIL,'msg'=>'超过个人允许投注限额']);
        list($r,$msg) = $this->User_item->tz($data);
        $this->ajaxReturn(['status'=>$r,'msg'=>$msg]);
    }

    public function rt()
    {
        $k = '1_1';
        $limit = 200;
        $r = Cache::setNx($k,$limit);
        $this->ajaxReturn(['status'=>$r]);
    }
}