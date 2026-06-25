---
title: Painel — Decisões em aberto e riscos
status: draft
owner: filipe-magarotto
last_updated: 2026-06-25
ai_friendly: true
tags: [painel, known-issues, open-decisions]
---

# Painel — Decisões em aberto e riscos

| ID | Tema | Pendência |
|----|------|-----------|
| PD-001 | **Stack do Painel** | Confirmar (provável Laravel + Tailwind, alinhado à aplicação e ao brand book) |
| PD-002 | **Integração app ↔ Painel** | Como o app lê tenants/licenças do Painel (API vs cache); bancos são separados |
| PD-003 | **Acesso aos dados da aplicação** | Relatórios/auditoria: leitura ao Postgres da app vs endpoints de leitura |
| PD-004 | **Anti-bot do login** | reCAPTCHA vs Cloudflare Turnstile |
| PD-005 | **GlitchTip** | Embed/link vs consumir a API; viabilidade do filtro por tenant |
| PD-006 | **Regras de licença** | Por ora só "nome"; modelar regras futuras (ex.: máx. usuários) e add-ons |
| PD-007 | **Rotinas** | Contrato do script de criação; modelo de histórico de execuções |
| PD-008 | **Auditoria** | Estratégia de armazenamento do JSON de versões (máx. 3 versões) |

## Notas

- O Painel tem **banco próprio**; dados da aplicação vêm por integração, não por
  tabela compartilhada.
- `pg_stat_statements` agrega por instância (queries normalizadas), **não** por
  tenant — relatórios por tenant precisam de outra estratégia (ex.: contagem por
  `tenant_id`).
- UI segue o brand book (Tailwind); **cor de marca via token**, sem hardcode.
