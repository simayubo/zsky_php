<?php
namespace app\index\controller;

use app\common\controller\Common;
use Sphinx\SphinxClient;
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
//        $keywords = Db::name('search_keywords')->field('keyword')->order('order asc')->select();

//        $sphinx = new SphinxClient();
//        $sphinx->host = '185.246.85.49';
//        $sphinx->port = '9312';

//        dump($sphinx->status());
//        dump($sphinx->query('11'));

        $count = Db::name('search_hash')->count();
        dump($count);

//        return view()->assign([
//            'keywords' => $keywords
//        ]);
    }

    public function test(){
        $count = Db::name('search_hash')->field('ifnull(max(id),0)-ifnull(min(id),0)+1 as rows')->find();
        dump($count);
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
