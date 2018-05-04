<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/26
 * Time: 15:49
 */

namespace app\controller;


use app\model\Project;
use app\model\Special;
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
      'start'  => date('Y-m-d H:i:s',strtotime("+$start day")),
      'end'    => date("Y-m-d H:i:s",strtotime("+$end day")),
      'status' => $status,
      'create_time' => date('Y-m-d H:i:s',time()),
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
    $r = $this->Special->delete($id);
    $result = ['status'=>Type::SUCCESS,'result'=>$r];
    return $result;
  }

  /**
   * 修改专题信息
   * @return array
   */
  public function updateSpecial()
  {
    //$data = $this->_special_new();
    $data = [];
    $conditions = ['id'=>6];
    $data['status'] = Special::S_ENABLE;
    $r = $this->Special->update($data,$conditions);
    $result = ['status'=>Type::SUCCESS,'result'=>$r];
    return $result;
  }

  /**
   * 专题列表：模糊查询
   * @return mixed
   */
  public function specials() {
    $params = [
      'status'   => Special::S_ENABLE,
      'pageNo'   => 1,
      'pageSize' => 5,
      'title'    => '15',
      'start'    => date('Y-m-d H:i:s',time()),
      'end'      => date("Y-m-d H:i:s",strtotime("+6 day")),
    ];
    $conditions = [];
    isset($params['status']) && $conditions['status'] = ['=',$params['status']];
    isset($params['title'])  && $conditions['title']  = ['like',"%".$params['title']."%"];
    isset($params['start'])  && $conditions['start']  = ['>=',$params['start']];
    isset($params['end'])    && $conditions['end']    = ['<=',$params['end']];
    isset($params['pageNo']) && $conditions['pageNo'] = ['=',$params['pageNo']];
    isset($params['pageSize']) && $conditions['pageSize'] = ['=',$params['pageSize']];

    $specials = $this->Special->lists('*',$conditions);
    $result = ['status'=>Type::SUCCESS,'result'=>$specials];
    $this->ajaxReturn($result);
  }

  /**
   * 专题详情
   * @param $id
   */
  public function special($id) {
    $special = $this->Special->detail($id);
    $this->ajaxReturn($special);
  }

  private function _project_new() {
    $start = rand(1,10);
    $end = $start + rand(1,7);
    $sid = rand(1,20);
    $status = rand(0,1);
    $count = 3;
    $data = [
      'title'     => randStr(10),
      'addition'  => randStr(20),
      'start'     => date('Y-m-d H:i:s',strtotime("+$start day")),
      'end'       => date("Y-m-d H:i:s",strtotime("+$end day")),
      'sid'       => $sid,
      'status'    => $status,
      'rule_title'   => randStr(20),
      'rule_content' => randStr(30),
      'create_time'  => date('Y-m-d H:i:s',time()),
      'update_time'  => date('Y-m-d H:i:s',time()),
      'operator'     => 1,
    ];
    while ($count) {
        $all_max = rand(1000,10000);
        $data['items'][] = [
          'name' => randStr(10),
          'rate' => rand(1,100)/100,
          'single_max'  => rand(100,1000),
          'all_max'     => $all_max,
          'left_all'    => $all_max,
          'create_time' => date('Y-m-d H:i:s',time()),
        ];
        $count--;
    }
    return $data;
  }

  /**
   * 新增项目及选项信息
   */
  public function addProject()
  {
    $data = $this->_project_new();
    $id = $this->Project->add($data);
    $status = Type::FAIL;
    $id && $status = Type::SUCCESS;
    $this->ajaxReturn(['status'=>$status,'id'=>$id]);
  }

  /**
   * 更新项目及项目下的选项信息
   */
  public function updateProject()
  {
    $data = $this->_project_new();
    $data['id'] = 5;
    $i = 3;
    foreach ($data['items'] as $k=>$item) {
      $data['items'][$k]['id'] = $i;
      $i++;
      unset($data['items'][$k]['create_time']);
    }
    unset($data['create_time']);
    $id = $data['id'];
    unset($data['id']);
    $r = $this->Project->update($data,['id'=>$id]);
    $status = Type::FAIL;
    $r && $status = Type::SUCCESS;
    $this->ajaxReturn(['status'=>$status]);
  }

  /**
   * 删除项目及对应的选项信息
   * @param int $id 项目ID
   * @return array
   */
  public function delProject($id) {
    $r = $this->Project->delete($id);
    $result = ['status'=>Type::SUCCESS,'result'=>$r];
    return $result;
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

  public function updateBc()
  {
    $this->ajaxReturn($this->Contest->updateBc());
  }

  /**
   * 查询项目列表信息
   */
  public function projects() {
    $params = [
      'status'   => Project::ENABLE,
      'pageNo'   => 1,
      'pageSize' => 5,
      'title'    => '15',
      'start'    => date('Y-m-d H:i:s',time()),
      'end'      => date("Y-m-d H:i:s",strtotime("+5 day")),
    ];
    $conditions = [];
    isset($params['status']) && $conditions['status'] = ['=',$params['status']];
    isset($params['title'])  && $conditions['title']  = ['like',"%".$params['title']."%"];
    isset($params['start'])  && $conditions['start']  = ['>=',$params['start']];
    isset($params['end'])    && $conditions['end']    = ['<=',$params['end']];
    isset($params['pageNo']) && $conditions['pageNo'] = ['=',$params['pageNo']];
    isset($params['pageSize']) && $conditions['pageSize'] = ['=',$params['pageSize']];

    $specials = $this->Project->lists('*',$conditions);
    $result = ['status'=>Type::SUCCESS,'result'=>$specials];
    $this->ajaxReturn($result);
  }

  /**
   * 删除选项
   * @param $id
   * @return array
   */
  public function deleteItem($id)
  {
    $r = $this->Item->delete($id);
    $result = ['status'=>Type::SUCCESS,'result'=>$r];
    return $result;
  }


}