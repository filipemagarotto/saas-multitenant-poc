---
title: "Feature: [Nome]"
status: draft
owner: [squad]
last_updated: YYYY-MM-DD
ai_friendly: true
tags: [feature, spec]
---

# Feature: [Nome]

## Objetivo

[1-2 frases: o que essa feature faz e por que estamos construindo.]

## Usuários-alvo

- **Quem:** [persona ou segmento de usuário]
- **Job to be done:** "Quando [situação], quero [motivação], para [resultado esperado]"

## Escopo

### Inclui
- ...

### Não inclui (out of scope)
- ...

## Comportamento esperado

[Descreva o fluxo principal como uma sequência numerada. Seja concreto.]

1. Usuário acessa [tela/endpoint]
2. Sistema verifica [condição]
3. ...

## Casos de borda

| Cenário | Comportamento esperado |
|---------|----------------------|
| Usuário sem permissão | Retorna 403 com mensagem X |
| Input vazio | Retorna 400, campos obrigatórios destacados |
| Timeout de terceiro | Mostra erro amigável, retry disponível |

## Modelo de dados

```sql
-- Tabelas novas ou alteradas
ALTER TABLE users ADD COLUMN ...;

CREATE TABLE nova_tabela (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

## API / Interface

```
POST /api/v1/[recurso]
Authorization: Bearer {token}

Body:
{
  "campo": "valor"
}

Response 201:
{
  "id": "uuid",
  "campo": "valor",
  "created_at": "2025-06-01T00:00:00Z"
}
```

## Métricas de sucesso

- [ ] [Métrica mensurável com baseline e meta — ex: "Taxa de adoção > 30% em 30 dias"]

## Decisões técnicas

[Decisões relevantes tomadas durante o design. Se merece um ADR próprio, criar em docs/architecture/adr/.]
