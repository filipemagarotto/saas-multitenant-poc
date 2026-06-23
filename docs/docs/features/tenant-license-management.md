---
title: "Feature: Gestão de Tenants e Licenças (Painel)"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-22
ai_friendly: true
tags: [feature, spec, control-plane, licensing, multi-tenancy, target]
---

# Feature: Gestão de Tenants e Licenças (Painel)

> **Status: draft / o que QUEREMOS na prática.** Ainda não implementado. Faz parte
> da [arquitetura do sistema](../../ARCHITECTURE.md).

## Objetivo

Ter o **Painel** — um **sistema próprio da empresa**, separado do app dos tenants e
**hospedado na VPS contratada** — para gerenciar o ciclo de vida dos tenants e suas
**licenças**, e **monitorar os tenants de fora**. Ações como "criar um tenant",
"adicionar/renovar a licença de um tenant" ou "suspender um tenant" são feitas pelo
Painel — nunca direto no banco.

## Usuários-alvo

- **Quem:** equipe interna (comercial/operações/admin) da plataforma.
- **Job to be done:** "Quando fecho/renovo um contrato com um cliente, quero
  ativar/ajustar a licença do tenant dele, para liberar o acesso e as
  funcionalidades contratadas."

## Escopo

### Inclui
- Criar e **provisionar** tenants (slug/subdomínio, dados básicos).
- Gerenciar **licenças**: plano contratado, validade, limites e funcionalidades.
- **Ativar / suspender / reativar** tenants.
- **Monitorar os tenants de fora** (status, uso, saúde).
- O app multi-tenant **respeita** a licença do tenant atual (enforcement).

### Não inclui (out of scope desta spec)
- Billing/cobrança automática e gateway de pagamento (pode virar feature própria).
- Self-service de cadastro de tenant pelo cliente final.

## Decisões fixadas

- O Painel é um **sistema próprio separado**, em **repositório próprio** (não um
  módulo do app dos tenants; fora do escopo deste repo — aqui é só documentação).
- Roda na **mesma VPS contratada**, porém em **porta própria** e com **banco de
  dados próprio** (separado do banco compartilhado dos tenants).
- **Conversa com o app** dos tenants (mesma VPS) para provisionar e informar
  licença.

## Decisões em aberto

- [ ] Como o app dos tenants obtém tenant/licença do Painel: **API** do Painel ou
      **cache sincronizado** (uma tabela compartilhada está descartada — os bancos
      são separados), incluindo como a lista de tenants é sincronizada.
- [ ] Modelo de planos (fixos vs. features configuráveis por tenant).

## Comportamento esperado (fluxo: adicionar licença a um tenant)

1. Operador acessa o Painel e seleciona o **tenant**.
2. Define a **licença**: plano, data de validade, limites (ex.: nº de usuários) e
   funcionalidades liberadas.
3. Sistema persiste a licença vinculada ao `tenant_id`.
4. No app multi-tenant, a cada requisição do tenant, um **middleware de licença**
   verifica se a licença está **ativa e válida**:
   - válida → segue o fluxo normal;
   - expirada/suspensa → bloqueia com mensagem (ex.: "licença expirada, contate o
     suporte") e/ou restringe a um modo limitado.

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Licença expirada | App bloqueia acesso às áreas restritas; aviso ao usuário |
| Tenant suspenso | Bloqueio total exceto tela de aviso |
| Funcionalidade fora do plano | 403 / recurso oculto conforme as features da licença |
| Tenant sem licença (recém-criado) | Estado "pendente" até o Painel definir a licença |

## Modelo de dados (proposto — no banco PRÓPRIO do Painel, PostgreSQL)

> Estas tabelas vivem no **banco do Painel** (separado do banco dos tenants). Como
> os bancos são separados, o app dos tenants lê a licença via integração (API/
> cache), não por FK direta entre os bancos — ver "Decisões em aberto".

```sql
CREATE TABLE plans (
  id        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name      VARCHAR(255) NOT NULL,          -- ex.: "Básico", "Pro"
  features  JSONB NOT NULL,                 -- funcionalidades habilitadas
  limits    JSONB                           -- ex.: { "max_users": 10 }
);

CREATE TABLE licenses (
  id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  tenant_id  BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plan_id    BIGINT NOT NULL REFERENCES plans(id),
  status     VARCHAR(20) NOT NULL DEFAULT 'active'
             CHECK (status IN ('active','suspended','expired')),
  starts_at  TIMESTAMPTZ,
  expires_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ,
  updated_at TIMESTAMPTZ
);
```

## Métricas de sucesso

- [ ] 100% das ativações/renovações de tenant feitas pelo Painel (zero edição
      manual no banco).
- [ ] App multi-tenant bloqueia corretamente tenants com licença expirada/suspensa.

## Decisões técnicas

- O `tenant_id` permanece o eixo do isolamento (alinhado ao
  [ADR-001](../architecture/adr/ADR-001-single-database-multitenancy.md)).
- Enforcement de licença como **middleware**, complementar ao middleware de
  identificação de tenant do `stancl/tenancy`.
- O Painel é um app **separado** (repo próprio), na VPS contratada, com banco
  próprio; definir o contrato de integração (**API vs cache**) antes de
  implementar — ver "Decisões em aberto".
