<?php
namespace app\admin\controller;

use app\common\controller\Admin;
use think\facade\Cache;
use think\facade\Env;
use think\Session;

class System extends Admin
{
    /**
     * 清除缓存
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function cleanCache(){
        if ($this->rmCache()){
            return rJson(true, '缓存清除成功！');
        }else{
            return rJson(false, '缓存清除失败！');
        }
    }

    /**
     * 系统配置
     */
    public function config(){
        $group_id = $this->request->param('group_id', 1);

        $list = \app\common\model\SysConfig::all(['group_id' => $group_id, 'is_show' => 1]);

        $this->assign([
            'group' => config('admin.config_group'),
            'group_id' => $group_id,
            'list' => $list
        ]);

        return view();
    }

    /**
     * 更新系统配置
     * @return \think\response\Json
     * @throws \Exception
     */
    public function updateConfig(){
        $post = $this->request->post();

        $list = [];
        foreach ($post as $key => $item) {
            $list[] = ['id' => $key, 'config_value' => $item];
        }

        $model = new \app\common\model\SysConfig();
        $result = $model->isUpdate()->saveAll($list);
        if ($result){
            Cache::rm('system_config');
            return rJson(true, '保存成功！');
        }else{
            return rJson(false,'保存失败！');
        }
    }

    /**
     * 上传文件
     */
    public function upLoad(){
        $dir = request()->post('type', 'file');
        $file = request()->file('file');
        if (!empty($file)){
            $info = $file->validate(['size' => 20000000, 'ext'=>'jpg,png,gif,pfx,cer'])->move(Env::get('root_path') . 'public/uploads/'.$dir);
            if($info){
                $file_path = str_replace("\\", "/", '/uploads/'.$dir.'/'.$info->getSaveName());
                return ['status' => 1, 'msg' => '上传成功！', 'path' => $file_path, 'url' => $file_path];
            }else{
                return ['status' => -1, 'msg' => $file->getError()];
            }
        }else{
            return ['status' => -1, 'msg' => '未选择文件'];
        }
    }

}
