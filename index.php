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

// --- Admin panel orqali o'zgartiriladigan sozlamalar (settings.json) ---
// Bu yerda saqlangan qiymat bo'lsa, u ENV dagi ADMIN_ID'dan ustun turadi —
// shu orqali admin botni qayta deploy qilmasdan turib o'z ID'sini almashtira oladi.
function load_settings(){
    $file = __DIR__.'/settings.json';
    if(!file_exists($file)) return [];
    $s = json_decode(file_get_contents($file), true);
    return is_array($s) ? $s : [];
}
function save_setting($key, $value){
    $file = __DIR__.'/settings.json';
    $s = load_settings();
    $s[$key] = $value;
    @file_put_contents($file, json_encode($s, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
$settings = load_settings();
if(!empty($settings['admin_id'])){
    $admin = (string)$settings['admin_id'];
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
    // Telegram sekin javob bersa ham so'rov abadiy osilib qolmasin
    // (bitta osilib qolgan chaqiruv butun botni sekinlashtirar edi)
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
    curl_setopt($ch,CURLOPT_TIMEOUT,10);
    curl_setopt($ch,CURLOPT_TCP_NODELAY,true);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        // Ilgari bu yerda var_dump ishlatilgan — u javob tanasiga chiqib,
        // ba'zan Telegramga yuboriladigan JSON javobni buzardi. Endi jim log qiladi.
        error_log('Telegram API xatosi ('.$method.'): '.curl_error($ch));
    }
    curl_close($ch);
    return json_decode($res);
}

// "👤Admin" tugmasi HAR DOIM hozirgi adminning shaxsiy Telegram profiliga (lichkasiga)
// ishora qilishi uchun — uning username'ini admin ID orqali avtomatik topib, keshlaydi.
// Agar admin o'zgartirilsa (settings.json'dagi admin_id), kesh ham avtomatik yangilanadi.
function get_admin_username($adminId){
    if(!$adminId) return '';
    $cacheFile = __DIR__.'/data/adminuser.json';
    $cache = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : null;
    if(is_array($cache) && (string)($cache['id'] ?? '') === (string)$adminId && !empty($cache['username'])){
        return $cache['username'];
    }
    $chat = bot('getChat', ['chat_id'=>$adminId]);
    $username = $chat->result->username ?? '';
    if($username){
        @file_put_contents($cacheFile, json_encode(['id'=>$adminId,'username'=>$username]));
    }
    return $username;
}

$channel_url = $settings['channel_url'] ?? 'https://t.me/Sinalgan_PHP_kodlar';
if(!empty($settings['admin_url'])){
    // Admin qo'lda o'zgartirgan bo'lsa, shuni ishlatamiz.
    $admin_url = $settings['admin_url'];
}else{
    // Aks holda hozirgi adminning shaxsiy profiliga avtomatik havola qo'yamiz.
    $adminUsername = get_admin_username($admin);
    $admin_url = $adminUsername ? 'https://t.me/'.$adminUsername : 'https://t.me/'.ltrim((string)$admin,'@');
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

// Xabardagi BARCHA entity (mention/url/text_link va h.k.) larni tekshiradi,
// faqat birinchisini emas — aks holda link matn ichida birinchi bo'lmasa filtrdan qochib ketardi.
function has_entity_type($message, array $types){
    if (!isset($message->entities) || !is_array($message->entities)) return false;
    foreach ($message->entities as $e) {
        if (isset($e->type) && in_array($e->type, $types, true)) return true;
    }
    return false;
}
// Foydalanuvchi ismi/guruh nomi ichida <,>,& kabi belgilar bo'lsa,
// parse_mode=html xabar butunlay yuborilmay qolardi ("can't parse entities" xatosi
// bilan Telegram uni rad etadi). Bu funksiya shunday belgilarni xavfsiz qilib beradi.
function esc_html($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Admin panel klaviaturasi — shart ichida emas, doim aniqlangan bo'lishi kerak,
// aks holda callback_query (tugma bosilganda) kabi $ty=="private" bo'lmaydigan
// holatlarda "undefined function" xatosi bilan butun so'rov qulab tushardi.
function admin_panel_keyboard(){
    return [
      'inline_keyboard'=>[
        [['text'=>'📊 Statistika','callback_data'=>'adm_stat']],
        [['text'=>'📨 Foydalanuvchilarga xabar','callback_data'=>'adm_send']],
        [['text'=>'📢 Guruhlarga xabar','callback_data'=>'adm_sendgr']],
        [['text'=>"📚 O'rgangan so'zlar",'callback_data'=>'adm_doc'],['text'=>'🗑 Bazani tozalash','callback_data'=>'adm_deldoc']],
        [['text'=>'⚙️ Sozlamalar','callback_data'=>'adm_settings']],
        [['text'=>'❌ Yopish','callback_data'=>'adm_close']],
      ]
    ];
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
$ty = $message->chat->type;
$title = $message->chat->title;
$repid = $message->reply_to_message->from->id;
$gruppa = file_get_contents("gruppa.db");
$lichka = file_get_contents("lichka.db");

// Guruh papkasini va ro'yxatga olishni ENG BOSHIDA qilamiz — aks holda
// /panel yoki boshqa sozlama o'qiydigan buyruqlar guruhning birinchi
// xabarida hali yaratilmagan papkani o'qishga urinib, bo'sh/xato natija
// berardi.
if($ty=="supergroup" or $ty=="group"){
if(!is_dir("data/$cid")){ @mkdir("data/$cid", 0777, true); }
$gruppa_list = array_filter(explode("\n",$gruppa), fn($v)=>$v!=="");
if(!in_array((string)$cid, $gruppa_list, true)){
file_put_contents("gruppa.db","$gruppa\n$cid");
}
}

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
if(($type=="supergroup" or $type=="group") and is_string($text) and $text!==""){
    $ex = $msgs[$text] ?? null;
    if($ex){
        $ex = explode("|",$ex);
        $txt = $ex[rand(0,count($ex)-1)];
        bot("sendmessage",[
            'chat_id'=>$message->chat->id,
            'text'=>"$txt",
            'reply_to_message_id'=>$mid
            ]);
    }
}
// DIQQAT: Bu yerda avval har bir yuborilgan xabarda (guruhdagi barcha yozishmalarda)
// getChatMemberCount so'rovi Telegram API'ga yuborilar edi, garchi natija ($azo)
// faqat "yangi a'zo qo'shildi" xabarida ishlatilsa ham. Bu botni sezilarli darajada
// sekinlashtirgan asosiy sabablardan biri edi — endi faqat kerak bo'lganda chaqiriladi.
$azo = null;

//Yangi odam id si
$new_chat_members = $message->new_chat_member->id;
$new_first_name = $message->new_chat_member->first_name;
$new_username = $message->new_chat_member->username ?? null;
$lan = $message->new_chat_member->language_code;
$first_name = $message->from->first_name;
$is_bot = $message->new_chat_member->is_bot;
$ismcha = $message->from->first_name;
$familiya = $message->from->last_name;
$bio1 = $message->from->about;
$login = $message->from->username;

// HTML xabarlarga qo'yiladigan ismlarning xavfsiz (escape qilingan) nusxalari —
// <,>,& kabi belgilar borligida xabar yuborilmay qolishining oldini oladi.
$rname_safe = esc_html($rname);
$title_safe = esc_html($title);
$from_user_first_name_safe = esc_html($from_user_first_name);
$new_first_name_safe = esc_html($new_first_name);
$ismcha_safe = esc_html($ismcha);
$login_safe = esc_html($login);

$soat1 = date('H:i:s',strtotime('5 hour')); 
$sana1 = date('d-M Y',strtotime('5 hour'));
$sana2 = date('z',strtotime('5 hour'));
$gmt = date('O',strtotime('5 hour'));
$oynomi = date('F',strtotime('5 hour'));
$buoy = date('t',strtotime('5 hour'));

if($replytx){
    if($type=="supergroup"  or $type=="group"){
   	$replytx = $message->reply_to_message->text;
   	$existing = $msgs[$replytx] ?? '';
   	      	if($existing !== '' && strpos($existing,"$text") !==false){
   	}else{
		$msgs[$replytx] = ($existing !== '') ? "$text|$existing" : "$text";
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
if(!$id){
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"❗ Admin qilmoqchi bo'lgan odamning xabariga <b>reply</b> qilib #adm yozing.",
    'parse_mode'=>'html'
  ]);
}else{
  $promo = bot('promoteChatMember',[
    'chat_id'=>$chat_id,
    'user_id'=>$id,
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
  if($promo->ok){
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"✅ <a href='tg://user?id=$id'>$from_user_first_name_safe</a> sizni tabriklayman , siz guruh <b>adminstratorisiz❗️</b>",
    'parse_mode'=>'html'
  ]);
  }else{
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"❌ Admin berib bo'lmadi. Botning o'zi guruhda <b>\"Yangi adminlar qo'shish\"</b> huquqiga ega admin ekanini tekshiring.",
    'parse_mode'=>'html'
  ]);
  }
}
}
}

if($cmd=="#unban" or $cmd=="#Unban"){
$gett = bot('getChatMember', [
  'chat_id' => $chat_id,
  'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){ 
if(!$id){
    bot('sendmessage',[
        'chat_id'=>$chat_id,
        'text'=>"❗ Banni olib tashlamoqchi bo'lgan odamning (eski) xabariga <b>reply</b> qilib #unban yozing.",
        'parse_mode'=>'html',
    ]);
}else{
    $ub = bot('unbanChatMember',[    
    'chat_id'=>$chat_id,    
    'user_id'=>$id,    
  ]);    
    if($ub->ok){
    bot('sendmessage',[
        'chat_id'=>$chat_id,
        'text'=>"<a href='tg://user?id=$id'>$rname_safe</a> Admin ruhsati bilan😎 <b>Bandan</b> olindi!",
        'parse_mode'=>'html',
    ]);
    }else{
    bot('sendmessage',[
        'chat_id'=>$chat_id,
        'text'=>"❌ Amalga oshmadi. Bu odam banlangan ro'yxatda emasligi mumkin.",
        'parse_mode'=>'html',
    ]);
    }
}
}
}

if($cmd == "#Admn" or $cmd == "#admn"){
$gett = bot('getChatMember', [
'chat_id' => $chat_id,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){
if(!$id){
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"❗ Admin qilmoqchi bo'lgan odamning xabariga <b>reply</b> qilib #admn yozing.",
    'parse_mode'=>'html'
  ]);
}else{
  $promo = bot('promoteChatMember',[
    'chat_id'=>$chat_id,
    'user_id'=>$id,
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
  if($promo->ok){
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"✅ <a href='tg://user?id=$id'>$from_user_first_name_safe</a> sizni tabriklayman , siz guruh <b>adminstratorisiz❗️</b>",
    'parse_mode'=>'html'
  ]);
  }else{
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"❌ Admin berib bo'lmadi. Botning o'zi guruhda \"Yangi adminlar qo'shish\" huquqiga ega admin ekanini tekshiring.",
    'parse_mode'=>'html'
  ]);
  }
}
}
}

