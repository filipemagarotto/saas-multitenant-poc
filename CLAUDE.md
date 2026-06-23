# Instruções para IAs — Sistema Multi-tenant

SaaS **multi-tenant** em Laravel. **Antes de codar, leia [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)**
e as convenções em [docs/docs/ai-context/conventions.md](docs/docs/ai-context/conventions.md).

## Pilares (decisões fixadas)

- **Multi-tenancy:** banco único compartilhado (`tenant_id`) com **`stancl/tenancy`**
  (modo single-database). Tenant identificado por **subdomínio**.
- **Banco:** **PostgreSQL** atrás do **PgBouncer**; **RLS** habilitado como camada
  de isolamento no banco.
- **Autenticação:** **JWT** — o token carrega `tenant_id`, validado contra o
  subdomínio.
- **Painel** (control plane): sistema próprio em **repo separado**, na mesma VPS,
  com **porta e banco próprios**. Cria/gerencia tenants e licenças; monitora de
  fora.
- **Observabilidade:** **GlitchTip** (erros) + `pg_stat_statements`; auditoria em
  duas trilhas (negócio e segurança).

## Regras inegociáveis

- Todo model com dados de cliente **USA o trait `BelongsToTenant`**. Nunca rodar
  query de dados de cliente sem o escopo de tenant.
- `tenant_id` é **NOT NULL** em tabelas de tenant.
- JWT **sempre** com claim `tenant_id` validado contra o subdomínio (um token de um
  tenant não vale em outro).
- RLS: definir o tenant atual com **`SET LOCAL app.tenant_id` por transação**
  (compatível com transaction pooling); role do app **sem** `BYPASSRLS`.
- Tenants e licenças são geridos **só pelo Painel** — nunca editar direto no banco.
- O middleware de tenancy roda **antes** de autenticação e licença.

## Documentação

- Arquitetura: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- ADRs: [single-DB/stancl](docs/docs/architecture/adr/ADR-001-single-database-multitenancy.md) ·
  [Postgres/PgBouncer/RLS](docs/docs/architecture/adr/ADR-002-postgres-pgbouncer.md)
- Features: [multi-tenancy](docs/docs/features/multi-tenancy.md) ·
  [auth (JWT)](docs/docs/features/authentication.md) ·
  [tenants/licenças (Painel)](docs/docs/features/tenant-license-management.md) ·
  [auditoria](docs/docs/features/auditing.md)
- Convenções: [conventions](docs/docs/ai-context/conventions.md) ·
  Decisões em aberto/riscos: [known-issues](docs/docs/ai-context/known-issues.md)
