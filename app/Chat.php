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

        $user = $db->getRow("select * from chat_user where mobile = '". $data['params']['mobile'] ."'");

		if($user){
			if($user['password'] == trim($data['params']['password'])){
				$pushMsg['data']['is_success'] = 1;
                $pushMsg['data']['user_id'] = $user['id'];
                $pushMsg['data']['errmsg'] = '登陆成功';
                $pushMsg['data']['avatar'] = $avatar;
			}else{
				$pushMsg['data']['is_success'] = 2;
				$pushMsg['data']['errmsg'] = '密码错误';
			}
		}else{
            //用户不存在，生成
            $avatar_path = rand(1,12);
            $avatar = '/public/static/images/face/f_'.$avatar_path.".jpg";
            echo ("insert into chat_user(mobile,password,add_time,avatar) values('".trim($pushMsg['data']['mobile'])."','".trim($data['params']['password'])."','".(date("Y-m-d H:i:s"))."','".$avatar."')");
            $user_id = $db->insert("chat_user",[
                'mobile' => trim($pushMsg['data']['mobile']),
                'password' => trim($data['params']['password']),
                'add_time' => date("Y-m-d H:i:s"),
                'avatar' => $avatar
            ]);

            $pushMsg['data']['is_success'] = 1;
            $pushMsg['data']['user_id'] = $user_id;
            $pushMsg['data']['errmsg'] = '登陆成功';
            $pushMsg['data']['avatar'] = $avatar;
		}

        unset($data);

		return $pushMsg;
	}
}