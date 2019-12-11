<?php

?>
<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>websoket</title>
</head>
<body style="height: 900px" >

<div style="background: #999999;text-align: center;height: 100%;">
    <h2 style="text-align: center">online chat</h2>
    测试账号admin ,user1 ,user2

<form action="" method="post" style="display: inline-block;overflow:auto;margin-top: 180px;" name="form">
输入用户名<input type="text" name="name" value="" style="height: 25px" id="name">
        <input type="submit" value="提交" style="height: 30px">

</form>
</div>

</body>

<script>
    var user_input = document.getElementById('name');
    user_input.onkeypress = function (){
        if(event.keyCode == 13){
            form.submit();
        }
    }
    if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
        document.form.action='chat.php';
    } else {
        document.form.action='chat_pc.php';
    }
</script>
</html>