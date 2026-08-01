<?php
// Bu faylni deploy qilgandan keyin BIR MARTA brauzerda oching:
//   https://SIZNING-DOMEN.up.railway.app/set_webhook.php
// Webhook o'rnatilgach, xavfsizlik uchun bu faylni o'chirib qo'ying
// (yoki repodan olib tashlab qayta deploy qiling).

$token = getenv('BOT_TOKEN') ?: '';
if (!$token) {
    die("BOT_TOKEN environment variable topilmadi.");
}

// Railway o'zi beradigan domenni avtomatik aniqlashga harakat qiladi,
// aks holda qo'lda pastdagi $url ni o'zgartiring.
$host = $_SERVER['HTTP_HOST'] ?? getenv('RAILWAY_PUBLIC_DOMAIN');
if (!$host) {
    die("Domenni aniqlab bo'lmadi. Skript ichida \$url ni qo'lda yozing.");
}
$url = "https://{$host}/index.php";

$res = file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url));
echo "<pre>";
echo "Webhook manzili: $url\n\n";
echo $res;
echo "</pre>";

// Guruhlarda "/" bosilganda chiqadigan buyruqlar ro'yxati
$group_commands = [
    ['command' => 'adm',       'description' => "Admin berish"],
    ['command' => 'admn',      'description' => "Adminga to'liq huquq berish"],
    ['command' => 'delmn',     'description' => "Adminlikdan olish"],
    ['command' => 'unban',     'description' => "Bandan olish"],
    ['command' => 'warn',      'description' => "Ogohlantirish berish"],
    ['command' => 'nowarn',    'description' => "Ogohlantirishlarni olib tashlash"],
    ['command' => 'mute',      'description' => "30 daqiqaga yozishdan cheklash"],
    ['command' => 'unmute',    'description' => "Yozishga ruxsat berish"],
    ['command' => 'pin',       'description' => "Xabarni yuqoriga qadash"],
    ['command' => 'kick',      'description' => "Guruhdan vaqtincha chiqarish"],
    ['command' => 'ban',       'description' => "Guruhdan ban qilish"],
    ['command' => 'panel',     'description' => "Guruh sozlamalari paneli"],
    ['command' => 'leavechat', 'description' => "Botni guruhdan chiqarish"],
    ['command' => 'vaqt',      'description' => "Vaqt haqida ma'lumot"],
    ['command' => 'id',        'description' => "Foydalanuvchi ID sini olish"],
    ['command' => 'gid',       'description' => "Guruh ID sini olish"],
];
$res2 = file_get_contents("https://api.telegram.org/bot{$token}/setMyCommands?"
    . "commands=" . urlencode(json_encode($group_commands))
    . "&scope=" . urlencode(json_encode(['type' => 'all_group_chats'])));
echo "<pre>";
echo "Guruh buyruqlari ro'yxati (setMyCommands):\n\n";
echo $res2;
echo "</pre>";

// Shaxsiy (private) chatda "/" bosilganda chiqadigan buyruqlar
$private_commands = [
    ['command' => 'start',   'description' => "Botni ishga tushirish"],
    ['command' => 'profil',  'description' => "Profil rasmingizni ko'rish"],
];
$res3 = file_get_contents("https://api.telegram.org/bot{$token}/setMyCommands?"
    . "commands=" . urlencode(json_encode($private_commands))
    . "&scope=" . urlencode(json_encode(['type' => 'all_private_chats'])));
echo "<pre>";
echo "Shaxsiy chat buyruqlari ro'yxati (setMyCommands):\n\n";
echo $res3;
echo "</pre>";

// /admin buyrug'i FAQAT bosh administratorning o'zi bilan bo'lgan shaxsiy
// chatda "/" menyusida chiqadi (scope=chat + chat_id=ADMIN_ID) — boshqa
// hech qaysi foydalanuvchi buni o'z menyusida ko'rmaydi.
$admin_id = getenv('ADMIN_ID') ?: '';
if ($admin_id) {
    $admin_commands = array_merge($private_commands, [
        ['command' => 'admin', 'description' => "🛠 Admin panel"],
    ]);
    $res4 = file_get_contents("https://api.telegram.org/bot{$token}/setMyCommands?"
        . "commands=" . urlencode(json_encode($admin_commands))
        . "&scope=" . urlencode(json_encode(['type' => 'chat', 'chat_id' => $admin_id])));
    echo "<pre>";
    echo "Admin uchun shaxsiy buyruqlar ro'yxati (setMyCommands):\n\n";
    echo $res4;
    echo "</pre>";
} else {
    echo "<pre>ADMIN_ID o'rnatilmagani uchun /admin buyrug'i menyuga qo'shilmadi.</pre>";
}
