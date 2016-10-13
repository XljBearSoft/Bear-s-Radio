var ServerAddress = "ws.music.xljbear.com:9532";
var blurEl = null;
var audio = null;
var ws = null;
var NotifyList = new Array();
var InNotify = false;
var InMute = false;
var Fly_ID = 0;
var Lrc = null;
var NowPlayAid = "";
$(function(){
  InitPlayer();
});
function ChangeMusic(music){
  if(NowPlayAid!="")PlayListRemove(NowPlayAid);
  NowPlayAid = music.id;
  $("#music-"+NowPlayAid).addClass("now");
  music.blurPic = "./api/?type=album&" + Math.random();
  blurcache.src = music.blurPic;
  audio.src = music.src;
  ChangeSongTitle(music.author + " - " + music.song);
  $("#song").html(music.song);
  $("#author").html(music.author);
  document.title = music.song + " - Bear's Radio";
  audio.currentTime = music.currentTime;
  var MusicNotify = new Object();
  MusicNotify.title = "正在播放";
  MusicNotify.icon = music.blurPic;
  MusicNotify.body = music.author + " - " + music.song;
  BrowserNotify(MusicNotify);
  setTimeout(function(){Notify("开始播放 - " + music.song,true);Lrc.play();},1000);
}
function InitPlayer(){
  audio = document.getElementById('audio');
  audio.controls = false;
  audio.autoplay = true;
  audio.loop = true;
  Lrc = new Selected();
  volume_val = getCookie("Player_Volume");
  volume_mute = getCookie("Player_Mute");
  openchat = getCookie("Player_Open_Chat");
  openlist = getCookie("Player_Open_List");
  if(openlist=="true"){
    $(".PlayList>.arrow").html("&gt;");
    $(".PlayList").animate({"width":310},0);
  }
  if(openchat=="true"){
    $(".chat>.arrow").html("▼");
    $(".chat>.arrow").removeClass("hide");
    $(".chat>.message").animate({"opacity":1},0);
  }
  if(volume_val!=''){
    audio.volume = volume_val;
    volume.value = volume_val;
  }
  if(volume_mute=="true")Mute(true);
  $('.single-slider').jRange({
    from: 0.0,
    to: 1.0,
    step: 0.1,
    scale: [],
    width: 100,
    showLabels: false,
    theme:"theme-blue",
  });
  audio.addEventListener('timeupdate', function() {
      updateProgress();
  }, false);
  window.onresize = Resize;
  Resize();
  ws = new WebSocket("ws://" + ServerAddress);
  ws.onmessage = GetMessage;
  blurcache.onload = function(){
    $('.album>img').attr('src',blurcache.src);
    stackBlurImage("blurcache","blur",40,false);
  }
  $(document).on("click",".volume-icon",function(){
    if($(this).hasClass("on")){
      Mute(true);
    }else{
      Mute(false);
    }
  });
  $(document).on("click",".chat>.arrow",function(){
    if($(this).html()=="▼"){
      setCookie("Player_Open_Chat",false,30);
      $(this).html("▲");
      $(this).addClass("hide");
      $(".chat>.message").animate({"opacity":0},300);
    }else{
      setCookie("Player_Open_Chat",true,30);
      $(this).html("▼");
      $(this).removeClass("hide");
      $(".chat>.message").animate({"opacity":1},300);
    }
  });
  $(document).on("click",".PlayList>.arrow",function(){
    if($(this).html()=="&lt;"){
      setCookie("Player_Open_List",true,30);
      $(this).html("&gt;");
      $(".PlayList").animate({"width":310},300);
    }else{
      setCookie("Player_Open_List",false,30);
      $(this).html("&lt;");
      $(".PlayList").animate({"width":20},300);
    }
  });
  $(document).on("blur","#message-input",function(){
    if(this.value=='')$(this).removeClass('active');
  });
  $(document).on("focus","#message-input",function(){
    $(this).addClass('active');
  });
  $(document).on("keyup","#message-input",function(event){
    if(event.keyCode==13)SendMessage();
  });
}
function CreateFly(name,message){
  var fid = Fly_ID++;
  $(".fly-message").append('<div id="fly-'+ fid +'" class="fly"><span>'+ name +':</span>'+ message +'</div>');
  if(message.length>10&&message.length<=15){
    $("#fly-"+fid).addClass('small');
  }else if(message.length>15){
    $("#fly-"+fid).addClass('small2');
  }
  $("#fly-"+fid).animate({"top":-300,"opacity":0},8000,function(){
    $(this).remove();
  });
}
function GetMessage(event){
  data = JSON.parse(event.data);
  switch(data.type){
    case "music":
      ChangeMusic(data);
      break;
    case "msg":
      AddMessage("网友",data.content);
      break;
    case "online":
      $("#online").html(data.online);
      break;
    case "list":
      PlayList(data.list,true);
      break;
    case "newlist":
      PlayList(data.list);
      break;
  }
}
function SendMessage(){
  content = $("#message-input").val().trim();
  if(content=='')return;
  $("#message-input").val('');
  if(content.length>20){Notify("笨蛋！字数太长啦！！");return;}
  var data = Object();
  data.type = "msg";
  data.content = content;
  ws.send(JSON.stringify(data));
}
function AddMessage(name,message){
  $(".message").append('<div class="msg"><div class="content"><span>'+ name +':</span>'+ message +'</div></div>');
  $('.message').scrollTop($('.message')[0].scrollHeight);
  if($('.arrow').html()=="▲"){
    CreateFly(name,message);
  }
}
function Resize(){
  var bodyHeight = document.body.clientHeight;
  var bodyWidth = document.body.clientWidth;
  $('.body').height(bodyHeight-120);
  $('#lyricWrapper').height(bodyHeight-240);
}
function BrowserNotify(obj){
  if(window.Notification && Notification.permission !== "denied") {
    Notification.requestPermission(function(status) {
      var n = new Notification(obj.title, {
        icon:obj.icon,  
        body:obj.body
      });
      n.onshow = function(){
        setTimeout(function(){
          n.close();
        },4000);
      };
    });
  }
}
function Notify(txt,important){
  if(important){
    NotifyList.splice(1,0,txt);
  }else{
    NotifyList.push(txt);
  }
  if(!InNotify)FlashNotify();
}
function FlashNotify(){
  InNotify = true;
  $("#notify>.content").html(NotifyList[0]);
  $("#notify").slideDown(1000);
  setTimeout(function(){
    $("#notify").fadeOut(300,function(){
      NotifyList.shift();
      if(NotifyList.length>0){
        FlashNotify();
      }else{
        InNotify = false;
      }
    });
  },2000);
}
function ChangeSongTitle(title){
  $(".song-title").fadeOut(500,function(){
    $(".song-title").html(title);
    $(".song-title").fadeIn(500);
  });
}
function setVolume(){
  if(InMute){
    InMute = false;
    return;
  }
  setCookie('Player_Volume',volume.value,30);
  audio.volume = volume.value;
  if(volume.value>0)Mute(false);
}
function Mute(bool){
  if(bool){
    InMute = true;
    $(".volume-icon").removeClass("on");
    $(".volume-icon").addClass("off");
    setCookie('Player_Mute',true,30);
    audio.volume = 0;
  }else{
    $(".volume-icon").removeClass("off");
    $(".volume-icon").addClass("on");
    setCookie('Player_Mute',false,30);
    volume_val = getCookie("Player_Volume");
    if(volume_val=="")volume_val = 1;
    audio.volume = volume_val;
    volume.value = volume_val;
  }
}
function updateProgress(){
  if(audio.paused)return;
  var percent = (100 / audio.duration) * audio.currentTime;
  $('.progress-bar>.bar').css("width",percent+"%");
  var time = audio.duration - audio.currentTime;
  var S = parseInt(time % 60);
  var H = parseInt((time - S) / 60);
  if(H < 10)H = "0" + H;
  if(S < 10)S = "0" + S;
  $('.progress-bar>.bar').html(H + ":" + S);
}
function setCookie(cname, cvalue, exdays){
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}
function getCookie(cname){
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) != -1) return c.substring(name.length, c.length);
    }
    return "";
}
function clearCookie(name){  
    setCookie(name, "", -1);
}
function PlayList(musicList,init){
  if(init==true){
    $(".list>ul").html('');
  }
  for(var i=0;i<musicList.length;i++){
    PlayListAdd(musicList[i]);
  }
}
function PlayListAdd(music){
  html = '<li id="music-'+ music.id +'">';
  html += '<img src="./api/?type=album&aid='+ music.album_id +'">';
  var time = music.totals;
  var S = parseInt(time % 60);
  var H = parseInt((time - S) / 60);
  if(H < 10)H = "0" + H;
  if(S < 10)S = "0" + S;
  html += '<p class="song">'+ music.song + '-' + music.author +'<br><span class="duration">'+ H + ':' + S +'</span></p></li>';
  $(".list>ul").append(html);
  $("#music-"+music.id+">img").load(function(){
    $(this).animate({"opacity":1},600);
  });
}
function PlayListRemove(musicid){
  $("#music-"+musicid).fadeOut(800,function(){
    $(this).remove();
  });
}