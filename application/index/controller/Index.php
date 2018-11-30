<?php
namespace app\index\controller;

use app\common\controller\Common;
use think\Db;

class Index extends Common
{
    /**
     * 首页
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(){
        $keywords = Db::name('search_keywords')->field('keyword')->order('order asc')->select();

        $count = Db::name('search_hash')->count();
        dump($count);

        return view()->assign([
            'keywords' => $keywords
        ]);
    }

    /**
     * 搜索提交
     */
    public function search(){
        $keyword = $this->request->post('search', '');
        if (empty($keyword)){
            $this->redirect('/');
        }else{
            $this->redirect("/main-search-kw-{$keyword}-1.html");
        }
    }

    /**
     * 搜索结果
     * @param $keyword
     * @param int $page
     * @return \think\response\View
     */
    public function searchResult($keyword, $type = '', $page = 1){



        return view('list')->assign([
            'keyword' => $keyword,
            'type' => $type,
            'page' => $page
        ]);
    }
}
