<?php
namespace app\index\controller;

use think\App;
use think\Controller;

class Error extends Controller{

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $this->view->engine->layout(false);
    }

    /**
     * 404
     * @return \think\response\View
     */
    public function err404(){
        return view('404');
    }
}