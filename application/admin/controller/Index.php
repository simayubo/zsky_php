<?php
namespace app\admin\controller;

use app\common\controller\Admin;
use think\Cache;
use think\Db;
use think\facade\Session;

class Index extends Admin
{
    /**
     * 主体框架
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {

        $sidebar = $this->getSidebar();

        $group_title = '超级管理员';

        $this->assign('group_title', $group_title);
        $this->assign('admin_info', self::$admin);
        $this->assign('sidebar', $sidebar);
        return view();
    }

    /**
     * 获取侧边栏
     * @return array
     */
    private function getSidebar(){
        $list = [
            [
                'name' => '商家管理',
                'icon' => 'fa fa-codepen',
                'url' => '',
                'active' => true,
                'child' => [
                    [
                        'name' => '小说管理',
                        'icon' => 'fa fa-cog',
                        'url' => 'admin/books/index'
                    ],
                    [
                        'name' => '章节列表',
                        'icon' => 'fa fa-cog',
                        'url' => 'admin/book_chapters/index'
                    ],
                    [
                        'name' => '采集来源',
                        'icon' => 'fa fa-cog',
                        'url' => 'admin/from_sites/index'
                    ],
                ]
            ],
            [
                'name' => '系统设置',
                'icon' => 'fa fa-codepen',
                'url' => '',
                'active' => false,
                'child' => [
                    [
                        'name' => '系统设置',
                        'icon' => 'fa fa-cog',
                        'url' => 'admin/system/config'
                    ],
                    [
                        'name' => '配置工具',
                        'icon' => 'fa fa-cog',
                        'url' => 'admin/system_config/index'
                    ],
                ]
            ],
        ];

        return $list;
    }

    public function welcome(){

        return view();
    }

    /**
     * 修改密码
     */
    public function updatePassword(){

        $admin_id = Session::get('admin')['id'];

        $data = request()->post('password');
        if (empty($data)) return rJson(false, '请输入新密码！');
        $salt = rand_char();
        $password = md5(md5($data).md5($salt));

        $admin = \app\common\model\Admin::get($admin_id);
        if (empty($admin)){
            return rJson(true,  '用户不存在！');
        }
        $result = $admin->isUpdate(true)->save([
            'password' => $password,
            'salt' => $salt
        ]);

        if ($result){
            return rJson(true,  '修改成功！');
        }else{
            return rJson(false,  '修改失败！');
        }
    }


}
