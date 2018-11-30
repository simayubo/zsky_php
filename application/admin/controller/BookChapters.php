<?php

namespace app\Admin\controller;

use app\common\controller\Admin;
use think\Db;

class BookChapters extends Admin {

    use \app\common\traits\Curd;

    public function model(){ return \app\common\model\BookChapters::class; }

    public function init(){
        $this->route = 'admin/book_chapters';
        $this->label = '书籍章节';
        $this->function['read'] = 0;
        $this->function['create'] = 0;
        $this->modelSize = ['x' => '80%', 'y' => '90%'];

        $this->translations = [
            'id'  => ['text' => '序号'],
            'bid'  => [
                'text' => '书籍',
                'type' => 'join',
                'data' => [
                    'table' => 'books',
                    'alias' => 'b',
                    'show_field' => 'name',
                    'show_field_alias' => 'b_name',
                    'value_field' => 'id',
                ]
            ],
            'book_chapter_id'  => ['text' => '书籍章节ID'],
            'title'  => ['text' => '章节标题'],
            'fid'  => [
                'text' => '来源',
                'type' => 'join',
                'data' => [
                    'table' => 'from_sites',
                    'alias' => 'c',
                    'show_field' => 'name',
                    'value_field' => 'id',
                ]
                ],
            'link'  => ['text' => '来源', 'type' => 'url'],
            'content'  => ['text' => '内容', 'type' => 'ueditor'],
            'collect_status'  => [
                'text' => '采集状态',
                'type' => 'radio',
                'list'=> [
                    1 => ['label label-success', '采集成功'],
                    -1 => ['label label-default', '未采集'],
                    -2 => ['label label-danger', '采集失败'],
                ]
            ],
            'collect_fail_num'  => ['text' => '失败次数'],
            'created_at'  => ['text' => '创建时间', 'type' => 'time'],
            'updated_at'  => ['text' => '更新时间', 'type' => 'time'],
            
        ];

        $this->listFields = ['id', 'bid', 'title', 'fid', 'link', 'collect_status', 'created_at', 'updated_at'];
        $this->addFormFields = ['title', 'fid', 'link', 'content'];

        $this->searchFields = ['bid', 'title', 'fid', 'collect_status'];
    }

    protected function getList($page = true)
    {
        $this->getWhere();

        $qeury = Db::name('book_chapters')->alias('_a')
            ->field(['_a.id', '_a.bid', '_a.title', '_a.fid', '_a.link', '_a.collect_status', '_a.created_at', '_a.updated_at', 'b.name as b_name', 'c.name'])
            ->leftJoin('books b', 'b.id = _a.bid')
            ->leftJoin('from_sites c', 'c.id = _a.fid')
            ->where($this->where);

        if ($page){
            return $qeury->order($this->order)->paginate($this->perPage, false, ['query' => $this->get]);
        }else{
            return $qeury->order($this->order)->select();
        }
    }
}