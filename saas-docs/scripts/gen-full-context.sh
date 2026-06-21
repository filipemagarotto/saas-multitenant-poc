#!/usr/bin/env bash
# Concatena os documentos principais em um único arquivo para contexto de IAs
# Uso: bash scripts/gen-full-context.sh

set -e

OUTPUT="docs/ai-context/FULL_CONTEXT.md"
DATE=$(date +%Y-%m-%d)

echo "Gerando $OUTPUT..."

cat > "$OUTPUT" << HEADER
---
title: Contexto Completo para IAs
status: stable
owner: engineering-lead
last_updated: $DATE
ai_friendly: true
tags: [ai-context, generated]
---

# Contexto Completo — $(grep '^# ' README.md | head -1 | sed 's/# //')

> Gerado automaticamente em $DATE por scripts/gen-full-context.sh
> Não edite manualmente.

HEADER

for doc in \
  "docs/context/product-vision.md" \
  "GLOSSARY.md" \
  "ARCHITECTURE.md" \
  "docs/ai-context/tech-stack.md" \
  "docs/ai-context/conventions.md" \
  "docs/ai-context/known-issues.md" \
  "docs/architecture/data-model.md"
do
  if [ -f "$doc" ]; then
    echo "" >> "$OUTPUT"
    echo "---" >> "$OUTPUT"
    echo "" >> "$OUTPUT"
    # Remove o frontmatter YAML antes de concatenar
    sed '/^---$/,/^---$/d' "$doc" >> "$OUTPUT"
  fi
done

echo "✅ $OUTPUT gerado com $(wc -l < $OUTPUT) linhas."
