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

class Contest extends BaseModel {

    public function updateBc()
    {
        return $this->db->updateBatch('jc_item',[
           ['name'=>'ccc','single_max'=>'123'],
           ['name'=>'sss','single_max'=>'256'],
        ], ['id',[1,2]]);
    }
}