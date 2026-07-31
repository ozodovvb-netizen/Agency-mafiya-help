<?php
// ================================================================
//  Guruh nazoratchi bot — Railway uchun moslashtirilgan versiya
// ================================================================
// Token va admin ID endi ENVIRONMENT VARIABLE orqali olinadi.
// Railway paneli -> Variables bo'limida quyidagilarni qo'shing:
//   BOT_TOKEN = <BotFather bergan token>
//   ADMIN_ID  = <sizning Telegram ID raqamingiz>
// ================================================================

error_reporting(E_ERROR | E_PARSE); // fayl mavjud bo'lmaganda chiqadigan notice/warninglarni yashiradi
chdir(__DIR__); // kod ichidagi barcha nisbiy fayl yo'llari ("data/...", "gruppa.db" va h.k.) shu papkaga nisbatan ishlaydi

$admin = getenv('ADMIN_ID') ?: '';
$token = getenv('BOT_TOKEN') ?: '';

if (!$token) {
    http_response_code(500);
    die("BOT_TOKEN environment variable topilmadi. Railway -> Variables bo'limiga qo'shing.");
}

if (!is_dir(__DIR__ . '/data')) {
    @mkdir(__DIR__ . '/data', 0777, true);
}
foreach (['gruppa.db', 'lichka.db', 'msgs.json'] as $f) {
    if (!file_exists(__DIR__ . '/' . $f)) {
        @file_put_contents(__DIR__ . '/' . $f, $f === 'msgs.json' ? '{}' : '');
    }
}

function bot($method,$datas=[]){
global $token;
    $url = "https://api.telegram.org/bot".$token."/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

function bot_username(){
    $cacheFile = __DIR__ . '/data/botuser.txt';
    if(file_exists($cacheFile)){
        $u = trim(file_get_contents($cacheFile));
        if($u) return $u;
    }
    $me = bot('getMe');
    $u = $me->result->username ?? '';
    if($u){
        @file_put_contents($cacheFile, $u);
    }
    return $u;
}
$botUsername = bot_username();



$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$mid = $message->message_id;
$cid = $message->chat->id;
$chat_id = $message->chat->id;
if ($cid && !is_dir(__DIR__ . "/data/$cid")) {
    @mkdir(__DIR__ . "/data/$cid", 0777, true);
}
$cid2 = $update->callback_query->message->chat->id;
if ($cid2 && !is_dir(__DIR__ . "/data/$cid2")) {
    @mkdir(__DIR__ . "/data/$cid2", 0777, true);
}
$data = $update->callback_query->data;
$tx = $message->text;
$uid= $message->from->id;
$id = $message->reply_to_message->from->id;
$rname= $message->reply_to_message->from->first_name;
$rmid= $message->reply_to_message->message_id;
$mention = $message->entities[0]->type;
$ty = $message->chat->type;
$title = $message->chat->title;
$repid = $message->reply_to_message->from->id;
$gruppa = file_get_contents("gruppa.db");
$lichka = file_get_contents("lichka.db");
$new = $message->new_chat_member;
$left = $message->left_chat_member;
$for = $message->forward_from;
$forx = $message->forward_from_chat;
$ssl = file_get_contents("data/$cid/ssilka.db");
          $stic = file_get_contents("data/$cid/stic.db");
          $ras = file_get_contents("data/$cid/rasm.db");
$join = file_get_contents("data/$cid/join.db");
          $gif = file_get_contents("data/$cid/gif.db");
          $ovoz = file_get_contents("data/$cid/ovoz.db");
$sticker = $message->sticker;
$rasm = $message->photo;
$animation = $message->animation;
$voice = $message->voice;
$replytx = $message->reply_to_message->text;
$url = $message->entities[0]->type;
$user =  $message->entities[1]->type;
$msgs = json_decode(file_get_contents('msgs.json'),true);
$type = $message->chat->type;
$text = $message->text;
$from_user_first_name = $message->reply_to_message->from->first_name;
$tx = $message->text;
$cmd = $tx;
if(is_string($cmd) && isset($cmd[0]) && $cmd[0] === '/'){
    $cmd = '#'.substr($cmd,1);
}
if(is_string($cmd)){
    $cmd = preg_replace('/@\S+/','',$cmd);
}
if($type=="supergroup" or $type=="group"){
    $ex = $msgs[$text];
$ex = explode("|",$ex);
    $txt = $ex[rand(0,count($ex)-1)];
bot("sendmessage",[
	'chat_id'=>$message->chat->id,
	'text'=>"$txt",
	'reply_to_message_id'=>$mid
	]);
}
$mem = bot('getChatMemberCount',[
'chat_id'=>$cid,
]);
$azo = $mem->result;

//Yangi odam id si
$new_chat_members = $message->new_chat_member->id;
$lan = $message->new_chat_member->language_code;
$first_name = $message->from->first_name;
$is_bot = $message->new_chat_member->is_bot;
$ismcha = $message->from->first_name;
$familiya = $message->from->last_name;
$bio1 = $message->from->about;
$login = $message->from->username;
$soat1 = date('H:i:s',strtotime('5 hour')); 
$sana1 = date('d-M Y',strtotime('5 hour'));
$sana2 = date('z',strtotime('5 hour'));
$gmt = date('O',strtotime('5 hour'));
$oynomi = date('F',strtotime('5 hour'));
$buoy = date('t',strtotime('5 hour'));

if($replytx){
    if($type=="supergroup"  or $type=="group"){
   	$replytx = $message->reply_to_message->text;
   	      	if(strpos($msgs[$replytx],"$text") !==false){
   	}else{
		$msgs[$replytx] ="$text|$msgs[$replytx]";
		file_put_contents('msgs.json', json_encode($msgs));
	}
	
}
}
if(($text=="/del_doc") and $cid==$admin){
unlink("msgs.json");
bot("sendmessage",[
"chat_id"=>$cid,
'parse_mode'=>"markdown",
"text"=>"*🗑Baza Tozalandi*"
]);
}

if($text=="/doc" or $text=="#doc"){
bot("senddocument",[
"chat_id"=>$message->chat->id,
"document"=>new CURLFile("msgs.json")
]);
}

if($cmd == "#Adm" or $cmd == "#adm"){
$gett = bot('getChatMember', [
'chat_id' => $chat_id,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){
  bot('promoteChatMember',[
    'chat_id'=>$chat_id,
    'user_id'=>$uid,
    'can_change_info'=>false,
    'can_post_messages'=>true,
    'can_edit_messages'=>true,
    'can_delete_messages'=>true,
    'can_invite_users'=>true,
    'can_restrict_members'=>false,
    'can_pin_messages'=>false,
    'can_promote_members'=>false
  ]);
  bot('sendChatAction',['chat_id'=>$chat_id,'action'=>"typing"]);
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"✅ <a href='tg://user?id=$id'>$from_user_first_name</a> sizni tabriklayman , siz guruh <b>adminstratorisiz❗️</b>",
    'parse_mode'=>'html'
  ]);
}
}

