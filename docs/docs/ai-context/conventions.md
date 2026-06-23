---
title: Convenções de Código e Projeto
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [conventions, standards, laravel, php]
---

# Convenções

> Convenções do sistema oficial (Laravel/PHP + `stancl/tenancy` + PostgreSQL).
> Ajuste conforme o repositório oficial evoluir.

## Linguagem e estilo

- **PHP 8.x / Laravel**, seguindo **PSR-12**.
- Formatação automática com **Laravel Pint** (`vendor/bin/pint`).
- Tipos sempre que possível (parâmetros, retornos, propriedades tipadas).
- `declare(strict_types=1)` quando o projeto adotar (definir no setup oficial).

## Nomenclatura

- **Classes / Models / Enums:** `PascalCase` (`Tenant`, `License`).
- **Métodos / variáveis:** `camelCase`.
- **Constantes:** `UPPER_SNAKE_CASE`.
- **Tabelas:** `snake_case`, plural (`tenants`, `licenses`).
- **Colunas:** `snake_case` (`tenant_id`, `created_at`).
- **Rotas nomeadas:** `recurso.acao` (`pets.index`).

## Multi-tenancy (regra de ouro)

- Todo model com **dados de cliente** usa o trait `BelongsToTenant` do
  `stancl/tenancy`. Sem exceção — é o que garante o isolamento.
- **`tenant_id`** é `NOT NULL` em tabelas de tenant.
- Lógica que roda **fora** do contexto de uma requisição de tenant (jobs, comandos
  artisan) deve **inicializar a tenancy** explicitamente antes de tocar em dados de
  tenant.
- Rotas: separar claramente **central** (não-tenant) de **tenant**
  (`{tenant}.dominio`).
- Nunca acessar/alterar tenants ou licenças direto no banco — usar o Painel.

## Banco de dados (PostgreSQL + PgBouncer)

- Migrations versionadas (`database/migrations`); nada de DDL manual em produção.
- Cuidado com recursos de **sessão** do Postgres sob PgBouncer em modo
  `transaction` (ver [ADR-002](../architecture/adr/ADR-002-postgres-pgbouncer.md)):
  evitar dependência de estado de sessão (prepared statements server-side, `SET`,
  advisory locks de sessão) sem validar.
- Toda query de dados de cliente deve passar pelo escopo de tenant (não rodar
  query "crua" que ignore o `tenant_id`).
- **RLS:** toda tabela de tenant tem policy de Row-Level Security; o tenant atual
  é definido com `SET LOCAL app.tenant_id` **dentro da transação** (compatível com
  o transaction pooling). O role do app não pode ter `BYPASSRLS`. Ver
  [ADR-002](../architecture/adr/ADR-002-postgres-pgbouncer.md).

## Testes

- **Pest** ou **PHPUnit** (definir no setup oficial); testes de feature por tenant.
- Cobrir **isolamento entre tenants** como cenário de primeira classe (um tenant
  não enxerga/loga em dados de outro).
- Não usar dados de produção em testes.

## Git e PRs

- Commits: **Conventional Commits** (`feat:`, `fix:`, `docs:`, `chore:`).
- Branch: `type/ticket-descricao` (ex.: `feat/PLT-123-licenca-tenant`).
- PRs precisam de ao menos 1 aprovação + CI verde.

## O que NÃO fazer

- ❌ Query de dados de cliente sem o escopo de tenant.
- ❌ Model de tenant sem o trait `BelongsToTenant`.
- ❌ JWT sem o claim `tenant_id` validado contra o subdomínio (ver
  [autenticação](../features/authentication.md)).
- ❌ Secrets hardcoded — usar `.env` / gerenciador de segredos.
- ❌ `SESSION_DOMAIN` no domínio raiz (vazaria sessão entre tenants).
- ❌ Portar a implementação própria da POC (ver
  [lições da POC](./poc-learnings.md)).
