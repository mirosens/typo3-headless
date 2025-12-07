#!/bin/bash
# JSON Schema Validierungsskript für CI/CD
# Validiert alle JSON Schema-Dateien auf korrekte JSON-Syntax

set -e

SCHEMA_DIR="$(dirname "$0")/v1"
ERRORS=0

echo "🔍 Validiere JSON Schema-Dateien..."

for schema_file in "$SCHEMA_DIR"/*.schema.json; do
    if [ -f "$schema_file" ]; then
        filename=$(basename "$schema_file")
        if python3 -m json.tool "$schema_file" > /dev/null 2>&1; then
            echo "✓ $filename ist valides JSON"
        else
            echo "✗ $filename enthält JSON-Syntaxfehler!"
            ERRORS=$((ERRORS + 1))
        fi
    fi
done

if [ $ERRORS -eq 0 ]; then
    echo ""
    echo "✅ Alle JSON Schema-Dateien sind syntaktisch korrekt."
    exit 0
else
    echo ""
    echo "❌ $ERRORS Schema-Datei(en) enthalten Fehler."
    exit 1
fi










