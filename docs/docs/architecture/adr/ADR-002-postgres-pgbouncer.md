---
title: "ADR-002: PostgreSQL + PgBouncer"
status: accepted
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [architecture, adr, database, postgres, pgbouncer]
---

# ADR-002: PostgreSQL + PgBouncer

## Status

`accepted`

> Complementa [ADR-001](./ADR-001-single-database-multitenancy.md) (multi-tenancy
> de banco único com `stancl/tenancy`). Aqui definimos o **SGBD** e o **pooling**.

## Contexto

O sistema oficial multi-tenant usa **banco único compartilhado** com `tenant_id`
(ADR-001). Precisamos escolher o SGBD e como gerenciar conexões, considerando que:

- Vários tenants compartilham o mesmo banco e a carga de conexões cresce com o
  número de tenants/requisições simultâneas (Laravel/PHP-FPM abre conexões por
  processo).
- Em **single-database**, **não** há troca de banco nem `SET search_path` por
  tenant — o isolamento é só por `tenant_id` na query. Isso simplifica o pooling.

## Decisão

1. **SGBD: PostgreSQL.** Banco único, isolamento lógico por `tenant_id`.
2. **Pooling: PgBouncer** entre a aplicação e o Postgres, para reaproveitar
   conexões e não esgotar o limite do banco sob muitos tenants/processos.
3. **Modo de pooling: `transaction`** como alvo (mais eficiente para web/PHP-FPM),
   **a confirmar por teste** com as ressalvas abaixo.

## Consequências

### Positivas
- Escala melhor o número de conexões: muitos workers PHP-FPM compartilham um pool
  pequeno de conexões reais ao Postgres.
- Single-database + transaction pooling combinam bem: como não dependemos de
  estado de sessão por tenant (`SET search_path`, temp tables por tenant), o
  modo transaction é aplicável.
- Postgres oferece recursos úteis para o futuro (ex.: **Row-Level Security** como
  camada extra de isolamento por `tenant_id` — *ideia a avaliar*, não decidida).

### Negativas / Trade-offs
- PgBouncer em modo `transaction` **não preserva estado de sessão** entre queries
  (prepared statements server-side, `SET`, advisory locks de sessão, `LISTEN/
  NOTIFY`). É preciso validar a configuração do driver PHP/PDO (ex.: prepared
  statements) para não quebrar nesse modo.
- Mais uma peça de infra para operar e monitorar (o PgBouncer).
- Migrar no futuro para **schema-per-tenant** (`SET search_path` por tenant)
  seria **incompatível** com transaction pooling — exigiria modo `session` ou
  outra estratégia. Hoje isso não se aplica (single-database).

### Neutras
- Troca de MySQL (usado na POC) por Postgres não muda o modelo de tenancy; muda o
  SGBD e a configuração de conexão.

## Alternativas consideradas

### Alternativa A: MySQL (como na POC)
**Descartada porque:** a decisão para o sistema oficial é Postgres. (A POC usou
MySQL apenas para validar o conceito; não é referência de produção.)

### Alternativa B: Postgres sem PgBouncer
**Descartada porque:** sob muitos tenants/processos PHP-FPM, o número de conexões
diretas ao Postgres tende a estourar limites. O pooler é o caminho padrão.

### Alternativa C: Pooling em modo `session`
**Adiada:** mais compatível (preserva estado de sessão), porém reaproveita menos
conexões. Fica como **fallback** caso o modo transaction se mostre incompatível
com o driver/configuração.

## A validar (spike)

- [ ] Comportamento de **prepared statements** do PDO/Laravel sob PgBouncer
      `transaction` (ajustar configuração de conexão se necessário).
- [ ] Confirmar o **modo de pooling** definitivo por teste de carga.
- [ ] Compatibilidade da versão do `stancl/tenancy` com a versão alvo do Laravel
      (ver [ADR-001](./ADR-001-single-database-multitenancy.md)).

## Referências
- [ADR-001 — Multi-tenancy de banco único](./ADR-001-single-database-multitenancy.md)
- [Arquitetura do sistema](../../../ARCHITECTURE.md)
- PgBouncer: https://www.pgbouncer.org/
