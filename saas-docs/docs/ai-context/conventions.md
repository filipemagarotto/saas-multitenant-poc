---
title: Convenções de Código e Projeto
status: stable
owner: engineering-lead
last_updated: 2025-06-01
ai_friendly: true
tags: [conventions, standards]
---

# Convenções

## Nomenclatura

- **Arquivos:** `kebab-case` para todos os arquivos
- **Variáveis/funções:** `camelCase` em TypeScript/JavaScript
- **Classes/Tipos:** `PascalCase`
- **Constantes:** `UPPER_SNAKE_CASE`
- **Tabelas de banco:** `snake_case`, plural (`users`, `workspaces`)
- **Colunas:** `snake_case` (`created_at`, `user_id`)

## Estrutura de pastas (backend)

```
src/
  modules/          # Um módulo por domínio (users, billing, notifications)
    [module]/
      [module].controller.ts
      [module].service.ts
      [module].repository.ts
      [module].schema.ts    # Validação Zod
      [module].types.ts
  shared/           # Utilitários, middlewares, helpers compartilhados
  config/           # Configuração e variáveis de ambiente
```

## Padrões de API

- Versionamento: `/api/v1/`
- Recursos em plural: `/api/v1/users`, `/api/v1/workspaces`
- IDs sempre UUID v4
- Timestamps sempre em ISO 8601 UTC
- Erros seguem o schema de `docs/api/error-codes.md`

## Git e PRs

- Commits: Conventional Commits (`feat:`, `fix:`, `docs:`, `chore:`)
- Branch: `type/ticket-descricao` (ex: `feat/PLT-123-webhook-retry`)
- PRs precisam de ao menos 1 aprovação + CI verde
- Squash merge para main

## Testes

- Unit tests: arquivo `.spec.ts` ao lado do arquivo testado
- Integration tests: pasta `tests/integration/`
- Cobertura mínima: 70% em lógica de negócio
- Mocks: usar `vi.mock()` do Vitest, nunca dados de produção

## O que NÃO fazer

- ❌ `any` em TypeScript — usar tipos explícitos ou `unknown`
- ❌ `console.log` em código de produção — usar o logger configurado
- ❌ Queries SQL raw sem sanitização — usar o ORM ou query builder
- ❌ Secrets hardcoded — sempre `process.env.NOME_DA_VAR`
