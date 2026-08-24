# Validação da conversão Railway

Validações executadas sobre esta versão:

- PHP: todos os arquivos `.php` passaram em `php -l` sem erro de sintaxe.
- JavaScript: scripts inline dos 55 arquivos HTML passaram em `node --check` sem erro de sintaxe.
- Referências locais: nenhum link local, CSS, script, imagem local ou `form action` aponta para arquivo inexistente.
- Credenciais: nenhuma credencial PostgreSQL antiga permanece gravada no código de produção.
- Healthcheck: `/health.php` foi executado localmente e retornou HTTP 200 com JSON válido.
- Falha sem banco: a API de login retorna HTTP 503 com mensagem controlada quando o PostgreSQL não está configurado, sem expor senha ou string de conexão.
- Migração: `scripts/migrate.php` é sintaticamente válido e contém criação idempotente das tabelas + seed das questões de teste.
- Entrypoint: `docker/entrypoint.sh` passou na validação de sintaxe do shell.

## Validação que depende do Railway

A conexão real com o PostgreSQL do Railway e o build do container precisam ser confirmados no primeiro deploy, porque dependem do `DATABASE_URL`, da rede privada e do projeto Railway que será criado pelo proprietário do sistema.

O roteiro de conferência pós-deploy está em `README_RAILWAY.md`.

## Correção V2 — Railway 502

O startup foi alterado para que o Apache seja configurado e iniciado independentemente do resultado da migração PostgreSQL. A migração agora roda em segundo plano e uma falha de `DATABASE_URL` não encerra o processo web. O Apache escuta explicitamente em `0.0.0.0:$PORT`.
