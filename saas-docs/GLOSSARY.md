---
title: Glossário
status: stable
owner: product-team
last_updated: 2025-06-01
ai_friendly: true
tags: [domain, glossary]
---

# Glossário

> Termos de domínio, entidades do sistema e siglas. Referência principal para alinhar linguagem entre produto, engineering e IAs.

## Entidades principais

| Termo | Definição | Notas |
|-------|-----------|-------|
| **Workspace** | Unidade organizacional. Uma empresa tem 1 workspace por padrão. | Pode ter sub-workspaces no plano Enterprise |
| **Member** | Usuário que pertence a um Workspace | Diferente de `User` — um User pode ser Member de vários Workspaces |
| **Plan** | Nível de assinatura (Free, Pro, Enterprise) | Define limites de uso e features disponíveis |
| **Seat** | Licença por usuário dentro de um Plan | Planos Pro e Enterprise são cobrados por seat |
| **Usage** | Consumo de recursos do produto (API calls, storage, etc.) | Medido mensalmente, resetado no ciclo de billing |

## Siglas e abreviações

| Sigla | Significado |
|-------|-------------|
| BFF | Backend for Frontend — o API Gateway que serve o frontend |
| ADR | Architecture Decision Record |
| SLO | Service Level Objective |
| MTR | Mean Time to Recovery |
| PII | Personally Identifiable Information |

## Estados e status

| Estado | Contexto | Significado |
|--------|----------|-------------|
| `pending` | Pagamento | Aguardando confirmação do gateway de pagamento |
| `active` | Workspace/Member | Em uso e com acesso completo |
| `suspended` | Workspace | Acesso bloqueado temporariamente (ex: inadimplência) |
| `churned` | Workspace | Cancelado definitivamente |
