# Nazoratchi bot — Railway'ga deploy qilish

3 ta yuklagan faylingiz ichidan **`qoravul.php`** eng to'liq guruh-boshqaruv funksiyalariga ega edi:
`#warn`, `#nowarn`, `#mute`, `#unmute`, `#kick`, `#ban`, `#unban`, `#adm`/`#admn`/`#delmn`,
`#pin`, reklama/link filtri, rasm/sticker/gif/ovoz filtri (`#panel` orqali yoqib-o'chirish) va h.k.

Shu fayl asosida tozalab, Railway uchun tayyorlab berdim (`index.php`), bunda:

## ⚠️ Nima o'zgardi (muhim)

1. **Token va admin ID kod ichidan olib tashlandi.** Eski faylda ular ochiq yozilgan edi —
   bu juda xavfli, chunki kodni ko'rgan har kim botingizni to'liq boshqarib olishi mumkin edi.
   Endi ular Railway'ning "Variables" bo'limidan o'qiladi (`BOT_TOKEN`, `ADMIN_ID`).
2. **`#ban` buyrug'idagi xatolik tuzatildi** — eski kodda operator ustuvorligi xatosi tufayli
   guruhning **istalgan a'zosi** (admin bo'lmasa ham) `#ban` yozib odam banlashi mumkin edi.
   Endi faqat admin/creator ishlatishi mumkin.
3. **`#unban` buyrug'i tuzatildi** — eski kodda aniqlanmagan o'zgaruvchiga tekshirilgani
   uchun umuman ishlamas edi, va admin o'zini o'zi unban qilardi (repdagi odam o'rniga).
4. Fayl/papkalar yo'qligida chiqadigan PHP xatoliklarning oldi olindi (`data/<chat_id>/` papkalari
   avtomatik yaratiladi).

## 🚨 SIZ QILISHINGIZ SHART BO'LGAN ISH

Yuklagan fayllaringizdagi tokenlar ushbu suhbatda ochiq ko'rindi, ya'ni ular endi **kompromentatsiya qilingan** hisoblanadi. Deploy qilishdan oldin:

1. Telegram'da **@BotFather** ga o'ting → `/mybots` → botingizni tanlang → **API Token** →
   **Revoke current token** — yangi token oling.
2. Yangi tokenni Railway'dagi `BOT_TOKEN` o'zgaruvchisiga yozing (pastda ko'rsatilgan).

## 📁 Papka tarkibi

```
index.php        ← asosiy bot kodi (tozalangan qoravul.php)
composer.json     ← Railway PHP loyihasini shundan taniydi
Procfile          ← serverni ishga tushirish buyrug'i
set_webhook.php   ← webhookni bir marta o'rnatish uchun yordamchi skript
.gitignore        ← tokenlar/ma'lumot fayllari GitHub'ga tushmasligi uchun
.env.example      ← qaysi environment variable kerakligini ko'rsatadi
```

## 🚀 Deploy qadamlari

### 1. GitHub'ga yuklang
Bu papkani yangi GitHub repositoryga joylang (`.env` faylini **hech qachon** yuklamang —
u `.gitignore`da allaqachon istisno qilingan).

### 2. Railway'da loyiha yarating
- railway.app → **New Project** → **Deploy from GitHub repo** → repo'ni tanlang
- Railway avtomatik `composer.json`ni ko'rib PHP loyiha ekanini aniqlaydi va `Procfile`dagi
  buyruq bilan ishga tushiradi

### 3. Environment Variables qo'shing
Loyiha sozlamalarida **Variables** bo'limiga kiring va qo'shing:

| Nomi | Qiymati |
|---|---|
| `BOT_TOKEN` | BotFaterdan olgan **yangi** token |
| `ADMIN_ID` | Sizning Telegram ID raqamingiz (masalan @userinfobot orqali bilib oling) |

### 4. Domen oling
**Settings → Networking → Generate Domain** tugmasini bosing. Sizga
`https://xxxxx.up.railway.app` kabi manzil beriladi.

### 5. Webhookni o'rnating
Brauzerda quyidagi manzilga kiring (domeningizni qo'yib):
```
https://xxxxx.up.railway.app/set_webhook.php
```
`"ok":true` javobini ko'rsangiz — tayyor. **Shundan so'ng xavfsizlik uchun
`set_webhook.php` faylini repodan o'chirib qayta deploy qiling** (yoki hech bo'lmasa
kimga oshkor qilmang).

### 6. Botni tekshiring
Botni guruhga qo'shing, **administrator** qiling (ayniqsa "Ban users", "Delete messages",
"Restrict members", "Pin messages" huquqlari kerak), va `#panel` yozib tekshiring.

## ⚠️ Muhim eslatma: ma'lumotlar saqlanishi haqida

Bot ogohlantirishlar sonini, guruh sozlamalarini va h.k. oddiy `.db`/`.txt` fayllarga yozadi
(`data/` papkasida). **Railway'ning odatiy fayl tizimi doimiy emas** — har safar qayta
deploy qilinganda yoki konteyner qayta ishga tushganda bu fayllar **o'chib ketishi mumkin**
(warn hisoblari, "botga qo'shilgan" ro'yxatlari va h.k. nolga tushadi).

Buni oldini olish uchun Railway loyihangizga **Volume** qo'shing:
- Loyiha → **Settings → Volumes → New Volume**
- Mount path: `/app/data`

Bu faqat `data/` papkasini doimiy qiladi; ildizdagi `gruppa.db`, `lichka.db`, `msgs.json`
kabi fayllar hali ham konteyner bilan birga o'chadi — agar ular ham muhim bo'lsa, aytsangiz
kodni shu fayllarni ham `data/` ichiga ko'chiradigan qilib moslab beraman.

## 💡 Ixtiyoriy: uzoq muddatda

Bu kod fayl-asosli saqlashdan foydalanadi — kichik-o'rta guruhlar uchun yetarli, lekin
bir nechta guruh va ko'p foydalanuvchi bo'lsa, MySQL/PostgreSQL'ga o'tish tavsiya etiladi
(Railway'da bazani ham bir necha bosishda ulash mumkin). Xohlasangiz shu tomonga ham
yordam bera olaman.
