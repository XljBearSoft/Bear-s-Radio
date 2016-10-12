<?php
use GlobalData\Client;
require_once '../Server/GlobalData/src/Client.php';
$global = new Client('127.0.0.1:2222');
$played = false;
if(isset($_POST['mid'])){
    $inpost = true;
    require_once '../api/TencentMusicAPI.php';
    $Mid = $_POST['mid'];
    $Aid = $_POST['aid'];
    if($Mid!=''&&$Aid!=''){
        $Tapi = new TencentMusicAPI();
        if($global->music['id'] == $Mid){
            $played = true;
        }else{
            $music = json_decode($Tapi->detail($Mid),true);
            $musicUrl = json_decode($Tapi->url($Mid),true);
            if(isset($music['data'])){
                $musicD['id'] = $Mid;
                $musicD['src'] = $musicUrl['320mp3'];
                $musicD['song'] = $music['data'][0]['title'];
                $musicD['author'] = $music['data'][0]['singer'][0]['name'];
                $global->music = $musicD;
                $global->totals = $music['data'][0]['interval'];
                $global->now = 0;
                $global->count = 0;
                $global->album_id = $Aid;
                $played = true;
            }else{
                $played = false;
            }
        }
    }
}else{
    $Data = json_decode(base64_decode(urldecode($_GET['music'])),true);
    if(!$Data)exit('Error');
    if($global->music['id'] == $Data[0]){
        $played = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
    <title>Bear's Radio</title>
    <style type="text/css">
        .button{
            background-color:#06C0EC;
            width:100%;
            height:60px;
            border-radius:15px;
            margin-top: 15px;
            text-align:center;
            line-height:60px;
            color:white;
            box-shadow:2px 2px #333;
        }
        .button:active{
            background-color:#2791AA;
        }
        .disable{
            background-color:#1E2021;
            color:#555;
        }
        .disable:hover{
            background-color:#1E2021;
            color:#555;
        }
        body{
            background-color:#888;
        }
        p{
            color:white;
        }
    </style>
</head>
<body>
    <p>当前播放曲目: <?=$global->music['song']?></p>
    <p>艺术家: <?=$global->music['author']?></p>
    <p>当前有<?=$global->people?>位用户在线</p>
    <form id="form" method="post">
        <?php if($played){?>
            <div class="button disable">当前正在播放 《<?=$global->music['song']?>》</div>
        <?php }else{?>
            <input type="hidden" name="mid" value="<?=$Data[0]?>" />
            <input type="hidden" name="aid" value="<?=$Data[1]?>" />
            <div class="button" onclick="form.submit();">立刻点播 《<?=$Data[2]?>》</div>
        <?php }?>
    </form>
</body>
</html>