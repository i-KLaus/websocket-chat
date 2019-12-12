<?php
/**
 * Created by PhpStorm.
 * User: lzq
 * Date: 2019/12/10
 * Time: 14:14
 */

/**
 * @param $user_id
 * 查询接收方的fd，若不在线则fd为-1
 * 不在线测消息推送到消息队列
 * 将消息先存入数据库，获取该条数据id，插入到redis队列中
 * 在线则发送消息给接收方
 */
function send_to($user_id){
    $id = go(function () use ($user_id){
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
            if($data[0]['fd']!=-1){
                //todo
                //离线消息入库,推送到消息队列mysql,redis

            }else{
                //todo
                //在线消息入库，发送消息，db,websocket
                return $data[0]['fd'];

            }
        }
    });
    echo $id;
    return $id;
}


/**
 * @param $user_id
 * 离线消息接收，消费redis离线消息队列，更改msg中未读消息状态
 */
function offline_msg_send($user_id){

}


$server = new Swoole\WebSocket\Server("0.0.0.0", 9501);
$server->set(array(
    'heartbeat_idle_time' => 9, // 一个连接如果9秒内未向服务器发送任何数据，此连接将被强制关闭
    'heartbeat_check_interval' => 5, // 每5秒遍历一次
));

$server->on('open', function (Swoole\WebSocket\Server $server, $request) {
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (Swoole\WebSocket\Server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    $server->push($frame->fd, "this is server");
});

$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();