<?php
use GlobalData\Client;
require_once '../Server/GlobalData/src/Client.php';
require_once '../api/TencentMusicAPI.php';
 class Wechat{
    private $Web = "http://music.xljbear.com/WeChat/";
    //签名
    private $token = '';
    //消息类型
    private $msgtype;
    //消息内容
    private $msgobj;
    //事件类型
    private $eventtype;
    //事件key值
    private $eventkey;
    #{服务号才可得到
    //AppId
    private $appid = "xxx";
    //AppSecret
    private $secret = "xxx";
    #}
    private $global = null;
    private $_isvalid = false;
    private $Tapi = null;

    public function __construct($token,$isvalid = false){
        $this->token = $token;
        $this->_isvalid = $isvalid;
        $this->global = new Client('127.0.0.1:2222');
        $this->Tapi = new TencentMusicAPI();
    }
    public function index(){
        if($this->_isvalid){
            $this->valid();
        }
        $this->getMsg();
        $this->responseMsg();
    }
    private function valid(){
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit();
        }
    }
    private function getMsg(){
        if(!$this->checkSignature()){
            exit();
        }
        $poststr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if(!empty($poststr)){
            $this->msgobj = simplexml_load_string($poststr,'SimpleXMLElement',LIBXML_NOCDATA);
            $this->msgtype = strtolower($this->msgobj->MsgType);
        }
        else{
            $this->msgobj = null;
        }
    }
    private function responseMsg(){
        switch ($this->msgtype) {
            case 'text':
                $content = trim($this->getData($this->msgobj->Content));
                if(empty($content) || !is_array($content)){
                    switch ($content) {
                        case '正在播放':
                            $data = "当前正在播放的是:\n";
                            $data .= "艺术家：{$this->global->music['author']}\n\n曲名：{$this->global->music['song']}\n\n";
                            $data .= "有{$this->global->people}人正在欣赏！\n";
                            $count = $this->global->count + 1;
                            $data .= "循环播放第{$count}遍中...";
                            break;
                        default:
                            $music = json_decode($this->Tapi->search($content),true);
                            if(isset($music['data']['song']['list'])&&sizeof($music['data']['song']['list'])>0){
                              $musicList = $music['data']['song']['list'];
                              if(sizeof($musicList)>10){
                                for($i=0;$i<9;$i++){
                                  $d['title']= $musicList[$i]['songname'] . ' - ' . $musicList[$i]['singer'][0]['name'];
                                  $d['summary'] = $musicList[$i]['singer'][0]['name'];
                                  $d['picurl'] = "http://y.gtimg.cn/music/photo_new/T002R300x300M000{$musicList[$i]['albummid']}.jpg";
                                  $d['url'] = $this->Web . 'music.php?music=' . urlencode(base64_encode(json_encode(array($musicList[$i]['songmid'],$musicList[$i]['albummid'],$musicList[$i]['songname']))));
                                  $data2[] = $d;
                                }
                                $d['title']= "查看更多相关歌曲>>";
                                $d['picurl'] = "";
                                $d['url'] = $this->Web . 'list.php?search=' . urlencode($content);
                                $data2[] = $d;
                              }else{
                                foreach ($musicList as $value) {
                                  $d['title']= $value['songname'] . ' - ' . $value['singer'][0]['name'];
                                  $d['summary'] = $value['singer'][0]['name'];
                                  $d['picurl'] = "http://y.gtimg.cn/music/photo_new/T002R300x300M000{$value['albummid']}.jpg";
                                  $d['url'] = $this->Web . 'music.php?music=' . urlencode(base64_encode(json_encode(array($value['songmid'],$value['albummid'],$value['songname']))));
                                  $data2[] = $d;
                                }
                              }
                              $this->newsMsg($data2);
                              exit();
                            }else{
                                $data = "很抱歉呢，找不到该歌曲的任何信息！";
                            }
                            break;
                    }
                    $this->textMsg($data);
                }
                else{
                    $this->newsMsg($data);
                }
                break;
            case 'event':
                $this->eventOpt();
                break;
            default:
                # code...
                break;
        }
    }
    private function textMsg($content=''){
        $textxml = "<xml><ToUserName><![CDATA[{$this->msgobj->FromUserName}]]></ToUserName><FromUserName><![CDATA[{$this->msgobj->ToUserName}]]></FromUserName><CreateTime>".time()."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
        if(empty($content)){
            $content = "功能正在开发中...";
        }
        $resultstr = sprintf($textxml,$content);
        echo $resultstr;
    }
    private function newsMsg($data){
        if(!is_array($data)){
            exit();
        }
        $newscount = (count($data) > 10)?10:count($data);
        $newsxml = "<xml><ToUserName><![CDATA[{$this->msgobj->FromUserName}]]></ToUserName><FromUserName><![CDATA[{$this->msgobj->ToUserName}]]></FromUserName><CreateTime>".time()."</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>{$newscount}</ArticleCount><Articles>%s</Articles></xml>";
        $itemxml = "";
        foreach ($data as $key => $value) {
            $itemxml .= "<item>";
            $itemxml .= "<Title><![CDATA[{$value['title']}]]></Title><Description><![CDATA[{$value['summary']}]]></Description><PicUrl><![CDATA[{$value['picurl']}]]></PicUrl><Url><![CDATA[{$value['url']}]]></Url>";
            $itemxml .= "</item>";
        }
        $resultstr = sprintf($newsxml,$itemxml);
        echo $resultstr;
    }
    /**
     *  事件处理
     */
    private function eventOpt(){
        $this->eventtype = strtolower($this->msgobj->Event);
        switch ($this->eventtype) {
            case 'subscribe':
                $content = "欢迎来到大熊点歌台！\n发送歌名来进行点歌！";
                $this->textMsg($content);
                break;
            case 'unsubscribe':
                
                //做用户取消绑定的处理
                break;
            default:
                # code...
                break;
        }
    }
    private function getData($key='ruiblog'){
        $data = $key;
        return $data;
    }
    private function checkSignature(){
        return true;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr,SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        return ($tmpStr == $signature)?true:false;
    }
    /**
     *  获取access token
     */
    private function getAccessToken(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->secret";
        $atjson=file_get_contents($url);
        $result=json_decode($atjson,true);//json解析成数组
        if(!isset($result['access_token'])){
            exit( '获取access_token失败！' );
        }
        return $result["access_token"];
    }
 }
 ?>