if($cmd == "#Delmn" or $cmd == "#delmn"){
$gett = bot('getChatMember', [
'chat_id' => $chat_id,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get == "administrator" or $get == "creator"){
if(!$id){
  bot('sendmessage',[
    'chat_id'=>$chat_id,
    'text'=>"❗ Admin huquqini olib tashlamoqchi bo'lgan odamning xabariga <b>reply</b> qilib #delmn yozing.",
    'parse_mode'=>'html'
  ]);
}else{
$demo = bot('promoteChatMember',[
    'chat_id'=>$chat_id,
    'user_id'=>$id,
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
  if($demo->ok){
  bot('sendmessage',[
    'chat_id'=> $chat_id,
    'text'=>"☑ <a href='tg://user?id=$id'>$from_user_first_name_safe</a> siz endi guruh adminstratori <b>emassiz</b>❗️",
    'parse_mode'=>'html'
  ]);
  }else{
  bot('sendmessage',[
    'chat_id'=> $chat_id,
    'text'=>"❌ Amalga oshmadi. Botning o'zi guruhda admin ekanini va uni admindan tushira olish huquqi borligini tekshiring.",
    'parse_mode'=>'html'
  ]);
  }
}
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
      $uname_part = $new_username ? " (@$new_username)" : "";
      $mem = bot('getChatMemberCount',['chat_id'=>$cid]);
      $azo = $mem->result ?? '?';
      $test = "🤝<b>Assalomu alaykum</b>, Hurmatli <a href='tg://user?id=$new_chat_members'>$new_first_name_safe</a>$uname_part, <b>$title_safe</b> guruhiga xush kelibsiz!
👥 Guruh a'zolari soni: $azo";
       bot('sendmessage',[
       'chat_id'=>$cid,
       'text'=>$test,
       'parse_mode'=>'html'
     ]);
   }
    }
////
   if (($new_chat_members != NUll)&&($is_bot!=false)&&(strcasecmp((string)$new_username, (string)$botUsername) !== 0)) {
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
$lichka_list = array_filter(explode("\n",$lichka), fn($v)=>$v!=="");
if(!in_array((string)$cid, $lichka_list, true)){
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
💥 /panel *buyrug'i orqali botni o'z guruhingizga moslab olishingiz mumkin!*
👨🏻‍💻 Coder :@Ozodovv56

*Shuningdek bot inline rejimda kanal va gruppa haqida ma'lumot ham beradi!
Sinab ko'rish tugmasi orqali tekshirib korishingiz mumkin!*",
'parse_mode'=>"markdown",
'reply_markup' => json_encode([
                'inline_keyboard'=> array_merge([
                   [['text'=>"➕Guruhga qo‘shish",'url'=>'https://t.me/'.$botUsername.'?startgroup=new']],
[['text'=>'🔰Kanalimiz','url'=>$channel_url],['text'=>'👤Admin','url'=>$admin_url]], 
                 [['text'=>'✔️Buyruqlar','callback_data'=>'buyruq'],['text'=>'☑️Qoshimcha Buyruqlar','callback_data'=>'qoshimcha']], 
[['text'=>'📲Telegram Tillari🇺🇿','callback_data'=>'til'],['text'=>"🆔Sinash",'switch_inline_query'=>"@"]],
                ], ($cid==$admin) ? [[['text'=>'🛠 Admin panel','callback_data'=>'adm_open']]] : [])
])
]);
}

// --- /admin: bot egasi uchun boshqaruv paneli ---
if($tx=="/admin" and $cid==$admin){
bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"🛠 <b>Admin panel</b>\n\nKerakli bo'limni tanlang:",
'parse_mode'=>'html',
'reply_markup'=>json_encode(admin_panel_keyboard())
]);
}
} 
$edit_text = $update->edited_message->text ?? '';
$chat_edit_id = $update->edited_message->chat->id ?? null;
$message_edit_id = $update->edited_message->message_id ?? null;
if($edit_text && $chat_edit_id && preg_match("/(https?:\/\/|t\.me\/|telegram\.me\/)/i", $edit_text)) {  
bot('deletemessage',[
    'chat_id'=>$chat_edit_id,
    'message_id'=>$message_edit_id
    ]);
}

