<?php
use Workerman\Worker;
use GlobalData\Client;
use \Workerman\Lib\Timer;
require './Workerman/Autoloader.php';
require './GlobalData/src/Client.php';
$global = new Client('127.0.0.1:2222');
$global->add('music', array('id'=>0,'totals'=>1,'album_id'=>0));
$global->add('currentTime', 0);
$global->add('online', 0);
$global->add('music_list',array());
$global->add('skip',0);
$global->add('play_time',0);
$global->add('list_new',array());
$music = array('id'=>0);
$ws_worker = new Worker("websocket://0.0.0.0:9532");
$ws_worker->name = "webWorker";
$ws_worker->count = 1;
$ws_worker->onWorkerStart = function($worker)use($global,&$music)
{
    Timer::add(1,function()use($worker,$global,&$music){
      if($global->music['id']!=$music['id']){
        $data = $global->music;
        $data['currentTime'] = $global->currentTime;
        $data['type'] = "music";
        unset($data['album_id']);
        unset($data['totals']);
        foreach($worker->connections as $connection)
        {
          $connection->send(json_encode($data));
        }
        $music = $global->music;
      }
      if(sizeof($global->list_new)>0){
        $MusicList = CreatePlayerListJson($global->list_new,"newlist");
        $global->list_new = array();
        foreach($worker->connections as $connection)
        {
          $connection->send($MusicList);
        }
      }
      $global->currentTime++;
      if($global->currentTime>=$global->music['totals']){//||$global->skip >= ($global->online / 2)||(sizeof($global->music_list)>0&&$global->play_time>0)){
        //$global->play_time++;
        $global->currentTime = 0;
        if(sizeof($global->music_list)>0){
          //$global->skip = 0;
          //$global->play_time = 0;
          $global->music = $global->music_list[0];
          $tempArr = $global->music_list;
          array_shift($tempArr);
          $global->music_list = $tempArr;
        }
      }
    });
};
$ws_worker->onConnect = function($connection)use($global)
{
    $data = $global->music;
    $data['currentTime'] = $global->currentTime;
    $data['type'] = "music";
    unset($data['album_id']);
    unset($data['totals']);
    $NowList = $global->music_list;
    array_unshift($NowList,$global->music);
    $MusicList = CreatePlayerListJson($NowList);
    $connection->send($MusicList);
    $connection->send(json_encode($data));
    $ol_data['type'] = "online";
    $ol_data['online'] = sizeof($connection->worker->connections);
    $global->online = $ol_data['online'];
    foreach ($connection->worker->connections as $conn) {
      $conn->send(json_encode($ol_data));
    }
};
$ws_worker->onClose = function($connection)use($global)
{
    $data['type'] = "online";
    $data['online'] = sizeof($connection->worker->connections);
    $global->online = $data['online'];
    foreach ($connection->worker->connections as $conn) {
      $conn->send(json_encode($data));
    }
};
$ws_worker->onMessage = function($connection, $data)
{
  $datas = json_decode($data,true);
  switch ($datas['type']) {
    case 'msg':
      if(trim($datas['content'])=='')return;
      $data = array('type'=>'msg','time'=>date('m-d H:i'),'content'=>htmlspecialchars($datas['content']));
      foreach ($connection->worker->connections as $conn){
        $conn->send(json_encode($data));
      }
      break;
    case 'skip':
      # code...
      break;
  }
};
Worker::runAll();
function CreatePlayerListJson($list,$type = "list"){
  foreach ($list as &$value)unset($value['src']);
  $list_data['list'] = $list;
  $list_data['type'] = $type;
  return json_encode($list_data);
}