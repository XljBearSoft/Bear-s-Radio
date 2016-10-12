<?php
use GlobalData\Client;
if(!isset($_GET['type'])||trim($_GET['type'])=='')exit();
require '../Server/GlobalData/src/Client.php';
$global = new Client('127.0.0.1:2222');
$type = trim($_GET['type']);
switch($type){
  case "lrc":
    if($global->music['id'] == '')exit();
    require './TencentMusicAPI.php';
    $TencentMusic = new TencentMusicAPI();
    $lrc = json_decode($TencentMusic->lyric($global->music['id']),true);
    if($lrc['retcode']==0&&isset($lrc['lyric'])){
      $lrc['lyric'] = html_entity_decode($lrc['lyric'],ENT_QUOTES);
      $rp = array('&apos;'=>"'");
      foreach ($rp as $key => $value) {
        $lrc['lyric'] = str_replace($key, $value, $lrc['lyric']);
      }
      echo $lrc['lyric'];
    }
  break;
  case "album":
    $g_aid = isset($_GET['aid'])?$_GET['aid']:'';
    if($global->album_id == '' && $g_aid =='')exit();
    $aid = $g_aid==''?$global->album_id:$g_aid;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "http://y.gtimg.cn/music/photo_new/T002R300x300M000{$aid}.jpg"); 
    curl_setopt($curl, CURLOPT_REFERER, '');
    curl_setopt($curl, CURLOPT_USERAGENT, '');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    $result = curl_exec($curl);
    header('Content-type: image/JPEG');
    echo $result;
  break;
  default:
    echo 'Type Error';
}