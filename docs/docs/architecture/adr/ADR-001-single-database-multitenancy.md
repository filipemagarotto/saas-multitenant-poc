---
title: "ADR-001: Multi-tenancy de banco único com stancl/tenancy"
status: accepted
owner: filipe-magarotto
last_updated: 2026-06-22
ai_friendly: true
tags: [architecture, adr, multi-tenancy, stancl]
---

# ADR-001: Multi-tenancy de banco único com stancl/tenancy

## Status

`accepted`

> Decisão: adotar o pacote **`stancl/tenancy`** no modo **single-database** para o
> multi-tenancy do sistema.

## Contexto

Temos hoje um sistema **single-user** (atende um único cliente) e queremos
transformá-lo num produto **SaaS multi-tenant** (vários clientes isolados).
Precisamos decidir **como** implementar o multi-tenancy no Laravel.

Forças em jogo:

- O isolamento precisa ir além das queries: idealmente também **cache, filas
  (queues), storage e sessões** por tenant.
- Teremos um **sistema de controle próprio** para gerenciar tenants e **licenças**
  (ex.: adicionar/renovar a licença de um tenant específico).
- Compatibilidade de pacotes de terceiros com a versão alvo do Laravel precisa
  ser verificada antes de fixar versões.

## Decisão

**Vamos usar**:

1. **`stancl/tenancy`** como base de multi-tenancy (não reinventar a roda).
2. **Modo single-database** (banco compartilhado, discriminador `tenant_id`).
3. **Identificação por subdomínio** (`cliente.dominio`) via
   `InitializeTenancyBySubdomain` do stancl.
4. **PostgreSQL** como SGBD, atrás do pooler **PgBouncer** — detalhes e trade-offs
   em [ADR-002](./ADR-002-postgres-pgbouncer.md).
5. Um **sistema de controle próprio (Painel)** para o ciclo de vida do tenant e
   **licenciamento** — ver
   [gestão de tenants e licenças](../../features/tenant-license-management.md).

## Consequências

### Positivas
- Isolamento "profundo" pronto (queries, cache, filas, storage) via bootstrappers
  do stancl.
- Menos código nosso de infraestrutura de tenancy para manter; foco no domínio e
  no Painel.
- Identificação por subdomínio e separação de rotas central/tenant já resolvidas
  pelo pacote.
- Caminho aberto para, no futuro, migrar para **multi-database** se necessário
  (o stancl suporta os dois modos).

### Negativas / Trade-offs
- Curva de aprendizado e uma dependência externa (caixa-preta) a acompanhar.
- **Risco de compatibilidade com a versão alvo do Laravel** — precisa ser
  confirmado antes de fixar a versão (ver
  [known-issues](../../ai-context/known-issues.md)).
- Convenções do pacote podem divergir das nossas em pontos específicos.

### Neutras
- O conceito central é `tenant_id` + global scope + auto-fill — muda a
  implementação (pacote vs próprio), não a ideia.

## Alternativas consideradas

### Alternativa A: Implementação própria de tenancy
**Descartada porque:** funciona para o escopo mínimo (filtrar queries por
subdomínio), mas teríamos de implementar e manter por conta própria o isolamento
de cache/filas/storage, provisionamento e migrations por tenant, além de cobrir
vários edge cases (jobs, comandos artisan, broadcasting). Custo de manutenção alto.

### Alternativa B: stancl/tenancy em modo multi-database
**Descartada por ora porque:** isolamento físico (um banco por tenant) traz
complexidade de provisionamento e migrations por tenant que não se justifica
agora. Pode ser **reavaliada** no futuro caso requisitos de compliance/isolamento
exijam — sem reescrever o domínio, já que continuaríamos no mesmo pacote.

## Referências
- [Arquitetura do sistema](../../../ARCHITECTURE.md)
- [ADR-002 — PostgreSQL + PgBouncer](./ADR-002-postgres-pgbouncer.md)
- [Roadmap single-user → multi-tenant](../migration-single-user-to-multitenant.md)
- Documentação do pacote: https://tenancyforlaravel.com/
