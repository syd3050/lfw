<?php
namespace app\model;

use core\DB;
use core\Type;

class BaseModel {
  protected $prefix = '';
  protected $db;
  protected $table;

  public function __construct($config=[])
  {
    $db_config = isset($config['database']) ? $config['database'] : [];
    $this->db = new DB($db_config);
    isset($config['prefix']) && $this->prefix = $config['prefix'];
    if(isset($config['table'])) {
      $this->table = $this->prefix.$config['table'];
    }else{
      $this->table = get_class($this);
      $arr = explode('\\',$this->table);
      $this->table = $this->prefix.end($arr);
    }
    $pos = strpos($this->table,'Model');
    $pos &&  $this->table = substr($this->table,0,$pos);
  }

  public function add($data)
  {
    $insertId = $this->db->insert($this->table,$data);
    return $insertId;
  }

  public function addBatch($data)
  {
    return $this->db->insertBatch($this->table,$data);
  }

  public function query($id)
  {
    return $this->db->query($this->table,['id' => $id]);
  }

  public function lists($columns='*',$conditions=[])
  {
    return $this->db->queryAll($this->table,$columns,$conditions);
  }

  /**
   * 直接执行sql语句查询
   * @param $sql
   * @param $params
   * @return array
   */
  public function sqlQuery($sql, $params=[])
  {
    return $this->db->sqlQuery($sql,$params);
  }

  /**
   * 删除数据
   * @param  string|array $conditions
   * @return int
   */
  public function delete($conditions)
  {
    //支持delete($id)的方式直接删除
    is_array($conditions) || $conditions = ['id'=>['=',$conditions]];
    return $this->db->delete($this->table,$conditions);
  }


  public function update($params,$conditions)
  {
    return $this->db->update($this->table,$params,$conditions);
  }

  public function exec($sql,$params)
  {
    return $this->db->execute($sql,$params);
  }

  public function send($url,$params)
  {
    return [Type::SUCCESS,''];
  }

}