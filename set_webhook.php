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
