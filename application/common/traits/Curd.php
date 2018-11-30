<?php

namespace app\common\traits;

use PHPExcel;
use PHPExcel_IOFactory;
use think\Db;
use think\facade\Env;
use think\facade\View;
use think\Request;
use think\Validate;
use traits\controller\Jump;

trait Curd
{
    use Jump;

    /**
     * @var mixed 模型
     */
    protected $model;

    /**
     * @var string 默认模型对应表别名
     */
    protected $alias = '_a';

    /**
     * @var array 视图文件
     */
    protected $views = [
        'index' => 'curd/index',
        'read' => 'curd/read',
        'create' => 'curd/create',
        'edit' => 'curd/edit',
    ];

    /**
     * @var object 自定义视图
     */
    protected $view;

    /**
     * @var int 每页多少条
     */
    protected $perPage  = 20;

    /**
     * @var string 页面名称
     */
    protected $label = '';

    /**
     * @var string 路由前缀
     */
    protected $route = '';

    /**
     * @var array 表单验证规则
     */
    protected $validateRules = [];

    /**
     * @var array 表单验证字段注释
     */
    protected $validateRulesField = [];

    /**
     * @var array 列表查询条件
     */
    protected $where = [];

    /**
     * @var array 列表排序
     */
    protected $order = ['_a.id' => 'desc'];

    /**
     * @var array 查询字段
     */
    protected $fields       = ['*'];

    /**
     * @var array 列表显示字段
     */
    protected $listFields   = [];

    /**
     * @var array 详情页面显示字段
     */
    protected $readFields = [];

    /**
     * @var Request 请求实例
     */
    protected $request;

    /**
     * @var array 添加表单显示字段
     */
    protected $addFormFields   = [];

    /**
     * @var array 更新表单显示字段
     */
    protected $updateFormFields   = [];

    /**
     * @var array 搜索显示字段
     */
    protected $searchFields = [];

    /**
     * @var array 不常用搜索显示字段
     */
    protected $searchMoreField = [];

    /**
     * @var array Excel导出字段
     */
    protected $excelFields = [];

    /**
     * @var string 表前缀
     */
    protected $dbPre = '';

    protected $not_where = ['token'];

    /**
     * @var array 添加和编辑弹框大小
     */
    protected $modelSize = ['x' => '600px', 'y' => '85%'];

    /**
     * 数据字典
     * @var array
     * type
     * text  文本输入
     * textarea 文本框
     * status 状态
     * guanlian 关联
     *
     */
    protected $translations = [];

    /**
     * @var array 操作按钮的显示与否
     */
    public $function = [
        'read'                   => 1,
        'create'                 => 1,
        'edit'                   => 1,
        'delete'                 => 1,
        'search'                 => 0,
    ];

    protected $is_api = false;

    /**
     * 更多操作
     * @var array
     */
    public $moreFunction = [];


    public function __construct(Request $request)
    {
        parent::__construct();

        $this->request = $request;
        $this->init();

        // 初始化模型
        if ($this->model()) {
            $this->model = app($this->model());
        }

        if (in_array($this->request->action(), ['index', 'read', 'create', 'edit'])){
            // 自定义视图路径
            if ($this->views[$this->request->action()] == 'curd/'.$this->request->action()){
                $this->view = View::init([
                    'view_path' => Env::get('root_path') . 'application/common/traits/'
                ]);
            }else{
                $this->view = new View();
            }
        }else{
            $this->view = new View();
        }

        $this->initField();
        $this->dbPre = config('database.prefix');

//        if (tf_to_xhx($request->module()) == 'admin'){
//            $admin_info = get_admin_info();
//            if ($admin_info['id'] != 1){
//                $this->checkButtonRole($admin_info['id']);
//            }
//        }
    }

    /**
     * 模型
     * @return mixed
     */
    abstract function model();

    /**
     * 初始化
     * @return mixed
     */
    abstract function init();

    /**
     * 初始化列表和表单显示字段
     */
    private function initField(){
        // 如果没有设置字段，则默认显示所有
        if (empty($this->listFields)){
            foreach ($this->translations as $key => $value) {
                $this->listFields[] = $key;
            }
        }
        if (empty($this->addFormFields)){
            foreach ($this->translations as $key => $value) {
                if ($key != 'id'){
                    $this->addFormFields[] = $key;
                }
            }
        }

        //初始化更新字段
        if (empty($this->updateFormFields)){
            $this->updateFormFields = $this->addFormFields;
        }

        // 初始化详情页面
        if (empty($this->readFields)){
            $this->readFields = $this->listFields;
        }
    }

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function index()
    {
        if ($this->is_api){
            $rsp_data = $this->getListApi();
            return rJson(true, '获取成功', $rsp_data);
        }else{
            $list = $this->getList();

            $this->view->assign([
                'list' => $list,
                'list_array' => $list->toArray(),
                'table' => $this->getListHtml($list),
                'search_html' => $this->getSearchHtml(),
                'function' => $this->function,
                'label' => $this->label,
                'route' => $this->route,
                'model_size' => $this->modelSize,
                'default_search' => $this->getDefaultSearch()
            ]);
            return $this->view->fetch($this->views['index']);
        }
    }

    /**
     * 获取默认搜索值
     * @return array
     */
    public function getDefaultSearch(){
        return [];
    }

    /**
     * 查看
     */
    public function read($id = 0){
        if ($this->function['read'] == 0){
            throw new \Exception("404 Not Found");
        }
        if ($this->is_api){
            $id = request()->param('id');
            if (empty($id)){
                return rJson(false, '参数错误');
            }

            return rJson(true, '获取成功', $this->getReadApi($id));
        }else{
            $data = $this->gatReadData($id);
            $this->view->assign([
                'form_html' => $this->getReadHtml($data),
                'label' => $this->label,
                'route' => $this->route
            ]);
            return $this->view->fetch($this->views['read']);
        }
    }

