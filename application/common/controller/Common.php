<?php
namespace app\common\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Env;

class Common extends Controller {

    protected $middleware = ['auth'];
    protected $system_config;

    /**
     * Common constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->system_config = array_merge(Env::get(), Config::pull('zsky'));
        $this->assign('system_config', $this->system_config);
    }
}
