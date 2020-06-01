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

        echo "select * from chat_user where mobile = '". $data['params']['mobile'] ."'";
        $user = $db->query("select * from chat_user where mobile = '". $data['params']['mobile'] ."'");

        echo json_encode($user);

		// if($user){
		// 	if($user['password'] == md5(trim($data['params']['password']))){
		// 		$tmp['is_success'] = 1;
		// 		$tmp['user_id'] = $user['id'];
		// 	}else{
		// 		$tmp['is_success'] = 2;
		// 		$tmp['errmsg'] = '密码错误';
		// 	}
		// }else{
        //     //用户不存在，生成
        //     echo ("insert into chat_user(mobile,password,add_time) values('".trim($pushMSG['data']['mobile'])."','".md5(trim($pushMSG['data']['password']))."','".(date("Y-m-d H:i:s"))."')");
        //     $user_id = $db->insert("chat_user",[
        //         'mobile' => trim($pushMsg['data']['mobile']),
        //         'password' => md5(trim($pushMsg['data']['password'])),
        //         'add_time' => date("Y-m-d H:i:s")
        //     ]);

        //     $tmp['is_success'] = 1;
		// 	$tmp['user_id'] = $user_id;
		// }

		$pushMsg['data']['result'] = $tmp;

        unset($data);

		return $pushMsg;
	}
}