    /**
     * 获取详情展示数据
     * @param $id
     * @return mixed
     */
    protected function gatReadData($id){

        $field = [$this->alias.'.*'];
        $join = [];
        $where = array_merge([$this->alias.'.id' => $id], $this->where);

        foreach ($this->translations as $key => $value) {
            if (!empty($value['type'])){
                switch ($value['type']){
                    case 'join':
                        $field[] = $value['data']['alias'].'.'.$value['data']['show_field'];
                        $join[] = [
                            'table' => $this->dbPre.$value['data']['table'].' '.$value['data']['alias'],
                            'where' => $this->alias.'.'.$key.' = '.$value['data']['alias'].'.'.$value['data']['value_field']
                        ];
                        break;
                    case 'alias':
                        $field[] = $this->alias.'.'.$key.' as '.$value['alias'];
                        break;
                }
            }
        }
        $info = $this->model->alias($this->alias)->field($field)->where($where);
        if (!empty($join)){
            foreach ($join as $value) {
                $info->join($value['table'], $value['where'], 'left');
            }
        }

        $info = $info->find();

        return $info;
    }

    /**
     * 获取查询条件
     * @return array
     */
    protected function getWhere(){

        $get = \request()->get();
        $default_search = $this->getDefaultSearch();
        $count = count($get);
        if (strtoupper(substr(PHP_OS,0,3)) === 'WIN'){
            $count = count($get) - 1;
        }
        if (!empty($default_search) && $count === 0){
            $get = array_merge($get, $default_search);
        }
        //去除不参与参数
        foreach ($this->not_where as $item) {
            if (isset($get[$item])){
                unset($get[$item]);
            }
        }

        $not_where_key = [];
        $where = [];
        foreach ($get as $key => $value) {
            if ($key == 'page'){
                continue;
            }
            if (in_array($key, $not_where_key)){
                continue;
            }
            if ($value != ''){
                if (!empty($this->translations[$key]['type'])){
                    if ($this->translations[$key]['type'] == 'time'){
                        $value = urldecode($value);
                        //截取时间
                        $arr_time = explode('-', $value);
                        if (count($arr_time) != 2){
                            //条件异常，跳过
                            continue;
                        }

                        //时间
                        $star_time = $arr_time[0];
                        $end_time = $arr_time[1];

                        if (!empty($star_time) && empty($end_time)){
                            $where[] = ['_a.'.$key, 'egt', strtotime($star_time)];
                        }elseif (empty($star_time) && !empty($end_time)){
                            $where[] = ['_a.'.$key, 'elt', strtotime($end_time)];
                        }elseif (!empty($star_time) && !empty($end_time)){
                            $where[] = ['_a.'.$key, 'between', [strtotime($star_time), strtotime($end_time)]];
                        }

                        continue;
                    }elseif ($this->translations[$key]['type'] == 'join'){
                        //联查
                        $key = $this->translations[$key]['data']['alias'].'.'.$this->translations[$key]['data']['show_field'];
                    }elseif ($this->translations[$key]['type'] == 'number'){
                        //数字

                        $_where = $get[$key.'_where_'];
                        $_value = $get[$key.'_value_'];

                        switch ($_where){
                            case 1:
                                $where[] = ['_a.'.$key, 'gt', $_value];
                                break;
                            case 2:
                                $where[] = ['_a.'.$key, 'egt', $_value];
                                break;
                            case 3:
                                $where[] = $_value;
                                break;
                            case 4:
                                $where[] = ['_a.'.$key, 'lt', $_value];
                                break;
                            case 5:
                                $where[] = ['_a.'.$key, 'elt', $_value];
                                break;
                        }

                        $not_where_key[] = $key.'_where_';
                        $not_where_key[] = $key.'_value_';

                        continue;
                    }else{
                        $key = '_a.'.$key;
                    }
                }else{
                    $key = '_a.'.$key;
                }

                $p_key = explode('.', $key);
                $p_key = $p_key[1];

                if (!empty($this->translations[$p_key]['type']) && $this->translations[$p_key]['type'] == 'radio'){
                    $where[] = [$key, '=', $value];
                }else{
                    $where[] = [$key, 'like', '%'.$value.'%'];
                }
            }
        }

        $this->get = $get;

        $__where = [];
        foreach ($this->where as $key => $item) {
            $__where[] = [$key, '=', $item];
        }
        $p_where = array_merge($where, $__where);
        $this->where = $p_where;

        return $p_where;
    }

    /**
     * 获取列表数据
     * @return mixed
     */
    protected function getList($page = true){

        $this->getWhere();

        $field = [$this->alias.'.*'];
        $join = [];

        foreach ($this->translations as $key => $value) {
            if (!empty($value['type'])){
                switch ($value['type']){
                    case 'join':
                        if (empty($value['data']['show_field_alias'])){
                            $field[] = $value['data']['alias'].'.'.$value['data']['show_field'];
                        }else{
                            $field[] = $value['data']['alias'].'.'.$value['data']['show_field'].' as '.$value['data']['show_field_alias'];
                        }

                        $_where = $this->alias.'.'.$key.' = '.$value['data']['alias'].'.'.$value['data']['value_field'];
                        if (!empty($value['data']['where'])){
                            $_where .= ' and ' . $value['data']['where'];
                        }
                        $join[] = [
                            'table' => $this->dbPre.$value['data']['table'].' '.$value['data']['alias'],
                            'where' => $_where
                        ];
                        break;
                    case 'alias':
                        $field[] = $this->alias.'.'.$key.' as '.$value['alias'];
                        break;
                }
            }
        }
        $list = $this->model->alias($this->alias)->field($field)->where($this->where);
        if (!empty($join)){
            foreach ($join as $value) {
                $list->join($value['table'], $value['where'], 'left');
            }
        }

        if ($page){
            return $list->order($this->order)->paginate($this->perPage, false, ['query' => $this->get]);
        }else{
            return $list->order($this->order)->select();
        }
    }

    /**
     * 添加
     * @return string|\think\response\View
     * @throws \think\Exception
     */
    public function create(){
        if ($this->function['create'] == 0){
            throw new \Exception("404 Not Found");
        }

        if ($this->views['create'] != 'curd/create') $this->view = new View();

        $this->view->assign([
            'form_html' => $this->getCreateHtml(),
            'label' => $this->label,
            'route' => $this->route
        ]);
        return $this->view->fetch($this->views['create']);
    }

    /**
     * 获取联查表单数据列表
     */
    protected function getJoinDateList($field, $data, $id){
        $list = Db::name($data['table'])->alias($data['alias']);
        if (!empty($data['where'])){
            $list->where($data['where']);
        }
        return $list->select();
    }

