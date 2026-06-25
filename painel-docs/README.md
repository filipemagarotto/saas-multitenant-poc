---
title: README — Painel (Control Plane)
status: draft
owner: filipe-magarotto
last_updated: 2026-06-25
ai_friendly: true
tags: [painel, control-plane, overview]
---

# Painel (Control Plane) — Documentação

> Documentação do **Painel**: a interface de controle interna da empresa, em
> **repositório separado** da aplicação multi-tenant. Estas docs serão migradas
> para o repo do Painel.

## O que é

O **Painel** é o *control plane* da plataforma — onde a equipe interna **cria e
gerencia tenants, licenças, suporte, rotinas, certificados e observabilidade**.
É um sistema **separado** do app dos tenants:

- Acesso por **subdomínio dedicado**, restrito a **administradores**.
- Roda na **mesma VPS** da aplicação, porém em **porta própria** e **banco
  próprio** (separado do banco compartilhado dos tenants).
- Conversa com a aplicação para provisionar tenants e definir licenças.

## Telas (Sprint 1)

Ver [docs/features/screens.md](./docs/features/screens.md). Cada tela tem um card
no Jira (épico **SCRUM-12**), com um prompt pronto para a IA construir.

| Tela | Card |
|------|------|
| Login do admin (subdomínio + reCAPTCHA/Turnstile) | SCRUM-13 |
| Menu central (shell) | SCRUM-14 |
| Tenants (listagem, criação, detalhe) | SCRUM-15 |
| Relatórios e dashboards (pg_stat_statements) | SCRUM-16 |
| Tickets de suporte | SCRUM-17 |
| Visualização de erros (GlitchTip) | SCRUM-18 |
| Gerenciamento de rotinas | SCRUM-19 |
| Auditoria | SCRUM-20 |
| Certificado SSL (Certbot) | SCRUM-21 |
| Licenças e add-ons | SCRUM-22 |

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Arquitetura do Painel e integrações |
| [docs/features/screens.md](./docs/features/screens.md) | Especificação das telas |
| [docs/ai-context/known-issues.md](./docs/ai-context/known-issues.md) | Decisões em aberto |

> **Contexto da aplicação:** o Painel administra o produto multi-tenant (Laravel +
> `stancl/tenancy` single-DB, auth JWT por tenant, PostgreSQL + PgBouncer + RLS,
> auditoria, GlitchTip). Ao construir o Painel, leia também os docs da **aplicação**.
