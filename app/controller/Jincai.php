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
        $single_limit = 100;
        $key = $data['uid'].'_'.$data['tid'];
        $exception = false;
        try{
            //该用户头一次投注
            $params = [
                'uid'      => $data['uid'],
                'tid'      => $data['tid'],
                'total'    => $data['value'],
                'left_all' => $single_limit - $data['value'],
                'create_time' => date('y-m-d h:i:s',time()),
            ];
            $this->User_item->add($params);
        }catch (Exception $e) {
            //已经是第二次投注该选项(uid,tid重复)
            if($e->getCode() == '23000') {
                //更新个人投注总剩余额度
                $sql = "UPDATE user_item SET ".
                    " total=total+:v,left_all=left_all-:v WHERE left_all>=:v";
                try{
                    $r = $this->User_item->exec($sql,['v'=>$data['value']]);
                }catch (Exception $e2) {
                    Log::error(__CLASS__."::".__FUNCTION__.",code:".$e2->getCode().',msg:'.$e2->getMessage());
                    $this->ajaxReturn(['msg'=>'投注失败，数据库异常']);
                }
                if(empty($r))
                    $this->ajaxReturn(['status'=>Type::FAIL,'msg'=>'投注失败，已超过上限']);
            }else{
                Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
                $this->ajaxReturn(['msg'=>'投注失败，数据库异常']);
            }
        }
        $db = new DB();
        $db->beginTransaction();
        try{
            //更新该选项总剩余投注额度
            $sql2 = "UPDATE item SET left_all=left_all-:v WHERE left_all>:v";
            $r2 = $db->execute($sql2,['v'=>$data['value']]);
            if(empty($r2))
                throw new Exception('该选项投注已达上限');
            //向Java请求积分减项
            //.....
            $r3 = $this->send();
            if(!$r3['status'])
                throw new Exception($r3['msg']);
            //在积分消费记录中插入本次投注

        }catch (Exception $e) {

        }
    }

    public function rt()
    {
        $k = '1_1';
        $limit = 200;
        $r = Cache::setNx($k,$limit);
        $this->ajaxReturn(['status'=>$r]);
    }
}