    /**
     * 添加数据
     */
    public function save(){
        if ($this->function['create'] == 0){
            throw new \Exception("404 Not Found");
        }
        $data = request()->post();

        $validate = new Validate($this->getValidateRule(), $this->getValidateMessage(), $this->getValidateFieldName());
        $result   = $validate->check($data);
        if(!$result){
            if ($this->is_api){
                return rJson(false, $validate->getError());
            }else{
                if (request()->isAjax()){
                    return rJson(false,  $validate->getError());
                }else{
                    $this->error($validate->getError());
                }
            }
        }
        $vali   = $this->saveBeforeValidate($data);
        if($vali['err_code'] != '0'){
            if ($this->is_api){
                return rJson(false, $vali['err_msg']);
            }else{
                if (request()->isAjax()){
                    return rJson(false,  $vali['err_msg']);
                }else{
                    $this->error($vali['err_msg']);
                }
            }
        }

        //剔除不保存数据
        foreach ($data as $key => $item){
            if (!in_array($key, $this->addFormFields)){
                unset($data[$key]);
            }
        }

        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data = $this->disposeData($data);

        // 保存默认数据
        $before_data = $this->saveBeforeData($data);
        if ($before_data){
            $data = array_merge($data, $before_data);
        }
        $ret = $this->saveOtherValidate($data);
        if ($ret['err_code'] != 0){
            if ($this->is_api){
                return rJson(false, $ret['err_msg']);
            }else{
                if (request()->isAjax()){
                    return rJson(false,  $ret['err_msg']);
                }else{
                    $this->error($ret['err_msg']);
                }
            }
        }

        $res = $this->model->allowField(true)->save($data);
        if ($res){
            if ($this->is_api){
                return rJson(true, '添加成功');
            }else{
                if (request()->isAjax()){
                    return rJson(true,  '添加成功！');
                }else{
                    $this->success('添加成功！');
                }
            }
        }else{
            if ($this->is_api){
                return rJson(false, '添加失败');
            }else{
                if (request()->isAjax()){
                    return rJson(false,  '添加失败！');
                }else{
                    $this->error('添加失败！');
                }
            }
        }
    }

    /**
     * 添加前额外验证
     * @param $data
     * @return array
     */
    protected function saveOtherValidate($data){
        return [
            'err_code' => 0,
            'err_msg' => 'ok'
        ];
    }

    /**
     * 更新前额外验证
     * @param $data
     * @return array
     */
    protected function updateOtherValidate($data){
        return [
            'err_code' => 0,
            'err_msg' => 'ok'
        ];
    }

