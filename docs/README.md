---
title: README
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [overview, onboarding, multi-tenancy]
---

# Sistema Multi-tenant — Documentação

> Documentação do sistema **SaaS multi-tenant** em Laravel: arquitetura, decisões
> e features.

## O que é

Uma aplicação **SaaS multi-tenant** onde cada cliente (tenant) acessa por um
**subdomínio próprio** (ex.: `cliente.dominio`) e enxerga **apenas os seus
dados**. O isolamento usa **banco de dados compartilhado** (uma base, separação
por `tenant_id`) com o pacote **`stancl/tenancy`** (modo single-database). O banco
é **PostgreSQL** atrás do pooler **PgBouncer**, com **Row-Level Security (RLS)**
como camada extra de isolamento. O **Painel** (sistema próprio da empresa, na VPS
contratada) cria/gerencia tenants e **licenças** e os monitora de fora.

## Pilares (decisões fixadas)

| Tema | Decisão | Referência |
|------|---------|-----------|
| Estratégia de tenancy | Banco único compartilhado (`tenant_id`) | [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) |
| Framework de tenancy | `stancl/tenancy` (single-database) | [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) |
| Identificação do tenant | Subdomínio | [multi-tenancy](./docs/features/multi-tenancy.md) |
| Autenticação | JWT por tenant (token carrega `tenant_id`) | [authentication](./docs/features/authentication.md) |
| Banco de dados | PostgreSQL + PgBouncer + RLS | [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md) |
| Gestão de tenants/licenças | Painel próprio — repo separado, mesma VPS (porta/banco próprios) | [feature](./docs/features/tenant-license-management.md) |
| Observabilidade / auditoria | GlitchTip (erros) + audit log de negócio + log de segurança | [auditoria](./docs/features/auditing.md) |
| UI / Design | Brand book em Tailwind (cor é custom por tenant) | [brand book](./docs/design/brand-book.md) |

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Arquitetura do sistema |
| [GLOSSARY.md](./GLOSSARY.md) | Termos de domínio |
| [docs/features/multi-tenancy.md](./docs/features/multi-tenancy.md) | Como o multi-tenant funciona |
| [docs/features/authentication.md](./docs/features/authentication.md) | Login isolado por tenant |
| [docs/features/tenant-license-management.md](./docs/features/tenant-license-management.md) | Sistema de controle de tenants e licenças |
| [docs/features/auditing.md](./docs/features/auditing.md) | Auditoria (negócio + segurança) e monitoramento de erros |
| [docs/design/brand-book.md](./docs/design/brand-book.md) | Brand book / design system (Tailwind) |
| [docs/design/persona-frontend-ux.md](./docs/design/persona-frontend-ux.md) | Persona de Front-end & UX |
| [docs/architecture/adr/ADR-001-single-database-multitenancy.md](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) | Decisão: stancl/tenancy, single-database, subdomínio |
| [docs/architecture/adr/ADR-002-postgres-pgbouncer.md](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md) | Decisão: PostgreSQL + PgBouncer |
| [docs/architecture/migration-single-user-to-multitenant.md](./docs/architecture/migration-single-user-to-multitenant.md) | Roadmap: single-user → multi-tenant |
| [docs/ai-context/conventions.md](./docs/ai-context/conventions.md) | Convenções de código |
| [docs/ai-context/known-issues.md](./docs/ai-context/known-issues.md) | Limitações, riscos e decisões em aberto |

## Stack alvo

- **Backend:** PHP / Laravel + `stancl/tenancy` (single-database)
- **Banco:** PostgreSQL (banco único, isolamento por `tenant_id` + RLS) + PgBouncer
- **Tenancy:** identificação por subdomínio
- **Controle:** Painel próprio (tenants, licenças, monitoramento) na VPS contratada

## Decisões em aberto

Ver [known-issues](./docs/ai-context/known-issues.md) (integração app × Painel,
versão Laravel × stancl, modo de pooling do PgBouncer / RLS, modelo de licenças).
