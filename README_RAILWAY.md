# StudyFlix — versão preparada para Railway

Esta pasta contém a conversão do projeto para executar em Railway usando **PHP + Apache + PostgreSQL** sem depender das credenciais antigas do Render.

## O que foi alterado

- Dockerfile compatível com Railway usando PHP 8.3 + Apache.
- Apache ajustado em runtime para escutar a variável `PORT` fornecida pelo Railway.
- Credenciais de banco removidas do código-fonte.
- Banco configurado por `DATABASE_URL` ou pelas variáveis `PG*`.
- Migração automática em segundo plano durante a inicialização do container.
- O Apache inicia mesmo se o PostgreSQL estiver temporariamente indisponível, evitando que uma falha de banco derrube todo o domínio.
- Tentativas automáticas de conexão enquanto o PostgreSQL ainda estiver iniciando.
- Criação automática das tabelas `usuarios`, `questions` e `user_scores`.
- Seed idempotente com questões mínimas para validar o quiz em um banco novo.
- Endpoint de saúde em `/health.php`.
- Sessão PHP configurada para funcionar atrás do proxy HTTPS do Railway.
- Login e cadastro mantidos em PHP/PostgreSQL, com mensagens de erro seguras.
- `submit_answer.php` passa a usar o usuário autenticado da sessão em vez de confiar no e-mail enviado pelo navegador.
- Endpoints antigos de teste, Render, MongoDB e manipulação direta do ranking foram removidos.
- Logout funcional em `/logout.php` e `/api/logout.php`.
- Proteção básica de cabeçalhos HTTP e bloqueio de listagem de diretórios.

## Estrutura importante

```text
Dockerfile
api/
  cadastro.php
  db_config.php
  get_question.php
  get_ranking.php
  login.php
  logout.php
  session.php
  submit_answer.php
  user_data.php
database/
  schema.sql
scripts/
  migrate.php
docker/
  apache-studyflix.conf
  entrypoint.sh
health.php
logout.php
```

Os diretórios `database/`, `scripts/` e `docker/` são usados durante a construção/execução do container e são removidos do diretório público servido pelo Apache.

# Deploy pelo painel do Railway

## 1. Suba o projeto para o GitHub

O `Dockerfile` deve permanecer na raiz do repositório. O Railway detecta automaticamente um Dockerfile chamado exatamente `Dockerfile`.

## 2. Crie o serviço web

No Railway:

1. Crie um projeto.
2. Escolha **Deploy from GitHub Repo**.
3. Selecione o repositório do StudyFlix.

Não é necessário configurar um comando de build ou de start manualmente. O Dockerfile já contém o processo completo.

## 3. Adicione PostgreSQL

No mesmo projeto, adicione um serviço PostgreSQL.

Depois abra **Variables** no serviço do StudyFlix e crie uma Reference Variable:

```text
DATABASE_URL=${{Postgres.DATABASE_URL}}
```

Se o serviço do PostgreSQL tiver outro nome, substitua `Postgres` pelo nome real do serviço.

Opcionalmente, para alterar as tentativas de conexão durante o startup:

```text
DB_CONNECT_RETRIES=20
DB_CONNECT_RETRY_SECONDS=3
```

Os valores padrão já são 20 tentativas com intervalo de 3 segundos, então normalmente não é necessário defini-los.

## 4. Não defina PORT manualmente

O Railway fornece `PORT` automaticamente. O `docker/entrypoint.sh` altera a porta do Apache em runtime para usar exatamente esse valor.

## 5. Gere o domínio público

No serviço web, abra **Settings > Networking** e gere um domínio Railway.

## 6. Configure o healthcheck

Em **Settings > Deploy > Healthcheck Path**, use:

```text
/health.php
```

O endpoint retorna HTTP 200 quando o servidor PHP/Apache está disponível.

## 7. Faça o deploy

Na inicialização, o Apache é configurado imediatamente para `0.0.0.0:$PORT` e a migração é executada em segundo plano:

```text
php /opt/studyflix/scripts/migrate.php
```

O processo:

1. configura e inicia o Apache na porta fornecida pelo Railway;
2. em paralelo, aguarda o PostgreSQL ficar disponível;
3. cria/valida as tabelas;
4. insere as questões mínimas de teste.

Se o banco estiver sem `DATABASE_URL`, o domínio continua respondendo e `/health.php` indica `database_configured: false`; apenas as funções que dependem do banco ficam indisponíveis até a variável ser corrigida.

# Roteiro de teste em produção

## Teste 1 — servidor

Abra:

```text
/health.php
```

Resultado esperado:

```json
{"status":"ok","service":"studyflix","timestamp":"..."}
```

## Teste 2 — cadastro

Abra:

```text
/cadaster.html
```

Crie um usuário novo. O usuário deve ser salvo na tabela `usuarios` e a sessão deve ser criada automaticamente.

## Teste 3 — login

Abra:

```text
/logout.php
/login.html
```

Entre usando o usuário criado no teste anterior.

## Teste 4 — quiz

Abra:

```text
/questoese.html
```

Selecione uma das áreas:

- Natureza
- Humanas
- Matemática
- Linguagens

Ao responder, o backend valida a sessão e atualiza `user_scores`.

## Teste 5 — ranking

Abra a aba de ranking em `questoese.html` e confirme se os acertos/tentativas foram atualizados.

## Teste 6 — logout

Abra:

```text
/logout.php
```

A sessão deve ser encerrada e o navegador deve voltar para `login.html`.

# Banco de dados

O esquema fica em:

```text
database/schema.sql
```

A migração fica em:

```text
scripts/migrate.php
```

O código aceita `DATABASE_URL` ou, alternativamente:

- `PGHOST`
- `PGPORT`
- `PGDATABASE`
- `PGUSER`
- `PGPASSWORD`
- `PGSSLMODE` (opcional)

# Dados do banco antigo

Esta conversão cria um banco Railway novo e funcional, mas **não copia automaticamente usuários, ranking ou questões de um banco externo existente**. Para manter dados do banco anterior, é necessário exportar e importar um dump PostgreSQL.

# Sessões e quantidade de réplicas

Para os testes de produção, mantenha o serviço com **1 réplica**. As sessões PHP permanecem no filesystem local do container. Para múltiplas réplicas, o próximo passo técnico é mover as sessões para Redis ou PostgreSQL.

# Segurança importante

O código original continha uma credencial PostgreSQL do Render escrita diretamente nos arquivos. Ela foi removida desta versão. A senha/credencial antiga deve ser rotacionada no provedor antigo, porque segredo colocado em código deve ser tratado como comprometido.


# Diagnóstico do erro `Application failed to respond`

Esta versão V2 foi endurecida especificamente para evitar o 502 do Railway causado por startup bloqueado.

Após o deploy, abra primeiro:

```text
/health.php
```

Se ele responder, o Apache e a porta pública estão corretos. O JSON também mostra se o banco foi configurado, sem expor credenciais.

Nos Deploy Logs deve aparecer algo semelhante a:

```text
[StudyFlix] Apache configurado em 0.0.0.0:8080
Syntax OK
```

O número pode ser diferente de 8080, pois é o valor recebido em `PORT`.

Se o domínio ainda mostrar `Application failed to respond`, verifique em **Settings > Networking** se existe um `Target Port` manual. Se existir, ele precisa apontar para a mesma porta mostrada no log acima; preferencialmente remova uma configuração manual incorreta e deixe o Railway usar a porta do serviço.
