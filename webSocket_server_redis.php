<?php

//创建websocket服务器对象，监听0.0.0.0:9501
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
//监听WebSocket连接打开事件
    $ws->on('open', function ($ws, $request) use($redis) {
//        var_dump($request->fd, $request->get, $request->server);
        //记录连接
        $redis->sAdd('fd',$request->fd);//添加到fd集合中
        $count = $redis->sCard('fd');//统计fd
        $fds = $redis->sMembers('fd');//统计fd集合总数
        $push_data = ['count'=> $count,'message'=>'hello, welcome ☺'];
        $ws->push($request->fd,json_encode($push_data));

    });

//监听WebSocket消息事件
    $ws->on('message', function ($ws, $frame) use($redis) {
        $fds  = $redis->sMembers('fd');
        $data = json_decode($frame->data,true);
        if($data['type'] ==1 ){
            $redis->setex($frame->fd,'7200',json_encode(['fd'=>$frame->fd,'user'=>$data['user']]));
            //通知所有用户新用户上线
            $fds = $redis->sMembers('fd');
            $users=[];
            $i=0;
            foreach ($fds as $fd_on){
                $info = $redis->get($fd_on);
                $is_time = $redis->ttl($fd_on);
                if($is_time > 0){
                    $users[$i]['fd']   = $fd_on;
                    $users[$i]['name'] = json_decode($info,true)['user'];

                }else{
                    $redis->sRem('fd',$fd_on);
                }
                $i++;
            }
            foreach ($fds as $fd_on){
                $message = date('Y-m-d H:i:s',time())."<br>欢迎 <b style='color: darkmagenta ;'>".$data['user']."</b> 进入聊天室<br>";
                $push_data = ['message'=>$message,'users'=>$users];
                $ws->push($fd_on,json_encode($push_data));

            }
        }else if($data['type'] ==2){
            if($data['to_user'] == 'all'){
                foreach ($fds as $fd){//改成推送成指定的fd即可
                    if($frame->fd == $fd){
                        $message = "<p style='text-align: right'>".date('Y-m-d H:i:s',time())."<br><b style='color:blue;'> 我说:</b>  ".$data['msg']."<br></p>";
                    }else{
                        $message = date('Y-m-d H:i:s',time())."<br><b style='color: crimson'>".$data['from_user']." 说:</b>  ".$data['msg']."<br>";
                    }
                    $push_data = ['message'=>$message];
                    $ws->push($fd,json_encode($push_data));
                }
            }
        }
        echo "Message: {$frame->data}\n";

        //循环所有连接人发送内容
        //foreach($ws->connections as $key => $fd) {
        //$user_message = $frame->data;
        //$ws->push($fd, $frame->fd.'say:'.$user_message);
        //}
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
            $message = date('Y-m-d H:i:s',time())."<br><b style='color: blueviolet'>".$user."</b> 离开聊天室了<br>";
            $push_data = ['message'=>$message,'users'=>$users];
            $ws->push($fd_on,json_encode($push_data));
        }
        echo "client-{$fd} is closed\n";
    });

    $ws->start();

}else{
    echo "server is doing\n";
}


function is_login($frame){
    go(function () use ($frame)  {
        $db = new Co\MySQL();
        $server = array(
            'host' => '39.108.49.102',
            'user' => 'root',
            'password' => '123456',
            'database' => 'webdb',
        );

        $db->connect($server);
        $data = json_decode($frame->data,true);
        $user = $data['user'];

        $result = $db->query("SELECT * FROM user WHERE user_name='$user'");
        var_dump($result);
        if(!empty($result)){

            $stmt = $db->prepare('update user set fd=? where user_name=?');
            if ($stmt == false){
                echo 'fail';
                var_dump($db->errno, $db->error);
            } else {
                echo 'success';
                $ret2 = $stmt->execute([$frame->fd, $user]);
            }
            $db->close();
            return true;
        }else{
            return false;
        }
    });
}

/**
 * @param $user_id
 * 找出
 */
function send_to($user_id){
    go(function () use ($user_id){
        $db = new Co\MySQL();
        $server = array(
            'host' => '39.108.49.102',
            'user' => 'root',
            'password' => '123456',
            'database' => 'webdb',
        );

        $db->connect($server);
        $id = $db->query("SELECT fd FROM user WHERE id='$user_id'");

    });
}
