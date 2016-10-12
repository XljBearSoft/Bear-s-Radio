<?php
use Workerman\Worker;
use GlobalData\Client;
use \Workerman\Lib\Timer;
require './Workerman/Autoloader.php';
require './GlobalData/src/Client.php';
$global = new Client('127.0.0.1:2222');
$global->add('music', array('id'=>0));
$global->add('totals', 0);
$global->add('now', 0);
$global->add('count', 0);
$global->add('people', 0);
$global->add('album_id', "");
$music = array('id'=>0);
$ws_worker = new Worker("websocket://0.0.0.0:9532");
$ws_worker->name = "webWorker";
$ws_worker->count = 1;
$ws_worker->onWorkerStart = function($worker)use($global,&$music)
{
    Timer::add(1,function()use($worker,$global,&$music){
      if($global->music['id']!=$music['id']){
        $data = $global->music;
        $data['currentTime'] = $global->now;
        $data['type'] = "music";
        foreach($worker->connections as $connection)
        {
          $connection->send(json_encode($data));
        }
        $music = $global->music;
      }
      $global->now++;
      if($global->now>=$global->totals){
        $global->count++;
        $global->now = 0;
      }
    });
    // Timer::add(5,function()use($worker,$global){
    //   $data['type'] = "online";
    //   $data['online'] = sizeof($worker->connections);
    //   $global->people = $data['online'];
    //   foreach($worker->connections as $connection)
    //   {
    //     $connection->send(json_encode($data));
    //   }
    // });
};
$ws_worker->onConnect = function($connection)use($global)
{
    $data = $global->music;
    $data['currentTime'] = $global->now;
    $data['type'] = "music";
    $connection->send(json_encode($data));
    $ol_data['type'] = "online";
    $ol_data['online'] = sizeof($connection->worker->connections);
    $global->people = $ol_data['online'];
    foreach ($connection->worker->connections as $conn) {
      $conn->send(json_encode($ol_data));
    }
};
$ws_worker->onClose = function($connection)use($global)
{
    $data['type'] = "online";
    $data['online'] = sizeof($connection->worker->connections);
    $global->people = $data['online'];
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
  }
};
Worker::runAll();