if(has_entity_type($message, ["mention"])  and $ssl=="on"){
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
    bot('answercallbackquery',['callback_query_id'=>$update->callback_query->id ?? null]);
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
    bot('answercallbackquery',['callback_query_id'=>$update->callback_query->id ?? null]);
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
    bot('answercallbackquery',['callback_query_id'=>$update->callback_query->id ?? null]);
    bot('sendmessage',[
        'chat_id'=>$cid2,
        'text'=>"*👋 Assalom Alaykum!*
👨‍✈️`@bot` *ni Gruppangizga Admin qilsangiz:
🛡 Gruppangizni botlardan himoya qiladi.
😷 Reklamalarni Tozalaydi.
⭕️ Kirdi chiqdilarni tozalaydi.
🔞 Video, Sticker, Reklama va boshqalarni o'chiradi!
💎 Va yana Koplab vazifalarni bajaradi!*
💥 /panel *buyrug'i orqali botni o'z guruhingizga moslab olishingiz mumkin!*

*Shuningdek bot inline rejimda kanal va gruppa haqida ma'lumot ham beradi!
Sinab ko'rish tugmasi orqali tekshirib korishingiz mumkin!*",
'parse_mode'=>"markdown",
'reply_markup' => json_encode([
                'inline_keyboard'=>[
                 [['text'=>"➕Guruhga qo‘shish",'url'=>'https://t.me/'.$botUsername.'?startgroup=new']],
[['text'=>'🔰Kanalimiz','url'=>$channel_url],['text'=>'👤Admin','url'=>$admin_url]], 
                 [['text'=>'✔️Buyruqlar','callback_data'=>'buyruq'],['text'=>'☑️Qoshimcha Buyruqlar','callback_data'=>'qoshimcha']], 
[['text'=>'📲Telegram Tillari🇺🇿','callback_data'=>'tillar'],['text'=>"🆔Sinash",'switch_inline_query'=>"@"]],
]
])
]);
}


