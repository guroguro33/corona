<?php

ini_set('log_errors', 'on'); //ログを取る
ini_set('error_log', 'php.log'); //ログの出力ファイル指定
session_start();
// error_log(print_r($_SESSION,true));

// モンスター格納用
$monsters = array();

$finishFlg = false;

// 抽象クラス
abstract class Creature{
  protected $name;
  protected $img;
  protected $hp;
  protected $hpMax;
  protected $attackMin;
  protected $attackMax;
  protected $battleMsg;
  public function setName($str){
    $this->name = $str;
  }
  public function getName(){
    return $this->name;
  }
  public function getImg(){
    return $this->img;
  }
  public function setHp($num){
    $this->hp = $num;
  }
  public function getHp(){
    return $this->hp;
  }
  public function getHpMax(){
    return $this->hpMax;
  }
  public function setBattleMsg($str){
    $this->battleMsg .= $str.'\n';
  }
  public function getBattleMsg(){
    return $this->battleMsg;
  }
  public function clearBattleMsg(){
    $this->battleMsg = '';
  }
  public function attack($targetObj){
    $targetObj->clearBattleMsg();
    $attackPoint = mt_rand($this->attackMin, $this->attackMax);
    if(!mt_rand(0,19)){ //20分の1の確率で攻撃をミス
      $attackPoint = 0;
      $targetObj->setBattleMsg('ミス!');
    }elseif(!mt_rand(0,9)){ //10分の1の確率でクリティカルヒット
      $attackPoint = (int)($attackPoint * 1.5);
      $targetObj->setBattleMsg('かいしんのいちげき!');
    }
    $targetObj->setHp($targetObj->getHp() - $attackPoint);
    $targetObj->setBattleMsg($attackPoint.'のダメージ!!');
  }
}

// 人クラス
class Human extends Creature{
  public function __construct($name, $img, $hp, $hpMax, $attackMin, $attackMax){
    $this->name = $name;
    $this->img = $img;
    $this->hp = $hp;
    $this->hpMax = $hpMax;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax; 
  }
}
// ねこクラス
class Cat extends Creature{
  public static function catRecovery(){
    $_SESSION['catRecoveryMsg'] = '';
    $recoveryPoint = (int)($_SESSION['human']->getHpMax() * 2 / 3);
    if(!mt_rand(0,7)){
      // 回復後、HPの上限を超える場合は、上限にする
      if($_SESSION['human']->getHp() + $recoveryPoint > $_SESSION['human']->getHpMax()){
        $_SESSION['human']->setHp($_SESSION['human']->getHpMax());
      }else{
        $_SESSION['human']->setHp($_SESSION['human']->getHp() + $recoveryPoint);
      }
      $_SESSION['catRecoveryMsg'] = '勇者を'.$recoveryPoint.'回復した!';
      error_log('ねこが回復させた');
    }
  }
}

// モンスタークラス
class Monster extends Creature{
  protected $bg;
  protected $magicAttackMin;
  protected $magicAttackMax;
  public function __construct($name, $img, $hp, $hpMax, $attackMin, $attackMax, $bg, $magicAttackMin, $magicAttackMax){
    $this->name = $name;
    $this->img = $img;
    $this->hp = $hp;
    $this->hpMax = $hpMax;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
    $this->bg = $bg;
    $this->magicAttackMin = $magicAttackMin;
    $this->magicAttackMax = $magicAttackMax;
  }
  public function getBg(){
    return $this->bg;
  }
  public function attack($targetObj){
    global $defenseFlg;
    $targetObj->clearBattleMsg();
    if($defenseFlg){ //勇者がぼうぎょを選んだ場合
      $attackPoint = (int)(mt_rand($this->attackMin, $this->attackMax) / 10);
      $targetObj->setHp($targetObj->getHp() - $attackPoint);
      $targetObj->setBattleMsg('ぼうぎょした!');
      $targetObj->setBattleMsg($attackPoint.'のダメージ!!');
    }else{ //勇者がぼうぎょじゃない場合
      if(!mt_rand(0,4)){ //5分の1の確率で魔法攻撃
        $magicAttackPoint = (int)mt_rand($this->magicAttackMin, $this->magicAttackMax);
        $targetObj->setHp($targetObj->getHp() - $magicAttackPoint);
        $targetObj->setBattleMsg('ウイルスをあびた!');
        $targetObj->setBattleMsg($magicAttackPoint.'のダメージ!!');
      }else{
        parent::attack($targetObj);
      }
    }
  }
}
// トップメッセージのクラス
class TopMsg{
  public static function set($str){
    if(empty($_SESSION['topMsg'])) $_SESSION['topMsg'] = '';
    //メッセージをセッションに格納
    $_SESSION['topMsg'] .= $str.'<br>';
  }
  public static function clear(){
    unset($_SESSION['topMsg']);
  }
}
 
