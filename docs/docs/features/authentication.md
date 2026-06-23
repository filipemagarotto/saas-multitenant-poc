---
title: "Feature: Autenticação por Tenant (JWT)"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [feature, auth, jwt, multi-tenancy, security]
---

# Feature: Autenticação por Tenant (JWT)

## Objetivo

Cada tenant tem seus próprios usuários e seu próprio login. Um usuário de um tenant
só consegue se autenticar no subdomínio dele e só enxerga os dados do próprio
tenant — nunca os de outro. O login é feito via **JWT** (token stateless).

## Comportamento esperado

1. Cliente acessa `cliente.dominio` e a tenancy é inicializada pelo subdomínio
   (middleware do `stancl/tenancy`).
2. No **login**, as credenciais são validadas **já filtradas pelo tenant atual** (o
   model `User` usa o trait `BelongsToTenant`), então só autentica usuários
   **deste** tenant.
3. Em caso de sucesso, o backend emite um **JWT assinado** contendo, no mínimo, o
   **id do usuário** e o **`tenant_id`** (claims), com prazo de expiração.
4. A cada requisição protegida, o cliente envia o token (ex.: header
   `Authorization: Bearer <jwt>`). O backend:
   - valida **assinatura** e **expiração**;
   - confere que o **`tenant_id` do token == tenant do subdomínio atual**;
   - resolve o usuário (busca escopada por tenant).
5. Sem token válido (ou tenant divergente) → **401 Unauthorized**.

## Como o isolamento é garantido

- **JWT vinculado ao tenant:** o token carrega o `tenant_id`; o backend rejeita um
  token cujo tenant não bate com o subdomínio. Um token emitido para um tenant
  **não** vale em outro.
- **`User` escopado por tenant:** `users.tenant_id` + trait `BelongsToTenant`. A
  autenticação e a resolução do usuário a cada requisição já vêm filtradas por
  tenant — um usuário de um tenant não autentica/resolve em outro.
- **Ordem dos middlewares:** tenancy **antes** de autenticação, para o escopo do
  `User` e a checagem do claim de tenant funcionarem.

## JWT — pontos de implementação

- **Claims mínimas:** `sub` (id do usuário), `tenant_id` (e opcionalmente o slug),
  `iat`/`exp`.
- **Validação de tenant:** comparar o claim de tenant com o tenant resolvido pelo
  subdomínio em **todo** request autenticado (guard/middleware).
- **Expiração e refresh:** access token de vida curta; estratégia de **refresh
  token** a definir.
- **Revogação:** JWT é stateless — para logout/revogação imediata, prever
  denylist/short-TTL (a definir).
- **Chave de assinatura:** segredo/par de chaves único do app, guardado fora do
  código (`.env`/gerenciador de segredos). Algoritmo (ex.: HS256/RS256) a definir.
- **Biblioteca/guard:** usar um guard JWT do Laravel (biblioteca a definir — ver
  [known-issues](../ai-context/known-issues.md)).

## Escopo

### Inclui
- Login (emite JWT) e logout no contexto do tenant.
- Usuários pertencem a um tenant (`users.tenant_id`, e-mail único por tenant).
- Rotas de dados protegidas por JWT, com checagem do tenant do token.

### Não inclui (out of scope desta fase)
- Self-service de cadastro de usuários (criados pelo Painel/seed).
- Recuperação de senha / verificação de e-mail.
- Papéis/permissões finos (pode virar feature própria).

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Requisição sem token (ou token inválido) | 401 |
| Token expirado | 401 (cliente deve renovar/refazer login) |
| Credenciais de um tenant usadas em outro subdomínio | Falha: usuário não existe no escopo daquele tenant |
| Token emitido para um tenant usado em outro subdomínio | 401: `tenant_id` do token diverge do tenant atual |
| E-mail repetido em tenants diferentes | Permitido: e-mail é único **por tenant** |

## Modelo de dados

- `users.tenant_id` **NOT NULL** + FK para `tenants`.
- Unicidade de e-mail **por tenant**: `unique(tenant_id, email)`.

## Referências
- [Multi-tenancy](./multi-tenancy.md)
- [Arquitetura do sistema](../../ARCHITECTURE.md)
- Documentação do pacote: https://tenancyforlaravel.com/
