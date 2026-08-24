#!/usr/bin/env sh
set -eu

PORT_VALUE="${PORT:-8080}"

case "$PORT_VALUE" in
  ''|*[!0-9]*)
    echo "[StudyFlix] PORT inválida: $PORT_VALUE" >&2
    exit 1
    ;;
esac

# Garante que o banco esteja pronto e atualizado antes de liberar o servidor web.
php /opt/studyflix/scripts/migrate.php

# Railway injeta PORT em runtime. Apache precisa escutar exatamente nela.
sed -ri "s/^Listen [0-9]+/Listen ${PORT_VALUE}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_VALUE}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
