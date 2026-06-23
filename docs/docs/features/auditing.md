---
title: "Feature: Auditoria e Logs (negócio e segurança)"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [feature, audit, security, observability, multi-tenancy]
---

# Feature: Auditoria e Logs (negócio e segurança)

## Objetivo

Suportar **dois tipos de trilha** complementares, com propósitos diferentes:

1. **Audit log de negócio** — *accountability*: quem fez o quê dentro do produto.
2. **Log de segurança/acesso** — *detecção de abuso/incidentes*: eventos sensíveis
   de autenticação e autorização.

> Não confundir com **monitoramento de erros** (exceções técnicas), que é feito
> pelo **GlitchTip** (ver [arquitetura](../../ARCHITECTURE.md#observabilidade-e-auditoria)).
> São três coisas distintas: erros técnicos (GlitchTip), ações de negócio (audit
> log) e eventos de segurança (log de acesso).

## 1. Audit log de negócio

Registro de **ações dos usuários sobre os dados do produto**, para o negócio
rastrear histórico e responsabilidade.

- **Exemplo:** "O usuário João editou o cadastro do cliente Maria às 14h."
- **Multi-tenant:** escopado por tenant (`tenant_id`), como qualquer dado de
  cliente — usa o trait `BelongsToTenant` e é protegido por RLS.
- **O que registrar:** ator (usuário), ação (create/update/delete/…), entidade
  alvo (tipo + id), o que mudou (antes/depois), quando.

## 2. Log de segurança/acesso

Registro de **eventos sensíveis de autenticação e autorização**, para detectar
abuso e investigar incidentes.

- **Exemplos:** "5 tentativas de login falhas", "alguém mudou uma permissão",
  "emissão/revogação de token", "login de novo dispositivo/IP".
- **Multi-tenant:** registra o `tenant_id` quando houver contexto de tenant, mas
  alguns eventos podem ser relevantes **de forma agregada** (ex.: abuso por IP
  atravessando tenants). Pode ser **surfaçado ao Painel** para o monitoramento
  externo dos tenants (ver [Painel](./tenant-license-management.md)).
- **O que registrar:** tipo de evento, ator (se conhecido), resultado
  (sucesso/falha), IP, user-agent, metadados, quando.

## Comportamento esperado

1. Ações de negócio relevantes disparam um registro no **audit log** (ex.: via
   eventos/observers do model, de forma centralizada para não depender de cada
   tela lembrar de logar).
2. Eventos de auth/autorização (login ok/falha, logout, mudança de permissão,
   emissão/revogação de JWT) disparam um registro no **log de segurança**.
3. Erros/exceções da aplicação são capturados pelo **GlitchTip** (não entram aqui).

## Modelo de dados (proposto — PostgreSQL)

```sql
-- Audit log de NEGÓCIO (escopado por tenant, com RLS).
CREATE TABLE audit_logs (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  tenant_id     BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  actor_user_id BIGINT REFERENCES users(id),
  action        VARCHAR(100) NOT NULL,        -- ex.: 'customer.updated'
  subject_type  VARCHAR(100),                 -- ex.: 'Customer'
  subject_id    BIGINT,
  changes       JSONB,                        -- antes/depois
  ip            INET,
  created_at    TIMESTAMPTZ NOT NULL
);

-- Log de SEGURANÇA/ACESSO.
CREATE TABLE security_events (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  tenant_id     BIGINT REFERENCES tenants(id) ON DELETE CASCADE,  -- nulo se sem contexto
  event_type    VARCHAR(100) NOT NULL,        -- ex.: 'login.failed', 'permission.changed'
  actor_user_id BIGINT REFERENCES users(id),  -- pode ser nulo
  outcome       VARCHAR(20),                  -- 'success' | 'failure'
  ip            INET,
  user_agent    TEXT,
  metadata      JSONB,
  created_at    TIMESTAMPTZ NOT NULL
);
```

## Decisões em aberto

- [ ] **Retenção**: por quanto tempo manter cada trilha (negócio vs segurança).
- [ ] **Onde vive o log de segurança**: só no banco do app, e/ou enviado ao Painel
      para monitoramento externo.
- [ ] **Append-only / imutabilidade**: garantir que registros de auditoria não são
      editáveis/apagáveis (ex.: permissões no banco, tabela append-only).
- [ ] **Captura automática**: estratégia (eventos do Eloquent, middleware, listener
      de eventos de auth) para não depender de chamadas manuais.

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Evento sem usuário (ex.: login falho com e-mail inexistente) | Registrado no log de segurança com `actor_user_id` nulo |
| Ação de negócio fora de contexto de tenant | Não deveria ocorrer; auditoria de negócio sempre tem `tenant_id` |
| Pico de falhas de login | Detectável pelo log de segurança; pode alimentar alerta no Painel |

## Referências
- [Arquitetura do sistema](../../ARCHITECTURE.md#observabilidade-e-auditoria)
- [Autenticação por tenant (JWT)](./authentication.md)
- [Gestão de tenants e licenças (Painel)](./tenant-license-management.md)
