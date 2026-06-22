---
title: Arquitetura Alvo (Produção)
status: draft
owner: filipe-magarotto
last_updated: 2026-06-22
ai_friendly: true
tags: [architecture, target, production, multi-tenancy, stancl]
---

# Arquitetura Alvo (Produção)

> **Este documento descreve o que QUEREMOS na prática**, não o que está feito.
> O "feito" (a prova de conceito) está em [ARCHITECTURE.md](../../ARCHITECTURE.md).
> A decisão por trás desta arquitetura está em
> [ADR-001](./adr/ADR-001-single-database-multitenancy.md).

## Visão

Transformar o sistema oficial **single-user** de hoje num SaaS **multi-tenant**,
construído num **novo repositório (greenfield)** que combina:

- a **base de domínio** do sistema oficial atual (que já funciona para um usuário); e
- os **aprendizados da POC** (este repo) sobre isolamento por `tenant_id` e
  identificação por subdomínio.

A tenancy de produção é feita com o pacote **`stancl/tenancy`** em **modo
single-database** (banco compartilhado, discriminador `tenant_id`), com
identificação por **subdomínio**.

## Componentes alvo

```
                         ┌───────────────────────────────┐
   Sistema de Controle   │  Control plane (sistema NOSSO) │
   (tenants + licenças)  │  - cria/provisiona tenants     │
   *onde vive = aberto*  │  - gerencia licenças/planos    │
                         │  - ativa/suspende tenants      │
                         └───────────────┬───────────────┘
                                         │ (cria tenant, define licença)
                                         ▼
   Navegador                  ┌────────────────────────┐      ┌─────────────┐
 cliente.dominio  ──HTTP(S)──▶│  App multi-tenant      │─────▶│  MySQL      │
                              │  Laravel + stancl/      │      │  banco único│
                              │  tenancy (single-DB)    │      │  (tenant_id)│
                              │  - subdomínio → tenant  │      └─────────────┘
                              │  - licença → acesso     │
                              └────────────────────────┘
```

## Decisões fixadas (ver ADR-001)

| Tema | Decisão |
|------|---------|
| Estratégia | Multi-tenancy de **banco único** (`tenant_id`) |
| Pacote | **`stancl/tenancy`** (modo single-database) |
| Identificação | **Subdomínio** (`cliente.dominio`) |
| Repo | **Novo repo greenfield** (POC + base do oficial) |

## Mapa: POC (próprio) → Produção (stancl)

O conceito é o mesmo; muda a implementação. Quem conhece a POC reconhece os
equivalentes:

| Peça na POC (própria) | Equivalente em produção (`stancl/tenancy`) |
|------------------------|---------------------------------------------|
| Singleton `App\Tenancy\Tenancy` (tenant atual) | `tenancy()` / `tenant()` helpers e estado do pacote |
| Middleware `IdentifyTenant` (subdomínio) | `InitializeTenancyBySubdomain` |
| Trait `App\Tenancy\BelongsToTenant` (global scope + auto-fill) | Trait `BelongsToTenant` + `TenantScope` do pacote |
| Grupos de rota central vs `{tenant}.dominio` | Rotas central vs tenant via config do pacote |
| `users.tenant_id` + login escopado | Mesmo padrão, com o trait do pacote no `User` |
| (não cobre) cache/filas/storage por tenant | **Bootstrappers** do stancl isolam automaticamente |

## Control plane: gestão de tenants e licenças

Teremos um **sistema de controle próprio** que governa o ciclo de vida do tenant
e o **licenciamento** (ex.: para adicionar uma licença a um tenant específico,
faz-se por esse sistema — não "na mão" no banco).

- **Onde esse sistema vive ainda é decisão em aberto** (app central separado vs
  módulo do app). Detalhes e modelo de dados proposto em
  [gestão de tenants e licenças](../features/tenant-license-management.md).
- O app multi-tenant **consome** a licença do tenant atual para liberar/bloquear
  acesso e funcionalidades (enforcement por middleware).

## Identificação por subdomínio

Igual à POC: `cliente.dominio` → middleware do stancl resolve o tenant pelo
subdomínio; o domínio central serve rotas não-tenant. Um wildcard DNS/Nginx
(`*.dominio`) cobre todos os tenants sem reconfiguração por cliente.

## Fora de escopo da POC, esperado em produção

- HTTPS/TLS obrigatório (a POC é HTTP puro).
- Isolamento de **cache, filas e storage** por tenant (via stancl).
- Provisionamento automático de tenants pelo control plane.
- CI/CD (a POC tem deploy manual).

## Riscos a validar

- **Compatibilidade `stancl/tenancy` × Laravel 13.** Estamos numa versão muito
  nova do Laravel; antes de fixar, confirmar a versão suportada do pacote
  (ex.: `composer require stancl/tenancy` no novo repo, ou checar o changelog).
  Se não houver versão compatível, reavaliar (segurar no Laravel LTS suportado
  ou usar a implementação própria temporariamente).
- Definição de onde vive o **control plane** (impacta integração e deploy).
- Estratégia de **migração de dados** do sistema single-user atual para o
  primeiro tenant (ver [roadmap](./migration-single-user-to-multitenant.md)).

## Decisões em aberto

- [ ] Onde vive o control plane (app central separado vs módulo).
- [ ] Modelo de licenças/planos (campos, limites, validade, features).
- [ ] Domínio próprio por tenant no futuro? (hoje: só subdomínio).
- [ ] Versão alvo do Laravel/stancl após validar compatibilidade.