    /**
     * 处理进入数据库的数据
     * @param $data
     * @return array
     */
    protected function disposeData($data){

        $result = [];
        foreach ($data as $key => $value) {
            if (!empty($this->translations[$key]['type'])){
                switch ($this->translations[$key]['type']){
                    case 'password':
                        if (!empty($value)){
                            $result['salt'] = rand_char();
                            $result[$key] = md5(md5($value).md5($result['salt']));
                        }else{
                            unset($data[$key]);
                        }
                        break;
                    case 'time':
                        if (!is_numeric($value)){
                            $result[$key] = strtotime($value);
                        }else{
                            $result[$key] = $value;
                        }
                        break;
                    case 'checkbox':
                            if (!empty($value) && is_array($value)){
                                $result[$key] = trim(implode(',', array_unique($value)), ',');
                            }else{
                                $result[$key] = '';
                            }
                        break;
                    default:
                        $result[$key] = $value;
                        break;
                }
            }else{
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 获取添加表单验证规则
     * @return array
     */
    protected function getValidateRule(){
        $rule = [];
        foreach ($this->addFormFields as $value){
            if (isset($this->translations[$value]['validate_rule'])){
                if ($this->translations[$value]['validate_rule'] != false){
                    $rule[$value] = $this->translations[$value]['validate_rule'];
                }
            }else{
                $rule[$value] = 'require';
            }
        }
        return $rule;
    }

    /**
     * 获取编辑表单验证规则
     * @return array
     */
    protected function getEditValidateRule($id){
        $rule = [];
        foreach ($this->updateFormFields as $value){
            if (!empty($this->translations[$value]['type']) && $this->translations[$value]['type'] == 'password'){
                continue;
            }
            if (isset($this->translations[$value]['validate_rule'])){
                if ($this->translations[$value]['validate_rule'] != false){
                    $rule[$value] = $this->translations[$value]['validate_rule'];
                }
            }else{
                $rule[$value] = 'require';
            }
        }
        return $rule;
    }

    /**
     * 获取表单验证字段名称
     * @return array
     */
    protected function getValidateFieldName(){
        $filed = [];
        foreach ($this->addFormFields as $value){
            $filed[$value] = $this->translations[$value]['text'];
        }
        return $filed;
    }

    /**
     * 编辑
     * @return string|\think\response\View
     * @throws \think\Exception
     */
    public function edit($id){
        if ($this->function['edit'] == 0){
            throw new \Exception("404 Not Found");
        }

        $info = $this->model->get($id);
        if (empty($info)) $this->error('记录不存在！');

        if ($this->views['edit'] != 'curd/edit') $this->view = new View();

        $this->view->assign([
            'form_html' => $this->getEditHtml($info->toArray()),
            'id' => $id,
            'label' => $this->label,
            'route' => $this->route
        ]);
        return $this->view->fetch($this->views['edit']);
    }

    /**
     * 添加数据
     */
    public function update($id = 0){
        if ($this->function['edit'] == 0){
            throw new \Exception("404 Not Found");
        }
        if (empty($id)){
            $id = \request()->param('id');
        }
        if (empty($id)){
            if ($this->is_api){
                return rJson(false, '缺少ID参数');
            }else{
                if (request()->isAjax()){
                    return rJson(false,  '缺少ID参数');
                }else{
                    $this->error('缺少ID参数');
                }
            }
        }

        $data = request()->post();

        $validate = new Validate($this->getEditValidateRule($id), $this->getValidateMessage(), $this->getValidateFieldName());
        $result   = $validate->check($data);
        if(!$result){
            if ($this->is_api){
                return rJson(false, $validate->getError());
            }else{
                if (request()->isAjax()){
                    return rJson(false,  $validate->getError());
                }else{
                    $this->error($validate->getError());
                }
            }
        }

        $vali   = $this->updateBeforeValidate($id, $data);
        if($vali['err_code'] != '0'){
            if ($this->is_api){
                return rJson(false, $vali['err_msg']);
            }else{
                if (request()->isAjax()){
                    return rJson(false,  $vali['err_msg']);
                }else{
                    $this->error($vali['err_msg']);
                }
            }
        }

        //剔除不保存数据
        foreach ($data as $key => $item){
            if (!in_array($key, $this->addFormFields)){
                unset($data[$key]);
            }
        }

        $data['updated_at'] = time();

        $info = $this->model->get($id);

        $data = $this->disposeData($data);

        // 保存默认数据
        $before_data = $this->updateBeforeData();
        if ($before_data){
            $data = array_merge($data, $before_data);
        }

        $ret = $this->updateOtherValidate($data);
        if ($ret['err_code'] != 0){
            if ($this->is_api){
                return rJson(false, $ret['err_msg']);
            }else{
                if (request()->isAjax()){
                    return rJson(false,  $ret['err_msg']);
                }else{
                    $this->error($ret['err_msg']);
                }
            }
        }

        $res = $info->allowField(true)->save($data);
        if ($res >= 0){
            if ($this->is_api){
                return rJson(true, '保存成功');
            }else{
                if (request()->isAjax()){
                    return rJson(true,  '保存成功！');
                }else{
                    $this->success('保存成功！');
                }
            }
        }else{
            if ($this->is_api){
                return rJson(false, '保存失败');
            }else{
                if (request()->isAjax()){
                    return rJson(false,  '保存失败！');
                }else{
                    $this->error('保存失败！');
                }
            }
        }
    }

    /**
     * 获取验证提示信息
     */
    protected function getValidateMessage(){
        return [];
    }

    /**
     * 记录删除
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function delete(){
        $id = request()->param('id');
        if (empty($id)){
            return rJson(false,  '请选择要删除的记录!');
        }
        $id = trim($id, ',');
        $id_array = explode(',', $id);
        $res = $this->model->destroy($id_array);
        if ($res){
            return rJson(true,  '删除成功!');
        }else{
            return rJson(false,  '删除失败!');
        }
    }

    /**
     * 获取列表页表格html
     * @param $list
     * @return string
     */
    private function getListHtml($list){

        $table = '<table class="table table-hover" id="list">';
        $table .= '<thead><tr class="text-c">';
        if ($this->function['delete'] == 1){
            $table .= '<th class="text-center" style="width: 50px;">选择</th>';
        }
        foreach ($this->listFields as $value) {
            $table .= '<th>';
            $table .= $this->translations[$value]['text'];
            $table .= '</th>';
        }
        if ($this->function['edit'] == 1 || $this->function['delete'] == 1){
            $table .= '<th>操作</th>';
        }
        $table .= '</tr></thead>';
        $table .= '<tbody>';
        foreach ($list as $key => $value) {
            $table .= '<tr class="text-c">';
            if ($this->function['delete'] == 1){
                $table .= '<td class="text-center"><input type="checkbox" class="ids" value="'.$value['id'].'"></td>';
            }
            foreach ($this->listFields as $v) {
                if (empty($this->translations[$v]['type'])){
                    $table .= '<td>';
                    $table .= $value[$v];
                    $table .= '</td>';
                }else{
                    $table .= '<td>';
                    switch ($this->translations[$v]['type']){
                        case 'radio':
                            //radio
                            $table .= '<span class="'.$this->translations[$v]['list'][$value[$v]][0].'">'.$this->translations[$v]['list'][$value[$v]][1].'</span>';
                            break;
                        case 'checkbox':
                            // 多选框
                            $__value = explode(',', trim($value[$v], ','));
                            if (is_array($__value)){
                                foreach ($this->translations[$v]['list'] as $_k => $_x) {
                                    if (in_array($_k, $__value)){
                                        $table .= '<span class="'.$_x[0].'">'.$_x[1].'</span>&nbsp;';
                                    }
                                }
                            }else{
                                $table .= $value[$v];
                            }
                            break;
                        case 'time':
                            //时间
                            $format = 'Y-m-d H:i:s';
                            if (isset($this->translations[$v]['format'])){
                                $format = $this->translations[$v]['format'];
                            }
                            if (is_numeric($value[$v])){
                                $table .= date($format, $value[$v]);
                            }else{
                                $table .= date($format, strtotime($value[$v]));
                            }
                            break;
                        case 'url':
                            // 超链接
                            $table .= '<a href="'.$value[$v].'" target="blank" class="btn btn-warning btn-xs btn-block btn-outline">查看</a>';
                            break;
                        case 'ip':
                            // IP
                            $table .= '<a href="https://www.baidu.com/s?wd='.$value[$v].'"  target="blank">'.$value[$v].'</a>';
                            break;
                        case 'qrcode':
                            // 二维码
                            $table .= '<a href="'.url('/qrcode_pay').'?q='.$value[$v].'"  target="blank">'.$value[$v].'</a>';
                            break;
                        case 'join':
                            $show_field = $this->translations[$v]['data']['show_field'];
                            if (!empty($this->translations[$v]['data']['show_field_alias'])){
                                $show_field = $this->translations[$v]['data']['show_field_alias'];
                            }
                            $table .= $value[$show_field];
                            break;
                        case 'alias':
                            $table .= $value[$this->translations[$v]['alias']];
                            break;
                        case 'image':
                            $table .= '<a href="'.$value[$v].'" target="_blank"><img src="'.$value[$v].'" style="width:50px; height: 50px;" /></a>';
                            break;
                        default :
                            //其它
                            $table .= $value[$v];
                            break;
                    }
                    $table .= '</td>';
                }
            }
            $table .= '<td>';
            if ($this->function['read'] == 1){
                $route = url($this->route.'/read', ['id' => $value['id']]);
                $table .= '<button onclick="layeropen(\''.$route.'\', \'查看'.$this->label.'\', \''.$this->modelSize['x'].'\', \''.$this->modelSize['y'].'\')" class="btn btn-success btn-xs" style="margin-bottom: 0;"><i class="fa fa-arrows-alt"></i> 查看</button>&nbsp;&nbsp;';
            }
            if ($this->function['edit'] == 1){
                $route = url($this->route.'/edit', ['id' => $value['id']]);
                $table .= '<button onclick="layeropen(\''.$route.'\', \'编辑'.$this->label.'\', \''.$this->modelSize['x'].'\', \''.$this->modelSize['y'].'\')" class="btn btn-info btn-xs" style="margin-bottom: 0;"><i class="fa fa-edit"></i> 编辑</button>&nbsp;&nbsp;';
            }
            // 更多按钮
            $model = tf_to_xhx(request()->module());
            if (!empty($this->moreFunction)){
                foreach ($this->moreFunction as $item) {
                    $full = false;
                    if (isset($item['full'])){
                        $full = $item['full'];
                    }
//                    if ($model == 'admin'){
//                        if (check_role($item['route'])){
//                            if (!empty($item['type'])){
//                                if ($item['type'] == 'href'){
//                                    $str = 'href = "'.url($item['route']).'?ids='.$value['id'].'"';
//                                }elseif($item['type'] == 'ajax'){
//                                    $str = 'onclick="ajax(\''.url($item['route']).'?ids='.$value['id'].'\')"';
//                                }
//                            }else{
//                                $str = 'onclick="layeropen(\''.url($item['route']).'?ids='.$value['id'].'\', \''.$item['text'].'\', \''.$item['model_x'].'\', \''.$item['model_y'].'\', false, '.$full.')"';
//                            }
//                            if (!isset($item['where']) || (isset($item['where']) && $value[$item['where']['key']] == $item['where']['value'])){
//                                $table .= '<a '.$str.' class="btn btn-'.$item['btn'].' btn-xs" style="margin-bottom: 0;"><i class="'.$item['icon'].'"></i> '.$item['text'].'</a>&nbsp;&nbsp;';
//                            }else{
//                                $table .= '<button class="btn btn-default btn-xs disabled" style="margin-bottom: 0;" disabled><i class="'.$item['icon'].'"></i> '.$item['text'].'</button>&nbsp;&nbsp;';
//                            }
//                        }
//                    }else{
                        if (!empty($item['type'])){
                            if ($item['type'] == 'href'){
                                $str = 'href = "'.url($item['route']).'?ids='.$value['id'].'"';
                            }elseif($item['type'] == 'ajax'){
                                $str = 'onclick="ajax(\''.url($item['route']).'?ids='.$value['id'].'\')"';
                            }
                        }else{
                            $str = 'onclick="layeropen(\''.url($item['route']).'?ids='.$value['id'].'\', \''.$item['text'].'\', \''.$item['model_x'].'\', \''.$item['model_y'].'\', false, '.$full.')"';
                        }

                        if (!isset($item['where']) || (isset($item['where']) && $value[$item['where']['key']] == $item['where']['value'])){
                            $table .= '<a '.$str.' class="btn btn-'.$item['btn'].' btn-xs" style="margin-bottom: 0;"><i class="'.$item['icon'].'"></i> '.$item['text'].'</a>&nbsp;&nbsp;';
                        }else{
                            $table .= '<button class="btn btn-default btn-xs disabled" style="margin-bottom: 0;" disabled><i class="'.$item['icon'].'"></i> '.$item['text'].'</button>&nbsp;&nbsp;';
                        }
//                    }
                }
            }

            if ($this->function['delete'] == 1){
                $table .= '<button onclick="del('.$value['id'].')" class="btn btn-danger btn-xs" style="margin-bottom: 0;"><i class="fa fa-trash-o"></i> 删除</button>';
            }

            $table .= '</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody>';

        $table .= '</table>';

        return $table;
    }

    /**
     * 获取添加页面html
     * @return string
     */
    private function getCreateHtml(){

        $html = '<form method="post" class="form-horizontal" id="form">';
        foreach ($this->addFormFields as $key => $value) {
            $default = '';
            if (isset($this->translations[$value]['default'])){
                $default = $this->translations[$value]['default'];
            }
            $html .= '<div class="form-group">';
            $html .= '<label class="col-sm-12">'.$this->translations[$value]['text'].'</label>';
            $html .= '<div class="col-sm-12">';
            if (empty($this->translations[$value]['type'])){
                $html .= '<input class="form-control" name="'.$value.'" type="text" value="'.$default.'" AUTOCOMPLETE="off">';
            }else{
                switch ($this->translations[$value]['type']){
                    case 'input':
                        // 表单输入
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off">';
                        break;
                    case 'disabled_input':
                        // 禁用表单
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off" disabled>';
                        break;
                    case 'textarea':
                        // 文本框
                        $html .= '<textarea class="form-control" name="'.$value.'" value="'.$default.'"  type="text"></textarea>';
                        break;
                    case 'password':
                        // 密码框
                        $html .= '<input class="form-control" name="'.$value.'" type="password" AUTOCOMPLETE="off">';
                        break;
                    case 'ip':
                        // ip地址
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off">';
                        break;
                    case 'time':
                        // 时间
                        $html .= '<div class="input-group m-b"><input class="form-control" id="'.$value.'" name="'.$value.'" value="'.$default.'" type="text" AUTOCOMPLETE="off"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
                        $html .= '<script>laydate({elem: \'#'.$value.'\', format:   \'YYYY-MM-DD hh:mm:ss\',istime: true});</script>';
                        break;
                    case 'radio':
                        // 单选
                        foreach ($this->translations[$value]['list'] as $_k => $_x) {
                            $html .= '<div class="radio i-checks" style="float:left;"><label><input type="radio" value="'.$_k.'" name="'.$value.'" ';
                            if (!empty($_x[2])) $html .= 'checked';
                            $html .= '> <i></i> '.$_x[1].'</label></div>';
                        }
                        break;
                    case 'checkbox':
                        // 多选
                        foreach ($this->translations[$value]['list'] as $_k => $_x) {
                            $html .= '<div class="checkbox i-checks" style="float:left;"><label><input type="checkbox" value="'.$_k.'" name="'.$value.'[]" > <i></i> '.$_x[1].'</label></div>';
                        }
                        break;
                    case 'join':
                        $list = $this->getJoinDateList($value, $this->translations[$value]['data'], 0);
                        $html .= '<select class="form-control" name="'.$value.'" id="'.$value.'"><option value="">请选择...</option>';
                        foreach ($list as $_k => $_x) {
                            $html .= '<option value="'.$_x[$this->translations[$value]['data']['value_field']].'">'.$_x[$this->translations[$value]['data']['show_field']].'</option>';
                        }
                        $html .= '</select>';
                        $html .= '<script>$("#'.$value.'").chosen({no_results_text: "未找到匹配项！",search_contains: true});</script>';
                        break;
                    case 'ueditor':
                        // 百度富文本编辑器
                        $html .= '<script id="'.$value.'" name="'.$value.'" type="text/plain" style="width:100%;min-height:350px;">'.$default.'</script>';
                        $html .= '<script type="text/javascript"> var ue = UM.getEditor(\''.$value.'\'); </script>';
                        break;
                    default :
                        // 其他
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off">';
                        break;
                }
            }
            if (!empty($this->translations[$value]['info'])){
                $html .= '<span class="help-block m-b-none"><i class="fa fa-info-circle"></i> '.$this->translations[$value]['info'].'</span>';
            }
            $html .= '</div></div>';
        }
        $html .= '<div style="clear: both;"></div><div class="form-group"><div class="col-sm-12"><button class="btn btn-primary btn-block" type="button" id="sub">确定添加</button></div></div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * 获取编辑页面html
     * @return string
     */
    private function getEditHtml($data){

        $html = '<form method="post" class="form-horizontal" id="form">';
        foreach ($this->updateFormFields as $key => $value) {
            $data[$value] = htmlspecialchars($data[$value]);

            $html .= '<div class="form-group">';
            $html .= '<label class="col-sm-12">'.$this->translations[$value]['text'].'</label>';
            $html .= '<div class="col-sm-12">';
            if (empty($this->translations[$value]['type'])){
                $html .= '<input class="form-control" name="'.$value.'" type="text" value="'.$data[$value].'" AUTOCOMPLETE="off">';
            }else{
                switch ($this->translations[$value]['type']){
                    case 'input':
                        // 表单输入
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$data[$value].'" type="text" AUTOCOMPLETE="off">';
                        break;
                    case 'disabled_input':
                        // 禁用表单
                        $html .= '<input class="form-control" name="'.$value.'" type="text" value = "'.$data[$value].'" AUTOCOMPLETE="off" disabled>';
                        break;
                    case 'textarea':
                        // 文本框
                        $html .= '<textarea class="form-control" name="'.$value.'" type="text">'.$data[$value].'</textarea>';
                        break;
                    case 'password':
                        // 密码框
                        $html .= '<input class="form-control" name="'.$value.'" type="password" AUTOCOMPLETE="off" >';
                        break;
                    case 'ip':
                        // ip地址
                        $html .= '<input class="form-control" name="'.$value.'" type="text" value="'.$data[$value].'" AUTOCOMPLETE="off">';
                        break;
                    case 'time':
                        // 时间
                        if (is_numeric($data[$value])){
                            $data[$value] = date('Y-m-d H:i:s', $data[$value]);
                        }
                        $html .= '<div class="input-group m-b"><input class="form-control" id="'.$value.'" name="'.$value.'" value="'.$data[$value].'" type="text" AUTOCOMPLETE="off"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
                        $html .= '<script>laydate({elem: \'#'.$value.'\', format:   \'YYYY-MM-DD hh:mm:ss\',istime: true});</script>';
                        break;
                    case 'radio':
                        // 状态
                        foreach ($this->translations[$value]['list'] as $_k => $_x) {
                            $html .= '<div class="radio i-checks" style="float:left;"><label><input type="radio" value="'.$_k.'" name="'.$value.'" ';
                            if ($_k == $data[$value]) $html .= 'checked';
                            $html .= '> <i></i> '.$_x[1].'</label></div>';
                        }
                        break;
                    case 'checkbox':
                        // 多选
                        $__value = explode(',', trim($data[$value], ','));
                        if (is_array($__value)){
                            foreach ($this->translations[$value]['list'] as $_k => $_x) {
                                $html .= '<div class="checkbox i-checks" style="float:left;"><label><input type="checkbox" value="'.$_k.'" name="'.$value.'[]" ';
                                if (in_array($_k, $__value)) $html .= 'checked';
                                $html .= '> <i></i> '.$_x[1].'</label></div>';
                            }
                        }
                        break;
                    case 'join':
                        $list = $this->getJoinDateList($value, $this->translations[$value]['data'], $data['id']);
                        $html .= '<select class="form-control" name="'.$value.'" id="'.$value.'"><option value="">请选择...</option>';
                        foreach ($list as $_k => $_x) {
                            $html .= '<option value="'.$_x[$this->translations[$value]['data']['value_field']].'"';
                            if ($data[$value] == $_x[$this->translations[$value]['data']['value_field']]){
                                $html .= ' selected';
                            }
                            $html .='>'.$_x[$this->translations[$value]['data']['show_field']].'</option>';
                        }
                        $html .= '</select>';
                        $html .= '<script>$("#'.$value.'").chosen({no_results_text: "未找到匹配项！",search_contains: true});</script>';
                        break;
                    case 'ueditor':
                        // 百度富文本编辑器
                        $html .= '<script id="'.$value.'" name="'.$value.'" type="text/plain" style="width: 100%;min-height:350px; ">'.htmlspecialchars_decode($data[$value]).'</script>';
                        $html .= '<script type="text/javascript"> var ue = UM.getEditor(\''.$value.'\'); </script>';
                        break;
                    default :
                        // 其他
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$data[$value].'" type="text" AUTOCOMPLETE="off">';
                        break;
                }
            }
            if (!empty($this->translations[$value]['info'])){
                $html .= '<span class="help-block m-b-none"><i class="fa fa-info-circle"></i> '.$this->translations[$value]['info'].'</span>';
            }
            $html .= '</div></div>';
        }
        $html .= '<div class="form-group"><div class="col-sm-12"><button class="btn btn-primary btn-block" type="button" id="sub">确定保存</button></div></div>';
        $html .= '</form>';


        return $html;
    }

    /**
     * 获取编辑页面html
     * @return string
     */
    private function getReadHtml($data){

        $html = '<table class="table table-striped">';
        $html .= '<thead><tr><th>字段</th><th>内容</th></tr></thead><tbody>';

        foreach ($this->readFields as $item) {

            $_key = $this->translations[$item]['text'];
            $_value = $data->$item;
            $is_show = true;
            if (isset($this->translations[$item]['type'])){
                switch ($this->translations[$item]['type']){
                    case 'password': $is_show = false; break;
                    case 'radio':
                        $_value = '<span class="'.$this->translations[$item]['list'][$data->$item][0].'">'.$this->translations[$item]['list'][$data->$item][1].'</span>';
                        break;
                    case 'time':
                        if (is_numeric($data->$item)){
                            $_value = date('Y-m-d H:i:s', $data->$item);
                        }
                        break;
                    case 'join':
                        $field = $this->translations[$item]['data']['show_field'];
                        $_value = $data->$field;
                        break;
                    default:
                        $_value = '<textarea class="form-control">'.$_value.'</textarea>';
                        break;
                }
            }else{
                $_value = '<textarea class="form-control">'.$_value.'</textarea>';
            }
            if ($is_show) $html .= '<tr><td>'.$_key.'</td><td>'.$_value.'</td></tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * 获取搜索html
     */
    public function getSearchHtml(){

        $html = '';
        if (!empty($this->searchFields)){
            $html .= '<form role="form" class="form-inline" id="form">';

            //常用搜索字段
            $html .= $this->searchField($this->searchFields);

            //不常用搜索字段
            $more_html = $this->searchField($this->searchMoreField);
            if (!empty($more_html)){
                $html .= '<a class="btn btn-outline btn-link" id="more_search">更多条件</a>';
                $html .= '<div style="display:none; margin-top: 15px" id="more_search_html">'.$more_html.'</div>';
            }

            $html .= '<br/><br/><div class="form-group" style="margin-left: 10px; margin-top: 5px;"><input type="button"  class="btn btn-success" id="search" value="搜索"> <a class="btn btn-default btn-outline" href="'.url($this->route.'/index').'">重置</a></div>';

//            $html .= '<div class="form-group" style="margin-left: 10px; margin-top: 5px;"><input type="button" class="btn btn-default" value="Excel 导出" id="excelExportData" > </div>';

            $html .= '</form>';
        }

        return $html;
    }

    /**
     * 搜索字段html处理
     * @return string
     */
    private function searchField($fields){

        $default_search = $this->getDefaultSearch();

        $html = '';
        foreach ($fields as $key => $v) {
            $html .= '<div class="form-group">';
            if (empty($this->translations[$v]['type'])){
                $html .= '<input type="text" name="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" style="width: 130px; margin-left: 8px" value="'.$this->request->get($v).'">';
            }else{
                switch ($this->translations[$v]['type']){
                    case 'input':
                        $_val = $this->request->get($v);
                        if (empty($_val) && isset($default_search[$v])){
                            $_val = $default_search[$v];
                        }
                        $html .= '<input type="text" name="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" style="width: 130px; margin-left: 8px" value="'.$_val.'">';
                        break;
                    case 'radio':
                        $_val = $this->request->get($v);
                        if (empty($_val) && $_val != 0 && isset($default_search[$v])){
                            $_val = $default_search[$v];
                        }
                        $html .= '<select class="form-control" name="'.$v.'" style="width: 130px; margin-left: 8px">';
                        $html .= '<option value="">'.$this->translations[$v]['text'].'</option>';
                        foreach ($this->translations[$v]['list'] as $_k => $_v) {
                            $_selected = '';
                            if ($_val != '' && $_val == $_k){
                                $_selected = 'selected';
                            }
                            $html .= '<option value="'.$_k.'" '.$_selected.'>'.$_v[1].'</option>';
                        }
                        $html .= '</select>';
                        break;
                    case 'time':
                        $_val = $this->request->get($v);
                        if (empty($_val) && isset($default_search[$v])){
                            $_val = $default_search[$v];
                        }
                        $html .= '<input type="text" name="'.$v.'" id="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" style="width: 130px; margin-left: 8px" value="'.$_val.'" autocomplete="off"><script>laydate.render({elem: \'#'.$v.'\', type: \'datetime\', format:   \'yyyy/MM/dd HH:mm:ss\',range: true});</script>';
                        break;
                    case 'number':
                        $where_field = $v.'_where_';
                        $value_field = $v.'_value_';

                        $input_where = $this->request->get($where_field);
                        $input_value = $this->request->get($value_field);

                        $where_value = $input_where;
                        $value_value = $input_value;

                        $html .= '<input type="hidden" id="'.$v.'" name="'.$v.'" value="number">';
                        $_arr = [
                            '1' => '>',
                            '2' => '>=',
                            '3' => '=',
                            '4' => '<',
                            '5' => '<=',
                        ];
                        $html .= '&nbsp;&nbsp;<select class="form-control" id="'.$v.'_where_" name="'.$v.'_where_" AUTOCOMPLETE="off"  style="width: 120px; margin-left: 8px"><option value="">'.$this->translations[$v]['text'].'选择..</option>';
                        foreach ($_arr as $_key => $_item) {
                            $_selected = '';
                            if ($where_value != '' && $where_value == $_key){
                                $_selected = 'selected';
                            }
                            $html .= '<option value="'.$_key.'" '.$_selected.'>'.$_item.'</option>';
                        }
                        $html .= '</select>';
                        $html .= '<input class="form-control" id="'.$v.'_value_" name="'.$v.'_value_" type="text" AUTOCOMPLETE="off" value="'.$value_value.'"  style="width: 80px;" placeholder="'.$this->translations[$v]['text'].'值">';

                        break;
                    default:
                        $_val = $this->request->get($v);
                        if (empty($_val) && isset($default_search[$v])){
                            $_val = $default_search[$v];
                        }
                        $html .= '<input type="text" name="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" value="'.$_val.'" style="width: 130px; margin-left: 8px">';
                        break;
                }
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * 验证权限按钮显示
     */
    protected function checkButtonRole($uid){
        $role = tf_to_xhx(request()->module()).'/'.tf_to_xhx(request()->controller());
        if (!$this->auth->check($role.'/read', $uid)){
            $this->function['read'] = 0;
        }
        if (!$this->auth->check($role.'/create', $uid)){
            $this->function['create'] = 0;
        }
        if (!$this->auth->check($role.'/edit', $uid)){
            $this->function['edit'] = 0;
        }
        if (!$this->auth->check($role.'/delete', $uid)){
            $this->function['delete'] = 0;
        }
    }

    /**
     * 添加时默认保存数据
     * @return bool
     */
    protected function saveBeforeData($data){
        return false;
    }

    /**
     * 更新时默认保存数据
     * @return bool
     */
    protected function updateBeforeData(){
        return false;
    }

    /**
     * 保存前额外验证数据
     */
    protected function saveBeforeValidate($data){
        return [
            'err_code' => '0',
            'err_msg' => 'ok'
        ];
    }

    /**
     * 更新前额外验证数据
     */
    protected function updateBeforeValidate($id, $data){
        return [
            'err_code' => '0',
            'err_msg' => 'ok'
        ];
    }

    /**
     * 删除前验证
     * @param $id
     * @return array
     */
    protected function deleteBeforeValidate($id){
        return [
            'err_code' => '0',
            'err_msg' => 'ok'
        ];
    }

    /**
     * Excel导出
     */
    public function excelExportData(){

        $list = $this->getList(false);

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->setActiveSheetIndex(0);

        $activeSheet = $objPHPExcel->getActiveSheet();

        if (empty($this->excelFields)){
            $this->excelFields = $this->listFields;
        }

        $index = 0;
        foreach ($this->excelFields as $item) {
            $activeSheet->setCellValue(int_to_chr($index).'1', $this->translations[$item]['text']);
            $index++;
        }
        foreach ($list as $key => $item) {
            $i = $key + 2;
            $activeSheetIndex = $objPHPExcel->setActiveSheetIndex(0);
            $_index = 0;
            foreach ($this->excelFields as $_item) {
                if (isset($this->translations[$_item]['type'])){
                    $type = $this->translations[$_item]['type'];
                    switch ($type){
                        case 'join':
                            $activeSheetIndex->setCellValue(int_to_chr($_index).$i, $item[$this->translations[$_item]['data']['show_field']]);
                            break;
                        case 'radio':
                            $activeSheetIndex->setCellValue(int_to_chr($_index).$i, $this->translations[$_item]['list'][$item[$_item]][1]);
                            break;
                        case 'time':
                            $time = $item[$_item];
                            if (is_numeric($time)){
                                $time = date('Y-m-d H:i:s', $time);
                            }
                            $activeSheetIndex->setCellValue(int_to_chr($_index).$i, $time);
                            break;
                        default:
                            if (is_numeric($item[$_item]) && mb_strlen($item[$_item]) > 15){
                                $item[$_item] = ' '.$item[$_item];
                            }
                            $activeSheetIndex->setCellValue(int_to_chr($_index).$i, $item[$_item]);
                            break;
                    }
                }else{
                    if (is_numeric($item[$_item]) && mb_strlen($item[$_item]) > 15){
                        $item[$_item] = ' '.$item[$_item];
                    }
                    $activeSheetIndex->setCellValue(int_to_chr($_index).$i, $item[$_item]);
                }
                $_index++;
            }
        }

        $title = $this->label.'_'.date('Y-m-d H:i:s', time());
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 获取列表整理后API数据
     * @return array
     */
    protected function getListApi(){

        $order = $this->getList();
        if (!empty($order)){
            $order = $order->toArray();
        }

        $rsp_data = [
            'total' => $order['total'],
            'per_page' => $order['per_page'],
            'current_page' => $order['current_page'],
            'last_page' => $order['last_page'],
        ];

        $data_list = [];
        if (!in_array('id', $this->listFields)){
            array_unshift($this->listFields, 'id');
        }

        foreach ($order['data'] as $key => $value) {
            $item = [];
            foreach ($this->listFields as $v) {
                if (empty($this->translations[$v]['type'])){
                    $item[$v] = $value[$v];
                }else{
                    switch ($this->translations[$v]['type']){
                        case 'time':
                            //时间
                            if (!empty($value[$v])){
                                $format = 'Y-m-d H:i:s';
                                if (isset($this->translations[$v]['format'])){
                                    $format = $this->translations[$v]['format'];
                                }
                                if (is_numeric($value[$v])){
                                    $_time = date($format, $value[$v]);
                                }else{
                                    $_time = date($format, strtotime($value[$v]));
                                }
                            }else{
                                $_time = '';
                            }
                            $item[$v] = $_time;
                            break;
                        case 'join':
                            $item[$v] = $value[$this->translations[$v]['data']['show_field']];
                            break;
                        case 'alias':
                            $item[$v] = $value[$this->translations[$v]['alias']];
                            break;
                        default :
                            //其它
                            $item[$v] = $value[$v];
                            break;
                    }
                }
            }
            $data_list[] = $item;
        }

        $rsp_data['data'] = $data_list;

        return $rsp_data;
    }

    /**
     * Api详情页面数据
     * @param $id
     * @return mixed
     */
    protected function getReadApi($id){
        $data = $this->gatReadData($id);

        if (!in_array('id', $this->readFields)){
            array_unshift($this->readFields, 'id');
        }
        $rsp_data = [];
        foreach ($this->readFields as $item) {
            if (isset($this->translations[$item]['type'])){
                switch ($this->translations[$item]['type']){
                    case 'time':
                        if (empty($data->$item)){
                            $rsp_data[$item] = '';
                        }else{
                            $rsp_data[$item] = date('Y-m-d H:i:s', $data->$item);
                        }
                        break;
                    case 'join':
                        $field = $this->translations[$item]['data']['show_field'];
                        $rsp_data[$item] = $data->$field;
                        break;
                    case 'alias':
                        $field = $this->translations[$item]['alias'];
                        $rsp_data[$item] = $data->$field;
                        break;
                    default:
                        $rsp_data[$item] = $data->$item;
                        break;
                }
            }else{
                $rsp_data[$item] = $data->$item;
            }
        }

        return $rsp_data;
    }
}