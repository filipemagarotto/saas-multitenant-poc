---
title: "Painel — Telas (Sprint 1)"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-25
ai_friendly: true
tags: [painel, features, screens]
---

# Painel — Telas (Sprint 1)

> Especificação resumida das telas do Painel. Cada tela tem um card no Jira
> (épico **SCRUM-12**) com um **prompt** pronto para a IA construir.

## 1. Login do admin — `SCRUM-13`
Login do Painel por **subdomínio dedicado**, restrito a admins. **Username + senha**,
**sem** recuperação de senha, com **reCAPTCHA/Cloudflare Turnstile** validado no
backend. Pós-login → menu central; rotas internas exigem admin autenticado.

## 2. Menu central (shell) — `SCRUM-14`
Layout autenticado base (header + navegação) com acesso a todas as seções,
destaque do item ativo, logout, guarda de rota admin e responsividade.

## 3. Tenants — `SCRUM-15`
Listagem (busca/filtro/status), criação/provisionamento (nome, slug/subdomínio) e
**detalhe** do tenant com: gestão de licenças, criação de usuário admin do tenant e
**ativar/inativar**. Tenants/licenças são geridos **só pelo Painel**.

## 4. Relatórios e dashboards — `SCRUM-16`
Visão filtrável por tenant a partir do PostgreSQL: painel via **`pg_stat_statements`**
(stats por instância, não por tenant), **nº de registros por tabela por tenant** e
**levantamento** do que mais dá para extrair do Postgres.

## 5. Tickets de suporte — `SCRUM-17`
Gestão de tickets **por tenant** (título, descrição, gravidade), com **comentários**
no chamado, no estilo "case" da Salesforce. Cliente pode abrir o ticket diretamente.

## 6. Visualização de erros (GlitchTip) — `SCRUM-18`
Integração com **GlitchTip** (self-hosted) para visualizar exceptions; lista/detalhe
e, se viável, filtro por tenant (tag `tenant_id`).

## 7. Gerenciamento de rotinas — `SCRUM-19`
Lista de rotinas agendadas **por tenant**; detalhe com **histórico de execuções**
(colunas selecionáveis no log), edição da **periodicidade** e **execução manual**. A
**criação** é via **script** (parâmetros), não pela tela.

## 8. Auditoria — `SCRUM-20`
Listagem **por tenant** lendo a tabela de auditoria: quem criou, quando criou,
última modificação. Para objetos de configuração, coluna **JSON** com versões
anteriores — **tracking das últimas 3 versões**.

## 9. Certificado SSL (Certbot) — `SCRUM-21`
Vigência dos certificados, **tenants habilitados** para SSL, botão de **renovação
manual** via **Certbot** e **log** da execução.

## 10. Licenças e add-ons — `SCRUM-22`
CRUD de **licenças** (por ora só **nome**); modelo **extensível** para regras
futuras (ex.: máx. de usuários ativos) e **produtos add-on**.
