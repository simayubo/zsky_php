<?php
namespace app\index\controller;

use app\common\controller\Common;
use Sphinx\SphinxClient;
use think\Db;
use think\facade\Cache;

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

        $cache_count = Cache::get('index_count');
        if (!empty($cache_count)){
            $total = $cache_count['total'];
            $today = $cache_count['today'];
        }else{
            $total = Db::name('search_hash')->field('ifnull(max(id),0)-ifnull(min(id),0)+1 as rows')->find();
            $today = Db::name('search_hash')->where('create_time', '>', date('Y-m-d'))->count('id');
            Cache::set('index_count', ['total' => $total, 'today' => $today], 600);
        }

        return view()->assign([
            'keywords' => $keywords,
            'total' => $total['rows'],
            'today' => $today,
        ]);
    }

    public function test(){
        $total = Db::name('search_hash')->where('create_time', '>', date('Y-m-d'))->count('id');
        var_dump($total);
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
