#!/bin/bash
set -e

# Faqat mpm_prefork yoqilgan bo'lishi kerak (mod_php shuni talab qiladi).
# Buni build vaqtida emas, aynan shu yerda — konteyner ishga tushayotganda
# qilish kerak, aks holda Railway'da "AH00534: More than one MPM loaded"
# xatosi bilan konteyner qulab tushardi.
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway konteynerga $PORT orqali qaysi portni tinglashni aytadi (odatda 80 emas).
PORT="${PORT:-8080}"
sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

apache2ctl -t
exec apache2-foreground
