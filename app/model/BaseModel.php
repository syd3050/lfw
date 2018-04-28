<?php
namespace app\model;

use core\DB;
use core\Type;

class BaseModel
{
    private $_db;
    private $_table;

    public function __construct($config=[])
    {
        $db_config = isset($config['database']) ? $config['database'] : [];
        $this->_db = new DB($db_config);
        if(isset($config['table'])) {
            $this->_table = $config['table'];
        }else{
            $this->_table = get_class($this);
            $arr = explode('\\',$this->_table);
            $this->_table = end($arr);
        }
        $pos = strpos($this->_table,'Model');
        $pos &&  $this->_table = substr($this->_table,0,$pos);
    }

    public function add($data)
    {
        $insertId = $this->_db->insert($this->_table,$data);
        return $insertId;
    }

    public function query($id)
    {
        return $this->_db->query($this->_table,['id' => $id]);
    }

    public function queryAll($columns='*',$conditions=[])
    {
        return $this->_db->queryAll($this->_table,$columns,$conditions);
    }

    /**
     * 直接执行sql语句查询
     * @param $sql
     * @param $params
     * @return array
     */
    public function sqlQuery($sql, $params=[])
    {
        return $this->_db->sqlQuery($sql,$params);
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
        return $this->_db->delete($this->_table,$conditions);
    }


    public function update($params,$conditions)
    {
        return $this->_db->update($this->_table,$params,$conditions);
    }

    public function exec($sql,$params)
    {
        return $this->_db->execute($sql,$params);
    }

    public function send($url,$params)
    {
        return [Type::SUCCESS,''];
    }

}