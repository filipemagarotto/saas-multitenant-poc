---
title: README
status: stable
owner: engineering-lead
last_updated: 2025-06-01
ai_friendly: true
tags: [overview, onboarding]
---

# [Nome do Produto]

> Uma frase descrevendo o que o produto faz e para quem.

## O que é

[2-3 parágrafos descrevendo o produto, o problema que resolve e o público-alvo.]

## Setup rápido (5 min)

```bash
git clone https://github.com/org/repo
cd repo
cp .env.example .env
docker compose up -d
pnpm install && pnpm dev
```

Acesse `http://localhost:3000`. Credenciais de dev: `admin@local.dev / password`.

## Documentação principal

| Documento | Descrição |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Visão geral do sistema |
| [docs/guides/onboarding.md](./docs/guides/onboarding.md) | Setup detalhado |
| [docs/api/authentication.md](./docs/api/authentication.md) | Como autenticar na API |
| [docs/ai-context/FULL_CONTEXT.md](./docs/ai-context/FULL_CONTEXT.md) | Contexto completo para IAs |
| [CHANGELOG.md](./CHANGELOG.md) | Histórico de versões |

## Stack

- **Frontend:** Next.js 14, TypeScript, Tailwind
- **Backend:** Node.js, Fastify, PostgreSQL, Redis
- **Infra:** AWS (ECS, RDS, ElastiCache), Terraform
- **CI/CD:** GitHub Actions

## Contatos

| Área | Contato |
|------|---------|
| Engineering | #eng-team (Slack) |
| On-call | PagerDuty / runbook em [docs/guides/runbooks/](./docs/guides/runbooks/) |
| Security | security@empresa.com |
