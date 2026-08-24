#!/usr/bin/env sh
set -eu

PORT_VALUE="${PORT:-8080}"

case "$PORT_VALUE" in
  ''|*[!0-9]*)
    echo "[StudyFlix] ERRO: PORT inválida: $PORT_VALUE" >&2
    exit 1
    ;;
esac

# Railway precisa alcançar o processo pela interface 0.0.0.0 e pela porta PORT.
# Configuramos o Apache antes de qualquer tentativa de banco, para o servidor web
# não ficar refém do PostgreSQL durante o boot.
sed -ri "s/^[[:space:]]*Listen[[:space:]]+[0-9]+$/Listen 0.0.0.0:${PORT_VALUE}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost[[:space:]]+\\*:[0-9]+>/<VirtualHost *:${PORT_VALUE}>/" /etc/apache2/sites-available/000-default.conf

echo "[StudyFlix] Apache configurado em 0.0.0.0:${PORT_VALUE}"
apache2ctl configtest

# A migração roda em segundo plano. Se o PostgreSQL ainda não estiver configurado
# ou pronto, isso NÃO derruba o Apache. Assim /health.php e as páginas estáticas
# continuam disponíveis e o Railway não devolve 502 apenas por causa do banco.
(
    if php /opt/studyflix/scripts/migrate.php; then
        echo "[StudyFlix] Banco preparado com sucesso."
    else
        echo "[StudyFlix] AVISO: migração do banco falhou. O site continuará online, mas login/cadastro/quiz dependerão da correção do DATABASE_URL." >&2
    fi
) &

exec apache2-foreground
