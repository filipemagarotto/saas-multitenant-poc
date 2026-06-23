---
title: "Roadmap: De single-user para multi-tenant"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-22
ai_friendly: true
tags: [architecture, roadmap, migration, multi-tenancy, target]
---

# Roadmap: De single-user para multi-tenant

> **O que QUEREMOS fazer**, não o que está feito. Plano para construir o "terceiro
> sistema": o produto oficial **multi-tenant**.

## Os repositórios

| # | Repositório | Papel |
|---|-------------|-------|
| 1 | **POC** (este repo) | Provou o conceito de banco único + subdomínio (implementação própria). Fonte de aprendizado. |
| 2 | **Sistema oficial atual** | Produto que já funciona hoje, porém **single-user** (um cliente). Fonte do domínio/negócio. |
| 3 | **Sistema oficial multi-tenant** (alvo) | **Novo repo greenfield** que junta o domínio do (2) com a tenancy validada no (1), usando `stancl/tenancy`. |
| 4 | **Painel** (control plane) | **Repo separado**: cria/gerencia tenants e licenças e monitora de fora. Mesma VPS, porta e banco próprios. Fora do escopo deste roadmap. |

> Decisão fixada: o alvo (repo 3) é um **novo repositório greenfield** (não evoluir
> o repo atual in-place). O **Painel** (repo 4) é construído à parte. Ver
> [arquitetura do sistema](../../ARCHITECTURE.md).

## Princípio

Reaproveitar o **domínio** do sistema oficial (models, regras de negócio, telas) e
**não** reaproveitar a infraestrutura de tenancy da POC (que era manual) — em
produção a tenancy vem do `stancl/tenancy` (ver
[ADR-001](./adr/ADR-001-single-database-multitenancy.md)).

## Fases

### Fase 0 — Validação (pré-código)
- [ ] Confirmar compatibilidade `stancl/tenancy` × versão alvo do Laravel. Ver
      [known-issues](../ai-context/known-issues.md).
- [ ] Validar PgBouncer (modo de pooling) + Postgres com o app (ver
      [ADR-002](./adr/ADR-002-postgres-pgbouncer.md)).
- [ ] Definir a integração app × Painel (como o app lê a licença: tabela
      compartilhada, API ou cache). Ver
      [gestão de tenants e licenças](../features/tenant-license-management.md).

### Fase 1 — Esqueleto multi-tenant (novo repo)
- [ ] Novo repo Laravel + `stancl/tenancy` (modo single-database).
- [ ] PostgreSQL + PgBouncer configurados.
- [ ] **RLS** habilitado: policies por tabela de tenant + `SET LOCAL app.tenant_id`
      a cada inicialização de tenancy; role do app sem `BYPASSRLS`.
- [ ] Identificação por subdomínio + domínio central configurados.
- [ ] Tenant de exemplo subindo (smoke test de isolamento).

### Fase 2 — Portar o domínio do sistema oficial
- [ ] Trazer models/migrations/telas do sistema oficial atual.
- [ ] Aplicar o trait `BelongsToTenant` (do stancl) em **todo** model com dados de
      cliente; adicionar `tenant_id` nas tabelas correspondentes.
- [ ] Garantir que nenhuma query de dados de cliente roda sem o escopo de tenant.

### Fase 3 — Autenticação por tenant (JWT)
- [ ] `users.tenant_id` + login via **JWT** isolado por tenant (token carrega o
      `tenant_id`, validado contra o subdomínio). Ver
      [autenticação por tenant](../features/authentication.md).

### Fase 4 — Painel (control plane) e licenças
- [ ] **Painel** como sistema próprio **em repo separado** (fora deste roadmap),
      na **mesma VPS** porém em **porta própria** e **banco próprio** (cria
      tenants, gerencia licenças, monitora de fora). Ver
      [feature](../features/tenant-license-management.md).
- [ ] Integração app ↔ Painel (API/cache) + enforcement de licença via middleware
      no app dos tenants.

### Fase 5 — Migração de dados (single-user → 1º tenant)
- [ ] Criar o primeiro tenant correspondente ao cliente atual.
- [ ] Migrar os dados existentes preenchendo `tenant_id` do 1º tenant.
- [ ] Validar isolamento criando um 2º tenant de teste.

### Fase 6 — Produção / hardening
- [ ] HTTPS/TLS, isolamento de cache/filas/storage por tenant (stancl), CI/CD,
      backups, observabilidade.

## Riscos e cuidados

- **Não portar** a trait/middleware próprios da POC para o repo alvo — usar os do
  pacote para não manter duas implementações.
- A migração de dados (Fase 5) é o ponto mais sensível: fazer com backup e em
  ambiente de validação antes da produção.
- Manter este roadmap e os docs de alvo sincronizados conforme as decisões em
  aberto forem fechadas.

## Referências
- [Arquitetura do sistema](../../ARCHITECTURE.md)
- [ADR-001 — Estratégia de multi-tenancy](./adr/ADR-001-single-database-multitenancy.md)
- [ADR-002 — PostgreSQL + PgBouncer](./adr/ADR-002-postgres-pgbouncer.md)
- [Gestão de tenants e licenças](../features/tenant-license-management.md)
- [Lições da POC](../ai-context/poc-learnings.md)