// インスタンス生成
$human = new Human('ゆうしゃ', 'img/human.png', 700, 700, 80, 120);
$monsters[] = new Monster('かぜウイルス', 'img/enemy1.png' , 300, 300, 20, 40, './img/battlebg1.jpg' ,30, 50);
$monsters[] = new Monster('インフルエンザウイルス', 'img/enemy2.png' , 400, 400, 40, 60, './img/battlebg2.jpg', 50, 70);
$monsters[] = new Monster('ノロウイルス', 'img/enemy3.png' , 500, 500, 60, 80, './img/battlebg3.jpg', 70, 90);
$monsters[] = new Monster('SARSウイルス', 'img/enemy4.png' , 700, 700, 80, 100, './img/battlebg4.jpg', 90, 110);
$monsters[] = new Monster('コロナウイルス', 'img/enemy5.png' , 900, 900, 100, 120, './img/battlebg5.jpg', 110, 130);


// 関数
function createHuman(){
  global $human;
  $_SESSION['human'] = $human;
}
function createMonster(){
  global $monsters;
  $_SESSION['monster'] = $monsters[$_SESSION['monsterCount']];
  TopMsg::set($_SESSION['monster']->getName().'が　あらわれた！');
}
function init(){
  $_SESSION['monsterCount'] = 0;
  $_SESSION['bossCount'] = 4;
  $_SESSION['catRecoveryMsg'] = '';
  $_SESSION['fadeInFlg'] = true;
  TopMsg::clear();
  createHuman();
  createMonster();
}
function gameOver(){
  $_SESSION = array();
}

