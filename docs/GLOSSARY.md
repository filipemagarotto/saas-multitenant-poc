---
title: Glossário
status: stable
owner: filipe-magarotto
last_updated: 2026-06-21
ai_friendly: true
tags: [domain, glossary]
---

# Glossário

> Termos de domínio, entidades do sistema e conceitos técnicos do multi-tenant.
> Referência para alinhar linguagem entre desenvolvimento e IAs.

## Entidades principais

| Termo | Definição | Notas |
|-------|-----------|-------|
| **Tenant** | Um cliente da plataforma. Cada tenant tem seus dados isolados. | Tabela `tenants`. Identificado pelo `slug`. |
| **Slug** | Identificador curto e único do tenant, usado como subdomínio. | Ex.: `cliente1` → `cliente1.tcsystem.shop`. Coluna `tenants.slug` (única). |
| **Domínio central** | Domínio raiz da aplicação, onde ficam rotas não-tenant. | `tcsystem.shop`. Configurável em `config/tenancy.php` / `TENANT_CENTRAL_DOMAIN`. |
| **Pet** | Entidade de exemplo usada para demonstrar o isolamento de dados. | Tabela `pets`, com `tenant_id`. Pertence a um tenant. |
| **Tenant atual** | O tenant identificado para a requisição em andamento. | Guardado no singleton `App\Tenancy\Tenancy`. |
| **Sistema oficial** | Produto que já funciona hoje, porém **single-user** (um cliente). Fonte do domínio para o sistema multi-tenant alvo. | Repo separado desta POC. |
| **Greenfield** | Projeto começado do zero. O sistema oficial multi-tenant será um **novo repo** greenfield (POC + domínio do oficial). | Ver [roadmap](./docs/architecture/migration-single-user-to-multitenant.md). |

## Conceitos técnicos

| Termo | Significado |
|-------|-------------|
| **Multi-tenancy de banco único** | Estratégia onde todos os tenants compartilham as mesmas tabelas, separados por uma coluna (`tenant_id`). |
| **Global Scope** | Filtro do Eloquent aplicado automaticamente a todas as queries de um model. Aqui, filtra por `tenant_id`. |
| **`BelongsToTenant`** | Trait (`App\Tenancy`) que adiciona o global scope de tenant e o preenchimento automático de `tenant_id`. |
| **`IdentifyTenant`** | Middleware que identifica o tenant pelo subdomínio e define o tenant atual. |
| **Deploy Key** | Chave SSH cadastrada num repositório específico do GitHub, usada pela VPS para clonar/enviar código. |
| **`stancl/tenancy`** | Pacote open-source de multi-tenancy para Laravel. Escolhido para o sistema oficial (modo single-database). Ver [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md). |
| **Bootstrappers** | Componentes do `stancl/tenancy` que isolam por tenant não só as queries, mas também cache, filas, storage e sessões. |
| **Control plane** | Sistema de controle **próprio** para gerenciar o ciclo de vida dos tenants e suas licenças. Ver [feature](./docs/features/tenant-license-management.md). |
| **Licença** | Vínculo de um tenant a um plano (validade, limites, funcionalidades) que o app respeita para liberar/bloquear acesso. |

## Siglas e abreviações

| Sigla | Significado |
|-------|-------------|
| ADR | Architecture Decision Record |
| POC | Proof of Concept (prova de conceito) |
| FPM | FastCGI Process Manager (PHP-FPM) |
| VPS | Virtual Private Server |
| DNS | Domain Name System |

## Estados e status

| Estado | Contexto | Significado |
|--------|----------|-------------|
| 404 (tenant) | Requisição de subdomínio | O `slug` do subdomínio não corresponde a nenhum tenant. |
| `nullable` | `pets.tenant_id` | Coluna aceita nulo por legado; pets reais sempre têm tenant. |
