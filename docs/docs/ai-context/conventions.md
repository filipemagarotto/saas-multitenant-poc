---
title: Convenções de Código e Projeto
status: stable
owner: filipe-magarotto
last_updated: 2026-06-21
ai_friendly: true
tags: [conventions, standards, laravel]
---

# Convenções

## Nomenclatura

- **Classes / Models:** `PascalCase` (`Pet`, `Tenant`, `IdentifyTenant`)
- **Métodos / variáveis:** `camelCase`
- **Tabelas de banco:** `snake_case`, plural (`pets`, `tenants`)
- **Colunas:** `snake_case` (`tenant_id`, `created_at`)
- **Rotas / slugs:** `kebab-case` / minúsculo (`cliente1`)
- **Arquivos de config:** `snake_case.php` em `config/`

## Estrutura relevante do projeto

```
app/
  Models/                 # Pet, Tenant, User
  Http/Middleware/        # IdentifyTenant
  Tenancy/                # Tenancy (singleton), BelongsToTenant (trait)
  Providers/              # AppServiceProvider (registra o singleton Tenancy)
config/
  tenancy.php             # central_domain
database/migrations/      # create_tenants, add_tenant_id_to_pets, ...
routes/
  web.php                 # grupos central e tenant
docs/                     # esta documentação
```

## Multi-tenancy (regras de ouro)

- Todo model com dados de cliente DEVE usar o trait `BelongsToTenant`.
- O tenant atual é acessado via `app(App\Tenancy\Tenancy::class)`.
- A leitura do tenant nas closures do trait acontece **em tempo de consulta**,
  nunca no boot (segurança em PHP-FPM com workers reaproveitados).
- Rotas de tenant ficam no grupo `Route::domain('{tenant}.'.$central)` com o
  middleware `tenant`.

## Banco de dados

- Migrations para toda mudança de schema (nunca alterar o banco "na mão").
- Atenção à **ordem das migrations**: quando o timestamp empata, o Laravel ordena
  por nome do arquivo. FKs exigem que a tabela referenciada seja criada antes.
- Charset `utf8mb4` (padrão das tabelas).
- A aplicação usa `saas_poc_user`, nunca o root.

## Git e commits

- **Conventional Commits** (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`).
- Branch de trabalho: `tipo/descricao-curta`.
- `main` = estável; `develop` = integração (ver estratégia de branches do repo).
- **Nunca** commitar `.env`, senhas ou chaves. Confirmar `.gitignore` antes de
  qualquer push inicial.

## O que NÃO fazer

- ❌ Consultar dados de cliente sem o trait `BelongsToTenant`.
- ❌ Usar o root do MySQL na aplicação.
- ❌ Hardcode de segredos — usar `.env` / `config()`.
- ❌ Editar `docs/ai-context/FULL_CONTEXT.md` à mão (é gerado por script).
- ❌ Editar o banco fora de migrations.
