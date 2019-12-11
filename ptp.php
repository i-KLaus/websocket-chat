<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 18-8-24
 * Time: 下午4:11
 */
//创建websocket服务器对象，监听0.0.0.0:9999端口


use Swoole\Coroutine\Redis;

$mgr_cli = new swoole_client( SWOOLE_SOCK_TCP );
    $isNotWorking = @!$mgr_cli->connect( '39.108.49.102', 9501, 0.1 );
    if($isNotWorking){
        $ws = new swoole_websocket_server("0.0.0.0", 9501);
        $redis = new \Redis();
        $redis->connect('39.108.49.102', 6379);
        $redis->auth('abc123');

        $ws->set(array(
//        'daemonize' => true,
            'worker_num'      => 1,
        ));

        $ws->on('open', function ($ws, $request) use($redis) {

//            $push_data = ['count'=> 1,'message'=>'hello, welcome ☺'];
//            $ws->push($request->fd,json_encode($push_data));

        });


        $ws->on('message', function ($ws, $frame)  {

            go(function () use ($ws,$frame){
                $server_info = $ws->getClientInfo($frame->fd);
                $redis = new Swoole\Coroutine\Redis();
                $redis->connect('39.108.49.102', 6379);
                $redis->auth('abc123');

                $data = json_decode($frame->data,true);
                if($data['type'] ==1 ){
                    $id = is_login($frame,$server_info['remote_ip']);//发送者id
                    $redis->sAdd('users',$data['user']);//添加到users集合中

//                    $redis_data = $redis->get($data['user']);
//                    $fd_on = json_decode($redis_data,true)['fd'];
//
//                    $users = json_decode($redis_data,true)['name'];
                      friedns_list($redis,$ws,$frame,$data);
                      $ws->push($frame->fd,json_encode(['id'=>$id]));

//                    $message = msg_generate($data['type'],$frame,$data,$fd_on);
//                    $push_data = ['message'=>$message,'users'=>$users];
//                    $ws->push($fd_on,json_encode($push_data));

                }else if($data['type'] ==2){
                    if($data['to_user'] == 'all'){//to_user即用户id
                        foreach ($fds as $fd){//改成推送成指定的fd即可
                            $message = msg_generate($data['type'],$frame,$data,$fd);
                            $push_data = ['message'=>$message];
                            $ws->push($fd,json_encode($push_data));
                        }
                    }else{
                        //信息接收方
                        $fd = send_to($data['to_user'],$data['msg'],$data['from_user_id']);
                        $message = msg_generate($data['type'],$frame,$data,$fd);
                        $push_data = ['message'=>$message];
                        $ws->push($fd,json_encode($push_data));
                        //self接收方
                        $message = msg_generate($data['type'],$frame,$data,$frame->fd);
                        $push_data = ['message'=>$message];
                        $ws->push($frame->fd,json_encode($push_data));
                    }
                }
            });

        });

        //监听WebSocket连接关闭事件
        //关闭时删除db中的fd字段
        $ws->on('close', function ($ws, $fd) use ($redis){
            $redis->sRem('fd',$fd);
            $fds = $redis->sMembers('fd');
            $i=0;$users=[];
            foreach ($fds as $fd_on){
                $info = $redis->get($fd_on);
                $is_time = $redis->ttl($fd_on);
                if($is_time){
                    $users[$i]['fd']   = $fd_on;
                    $users[$i]['name'] = json_decode($info,true)['user'];
                }else{
                    $redis->sRem('fd',$fd_on);
                }
                $i++;
            }
            foreach ($fds as $fd_on){
                $user = json_decode($redis->get($fd),true)['user'];
                $message = date('Y-m-d H:i:s',time())."<br><b style='color: blueviolet'>".$user."</b> 已下线<br>";
                $push_data = ['message'=>$message,'users'=>$users];
                $ws->push($fd_on,json_encode($push_data));
            }
            echo "client-{$fd} is closed\n";
        });

        $ws->start();

    }else{
        echo "server is doing\n";
    }




