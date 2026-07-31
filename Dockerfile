FROM php:8.2-apache-bookworm
# DIQQAT: versiyasiz "php:8.2-apache" endi Debian "trixie" asosida quriladi,
# va trixie'da "docker-php-ext-install" (curl/mbstring o'rnatishda) build vaqtida
# "xz: Failed to enable the sandbox" xatosi bilan buziladi (docker-library/php'ning
# hozircha hal qilinmagan muammosi). Shu sabab aniq "bookworm" versiyasiga
# qulflab qo'ydik — u barqaror ishlaydi.

# --- Kerakli PHP kengaytmalari ---
# curl -> Telegram API bilan gaplashish uchun
# mbstring -> matn (mb_stripos va h.k.) funksiyalari uchun (oniguruma kutubxonasi kerak)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev \
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

# --- Apache: bir nechta so'rovni parallel qayta ishlashi uchun prefork MPM sozlamalari ---
# (MPM'ni yoqish/o'chirishning o'zi endi pastdagi start.sh ichida, konteyner
# ISHGA TUSHGANDA bajariladi — build vaqtida qilingani Railway'da ishonchli
# ishlamadi: "AH00534: More than one MPM loaded" xatosini berardi.)
RUN sed -i 's/^\s*StartServers.*/StartServers 5/' /etc/apache2/mods-available/mpm_prefork.conf \
    && sed -i 's/^\s*MinSpareServers.*/MinSpareServers 5/' /etc/apache2/mods-available/mpm_prefork.conf \
    && sed -i 's/^\s*MaxSpareServers.*/MaxSpareServers 15/' /etc/apache2/mods-available/mpm_prefork.conf \
    && sed -i 's/^\s*MaxRequestWorkers.*/MaxRequestWorkers 60/' /etc/apache2/mods-available/mpm_prefork.conf

WORKDIR /var/www/html
COPY . /var/www/html/

# data/ papkasi va .db fayllari Apache foydalanuvchisi nomidan yozilishi kerak
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

RUN cp /var/www/html/start.sh /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]
