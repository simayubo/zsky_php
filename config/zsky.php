<?php

use \think\facade\Env;

return [
    'SPHINX_SERVER' => Env::get('SPHINX_SERVER', '127.0.0.1'),
    'SPHINX_PORT' => Env::get('SPHINX_PORT', '9312'),

    'SITE_TITLE' => Env::get('SITE_TITLE', '网站名称'),
    'SITE_LOGO' => Env::get('SITE_LOGO', '/static/web/logo.png'),
    'SITE_LOGO_NAV' => Env::get('SITE_LOGO_NAV', '/static/web/nav_logo.png'),
    'SITE_KEYWORDS' => Env::get('SITE_KEYWORDS', '网站关键词'),
    'SITE_DESCRIPTION' => Env::get('SITE_DESCRIPTION', '网站介绍'),
    'SITE_COMPLAIN_EMAIL' => Env::get('SITE_COMPLAIN_EMAIL', 'admin@admin.com'),
    'SITE_HOME_WELCOME' => Env::get('SITE_HOME_WELCOME', '网站首页横幅文字'),
    'SITE_LIKE_TAGS' => Env::get('SITE_LIKE_TAGS', ''),
    'SITE_LIST_RIGHT_TEST' => Env::get('SITE_LIST_RIGHT_TEST', '电影磁力链接,电影bt种子下载,电影迅雷下载相关资源由用户搜索指令自动获取，结果来源于网络，本站不存储任何资料，亦不承担任何责任，请于24小时内删除！'),

    'SITE_FOOTER_HTML' => '<span><a href="#">纸上烤鱼</a></span> | <span><a href="#">种子搜索</a></span> | <span><a href="#">磁力链接</a></span>',
    'SITE_FOOTER_JS' => '<script charset="gbk" type="text/javascript" src="//www.baidu.com/js/opensug.js"></script><script type="text/javascript">var params = { "XOffset": 0, "YOffset": 0, "fontColor": "#444", "fontColorHI": "#000", "fontSize": "16px", "fontFamily": "arial", "borderColor": "gray", "bgcolorHI": "#ebebeb", "sugSubmit": false };BaiduSuggestion.bind("search", params);</script>',

];