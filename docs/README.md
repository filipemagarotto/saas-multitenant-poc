---
title: README
status: stable
owner: filipe-magarotto
last_updated: 2026-06-21
ai_friendly: true
tags: [overview, onboarding]
---

# SaaS Multitenant POC

> Prova de conceito de um SaaS multi-tenant em Laravel, com isolamento de dados
> por tenant identificado via subdomínio.

## O que é

Este projeto é uma **prova de conceito (POC)** de uma aplicação SaaS multi-tenant
construída em Laravel. O objetivo é validar a arquitetura de **banco único com
isolamento por `tenant_id`**, onde cada cliente (tenant) acessa a aplicação por um
**subdomínio próprio** (ex.: `cliente1.tcsystem.shop`) e enxerga **apenas os seus
próprios dados**.

A entidade de exemplo usada para demonstrar o isolamento é `Pet` — cada tenant tem
sua própria lista de pets, completamente isolada dos demais.

Esta é uma POC de aprendizado, rodando numa VPS Ubuntu, com deploy manual. Itens de
produção (HTTPS, CI/CD, hardening) estão fora do escopo atual — ver
[known-issues](./docs/ai-context/known-issues.md).

## Setup rápido

Pré-requisitos na máquina: PHP 8.4, Composer, MySQL 8, Nginx.

```bash
git clone git@github.com:filipemagarotto/saas-multitenant-poc.git
cd saas-multitenant-poc
composer install
cp .env.example .env
php artisan key:generate
# Ajuste DB_* e TENANT_CENTRAL_DOMAIN no .env, então:
php artisan migrate
php artisan tinker   # criar tenants de exemplo (ver docs/features/multi-tenancy.md)
```

Acesso em desenvolvimento (sem DNS): adicione ao seu `hosts` local
`IP_DA_VPS cliente1.tcsystem.shop` e acesse `http://cliente1.tcsystem.shop/pets`.

## Documentação principal

| Documento | Descrição |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Visão geral do sistema e do multi-tenant |
| [GLOSSARY.md](./GLOSSARY.md) | Termos de domínio |
| [docs/features/multi-tenancy.md](./docs/features/multi-tenancy.md) | Como o multi-tenant funciona |
| [docs/architecture/adr/ADR-001-single-database-multitenancy.md](./docs/architecture/adr/ADR-001-single-database-multitenancy.md) | Decisão da estratégia de tenancy |
| [docs/ai-context/conventions.md](./docs/ai-context/conventions.md) | Convenções de código |
| [docs/ai-context/known-issues.md](./docs/ai-context/known-issues.md) | Limitações conhecidas |
| [docs/ai-context/FULL_CONTEXT.md](./docs/ai-context/FULL_CONTEXT.md) | Contexto agregado para IAs |

## Stack

- **Backend:** PHP 8.4, Laravel 13
- **Banco:** MySQL 8.0 (banco único, isolamento por `tenant_id`)
- **Web:** Nginx 1.24 + PHP-FPM 8.4
- **SO:** Ubuntu 24.04 LTS (VPS)
- **Versionamento:** Git + GitHub (deploy key SSH)

## Ambiente atual (POC)

| Item | Valor |
|------|-------|
| Caminho do projeto | `/var/www/saas-poc` |
| Domínio central | `tcsystem.shop` |
| Banco / usuário | `saas_poc` / `saas_poc_user@localhost` |
| Tenants de exemplo | `cliente1` (Clinica Pet Feliz), `cliente2` (Aumiga Veterinaria) |
