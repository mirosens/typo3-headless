# API Contract (JSON Schema) – Dokumentation

## Übersicht

Dieses Verzeichnis enthält die formalen JSON-Schema-Definitionen für alle öffentlichen JSON-Endpunkte des FAHN-CORE Headless TYPO3 Systems. Die Schemas dienen als **Single Source of Truth** für die API-Struktur und binden Backend (TYPO3), Frontend (Next.js) und Tests/CI.

## Grundprinzipien

* **Alle öffentlichen JSON-Endpunkte** müssen ein formal definiertes JSON Schema (Draft 2020-12) besitzen
* **Schemas werden versioniert** (z.B. v1, v2, v3) und unter `docs/api/schema/` abgelegt
* **Das Schema ist verbindlich** – Implementierung in TYPO3 und Nutzung im Frontend müssen sich daran orientieren
* **Änderungen am Schema** sind als breaking/non-breaking zu klassifizieren und erfordern ggf. Versionserhöhung
* **CI/CD validiert** jede API-Response gegen das passende Schema (Contract-Tests)

## Verzeichnisstruktur

```
docs/api/schema/
├── v1/
│   ├── page.schema.json          # Schema für /api/page
│   ├── navigation.schema.json    # Schema für /api/navigation
│   └── fahndungen.schema.json    # Schema für /api/fahndungen
└── README.md                      # Diese Datei
```

## Versionierung

### Aktuelle Version: v1

Die aktuelle Version v1 definiert die Grundstruktur für:
- **Page-Responses**: Seiteninhalte mit Content-Blocks
- **Navigation-Responses**: Hauptnavigation, Footer-Nav, Utility-Nav
- **Fahndungen-Responses**: Listen- und Detaildarstellungen von Fahndungen

### Schema-Versionierung bei Änderungen

Bei **breaking Changes** (z.B. Pflichtfeld entfernt, Typ geändert):
1. Neues Verzeichnis `v2/` anlegen
2. Bestehende v1-Schemas **nicht ändern**, nur als deprecated markieren
3. Neue Schemas in `v2/` erstellen
4. Frontend und Backend schrittweise migrieren

Bei **non-breaking Changes** (z.B. neues optionales Feld):
- Schema in der aktuellen Version erweitern
- Frontend kann neue Felder optional nutzen

## Schema-Dateien

### page.schema.json

**Endpunkt**: `/api/page`

**Beschreibung**: Standardisierte Struktur für Seiteninhalte (z.B. Fahndungs-Detailseite, Informationsseiten, statische Inhalte). Grundlage für SSR/ISR in Next.js.

**Pflichtfelder**:
- `id`: Eindeutige Seiten-ID (String)
- `slug`: URL-freundlicher Pfad
- `locale`: Sprachcode (z.B. "de-DE")
- `title`: Titel der Seite
- `meta`: SEO- und OpenGraph-Metadaten
- `content`: Liste von Inhaltselementen (Sections/Blocks)

**Optionale Felder**:
- `accessibility`: Hinweise zu Sprache, Landmarks etc.

**Schema-ID**: `https://fahn-core.mirosens.com/schema/v1/page.json`

### navigation.schema.json

**Endpunkt**: `/api/navigation`

**Beschreibung**: Einheitliche, frontend-freundliche Struktur für Hauptnavigation, Footer-Navigation und Utility-Nav (z.B. Sprache, A11y-Links).

**Pflichtfelder**:
- `menus`: Container für verschiedene Navigationsmenüs

**Menü-Typen**:
- `main`: Hauptnavigation
- `footer`: Footer-Links
- `utility`: Service-/Meta-Navigation

**Struktur**:
- Jedes Menü besteht aus Items mit optionalen Kindern
- v1: max. 2 Ebenen (navItem → navItemChild)

**Schema-ID**: `https://fahn-core.mirosens.com/schema/v1/navigation.json`

### fahndungen.schema.json

**Endpunkt**: `/api/fahndungen` (Liste) und `/api/fahndungen/{id}` (Detail)

**Beschreibung**: Klarer Vertrag für Listen- und Detaildarstellungen von Fahndungen (Such-/Filter-UI, Detailseiten).

**Pflichtfelder**:
- `items`: Liste von Fahndungsfällen
- `pagination`: Paginierungsinformationen (page, perPage, total)

**Optionale Felder**:
- `filters`: Verfügbare Filteroptionen (status, categories, regions)

