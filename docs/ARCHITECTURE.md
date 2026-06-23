---
title: Arquitetura do Sistema
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [architecture, system-design, multi-tenancy, stancl, postgres]
---

# Arquitetura do Sistema

> Arquitetura do sistema multi-tenant. As decisões formais estão nos ADRs
> ([ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md),
> [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md)).

## Visão geral

Aplicação **Laravel** multi-tenant com **banco de dados compartilhado** (um único
banco, todos os tenants nas mesmas tabelas, separados pela coluna `tenant_id`). A
tenancy é provida pelo pacote **`stancl/tenancy`** em **modo single-database**. O
tenant é identificado pelo **subdomínio** da requisição. O banco é **PostgreSQL**,
acessado através do pooler **PgBouncer**, com **Row-Level Security (RLS)** como
camada extra de isolamento no próprio banco.

O **Painel** (sistema próprio da nossa empresa) gerencia o ciclo de vida dos
tenants e suas **licenças** — e permite **criar novos tenants e monitorá-los de
fora** (ver [feature](./docs/features/tenant-license-management.md)). Ele roda na
**mesma VPS contratada**, mas em **porta própria** e com **banco de dados próprio**
(separado do banco dos tenants), conversando com o app. O Painel é um
**repositório separado** — aqui apenas documentamos essa integração.

## Diagrama de componentes

```
   Painel (REPO SEPARADO)         ┌─────────────────────────────┐      ┌──────────────┐
   mesma VPS · porta própria      │  Painel (porta própria)     │─────▶│  Banco do    │
   ─ documentado aqui só p/       │  cria/provisiona tenants,   │      │  Painel      │
     registro ─                   │  licenças, monitoramento    │      │  (próprio)   │
                                  └──────────────┬──────────────┘      └──────────────┘
                                                 │ conversa com o app (mesma VPS)
                                                 ▼
   Navegador                      ┌────────────────────────┐
 cliente.dominio  ──HTTPS───────▶ │  App Laravel +          │
                                  │  stancl/tenancy         │
                                  │  (single-database)      │
                                  │  subdomínio → tenant    │
                                  │  licença → acesso       │
                                  └───────────┬────────────┘
                                              │ SQL (pool)
                                              ▼
                                  ┌────────────────────────┐      ┌─────────────────┐
                                  │     PgBouncer           │─────▶│  PostgreSQL     │
                                  │  (connection pooler)    │      │  banco único    │
                                  └────────────────────────┘      │  (tenant_id)    │
                                                                  └─────────────────┘
```

## Componentes principais

### App Laravel + `stancl/tenancy`
- **Tenancy:** pacote `stancl/tenancy` em **modo single-database**. Não há troca
  de conexão/banco por tenant — o isolamento é por `tenant_id` via *global scope*
  (trait `BelongsToTenant` do pacote), que filtra as queries e preenche o
  `tenant_id` ao criar registros.
- **Identificação:** por **subdomínio** (`InitializeTenancyBySubdomain`). O
  domínio central serve rotas não-tenant.
- **Bootstrappers:** o pacote isola por tenant também **cache, filas (queues) e
  storage**, além das queries.
- **Autenticação:** via **JWT** (stateless), com o `tenant_id` no token validado
  contra o subdomínio — ver [autenticação](./docs/features/authentication.md).

### PostgreSQL (banco de dados)
- **Banco único compartilhado** por todos os tenants. Isolamento por
  `tenant_id` (ver [ADR-001](./docs/architecture/adr/ADR-001-single-database-multitenancy.md)).
- **Row-Level Security (RLS):** políticas no banco filtram as linhas pelo tenant
  atual (`SET LOCAL app.tenant_id` por transação). É uma camada **no banco**,
  complementar ao escopo da aplicação — mesmo um bug de query não vaza dados de
  outro tenant. Detalhes e interação com o PgBouncer:
  [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md).
- **Observabilidade:** `pg_stat_statements` habilitado para estatísticas de
  queries; **PgHero** em avaliação como dashboard de monitoramento (ver ADR-002).

### PgBouncer (connection pooler)
- Fica entre a aplicação e o Postgres, reutilizando conexões para suportar muitas
  requisições/tenants sem esgotar as conexões do banco.
- Como estamos em **single-database** (sem `SET search_path`/troca de conexão por
  tenant), o pooling em **modo transaction** é viável — com as ressalvas de
  prepared statements documentadas no [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md).

### Painel (control plane próprio)
- **Sistema próprio da empresa**, em **repositório separado** — fora do escopo
  deste repo, documentado aqui só para registrar a integração.
- Roda na **mesma VPS contratada**, mas em **porta própria** e com **banco de
  dados próprio** (separado do banco compartilhado dos tenants); conversa com o
  app.
