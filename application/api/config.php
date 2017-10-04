<?php
/**
 * Created by PhpStorm.
 * User: Mak
 * Email：xiaomak@qq.com
 * Date: 2017/10/3
 * Time: 13:37
 */

return [
    'default_return_type'=>'json',
    'jwt-key'=>'test',
    'apiCode'=>[
        'ok'=>['msg'=>'ok','errcode'=>0],
        'token_fail'=>['msg'=>'token校验失败','errcode'=>10001],
        'user_permission_fail'=>['msg'=>'用户权限错误','errcode'=>10002],
    ]
];