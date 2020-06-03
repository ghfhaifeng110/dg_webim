<?php
/**
 * 服务类
 * ---------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author: ghfhaifeng
 * @email: ghfhaifeng@163.com
 */
namespace app;

use app\Common;
use app\Chat;

require_once "Common.php";
require_once "Chat.php";

class DgServer
{
    //服务对象
    private $server = null;

    /**
     * 构造方法
     */
    public function __construct()
    {
        //初始化其他类
        Common::init();
        Chat::init();

        $table = new \Swoole\Table(1024);
        $table->column('fd', \Swoole\Table::TYPE_INT);
        $table->column('user_id', \Swoole\Table::TYPE_INT);
        $table->column('data', \Swoole\Table::TYPE_STRING, 255);
        $table->create();

        //初始化
        $this->server = new \Swoole\WebSocket\Server(SOCKET_SERVER_IP,SOCKET_SERVER_PORT);

        //服务参数配置
		$this->server->set(array(
			'task_worker_num'     => TASK_WORKER_NUM, //线程个数
			'heartbeat_idle_time' => 120, //表示一个连接如果120秒内未向服务器发送任何数据，此连接将被强制关闭
			'heartbeat_check_interval' => 60, //表示每60秒遍历一次
			'daemonize' => DAEMONIZE, //是否作为守护进程
			//'log_file' => LOG_FILE,
        ));

        $this->server->table = $table;

        //客户端与服务器建立连接并完成握手后会回调此函数
        $this->server->on("Open",array($this,"onOpen"));

        //服务器收到来自客户端的数据帧
        $this->server->on("Message",array($this,"onMessage"));

        //进程任务
        $this->server->on("Task",array($this,"onTask"));

        //程投递的任务完成时
        $this->server->on("Finish",array($this,"onFinish"));

        //客户端连接关闭后
        $this->server->on("Close",array($this,"onClose"));

        //启动服务器
		$this->server->start();
    }

    /**
	* 客户端与服务器建立连接并完成握手后会回调此函数
	* $request 是一个Http请求对象，包含了客户端发来的握手请求信息
	* $fd 客户端与服务器建立连接后生成的客户端唯一ID，
	*/
    public function onOpen($server,$request)
    {
        echo Common::getTime().":open\n";

        $data = array(
			'task' => 'open', //进程任务名称
            'fd' => $request->fd,
            'msg_type' => 1, //推送方式，1单推，2群推
        );

		$this->server->task($data); //加入进程任务
    }

    /**
	* 服务器收到来自客户端的数据帧
	* $frame 是swoole_websocket_frame对象，包含了客户端发来的数据帧信息 -- json格式
	*/
    public function onMessage($server, $frame)
    {
        $data = json_decode($frame->data,true); //传送过来的信息内容
        echo Common::getTime().":onmessage:".json_encode($data)."\n";

        switch($data['type']){
            //登陆
            case 'login':
                echo Common::getTime().":登录开始-->";
				echo "登录原数据：". json_encode($data)."\n";
				$datas = array(
					'task' => 'login',
					'params' => array(
						'mobile' => $data['mobile'],
						'password' => $data['password'],
						'last_time' => $data['last_time']
					),
                    'fd' => $frame->fd,
                    'msg_type' => 2
                );

				echo Common::getTime().":登录数据提交到进程任务。\n";
				$this->server->task($datas);
                break;
            //心跳检测
            case 'ping':
                $data['fd'] = $frame->fd;
                $data['code'] = 'ping';
                $this->server->task($data);
                break;
            //发送消息
            case 'message':
                echo "消息开始-->";
				echo "消息原数据：". $frame->data ."\n";
				$data['task'] = 'message';
				$data['fd'] = $frame->fd;

				if($data['msg_type'] == 1){
					$data['receive_user_id'] = $data['receive_user_id'];
				}else{
					$data['group_id'] = $data['group_id'];
				}
				echo "消息数据提交到进程任务。\n";
				$this->server->task($data);
                break;
            //默认操作
            default:
                //默认操作，参数不全或错误
                $this->server->push($frame->fd, json_encode(array('code'=>0,'msg'=>'参数错误!')));
        }
    }