- Gerencia tenants (criação/provisionamento) e **licenças** (plano, validade,
  limites, funcionalidades), e **monitora os tenants de fora**.
- Como o app dos tenants lê a licença (API do Painel ou cache; **não** via tabela
  compartilhada, já que os bancos são separados) ainda é **decisão em aberto** —
  ver [feature](./docs/features/tenant-license-management.md).

## Multi-tenancy — como o isolamento funciona

1. **Identificação:** o middleware do `stancl/tenancy` lê o subdomínio e inicializa
   o tenant atual (ou retorna 404 se o subdomínio não corresponder a um tenant).
2. **Isolamento na aplicação:** todo model com dados de cliente usa o trait
   `BelongsToTenant` do pacote → *global scope* filtra por `tenant_id` e o
   preenche automaticamente ao criar.
3. **Isolamento no banco (RLS):** políticas de Row-Level Security garantem, no
   próprio Postgres, que cada tenant só vê suas linhas — defesa em profundidade
   caso uma query escape do escopo da aplicação.
4. **Autenticação por tenant (JWT):** login via **JWT**; o token carrega o
   `tenant_id` e é validado contra o subdomínio (um token de um tenant não vale em
   outro). O `User` também é escopado por tenant (ver
   [autenticação](./docs/features/authentication.md)).
5. **Licença:** um middleware verifica a licença do tenant atual antes de liberar
   as áreas restritas.

## Fluxo de uma requisição de tenant

1. Navegador acessa `cliente.dominio/...`.
2. O middleware do `stancl/tenancy` identifica `cliente` pelo subdomínio e
   inicializa a tenancy.
3. Middleware de licença confere se o tenant está ativo/em dia.
4. Middleware de autenticação valida o **JWT** (assinatura, expiração e
   `tenant_id` == tenant atual) e resolve o usuário **deste** tenant.
5. As queries dos models de cliente já vêm filtradas por `tenant_id`.

## Observabilidade e auditoria

Três trilhas **distintas**, com propósitos diferentes:

| Trilha | O que registra | Como |
|--------|----------------|------|
| **Erros/exceções** | Falhas técnicas da aplicação | **GlitchTip** (self-hosted, open source, compatível com SDKs Sentry) |
| **Audit log de negócio** | Quem fez o quê no produto (accountability) | Tabela `audit_logs`, escopada por tenant (RLS) |
| **Log de segurança/acesso** | Eventos de auth/autorização (abuso, incidentes) | Tabela `security_events`; pode alimentar o Painel |

- **GlitchTip:** monitora **erros/exceções** da aplicação. Self-hosted (pode rodar
  na VPS); anexar o `tenant_id` como contexto/tag nos eventos ajuda a saber de qual
  tenant veio o erro.
- **Monitoramento do banco:** `pg_stat_statements` (+ PgHero em avaliação) — ver
  [ADR-002](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md).
- **Auditoria (negócio e segurança):** detalhes e modelo de dados em
  [auditoria](./docs/features/auditing.md).

## Decisões de arquitetura (ADRs)

- [ADR-001 — Multi-tenancy de banco único com stancl/tenancy](./docs/architecture/adr/ADR-001-single-database-multitenancy.md)
- [ADR-002 — PostgreSQL + PgBouncer](./docs/architecture/adr/ADR-002-postgres-pgbouncer.md)

## O que NÃO fazer

- ❌ Não consultar models com dados de tenant **sem** o trait `BelongsToTenant` —
  fura o isolamento.
- ❌ Não emitir/aceitar JWT sem o claim de `tenant_id` ou sem validá-lo contra o
  subdomínio — é o que impede um token de um tenant valer em outro.
- ❌ Se usar sessão/cookies para algo, não definir `SESSION_DOMAIN` no domínio raiz
  (`.dominio`) — manter host-only para não trafegar entre subdomínios. (A
  autenticação em si é via JWT, stateless.)
- ❌ Não acessar/alterar tenants ou licenças direto no banco — usar o Painel.
- ❌ Não assumir **schema/banco por tenant** (multi-database): a decisão atual é
  **single-database**. Migrar para schema-per-tenant no futuro exige rever o modo
  de pooling do PgBouncer (ver ADR-002).

## Decisões em aberto

- [ ] Como o app dos tenants lê a licença do Painel (tabela compartilhada, API ou
      cache).
- [ ] Versão alvo de Laravel × compatibilidade do `stancl/tenancy` (validar).
- [ ] Modo de pooling do PgBouncer (transaction vs session) e wiring do RLS
      (`SET LOCAL` por transação) confirmados por teste.

## Limitações e riscos

Ver [known-issues](./docs/ai-context/known-issues.md).