if($cmd=="#unban" or $cmd=="#Unban"){
$gett = bot('getChatMember', [
  'chat_id' => $chat_id,
  'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){ 
    bot('unbanChatMember',[    
    'chat_id'=>$chat_id,    
    'user_id'=>$id,    
  ]);    
    bot('sendmessage',[
        'chat_id'=>$chat_id,
        'text'=>"[$rname](tg://user?id=$id) Admin ruhsati bilan😎 *Bandan* olindi!",
        'parse_mode'=>'markdown',
    ]);
}
}

if($cmd == "#Admn" or $cmd == "#admn"){
$gett = bot('getChatMember', [
'chat_id' => $chat_id,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){
  bot('promoteChatMember',[
    'chat_id'=>$chat_id,
    'user_id'=>$uid,
    'can_change_info'=>true,
    'can_post_messages'=>true,
    'can_edit_messages'=>true,
    'can_delete_messages'=>true,
    'can_invite_users'=>true,
    'can_restrict_members'=>true,
    'can_pin_messages'=>true,
    'can_promote_members'=>true
  ]);
  bot('sendChatAction',['chat_id'=>$chat_id,'action'=>"typing"]);
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"✅ <a href='tg://user?id=$id'>$from_user_first_name</a> sizni tabriklayman , siz guruh <b>adminstratorisiz❗️</b>",
    'parse_mode'=>'html'
  ]);
}
}

