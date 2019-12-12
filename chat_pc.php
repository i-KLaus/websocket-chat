<?php
$user = $_POST['name'];
if(!$user){
    header('Location:http://www.chat.com/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>chat online-:<?php echo $user;?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <link rel="stylesheet" href="static/css/chat.css" type="text/css">
<!--    <script type="text/javascript" src="static/js/chat.js"></script>-->
</head>
<body>
<div class="all">
    <div class="chat_index">
        <!--banner-->
        <div class="chat_banner">
            <h1 style="text-align: center">点击左侧好友选择发送</h1>
        </div>

        <div class="chat_body">
            <!--在线列表-->
            <div class="chat_online">
                <!--搜索-->
                <div class="search_online">
                    <form>
                        <input type="text" placeholder="" readonly value="online user">
                    </form>
                </div>
                <div class="online_friend">
                    <ul id="user_list">
                        <?php
                        $servername = "39.108.49.102";
                        $username = "root";
                        $password = "123456";
                        $dbname = "webdb";

                        // 创建连接
                        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $stmt = $conn->query("select * from user");

                        $i = 0;
                        while ($row = $stmt->fetch())
                        {
                            $users[$i]['name'] = $row['user_name'];
                            $users[$i]['id'] = $row['id'];
                            $i++;
                        }

                        $html='';
                        if(!empty($users)){
                            foreach ($users as $key=>$value){
                                if($value['name']!=$user){
                                    $html.= "<li class='abc'> <div class='a_friend'><div class=''><div class='head_text'>".$value['name']."</div></div><input  type='hidden' "."value=".$value['id'].">";
                                    $html.= "</li>";
                                }
                            }
                        }
                        echo $html;
                        ?>
                    </ul>
                </div>

            </div>
            <!--聊天界面-->
            <div class="chat_main">
                <div class="chat_div" id="div">
                    <ul id="chat_ul" class="chat_content">

                    </ul>

                </div>

                <div class="send_message">
                        <input type="text" placeholder="请输入消息" id="send_txt">
                        <input type="button" value="enter" id="send_btn">
                </div>
            </div>
            <!--名片-->
            <input type="hidden" value="" id="receiver_id">
            <input type="hidden" value="" id="sender_id">
            <div class="chat_namecard">

            </div>
        </div>

    </div>
</div>
</body>
<script src="./static/js/jquery-1.8.2.min.js"></script>
<script>
    var wsServer = 'ws://39.108.49.102:9501/chat';
    var websocket = new WebSocket(wsServer);

    websocket.onopen = function (evt) {
        console.log("Connected to WebSocket server.");
        websocket.send('{"user":"<?php echo $user;?>" ,"type":"1"}')
    };

    websocket.onclose = function (evt) {
        console.log("Disconnected");
    };

    websocket.onmessage = function (evt) {

        var data = eval('(' + evt.data + ')');
        var message = data.message;
        var count = data.count;
        var id = data.id
        //发送者id
        // console.log(data);
        if(data.users){
            // var users = data.users,html='';
            // for(var i=0;i<users.length;i++){
            //     html+= "<li class='abc'> <div class='a_friend'><div class=''><div class='head_text'>"+users[i].name+"</div></div>"+
            //             "<input  type='hidden' value="+users[i].id+"></div></li>"
            // }
            // $('#user_list').html(html);
        }
        if(id){
            $("#sender_id").val(id)
        }
        if(count){
            $('#chat_ul').append(count+"people online"+message+"<br>");
        }else if(message){
            $('#chat_ul').append(message+"<br>");
        }


        $('#chat_ul').scrollTop($('#chat_ul')[0].scrollHeight);
        // document.getElementById('div').style.background = evt.data;
        // console.log('Retrieved data from server: ' + evt.data);
    };

    websocket.onerror = function (evt, e) {
        console.log('Error occured: ' + evt.data);
    };

    var send_input = document.getElementById('send_txt');

    send_input.onkeydown = function (){
        if(event.keyCode == 13){
            var receiver_id =  $("#receiver_id").val()
            var sender_id =   $("#sender_id").val()
            console.log(receiver_id)
            console.log(sender_id)
            sendMassage(receiver_id,sender_id);//推送给所有人all，推送给指定人sid，发送方id
        }
    }

    $("body").on('click', '.abc', function(){
        sid = $(this).find('input').val();
        $("#receiver_id").val(sid)
    })

    function sendMassage(receiver_id,sender_id){
        var massage=document.getElementById('send_txt').value;
        if(massage){
            var msg = '{"type":"2","msg":"'+massage+'","from_user":"<?php echo $user;?>","to_user":"'+receiver_id+'","from_user_id":"'+sender_id+'"}';

            websocket.send(msg);
            $('#send_txt').val('');
        }else{
            alert('请不要惜字如金')
        }
    }


</script>

</html>
