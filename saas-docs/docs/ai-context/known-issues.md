---
title: Limitações e Problemas Conhecidos
status: stable
owner: engineering-lead
last_updated: 2025-06-01
ai_friendly: true
tags: [known-issues, tech-debt]
---

# Limitações e Problemas Conhecidos

> Para IAs: ao sugerir mudanças de código, verifique se a sugestão não conflita com alguma das limitações abaixo.

## Dívidas técnicas ativas

| ID | Descrição | Impacto | Workaround ativo |
|----|-----------|---------|-----------------|
| TD-001 | A tabela `events` não tem índice em `workspace_id` | Queries lentas para workspaces grandes | Cache no Redis por 60s |
| TD-002 | Autenticação não suporta SSO ainda | Bloqueador para clientes Enterprise | - |
| TD-003 | Módulo de billing usa Stripe API v1 (deprecada em 2026) | Migração necessária antes de dez/2025 | - |

## Limitações de produto

- Máximo de 10.000 registros por export CSV (limitação de memória)
- Webhooks têm retry automático por até 24h, não configurável pelo usuário
- Pesquisa full-text usa `ILIKE` (não tem índice FTS ainda)

## Comportamentos não-óbvios

- Ao deletar um `Workspace`, os dados ficam em soft-delete por 30 dias antes da exclusão permanente
- O rate limit de API é por `workspace_id`, não por `user_id`
- Jobs de background têm timeout de 30s — jobs longos devem usar chunks
