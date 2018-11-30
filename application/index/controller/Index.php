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
     */
    public function index(){

        $keywords = $this->getKeywords();
        $count = $this->getCount();

        return view()->assign([
            'keywords' => $keywords,
            'count' => $count,
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
     * @param string $type
     * @param int $page
     * @return \think\response\View
     * @throws \ErrorException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchResult($keyword, $type = '', $page = 1){

        $result = ['total' => 0, 'sec' => 0, 'error' => '', 'warning' => '', 'list' => []];
        if (!empty($keyword)){
            $sphinx = new SphinxClient();
            $sphinx->setServer('185.246.85.49', 9312);
            $sphinx->setSortMode(2, 'requests');
            $sphinx->setLimits(0,20,50000);
            $ret = $sphinx->query($keyword);

            if (empty($ret)){
                $result['list'] = [];
            }else{
                $result['total'] = $ret['total'];
                $result['sec'] = $ret['time'] * 1000;
                $result['error'] = $ret['error'];
                $result['warning'] = $ret['warning'];

                //查询ids组合
                $hash_ids = [];
                foreach ($ret['matches'] as $key => $item) {
                    $hash_ids[] = $item['attrs']['info_hash'];
                }
                //查询关联文件表
                $files = Db::name('search_filelist')->whereIn('info_hash', $hash_ids)->select();
                $files_list = [];
                foreach ($files as $item) {
                    $files_list[$item['info_hash']] = $item['file_list'];
                }
                //拼装数组列表
                $result_list = [];
                foreach ($ret['matches'] as $item) {
                    $_files = isset($files_list[$item['attrs']['info_hash']])?$files_list[$item['attrs']['info_hash']]:'[]';
                    $result_list[] = array_merge($item['attrs'], [
                        'files' => json_decode($_files, true)
                    ]);
                }
                $result['list'] = $result_list;
            }
        }

        return view('list')->assign([
            'keyword' => $keyword,
            'result' => $result,
            'type' => $type,
            'page' => $page
        ]);
    }




    /**
     * 获取搜索关键词
     */
    private function getKeywords(){
        $cache_keywords = Cache::get('index_keywords');
        if (!empty($cache_count)){
            return $cache_keywords;
        }else{
            $keywords = Db::name('search_keywords')->field('keyword')->order('order asc')->select();
            Cache::set('index_keywords', $keywords);
            return $keywords;
        }
    }

    /**
     * 统计
     */
    private function getCount(){
        $cache_count = Cache::get('index_count');
        if (!empty($cache_count)){
            return [
                'total' => $cache_count['total'],
                'today' => $cache_count['today'],
            ];
        }else{
            $total = Db::name('search_hash')->field('ifnull(max(id),0)-ifnull(min(id),0) as rows')->find();
            $today = Db::name('search_hash')->where('create_time', '>', date('Y-m-d'))->count('id');
            Cache::set('index_count', ['total' => $total['rows'], 'today' => $today], 3000);

            return [
                'total' => $total['rows'],
                'today' => $today,
            ];
        }
    }
}
