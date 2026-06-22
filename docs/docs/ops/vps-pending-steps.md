---
title: Passos Pendentes de Execução na VPS
status: living
owner: filipe-magarotto
last_updated: 2026-06-22
ai_friendly: true
tags: [ops, deploy, runbook, ai-context]
---

# Passos Pendentes de Execução na VPS

> **Para IAs:** este arquivo é um runbook vivo. O ambiente de desenvolvimento
> (Windows) **não tem PHP/Composer**, então comandos como `php artisan migrate`,
> `db:seed` e `php artisan test` **não rodam lá** — eles ficam registrados aqui
> como entradas **⬜ PENDENTE** e devem ser executados na VPS
> (`/var/www/saas-poc`, Ubuntu, PHP 8.4).

## Como usar (IA executora na VPS)

1. Leia as entradas de baixo para cima e ache as marcadas **⬜ PENDENTE**.
2. Faça `git pull` até o commit indicado em **Commit** (ou mais recente).
3. Execute os **Passos** na ordem; marque cada `- [ ]` como `- [x]` conforme concluir.
4. Rode a **Verificação**; se passar, mude o **Status** da entrada para **✅ FEITO**
   e preencha **Executado em**.
5. Faça commit desta atualização do `.md` (`docs: marca <entrada> como feito na VPS`).
6. Se um passo falhar, **pare**, anote o erro em **Notas** e mantenha **⬜ PENDENTE**.

## Legenda

- ⬜ **PENDENTE** — ainda não executado na VPS.
- ✅ **FEITO** — executado e verificado na VPS.
- ⚠️ **BLOQUEADO** — tentado, falhou; ver Notas.

---

## ⬜ PENDENTE — Login isolado por tenant

- **Commit:** `(uncommitted — preencher com o hash ao commitar)`
- **Branch:** `main`
- **Status:** ⬜ PENDENTE
- **Registrado em:** 2026-06-22
- **Executado em:** —

### Contexto

Adiciona login por tenant: `users.tenant_id`, trait `BelongsToTenant` no `User`,
rotas `/login` e `/logout`, e `/` + `/pets` atrás do middleware `auth`. Ver
[feature](../features/authentication.md).

### Passos

- [ ] `cd /var/www/saas-poc`
- [ ] `git pull`
- [ ] `php artisan migrate` — cria `users.tenant_id` e o unique `(tenant_id, email)`
- [ ] `php artisan db:seed` — cria cliente1/cliente2 + admins + pets (idempotente)
- [ ] `php artisan test --filter=TenantAuthTest` — 5 testes de isolamento devem passar
- [ ] `systemctl reload php8.4-fpm` — limpa o OPcache

### Verificação

- [ ] `cliente1.tcsystem.shop/pets` sem login redireciona para `/login`.
- [ ] Login com `admin@cliente1.test` / `password` mostra só os pets do cliente1.
- [ ] As mesmas credenciais em `cliente2.tcsystem.shop/login` retornam
      "Credenciais inválidas para este tenant".

### Notas

- Se a migration falhar no `dropUnique(['email'])`/`unique(['tenant_id','email'])`,
  cheque se há e-mails duplicados na tabela `users` antes de prosseguir.
- `SESSION_DOMAIN` deve continuar vazio/`null` no `.env` da VPS (cookie host-only).

---

## Template para novas entradas

> Copie o bloco abaixo para o topo da lista (acima desta seção) ao registrar um
> novo conjunto de passos pendentes.

```markdown
## ⬜ PENDENTE — <título curto>

- **Commit:** `<hash>`
- **Branch:** `<branch>`
- **Status:** ⬜ PENDENTE
- **Registrado em:** YYYY-MM-DD
- **Executado em:** —

### Contexto
<1-2 frases do que muda e por quê.>

### Passos
- [ ] <comando 1>
- [ ] <comando 2>

### Verificação
- [ ] <como confirmar que funcionou>

### Notas
- <riscos, dependências, rollback>
```
