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
