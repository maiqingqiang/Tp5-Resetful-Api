<?php
/**
 * Created by PhpStorm.
 * User: Mak
 * Email：xiaomak@qq.com
 * Date: 2017/10/3
 * Time: 14:22
 */

namespace app\api\controller;

class ApiUserBaseController extends ApiBaseController {

    protected function _initialize() {
        $this->checkRequestAuth();
    }
}