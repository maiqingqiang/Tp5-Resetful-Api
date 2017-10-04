<?php

use think\Config;

/**
 * Created by PhpStorm.
 * User: Mak
 * Email：xiaomak@qq.com
 * Date: 2017/10/3
 * Time: 13:58
 */

/**
 * 获取api响应错误信息
 *
 * @param string $key 错误标识
 *
 * @return string
 */
function apiMsg($key = 'ok') {
    $arr = Config::get("apiCode.{$key}");

    if (!is_array($arr) || !array_key_exists('msg', $arr) || empty($arr['msg'])) {
        return '未知错误';
    }

    return $arr['msg'];
}

/**
 * 获取api响应错误码
 *
 * @param string $key 错误标识
 *
 * @return int|mixed
 */
function apiErrcode($key = 'ok') {
    $arr = Config::get("apiCode.{$key}");
    if (!is_array($arr) || !array_key_exists('errcode', $arr) || ($arr['errcode'] != 0 && empty($arr['errcode']))) {
        return -1;
    }

    return $arr['errcode'];
}