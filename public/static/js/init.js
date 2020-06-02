var config = {
	'domain' : "http://127.0.0.1:92", //域名地址
	'wsserver' : "ws://127.0.0.1:9501", //websocket地址
}

var lastHealthTime = 0; //记录最近一次心跳更新时间
var heartbeatTimer = null; //心跳执行timer
var checkTime = 6000; //心跳检查时间间隔-毫秒 10秒
var healthTimeOut = 20000; //心跳时间间隔-毫秒  20秒
var reconnectTimer = null; //重连接timer
var reconnectTime = 6000; //重连接时间10秒后