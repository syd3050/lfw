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

class User_item extends BaseModel
{

    private $_value;
    private $_uid;
    private $_tid;
    private $_id;

    public function tz($data)
    {
        $single_limit = 200;
        $this->_value = $data['value'];
        $this->_uid   = $data['uid'];
        $this->_tid   = $data['tid'];
        //投注前不需要请求Java后台取用户积分，在前面进入投注页时已经取过一次积分，实际上也无法实时精确判断积分余额是否足够
        $sql = "SELECT 1 FROM user_item WHERE uid=:uid AND tid=:tid";
        $r = $this->exec($sql,['uid'=>$this->_uid,'tid'=>$this->_tid]);
        if($r) {
            //更新个人投注总剩余额度
            list($success,$msg) = $this->_update();
            if(!$success)
                return [Type::FAIL,$msg];
        }else{
            //该用户头一次投注，须新增记录
            $params = [
                'uid'      => $data['uid'],
                'tid'      => $data['tid'],
                'total'    => $data['value'],
                'left_all' => $single_limit - $data['value'],
                'create_time' => date('y-m-d h:i:s',time()),
            ];
            list($success, $msg) = $this->_add_user_item($params);
            if(!$success)
                return [Type::FAIL,$msg];
        }
        //1.向Java后台发送扣积分请求，2.更新该选项总限额，3.将本次积分消费写入记录
        return $this->_updateMain();
    }

    public function _add_user_item($data)
    {
        try{
            $this->_id = $this->add($data);
        }catch (Exception $e) {
            //请求延迟导致，相当于该用户第二次请求插入(uid,tid重复)
            if($e->getCode() == '23000') {
                //更新个人投注总剩余额度
                list($success,$msg) = $this->_update();
                if(!$success)
                    return [Type::FAIL,$msg];
            }else{
                Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
                return [Type::FAIL,'投注失败，数据库异常'];
            }
        }
        return [Type::SUCCESS,''];
    }

    private function _update()
    {
        //更新个人投注总剩余额度
        $sql = "UPDATE user_item SET ".
            " total=total+:v,left_all=left_all-:v ".
            " WHERE left_all>=:v AND uid=:uid AND tid=:tid";
        try{
            $r = $this->exec($sql,['v'=>$this->_value,'uid'=>$this->_uid,'tid'=>$this->_tid]);
        }catch (Exception $e2) {
            Log::error(__CLASS__."::".__FUNCTION__.",code:".$e2->getCode().',msg:'.$e2->getMessage());
            return [Type::FAIL,'投注失败，数据库异常'];
        }
        if(empty($r))
            return [Type::FAIL,'投注失败，已超过上限'];
        return [Type::SUCCESS,''];
    }

    private function _updateMain()
    {
        //1.向Java请求积分减项
        $params = [
            'uid'  => $this->_uid,
            'value'=> intval('-'.$this->_value),
        ];
        list($success,$msg) = $this->_request($params);
        //Java后台不允许本次减项操作
        if(!$success) {
            $this->_rollback();
            return [Type::FAIL,$msg];
        }
        $db = new DB();
        $db->beginTransaction();
        try{
            //2.更新该选项总剩余投注额度
            $sql2 = "UPDATE item SET left_all=left_all-:v WHERE left_all>=:v AND tid=:tid";
            $r2 = $db->execute($sql2,['v'=>$this->_value,'tid'=>$this->_tid]);
            if(empty($r2))
                throw new Exception('该选项投注已达上限');
            //3.在积分消费记录中插入本次投注
            $record = new Record();
            $rd = [
                'uid'  => $this->_uid,
                'value'=> intval('-'.$this->_value),
                'bid'  => $this->_tid,
                'create_time'  => date('y-m-d h:i:s',time()),
            ];
            $record->add($rd);
        }catch (Exception $e) {
            Log::error(__CLASS__."::".__FUNCTION__.",code:".$e->getCode().',msg:'.$e->getMessage());
            $db->rollBack();
            //执行过程出错，回滚之前的插入或更新用户对该项的投注操作
            $this->_rollback();
            return [Type::FAIL,$e->getMessage()];
        }
        return [Type::SUCCESS,''];
    }

    private function _rollback()
    {
        try{
            if($this->_id) {
                $this->delete($this->_id);
            }else{
                //回滚上面的积分操作
                $sql = "UPDATE user_item SET ".
                    " total=total-:v,left_all=left_all+:v ".
                    " WHERE uid=:uid AND tid=:tid";
                $this->exec($sql,['v'=>$this->_value,'uid'=>$this->_uid,'tid'=>$this->_tid]);
            }
        }catch (Exception $e) {
            Log::error(__CLASS__."::".__FUNCTION__.','.$this->_build().",code:".$e->getCode().',msg:'.$e->getMessage());
            return [Type::FAIL,'投注失败，数据库异常'];
        }
        return [Type::SUCCESS,''];
    }

    private function _build()
    {
        return "id:{$this->_id},uid:{$this->_uid},tid:{$this->_tid},value:{$this->_value}";
    }

    private function _request($params)
    {
        return $this->send('',$params);
    }
}