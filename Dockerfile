FROM php:8.2-apache-bookworm
# DIQQAT: versiyasiz "php:8.2-apache" endi Debian "trixie" asosida quriladi,
# va trixie'da "docker-php-ext-install" (curl/mbstring o'rnatishda) build vaqtida
# "xz: Failed to enable the sandbox" xatosi bilan buziladi (docker-library/php'ning
# hozircha hal qilinmagan muammosi). Shu sabab aniq "bookworm" versiyasiga
# qulflab qo'ydik — u barqaror ishlaydi.

# --- Kerakli PHP kengaytmalari ---
# curl -> Telegram API bilan gaplashish uchun
# mbstring -> matn (mb_stripos va h.k.) funksiyalari uchun
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install curl mbstring \
    && rm -rf /var/lib/apt/lists/*

# --- OPcache: PHP kodini har safar qayta compile qilmaslik uchun (tezlik uchun juda muhim) ---
RUN docker-php-ext-enable opcache \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=64'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# --- Apache: bir nechta so'rovni parallel qayta ishlashi uchun prefork MPM + ko'proq worker ---
RUN a2enmod mpm_prefork \
    && sed -i 's/^\s*StartServers.*/StartServers 5/' /etc/apache2/mods-available/mpm_prefork.conf \
    && sed -i 's/^\s*MinSpareServers.*/MinSpareServers 5/' /etc/apache2/mods-available/mpm_prefork.conf \
    && sed -i 's/^\s*MaxSpareServers.*/MaxSpareServers 15/' /etc/apache2/mods-available/mpm_prefork.conf \
    && sed -i 's/^\s*MaxRequestWorkers.*/MaxRequestWorkers 60/' /etc/apache2/mods-available/mpm_prefork.conf

WORKDIR /var/www/html
COPY . /var/www/html/

# data/ papkasi va .db fayllari Apache foydalanuvchisi nomidan yozilishi kerak
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

# Railway konteynerga $PORT orqali qaysi portni tinglashni aytadi (odatda 80 emas).
# Apache doim 80-portni tinglashga sozlangan, shuning uchun konteyner ishga tushganda
# uni haqiqiy $PORT qiymatiga moslab qo'yamiz.
RUN printf '#!/bin/bash\nset -e\nPORT="${PORT:-8080}"\nsed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf\nsed -ri "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]
