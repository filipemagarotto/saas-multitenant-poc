# Instruções para IAs — Painel (Control Plane)

O **Painel** é a interface de controle interna da empresa (*control plane*), em
**repositório separado** da aplicação multi-tenant. **Antes de codar, leia
[ARCHITECTURE.md](ARCHITECTURE.md) e [docs/features/screens.md](docs/features/screens.md)**
— e também os docs da **aplicação** que o Painel administra.

## O que é (fixado)

- Sistema **separado** do app dos tenants, em repo próprio.
- Acesso por **subdomínio dedicado**, restrito a **administradores**.
- Roda na **mesma VPS**, em **porta própria** e **banco próprio**.
- Gerencia tenants, licenças, suporte, rotinas, certificados e observabilidade.

## Regras

- **Login admin:** username + senha, **sem** recuperação de senha, com
  **reCAPTCHA/Cloudflare Turnstile** validado no backend. Distinto do JWT da app.
- **Integração com a aplicação:** bancos são **separados** — ler tenants/licenças
  via **API/cache** do/para o app, nunca por tabela compartilhada.
- **UI:** seguir o **brand book** (Tailwind) e a **persona de UX** dos docs da
  aplicação. **Cor de marca via token do tenant**, nunca hardcoded.
- Tenants e licenças são geridos **só pelo Painel** — nunca editar direto no banco
  da aplicação.

## Telas (Sprint 1)

Login · Menu central · Tenants · Relatórios/Dashboards (pg_stat_statements) ·
Tickets · Erros (GlitchTip) · Rotinas · Auditoria · SSL (Certbot) · Licenças &
add-ons. Detalhe e prompts: épico Jira **SCRUM-12**.

## Aberto

Ver [docs/ai-context/known-issues.md](docs/ai-context/known-issues.md).
