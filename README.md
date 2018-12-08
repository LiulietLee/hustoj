# hustoj
> 当前郑州大学在线测评系统

### 关于本地测试环境搭建（生产环境搭建出门右转 Wiki 部分）
- 把 hustoj/trunk/install/db.sql 导入到本地 mysql 数据库里
- 在 hustoj/trunk/web/include/db_info.inc.php 里把数据库的用户和密码填一下
- 在 db_info.inc.php 文件里加一行 `static $OJ_VCODE_SUBMIT_CODE = false;`
- cd 到 web 目录下执行 `php -S 127.0.0.1:8000` 命令
- 浏览器里输入 127.0.0.1:8000 即可访问
