#!/usr/bin/env sh
set -eu

PORT_VALUE="${PORT:-8080}"

case "$PORT_VALUE" in
  ''|*[!0-9]*)
    echo "[StudyFlix] ERRO: PORT inválida: $PORT_VALUE" >&2
    exit 1
    ;;
esac

# Gera a configuração do nginx com a porta dinâmica do Railway.
sed "s/__PORT__/${PORT_VALUE}/g" \
  /etc/nginx/templates/studyflix.conf.template \
  > /etc/nginx/conf.d/default.conf

echo "[StudyFlix] Nginx configurado em 0.0.0.0:${PORT_VALUE}"

# Falha de configuração derruba o deploy imediatamente, em vez de entrar em loop silencioso.
php-fpm -t
nginx -t

# O PHP-FPM é iniciado antes do nginx para que /health.php responda assim que o domínio abrir.
php-fpm -D

# Preparação do MongoDB roda em paralelo. Banco indisponível não impede o site estático/healthcheck de subir.
(
    if php /opt/studyflix/scripts/bootstrap_mongo.php; then
        echo "[StudyFlix] MongoDB preparado com sucesso."
    else
        echo "[StudyFlix] AVISO: preparação do MongoDB falhou. O site continuará online; login/cadastro/quiz dependem do MONGO_URL." >&2
    fi
) &

exec nginx -g 'daemon off;'
