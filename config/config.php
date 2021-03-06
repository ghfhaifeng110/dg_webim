<?php
/**
 * 数据库配置文件
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author: ghfhaifeng
 * @email: ghfhaifeng@163.com
 */

//数据库配置
global $db,$db_config;
$db_config = array(
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'user'      => 'db_chat',
    'pass'      => '123456',
    'db'        => 'db_chat',
    'charset'   => 'utf8',
);

//参数配置
define("SOCKET_SERVER_IP","127.0.0.1"); //服务器IP地址
define("SOCKET_SERVER_PORT",9501); //socket服务器端口

$socket_mysql_port = 9508; //socket数据库端口