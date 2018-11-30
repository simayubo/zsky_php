<?php
namespace app\common\controller;

use think\facade\Cache;
use think\facade\Session;

class Admin extends Common {

    protected static $admin;

    public function __construct()
    {
        parent::__construct();
        exit('小子，你想干啥？');
        self::$admin = \app\common\model\Admin::get(Session::get('admin')['id']);
    }

    /**
     * 清除缓存
     * @return bool
     */
    protected function rmCache(){
        if (Cache::clear()){
            return true;
        }else{
            return false;
        }
    }
}
