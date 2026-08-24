# StudyFlix - Railway + MongoDB (V3)

Esta versão substitui integralmente o PostgreSQL por MongoDB e remove o Apache.
O servidor web agora é **Nginx + PHP-FPM**, eliminando o erro do Apache:
`AH00534: Configuration error: More than one MPM loaded.`

## Estrutura no Railway

Crie dois serviços no mesmo projeto:

1. `StudyFlix` - repositório GitHub com este projeto.
2. `MongoDB` - Add New Service > Database > MongoDB.

No serviço **StudyFlix > Variables**, crie uma referência para o MongoDB:

```text
MONGO_URL=${{MongoDB.MONGO_URL}}
```

Se o serviço tiver outro nome, troque `MongoDB` pelo nome real do serviço.

Opcionalmente defina:

```text
MONGO_DATABASE=studyflix
```

Se `MONGO_DATABASE` não existir, o backend usa o banco informado no caminho da URI; se a URI não trouxer um nome, usa `studyflix`.

## Porta

Não crie `PORT` manualmente no Railway. O container lê a `PORT` injetada pela plataforma e gera a configuração do Nginx em tempo de boot.

O log esperado é parecido com:

```text
[StudyFlix] Nginx configurado em 0.0.0.0:8080
configuration file ... test is successful
[StudyFlix] MongoDB pronto. database=studyflix, questions_seed=8
```

## Healthcheck

Use:

```text
/health.php
```

A resposta deve indicar:

```json
{
  "status": "ok",
  "web_server": "nginx+php-fpm",
  "database_type": "mongodb",
  "database_configured": true
}
```

O healthcheck retorna HTTP 200 mesmo se o MongoDB estiver temporariamente indisponível. Isso evita que um erro do banco derrube o site inteiro.

## Coleções criadas automaticamente

O boot cria/usa:

- `usuarios`
- `questions`
- `user_scores`

Também cria índices únicos para e-mail, `question_id` e username do ranking, além de inserir 8 questões iniciais de teste sem apagar dados já existentes.

## Fluxos convertidos para MongoDB

- cadastro;
- login;
- sessão;
- consulta do usuário;
- questões aleatórias por área;
- validação de resposta;
- pontuação;
- ranking;
- bootstrap/seed;
- healthcheck.

## Importante

Esta V3 não usa mais:

- PostgreSQL;
- `DATABASE_URL`;
- `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`;
- PDO PostgreSQL;
- Apache;
- MPM do Apache;
- `schema.sql`.

Portanto, não reutilize as variáveis PostgreSQL da V2.