if($cmd == "#Delmn" or $cmd == "#delmn"){
$gett = bot('getChatMember', [
'chat_id' => $chat_id,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get == "administrator" or $get == "creator"){
bot('promoteChatMember',[
    'chat_id'=>$chat_id,
    'user_id'=>$uid,
    'can_change_info'=>false,
    'can_post_messages'=>false,
    'can_edit_messages'=>false,
    'can_delete_messages'=>false,
    'can_invite_users'=>false,
    'can_restrict_members'=>false,
    'can_pin_messages'=>false,
    'can_promote_members'=>false
  ]);
  bot('sendChatAction',['chat_id'=>$chat_id,'action'=>"typing"]);
  bot('sendmessage',[
    'chat_id'=> $chat_id,
    'text'=>"☑ <a href='tg://user?id=$id'>$from_user_first_name</a> siz endi guruh adminstratori <b>emassiz</b>❗️",
    'parse_mode'=>'html'
  ]);
}
}

if($cmd=="#panel"){
	$panelStatus = bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$panelStatus = $panelStatus->result->status;
if($panelStatus=="administrator" or $panelStatus=="creator"){
 $ssl = file_get_contents("data/$cid/ssilka.db");
          $stic = file_get_contents("data/$cid/stic.db");
          $ras = file_get_contents("data/$cid/rasm.db");
        $join = file_get_contents("data/$cid/join.db");
          $gif = file_get_contents("data/$cid/gif.db");
          $ovoz = file_get_contents("data/$cid/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);

	bot('sendmessage',[
		'chat_id'=>$cid,
		'text'=>"👇*Holati*


*✅Yoqilgan*
__________

*☑️O'chirilgan*",
'parse_mode'=>"markdown",
'reply_markup' => json_encode([
                'inline_keyboard'=>[
                   [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz       $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif            $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],
                    ],
])
]);
}
}


if($ty=="supergroup"){
mkdir("data");
mkdir("data/$cid");
if(strpos($gruppa,"$cid") !==false){
}else{
file_put_contents("gruppa.db","$gruppa\n$cid");
}

}

 if(($sticker) and $stic=="on"){
     $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}
 if(($rasm) and $ras=="on"){
     $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}

    if (($new_chat_members != NUll)&&($is_bot!=true)) {
  if((stripos($lan,"fa")!== false) or (stripos($lan,"ar")!==false)){
      $vaqti = strtotime("+999999999999 minutes");
  bot('kickChatMember', [
      'chat_id' => $cid,
      'user_id' => $new_chat_members,
      'until_date'=> $vaqti,
    ]);
    }else{
      $test = "<b>👋Salom</b> <a href='tg://user?id=$new_chat_members'>$ismcha</a> ,<b>$title</b> guruhimizga xush kelibsiz!
👥 Grupamiz azolari : $azo";
       bot('sendmessage',[
       'chat_id'=>$cid,
       'text'=>$test,
       'parse_mode'=>'html'
     ]);
   }
    }
////
   if (($new_chat_members != NUll)&&($is_bot!=false)) {
$gett = bot('getChatMember', [
'chat_id' => $cid,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="member"){
   $vaqti = strtotime("+999999999999 minutes");
  bot('kickChatMember', [
      'chat_id' => $cid,
      'user_id' => $new_chat_members,
      'until_date'=> $vaqti,
  ]);
  bot('sendChatAction',['chat_id'=>$cid,'action'=>"typing"]);
  bot('sendmessage', [
      'chat_id' => $cid,
      'text' => "❗<b>Guruhga faqat adminlar bot qo'shishi mumkin!</b>",
      'parse_mode' => 'html'
  ]);
}
}

////


if($ty=="private"){
if(strpos($lichka,"$cid") !==false){
}else{
file_put_contents("lichka.db","$lichka\n$cid");
}
} 
$kanal = "@kanal";
if($ty=="private"){
   


if($tx=="/start"){
bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"*👋Assalom Alaykum!*
👨‍✈️`@bot` *ni Gruppangizga Admin qilsangiz:
🛡 Gruppangizni botlardan himoya qiladi.
😷 Reklamalarni Tozalaydi.
⭕️ Kirdi chiqdilarni tozalaydi.
🔞 Video, Sticker, Reklama va boshqalarni o'chiradi!
💎 Va yana Koplab vazifalarni bajaradi!*
💥 #panel *buyrug'i orqali botni o'z guruhingizga moslab olishingiz mumkin!*

*Shuningdek bot inline rejimda kanal va gruppa haqida ma'lumot ham beradi!
Sinab ko'rish tugmasi orqali tekshirib korishingiz mumkin!*",
'parse_mode'=>"markdown",
'reply_markup' => json_encode([
                'inline_keyboard'=>[
                   [['text'=>"➕Guruhga qo‘shish",'url'=>'https://t.me/'.$botUsername.'?startgroup=new']],
[['text'=>'🔰Kanalimiz','url'=>'https://t.me/Sinalgan_PHP_kodlar'],['text'=>'👤Admin','url'=>'https://t.me/Big_CoderUz']], 
                 [['text'=>'✔️Buyruqlar','callback_data'=>'buyruq'],['text'=>'☑️Qoshimcha Buyruqlar','callback_data'=>'qoshimcha']], 
[['text'=>'📲Telegram Tillari🇺🇿','callback_data'=>'til'],['text'=>"🆔Sinash",'switch_inline_query'=>"@"]],
]
])
]);
}
} 
$edit_text = $update->edited_message->text ?? '';
$chat_edit_id = $update->edited_message->chat->id ?? null;
$message_edit_id = $update->edited_message->message_id ?? null;
if($edit_text && $chat_edit_id && preg_match("/^(.*)([Hh]ttp|[Hh]ttps|t.me)(.*)|([Hh]ttp|[Hh]ttps|t.me)(.*)|(.*)([Hh]ttp|[Hh]ttps|t.me)|(.*)[Tt]elegram.me(.*)|[Tt]elegram.me(.*)|(.*)[Tt]elegram.me|(.*)[Tt].me(.*)|[Tt].me(.*)|(.*)[Tt].me/", $edit_text)) {  
bot('deletemessage',[
    'chat_id'=>$chat_edit_id,
    'message_id'=>$message_edit_id
    ]);
}

if($mention=="mention"  and $ssl=="on"){
    $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}
//
 if(($voice) and $ovoz=="on"){
     $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}
//alooo
 if(($animation) and $gif=="on"){
     $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}

if(mb_stripos($cmd,"#post") !== false){ 
$ex = explode("-",$tx);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"$ex[1]",
'parse_mode'=>'markdown',
    'reply_markup'=> json_encode([
    'inline_keyboard'=>[
[['text'=>"$ex[2]", 'url'=>"$ex[3]"]],
]
])
]);
} 

 
if($data=="qoshimcha"){
    bot('sendmessage',[
        'chat_id'=>$cid2,
        'text'=>"*Botning Qoshimcha Buyruqlari*
`#king` - *va so'z - rasmga yozish*
`#fuck` - *va so'z - rasmga yozish*
`#love` - *va so'z - yurakchali rasmga yozish*
`#screen` - *sayt nomi - saytni rasmga olish*
`#search` - *kerakli narsani izlash*
`#vaqt` - *vaqt haqida malumot*
`#user` -* user ma‘lumotnomasi*
`#profil` - *profildagi rasmingiz*
`#id` - *id kodizni beradi*
`#gid` - *guruh id kodini beradi*
`#doc` - *bot Yodlagan So'zlar*
`#post` - *knopkali post yasash*
`/sms`- *id va yuboriladigan xabar - yozilgan id egasiga xabar jo'natish, faqat bosh admin jo'nata oladi*
`soat` -*o'zbekistondagi aniq vaqt*
`sana` -*aniq Sana*",
'parse_mode'=>'markdown',
'reply_markup' => json_encode([
                'inline_keyboard'=>[
                   [['text'=>'Chiqish↩️','callback_data'=>'orqa']],
]
])
]);
}

