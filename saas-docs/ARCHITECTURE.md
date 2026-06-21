---
title: Arquitetura do Sistema
status: stable
owner: staff-engineer
last_updated: 2025-06-01
ai_friendly: true
tags: [architecture, system-design]
---

# Arquitetura do Sistema

## Visão geral

[Descrição em 1 parágrafo do que o sistema faz e como ele está organizado.]

## Diagrama de componentes

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Frontend  │────▶│   API GW    │────▶│   Backend   │
│  (Next.js)  │     │  (Fastify)  │     │  Services   │
└─────────────┘     └─────────────┘     └──────┬──────┘
                                               │
                         ┌─────────────────────┼──────────────┐
                         ▼                     ▼              ▼
                   ┌──────────┐        ┌──────────┐   ┌──────────┐
                   │PostgreSQL│        │  Redis   │   │   S3     │
                   └──────────┘        └──────────┘   └──────────┘
```

## Componentes principais

### Frontend
- **Tecnologia:** Next.js 14 com App Router
- **Responsabilidade:** Interface de usuário, SSR, autenticação via NextAuth
- **Comunicação:** REST com o API Gateway, WebSocket para eventos em tempo real

### API Gateway (Backend for Frontend)
- **Tecnologia:** Fastify + TypeScript
- **Responsabilidade:** Autenticação, rate limiting, roteamento para serviços internos
- **Padrões:** JWT stateless, validação com Zod

### Banco de dados
- **Principal:** PostgreSQL 15 — dados transacionais
- **Cache:** Redis 7 — sessões, cache de queries, filas de jobs
- **Storage:** S3 — arquivos de usuários, exports

## Fluxo de dados principal

1. Usuário faz request ao Frontend
2. Frontend chama o API Gateway com JWT
3. API Gateway valida token, aplica rate limit e roteia
4. Service processa a lógica de negócio
5. Resultado retorna e é cacheado no Redis quando aplicável

## Decisões de arquitetura

Ver pasta [docs/architecture/adr/](./docs/architecture/adr/) para Architecture Decision Records.

## O que NÃO fazer

- ❌ Nunca chamar o banco de dados diretamente do Frontend
- ❌ Não armazenar secrets em código — usar variáveis de ambiente ou AWS Secrets Manager
- ❌ Não implementar lógica de negócio no API Gateway — ele é apenas um proxy inteligente

## Limitações conhecidas

- O sistema não suporta multi-region atualmente (ver ADR-005)
- Jobs longos (>30s) devem usar a fila de background, não requests síncronos
