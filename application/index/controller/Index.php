<?php
namespace app\index\controller;

use app\common\controller\Common;
use app\common\traits\Page;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Jieba;
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
            'count' => $count
        ]);
    }

    /**
     * 搜索
     * @return \think\response\View
     * @throws \ErrorException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search(){

        $keyword = $this->request->param('keyword', '');
        $type = $this->request->param('type', '');
        $page = $this->request->param('page', 1);

        if (!empty($keyword)){
            $result = ['total' => 0, 'sec' => 0, 'error' => '', 'warning' => '', 'list' => []];
            $page_size = 20;
            $start = ($page - 1) * $page_size;

            $keyword = filter_keyword($keyword);

            $sphinx = new SphinxClient();
            $sphinx->setServer('185.246.85.49', 9312);
            if ($type == 'length'){
                $sphinx->setSortMode(1, 'length');
            }elseif ($type == 'time'){
                $sphinx->setSortMode(1, 'create_time');
            }elseif ($type == 'requests'){
                $sphinx->setSortMode(1, 'requests');
            }
            $sphinx->setLimits($start, $page_size, 50000);
            $ret = $sphinx->query($keyword);
            $sphinx->close();

            if (empty($ret)){
                $result['error'] = '服务开小差了，请重试！';
                $result['list'] = [];
            }elseif (!empty($ret) && !isset($ret['matches'])){
                $result['list'] = [];
                $result['sec'] = $ret['time'] * 1000;
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
                    $_files = isset($files_list[$item['attrs']['info_hash']])?$files_list[$item['attrs']['info_hash']]:'';

                    $result_list[] = array_merge($item['attrs'], [
                        'files' => (array)json_decode($_files, true)
                    ]);
                }
                $result['list'] = $result_list;
            }

            //分页
            $query = [];
            if (!empty($keyword)) $query['keyword'] = $keyword;
            if (!empty($type)) $query['type'] = $type;
            if (!empty($page)) $query['page'] = $page;

            $pages = Page::make(
                $result['list'], $page_size, $page, $result['total']
                , false, ['path' => url('/search'), 'query' => $query]
            );

            return view('list')->assign([
                'keyword' => $keyword,
                'result' => $result,
                'type' => $type,
                'pages' => $pages,
                'tags' => $this->getTags(),
                'title' => $keyword.' - 搜索结果(第'.$page.'页)'
            ]);
        }else{
            $this->redirect('/');
        }
    }

    /**
     * 获取搜索关键词
     */
    private function getKeywords(){
        $cache_keywords = Cache::get('index_keywords');
        if (!empty($cache_keywords)){
            return $cache_keywords;
        }else{
            $keywords = Db::name('search_keywords')->field('keyword')->order('order asc')->select();
            Cache::set('index_keywords', $keywords);
            return $keywords;
        }
    }

    /**
     * 获取搜索历史关键词(TAGS)（数据来源于数据库搜索历史）
     */
    private function getSearchKeywords(){
        $cache_search_keywords = Cache::get('search_keywords');
        if (!empty($cache_search_keywords)){
            return $cache_search_keywords;
        }else{
            $keywords = Db::name('search_tags')->field('tag')->order('id desc')->select();
            Cache::set('search_keywords', $keywords, 600);
            return $keywords;
        }
    }

    /**
     * 自定义标签(标签页或列表右侧展示)，请在.env中定义 SITE_LIKE_TAGS
     */
    private function getTags(){
        $tags = [];
        $tags_array = explode(',', trim($this->system_config['SITE_LIKE_TAGS'], ','));
        foreach ($tags_array as $item) {
            $tags[]['tag'] = $item;
        }

        return $tags;
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

    /**
     * 详情
     * @param $hash
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($hash){
        if (empty($hash)){
            return $this->redirect('/');
        }
        $info = Db::name('search_hash')->alias('a')
            ->field('a.*, b.file_list')
            ->leftJoin('search_filelist b', 'a.info_hash = b.info_hash')
            ->where('a.info_hash', $hash)
            ->find();
        if (empty($info)){
            return $this->redirect('/');
        }
        $files = json_decode($info['file_list'], true);
        if (empty($files)){
            $files = [];
        }
        //jieba分词
        Jieba::init();
        Finalseg::init();
        $jieba_name = Jieba::cut($info['name']);

        return view()->assign([
            'info' => $info,
            'files' => $files,
            'jieba_name' => $jieba_name,
            'title' => $info['name']
        ]);
    }

    /**
     * 标签
     * @return \think\response\View
     */
    public function tag(){

        return view()->assign([
            'tags' => $this->getTags(),
            'title' => '标签'
        ]);
    }

    /**
     * 周排行
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function weekhot(){

        $weekhot = Cache::get('weekhot');
        if (empty($weekhot)){
            $week_time = date('Y-m-d', strtotime('this week Monday'));
            $weekhot = Db::name('search_hash')->where('create_time', '>', $week_time)->order('requests desc')->limit(50)->select();
            if (empty($weekhot)) {
                $weekhot = [];
            }else{
                Cache::set('weekhot', $weekhot, 36000);
            }
        }

        return view()->assign([
            'weekhot' => $weekhot,
            'title' => '周排行'
        ]);
    }

    /**
     * 最新
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function news(){

        $news_list = Cache::get('news_list');
        if (empty($news_list)){
            $news_list = Db::name('search_hash')->order('id desc')->limit(50)->select();
            if (empty($news_list)) {
                $news_list = [];
            }else{
                Cache::set('news_list', $news_list, 1800);
            }
        }
        return view('new')->assign([
            'news_list' => $news_list,
            'title' => '最新'
        ]);
    }


}