if($data=="buyruq"){
    bot('sendmessage',[
        'chat_id'=>$cid2,
        'text'=>"*Guruh Admini Uchun Buyruqlar*
`#adm` - *admin berish*
`#admn` - *adminga barcha imkoniyatlarni berish*
`#delmn` - *adminlikdan olish*
`#warn` - *reply qilgan odamga ogohlantirish berish*
`#nowarn` - *ogohlantirishlarni olib tashlash*
`#ban` -*guruhdan ban qilish*
`#kick` -*guruhdan chiqarib yuborish*
`#mute` - *reply qilgan odamni yozishdan cheklash*
`#unmute` - *reply qilgan odamni yozishiga ruxsat berish*
`#leavechat` - *botni guruhdan haydash*
`#pin` - *reply qilingan textni yuqoriga qistirish*",
'parse_mode'=>'markdown',
'reply_markup' => json_encode([
                'inline_keyboard'=>[
                   [['text'=>'Chiqish↩️','callback_data'=>'orqa']],
]
])
]);
}


if($data=="orqa"){
    bot('sendmessage',[
        'chat_id'=>$cid2,
        'text'=>"*👋 Assalom Alaykum!*
👨‍✈️`@bot` *ni Gruppangizga Admin qilsangiz:
🛡 Gruppangizni botlardan himoya qiladi.
😷 Reklamalarni Tozalaydi.
⭕️ Kirdi chiqdilarni tozalaydi.
🔞 Video, Sticker, Reklama va boshqalarni o'chiradi!
💎 Va yana Koplab vazifalarni bajaradi!*
💥 #panel *buyrug'i orqali botni o'z guruhingizga moslab olishingiz mumkin!*

*Shuningdek bot inline rejimda kanal va gruppa haqida ma'lumot ham beradi!
Sinab ko'rish tugmasi orqali tekshirib korishingiz mumkin!*",
'parse_mode'=>"markdown",
'reply_markup' => json_encode([
                'inline_keyboard'=>[
                 [['text'=>"➕Guruhga qo‘shish",'url'=>'https://t.me/'.$botUsername.'?startgroup=new']],
[['text'=>'🔰Kanalimiz','url'=>'https://t.me/GDance'],['text'=>'👤Admin','url'=>'https://t.me/Sayfer_Uz']], 
                 [['text'=>'✔️Buyruqlar','callback_data'=>'buyruq'],['text'=>'☑️Qoshimcha Buyruqlar','callback_data'=>'qoshimcha']], 
[['text'=>'📲Telegram Tillari🇺🇿','callback_data'=>'tillar'],['text'=>"🆔Sinash",'switch_inline_query'=>"@"]],
]
])
]);
}


//
 if($mention=="url" and $ssl=="on"){
     $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}
 if(($for or $forx) and $join=="on"){
      $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="member"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
}
if($new){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
if($left){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}
if($mention=="text_link" and $ssl=="on"){
bot('deletemessage',[
'chat_id'=>"$cid","message_id"=>"$mid"]);
}

if($ty=="supergroup"){

if(strpos($tx,"/start") !==false){
 $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator"or$cr=="administrator"){    
$yes = file_get_contents("data/$cid/index.db");
if($yes){
bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"<b>Men $title gruppasida qayta ishga tushirildim😜</b>",
'parse_mode'=>"html"
]);

}else{

bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"<b>Men $title gruppasida ishga tushirildim😃</b>",
'parse_mode'=>"html"
]);
file_put_contents("data/$cid/index.db","ok");
}
}
}
}
$reply = $message->reply_to_message->text;
$rpl = json_encode([
           'resize_keyboard'=>false,
            'force_reply' => true,
            'selective' => true
        ]);
if($tx=="/send" and $cid==$admin){
    bot('sendmessage',[
'chat_id'=>$admin,
'text'=>"*📨 Yuboriladigan xabar matnini kiriting. Xabar turi markdown*",'parse_mode'=>"markdown",'reply_markup'=>$rpl
]);
}
    if($reply=="📨 Yuboriladigan xabar matnini kiriting. Xabar turi markdown"){
        $lich = file_get_contents("lichka.db");
        $lichka = explode("\n",$lich);
foreach($lichka as $id){
    bot("sendmessage",[
        'chat_id'=>$id,
        'text'=>"$tx"]);
}
}
//sendgroup

     if($tx == "/sendgr" and $cid == $admin){
    bot('sendmessage',[
'chat_id'=>$admin,
'text'=>"*📨 Yuboriladigan xabar matnini kiriting. Xabar turi markdown*",'parse_mode'=>"markdown",'reply_markup'=>$rpl
]);
}
    if($reply=="📨 Yuboriladigan xabar matnini kiriting. Xabar turi markdown"){
        $gr = file_get_contents("gruppa.db");
        $gruppa= explode("\n",$gr);
foreach($gruppa as $cid){
    bot("sendmessage",[
        'chat_id'=>$cid,
      'text'=>$tx,
      'parse_mode'=>'markdown',
      'disable_web_page_preview' => true,
      ]);
      }
         if($gr){
          bot('sendmessage',[
          'chat_id'=>$admin,
          'text'=>"*Umumiy hammaga yuborildi!*",
          'parse_mode'=>'markdown',
          ]);      
        }
      }