**Fahndungsfall-Properties**:
- `id`, `caseId`, `title`, `status` (Pflicht)
- `category`, `dateOfCrime`, `location`, `priority`, `teaser` (Optional)
- `images`: Array von Bild-DTOs mit Accessibility-Informationen
- `accessibility`: Accessibility-Metadaten

**Schema-ID**: `https://fahn-core.mirosens.com/schema/v1/fahndungen.json`

## Verwendung

### Backend (TYPO3)

Die TYPO3-API-Implementierung muss sicherstellen, dass:
- Jede Response von `/api/page`, `/api/navigation`, `/api/fahndungen` dem entsprechenden Schema v1 entspricht
- DTOs/Controller so implementiert sind, dass sie die Schema-Struktur erzeugen
- Optional: Dev/Stage-Middleware zur Runtime-Validierung gegen die Schemas

### Frontend (Next.js)

Das Frontend sollte:
- TypeScript-Typen basierend auf den Schemas verwenden (manuell oder generiert)
- Nur Felder nutzen, die im Schema definiert sind
- ContentRenderer robust implementieren (unbekannte Typen sicher behandeln)

### CI/CD

Contract-Tests sollten:
- Die API-Endpunkte gegen ein lokales TYPO3 im DDEV oder Test-Container aufrufen
- Die Responses gegen `docs/api/schema/v1/*.schema.json` validieren
- Den Build bei Verstoß abbrechen

## Validierung

### Lokale Validierung

Die Schemas können mit einem JSON-Schema-Validator geprüft werden:

```bash
# Beispiel mit Node.js (ajv-cli)
npm install -g ajv-cli
ajv validate -s docs/api/schema/v1/page.schema.json -d test-data/page-response.json

# Beispiel mit Python (jsonschema)
pip install jsonschema
python -m jsonschema test-data/page-response.json docs/api/schema/v1/page.schema.json
```

### Schema-Validierung der Schemas selbst

Die Schemas selbst sind JSON-Schema Draft 2020-12 konform und können gegen das Meta-Schema validiert werden.

## Technische Details

### JSON Schema Draft 2020-12

Alle Schemas verwenden **JSON Schema Draft 2020-12** (`$schema: "https://json-schema.org/draft/2020-12/schema"`).

### Schema-Strenge

- **additionalProperties: false** an der Wurzel verhindert unkontrollierte Felder
- Ausnahmen (z.B. `filters` mit dynamischen Keys) sind bewusst markiert und dokumentiert
- Ziel: keine versehentliche Exposition interner Felder (pid, cruser_id, deleted etc.)

### Definitions

Wiederverwendbare Typen werden in `$defs` (Draft 2020-12) definiert und via `$ref` referenziert.

## Erweiterungen (Ausblick)

### TypeScript-Typen generieren

Im Next.js-Projekt kann ein Skript eingerichtet werden, das aus den JSON-Schemas TypeScript-Typen erzeugt:

```bash
# Beispiel mit json-schema-to-typescript
npm install -D json-schema-to-typescript
npx json2ts docs/api/schema/v1/page.schema.json > types/api/page.d.ts
```

### OpenAPI/Swagger

Optional kann ein OpenAPI/Swagger-Dokument für zusätzliche Dokumentation aus den JSON-Schemas generiert werden.

## Definition of Done (DoD)

Phase A.8 ist erfüllt, wenn:

✅ `docs/api/schema/v1/` existiert und folgende Dateien enthält:
- `page.schema.json`
- `navigation.schema.json`
- `fahndungen.schema.json`

✅ Alle drei Schemas:
- sind valide JSON-Schemas (durch jsonschema-Validator geprüft)
- besitzen `$id`, `$schema`, `title`
- haben keine `additionalProperties: true` an der Wurzel (außer explizit gewollt)

✅ TYPO3-API-Implementierung hält sich an diese Schemas:
- Jede Response von `/api/page`, `/api/navigation`, `/api/fahndungen` entspricht Schema v1

✅ CI/CD enthält Contract-Tests:
- Für jede Route wird ein Sample-Response gezogen und gegen das passende Schema validiert
- Build schlägt fehl, wenn Validierung scheitert

✅ Frontend (Next.js) nutzt nur Felder, die im Schema definiert sind

## Kontakt & Support

Bei Fragen zur Schema-Definition oder Vorschlägen für Erweiterungen, bitte ein Issue im Projekt-Repository erstellen.