    /**
	* 进程任务
	* $task_id是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id 和 $from_id 组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
	* $from_id 来自于哪个worker进程
	* $data 是任务的内容
	*/
    public function onTask($server, $task_id, $from_id, $data)
    {
        //推送数据格式
        $pushMsg = [];

        switch($data['task']){
            //socket通道建立来接
			case 'open':
				$pushMsg = Chat::open($data);
				$this->server->push($data['fd'], json_encode($pushMsg));
                return 'Finished open';
                break;
            //登陆
			case 'login':
				echo Common::getTime()."登录进程任务中的数据：". json_encode($data,true)."\n";
                $pushMsg = Chat::doLogin($data);
                //加入table
                $ret = $this->server->table->set($data['fd'], array('user_id' =>$pushMsg['user_id'], 'fd' => $data['fd'], 'data' => json_encode($pushMsg['data'])));
                if($ret === false){
                    echo "table error \n";
                }else{
                    echo "table ok \n";
                }
                break;
            //心跳
            case 'ping':
                $this->server->push($data['fd'],json_encode($data));
                return 'Finished heart';
                break;
            //
        }

        if($pushMsg){
            echo Common::getTime()."pushMsg数据:".json_encode($pushMsg,JSON_UNESCAPED_UNICODE)."\n";
            $this->sendMsg($pushMsg,$data['fd'],$data['msg_type']);
            return "Finished";
        }
    }

    /**
	* 客户端连接关闭后
	* $fd 是连接的文件描述符
	*/
    public function onClose($server, $fd)
    {
		$pushMsg = array('code'=>0,'msg'=>'','data'=>array());

		echo "client {$fd} closed\n";
    }

    /**
	* 推送信息
	* $pushMsg 推送的内容
	* $myfd 推送者$fd
	* $msg_type 推送类型 1单推，2群推
	*/
    public function sendMsg($pushMsg,$myfd,$msg_type)
    {
        echo Common::getTime()."推送的信息：". json_encode($pushMsg)."\n";

        if($msg_type == 1){
			if($pushMsg['data']['fd']){
				$pushMsg['data']['mine'] = 1;
				$this->server->push($pushMsg['data']['fd'], json_encode($pushMsg));
			}
		}else{
            echo "msgType<>1:".json_encode($pushMsg)."\n";
            $user_fd = [];

            //先循环table，把其他人员信息发送给自己，并记录其他人员fd
			if($pushMsg['data']){
				foreach($this->server->table as $row) {
                    echo "fd:".json_encode($row)."\n";
                    // $n_data = json_decode($row['data'],true);
					if($row['fd']){
                        if($myfd != $row['fd']){
                            array_push($user_fd,$row['fd']);

                            $row['data'] = json_decode($row['data'],true);
                            $row['data']['mine'] = 0; //来自其它客户端
                            $row['code'] = $pushMsg['code'];
                            echo "msgType<>1-0:".json_encode($row)."\n";

                            $this->server->push($myfd, json_encode($row));
                        }else{
                            $pushMsg['data']['mine'] = 1; //来自其它客户端
                            $this->server->push($myfd, json_encode($pushMsg));
                        }
					}
				}
            }

            //判断其他人员信息并把当前人员信息发送给其他人
            if($user_fd){
                foreach($user_fd as $fd){
                    $pushMsg['data']['mine'] = 0; //来自其它客户端
                    echo "msgType<>1-1:".json_encode($pushMsg)."\n";

                    $this->server->push($fd, json_encode($pushMsg));
                }
            }
        }
    }

    /**
	* 进程中的任务完成时调用，发送调试信息
	* $task_id 任务ID
	* $data 内容
	*/
	public function onFinish($server, $task_id, $data){
		echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";
	}
}