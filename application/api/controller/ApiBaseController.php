<?php
/**
 * Api基础控制器
 * Created by PhpStorm.
 * User: Mak
 * Email：xiaomak@qq.com
 * Date: 2017/10/3
 * Time: 13:34
 */

namespace app\api\controller;

use Firebase\JWT\JWT;
use think\Db;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Request;
use think\Config;
use think\Response;
use think\Loader;

class ApiBaseController {

    /**
     * @var \think\Request Request实例
     */
    protected $request;
    // 验证失败是否抛出异常
    protected $failException = false;
    // 是否批量验证
    protected $batchValidate = false;

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [];

    protected $token = '';
    protected $user;
    protected $userId = 0;
    protected $userType;

    /**
     * 构造方法
     *
     * @param Request $request Request对象
     *
     * @access public
     */
    public function __construct(Request $request = null) {
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->request = $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ? $this->beforeAction($options) : $this->beforeAction($method, $options);
            }
        }
    }

    // 初始化
    protected function _initialize() {
    }

    /**
     * api 验证
     */
    protected function checkRequestAuth() {
        $this->token = $this->request->header('Authorization');
        if (empty($this->token)) {
            $this->error(apiMsg('token_fail'), apiErrcode('token_fail'), 400);
        }

        try {
            if (!$this->user) {
                $payload = JWT::decode($this->token, config('jwt-key'), array('HS256'));
                $this->user = $payload->data->user;
                $this->userId = $payload->data->userId;
                $this->userType = $payload->data->userType;
            }
        } catch (\Exception $e) {
            $this->error(apiMsg('token_fail'), apiErrcode('token_fail'), 400);
        }
    }

    /**
     * 前置操作
     * @access protected
     *
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者['except'=>[...]]
     */
    protected function beforeAction($method, $options = []) {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }
            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }
            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this,
            $method]);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     *
     * @param bool $fail 是否抛出异常
     *
     * @return $this
     */
    protected function validateFailException($fail = true) {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     *
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     *
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null) {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $v = Loader::validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if (is_array($message)) {
            $v->message($message);
        }

        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v,
                &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            } else {
                return $v->getError();
            }
        } else {
            return true;
        }
    }

    /**
     * 输出返回数据
     * @param string $msg
     * @param array  $data
     * @param int    $errcode
     * @param int    $httpCode
     * @param array  $header
     *
     * @return \think\response\Json
     */
    protected function response($msg, $data = [], $errcode = 0, $httpCode = 200, $header = []) {
        $result = ['errcode' => $errcode,
            'msg' => $msg,
            'data' => $data];
        $header['Access-Control-Allow-Origin'] = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type,XX-Device-Type,XX-Token';
        $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';

        return json($result, $httpCode, $header);
    }

    /**
     * 输出返回成功数据
     * @param string $msg
     * @param array  $data
     * @param int    $httpCode
     * @param array  $header
     *
     * @return \think\response\Json
     */
    protected function success($msg, $data = [], $httpCode = 200, $header = []) {
        return $this->response($msg, $data, 0, $httpCode, $header);
    }

    /**
     * 输出返回失败数据
     * @param string $msg
     * @param int    $errcode
     * @param int    $httpCode
     * @param array  $header
     *
     * @return \think\response\Json
     */
    protected function error($msg, $errcode = -1, $httpCode = 200, $header = []) {
        return $this->response($msg, [], $errcode, $httpCode, $header);
    }

    /**
     * 字符串根据逗号分隔成数组
     * @param $string
     *
     * @return array
     */
    public function strToArr($string) {
        return is_string($string) ? explode(',', $string) : $string;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index() {
        $model = $this->getModel();

        $params = $this->request->get();

        $map = array();
        foreach ($model->getTableFields() as $key => $val) {
            if (isset($params[$val]) && $params[$val] != '') {
                $map[$val] = $params[$val];
            }
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        if (!empty($params['order'])) {
            $order = $this->strToArr($params['order']);
            foreach ($order as $key => $value) {
                $upDwn = substr($value, 0, 1);
                $orderType = $upDwn == '-' ? 'desc' : 'asc';
                $orderField = substr($value, 1);
                $orderWhere[$orderField] = $orderType;
            }
        } else {
            $orderWhere = 'id desc';
        }

        $filterFields = !empty($params['field']) ? $this->strToArr($params['field']) : '';

        try {

            if (!empty($params['page'])) {
                $size = !empty($params['size']) ? $params['size'] : 10;
                $fromPage = ($params['page'] - 1) * $size;
                $result = $model->field($filterFields)->where($map)->order($orderWhere)->limit($fromPage . ',' . $size)->select();
            } else {
                $result = $model->field($filterFields)->where($map)->order($orderWhere)->select();
            }
            if ($result) {
                return $this->success('获取成功', $result);
            } else {
                return $this->error('没有数据');
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create() {
        //
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save() {
        $model = $this->getModel();
        $params = $this->request->post();
        if (method_exists($this, "_setSaveParamsFilter")) {
            $this->_setSaveParamsFilter($params);
        }
        try {
            $id = $model->insertGetId($params);

            return $this->success("新增成功", ['id' => $id]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function read($id) {
        $model = $this->getModel();
        try {
            $data = $model->where('id', $id)->find();

            return $this->success("查询成功", $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function edit($id) {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function update($id) {
        $model = $this->getModel();
        $params = $this->request->post();
        if (method_exists($this, "_setUpdateParamsFilter")) {
            $this->_setUpdateParamsFilter($params);
        }
        try {
            $result = $model->allowField(true)->save($params, ['id' => $id]);

            if ($result) {
                return $this->success("更新成功", ['id' => $id]);
            }

            return $this->error("更新失败");

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     *
     * @return \think\Response
     */
    public function delete($id) {
        if (!intval($id)) {
            $this->error('id error');
        }
        $model = $this->getModel();
        try {
            $result = $model->where('id', $id)->save(['status' => -1]);
            if ($result) {
                return $this->success('删除成功');
            }

            return $this->error('删除失败');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取model
     * @param null $model
     *
     * @return null|\think\db\Query
     */
    protected function getModel($model = null) {
        $action = $this->request->action();
        $method = "_set" . ucfirst($action) . "Model";
        if (method_exists($this, $method)) {
            $this->$method($model);
        } else {
            if (method_exists($this, "_setModel")) {
                $this->_setModel($model);
            } else {
                $routeInfo = $this->request->routeInfo();
                $className = $routeInfo['rule'][2];
                $model = db($className);
            }
        }

        return $model;
    }
}