//
 if(has_entity_type($message, ["url"]) and $ssl=="on"){
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
if(has_entity_type($message, ["text_link"]) and $ssl=="on"){
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
'text'=>"<b>Men $title_safe gruppasida qayta ishga tushirildim😜</b>",
'parse_mode'=>"html"
]);

}else{

bot('sendmessage',[
'chat_id'=>$cid,
'text'=>"<b>Men $title_safe gruppasida ishga tushirildim😃</b>",
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

if($cid==$admin and $reply=="🆔 Yangi admin ID raqamini kiriting (Telegram foydalanuvchi ID'si, faqat raqam):"){
    $new_admin_id = trim($tx);
    if($new_admin_id !== '' && ctype_digit(ltrim($new_admin_id,'-'))){
        $old_admin = $admin;
        save_setting('admin_id', $new_admin_id);
        bot('sendmessage',[
            'chat_id'=>$old_admin,
            'text'=>"✅ Admin ID muvaffaqiyatli o'zgartirildi: <code>".esc_html($new_admin_id)."</code>\nEndi shu ID admin sifatida ishlaydi.",
            'parse_mode'=>'html',
        ]);
        if((string)$new_admin_id !== (string)$old_admin){
            bot('sendmessage',[
                'chat_id'=>$new_admin_id,
                'text'=>"👋 Siz endi ushbu botning admini etib tayinlandingiz.",
            ]);
        }
    }else{
        bot('sendmessage',[
            'chat_id'=>$admin,
            'text'=>"❗ Noto'g'ri format. Faqat raqamlardan iborat Telegram ID kiriting.",
        ]);
    }
}

if($cid==$admin and $reply=="🔰 Yangi kanal havolasini kiriting (masalan: https://t.me/mychannel):"){
    $new_channel = trim($tx);
    if($new_channel !== ''){
        save_setting('channel_url', $new_channel);
        bot('sendmessage',[
            'chat_id'=>$admin,
            'text'=>"✅ Kanal havolasi yangilandi: ".esc_html($new_channel),
        ]);
    }
}

if($cid==$admin and $reply=="👤 Yangi admin (aloqa) havolasini kiriting (masalan: https://t.me/username, yoki avtomatikka qaytarish uchun \"avtomatik\" deb yozing):"){
    $new_adminlink = trim($tx);
    if(mb_strtolower($new_adminlink)==="avtomatik" or mb_strtolower($new_adminlink)==="auto"){
        save_setting('admin_url', '');
        bot('sendmessage',[
            'chat_id'=>$admin,
            'text'=>"✅ Admin havolasi avtomatik rejimga qaytarildi — endi hozirgi adminning shaxsiy profiliga ishora qiladi.",
        ]);
    }elseif($new_adminlink !== ''){
        save_setting('admin_url', $new_adminlink);
        bot('sendmessage',[
            'chat_id'=>$admin,
            'text'=>"✅ Admin havolasi yangilandi: ".esc_html($new_adminlink),
        ]);
    }
}

if($tx=="/send" and $cid==$admin){
    bot('sendmessage',[
'chat_id'=>$admin,
'text'=>"📨 Yuboriladigan xabar matnini kiriting (foydalanuvchilarga). Xabar turi markdown",'parse_mode'=>"markdown",'reply_markup'=>$rpl
]);
}
    if($reply=="📨 Yuboriladigan xabar matnini kiriting (foydalanuvchilarga). Xabar turi markdown"){
        $lich_content = file_get_contents("lichka.db");
        $lich_list = explode("\n",$lich_content);
foreach($lich_list as $luid){
    if(!$luid) continue;
    bot("sendmessage",[
        'chat_id'=>$luid,
        'text'=>"$tx"]);
}
}
//sendgroup

     if($tx == "/sendgr" and $cid == $admin){
    bot('sendmessage',[
'chat_id'=>$admin,
'text'=>"📨 Yuboriladigan xabar matnini kiriting (guruhlarga). Xabar turi markdown",'parse_mode'=>"markdown",'reply_markup'=>$rpl
]);
}
    if($reply=="📨 Yuboriladigan xabar matnini kiriting (guruhlarga). Xabar turi markdown"){
        $gr_content = file_get_contents("gruppa.db");
        $gr_list = explode("\n",$gr_content);
foreach($gr_list as $gcid){
    if(!$gcid) continue;
    bot("sendmessage",[
        'chat_id'=>$gcid,
      'text'=>$tx,
      'parse_mode'=>'markdown',
      'disable_web_page_preview' => true,
      ]);
      }
         if($gr_content){
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

if((mb_stripos($tx,"@admin") !== false) or (mb_stripos($tx,"Alimardon")!==false) or (mb_stripos($tx,"admin")!==false)){ 
bot('SendMessage',[
'chat_id'=>$admin,
'parse_mode'=>'html',
'text'=>"✉<b>$title_safe(</b>  $chat_id  <b>) guruhida sizni eslashdi:</b>\n<code>".esc_html($tx)."</code>\n  <b>Xabarchi  haqida  ma'lumotlar: </b>
👤<b>Ismi:</b>  <a href='tg://user?id=$uid'>$ismcha_safe</a>
🆔<b>ID</b>si: $uid
🔅<b>Usernamesi:</b> @$login_safe ", null, false
      ]);
   }
   
   
    
    if((stripos($tx,"/sms") !== false) and $cid == $admin){
    $ex = explode("-",$tx);
    if(isset($ex[1]) && isset($ex[2])){
    bot('sendMessage',[
    'chat_id'=>trim($ex[1]),
    'text'=>trim($ex[2]),
    ]);
    }
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
if(!isset($ex[1]) || !isset($ex[2]) || !isset($ex[3]) || !isset($ex[4])){
bot('sendmessage',[
'chat_id'=>$cid, 'reply_to_message_id'=>$mid,
'text'=>"❗ To'g'ri foydalanish: <code>#love So'z1 So'z2 So'z3 So'z4</code> (4 ta so'z kerak)",
'parse_mode'=>'html',
]);
}else{
bot('SendPhoto',[
'chat_id'=>$cid, 'reply_to_message_id'=>$mid,
'photo'=>"http://www.iloveheartstudio.com/-/p.php?t=%EE%BB%AE$ex[1]%EE%BB%AE$ex[2]%20$ex[3]%0A$ex[4]%0D%0A%20%20%EE%BB%AELOVE%EE%BB%AE&bc=000000&tc=ffffff&hc=FF0000&f=n&uc=true&ts=true&ff=PNG&w=500&ps=sq",
'caption'=>"By @admin",
]);
}
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
   'reply_to_message_id'=>$mid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode' => 'markdown'
  ]));
}

		if(stripos($tx,"sana") !== false){
        $bugun = date('d-M Y',strtotime('5 hour'));
  $text = "📆 Bugungi sana: *$bugun*";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode'=> 'markdown'
  ]));
}

