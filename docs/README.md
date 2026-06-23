---
title: README
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [overview, onboarding, multi-tenancy]
---

# Sistema Multi-tenant — Documentação

> Documentação do sistema oficial **SaaS multi-tenant** em Laravel. O conceito foi
> validado numa POC; aqui fica a arquitetura e as decisões para o produto real.

## O que é

Uma aplicação **SaaS multi-tenant** onde cada cliente (tenant) acessa por um
**subdomínio próprio** (ex.: `cliente.dominio`) e enxerga **apenas os seus
dados**. O isolamento usa **banco de dados compartilhado** (uma base, separação
por `tenant_id`) com o pacote **`stancl/tenancy`** (modo single-database). O banco
é **PostgreSQL** atrás do pooler **PgBouncer**. Um **sistema de controle próprio**
gerencia tenants e **licenças**.

## Pilares (decisões fixadas)

| Tema | Decisão | Referência |
|------|---------|-----------|
| Estratégia de tenancy | Banco único compartilhado (`tenant_id`) | [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) |
| Framework de tenancy | `stancl/tenancy` (single-database) | [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) |
| Identificação do tenant | Subdomínio | [multi-tenancy](./docs/features/multi-tenancy.md) |
| Banco de dados | PostgreSQL + PgBouncer | [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md) |
| Gestão de tenants/licenças | Control plane próprio | [feature](./docs/features/tenant-license-management.md) |

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Arquitetura do sistema |
| [GLOSSARY.md](./GLOSSARY.md) | Termos de domínio |
| [docs/features/multi-tenancy.md](./docs/features/multi-tenancy.md) | Como o multi-tenant funciona |
| [docs/features/authentication.md](./docs/features/authentication.md) | Login isolado por tenant |
| [docs/features/tenant-license-management.md](./docs/features/tenant-license-management.md) | Sistema de controle de tenants e licenças |
| [docs/architecture/adr/ADR-001-single-database-multitenancy.md](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) | Decisão: stancl/tenancy, single-database, subdomínio |
| [docs/architecture/adr/ADR-002-postgres-pgbouncer.md](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md) | Decisão: PostgreSQL + PgBouncer |
| [docs/architecture/migration-single-user-to-multitenant.md](./docs/architecture/migration-single-user-to-multitenant.md) | Roadmap: single-user → multi-tenant |
| [docs/ai-context/conventions.md](./docs/ai-context/conventions.md) | Convenções de código |
| [docs/ai-context/known-issues.md](./docs/ai-context/known-issues.md) | Limitações, riscos e decisões em aberto |
| [docs/ai-context/poc-learnings.md](./docs/ai-context/poc-learnings.md) | Lições da POC + mapa POC→produção |

## Stack alvo

- **Backend:** PHP / Laravel + `stancl/tenancy` (single-database)
- **Banco:** PostgreSQL (banco único, isolamento por `tenant_id`) + PgBouncer
- **Tenancy:** identificação por subdomínio
- **Controle:** sistema próprio de tenants e licenças (control plane)

## Decisões em aberto

Ver [known-issues](./docs/ai-context/known-issues.md) (control plane, versão
Laravel × stancl, modo de pooling do PgBouncer, modelo de licenças).
