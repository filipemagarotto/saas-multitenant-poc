---
title: Limitações, Riscos e Decisões em Aberto
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [known-issues, risks, open-decisions]
---

# Limitações, Riscos e Decisões em Aberto

> Para IAs: ao sugerir mudanças, verifique se não conflitam com os itens abaixo.
> Este documento é do **sistema oficial multi-tenant**; itens da POC foram
> descartados de propósito (ver [lições da POC](./poc-learnings.md)).

## Decisões em aberto

| ID | Tema | Pendência |
|----|------|-----------|
| OD-002 | **Modelo de licenças** | Campos, planos, limites e funcionalidades por plano |
| OD-003 | **Versão Laravel × stancl** | Fixar a versão alvo do Laravel compatível com o `stancl/tenancy` |
| OD-004 | **Pooling do PgBouncer** | Confirmar `transaction` vs `session` por teste (ver ADR-002) |
| OD-005 | **Integração app × Painel** | Bancos separados → app lê tenant/licença via **API do Painel** ou **cache** (não tabela compartilhada); definir sync da lista de tenants |
| OD-006 | **Monitoramento do banco** | Adotar ou não o **PgHero** (lê do `pg_stat_statements`) — em avaliação (ver ADR-002) |
| OD-007 | **JWT** | Biblioteca/guard, algoritmo de assinatura, estratégia de **refresh** e de **revogação** (ver [autenticação](../features/authentication.md)) |

> **Decidido:** o Painel (gestão de tenants/licenças) é um **sistema próprio em
> repo separado**, na **mesma VPS contratada** porém em **porta própria** e
> **banco próprio** (era OD-001). Falta só definir a integração (OD-005).

## Riscos a validar (spikes)

- **Compatibilidade `stancl/tenancy` × Laravel.** Confirmar versão suportada antes
  de fixar dependências. Pode ser o fator que define a versão do Laravel.
- **PgBouncer em modo `transaction`.** Validar prepared statements do PDO/Laravel
  e ausência de dependência de estado de sessão (ver
  [ADR-002](../architecture/adr/ADR-002-postgres-pgbouncer.md)).
- **RLS sob transaction pooling.** Garantir que o tenant atual é definido com
  `SET LOCAL` por transação (sem vazar entre tenants no pool) e que o role do app
  não tem `BYPASSRLS` (ver [ADR-002](../architecture/adr/ADR-002-postgres-pgbouncer.md)).
- **Migração de dados** do sistema single-user atual para o primeiro tenant
  (ver [roadmap](../architecture/migration-single-user-to-multitenant.md)).

## Limitações conhecidas (por design, nesta fase)

- **Isolamento lógico**, não físico: todos os tenants no mesmo banco, separados
  por `tenant_id`. O **RLS** reduz o risco (mesmo um bug de query não vaza dados de
  outro tenant), mas a regra de **sempre** usar `BelongsToTenant` e cobrir com
  testes permanece.
- **Single-database**: sem schema/banco por tenant nesta fase. Migrar para
  schema-per-tenant no futuro impacta o modo de pooling (ADR-002).
- **Sem self-service de cadastro** de tenants/usuários nesta fase — criados pelo
  Painel.

## Comportamentos não-óbvios (a observar na implementação)

- O middleware de tenancy precisa rodar **antes** de autenticação/licença, pois o
  escopo por tenant depende do tenant atual já estar definido.
- O cookie de sessão deve ficar **host-only** (`SESSION_DOMAIN` vazio) para não
  trafegar entre subdomínios de tenants.
- Jobs/filas e comandos artisan **não** têm tenant atual automaticamente —
  inicializar a tenancy explicitamente antes de tocar em dados de tenant.
