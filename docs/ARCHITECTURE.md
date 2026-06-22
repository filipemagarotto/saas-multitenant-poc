---
title: Arquitetura do Sistema
status: stable
owner: filipe-magarotto
last_updated: 2026-06-21
ai_friendly: true
tags: [architecture, system-design, multi-tenancy]
---

# Arquitetura do Sistema

## Visão geral

Aplicação Laravel monolítica servida por Nginx + PHP-FPM, com persistência em um
**único banco MySQL**. O multi-tenancy é feito por **discriminador de coluna**
(`tenant_id`): todos os tenants compartilham as mesmas tabelas, e o isolamento é
garantido por um *global scope* do Eloquent aplicado automaticamente. O tenant é
identificado pelo **subdomínio** da requisição.

## Diagrama de componentes

```
   Navegador
 cliente1.tcsystem.shop
        │  HTTP (porta 80)
        ▼
┌────────────────┐     FastCGI      ┌────────────────┐
│     Nginx      │─────────────────▶│   PHP-FPM 8.4  │
│  server_name   │  unix socket     │   (Laravel 13) │
│ *.tcsystem.shop│                  └───────┬────────┘
└────────────────┘                          │
                                            │  PDO (127.0.0.1:3306)
                                            ▼
                                   ┌──────────────────┐
                                   │     MySQL 8.0     │
                                   │  banco: saas_poc  │
                                   │  (todos tenants)  │
                                   └──────────────────┘
```

## Componentes principais

### Nginx (web server)
- **Versão:** 1.24
- **Responsabilidade:** Receber HTTP na porta 80, servir a pasta `public/` do
  Laravel e repassar `.php` ao PHP-FPM via socket Unix.
- **server_name:** `tcsystem.shop *.tcsystem.shop` (wildcard cobre os subdomínios
  de todos os tenants sem reconfiguração por cliente).
- **Config:** `/etc/nginx/sites-available/saas-poc`.

### PHP-FPM + Laravel (aplicação)
- **Versões:** PHP 8.4, Laravel 13.
- **Responsabilidade:** Toda a lógica de aplicação, roteamento, identificação de
  tenant e acesso a dados.
- **Socket:** `/run/php/php8.4-fpm.sock` (usuário `www-data`).

### MySQL (banco de dados)
- **Versão:** 8.0
- **Estratégia:** Banco único `saas_poc`. Tabelas compartilhadas com coluna
  `tenant_id`. Escuta apenas em `127.0.0.1` (não exposto à internet).
- **Usuário da aplicação:** `saas_poc_user@localhost` com privilégios restritos ao
  banco `saas_poc` (a aplicação NÃO usa o root do banco).

## Multi-tenancy — como o isolamento funciona

O isolamento é montado em 4 peças (ver [feature](./docs/features/multi-tenancy.md)):

1. **`Tenancy` (singleton)** — guarda o "tenant atual" durante a requisição.
2. **`IdentifyTenant` (middleware)** — lê o subdomínio, encontra o tenant pelo
   `slug` e o define como atual (ou retorna 404).
3. **`BelongsToTenant` (trait)** — aplicado em models como `Pet` e `User`.
   Adiciona um *global scope* que filtra toda consulta pelo `tenant_id` atual e
   preenche `tenant_id` automaticamente ao criar registros.
4. **Grupos de rota por domínio** — rotas centrais em `tcsystem.shop`; rotas de
   tenant em `{tenant}.tcsystem.shop` (protegidas pelo middleware).
5. **Autenticação por tenant** — como `User` também usa `BelongsToTenant`, o
   login (`Auth::attempt`) e a resolução do usuário logado já vêm filtrados pelo
   tenant atual: um usuário do `cliente1` não autentica no `cliente2`. As rotas
   de dados ficam atrás do middleware `auth`. Ver
   [feature](./docs/features/authentication.md).

## Fluxo de uma requisição de tenant

1. Navegador acessa `cliente1.tcsystem.shop/pets`.
2. Nginx (wildcard) serve a requisição e repassa ao PHP-FPM.
3. Laravel casa a rota `{tenant}.tcsystem.shop/pets` e aplica o middleware `tenant`.
4. `IdentifyTenant` extrai `cliente1`, busca o `Tenant` com `slug=cliente1` e o
   registra no `Tenancy`.
5. A rota chama `Pet::all()`. O *global scope* do `BelongsToTenant` injeta
   `where tenant_id = <id do cliente1>` automaticamente.
6. Retornam apenas os pets do cliente1.

## Decisões de arquitetura

Ver [docs/architecture/adr/](./docs/architecture/adr/) para os ADRs. Decisão
principal: [ADR-001 — Banco único multi-tenant](./docs/architecture/adr/ADR-001-single-database-multitenancy.md).

## O que NÃO fazer

- ❌ Não consultar models com dados de tenant **sem** o trait `BelongsToTenant` —
  isso fura o isolamento. Todo model com dados de cliente deve usar o trait.
- ❌ Não usar o usuário **root** do MySQL na aplicação — use `saas_poc_user`.
- ❌ Não avaliar o tenant atual no `boot` do model (apenas em tempo de consulta) —
  processos PHP-FPM são reaproveitados entre requisições e isso vazaria dados.
- ❌ Não commitar `.env` nem segredos — o `.env` está no `.gitignore`.
- ❌ Não criar server block por cliente no Nginx — o wildcard já cobre todos.
- ❌ Não definir `SESSION_DOMAIN=.tcsystem.shop` — isso compartilharia o cookie de
  sessão entre subdomínios. O cookie deve ficar host-only (`SESSION_DOMAIN` vazio)
  para que a sessão de um tenant não trafegue para outro.

## Limitações conhecidas

- Sem HTTPS ainda (acesso por HTTP). Ver [known-issues](./docs/ai-context/known-issues.md).
- Isolamento é **lógico** (coluna), não físico (banco por tenant) — um bug de query
  sem o scope poderia vazar dados entre tenants.
- `pets.tenant_id` é `nullable` no schema (legado da fase pré-tenant).