if(stripos($cmd,"#id") !== false){
  $text = "Sizning🆔Kodingiz: `$uid`";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mid,
   'chat_id'=>$cid,
   'text'=>$text,
   'parse_mode'=> 'markdown'
  ]));
}

if(stripos($cmd,"#gid") !== false){
  $text = "*Guruhning🆔Kodi:* $cid";
  $a=json_encode(bot('sendmessage',[
   'reply_to_message_id'=>$mid,
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
	if(stripos($cmd,"#warn") !==false){
$cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
if(!$repid){
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"❗ Ogohlantirish bermoqchi bo'lgan odamning xabariga <b>reply</b> qilib #warn yozing.",
	'parse_mode'=>'html'
	]);
}else{
$soni = intval(@file_get_contents("data/$cid/$repid.db"));
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
        'text' => "<b></b><a href='tg://user?id=$repid'>$rname_safe</a><b></b> <b>siz gruppadan chiqarildingiz,chunki shuncha ogohlantirishlarga parvo qilmadingiz!</b>",
        'parse_mode' => 'html'
    ]);
    unlink("data/$cid/$repid.db");
    }
    
}else{
    $hisob = $soni + 1;
$ok = file_put_contents("data/$cid/$repid.db","$hisob");
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"<b></b><a href='tg://user?id=$repid'>$rname_safe</a><b></b>  <b>Siz ogohlantirish oldiz!
Ogohlantirishlar soni:</b> <code>$hisob/4</code>",'parse_mode'=>"html"
	]);
	
}

}else{
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"❗ Admin yoki guruh egasiga ogohlantirish berib bo'lmaydi.",
	'parse_mode'=>'html'
	]);
}
}
}
}


