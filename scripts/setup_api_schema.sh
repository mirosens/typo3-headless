#!/usr/bin/env bash

# FAHN-CORE – PHASE A.8 – Schema-Automatisierung
# Automatisiertes Anlegen der Verzeichnisstruktur docs/api/schema/v1/
# und Initialisieren der drei JSON-Schema-Dateien (page, navigation, fahndungen)
#
# Features:
# - Idempotent: Mehrfaches Ausführen ohne Fehler
# - Bash Strict Mode: set -euo pipefail
# - JSON-Validierung nach Erstellung
# - Optional: Backup vorhandener Dateien

set -euo pipefail

# Konfiguration
readonly BASE_DIR="docs/api/schema/v1"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
readonly SCHEMA_DIR="$PROJECT_ROOT/$BASE_DIR"
readonly BACKUP_DIR="$PROJECT_ROOT/docs/api/schema/.backup"
readonly BACKUP_ENABLED="${BACKUP_ENABLED:-false}"

# Farben für Logging (optional, funktioniert auch ohne)
readonly COLOR_RESET='\033[0m'
readonly COLOR_INFO='\033[0;32m'
readonly COLOR_WARN='\033[0;33m'
readonly COLOR_ERROR='\033[0;31m'

# Logging-Funktionen
log_info() {
  printf "${COLOR_INFO}[INFO]${COLOR_RESET} %s\n" "$1" >&2
}

log_warn() {
  printf "${COLOR_WARN}[WARN]${COLOR_RESET} %s\n" "$1" >&2
}

log_error() {
  printf "${COLOR_ERROR}[ERROR]${COLOR_RESET} %s\n" "$1" >&2
}

# Prüft, ob ein Befehl verfügbar ist
command_exists() {
  command -v "$1" >/dev/null 2>&1
}

# Validiert eine JSON-Datei
validate_json() {
  local file="$1"
  
  if ! command_exists python3; then
    log_warn "python3 nicht gefunden, überspringe JSON-Validierung für $file"
    return 0
  fi
  
  if python3 -m json.tool "$file" >/dev/null 2>&1; then
    log_info "✓ JSON-Validierung erfolgreich: $file"
    return 0
  else
    log_error "✗ JSON-Validierung fehlgeschlagen: $file"
    python3 -m json.tool "$file" 2>&1 || true
    return 1
  fi
}

# Erstellt ein Backup einer Datei, falls sie existiert
backup_file() {
  local file="$1"
  
  if [[ "$BACKUP_ENABLED" != "true" ]]; then
    return 0
  fi
  
  if [[ -f "$file" ]]; then
    local filename
    filename="$(basename "$file")"
    local timestamp
    timestamp="$(date +%Y%m%d_%H%M%S)"
    local backup_file="$BACKUP_DIR/${filename}.${timestamp}.bak"
    
    mkdir -p "$BACKUP_DIR"
    cp "$file" "$backup_file"
    log_info "Backup erstellt: $backup_file"
  fi
}

# Erstellt das page.schema.json
create_page_schema() {
  local file="$SCHEMA_DIR/page.schema.json"
  
  if [[ -f "$file" ]]; then
    log_warn "Datei existiert bereits: $file"
    backup_file "$file"
  fi
  
  log_info "Erstelle $file"
  
  cat << 'EOF' > "$file"
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://fahn-core.mirosens.com/schema/v1/page.json",
  "title": "PageResponseV1",
  "type": "object",
  "required": ["id", "slug", "locale", "title", "meta", "content"],
  "properties": {
    "id": { "type": "string", "minLength": 1 },
    "slug": { "type": "string" },
    "locale": { "type": "string" },
    "title": { "type": "string" },
    "meta": { "type": "object" },
    "content": { "type": "array" }
  },
  "additionalProperties": false
}
EOF

  validate_json "$file"
}

# Erstellt das navigation.schema.json
create_navigation_schema() {
  local file="$SCHEMA_DIR/navigation.schema.json"
  
  if [[ -f "$file" ]]; then
    log_warn "Datei existiert bereits: $file"
    backup_file "$file"
  fi
  
  log_info "Erstelle $file"
  
  cat << 'EOF' > "$file"
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://fahn-core.mirosens.com/schema/v1/navigation.json",
  "title": "NavigationResponseV1",
  "type": "object",
  "required": ["menus"],
  "properties": {
    "version": { "type": "integer", "minimum": 1 },
    "menus": { "type": "object" }
  },
  "additionalProperties": false
}
EOF

  validate_json "$file"
}

# Erstellt das fahndungen.schema.json
create_fahndungen_schema() {
  local file="$SCHEMA_DIR/fahndungen.schema.json"
  
  if [[ -f "$file" ]]; then
    log_warn "Datei existiert bereits: $file"
    backup_file "$file"
  fi
  
  log_info "Erstelle $file"
  
  cat << 'EOF' > "$file"
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://fahn-core.mirosens.com/schema/v1/fahndungen.json",
  "title": "FahndungenListResponseV1",
  "type": "object",
  "required": ["items", "pagination"],
  "properties": {
    "items": { "type": "array" },
    "pagination": { "type": "object" }
  },
  "additionalProperties": false
}
EOF

  validate_json "$file"
}

# Hauptfunktion
main() {
  log_info "FAHN-CORE – PHASE A.8 – Schema-Automatisierung"
  log_info "=============================================="
  
  # Wechsle ins Projekt-Root-Verzeichnis
  cd "$PROJECT_ROOT" || {
    log_error "Konnte nicht ins Projekt-Root-Verzeichnis wechseln: $PROJECT_ROOT"
    exit 1
  }
  
  log_info "Projekt-Root: $PROJECT_ROOT"
  log_info "Erstelle Verzeichnisstruktur: $BASE_DIR"
  
  # Erstelle Verzeichnisstruktur (idempotent)
  mkdir -p "$SCHEMA_DIR"
  
  # Erstelle Schema-Dateien
  create_page_schema
  create_navigation_schema
  create_fahndungen_schema
  
  log_info "=============================================="
  log_info "Fertig. Generierte Dateien:"
  ls -lh "$SCHEMA_DIR"/*.schema.json 2>/dev/null || {
    log_error "Keine Schema-Dateien gefunden in $SCHEMA_DIR"
    exit 1
  }
  
  log_info ""
  log_info "Hinweis: Dies sind Skeleton-Dateien."
  log_info "Die detaillierten Strukturen werden in PHASE A.8 eingepflegt."
  
  if [[ "$BACKUP_ENABLED" == "true" ]] && [[ -d "$BACKUP_DIR" ]]; then
    log_info ""
    log_info "Backups gespeichert in: $BACKUP_DIR"
  fi
}

# Führe Hauptfunktion aus
main "$@"









