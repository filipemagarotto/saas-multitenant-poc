---
title: "Feature: Autenticação por Tenant"
status: stable
owner: filipe-magarotto
last_updated: 2026-06-21
ai_friendly: true
tags: [feature, auth, multi-tenancy, security]
---

# Feature: Autenticação por Tenant

## Objetivo

Cada tenant tem seus próprios usuários e seu próprio login. Um usuário do
`cliente1` só consegue se autenticar em `cliente1.tcsystem.shop` e só enxerga os
dados (pets) do `cliente1` — nunca os de outro tenant.

## Usuários-alvo

- **Quem:** usuários finais de cada cliente (tenant) do SaaS.
- **Job to be done:** "Quando acesso o subdomínio da minha empresa, quero entrar
  com meu login e senha, para ver apenas os dados da minha empresa."

## Escopo

### Inclui
- Login (`GET/POST /login`) e logout (`POST /logout`) no subdomínio do tenant.
- Usuários pertencem a um tenant (`users.tenant_id`).
- Rotas de dados (`/` e `/pets`) protegidas: exigem usuário autenticado.
- Isolamento do login pelo mesmo mecanismo dos dados (`BelongsToTenant`).

### Não inclui (out of scope)
- Cadastro self-service de usuários (criados via `seed`/`tinker`, como os tenants).
- Recuperação de senha / verificação de e-mail.
- Papéis/permissões (todo usuário do tenant tem o mesmo acesso).
- HTTPS (ver [known-issues](../ai-context/known-issues.md)).

## Comportamento esperado

1. Visitante acessa `cliente1.tcsystem.shop/pets`.
2. Middleware `tenant` identifica o tenant pelo subdomínio (`cliente1`).
3. Middleware `auth` vê que não há usuário logado → redireciona para `/login`
   (caminho relativo, permanece no subdomínio do `cliente1`).
4. Usuário envia e-mail + senha. `Auth::attempt` busca o usuário **já filtrado
   pelo tenant atual** (trait `BelongsToTenant` no model `User`).
5. Credenciais válidas **deste tenant** → sessão criada, redireciona para `/pets`.
6. `/pets` lista apenas os pets do `cliente1` (global scope do `BelongsToTenant`
   no model `Pet`).
7. `POST /logout` encerra a sessão e volta para `/login`.

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Visitante acessa `/pets` sem login | 302 → `/login` do próprio subdomínio |
| Credenciais do `cliente1` usadas em `cliente2.../login` | Falha: "Credenciais inválidas para este tenant" (o usuário não existe no escopo do `cliente2`) |
| Sessão do `cliente1` reaproveitada em `cliente2` | Tratado como visitante: a busca do usuário logado é escopada por tenant e não resolve o usuário do `cliente1` → 302 `/login` |
| E-mail repetido em tenants diferentes | Permitido: `email` é único **por tenant** (`unique(tenant_id, email)`) |
| Campos vazios | 302 de volta com erros de validação |

## Modelo de dados

```sql
-- Cada usuário pertence a um tenant; e-mail único por tenant.
ALTER TABLE users ADD COLUMN tenant_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE users ADD CONSTRAINT users_tenant_id_foreign
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;
ALTER TABLE users DROP INDEX users_email_unique;
ALTER TABLE users ADD UNIQUE users_tenant_id_email_unique (tenant_id, email);
```

> `tenant_id` é `nullable` (mesmo padrão de `pets.tenant_id`, ver TD-001). Em
> produção deveria ser `NOT NULL`.

## API / Interface

```
GET  /login     -> formulário de login (Blade: resources/views/auth/login.blade.php)
POST /login     -> { email, password, remember? }  (CSRF obrigatório)
                   200/302 sucesso -> redirect /pets
                   302 falha       -> volta com errors.email
POST /logout    -> encerra sessão -> redirect /login   (CSRF obrigatório)
```

Todas as rotas vivem no grupo de subdomínio `{tenant}.tcsystem.shop`.

## Decisões técnicas

- **Login isolado pelo mesmo mecanismo dos dados.** Em vez de comparar
  `tenant_id` manualmente no controller, o model `User` usa o trait
  `BelongsToTenant`. Assim `Auth::attempt` (busca por credenciais) e a
  recuperação do usuário logado a cada requisição já vêm filtradas pelo tenant
  atual. Menos código e sem caminho para esquecer a verificação.
- **Defesa em profundidade (cookies host-only).** `SESSION_DOMAIN` fica em
  branco/`null`, então o cookie de sessão é específico do host
  (`cliente1.tcsystem.shop`) e **não** é enviado a outros subdomínios. Mesmo que
  fosse, o item anterior já impediria o vazamento. **Não** definir
  `SESSION_DOMAIN=.tcsystem.shop`.
- **Redirect por caminho relativo.** `redirectGuestsTo('/login')` em
  `bootstrap/app.php` usa um caminho (não `route('login')`), evitando ter de
  resolver o parâmetro `{tenant}` da rota de domínio e mantendo o subdomínio
  atual.
- **Ordem dos middlewares:** `tenant` antes de `auth`. O tenant precisa estar
  definido para que o escopo do `User` funcione na resolução do usuário logado.

## Como testar

```bash
php artisan migrate         # cria users.tenant_id
php artisan db:seed         # cria cliente1/cliente2 + admins + pets

# Usuários de exemplo (senha: "password"):
#   admin@cliente1.test  -> cliente1.tcsystem.shop
#   admin@cliente2.test  -> cliente2.tcsystem.shop

php artisan test --filter=TenantAuthTest
```

Teste manual: logar em `cliente1.tcsystem.shop/login` com `admin@cliente1.test`
deve mostrar os pets do cliente1; as credenciais não funcionam em
`cliente2.tcsystem.shop/login`.
