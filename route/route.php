<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;

Route::any('/search', 'index/index/search');
Route::get('/main-search-kw-:keyword-:type-:page', 'index/index/searchResult');
Route::get('/main-search-kw-:keyword-:page', 'index/index/searchResult');
Route::get('/hash/:hash', 'index/index/detail');
Route::get('/tag', 'index/index/tag');
Route::get('/weekhot', 'index/index/weekhot');