//
if(mb_stripos($cmd,"#screen") !== false){ 
$ex = explode(" ",$tx);
bot('SendPhoto',[
'chat_id'=>$cid, 'reply_to_message_id'=>$mid,
'photo'=>"https://api.site-shot.com/?url=$ex[1]",
'caption'=>"By @admin",
]);
}

if((mb_stripos($tx,"@admin") !== false) or (mb_stripos($tx,"Alimardon")!==false) or (stripos($tx,"Alimardon")!==false) or (mb_stripos($tx,"admin")!==false) or (mb_stripos($tx,"@admin")!==false)){ 
bot('SendMessage',[
'chat_id'=>$admin,
'parse_mode'=>'html',
'text'=>"✉<b>$title(</b>  $chat_id  <b>) guruhida sizni eslashdi:</b>\n<code>$tx</code>\n  <b>Xabarchi  haqida  ma'lumotlar: </b>
👤<b>Ismi:</b>  <a href='tg://user?id=$uid'>$ismcha</a>
🆔<b>ID</b>si: $uid
🔅<b>Usernamesi:</b> @$login ", null, false
      ]);
   }
   
   
    
    if((stripos($tx,"/sms") !== false) and $cid == $admin){
    $ex = explode("-",$tx);
    bot('sendMessage',[
    'chat_id'=>$ex[1], 
    'text'=>"$ex[2]",
    'reply_markup'=>$key
    ]);
    }
    
    if(mb_stripos($cmd,"#search") !== false){ 
	$ex = explode(" ",$tx);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"🔍 Qidiruv \n☑️ Matn kiriting!\n",
'parse_mode'=>"Markdown",
    'reply_markup'=> json_encode([
    'inline_keyboard'=>[
    [
['text'=>"App store 🌐", 'url'=>"https://www.apple.com/us/search?q=$ex[1]"],
],
[
['text'=>"Google 📈", 'url'=>"https://www.google.com.iq/search?q=$ex[1]"],
],
[
['text'=>"Youtube 🎥", 'url'=>"https://m.youtube.com/results?q=$ex[1]&sm=3"],
],
[
['text'=>"instagram 📯", 'url'=>"https://www.instagram.com/$ex[1]"],
],

[
['text'=>"Telegram 📪", 'url'=>"https://www.telegram.me/$ex[1]"],
],
[
['text'=>"Github 🐱", 'url'=>"https://github.com/search?utf8=✓&q=$ex[1]"],
],
    ]
    ])
    ]);

    }
///
if($cmd == "#profil" or ($cmd=="#Profil")){
    $photos = bot('getUserProfilePhotos',[
        'user_id'=>$uid,
        'limit'=>1
    ]);
    $photo = $photos->result->photos[0][0]->file_id ?? null;
    if($photo){
        bot('sendPhoto',[
           'chat_id'=>$cid,
            'photo'=>$photo,
            'parse_mode'=>'markdown',
            'caption'=>"*Sizning profildagi rasmingiz*",
            'reply_to_message_id'=>$mid,
        ]);
    }else{
        bot('sendmessage',[
            'chat_id'=>$cid,
            'text'=>"❗ Sizda profil rasmi topilmadi.",
            'reply_to_message_id'=>$mid,
        ]);
    }
}
//




if(mb_stripos($cmd,"#love") !== false){ 
$ex = explode(" ",$tx);
bot('SendPhoto',[
'chat_id'=>$cid, 'reply_to_message_id'=>$mid,
'photo'=>"http://www.iloveheartstudio.com/-/p.php?t=%EE%BB%AE$ex[1]%EE%BB%AE$ex[2]%20$ex[3]%0A$ex[4]%0D%0A%20%20%EE%BB%AELOVE%EE%BB%AE&bc=000000&tc=ffffff&hc=FF0000&f=n&uc=true&ts=true&ff=PNG&w=500&ps=sq",
'caption'=>"By @admin",
]);
}


///
if($cmd=="#leavechat" &&$uid==$admin) {
  bot('sendmessage', [
      'chat_id' => $cid,
      'text' => "<b>Ho‘p xo‘jayin, guruhni tark etaman!</b>.",
      'parse_mode' => 'html'
  ]);
  bot('leaveChat',[
    'chat_id'=>$cid
  ]);
}

//stat

if($tx=="/stat" and  $cid==$admin){
$lich = substr_count($lichka,"\n");
$gr = substr_count($gruppa,"\n");
$jami = $lich + $gr;
 $soat = date('H:i:s', strtotime('5 hour'));
$bugun = date('d-M Y',strtotime('5 hour'));
bot('sendmessage',[
'chat_id'=>$cid,
    'text'=> "🔷<b> Bot statistikasi:</b>\n\n👤 A'zolar: <b>$lich</b>\n👥 Guruhlar: <b>$gr</b>\n📣 Umumiy: <b>$jami</b>\n\n$bugun $soat",
'parse_mode' => 'html',
]);
}
///

		if(stripos($tx,"soat") !== false){
		$soat = date('H:i:s', strtotime('5 hour'));
  $text = "⏰ Hozir soat: *$soat*";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mesid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode' => 'markdown'
  ]));
}

		if(stripos($tx,"sana") !== false){
        $bugun = date('d-M Y',strtotime('5 hour'));
  $text = "📆 Bugungi sana: *$bugun*";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mesid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode'=> 'markdown'
  ]));
}

