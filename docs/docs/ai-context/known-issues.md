---
title: Limitações e Problemas Conhecidos
status: stable
owner: filipe-magarotto
last_updated: 2026-06-21
ai_friendly: true
tags: [known-issues, tech-debt]
---

# Limitações e Problemas Conhecidos

> Para IAs: ao sugerir mudanças, verifique se não conflitam com as limitações abaixo.
> Esta é uma POC — vários itens de produção são omissões propositais desta fase.

## Dívidas técnicas ativas

| ID | Descrição | Impacto | Workaround / nota |
|----|-----------|---------|-------------------|
| TD-001 | `pets.tenant_id` e `users.tenant_id` são `nullable` | Permite registro sem tenant no schema | Trait/seed preenchem sempre; tornar `NOT NULL` em migration futura |
| TD-002 | Isolamento apenas lógico (coluna), não físico | Bug de query sem o scope vazaria dados entre tenants | Cobrir com testes; sempre usar `BelongsToTenant` |
| TD-003 | Senha do root do MySQL gravada em `/root/mysql_root_password.txt` | Segredo em texto na VPS | Salvar em gerenciador e apagar o arquivo |
| TD-004 | Cobertura de testes ainda parcial | Regressões podem passar despercebidas | Há `TenantAuthTest` (login/isolamento); ampliar para os demais models |

## Fora de escopo nesta fase (propositais)

- **HTTPS / TLS:** acesso é HTTP puro. Não usar dados sensíveis reais.
- **CI/CD:** deploy é manual na VPS.
- **Cadastro self-service de tenants:** tenants são criados via `tinker`/seed.
- **Cadastro self-service de usuários:** usuários são criados via `tinker`/seed,
  igual aos tenants. Sem recuperação de senha nem verificação de e-mail.
- **Painel administrativo / rotas centrais ricas:** o domínio central só tem a
  landing padrão.
- **Migrations automáticas por tenant:** não se aplica (banco único).

## Infraestrutura (POC)

- **Sem swap** configurado na VPS (7.8 GB RAM cobrem o uso atual).
- **Firewall `ufw` inativo** — a proteção de porta depende do firewall do provedor.
- **DNS de `tcsystem.shop` em propagação** — testar via arquivo `hosts` enquanto não
  propaga; requer registros A `tcsystem.shop` e wildcard `*` apontando para o IP.

## Comportamentos não-óbvios

- O parâmetro de rota `{tenant}` é removido (`forgetParameter`) pelo middleware, então
  as closures/controllers das rotas de tenant **não** recebem o slug como argumento.
- Após mudar código, o **OPcache** do PHP-FPM pode servir a versão antiga por alguns
  segundos; um `systemctl reload php8.4-fpm` força a releitura.
- Acessar pelo IP direto (sem Host de domínio) não casa as rotas de tenant — é
  esperado, o roteamento é por domínio.
- O middleware `tenant` precisa rodar **antes** do `auth`: a resolução do usuário
  logado depende do tenant atual já estar definido (escopo do `User`).
- O cookie de sessão é host-only (`SESSION_DOMAIN` vazio). Não preencher com
  `.tcsystem.shop`, senão a sessão passaria a valer entre subdomínios.
