#!/usr/bin/env bash
# Lista documentos com last_updated mais antigo que 90 dias
# Uso: bash scripts/check-stale-docs.sh

THRESHOLD=90
TODAY=$(date +%s)
STALE=()

while IFS= read -r file; do
  date_str=$(grep 'last_updated:' "$file" | head -1 | awk '{print $2}')
  if [ -n "$date_str" ]; then
    file_date=$(date -d "$date_str" +%s 2>/dev/null || date -j -f "%Y-%m-%d" "$date_str" +%s)
    diff_days=$(( (TODAY - file_date) / 86400 ))
    if [ "$diff_days" -gt "$THRESHOLD" ]; then
      STALE+=("$file ($diff_days dias)")
    fi
  fi
done < <(find docs -name "*.md" -not -path "*/ai-context/FULL_CONTEXT.md")

if [ ${#STALE[@]} -eq 0 ]; then
  echo "✅ Nenhum documento desatualizado encontrado."
else
  echo "⚠️  Documentos com mais de $THRESHOLD dias sem atualização:"
  for f in "${STALE[@]}"; do
    echo "  - $f"
  done
  exit 1
fi