if(stripos($cmd,"#id") !== false){
  $text = "Sizning🆔Kodingiz*`$uid`";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mesid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode'=> 'markdown'
  ]));
}

if(stripos($cmd,"#gid") !== false){
  $text = "*Guruhning🆔Kodi:* $cid";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mesid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode'=> 'markdown'
  ]));
}

if($cmd == "#vaqt"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"*📆Bugun: $sana1-yil
⌚Soat: $soat1
📅Oy nomi: $oynomi
📅Yilning: $sana2-kuni
⏳Vaqt mintaqasi: $gmt
📅Bu oy $buoy kundan iborat*",
'parse_mode'=>"markdown",
]);
}

//warn
$soni = file_get_contents("data/$cid/$uid.db");
	if(stripos($cmd,"#warn") !==false){
$cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
$azo = bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$repid
	]);
$yoz = $azo->result->status;

if($yoz=="member"){


   if($soni>=3){
   $kickm = bot('kickChatMember', [
        'chat_id' => $cid,
        'user_id' => $repid,
        'can_send_messages' => false,
        'can_send_media_messages' => false,
        'can_send_other_messages' => false,
        'can_add_web_page_previews' => false
    ]);
   if($kickm->ok){
        bot('sendMessage', [
        'chat_id' =>$cid,
        'text' => "<b></b><a href='tg://user?id=$repid'>$rname</a><b></b> <b>siz gruppadan chiqarildingiz,chunki shuncha ogohlantirishlarga parvo qilmadingiz!</b>",
        'parse_mode' => 'html'
    ]);
    unlink("data/$cid/$uid.db");
    }
    
}else{
    $hisob = $soni + 1;
$ok = file_put_contents("data/$cid/$uid.db","$hisob");
$soni = file_get_contents("data/$cid/$uid.db");
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"<b></b><a href='tg://user?id=$repid'>$rname</a><b></b>  <b>Siz ogohlantirish oldiz!
Ogohlantirishlar soni:</b> <code>$soni/4</code>",'parse_mode'=>"html"
	]);
	
}

}
}
}


//nowarn
$soni = file_get_contents("data/$cid/$uid.db");
	if(stripos($cmd,"#nowarn") !==false){
$cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
$azo = bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$repid
	]);
$yoz = $azo->result->status;

if($yoz=="member"){    
if($soni){
  bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"<b></b><a href='tg://user?id=$repid'>$rname</a><b></b>    

<b>sizdagi ogohlantirishlar:</b><code>0/4</code>",'parse_mode'=>"html"
]);
unlink("data/$cid/$uid.db");
}else{
 bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"<b></b><a href='tg://user?id=$repid'>$rname</a><b></b>    

<b>menimcha u ogohlantirish olmagan😊</b> ",'parse_mode'=>"html"
]);
}
}
}
}
//mute
if ($cmd=="#unmute" or $cmd=="#Unmute"){
	$cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
 $ok= bot('restrictChatMember',[
    'chat_id'=>$cid,
    'user_id'=>$repid,
    'can_send_messages'=>true,
    'can_send_media_messages'=>true,
    'can_send_other_messages'=>true,
    'can_add_web_page_previews'=>true
  ]);
 if($ok->ok){
  bot('sendmessage',[
    'chat_id'=>$cid,
    'text'=>"<a href='tg://user?id=$repid'>$rname</a><b>siz gruppada yozishingiz mumkin</b>",
    'parse_mode'=>"html"
    ]);
}
}
}



