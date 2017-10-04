<?php
/**
 * Created by PhpStorm.
 * User: Mak
 * Email：xiaomak@qq.com
 * Date: 2017/10/3
 * Time: 17:24
 */
namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;

class User extends ApiBaseController{
    public function _setReadModel(&$model){
        $model=db('user_copy');
    }
}