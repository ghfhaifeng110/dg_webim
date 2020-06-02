$(document).ready(function(){
	face.init();
	chat.init(); //初始化
});

//msg_type:信息类型，1：一对一用户聊天
//receive_mobile:接收信息的用户手机号
var chat = {
	data : {
		wSock       : null, //socket连接
		login		: false, //是否登录
		storage     : null,
		type	    : '', //1登录标志，2发送信息标志
		fd          : 0,
		mobile      : "",
		password    : "",
		avatar      : "",
		rds         : [],//所有房间ID
		crd         : 'a', //当前房间ID
		remains     : []
    },

    //初始化聊天信息
	init:function(){
		this.off(); //取消回车键功能
		chat.data.storage = window.localStorage; //本地缓存数据
		this.ws(); //初始化websocket连接
		this.textArea(); //初始化界面
    },

    //登陆并推送信息给服务端
	doLogin:function(mobile, password){

		if(mobile == '' || password == ''){
			mobile =  $("#mobile").val();
			password = $('#password').val();
		}
		mobile = $.trim(mobile);
		password = $.trim(password);

		if(mobile == "" || password == ""){
			chat.displayError('chatErrorMessage_logout',"请输入手机号和密码才可以参与群聊哦～",1);
			return false;
		}

		//登录操作
		chat.data.type = 'login'; //登录标志
		chat.data.password = password; //密码
		chat.data.login = true;
		var json = {"type": chat.data.type,"mobile": mobile,"password": password,'roomid':'a'};
		chat.wsSend(JSON.stringify(json)); //推送登陆信息
    },

    //退出
	logout : function(){
		if(!this.data.login) return false;
		chat.data.type = 0;
		chat.data.storage.removeItem('dologin');
		chat.data.storage.removeItem('mobile');
		chat.data.storage.removeItem('password');
		chat.data.fd = '';
		chat.data.mobile = '';
		chat.data.avatar = '';
		location.reload() ;
	},
	keySend : function( event ){
		if (event.ctrlKey && event.keyCode == 13) {
			$('#chattext').val($('#chattext').val() +  "\r\n");
		}else if( event.keyCode == 13){
			event.preventDefault();//避免回车换行
			this.sendMessage();
		}
	},
	sendMessage : function(){
		if(!this.data.login) return false;
		//发送消息操作
		var text = $('#chattext').val();
		var receive_mobile = $("#receive_mobile").val();
		var receive_group = $("#receive_group").val();

		if(receive_mobile == '' && receive_group == ''){
			alert("请选择聊天对象!"); return false;
		}

		if(text.length == 0) return false;

		$("#chattext").val('');
		chat.data.type = 'message'; //发送消息标志

		//发布消息
		var params = [];
		var json;
		params['newmessage'] = text;
		params['avatar'] = chat.data.avatar;
		var markup = cdiv.render('mymessage',params);
		//$("#chatLineHolder_" + params.remains[0].mobile).append(markup);
		if(receive_mobile){
			//$("#chatLineHolder_" + receive_mobile).append(markup);
			json = {"type": chat.data.type,"mobile": chat.data.mobile,"avatar": chat.data.avatar,"message": text,"c":'text',"roomid":this.data.crd,'receive_mobile':receive_mobile,'msg_type':1};
		}else{
			//$("#chatLineHolder_" + receive_group).append(markup);
			json = {"type": chat.data.type,"mobile": chat.data.mobile,"avatar": chat.data.avatar,"message": text,"c":'text',"roomid":this.data.crd,'receive_mobile':receive_group,'msg_type':2};
		}
		this.scrollDiv('chat-lists');

		chat.wsSend(JSON.stringify(json));
		return true;
    },

    //初始化websocket连接
	ws:function(){
		this.data.wSock = new WebSocket(config.wsserver); //初始化建立SOCKET
		this.wsOpen(); //打开SOCKET连接并判断是否登陆
		this.wsMessage(); //接受服务端信息
		this.wsOnclose(); //关闭SOCKET
		this.wsOnerror(); //SOCKET ERROR
	},
	wsSend : function(data){
		this.data.wSock.send(data);
    },

    //打开SOCKET连接并判断是否登陆
	wsOpen:function (){
        //检测重连接心跳是否存在，存在就清掉
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
        }

		this.data.wSock.onopen = function(event){
            chat.print('wsopen',event);

			//判断是否已经登录过，如果登录过。自动登录。不需要再次输入昵称和邮箱
			var isLogin = chat.data.storage.getItem("dologin");
			if(isLogin){
				var mobile =  chat.data.storage.getItem("mobile");
				var password =  chat.data.storage.getItem("password");
				chat.doLogin(mobile, password); //推送登陆信息给服务端
            }

            //开启心跳检测
            var time = new Date();
            lastHealthTime = time.getTime();

            //检测心跳执行
            if (heartbeatTimer) {
                clearInterval(heartbeatTimer);
            }

            heartbeatTimer = setInterval(function() {
                chat.keepalive();
            }, checkTime);
        }
    },

    //接收服务端消息
	wsMessage:function(){
		this.data.wSock.onmessage=function(event){
			var d = jQuery.parseJSON(event.data);
			console.info(d);
			switch(d.code){
				//登录
				case 'login':
					if(d.data.result.is_success > 1){
						chat.displayError('chatErrorMessage_logout',d.data.result.errmsg,1);
					}else{
						if(d.data.mine){
							//mine=1 没有登录
							chat.data.fd = d.data.fd;
							chat.data.mobile = d.data.mobile;
							chat.data.avatar = d.data.avatar;
							chat.data.storage.setItem("dologin",1);
							chat.data.storage.setItem("mobile",d.data.mobile);
							chat.data.storage.setItem("password",chat.data.password);
							document.title = d.data.mobile + '-' + document.title;
							chat.loginDiv(d.data);

							//获取好友列表
							// if(d.data.result.friend){
							// 	var params = [];
							// 	params['fd'] = d.data.fd;
							// 	params['time'] = d.data.time;

							// 	$.each(d.data.result.friend, function(index,item){
							// 		params['mobile'] = item.mobile;
							// 		params['avatar'] = item.avatar;
							// 		chat.addFriendLine('friend',params);
							// 	});
							// }

							// //获取群组列表
							// if(d.data.result.group){
							// 	var params = [];
							// 	params['fd'] = d.data.fd;
							// 	params['time'] = d.data.time;

							// 	$.each(d.data.result.group, function(index,item){
							// 		params['name'] = item.name;
							// 		params['avatar'] = item.avatar;
							// 		params['id'] = item.group_id;
							// 		chat.addGroupLine('group',params);
							// 	});
							// }
						}else{
							chat.addChatLine('newlogin',d.data,d.data.roomid,d.code);
							console.info($("div[uname='"+d.data.mobile+"']").length);
							if($("div[uname='"+d.data.mobile+"']").length <= 0){
								//更改在线状态
								chat.addUserLine('user',d.data);
							}
                            chat.displayError('chatErrorMessage_login', d.data.result.errmsg,1);
						}
					}
					break;
			// 	//发送和接收消息
			// 	case 2:
			// 		if(d.data.mine == 0){
			// 			if($("div[id='chatLineHolder_"+ d.data.mobile +"']").length <= 0){
			// 				$("#chat-lists").append("<div class='msg-items' id='chatLineHolder_"+ d.data.mobile +"' style='display:none;'></div>");
			// 			}

			// 			chat.chatAudio();
			// 			chat.addChatLine('chatLine',d.data,d.data.roomid,d.code);
			// 			$("#chattext").val('');
			// 		} else {
			// 			// if(d.data.remains){
			// 			// 	for(var i = 0 ; i < d.data.remains.length;i++){

			// 			// 		if(chat.data.fd == d.data.remains[i].fd){
			// 			// 			chat.shake();
			// 			// 			var msg = d.data.mobile + "在群聊@了你。";
			// 			// 			chat.displayError('chatErrorMessage_logout',msg,0);
			// 			// 		}
			// 			// 	}
			// 			// }
			// 			//chat.chatAudio();
			// 			chat.addChatLine('mymessage',d.data,d.data.roomid,d.code);
			// 			//增加消息
			// 			chat.showMsgCount(d.data.roomid,'show');
			// 		}
			// 		break;
			// 	//退出
			// 	case 3:
			// 		chat.removeUser('logout',d.data);
			// 		if(d.data.mine && d.data.action == 'logout'){

			// 			return;
			// 		}
			// 		chat.displayError('chatErrorMessage_logout',d.msg,1);
            // 		break;
                //页面初始化
				case 'open':
					chat.initPage(d.data);
                    break;
                //心跳
                case 'ping':
                    var time = new Date();
                    lastHealthTime = time.getTime(); //更新客户端的最后一次心跳时间
                    break;
			// 	//其它用户退出
			// 	case 5:
			// 		if(d.data.mine){
			// 			chat.displayError('chatErrorMessage_logout',d.msg,1);
			// 		}
			// 		break;
			// 	case 6:
			// 		if(d.data.mine){
			// 			//如果是自己

			// 		} else {
			// 			//如果是其他人

			// 		}
			// 		//删除旧房间该用户
			// 		chat.changeUser(d.data);
			// 		chat.addUserLine('user',d.data);
			// 		break;
			// 	//获取最近联系记录
			// 	case 7:
			// 		if(d.data){
			// 			var params = [];
			// 			params['fd'] = d.fd;
			// 			params['time'] = d.time;

			// 			$.each(d.data,function(index,item){
			// 				params['mobile'] = item.mobile;
			// 				params['avatar'] = item.avatar;

			// 				chat.addUserLine('user',params);
			// 			});
			// 		}
			// 		break;
			    default :
					chat.displayError('chatErrorMessage_logout',d.msg,1);
			}
		}
    },

    //关闭SOCKET
	wsOnclose : function(){
		this.data.wSock.onclose = function(event){
            chat.print("websocket close:",event);
		}
    },

    //SOCKET ERROR
	wsOnerror : function(){
		this.data.wSock.onerror = function(event){
			//alert('服务器关闭，请联系QQ:1335244575 开放测试2');
		}
    },

    keepalive:function() {
        var time = new Date();
        console.log(time.getTime() - lastHealthTime);
        // if ((time.getTime() - lastHealthTime) > healthTimeOut) {
        //     console.log("心跳超时，请连接断开...");

        //     if (heartbeatTimer) {
        //         clearInterval(heartbeatTimer);

        //         //n秒后重连接
        //         chat.wsOnclose();
        //         reconnectTimer = setTimeout(function () {
        //             chat.wsOpen();
        //         }, reconnectTime);
        //     }
        // } else {
            console.log("我依然在连接状态");
            //ws.send(data);
            var json = {
                "type": 'ping'
            };
            chat.wsSend(JSON.stringify(json));
        //}
    },

	showMsgCount:function(roomid,type){
		if(!this.data.login) {return;}
		if(type == 'hide'){
			$("#message-"+roomid).text(parseInt(0));
			$("#message-"+roomid).css('display','none');
		} else {
			if(chat.data.crd != roomid){
				$("#message-"+roomid).css('display','block');
				var msgtotal = $("#message-"+roomid).text();
				$("#message-"+roomid).text(parseInt(msgtotal)+1);
			}
		}
    },

	/**
	 * 当一个用户进来或者刷新页面触发本方法
	 *
	 */
	initPage:function(data){
		//this.initRooms( data.rooms );
		//this.initUsers( data.users );
	},
	/**
	 * 填充房间用户列表
	 */
	initUsers : function( data ){
		if(getJsonLength(data)){
			for(var item in data){
				var users = [];
				var len = data[item].length;
				if(len){
					for(var i = 0 ; i < len ; i++){
						if(data[item][i]){
							users.push(cdiv.render('user',data[item][i]));
						}
					}
				}
				$('#conv-lists-' + item).html(users.join(''));
			}
		}
	},
	loginDiv : function(data){
		//获取最近联系记录
		var json = {"type": 'getChatLog',"id": data.result.user_id};
		chat.wsSend(JSON.stringify(json));

		/*设置当前房间*/
		this.data.crd = data.roomid;
		/*显示头像*/
		$('.profile').html(cdiv.render('my',data));
		$('#loginbox').fadeOut(function(){
			$('.input-area').fadeIn();
			$('.action-area').fadeIn();
			$('.input-area').focus();
			chat.textArea();
		});
    },

    //初始化界面
	textArea: function(){
		if(chat.data.login){
			$('.input-area').fadeIn();
			$('.action-area').fadeIn();
			$('.input-area').focus();
		}
	},
	changeMenu : function(obj){
		var name = $(obj).attr("data");

		$("#main-menus li").removeClass("selected");
		$(obj).addClass('selected');

		//显示列表
		$(".conv-list-pannel .conv-lists-box").hide();
		$("#" + name + "-lists").show();
	},

	// The addChatLine method ads a chat entry to the page
	addChatLine : function(t,params,roomid,code){
		var markup = cdiv.render(t,params);
		if(code == 2){
			if(params.mine){
				$("#chatLineHolder_" + params.remains[0].mobile).append(markup);
			}else{
				$("#chatLineHolder_" + params.mobile).append(markup);
			}
		}
		this.scrollDiv('chat-lists');
    },

	addUserLine : function(t,params){
		var markup = cdiv.render(t,params);
		$('#conv-lists').append(markup);
	},
	addFriendLine : function(t,params){
		var markup = cdiv.render(t,params);
		$('#friends-lists').append(markup);
	},
	addGroupLine : function(t,params){
		var markup = cdiv.render(t,params);
		$('#groups-lists').append(markup);
	},
	removeUser : function (t,params){ //type 1=换房切换，0=退出
		$("#user-"+params.fd).fadeOut(function(){
			$(this).remove();
			$("#chatLineHolder").append(cdiv.render(t,params));
		});
	},
	changeUser : function( data ){
		console.log(data);
		$("#conv-lists-"+data.oldroomid).find('#user-' + data.fd).fadeOut(function(){
			chat.showMsgCount(data.roomid,'hide');
			$(this).remove();
			//chat.addChatLine('logout',data,data.oldroomid);
		});
	},
	scrollDiv:function(t){
		var mai=document.getElementById(t);
		mai.scrollTop = mai.scrollHeight+100;//通过设置滚动高度
	},
	remind : function(obj){
		var msg = $("#chattext").val();

		$("#receive_mobile").val($(obj).attr('uname'));
		$("#receive_group").val($(obj).attr('group_id'));
		this.textArea();

		//添加或移除样式，
		$("#conv-lists div").removeClass('select');
		$(obj).addClass('select');
		//$("#chattext").val(msg + "@" + $(obj).attr('uname') + "　");

		//先隐藏所有窗口
		$("#chat-lists .msg-items").hide();

		//切换聊天内容窗口
		if($("div[id='chatLineHolder_"+ $(obj).attr('uname') +"']").length <= 0){
			$("#chat-lists").append("<div class='msg-items' id='chatLineHolder_"+ $(obj).attr('uname') +"'></div>");
		}else{
			$("div[id='chatLineHolder_"+ $(obj).attr('uname') +"']").show();
		}
	},
	list : function(obj){
		var fd = $(obj).attr("fd");
		var mobile = $(obj).attr("uname");
		var time = $(obj).find(".time").html();
		var avatar = $(obj).find(".group-logo-avatar").css("backgroundImage").replace('url(','').replace(')','');
		var params = [];

		params['fd'] = fd;
		params['mobile'] = mobile;
		params['time'] = time;
		params['avatar'] = avatar;

		$("#receive_mobile").val(mobile);
		$("#receive_group").val('');

		if($("#conv-lists div[uname='"+ mobile +"']").length <= 0){
			//在联系记录中添加用户
			chat.addUserLine('user',params);
		}

		//切换到联系记录中，并打开对应的聊天窗口
		$("#conv-lists div").removeClass('select');
		$("#conv-lists div[uname='"+ mobile +"']").addClass('select');

		//切换聊天内容窗口
		if($("div[id='chatLineHolder_"+ mobile +"']").length <= 0){
			$("#chat-lists").append("<div class='msg-items' id='chatLineHolder_"+ mobile +"'></div>");
		}else{
			$("div[id='chatLineHolder_"+ mobile +"']").show();
		}

		//切换左侧菜单
		chat.changeMenu($("#main-menus li[data='user']"));
	},

	group : function(obj){
		var fd = $(obj).attr("fd");
		var group_id = $(obj).attr("group_id");
		var name = $(obj).attr("uname");
		var time = $(obj).find(".time").html();
		var avatar = $(obj).find(".group-logo-avatar").css("backgroundImage").replace('url("','').replace('")','');
		var params = [];

		params['fd'] = fd;
		params['id'] = group_id;
		params['name'] = name;
		params['time'] = time;
		params['avatar'] = avatar;

		$("#receive_mobile").val('');
		$("#receive_group").val(group_id);

		if($("#conv-lists div[uname='"+ name +"']").length <= 0){
			//在联系记录中添加用户
			chat.addUserLine('group',params);
		}

		//切换到联系记录中，并打开对应的聊天窗口
		$("#conv-lists div").removeClass('select');
		$("#conv-lists div[uname='"+ name +"']").addClass('select');

		//切换聊天内容窗口
		if($("div[id='chatLineHolder_"+ group_id +"']").length <= 0){
			$("#chat-lists").append("<div class='msg-items' id='chatLineHolder_"+ group_id +"'></div>");
		}else{
			$("div[id='chatLineHolder_"+ group_id +"']").show();
		}

		//切换左侧菜单
		chat.changeMenu($("#main-menus li[data='user']"));
	},

	// 显示错误信息DIV
	displayError:function(divID,msg,f){
		var elem = $('<div>',{
			id		: divID,
			html	: msg
		});

		elem.click(function(){
			$(this).fadeOut(function(){
				$(this).remove();
			});
		});
		if(f){
			setTimeout(function(){
				elem.click();
			},5000);
		}
		elem.hide().appendTo('body').slideDown();
	},
	chatAudio : function(){
		if ( $("#chatAudio").length <= 0 ) {
			$('<audio id="chatAudio"><source src="./static/voices/notify.ogg" type="audio/ogg"><source src="./static/voices/notify.mp3" type="audio/mpeg"><source src="./static/voices/notify.wav" type="audio/wav"></audio>').appendTo('body');
		}
		$('#chatAudio')[0].play();
	},
	shake : function(){
		$("#layout-main").attr("class", "shake_p");
		var shake = setInterval(function(){
			$("#layout-main").attr("class", "");
			clearInterval(shake);
		},200);
    },

    //取消回车键功能
	off : function(){
		document.onkeydown = function (event){
			if ( event.keyCode==116){
				event.keyCode = 0;
				event.cancelBubble = true;
				return false;
			}
		}
    },

    //打印输出信息
	print:function(flag,obj){
		//console.log('----' + flag + ' start-------');
		console.log(obj);
		//console.log('----' + flag + ' end-------');
	}
}
