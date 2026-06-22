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

## Conceitos técnicos

| Termo | Significado |
|-------|-------------|
| **Multi-tenancy de banco único** | Estratégia onde todos os tenants compartilham as mesmas tabelas, separados por uma coluna (`tenant_id`). |
| **Global Scope** | Filtro do Eloquent aplicado automaticamente a todas as queries de um model. Aqui, filtra por `tenant_id`. |
| **`BelongsToTenant`** | Trait (`App\Tenancy`) que adiciona o global scope de tenant e o preenchimento automático de `tenant_id`. |
| **`IdentifyTenant`** | Middleware que identifica o tenant pelo subdomínio e define o tenant atual. |
| **Deploy Key** | Chave SSH cadastrada num repositório específico do GitHub, usada pela VPS para clonar/enviar código. |

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
