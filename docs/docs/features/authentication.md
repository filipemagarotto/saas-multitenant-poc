---
title: "Feature: Autenticação por Tenant"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [feature, auth, multi-tenancy, security]
---

# Feature: Autenticação por Tenant

## Objetivo

Cada tenant tem seus próprios usuários e seu próprio login. Um usuário de um tenant
só consegue se autenticar no subdomínio dele e só enxerga os dados do próprio
tenant — nunca os de outro.

## Comportamento esperado

1. Visitante acessa uma rota protegida em `cliente.dominio`.
2. A tenancy é inicializada pelo subdomínio (middleware do `stancl/tenancy`).
3. Se não houver usuário logado, redireciona para o `/login` do **próprio
   subdomínio** (caminho relativo, mantém o tenant).
4. No login, a busca por credenciais já vem **filtrada pelo tenant atual** (o model
   `User` usa o trait `BelongsToTenant`), então só autentica usuários **deste**
   tenant.
5. Autenticado, segue para as áreas restritas, que mostram apenas dados do tenant.

## Como o isolamento é garantido

- **`User` escopado por tenant:** `users.tenant_id` + trait `BelongsToTenant`. A
  autenticação (`Auth::attempt`) e a recuperação do usuário logado a cada
  requisição já vêm filtradas por tenant. Um usuário de um tenant não autentica em
  outro.
- **Cookie de sessão host-only:** `SESSION_DOMAIN` vazio → o cookie do
  `cliente.dominio` não trafega para outros subdomínios (defesa em profundidade).
- **Ordem dos middlewares:** tenancy **antes** de autenticação, para o escopo do
  `User` funcionar na resolução do usuário logado.

## Escopo

### Inclui
- Login/logout no subdomínio do tenant.
- Usuários pertencem a um tenant (`users.tenant_id`, e-mail único por tenant).
- Rotas de dados protegidas por autenticação.

### Não inclui (out of scope desta fase)
- Self-service de cadastro de usuários (criados pelo control plane/seed).
- Recuperação de senha / verificação de e-mail.
- Papéis/permissões finos (pode virar feature própria).

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Acesso sem login | Redireciona para o `/login` do próprio subdomínio |
| Credenciais de um tenant usadas em outro subdomínio | Falha: usuário não existe no escopo daquele tenant |
| Sessão de um tenant reaproveitada em outro | Tratado como visitante (a resolução do usuário é escopada por tenant) |
| E-mail repetido em tenants diferentes | Permitido: e-mail é único **por tenant** |

## Modelo de dados

- `users.tenant_id` **NOT NULL** + FK para `tenants`.
- Unicidade de e-mail **por tenant**: `unique(tenant_id, email)`.

## Referências
- [Multi-tenancy](./multi-tenancy.md)
- [Arquitetura do sistema](../../ARCHITECTURE.md)
- Documentação do pacote: https://tenancyforlaravel.com/
