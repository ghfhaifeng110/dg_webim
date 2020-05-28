<?php
/**
 * 启动类
 * ---------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author: ghfhaifeng
 * @email: ghfhaifeng@163.com
 */

//定义根目录
define("APP_ROOT", __DIR__);

//插件目录
define('PLUGIN_PATH', APP_ROOT . '/plugins/');

//file 文件存储，mysql 数据库存储，redis 缓存存储
define("STORAGE","file");

//域名地址
define("DOMAIN","http://127.0.0.1:92");

//房间号文件
define('ONLINE_DIR',APP_ROOT.'/rooms/');

//线程个数
define('TASK_WORKER_NUM',8);

//是否开户守护进程，1是，0否
define('DAEMONIZE',0);

//开户守护进程时，日志信息保存文档
define('LOG_FILE',APP_ROOT.'/server.log');

//加载文件
require_once(APP_ROOT."/config/config.php");
// $files = glob('./config/*.php');
// foreach($files as $file){
//     require_once($file);
// }

//加载数据库服务
// require_once(APP_ROOT."/lib/core/Db.php");
// use lib\core\ConnectMysqli;
// $db = new ConnectMysqli($db_config);

//加载服务
require_once(APP_ROOT."/app/DgServer.php");
use app\DgServer;

//启动服务
$server = new DgServer();