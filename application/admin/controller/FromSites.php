<?php

namespace app\Admin\controller;

use app\common\controller\Admin;

class FromSites extends Admin {

    use \app\common\traits\Curd;

    public function model(){ return \app\common\model\FromSites::class; }

    public function init(){
        $this->route = 'admin/from_sites';
        $this->label = '来源网站';
        $this->function['read'] = 0;

        $this->translations = [
            'id'  => ['text' => '序号'],
            'name'  => ['text' => '网站名'],
            'url'  => ['text' => '网站url'],
            'list_title_rule'  => ['text' => '标题规则'],
            'list_link_rule'  => ['text' => '链接规则'],
            'list_remove_rule'  => ['text' => '列表移除多余元素规则', 'validate_rule' => false],
            'detail_content_rule'  => ['text' => '详情内容规则'],
            'detail_remove_rule'  => ['text' => '内容删除规则', 'validate_rule' => false],
            'detail_remove_content'  => ['text' => '内容替换字符', 'validate_rule' => false],
            'charset'  => [
                'text' => '编码',
                'type' => 'radio',
                'list'=> [
                    'gbk' => ['label label-success', 'gbk', true],
                    'utf8' => ['label label-info', 'utf8']
                ]
            ],
        ];

        $this->listFields = ['id', 'name', 'url', 'list_title_rule', 'list_link_rule', 'detail_content_rule', 'charset'];

        $this->addFormFields = ['name', 'url', 'list_title_rule', 'list_link_rule', 'list_remove_rule', 'detail_content_rule', 'detail_remove_rule', 'detail_remove_content', 'charset'];
    }
}