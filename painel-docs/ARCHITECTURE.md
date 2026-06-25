---
title: Arquitetura do Painel
status: draft
owner: filipe-magarotto
last_updated: 2026-06-25
ai_friendly: true
tags: [painel, control-plane, architecture]
---

# Arquitetura do Painel

## Visão geral

O **Painel** é a interface de controle interna da empresa (*control plane*), um
sistema **separado** do app dos tenants. Administra a plataforma multi-tenant:
tenants, licenças, suporte, rotinas, certificados e observabilidade.

- **Repositório próprio** (não é parte do repo da aplicação).
- **Subdomínio dedicado** (ex.: `painel.<dominio>`), acesso restrito a **admins**.
- **Mesma VPS** da aplicação, mas **porta própria** e **banco próprio** (separado
  do banco compartilhado dos tenants).
- **Stack:** a definir — provável **Laravel + Tailwind** para consistência com a
  aplicação e com o **brand book** (nos docs da aplicação).

## Autenticação

- Login de **admin** com **username + senha**.
- **Sem** recuperação de senha (fluxo direto).
- Proteção anti-bot: **reCAPTCHA** ou **Cloudflare Turnstile** (validado no backend).
- Distinto da auth da aplicação (que é JWT por tenant); aqui é login administrativo.

## Relação com a aplicação (integração)

O Painel **gerencia** o app multi-tenant, mas tem banco próprio. Logo, a troca de
dados é por **integração**, não por banco compartilhado:

- **Provisionamento de tenants e licenças:** o Painel cria/define; o app consome
  (via **API** do Painel ou **cache** sincronizado — a definir).
- **Relatórios/auditoria:** leem dados da **aplicação** (registros por tabela/
  tenant, trilhas de auditoria). Definir o acesso: leitura ao Postgres da app ou
  endpoints de leitura — **decisão em aberto**.

## Integrações externas

| Integração | Para quê |
|------------|----------|
| **API/cache da aplicação** | Provisionar tenants, definir/ler licenças |
| **PostgreSQL da aplicação** (`pg_stat_statements`) | Relatórios e métricas por tenant |
| **GlitchTip** (self-hosted) | Visualização de erros/exceptions |
| **Certbot** | Emissão/renovação de certificados SSL |

## Telas

Detalhe em [docs/features/screens.md](./docs/features/screens.md): Login · Menu
central · Tenants · Relatórios/Dashboards · Tickets · Erros (GlitchTip) · Rotinas ·
Auditoria · SSL (Certbot) · Licenças & add-ons.

## UI

Segue o **brand book** (Tailwind) e a **persona de UX** definidos nos docs da
aplicação. **Cores de marca via token** — não fixar cor de marca nos componentes.

## Decisões em aberto

Ver [docs/ai-context/known-issues.md](./docs/ai-context/known-issues.md).
