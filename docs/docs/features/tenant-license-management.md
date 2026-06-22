---
title: "Feature: Gestão de Tenants e Licenças (Control Plane)"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-22
ai_friendly: true
tags: [feature, spec, control-plane, licensing, multi-tenancy, target]
---

# Feature: Gestão de Tenants e Licenças (Control Plane)

> **Status: draft / o que QUEREMOS na prática.** Ainda não implementado. Faz parte
> da [arquitetura alvo de produção](../architecture/target-production.md).

## Objetivo

Ter um **sistema de controle próprio** (control plane) para gerenciar o ciclo de
vida dos tenants e suas **licenças**. Ações como "adicionar/renovar a licença de
um tenant específico" ou "suspender um tenant" são feitas por esse sistema — nunca
direto no banco.

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
- O app multi-tenant **respeita** a licença do tenant atual (enforcement).

### Não inclui (out of scope desta spec)
- Billing/cobrança automática e gateway de pagamento (pode virar feature própria).
- Self-service de cadastro de tenant pelo cliente final.
- Definição final de **onde o control plane vive** — ver "Decisões em aberto".

## Decisões em aberto

- [ ] **Onde vive o control plane:** app central separado (com seu próprio repo/DB)
      **ou** módulo dentro do app multi-tenant. (No questionário do spike ficou
      como *indefinido*.)
- [ ] Como o app dos tenants lê a licença: tabela compartilhada no banco único,
      API do control plane, ou cache sincronizado.
- [ ] Modelo de planos (fixos vs. features configuráveis por tenant).

## Comportamento esperado (fluxo: adicionar licença a um tenant)

1. Operador acessa o sistema de controle e seleciona o **tenant**.
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
| Tenant sem licença (recém-criado) | Estado "pendente" até o control plane definir a licença |

## Modelo de dados (proposto — sujeito às decisões em aberto)

```sql
-- Vinculadas ao tenant (banco único, discriminador tenant_id).
CREATE TABLE plans (
  id           BIGINT PRIMARY KEY,
  name         VARCHAR(255) NOT NULL,         -- ex.: "Básico", "Pro"
  features     JSON NOT NULL,                 -- funcionalidades habilitadas
  limits       JSON NULL                      -- ex.: { "max_users": 10 }
);

CREATE TABLE licenses (
  id           BIGINT PRIMARY KEY,
  tenant_id    BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plan_id      BIGINT NOT NULL REFERENCES plans(id),
  status       ENUM('active','suspended','expired') NOT NULL DEFAULT 'active',
  starts_at    TIMESTAMP NULL,
  expires_at   TIMESTAMP NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL
);
```

## Métricas de sucesso

- [ ] 100% das ativações/renovações de tenant feitas pelo control plane (zero
      edição manual no banco).
- [ ] App multi-tenant bloqueia corretamente tenants com licença expirada/suspensa.

## Decisões técnicas

- O `tenant_id` permanece o eixo do isolamento (alinhado ao
  [ADR-001](../architecture/adr/ADR-001-single-database-multitenancy.md)).
- Enforcement de licença como **middleware**, complementar ao middleware de
  identificação de tenant do `stancl/tenancy`.
- Se o control plane for um app separado, definir o contrato de integração
  (tabela compartilhada vs API) antes de implementar — ver "Decisões em aberto".
