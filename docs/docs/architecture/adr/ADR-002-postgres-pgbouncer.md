---
title: "ADR-002: PostgreSQL + PgBouncer"
status: accepted
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [architecture, adr, database, postgres, pgbouncer]
---

# ADR-002: PostgreSQL + PgBouncer

## Status

`accepted`

> Complementa [ADR-001](./ADR-001-single-database-multitenancy.md) (multi-tenancy
> de banco único com `stancl/tenancy`). Aqui definimos o **SGBD** e o **pooling**.

## Contexto

O sistema oficial multi-tenant usa **banco único compartilhado** com `tenant_id`
(ADR-001). Precisamos escolher o SGBD e como gerenciar conexões, considerando que:

- Vários tenants compartilham o mesmo banco e a carga de conexões cresce com o
  número de tenants/requisições simultâneas (Laravel/PHP-FPM abre conexões por
  processo).
- Em **single-database**, **não** há troca de banco nem `SET search_path` por
  tenant — o isolamento é só por `tenant_id` na query. Isso simplifica o pooling.

## Decisão

1. **SGBD: PostgreSQL.** Banco único, isolamento lógico por `tenant_id`.
2. **Pooling: PgBouncer** entre a aplicação e o Postgres, para reaproveitar
   conexões e não esgotar o limite do banco sob muitos tenants/processos.
3. **Modo de pooling: `transaction`** como alvo (mais eficiente para web/PHP-FPM),
   **a confirmar por teste** com as ressalvas abaixo.
4. **Row-Level Security (RLS) habilitado** como camada de isolamento **no banco**,
   além do escopo por `tenant_id` da aplicação (defesa em profundidade). Políticas
   por tabela do tipo `USING (tenant_id = current_setting('app.tenant_id')::bigint)`,
   e o tenant atual é definido por requisição via **`SET LOCAL`** dentro de uma
   transação (ver interação com o PgBouncer abaixo).
5. **`pg_stat_statements` habilitado** para coletar estatísticas de execução das
   queries (base de observabilidade do banco).

## Consequências

### Positivas
- Escala melhor o número de conexões: muitos workers PHP-FPM compartilham um pool
  pequeno de conexões reais ao Postgres.
- Single-database + transaction pooling combinam bem: como não dependemos de
  estado de sessão por tenant (`SET search_path`, temp tables por tenant), o
  modo transaction é aplicável.
- **RLS** dá isolamento **no próprio banco**: mesmo que uma query da aplicação
  esqueça o filtro de tenant (bug), o Postgres não retorna linhas de outro tenant.
  Complementa (não substitui) o global scope do `stancl/tenancy`.

### Negativas / Trade-offs
- PgBouncer em modo `transaction` **não preserva estado de sessão** entre queries
  (prepared statements server-side, `SET`, advisory locks de sessão, `LISTEN/
  NOTIFY`). É preciso validar a configuração do driver PHP/PDO (ex.: prepared
  statements) para não quebrar nesse modo.
- Mais uma peça de infra para operar e monitorar (o PgBouncer).
- Migrar no futuro para **schema-per-tenant** (`SET search_path` por tenant)
  seria **incompatível** com transaction pooling — exigiria modo `session` ou
  outra estratégia. Hoje isso não se aplica (single-database).
- **RLS + transaction pooling exige cuidado:** o tenant atual deve ser definido com
  **`SET LOCAL` dentro da transação** (escopo da transação), nunca com `SET` de
  sessão — senão a configuração persiste na conexão do pool e poderia vazar para a
  requisição de outro tenant. O role da aplicação **não** pode ter `BYPASSRLS`
  (nem ser superuser); usar `FORCE ROW LEVEL SECURITY` nas tabelas de tenant.
- RLS adiciona overhead pequeno por query e exige disciplina nas migrations
  (criar a policy em toda tabela de tenant).

### Neutras
- A escolha do SGBD não muda o modelo de tenancy (single-database com `tenant_id`);
  muda a configuração de conexão e os recursos disponíveis.

## Alternativas consideradas

### Alternativa A: MySQL
**Descartada porque:** a decisão é PostgreSQL, pelos recursos que ele agrega ao
isolamento e à observabilidade (RLS, `pg_stat_statements`, JSONB).

### Alternativa B: Postgres sem PgBouncer
**Descartada porque:** sob muitos tenants/processos PHP-FPM, o número de conexões
diretas ao Postgres tende a estourar limites. O pooler é o caminho padrão.

### Alternativa C: Pooling em modo `session`
**Adiada:** mais compatível (preserva estado de sessão), porém reaproveita menos
conexões. Fica como **fallback** caso o modo transaction se mostre incompatível
com o driver/configuração.

## Observabilidade e monitoramento

- **`pg_stat_statements` (habilitado):** extensão que registra estatísticas de
  execução das queries (tempo total/médio, chamadas, linhas). Requer carregar a
  biblioteca no Postgres (`shared_preload_libraries = 'pg_stat_statements'`) e
  `CREATE EXTENSION pg_stat_statements`. É a base para identificar queries lentas.
  > Nota multi-tenant: as estatísticas são **por instância** (queries
  > normalizadas/parametrizadas), não por tenant — mostram o "formato" da query
  > agregando todos os tenants.
- **PgHero (possibilidade):** dashboard de monitoramento para Postgres que lê do
  `pg_stat_statements` (queries lentas, índices ausentes, conexões, locks). Fica
  como **opção em avaliação** para o monitoramento do banco — pode rodar junto na
  VPS. Decisão ainda **em aberto**.

## A validar (spike)

- [ ] Comportamento de **prepared statements** do PDO/Laravel sob PgBouncer
      `transaction` (ajustar configuração de conexão se necessário).
- [ ] Confirmar o **modo de pooling** definitivo por teste de carga.
- [ ] **Wiring de RLS:** definir o `app.tenant_id` via `SET LOCAL` por transação
      a cada inicialização de tenancy do `stancl/tenancy`, garantindo que funcione
      sob transaction pooling (sem vazamento entre tenants).
- [ ] Role/permissões do banco para RLS (sem `BYPASSRLS`, `FORCE ROW LEVEL
      SECURITY` nas tabelas de tenant).
- [ ] Compatibilidade da versão do `stancl/tenancy` com a versão alvo do Laravel
      (ver [ADR-001](./ADR-001-single-database-multitenancy.md)).

## Referências
- [ADR-001 — Multi-tenancy de banco único](./ADR-001-single-database-multitenancy.md)
- [Arquitetura do sistema](../../../ARCHITECTURE.md)
- PgBouncer: https://www.pgbouncer.org/
