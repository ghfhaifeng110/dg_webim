<?php
/**
 * 公共方法类
 * ---------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author: ghfhaifeng
 * @email: ghfhaifeng@163.com
 */
namespace app;

class Common
{
    private static $instance;

    //构造函数
    public function __construct(){

    }

    public static function init(){
		if(self::$instance instanceof self){
			return false;
		}
		self::$instance = new self();
	}

    //模拟POST调用接口
    public static function curl_post($url,$curlPost){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_REFERER,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$curlPost);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    //获取当前时间戳
    public static function getTime(){
        $mtimestamp = sprintf("%.3f", microtime(true)); // 带毫秒的时间戳

        $timestamp = floor($mtimestamp); // 时间戳
        $milliseconds = round(($mtimestamp - $timestamp) * 1000); // 毫秒

        $datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        return $datetime;
    }

    //记录日志
    public static function writelog($loginfo){
        $file='socket_'.date('y-m-d').'.log';
        if(!is_file($file)){
            file_put_contents($file,'',FILE_APPEND);//如果文件不存在，则创建一个新文件。
        }
        $contents=$loginfo."\r\n";
        file_put_contents($file, $contents,FILE_APPEND);
    }
}