<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/28
 * Time: 16:13
 */

namespace app\model;


use core\DB;
use core\Exception;
use core\Log;
use core\Type;

class User_item extends BaseModel {

    private $_value;
    private $_uid;
    private $_tid;
    private $_id;
    //单用户总限额
    private $_single_limit = 0;

    public function tz($data)
    {
      $this->_single_limit = 200;
      $this->_value = $data['value'];
      $this->_uid   = $data['uid'];
      $this->_tid   = $data['tid'];

      $this->_db->beginTransaction();
      try{
        $result = $this->_update_user_item();
        if(!$result) {
          throw new Exception('投注失败，已超过上限');
        }
        //向Java后台发送扣积分请求
        $result = $this->_request();
        $this->_db->commit();
      }catch (Exception $e) {
        $this->_db->rollBack();
        $result = [Type::FAIL,$e->getMessage()];
      }
      return $result;
    }

    /**
     * 更新用户对该项的投注信息
     * @return int|string
     */
    public function _update_user_item()
    {
      $sql = "SELECT 1 FROM jc_user_item WHERE uid=:uid AND tid=:tid";
      $r = $this->_db->execute($sql,['uid'=>$this->_uid,'tid'=>$this->_tid]);
      if(!$r)
        return $this->_add_user_item(); //该用户第一次投注，须新增记录
      return $this->_update();            //非第一次投注，更新记录
    }

    /**
     * 新增用户投递选项的记录
     * @return int|string 返回操作结果
     */
    public function _add_user_item()
    {
      try{
        $this->_id = $this->add([
          'uid'      => $this->_uid,
          'tid'      => $this->_tid,
          'total'    => $this->_value,
          'left_all' => $this->_single_limit - $this->_value,
          'create_time' => date('y-m-d h:i:s',time()),
        ]);
      }catch (Exception $e) {
        //请求延迟导致，相当于该用户第二次请求插入(uid,tid重复)
        if($e->getCode() == '23000') {
          //更新个人投注总剩余额度
          return $this->_update();
        }
        Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
      }
      return $this->_id;
    }

    /**
     * 更新个人投注项信息以及该选项已投注额度
     * @return int
     */
    private function _update()
    {
      $r = false;
      try{
        //1.更新个人投注总剩余额度
        $sql = "UPDATE jc_user_item SET total = total+:v,left_all = left_all-:v ".
            "WHERE left_all>=:v AND uid=:uid AND tid=:tid";
        $r = $this->_db->execute($sql,['v'=>$this->_value,'uid'=>$this->_uid,'tid'=>$this->_tid]);
        //2.更新该选项总剩余投注额度
        $sql = "UPDATE jc_item SET left_all = left_all-:v WHERE left_all >= :v AND tid=:tid";
        $r && $r = $this->_db->execute($sql,['v'=>$this->_value,'tid'=>$this->_tid]);
      }catch (Exception $e) {
        Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
      }
      return $r;
    }

    private function _updateMain()
    {
      //向Java请求积分减项
      list($success,$msg) = $this->_request();
      //Java后台不允许本次减项操作
      if(!$success)
        throw new Exception($msg);
      return [Type::SUCCESS,''];
    }

    private function _request()
    {
      $params = [
        'uid'  => $this->_uid,
        'value'=> intval('-'.$this->_value),
      ];
      $r = $this->send('',$params);
      //Java后台不允许本次减项操作
      if(!$r['status'])
        throw new Exception($r['msg']);
    }
}