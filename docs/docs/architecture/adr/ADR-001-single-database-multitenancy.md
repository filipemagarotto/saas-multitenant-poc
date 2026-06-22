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

> A POC validou a estratégia com implementação própria. Para o **sistema oficial
> (produção)**, a decisão é adotar o pacote **`stancl/tenancy`** no modo
> single-database. A implementação própria da POC **não será portada**.

## Contexto

Temos hoje um sistema oficial **single-user** (atende um único cliente) e
queremos transformá-lo num produto **SaaS multi-tenant** (vários clientes
isolados). Antes de escrever código de produção, foi feito um spike para decidir
**como** implementar o multi-tenancy no Laravel.

Forças em jogo:

- Já existe uma POC ([este repositório](../../../README.md)) que **provou** o
  conceito de banco único com `tenant_id` + identificação por subdomínio, usando
  uma implementação própria enxuta (trait `BelongsToTenant`, middleware
  `IdentifyTenant`, singleton `Tenancy`).
- O isolamento de produção precisa ir além das queries: idealmente também
  **cache, filas (queues), storage e sessões** por tenant.
- Teremos um **sistema de controle próprio** para gerenciar tenants e **licenças**
  (ex.: adicionar/renovar a licença de um tenant específico).
- Estamos no **Laravel 13** (versão muito recente) — compatibilidade de pacotes
  de terceiros precisa ser verificada.

## Decisão

Para o sistema oficial multi-tenant, **vamos usar**:

1. **`stancl/tenancy`** como base de multi-tenancy (não reinventar a roda).
2. **Modo single-database** (banco compartilhado, discriminador `tenant_id`),
   alinhado ao que a POC validou e ao escopo do spike.
3. **Identificação por subdomínio** (`cliente.dominio`), como na POC
   (`InitializeTenancyBySubdomain` do stancl).
4. Um **sistema de controle próprio (control plane)** para o ciclo de vida do
   tenant e **licenciamento** — onde esse sistema vive (app central separado vs
   módulo) ainda é **decisão em aberto**, detalhada em
   [gestão de tenants e licenças](../../features/tenant-license-management.md).

A implementação própria da POC serviu para **aprender e validar**; ela **não** vai
para produção (ver "Alternativas").

## Consequências

### Positivas
- Isolamento "profundo" pronto (queries, cache, filas, storage) via bootstrappers
  do stancl — coisas que a POC não cobre.
- Menos código nosso de infraestrutura de tenancy para manter; foco no domínio e
  no control plane.
- Identificação por subdomínio e separação de rotas central/tenant já resolvidas
  pelo pacote.
- Caminho aberto para, no futuro, migrar para **multi-database** se necessário
  (o stancl suporta os dois modos).

### Negativas / Trade-offs
- Curva de aprendizado e uma dependência externa (caixa-preta) a acompanhar.
- **Risco de compatibilidade com Laravel 13** — precisa ser confirmado antes de
  fixar a versão (ver [arquitetura alvo](../target-production.md#riscos-a-validar)).
- Convenções do pacote podem divergir das nossas em pontos específicos.

### Neutras
- O conceito central (`tenant_id` + global scope + auto-fill) é o mesmo da POC —
  a equipe já entende o modelo mental; muda a implementação, não a ideia.

## Alternativas consideradas

### Alternativa A: Manter a implementação própria (como na POC)
**Descartada para produção porque:** funciona para o escopo mínimo (filtrar
queries por subdomínio), mas teríamos de implementar e manter por conta própria o
isolamento de cache/filas/storage, provisionamento e migrations por tenant, além
de cobrir vários edge cases (jobs, comandos artisan, broadcasting). Ótima para a
POC; custo de manutenção alto para produção.

### Alternativa B: stancl/tenancy em modo multi-database
**Descartada por ora porque:** isolamento físico (um banco por tenant) traz
complexidade de provisionamento e migrations por tenant que não se justifica
agora. Pode ser **reavaliada** no futuro caso requisitos de compliance/isolamento
exijam — sem reescrever o domínio, já que continuaríamos no mesmo pacote.

## Referências
- POC (implementação própria): [feature multi-tenancy](../../../ARCHITECTURE.md) e
  [autenticação por tenant](../../features/authentication.md)
- [Arquitetura alvo de produção](../target-production.md)
- [Roadmap single-user → multi-tenant](../migration-single-user-to-multitenant.md)
- Documentação do pacote: https://tenancyforlaravel.com/