// post送信されていた場合
if(!empty($_POST)){
  $startFlg = (!empty($_POST['start']))? true : false;
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $defenseFlg = (!empty($_POST['defense'])) ? true : false;
  $resetFlg = (!empty($_POST['reset'])) ? true : false;

  error_log('POSTされた！');

  if($startFlg){
    error_log('ゲームスタート');
    init();
  }else{
    if($resetFlg){
      error_log('スタート画面に戻る');
      gameOver();
      header("Location:index.php");
      exit();
    }
    if($attackFlg){ //攻撃する場合
      error_log('こうげきを選択');
      // 勇者の攻撃
      $_SESSION['human']->attack($_SESSION['monster']);
      // ねこが回復
      Cat::catRecovery();
      // モンスターの攻撃
      $_SESSION['monster']->attack($_SESSION['human']);
      // topメッセージの消去
      if(!empty($_SESSION['topMsg'])){
        TopMsg::clear();
      }
      $_SESSION['fadeInFlg'] = false;
    }
    if($defenseFlg){ //防御する場合
      error_log('ぼうぎょを選択');
      //モンスターの攻撃
      $_SESSION['monster']->attack($_SESSION['human']);
      $_SESSION['monster']->clearBattleMsg();
      // ねこが回復
      Cat::catRecovery();
      // topメッセージの消去
      if(!empty($_SESSION['topMsg'])){
        TopMsg::clear();
      }
      $_SESSION['fadeInFlg'] = false;
    }
    if($resetFlg){ //さいしょからを選んだ場合
      error_log('さいしょからを選択');
      gameOver();
      header("Location:index.php");
      exit();
    }
    if($_SESSION['human']->getHp() <= 0){ //勇者HPが0以下になったらゲームオーバー
      error_log('ゆうしゃは力尽きた');
      gameOver();
      header("Location:index.php");
      exit();
    }else{
      if($_SESSION['monster']->getHp() <= 0 && $_SESSION['monsterCount'] < 4){
        //敵のHPが0以下かつ4番目までの敵だった場合、次の敵へ
        error_log($_SESSION['monster']->getName().'を倒した');
        TopMsg::clear();
        TopMsg::set($_SESSION['monster']->getName().'をたおした！');
        //倒した敵の数をカウント
        $_SESSION['bossCount'] -= 1;
        $_SESSION['monsterCount'] += 1;
        //新しい敵を出現
        createMonster();
        //新しい敵をフェードインさせるフラグをたてる
        $_SESSION['fadeInFlg'] = true;
        // error_log(print_r($_SESSION,true));
      }else{
        //敵のHPが0以下かつ5番目（ラスボス）だった場合、エンディングへ
        if($_SESSION['monster']->getHp() <= 0 && $_SESSION['monsterCount'] == 4){
        error_log($_SESSION['monster']->getName().'を倒した');
        $finishFlg = true;
        }
      }
    }
  }
  // error_log(print_r($_POST,true));
  $_POST = array();
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="オブジェクト指向部アウトプット">
    <meta name="keywords" content="">
    <!-- Twitterカード -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@guroguro33" />
    <meta property="og:url" content="https://kurosuke-web.com/ff-battle/" />
    <meta property="og:title" content="ウイルスをやっつけろ！" />
    <meta property="og:description" content="F〇風バトルゲーム（phpオブジェクト指向部アウトプット）" />
    <meta property="og:image" content="https://kurosuke-web.com/ff-battle/sample.jpg" />
    <!-- ファビコン -->
    <link rel="shortcut icon" href="./img/favicon.ico">
    <!-- スマホ用アイコン -->
    <link rel="apple-touch-icon" sizes="152x152" href="./img/apple-touch-icon.png">
    <!-- font awesome -->
    <!-- <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.9/css/all.css"> -->
    <!-- google font -->
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans+JP&display=swap" rel="stylesheet">
		<!-- CSS -->
		<link rel="stylesheet" href="css/style.css">
    <title>ウイルスをやっつけろ！</title>
    <style>
      body{
         background-image: url("<?php echo (!empty($_SESSION['monster'])) ? $_SESSION['monster']->getBg() : './img/opbg.jpg'; ?>");
      }
      <?php if(!empty($_SESSION['fadeInFlg'])) : ?>
        <?php if($_SESSION['fadeInFlg'] === true) : ?>
          .enemy img{
            animation: fadeIn 3s ease 0s 1 normal;
          }
          @keyframes fadeIn{
            0% {opacity:0}
            100% {opacity:1}
          }
        <?php endif; ?>
      <?php endif; ?>
    </style>
</head>
<body onload="message_char1()">
  <?php if(empty($_SESSION)): ?>
    <main class="main wrap">
      <h1>ウイルスをやっつけろ！</h1>
      <h2>～F〇風バトルゲーム～</h2>
      <form action="" method="post">
        <input type="submit" class="start btn" name="start" value="ゲームスタート">
      </form>
      <p class="play-rule">
        【あそびかた】<br>
        たたかう　か　ぼうぎょ　をタップ!<br>
        ときどき、かいふくしてくれます<br>
      </p>
      <p class="img-offer">素材提供元　ぴぽや  <a href="https://pipoya.net/" target="_blank" rel="noopener noreferrer">https://pipoya.net/</a></p>
    </main>
  <?php elseif(!empty($_SESSION) && !($finishFlg)): ?>
    <main class="main wrap">
      <div class="msg">
        <p class="window" id="msg-window">
          <?php echo (!empty($_SESSION['topMsg']))? $_SESSION['topMsg'] : ""; ?>
        </p>
      </div>
      <section class="char">
        <div class="enemy">
          <img src="<?php echo $_SESSION['monster']->getImg(); ?>" alt="<?php echo $_SESSION['monster']->getName(); ?>">
          <p class="msg-damage" id="enemy-damage">
            <?php // echo $_SESSION['monster']->getBattleMsg(); ?>
          </p>
        </div>
        <div class="fighter">
          <img src="<?php echo $_SESSION['human']->getImg(); ?>" alt="<?php echo $_SESSION['human']->getName(); ?>">
          <p class="msg-damage" id="fighter-damage">
            <?php // echo $_SESSION['human']->getBattleMsg(); ?>
          </p>
          <img src="./img/cat.png" alt="ねこ">
          <p class="msg-damage" id="cat-recovery">
            <?php // echo $_SESSION['catRecoveryMsg']; ?>
          </p>
        </div>
      </section>
      <section class="status">
        <div class="status-enemy window">
          <p><?php echo $_SESSION['monster']->getName(); ?></p>
          <p>HP <?php echo $_SESSION['monster']->getHp(); ?> / <?php echo $_SESSION['monster']->getHpMax(); ?></p>
          <p>ラスボスまであと <?php echo $_SESSION['bossCount']; ?></p>
        </div>
        <div class="status-fighter window">
          <div class="hero">
            <p><?php echo $_SESSION['human']->getName(); ?></p>
            <p>HP <?php echo $_SESSION['human']->getHp(); ?> / <?php echo $_SESSION['human']->getHpMax(); ?></p>
          </div>
          <div class="cat">
            <p>ねこ</p>
            <p>HP 999 / 999</p>
          </div>
        </div>
      </section>
      <section class="cmd">
        <p class="msg-cmd window">こうどうコマンドをタップ</p>
          <form class="cmd-wrap" action="" method="post">
            <input type="submit" class="cmd-btn window" name="attack" value="たたかう">
            <input type="submit" class="cmd-btn window" name="defense" value="ぼうぎょ">
            <input type="submit" class="cmd-btn window" name="reset" value="はじめから">
          </form>
      </section>
    </main>
  <?php else: ?>
    <main class="main wrap">
      <h1>ゲームクリア</h1>
      <p class="lastMsg">コロナウイルス　は　しょうめつし</p>
      <p class="lastMsg">じんるい　は　へいわを</p>
      <p class="lastMsg">とりもどした</p>
      <form action="" method="post">
        <input type="submit" class="start btn" name="reset" value="タイトル画面へ">
      </form>
    </main>
  <?php endif; ?>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<!-- javascript -->
<script>
  // TOPメッセージを1番下に
  $(function(){
    $('#msg-window').animate({
      scrollTop: $('#msg-window')[0].scrollHeight
    }, 3000);

  });

  // 流れるテキスト
  var msg_buff1 = '<?php echo (!empty($_SESSION['monster']))? $_SESSION['monster']->getBattleMsg() : ''; ?>';
  function message_char1()
  {
    if (msg_buff1 == '') {
      //メッセージバッファに文字がなければ何もしない
      message_char2();
    }
    //メッセージバッファの先頭1文字を取得
    var c = msg_buff1.slice(0, 1)
    if (c == "\n") {
      c = '<br>';//改行の場合はタグへ変換
    }
    document.getElementById('enemy-damage').innerHTML += c;
    //メッセージバッファから先頭1文字を削除
    msg_buff1 = msg_buff1.slice(1);
    //
    setTimeout('message_char1()', 50);
  }
  
  var msg_buff2 = '<?php echo (!empty($_SESSION['human']))? $_SESSION['human']->getBattleMsg() : ''; ?>';
  function message_char2()
  {
    if (msg_buff2 == '') {
      //メッセージバッファに文字がなければ何もしない
      message_char3();
    }
    //メッセージバッファの先頭1文字を取得
    var c = msg_buff2.slice(0, 1)
    if (c == "\n") {
      c = '<br>';//改行の場合はタグへ変換
    }
    document.getElementById('fighter-damage').innerHTML += c;
    //メッセージバッファから先頭1文字を削除
    msg_buff2 = msg_buff2.slice(1);
    //
    setTimeout('message_char2()', 800);
  }

  var msg_buff3 = '<?php echo (!empty($_SESSION['catRecoveryMsg']))?  $_SESSION['catRecoveryMsg'] : ''; ?>';
  function message_char3()
  {
    if (msg_buff3 == '') {
      //メッセージバッファに文字がなければ何もしない
      return;
    }
    //メッセージバッファの先頭1文字を取得
    var c = msg_buff3.slice(0, 1)
    if (c == "\n") {
      c = '<br>';//改行の場合はタグへ変換
    }
    document.getElementById('cat-recovery').innerHTML += c;
    //メッセージバッファから先頭1文字を削除
    msg_buff3 = msg_buff3.slice(1);
    //
    setTimeout('message_char3()', 800);
  }
  
  
</script>
</body>
</html>