---
title: Lições da POC
status: stable
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [poc, learnings, multi-tenancy, context]
---

# Lições da POC

> Resumo do que foi **validado numa prova de conceito (POC)** antes deste sistema.
> A POC **não** é o sistema oficial: ela usou uma implementação própria de tenancy
> e MySQL, só para provar o conceito. Aqui ficam os aprendizados que importam — os
> detalhes de infraestrutura da POC foram descartados de propósito.

## O que a POC provou

- **Banco único com `tenant_id` funciona** para isolar dados por tenant: dois
  tenants (`cliente1`, `cliente2`) acessando o mesmo endpoint viam **apenas os
  próprios dados**.
- **Identificação por subdomínio** (`cliente.dominio`) é um caminho simples e
  eficaz para resolver o tenant da requisição.
- **Isolamento via global scope do Eloquent** (filtrar toda query por `tenant_id`
  e preencher `tenant_id` ao criar) cobre o caso comum sem repetir `where` em todo
  lugar.
- **Login por tenant** sai "de graça" quando o model `User` também é escopado por
  tenant: a busca por credenciais já vem filtrada, então um usuário de um tenant
  não autentica em outro. Mantido como requisito — ver
  [autenticação](../features/authentication.md).
- **Cookie de sessão host-only** (sem `SESSION_DOMAIN` no domínio raiz) evita que
  a sessão de um tenant trafegue para o subdomínio de outro.

## Como os conceitos da POC mapeiam para o sistema oficial (`stancl/tenancy`)

O conceito é o mesmo; muda a implementação. A POC fez "na mão"; em produção isso
vem pronto do pacote.

| Conceito (POC, próprio) | Equivalente oficial (`stancl/tenancy`) |
|--------------------------|-----------------------------------------|
| Singleton com o "tenant atual" | Estado/`helpers` de tenancy do pacote |
| Middleware de subdomínio próprio | `InitializeTenancyBySubdomain` |
| Trait própria (global scope + auto-fill) | Trait `BelongsToTenant` + `TenantScope` do pacote |
| Grupos de rota central vs `{tenant}.dominio` | Rotas central vs tenant via config do pacote |
| `users.tenant_id` + login escopado | Mesmo padrão, com o trait do pacote no `User` |
| (não cobria) cache/filas/storage por tenant | **Bootstrappers** do stancl isolam automaticamente |
| MySQL | **PostgreSQL** + **PgBouncer** (ver ADR-002) |

## O que **não** trazer da POC

- ❌ A trait/middleware/singleton próprios — usar os do `stancl/tenancy`.
- ❌ MySQL, configs de Nginx/PHP-FPM e o runbook de VPS da POC.
- ❌ `tenant_id` `nullable` (legado da POC); no sistema oficial deve ser
  `NOT NULL` desde o início.

## Referências
- [Arquitetura do sistema](../../ARCHITECTURE.md)
- [ADR-001 — Multi-tenancy de banco único](../architecture/adr/ADR-001-single-database-multitenancy.md)
- Documentação do pacote: https://tenancyforlaravel.com/
