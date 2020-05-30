<?php
/**
 * 聊天数据处理类
 * ---------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author: ghfhaifeng
 * @email: ghfhaifeng@163.com
 */
namespace app;

class Chat
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

    /**
	* 建立连接
	*/
	public static function open($data){
		$pushMsg['code'] = 'open';
		$pushMsg['msg'] = 'success';
		$pushMsg['data']['mine'] = 0;
		//$pushMsg['data']['rooms'] = self::getRooms();
		//$pushMsg['data']['users'] = self::getOnlineUsers();
		unset($data);
		return $pushMsg;
    }

    //登录
	public static function doLogin($data){
        global $db;

		$pushMsg['code'] = 'login';

		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['mobile'] = $data['params']['mobile'];
		$pushMsg['data']['time'] = time();

        $tmp = [];

		$user = $db->getRow("select * from chat_user where mobile = '". $data['params']['mobile'] ."'");
		echo "登录查询用户信息，语句：".("select * from chat_user where mobile = '". $data['params']['mobile'] ."'")."\n";

		if($user){
			if($user['password'] == md5(trim($data['params']['password']))){
				$tmp['is_success'] = 1;
				$tmp['user_id'] = $user['id'];
			}else{
				$tmp['is_success'] = 2;
				$tmp['errmsg'] = '密码错误';
			}
		}else{
			$tmp['is_success'] = 3;
			$tmp['errmsg'] = '此用户不存在！';
		}

		$pushMsg['data']['result'] = $tmp;

        unset($data);

		return $pushMsg;
	}
}