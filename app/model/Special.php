<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3
 * Time: 19:43
 */

namespace app\model;


use core\Exception;
use core\Log;
use core\Type;

class Special extends BaseModel {

  const S_DISABLE = 0; //已禁用
  const S_ENABLE  = 1; //已启用
  const S_PAST    = 2; //已过期

  const P_WAIT = 0; //未开始
  const P_ON   = 1; //可下注
  const P_END  = 2; //已发奖
  const P_OFF  = 3; //待开奖

  public function __construct(array $config = []) {
    $this->prefix = 'jc_';
    parent::__construct($config);
  }

  /**
   * 获取专题详情信息，包含专题下的所有项目信息
   * @param int $id 专题ID
   * @return array
   */
  public function detail($id) {
    $special = $this->info($id);
    $projects = $this->db->queryAll($this->prefix.'project','*',
      ['sid'=>['=',$id],'status'=>['>=',Type::D_ENABLE]]
    );
    foreach ($projects as $k=>$project) {
      $projects[$k]['status'] = $this->_getStatus($project);
    }
    $special['project'] = $projects;
    return $special;
  }

  /**
   * 删除专题
   * @param int $id 项目ID
   * @return bool
   */
  public function delete($id) {
    $this->db->beginTransaction();
    try{
      $this->db->delete($this->prefix.'special',['id' =>['=',$id]]);
      $this->db->delete($this->prefix.'project',['sid'=>['=',$id]]);
      $this->db->delete($this->prefix.'item',['pid'=>['=',$id]]);
      $this->db->commit();
    }catch (Exception $e) {
      Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
      $this->db->rollBack();
      return false;
    }
    return true;
  }

  /**
   * 获取专题主表信息
   * @param int $id 专题ID
   * @return array
   */
  public function info($id) {
    return $this->db->query($this->prefix.'special',['id' => $id]);
  }

  /**
   * 更新专题信息
   * @param array $params 更新字段
   * @param array $conditions 条件
   * @return int
   */
  public function update($params, $conditions) {
    return $this->db->update($this->prefix.'special',$params,$conditions);
  }

  /**
   * 获取项目当前状态：未开始，可下注，待开奖，已发奖
   * @param  array $project 项目信息
   * @return int 项目状态
   */
  private function _getStatus($project) {
    //所有不是已发奖状态的，都需要根据下注时间段判断当前状态
    if($project['status'] != self::P_END) {
      $current = strtotime(date('Y-m-d H:i:s',time()));
      $start = strtotime($project['start']);
      $end = strtotime($project['end']);
      $project['status'] = $start <= $current ? ($current < $end ? self::P_ON : self::P_OFF) : self::P_WAIT;
    }
    return $project['status'];
  }
}