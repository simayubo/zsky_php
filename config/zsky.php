<?php

use \think\facade\Env;

return [
    'SITE_TITLE' => Env::get('SITE_TITLE', '网站名称'),
    'SITE_LOGO' => Env::get('SITE_LOGO', '/static/web/logo.png'),
    'SITE_LOGO_NAV' => Env::get('SITE_LOGO_NAV', '/static/web/nav_logo.png'),
    'SITE_KEYWORDS' => Env::get('SITE_KEYWORDS', '网站关键词'),
    'SITE_DESCRIPTION' => Env::get('SITE_DESCRIPTION', '网站介绍'),
    'SITE_HOME_WELCOME' => Env::get('SITE_HOME_WELCOME', '网站首页横幅文字'),

    'SITE_FOOTER_HTML' => '<span><a href="#">纸上烤鱼</a></span> | <span><a href="#">种子搜索</a></span> | <span><a href="#">磁力链接</a></span>',
    'SITE_FOOTER_JS' => '<script charset="gbk" type="text/javascript" src="//www.baidu.com/js/opensug.js"></script><script type="text/javascript">var params = { "XOffset": 0, "YOffset": 0, "fontColor": "#444", "fontColorHI": "#000", "fontSize": "16px", "fontFamily": "arial", "borderColor": "gray", "bgcolorHI": "#ebebeb", "sugSubmit": false };BaiduSuggestion.bind("search", params);</script>',

];