//nowarn
	if(stripos($cmd,"#nowarn") !==false){
$cr=bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$uid
	]);
$cr = $cr->result->status;
if($cr=="creator" or $cr=="administrator"){
if(!$repid){
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"❗ Ogohlantirishlarini olib tashlamoqchi bo'lgan odamning xabariga <b>reply</b> qilib #nowarn yozing.",
	'parse_mode'=>'html'
	]);
}else{
$soni = file_get_contents("data/$cid/$repid.db");
$azo = bot('getchatmember',[
	'chat_id'=>$cid,
	'user_id'=>$repid
	]);
$yoz = $azo->result->status;

if($yoz=="member"){    
if($soni){
  bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"<b></b><a href='tg://user?id=$repid'>$rname_safe</a><b></b>    

<b>sizdagi ogohlantirishlar:</b><code>0/4</code>",'parse_mode'=>"html"
]);
unlink("data/$cid/$repid.db");
}else{
 bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"<b></b><a href='tg://user?id=$repid'>$rname_safe</a><b></b>    

<b>menimcha u ogohlantirish olmagan😊</b> ",'parse_mode'=>"html"
]);
}
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
if(!$repid){
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"❗ Mute'dan chiqarmoqchi bo'lgan odamning xabariga <b>reply</b> qilib #unmute yozing.",
	'parse_mode'=>'html'
	]);
}else{
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
    'text'=>"<a href='tg://user?id=$repid'>$rname_safe</a><b>siz gruppada yozishingiz mumkin</b>",
    'parse_mode'=>"html"
    ]);
}else{
  bot('sendmessage',[
    'chat_id'=>$cid,
    'text'=>"❌ Amalga oshmadi. Botning o'zi guruhda \"A'zolarni cheklash\" huquqiga ega admin ekanini tekshiring.",
    'parse_mode'=>"html"
    ]);
}
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
if(!$repid){
bot('sendmessage',[
	'chat_id'=>$cid,
	'text'=>"❗ Mute qilmoqchi bo'lgan odamning xabariga <b>reply</b> qilib #mute yozing.",
	'parse_mode'=>'html'
	]);
}else{
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
        'text' => "<a href='tg://user?id=$repid'>$rname_safe</a><b>siz gruppada 30 minutga yozishdan mahrum etildingiz</b>",
        'parse_mode' => 'html'
    ]);
}else{
    bot('sendmessage', [
        'chat_id' =>$cid,
        'text' => "❌ Mute qila olmadim. Botning o'zi guruhda \"A'zolarni cheklash\" huquqiga ega admin ekanini va bu odam sizdan yuqori admin emasligini tekshiring.",
        'parse_mode' => 'html'
    ]);
}
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
if(!$rmid){
    bot('sendmessage',[
    'chat_id'=>$cid,
    'text'=>"❗ Pin qilmoqchi bo'lgan xabarga <b>reply</b> qilib #pin yozing.",
    'parse_mode'=>'html',
    ]);
}else{
    $pn = bot('pinchatmessage',[
    'chat_id'=>$cid,
    'message_id'=>$rmid,
    ]);
    if(!$pn->ok){
    bot('sendmessage',[
    'chat_id'=>$cid,
    'text'=>"❌ Pin qila olmadim. Botning o'zi guruhda \"Xabarlarni pin qilish\" huquqiga ega admin ekanini tekshiring.",
    'parse_mode'=>'html',
    ]);
    }
}
}
}

    if($cmd == "#Kick"  or  $cmd == "#kick"){
$gett = bot('getChatMember', [
'chat_id' => $cid,
'user_id' => $uid,
]);
$get = $gett->result->status;
if($get =="administrator" or $get == "creator"){
if(!$repid){
  bot('sendmessage',[
    'chat_id'=>$cid,
    'text'=>"❗ Kick qilmoqchi bo'lgan odamning xabariga <b>reply</b> qilib #kick yozing.",
    'parse_mode'=>'html'
  ]);
}else{
  $vaqti = strtotime("+360 minutes");
  $kk = bot('kickChatMember', [
      'chat_id' => $cid,
      'user_id' => $repid,
      'until_date'=> $vaqti,
  ]);
  bot('unbanChatMember', [
        'chat_id' => $cid,
        'user_id' => $repid,
    ]);
  bot('sendChatAction',['chat_id'=>$cid,'action'=>"typing"]);
  if($kk->ok){
  bot('sendmessage', [
      'chat_id' => $cid,
      'text' => "🔹 <a href='tg://user?id=$repid'>$rname_safe</a> guruhdan 6 Soatga <b>Kick</b> bo‘ldi! 6 Soatdan keyin guruhga yana kirishi mumkun",
      'parse_mode' => 'html'
  ]);
  }else{
  bot('sendmessage', [
      'chat_id' => $cid,
      'text' => "❌ Kick qila olmadim. Botning o'zi guruhda \"A'zolarni cheklash\" huquqiga ega admin ekanini tekshiring.",
      'parse_mode' => 'html'
  ]);
  }
}
}
}

