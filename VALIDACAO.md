# Validação da V3 MongoDB

Validações executadas neste pacote antes da geração do ZIP:

- 13 arquivos PHP verificados com `php -l`: **0 erros de sintaxe**;
- 55 HTMLs com JavaScript inline analisados, total de 64 blocos JS: **0 erros de sintaxe**;
- 18 referências locais em HTML verificadas: **0 caminhos inexistentes**;
- `docker/entrypoint.sh` verificado com `sh -n`: **OK**;
- template do Nginx testado após substituir `__PORT__` por `8080`: `nginx -t` **OK**;
- `/health.php` executado localmente via PHP: **HTTP 200**;
- `index.html` executado localmente via PHP: **HTTP 200**;
- API sem MongoDB disponível retorna **503 controlado**, sem fatal error;
- busca nos arquivos de runtime: **0 referências a PDO, PostgreSQL, DATABASE_URL, PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD ou schema.sql**;
- não existe arquivo de configuração Apache no pacote;
- não existe `schema.sql` nem migration PostgreSQL no pacote;
- bootstrap MongoDB usa índices + upsert e não apaga dados existentes.

## Limite da validação local

O ambiente de geração deste pacote não possui Docker Engine nem um MongoDB Railway real conectado. Portanto, o build completo da imagem e a autenticação contra o `MONGO_URL` do seu projeto só podem ser confirmados durante o deploy no Railway.

O entrypoint foi feito para validar `php-fpm -t` e `nginx -t` no próprio container antes de iniciar o serviço. Se houver falha de configuração no build/runtime, ela aparecerá diretamente nos Deploy Logs em vez de gerar um loop obscuro do Apache.
