<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/5/19
 * Time: 20:47
 */

namespace app\api\controller;


use think\Request;

class PlugsBaseApi extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

    }

}