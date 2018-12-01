# zsky_php
纸上烤鱼PHP前端

### 注意
1. 没有后台，只有前台
2. 除了搜索的url路由和原版Python不一致，其他的都一致（尽力了，这tp自定义路由接收参数搞不明白，符号都识别不了）
3. Sphinx还是用的原版，只是调用了API(只在搜索时用到了)
4. 所有配置均在 `.env` 文件中
5. 首页统计、搜索词、标签、周排行、最新列表 都做了不定期文件缓存，删除 `runtime/cache` 文件夹即可清除缓存 
6. 建议把 `search_hash` 表 `create_time`和`requests`字段 增加索引

### 安装方法

1. 拉取代码：
`git clone git@github.com:simayubo/zsky_php.git`
2. 进入目录：
`cd zsky_php`
3. 安装依赖包：
`composer install`
4. 修改配置：
将`.env.dev`文件修改为`.env`，并修改其中参数
5. 配置apache或nginx根目录指向`public`文件夹
6. 配置伪静态：
  - Nginx:
  ```
  location / {
  	if (!-e $request_filename){
  		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
  	}
  }
  ```
  - Apache:
  ```apacheconfig
    <IfModule mod_rewrite.c>
      Options +FollowSymlinks -Multiviews
      RewriteEngine On
    
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteRule ^(.*)$ index.php?/$1 [QSA,PT,L]
    </IfModule>
  ```

