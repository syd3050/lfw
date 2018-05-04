<?php
namespace app\model;


use core\Exception;
use core\Log;

class Project extends BaseModel {

  const DISABLE = 0;
  const ENABLE = 1;

  const WAIT = 0; //未开始
  const ON   = 1; //可下注
  const END  = 2; //已发奖
  const OFF  = 3; //待开奖

  public function __construct(array $config = []) {
    $this->prefix = 'jc_';
    parent::__construct($config);
  }

  /**
   * 新增项目及选项信息
   * @param array $data 项目信息，包含项目下相关选项信息
   * @return int|string
   */
  public function add($data)
  {
    $items = $data['items'];
    unset($data['items']);
    $id = -1;
    $this->db->beginTransaction();
    try{
      //项目主表信息
      $id = $this->db->insert($this->prefix.'project',$data);
      foreach ($items as $k=>$v) {
        $items[$k]['pid'] = $id;
      }
      //项目包含的选项信息
      $this->db->insertBatch($this->prefix.'item',$items);
      $this->db->commit();
    }catch (Exception $e) {
      Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
      $this->db->rollBack();
      return $id;
    }
    return $id;
  }

  /**
   * 更新项目及项目下的选项信息
   * @param $parameters
   * @param $condition
   * @return bool
   */
  public function update($parameters,$condition)
  {
    $items = isset($parameters['items']) ? $parameters['items'] : [];
    $ids = [];
    foreach ($items as $k=>$item) {
      $ids[] = $item['id'];
      unset($items[$k]['id']);
    }
    unset($parameters['items']);
    $this->db->beginTransaction();
    try{
      $this->db->update($this->prefix.'project',$parameters, $condition);
      $items && $this->db->updateBatch($this->prefix.'item',$items,['id',$ids]);
      $this->db->commit();
    }catch (Exception $e) {
      Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
      $this->db->rollBack();
      return false;
    }
    return true;
  }

  /**
   * 删除项目及对应的选项信息
   * @param int $id 项目ID
   * @return bool
   */
  public function delete($id) {
    $this->db->beginTransaction();
    try{
      $this->db->delete($this->prefix.'project',['id'=>['=',$id]]);
      $this->db->delete($this->prefix.'item',['pid'=>['=',$id]]);
      $this->db->commit();
    }catch (Exception $e) {
      Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
      $this->db->rollBack();
      return false;
    }
    return true;
  }
}