function is_login($frame,$ip){
        $db = new Co\MySQL();
        $server = array(
            'host' => '39.108.49.102',
            'user' => 'root',
            'password' => '123456',
            'database' => 'webdb',
        );

        $redis = new Swoole\Coroutine\Redis();
        $redis->connect('39.108.49.102', 6379);
        $redis->auth('abc123');

        $db->connect($server);
        $data = json_decode($frame->data,true);
        $user = $data['user'];

        $result = $db->query("SELECT * FROM user WHERE user_name='$user'");

        if(!empty($result)){

            $stmt = $db->prepare('update user set fd=? ,last_login_ip=? where user_name=?');
            if ($stmt == false){
                var_dump($db->errno, $db->error);
            } else {
                $ret2 = $stmt->execute([$frame->fd,$ip, $user]);
                $user_data = json_encode(['fd'=>$frame->fd,'name'=>$user,'id'=>$result[0]['id']]);
                $redis->set($user,$user_data);
            }
            $db->close();
            return $result[0]['id'];
        }else{
            return false;
        }
}

/**
 * @param $user_id 接收方id
 * @param $from_user_id 发送方
 * return 接收方的fd
 */
function send_to($user_id,$msg,$from_user_id){
        $db = new Co\MySQL();
        $server = array(
            'host' => '39.108.49.102',
            'user' => 'root',
            'password' => '123456',
            'database' => 'webdb',
        );

        $db->connect($server);
        $data = $db->query("SELECT fd FROM user WHERE id='$user_id'");

        if(!empty($data)){
            if($data[0]['fd']==-1){
                //todo
                //离线消息入库,推送到消息队列mysql,redis
                save_msg($msg,$user_id,$from_user_id,$data[0]['fd'],$db);
            }else{
                //todo
                //在线消息入库，发送消息，db,websocket
                save_msg($msg,$user_id,$from_user_id,$data[0]['fd'],$db);
                $db->close();
                return $data[0]['fd'];
            }
        }
}

/**
 * @param $type
 * @param $frame
 * @param $data
 * @param $fd
 * @return string
 * 模板消息生成
 */
function msg_generate($type,$frame,$data,$fd){
    if($type == 1){
        $message = date('Y-m-d H:i:s',time())."<br>您的好友 <b style='color: darkmagenta ;'>".$data['user']."</b> 上线啦！<br>";
    }elseif ($type == 2){
        if ($frame->fd == $fd) {
            $message = "<p style='text-align: right'>" . date('Y-m-d H:i:s', time()) . "<br><b style='color:blue;'> 我说:</b>  " . $data['msg'] . "<br></p>";
        } else {
            $message = date('Y-m-d H:i:s', time()) . "<br><b style='color: crimson'>" . $data['from_user'] . " 说:</b>  " . $data['msg'] . "<br>";
        }
    }else{
        $message ='';
    }
    return $message;
}

/**
 * 消息入库
 */
function save_msg($msg,$recever_id,$sender_id,$fd,$db){
    $stmt = $db->prepare('insert into msg (sender_id,receiver_id,msg,status,create_time) values (?,?,?,?,?)');
    if ($stmt == false){
        var_dump($db->errno, $db->error);
    } else {
        $status = ($fd == -1)?'$fd':1;
        $stmt->execute([$sender_id, $recever_id,$msg,$status,time()]);
    }


}


/**
 * 在线好友列表生成
 */
function friedns_list($redis,$ws,$frame,$data){
    $users=[];
    $i=0;
    $fds  = $redis->sMembers('users');
    foreach ($fds as $fd_on){
        $info = $redis->get($fd_on);
//        $is_time = $redis->ttl($fd_on);
        //todo
        //判断在线用户
        if(1){//
            $users[$i]['id']   = json_decode($info,true)['id'];
            $users[$i]['fd']   = json_decode($info,true)['fd'];;
            $users[$i]['name'] = json_decode($info,true)['name'];
        }else{
            $redis->sRem('fd',$fd_on);
        }
        $i++;
    }
    foreach ($users as $user){
        $message = msg_generate($data['type'],$frame,$data,$user['fd']);
        $push_data = ['message'=>$message,'users'=>$users];
        $ws->push($user['fd'],json_encode($push_data));
    }
}