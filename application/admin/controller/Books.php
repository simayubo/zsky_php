<?php

namespace app\Admin\controller;

use app\common\controller\Admin;

class Books extends Admin {

    use \app\common\traits\Curd;

    public function model(){ return \app\common\model\Books::class; }

    public function init(){
        $this->route = 'admin/books';
        $this->label = '书籍列表';
        $this->function['read'] = 0;

        $this->translations = [
            'id'  => ['text' => '序号'],
            'name'  => ['text' => '书籍名', 'type' => 'alias', 'alias' => 'b_name'],
            'images'  => ['text' => '封面', 'type' => 'image'],
            'author'  => ['text' => '作者'],
            'fid'  => [
                'text' => '来源',
                'type' => 'join',
                'data' => [
                    'table' => 'from_sites',
                    'alias' => 'b',
                    'show_field' => 'name',
                    'show_field_alias' => 't_name',
                    'value_field' => 'id',
                ]
            ],
            'tid'  => [
                'text' => '类型',
                'type' => 'join',
                'data' => [
                    'table' => 'book_types',
                    'alias' => 'c',
                    'show_field' => 'name',
                    'value_field' => 'id',
                ]
                ],
            'desc'  => ['text' => '介绍', 'type' => 'textarea'],
            'is_hot'  => ['text' => '是否热门'],
            'serialize'  => [
                'text' => '是否完本',
                'type' => 'radio',
                'list'=> [
                    -1 => ['label label-success', '连载', true],
                    1 => ['label label-info', '完本']
                ]
            ],
            'sort'  => ['text' => '排序', 'default' => 0],
            'created_at'  => ['text' => '添加时间', 'type' => 'time'],
            'list_url'  => ['text' => '章节列表URL', 'type' => 'url'],
            'absolute_url'  => ['text' => '章节详情替换域名', 'type' => 'url'],
        ];

        $this->listFields = ['id', 'name', 'images', 'author', 'fid', 'tid', 'serialize', 'sort', 'created_at'];

        $this->addFormFields = ['name', 'images', 'author', 'fid', 'tid', 'desc', 'list_url', 'absolute_url', 'serialize', 'sort'];
    }
}