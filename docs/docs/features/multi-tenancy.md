---
title: "Feature: Multi-tenancy"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [feature, multi-tenancy, stancl]
---

# Feature: Multi-tenancy

## Objetivo

Permitir que vários clientes (tenants) usem a mesma aplicação com **dados
totalmente isolados**, cada um acessando por seu **subdomínio**.

## Abordagem

Multi-tenancy de **banco único compartilhado** (todos os tenants nas mesmas
tabelas, separados por `tenant_id`), usando o pacote **`stancl/tenancy`** em modo
single-database. Decisão em
[ADR-001](../architecture/adr/ADR-001-single-database-multitenancy.md).

## Comportamento esperado

1. Requisição chega em `cliente.dominio`.
2. O middleware do `stancl/tenancy` (`InitializeTenancyBySubdomain`) resolve o
   tenant pelo subdomínio e inicializa a tenancy (404 se não existir).
3. Models de dados de cliente usam o trait `BelongsToTenant` → as queries já vêm
   filtradas por `tenant_id`, e novos registros recebem `tenant_id` automaticamente.
4. Cache, filas e storage também ficam isolados por tenant (bootstrappers do
   pacote).

## Escopo

### Inclui
- Identificação por subdomínio.
- Isolamento de dados por `tenant_id` (global scope do pacote).
- Isolamento de cache/filas/storage por tenant.
- Rotas central (não-tenant) vs rotas de tenant.

### Não inclui (out of scope)
- Schema/banco por tenant (multi-database) — possível evolução futura.
- Self-service de cadastro de tenant (fica no Painel).

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Subdomínio sem tenant | 404 |
| Acesso ao domínio central | Rotas não-tenant (sem tenancy) |
| Job/comando sem tenant atual | Tenancy deve ser inicializada explicitamente antes de tocar em dados de tenant |

## Modelo de dados

- Tabela `tenants` (identificada por `slug`/subdomínio).
- Toda tabela de dados de cliente: coluna `tenant_id` **NOT NULL** + FK para
  `tenants`.

## Decisões técnicas

- `tenant_id` **NOT NULL** em toda tabela de dados de cliente.
- O middleware de tenancy roda antes de autenticação e licença.
- Não rodar query de dados de cliente sem o escopo de tenant.

## Referências
- [Arquitetura do sistema](../../ARCHITECTURE.md)
- [Autenticação por tenant](./authentication.md)
- [Gestão de tenants e licenças](./tenant-license-management.md)
- Documentação do pacote: https://tenancyforlaravel.com/
