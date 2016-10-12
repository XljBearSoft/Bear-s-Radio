<?php
use Workerman\Worker;
use GlobalData\Server;
require './Workerman/Autoloader.php';
require_once './GlobalData/src/Server.php';
$GD = new GlobalData\Server('127.0.0.1', 2222);
Worker::runAll();