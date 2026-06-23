---
title: Glossário
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [domain, glossary]
---

# Glossário

> Termos de domínio e conceitos técnicos do sistema multi-tenant. Referência para
> alinhar linguagem entre desenvolvimento e IAs.

## Entidades principais

| Termo | Definição | Notas |
|-------|-----------|-------|
| **Tenant** | Um cliente da plataforma, com dados isolados dos demais. | Tabela `tenants`. Identificado pelo `slug`. |
| **Slug** | Identificador curto e único do tenant, usado como subdomínio. | Ex.: `cliente` → `cliente.dominio`. |
| **Domínio central** | Domínio raiz da aplicação, onde ficam rotas não-tenant. | Configurável; rotas centrais vs rotas de tenant. |
| **Tenant atual** | O tenant identificado/inicializado para a requisição em andamento. | Gerenciado pelo `stancl/tenancy`. |
| **Licença** | Vínculo de um tenant a um plano (validade, limites, funcionalidades) que o app respeita para liberar/bloquear acesso. | Ver [feature](./docs/features/tenant-license-management.md). |
| **Plano** | Conjunto de funcionalidades/limites que uma licença concede. | Ex.: Básico, Pro. |

## Conceitos técnicos

| Termo | Significado |
|-------|-------------|
| **Multi-tenancy de banco único** | Estratégia onde todos os tenants compartilham as mesmas tabelas, separados pela coluna `tenant_id`. |
| **`stancl/tenancy`** | Pacote de multi-tenancy para Laravel adotado pelo sistema (modo single-database). Ver [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md). |
| **`BelongsToTenant`** | Trait do `stancl/tenancy` aplicada em models com dados de tenant: adiciona o global scope por `tenant_id` e o preenche ao criar. |
| **Global Scope** | Filtro do Eloquent aplicado automaticamente a todas as queries de um model. Aqui, filtra por `tenant_id`. |
| **Bootstrappers** | Componentes do `stancl/tenancy` que isolam por tenant também cache, filas e storage — não só as queries. |
| **Control plane** | Sistema de controle próprio para gerenciar o ciclo de vida dos tenants e suas licenças. |
| **PgBouncer** | Pooler de conexões para PostgreSQL, entre a aplicação e o banco. Ver [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md). |
| **Modo de pooling** | Estratégia do PgBouncer (`session`, `transaction`, `statement`) que define como as conexões são reaproveitadas. |

## Contexto

| Termo | Significado |
|-------|-------------|
| **Sistema oficial (atual)** | Produto que já funciona hoje, porém **single-user**. Fonte do domínio para o sistema multi-tenant. |
| **Greenfield** | Projeto começado do zero. O sistema multi-tenant será um novo repo greenfield. Ver [roadmap](./docs/architecture/migration-single-user-to-multitenant.md). |

## Siglas e abreviações

| Sigla | Significado |
|-------|-------------|
| ADR | Architecture Decision Record |
| POC | Proof of Concept (prova de conceito) |
| SGBD | Sistema Gerenciador de Banco de Dados |
| DNS | Domain Name System |
| TLS | Transport Layer Security (HTTPS) |

## Estados e status

| Estado | Contexto | Significado |
|--------|----------|-------------|
| 404 (tenant) | Requisição de subdomínio | O `slug` do subdomínio não corresponde a nenhum tenant. |
| `active` / `suspended` / `expired` | Licença do tenant | Controla o acesso do tenant à aplicação. |
