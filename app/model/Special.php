<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/26
 * Time: 16:28
 */

namespace app\model;


class Special extends BaseModel
{
    public function add($data)
    {
        return $this->insert($data);
    }

}