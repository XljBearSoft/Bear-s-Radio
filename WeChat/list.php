<?php
require_once '../api/TencentMusicAPI.php';
$api = new TencentMusicAPI();
$content = urldecode($_GET['search']);
if(!$content)exit('Error');
$page = isset($_GET['page'])?intval($_GET['page']):'1';
if($page<=0)$page = 1;
$result = json_decode($api->search($content,20,$page-1),true);
if(isset($result['data']['song']['list'])){
  $musicList = $result['data']['song']['list'];
}else{
  exit('Error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
  <title>Bear's Radio</title>
  <style type="text/css">
    body,html{
      padding:0px;
      margin: 0px;
      background-color:#888;
    }
    li{
      list-style-type:none;
      height:60px;
      background-color:#E1E1E1;
      padding:5px;
      border-radius:10px;
      margin: 0px 10px 10px -30px;
      box-shadow:1px 1px 3px black;
    }
    .album{
      margin-left:10px;
      height:60px;
      display:inline-block;
      box-shadow:0px 0px 3px black;
    }
    .info{
      height:60px;
      display:inline-block;
      margin-left:15px;
      position: absolute;
    }
    .info>p{
      line-height:30px;
      margin:0px;
    }
    .info>p.title{
      font-size:15px;
      color:#333;
    }
    .info>p.author{
      font-size:10px;
      color:#959595;
    }
    .button{
      height:60px;
      display:inline-block;
      background-color:#606060;
      height:50px;
      border-radius:15px;
      text-align:center;
      line-height:50px;
      color:white;
      box-shadow:2px 2px #333;
      padding:2px 15px;
      cursor:pointer;
    }
    .button:active{
      background-color:#444444;
    }
    .album>img{
      height:60px;
      width:60px;
    }
    .fr{
      float:right;
      margin-right:5px;
    }
    .mb{
      margin-bottom:20px;
    }
  </style>
  <script src="../js/jquery.min.js"></script>
</head>
<body>
  <ul>
  <?php foreach ($musicList as $music) {?>
    <li>
      <div class="album">
        <img src="<?php echo "http://y.gtimg.cn/music/photo_new/T002R300x300M000{$music['albummid']}.jpg";?>"/>
      </div>
      <div class="info">
        <p class="title"><?=$music['songname']?></p>
        <p class="author">艺术家:<?=$music['singer'][0]['name']?></p>
      </div>
      <div data="<?=urlencode(base64_encode(json_encode(array($music['songmid'],$music['albummid'],$music['songname']))))?>" class="fr music button">点播</div>
    </li>
    <?php }?>
  </ul>
  <div align="center" class="Page">
    <?php if($page>1){?><div id="pre" class="mb button">上一页</div><?php }?>
    <?php if(sizeof($musicList)>1){?><div id="next" class="mb button">下一页</div><?php }else{?>
    <div id="page1" class="mb button">无结果（回到第一页）</div>
    <?php }?>
  </div>
  <form id="page" method="get">
    <input type="hidden" name="search" value="<?=$_GET['search']?>"/>
    <input type="hidden" id="page_val" name="page" value="<?=$page?>"/>
  </form>
  <form id="music" action="music.php" method="get">
    <input type="hidden" id="music_val" name="music" value=""/>
  </form>
  <script type="text/javascript">
    $(document).on("click","#next",function(){
      $("#page_val").val($("#page_val").val()-(-1));
      page.submit();
    });
    $(document).on("click","#pre",function(){
      $("#page_val").val($("#page_val").val()-1);
      page.submit();
    });
    $(document).on("click","#page1",function(){
      $("#page_val").val(1);
      page.submit();
    });
    $(document).on("click",".music.button",function(){
      $("#music_val").val($(this).attr("data"));
      music.submit();
    });
  </script>
</body>
</html>