if ($cmd=="#mute" or $cmd=="#Mute") {
	$cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
$minut = strtotime("+30 minutes");
   $ok = bot('restrictChatMember', [
        'chat_id' => $cid,
        'user_id' => $repid,
        'until_date' => $minut,
        'can_send_messages' =>false,
        'can_send_media_messages' => false,
        'can_send_other_messages' => false,
        'can_add_web_page_previews' => false
    ]);
   if($ok->ok){
    bot('sendmessage', [
        'chat_id' =>$cid,
        'text' => "<a href='tg://user?id=$repid'>$rname</a><b>siz gruppada 30 minutga yozishdan mahrum etildingiz</b>",
        'parse_mode' => 'html'
    ]);
}
 }    
}
//
if($cmd=="#pin" or $cmd=="#Pin"){
    $cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
    bot('pinchatmessage',[
    'chat_id'=>$cid,
    'message_id'=>$rmid,
    ]);
}
}

    if($cmd == "#Kick"  or  $cmd == "#kick"){
$gett = bot('getChatMember', [
'chat_id' => $cid,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){
  $vaqti = strtotime("+720 minutes");
  bot('kickChatMember', [
      'chat_id' => $cid,
      'user_id' => $repid,
      'until_date'=> $vaqti,
  ]);
  bot('unbanChatMember', [
        'chat_id' => $cid,
        'user_id' => $repid,
    ]);
  bot('sendChatAction',['chat_id'=>$cid,'action'=>"typing"]);
  bot('sendmessage', [
      'chat_id' => $cid,
      'text' => "🔹 <a href='tg://user?id=$repid'>$rname</a> guruhdan 6 Soatga <b>Kick</b> bo‘ldi! 6 Soatdan keyin guruhga yana kirishi mumkun",
      'parse_mode' => 'html'
  ]);
}
}

if($cmd =="#ban" or $cmd == "#Ban"){
  $gett = bot('getChatMember', [
    'chat_id' => $cid,
    'user_id' => $uid,
  ]);
  $get = $gett->result->status;
  if($get == "administrator" or $get == "creator"){
       $vaqti = strtotime("+43200 minutes");
      bot('kickChatMember', [
        'chat_id' => $cid,
        'user_id' => $repid,
        'until_date' => $vaqti,
      ]);
    bot('sendChatAction',['chat_id'=>$cid,'action'=>"typing"]);
    bot('sendMessage', [
        'chat_id'=>$cid,
        'text' => "🔹 <a href='tg://user?id=$repid'>$rname</a> guruhdan 30 Kunga <b>ban</b> bo‘ldi! 30 Kundan keyin guruhga yana kirishi mumkun",
        'parse_mode'=>'html'
    ]);
  }
  }






//inline
$userID = $update->inline_query->from->id;
$theQuery = $update->inline_query->query;
$cid = $update->inline_query->query;
if(mb_stripos($cid,"@")!==false){
$user = bot("getchat",[
	'chat_id'=>$cid,
	]);
$type = $user->result->type;
$id = $user->result->id;
$us = bot('getChatMemberCount',[
	'chat_id'=>$cid
	]);
	$count = $us->result;
if($type=="channel"){
bot('answerInlineQuery', [
'inline_query_id'=>$update->inline_query->id,
'cache_time'=>1,
'results'=>json_encode([[
'type'=>'article',
'id'=>base64_encode(1),
'title'=>"$cid\nhaqida ma'lumot",
'input_message_content'=>[
'disable_web_page_preview'=>true,
'parse_mode' => 'markdown',
'message_text'=>"*📡Kanal useri:*  [$cid]\n*👥A'zolari*: `$count`\n*🆔Kanal id:* `$id`",
],
'caption'=>"By @bot",
'reply_markup' =>
[ 'inline_keyboard'=>[
                   [["switch_inline_query"=>"@", 'text' => "🆔Aniqlash"],],
               ] ],

]
])
]);
}
//end
if($type=="supergroup"){
bot('answerInlineQuery', [
'inline_query_id'=>$update->inline_query->id,
'cache_time'=>1,
'results'=>json_encode([[
'type'=>'article',
'id'=>base64_encode(1),
'title'=>"$cid\ngruppasi haqida ma'lumot",
'input_message_content'=>[
'disable_web_page_preview'=>true,
'parse_mode' => 'markdown',
'message_text'=>"*📡Gruppa useri:*  [$cid]\n*👥 Gruppa a'zolari*: `$count`\n*🆔Gruppa id:* `$id`",
],
'caption'=>"By @bot",
'reply_markup' =>
[ 'inline_keyboard'=>[
                   [["switch_inline_query"=>"@", 'text' => "🆔Aniqlash"],],
               ] ],

]
])
]);
}
}
//media
$qid = $update->callback_query->id;
$cid2 = $update->callback_query->message->chat->id;
$from2 = $update->callback_query->from->id;
$mid2 = $update->callback_query->message->message_id;

$data = $update->callback_query->data;
if($data){
	
$ty = bot('getchatmember',[
	'chat_id'=>$cid2,
	'user_id'=>$from2
	]);
$ty = $ty->result->status;
if($ty=="administrator" or $ty=="creator"){

bot('answercallbackquery',[
	'callback_query_id'=>$qid
	]);	
         if($data=="rasm"){         
              $stic = file_get_contents("data/$cid2/rasm.db");
              if($stic){
              if($stic=="on"){
              	file_put_contents("data/$cid2/rasm.db","off");
              }
              if($stic=="off"){
              	file_put_contents("data/$cid2/rasm.db","on");
              }
          }else{
          	file_put_contents("data/$cid2/rasm.db","on");
          }
        $ssl = file_get_contents("data/$cid2/ssilka.db");
         $stic = file_get_contents("data/$cid2/stic.db");
          $ras = file_get_contents("data/$cid2/rasm.db");
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);
$join = file_get_contents("data/$cid2/join.db");
          $gif = file_get_contents("data/$cid2/gif.db");
          $ovoz = file_get_contents("data/$cid2/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
        bot("editMessageReplyMarkup",[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
             'reply_markup' => json_encode([
                'inline_keyboard'=>[
                   [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz       $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif            $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],
             
 ]
                ]),
        ]);
    }
    

     if($data=="ssl"){
  $ssl = file_get_contents("data/$cid2/ssilka.db");
         if($ssl){
         if($ssl=="on"){
         file_put_contents("data/$cid2/ssilka.db","off");
         }
         if($ssl=="off"){
         file_put_contents("data/$cid2/ssilka.db","on");
         }
         }else{
         file_put_contents("data/$cid2/ssilka.db","on");
         } 
          $ssl = file_get_contents("data/$cid2/ssilka.db");
          $stic = file_get_contents("data/$cid2/stic.db");
          $ras = file_get_contents("data/$cid2/rasm.db");
$join = file_get_contents("data/$cid2/join.db");
          $gif = file_get_contents("data/$cid2/gif.db");
          $ovoz = file_get_contents("data/$cid2/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);
        bot("editMessageReplyMarkup",[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
             'reply_markup' => json_encode([
                'inline_keyboard'=>[
                    [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz       $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif            $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],

                    ]
        ]),
        ]);
    }


    if($data=="stic"){
  $ssl = file_get_contents("data/$cid2/stic.db");
         if($ssl){
         if($ssl=="on"){
         file_put_contents("data/$cid2/stic.db","off");
         }
         if($ssl=="off"){
         file_put_contents("data/$cid2/stic.db","on");
         }
         }else{
         file_put_contents("data/$cid2/stic.db","on");
         } 
          $ssl = file_get_contents("data/$cid2/ssilka.db");
          $stic = file_get_contents("data/$cid2/stic.db");
          $ras = file_get_contents("data/$cid2/rasm.db");
           $join = file_get_contents("data/$cid2/join.db");
          $gif = file_get_contents("data/$cid2/gif.db");
          $ovoz = file_get_contents("data/$cid2/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);
        bot("editMessageReplyMarkup",[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
             'reply_markup' => json_encode([
                'inline_keyboard'=>[
                 [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz       $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif            $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],
                    ]
                    ]),
        ]);
    }

//JOIN
  if($data=="join"){
  $ssl = file_get_contents("data/$cid2/join.db");
         if($ssl){
         if($ssl=="on"){
         file_put_contents("data/$cid2/join.db","off");
         }
         if($ssl=="off"){
         file_put_contents("data/$cid2/join.db","on");
         }
         }else{
         file_put_contents("data/$cid2/join.db","on");
         } 
          $ssl = file_get_contents("data/$cid2/ssilka.db");
          $stic = file_get_contents("data/$cid2/stic.db");
          $ras = file_get_contents("data/$cid2/rasm.db");
           $join = file_get_contents("data/$cid2/join.db");
          $gif = file_get_contents("data/$cid2/gif.db");
          $ovoz = file_get_contents("data/$cid2/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);
        bot("editMessageReplyMarkup",[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
             'reply_markup' => json_encode([
                'inline_keyboard'=>[
                  [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz       $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif            $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],
                    ]
                    ]),
        ]);
    }
//ovoz
  if($data=="ovoz"){
  $ssl = file_get_contents("data/$cid2/ovoz.db");
         if($ssl){
         if($ssl=="on"){
         file_put_contents("data/$cid2/ovoz.db","off");
         }
         if($ssl=="off"){
         file_put_contents("data/$cid2/ovoz.db","on");
         }
         }else{
         file_put_contents("data/$cid2/ovoz.db","on");
         } 
          $ssl = file_get_contents("data/$cid2/ssilka.db");
          $stic = file_get_contents("data/$cid2/stic.db");
          $ras = file_get_contents("data/$cid2/rasm.db");
           $join = file_get_contents("data/$cid2/join.db");
          $gif = file_get_contents("data/$cid2/gif.db");
          $ovoz = file_get_contents("data/$cid2/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);
        bot("editMessageReplyMarkup",[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
             'reply_markup' => json_encode([
                'inline_keyboard'=>[
                  [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz        $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif             $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],
                    ]
                    ]),
        ]);
    }
//gif
  if($data=="gif"){
  $ssl = file_get_contents("data/$cid2/gif.db");
         if($ssl){
         if($ssl=="on"){
         file_put_contents("data/$cid2/gif.db","off");
         }
         if($ssl=="off"){
         file_put_contents("data/$cid2/gif.db","on");
         }
         }else{
         file_put_contents("data/$cid2/gif.db","on");
         } 
          $ssl = file_get_contents("data/$cid2/ssilka.db");
          $stic = file_get_contents("data/$cid2/stic.db");
          $ras = file_get_contents("data/$cid2/rasm.db");
           $join = file_get_contents("data/$cid2/join.db");
          $gif = file_get_contents("data/$cid2/gif.db");
          $ovoz = file_get_contents("data/$cid2/ovoz.db");
          $join =  str_replace("on","✅",$join);
          $join = str_replace("off","☑️",$join); 
          $gif =  str_replace("on","✅",$gif);
          $gif = str_replace("off","☑️",$gif);
          $ovoz =  str_replace("on","✅",$ovoz);
          $ovoz = str_replace("off","☑️",$ovoz);
          $ssl =  str_replace("on","✅",$ssl);
          $ssl = str_replace("off","☑️",$ssl);
          $stic =  str_replace("on","✅",$stic);
          $stic = str_replace("off","☑️",$stic);
          $ras =  str_replace("on","✅",$ras);
          $ras = str_replace("off","☑️",$ras);
        bot("editMessageReplyMarkup",[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
             'reply_markup' => json_encode([
                'inline_keyboard'=>[
                  [['text'=>"🖼Rasm   $ras",'callback_data'=>'rasm'],['text'=>"🎤Ovoz        $ovoz",'callback_data'=>'ovoz']],
            [['text'=>"🎁Sticker $stic",'callback_data'=>'stic'],['text'=>"🎭Gif             $gif",'callback_data'=>'gif']],
            [['text'=> "🗝Ssilka   $ssl",'callback_data'=>'ssl'],['text'=>"👑Forward $join",'callback_data'=>'join']],
                    ]
                    ]),
        ]);
    }


 }else{
bot('answercallbackquery',[
	'callback_query_id'=>$qid
	]);
}
}
$userID = $update->inline_query->from->id;
$theQuery = $update->inline_query->query;
$cid = $update->inline_query->query;
/////

?>
