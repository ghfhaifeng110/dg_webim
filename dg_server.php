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
define('PLUGIN_PATH', __DIR__ . '/plugins/');

//加载文件
// require_once(APP_ROOT."/config/*.php");
// $files = glob(APP_ROOT.'/config/*.php');
// foreach($files as $file){
//     require_once($file);
// }
require_once(APP_ROOT."/app/DgServer.php");

//启动服务
$server = new DgServer();