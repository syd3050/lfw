<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/2
 * Time: 14:06
 */

namespace app\model;


use core\Exception;
use core\Log;

class Contest extends BaseModel
{
    public function addProject($data)
    {
        $items = $data['items'];
        unset($data['items']);
        $id = false;
        $this->_db->beginTransaction();
        try{
            $id = $this->_db->insert('project',$data);
            foreach ($items as $k=>$v) {
                $items[$k]['pid'] = $id;
            }
            $this->_db->insertBatch('item',$items);
            $this->_db->commit();
        }catch (Exception $e) {
            Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
            $this->_db->rollBack();
            return $id;
        }
        return $id;
    }

    public function updateProject($data)
    {
        $items = isset($data['items']) ? $data['items'] : [];
        $ids = [];
        $id = $data['id'];
        foreach ($items as $k=>$item) {
            $ids[] = $item['id'];
            unset($items[$k]['id']);
        }
        unset($data['items']);
        unset($data['id']);
        $this->_db->beginTransaction();
        try{
            $this->_db->update('project',$data, ['id'=>$id]);
            $this->_db->updateBatch('item',$items,['id',$ids]);
            $this->_db->commit();
        }catch (Exception $e) {
            Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
            $this->_db->rollBack();
            return false;
        }
        return true;
    }

    public function updateBc()
    {
        return $this->_db->updateBatch('item',[
           ['name'=>'ccc','single_max'=>'123'],
           ['name'=>'sss','single_max'=>'256'],
        ], ['id',[1,2]]);
    }
}