if($cmd =="#ban" or $cmd == "#Ban"){
  $gett = bot('getChatMember', [
    'chat_id' => $cid,
    'user_id' => $uid,
  ]);
  $get = $gett->result->status;
  if($get == "administrator" or $get == "creator"){
  if(!$repid){
      bot('sendmessage',[
        'chat_id'=>$cid,
        'text'=>"❗ Ban qilmoqchi bo'lgan odamning xabariga <b>reply</b> qilib #ban yozing.",
        'parse_mode'=>'html'
      ]);
  }else{
       $vaqti = strtotime("+43200 minutes");
      $bb = bot('kickChatMember', [
        'chat_id' => $cid,
        'user_id' => $repid,
        'until_date' => $vaqti,
      ]);
    bot('sendChatAction',['chat_id'=>$cid,'action'=>"typing"]);
    if($bb->ok){
    bot('sendMessage', [
        'chat_id'=>$cid,
        'text' => "🔹 <a href='tg://user?id=$repid'>$rname_safe</a> guruhdan 30 Kunga <b>ban</b> bo‘ldi! 30 Kundan keyin guruhga yana kirishi mumkun",
        'parse_mode'=>'html'
    ]);
    }else{
    bot('sendMessage', [
        'chat_id'=>$cid,
        'text' => "❌ Ban qila olmadim. Botning o'zi guruhda \"A'zolarni cheklash\" huquqiga ega admin ekanini tekshiring.",
        'parse_mode'=>'html'
    ]);
    }
  }
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

// "Til" tugmasi (/start va "orqa" menyularida) hech qanday javob bermas edi —
// bosilganda hech narsa bo'lmasdi (o'chirilgandek ko'rinardi). Endi javob beradi.
if($data=="til" or $data=="tillar"){
    bot('answercallbackquery',[
        'callback_query_id'=>$qid,
        'text'=>"Til sozlamasi Telegram ilovasining o'zida: Settings → Language",
    ]);
}

// --- Admin panel tugmalari (faqat ADMIN_ID egasi uchun) ---
if(in_array($data, ['adm_open','adm_stat','adm_send','adm_sendgr','adm_doc','adm_deldoc','adm_close','adm_settings','adm_set_admin','adm_set_channel','adm_set_adminlink','adm_settings_view'], true)){
    if((string)$from2 !== (string)$admin){
        bot('answercallbackquery',[
            'callback_query_id'=>$qid,
            'text'=>"⛔ Sizga ruxsat yo'q.",
            'show_alert'=>true,
        ]);
    }else{
        bot('answercallbackquery',['callback_query_id'=>$qid]);
        $force_reply = json_encode(['resize_keyboard'=>false,'force_reply'=>true,'selective'=>true]);

        if($data=="adm_open"){
            bot('sendmessage',[
                'chat_id'=>$cid2,
                'text'=>"🛠 <b>Admin panel</b>\n\nKerakli bo'limni tanlang:",
                'parse_mode'=>'html',
                'reply_markup'=>json_encode(admin_panel_keyboard())
            ]);
        }

        if($data=="adm_stat"){
            $lich = substr_count($lichka,"\n");
            $gr = substr_count($gruppa,"\n");
            $jami = $lich + $gr;
            $soat = date('H:i:s', strtotime('5 hour'));
            $bugun = date('d-M Y',strtotime('5 hour'));
            bot('sendmessage',[
                'chat_id'=>$cid2,
                'text'=> "🔷<b> Bot statistikasi:</b>\n\n👤 A'zolar: <b>$lich</b>\n👥 Guruhlar: <b>$gr</b>\n📣 Umumiy: <b>$jami</b>\n\n$bugun $soat",
                'parse_mode' => 'html',
            ]);
        }

        if($data=="adm_send"){
            bot('sendmessage',[
                'chat_id'=>$admin,
                'text'=>"📨 Yuboriladigan xabar matnini kiriting (foydalanuvchilarga). Xabar turi markdown",
                'parse_mode'=>"markdown",
                'reply_markup'=>$force_reply,
            ]);
        }

        if($data=="adm_sendgr"){
            bot('sendmessage',[
                'chat_id'=>$admin,
                'text'=>"📨 Yuboriladigan xabar matnini kiriting (guruhlarga). Xabar turi markdown",
                'parse_mode'=>"markdown",
                'reply_markup'=>$force_reply,
            ]);
        }

        if($data=="adm_doc"){
            if(file_exists('msgs.json')){
                bot('senddocument',[
                    'chat_id'=>$cid2,
                    'document'=>new CURLFile('msgs.json'),
                ]);
            }else{
                bot('sendmessage',[
                    'chat_id'=>$cid2,
                    'text'=>"❗ Hali hech narsa o'rgatilmagan.",
                ]);
            }
        }

        if($data=="adm_deldoc"){
            @unlink('msgs.json');
            bot('sendmessage',[
                'chat_id'=>$cid2,
                'parse_mode'=>'markdown',
                'text'=>"*🗑Baza Tozalandi*",
            ]);
        }

        if($data=="adm_close"){
            bot('deletemessage',['chat_id'=>$cid2,'message_id'=>$mid2]);
        }

        if($data=="adm_settings"){
            bot('sendmessage',[
                'chat_id'=>$cid2,
                'text'=>"⚙️ <b>Sozlamalar</b>\n\nKerakli bo'limni tanlang:",
                'parse_mode'=>'html',
                'reply_markup'=>json_encode([
                  'inline_keyboard'=>[
                    [['text'=>'👁 Joriy sozlamalarni ko\'rish','callback_data'=>'adm_settings_view']],
                    [['text'=>"👤 Admin ID'ni o'zgartirish",'callback_data'=>'adm_set_admin']],
                    [['text'=>'🔰 Kanal havolasini o\'zgartirish','callback_data'=>'adm_set_channel']],
                    [['text'=>'👤 Admin havolasini o\'zgartirish','callback_data'=>'adm_set_adminlink']],
                    [['text'=>'◀️ Orqaga','callback_data'=>'adm_open']],
                  ]
                ])
            ]);
        }

        if($data=="adm_settings_view"){
            $adminlink_mode = !empty($settings['admin_url']) ? "(qo'lda o'rnatilgan)" : "(avtomatik)";
            bot('sendmessage',[
                'chat_id'=>$cid2,
                'text'=>"⚙️ <b>Joriy sozlamalar:</b>\n\n🆔 Admin ID: <code>".esc_html($admin)."</code>\n🔰 Kanal havolasi: ".esc_html($channel_url)."\n👤 Admin havolasi: ".esc_html($admin_url)." ".esc_html($adminlink_mode),
                'parse_mode'=>'html',
            ]);
        }

        if($data=="adm_set_admin"){
            bot('sendmessage',[
                'chat_id'=>$admin,
                'text'=>"🆔 Yangi admin ID raqamini kiriting (Telegram foydalanuvchi ID'si, faqat raqam):",
                'reply_markup'=>$force_reply,
            ]);
        }

        if($data=="adm_set_channel"){
            bot('sendmessage',[
                'chat_id'=>$admin,
                'text'=>"🔰 Yangi kanal havolasini kiriting (masalan: https://t.me/mychannel):",
                'reply_markup'=>$force_reply,
            ]);
        }

        if($data=="adm_set_adminlink"){
            bot('sendmessage',[
                'chat_id'=>$admin,
                'text'=>"👤 Yangi admin (aloqa) havolasini kiriting (masalan: https://t.me/username, yoki avtomatikka qaytarish uchun \"avtomatik\" deb yozing):",
                'reply_markup'=>$force_reply,
            ]);
        }
    }
}

// Faqat guruh panelidagi haqiqiy tugmalar (rasm/ssl/stic/join/ovoz/gif) uchun
// admin tekshiruvi ishga tushsin — boshqa (masalan shaxsiy chatdagi menyu)
// tugmalar uchun keraksiz getChatMember chaqirilmasin.
if(in_array($data, ['rasm','ssl','stic','join','ovoz','gif'], true)){
	
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
?>
