---
title: Arquitetura do Sistema
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [architecture, system-design, multi-tenancy, stancl, postgres]
---

# Arquitetura do Sistema

> Arquitetura do sistema oficial multi-tenant. As decisões formais estão nos ADRs
> ([ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md),
> [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md)). O conceito
> foi validado numa POC — ver [lições da POC](./docs/ai-context/poc-learnings.md).

## Visão geral

Aplicação **Laravel** multi-tenant com **banco de dados compartilhado** (um único
banco, todos os tenants nas mesmas tabelas, separados pela coluna `tenant_id`). A
tenancy é provida pelo pacote **`stancl/tenancy`** em **modo single-database**. O
tenant é identificado pelo **subdomínio** da requisição. O banco é **PostgreSQL**,
acessado através do pooler **PgBouncer**.

Um **sistema de controle próprio (control plane)** gerencia o ciclo de vida dos
tenants e suas **licenças** (ver
[feature](./docs/features/tenant-license-management.md)).

## Diagrama de componentes

```
                         ┌───────────────────────────────┐
   Sistema de Controle   │  Control plane (sistema NOSSO) │
   (tenants + licenças)  │  cria/provisiona tenants e     │
                         │  define/renova licenças        │
                         └───────────────┬───────────────┘
                                         │ (cria tenant, define licença)
                                         ▼
   Navegador                  ┌────────────────────────┐
 cliente.dominio  ──HTTPS───▶ │  App Laravel +          │
                              │  stancl/tenancy         │
                              │  (single-database)      │
                              │  subdomínio → tenant    │
                              │  licença → acesso       │
                              └───────────┬────────────┘
                                          │ SQL (pool)
                                          ▼
                              ┌────────────────────────┐      ┌─────────────────┐
                              │     PgBouncer           │─────▶│  PostgreSQL     │
                              │  (connection pooler)    │      │  banco único    │
                              └────────────────────────┘      │  (tenant_id)    │
                                                              └─────────────────┘
```

## Componentes principais

### App Laravel + `stancl/tenancy`
- **Tenancy:** pacote `stancl/tenancy` em **modo single-database**. Não há troca
  de conexão/banco por tenant — o isolamento é por `tenant_id` via *global scope*
  (trait `BelongsToTenant` do pacote), que filtra as queries e preenche o
  `tenant_id` ao criar registros.
- **Identificação:** por **subdomínio** (`InitializeTenancyBySubdomain`). O
  domínio central serve rotas não-tenant.
- **Bootstrappers:** o pacote isola por tenant também **cache, filas (queues) e
  storage**, além das queries.

### PostgreSQL (banco de dados)
- **Banco único compartilhado** por todos os tenants. Isolamento **lógico** pela
  coluna `tenant_id` (ver [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md)).
- Escolha do Postgres e seus motivos: [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md).

### PgBouncer (connection pooler)
- Fica entre a aplicação e o Postgres, reutilizando conexões para suportar muitas
  requisições/tenants sem esgotar as conexões do banco.
- Como estamos em **single-database** (sem `SET search_path`/troca de conexão por
  tenant), o pooling em **modo transaction** é viável — com as ressalvas de
  prepared statements documentadas no [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md).

### Control plane (sistema de controle próprio)
- Gerencia tenants (criação/provisionamento) e **licenças** (plano, validade,
  limites, funcionalidades). Onde ele vive (app separado vs módulo) é **decisão em
  aberto** — ver [feature](./docs/features/tenant-license-management.md).

## Multi-tenancy — como o isolamento funciona

1. **Identificação:** o middleware do `stancl/tenancy` lê o subdomínio e inicializa
   o tenant atual (ou retorna 404 se o subdomínio não corresponder a um tenant).
2. **Isolamento de dados:** todo model com dados de cliente usa o trait
   `BelongsToTenant` do pacote → *global scope* filtra por `tenant_id` e o
   preenche automaticamente ao criar.
3. **Autenticação por tenant:** o `User` também é escopado por tenant, então o
   login só autentica usuários do tenant atual (ver
   [autenticação](./docs/features/authentication.md)).
4. **Licença:** um middleware verifica a licença do tenant atual antes de liberar
   as áreas restritas.

## Fluxo de uma requisição de tenant

1. Navegador acessa `cliente.dominio/...`.
2. O middleware do `stancl/tenancy` identifica `cliente` pelo subdomínio e
   inicializa a tenancy.
3. Middleware de licença confere se o tenant está ativo/em dia.
4. Middleware de autenticação garante usuário logado **deste** tenant.
5. As queries dos models de cliente já vêm filtradas por `tenant_id`.

## Decisões de arquitetura (ADRs)

- [ADR-001 — Multi-tenancy de banco único com stancl/tenancy](./docs/architecture/adr/ADR-001-single-database-multitenancy.md)
- [ADR-002 — PostgreSQL + PgBouncer](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md)

## O que NÃO fazer

- ❌ Não consultar models com dados de tenant **sem** o trait `BelongsToTenant` —
  fura o isolamento.
- ❌ Não definir `SESSION_DOMAIN` no domínio raiz (ex.: `.dominio`) — o cookie de
  sessão deve ficar host-only para não trafegar entre subdomínios de tenants.
- ❌ Não acessar/alterar tenants ou licenças direto no banco — usar o control plane.
- ❌ Não assumir **schema/banco por tenant** (multi-database): a decisão atual é
  **single-database**. Migrar para schema-per-tenant no futuro exige rever o modo
  de pooling do PgBouncer (ver ADR-002).

## Decisões em aberto

- [ ] Onde vive o control plane (app central separado vs módulo).
- [ ] Versão alvo de Laravel × compatibilidade do `stancl/tenancy` (validar).
- [ ] Modo de pooling do PgBouncer (transaction vs session) confirmado por teste.

## Limitações e riscos

Ver [known-issues](./docs/ai-context/known-issues.md).
