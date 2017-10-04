<?php
/**
 * Created by PhpStorm.
 * User: Mak
 * Email：xiaomak@qq.com
 * Date: 2017/10/3
 * Time: 14:22
 */

namespace app\api\controller;

class ApiAdminBaseController extends ApiBaseController {

    protected function _initialize() {
        $this->checkRequestAuth();
        if ($this->userType != 1) {
            $this->error(apiMsg('user_permission_fail'), apiErrcode('user_permission_fail'));
        }
    }
}