<?php

namespace app\common\command;

use think\console\command\Make;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;

class Curd extends Make
{
    protected $type = "Curd";
    protected $model = '';
    protected $module = '';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:curd')
            ->addOption('plain', null, Option::VALUE_NONE, 'Generate an curd class. Model name --module name')
            ->addOption('module', null, Option::VALUE_REQUIRED, 'module name')
            ->setDescription('Create a new resource curd class. Model name --module name');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->module = $input->getOption('module');
        if (empty($this->module)){
            $this->module = 'Admin';
        }
        return parent::execute($input, $output);
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/curd.stub';
    }
    protected function getNamespace($appNamespace, $module)
    {
        return "app\\".$this->module."\\controller";
    }

    protected function buildClass($name)
    {
        $stub = file_get_contents($this->getStub());
        $namespace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
        $class = str_replace($namespace . '\\', '', $name);
        $translations = "";
        $str = explode('/', $class);
        $str_count = count($str);
        $_name = $str[$str_count-1];
        $route = trim(preg_replace_callback('/([A-Z]{1})/',function($matches){
            return '_'.strtolower($matches[0]);
        },$_name), '_');

        $db_name = config("database.database"); //数据库名
        $table_name = config("database.prefix").$route; //表名
        $route = $this->module.'/'.$route; //路由

        $table = Db::query('show full columns from '.$table_name);
        $table_zhushi = Db::query("Select table_name , table_comment from INFORMATION_SCHEMA.TABLES Where table_schema = '{$db_name}' AND table_name LIKE '{$table_name}'");

        $field = '';
        foreach ($table as $item) {
            $field .= "'".$item['Field']."', ";
            $translations .= "'".$item['Field']."'  => ['text' => '".$item['Comment']."'],
            ";
        }
        $field = trim($field, ',');
        $translations = trim($translations, "\r\n");
        system('php think make:model '.$_name);
        return str_replace(['{%className%}', '{%namespace%}', '{%app_namespace%}', '{%translations%}', '{%route%}', '{%model%}', '{%field%}', '{%label%}', '{%module%}'], [
            $class,
            $namespace,
            \config('app_namespace'),
            $translations,
            $route,
            $_name,
            $field,
            $table_zhushi[0]['table_comment'],
            $this->module
            ], $stub);
    }
}