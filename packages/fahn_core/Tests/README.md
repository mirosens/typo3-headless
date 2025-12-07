# A.9: Testing & Quality Assurance

Dieses Verzeichnis enthält die vollständige Test-Infrastruktur für die FAHN-CORE Extension gemäß Phase A.9.

## Struktur

```
Tests/
├── Unit/              # Unit Tests (isoliert, ohne Datenbank)
│   ├── Middleware/
│   ├── Domain/
│   └── ...
├── Functional/        # Functional Tests (mit Datenbank, interne Requests)
│   ├── Api/           # API-Endpunkt Tests
│   └── Fixtures/       # Testdaten (CSV, XML)
└── README.md
```

## Test-Philosophie: Testing Trophy

Wir folgen dem "Testing Trophy"-Modell:
- **Viele Integration/Functional Tests** (höchste Priorität)
- **Wenige Unit Tests** (für isolierte Logik)
- **Minimale E2E Tests** (nur kritische User-Flows)

## Ausführung

### Alle Tests
```bash
composer test
```

### Nur Unit Tests
```bash
composer test:unit
```

### Nur Functional Tests
```bash
composer test:functional
```

### Mit Coverage
```bash
composer test:coverage
```

## A.9.4: Functional Tests mit internen Requests

Die Functional Tests nutzen `executeFrontendRequest()` statt externer HTTP-Requests:

- ✅ Schneller (kein HTTP-Overhead)
- ✅ Stabiler (keine Netzwerk-Abhängigkeiten)
- ✅ Isoliert (Transaction Rollbacks)

### Beispiel

```php
$uri = new Uri('https://example.com/?id=1&type=834');
$request = new ServerRequest($uri, 'GET');
$response = $this->executeFrontendRequest($request);
```

## JSON Schema Validierung

Die API-Tests validieren die JSON-Ausgabe gegen JSON Schemas aus `docs/api/schema/v1/`:

- `page.schema.json` - für Page API (typeNum 834)
- `navigation.schema.json` - für Navigation API (typeNum 835)
- `fahndungen.schema.json` - für Fahndungen API (typeNum 836, 837)

## Statische Analyse

### PHPStan (Level 8)
```bash
composer phpstan
```

### PHP CS Fixer
```bash
composer cs:check  # Prüfen
composer cs:fix    # Automatisch fixen
```

### Rector
```bash
composer rector        # Dry-run
composer rector:fix    # Automatisch refaktorisieren
```

## CI/CD

Die Tests werden automatisch in GitHub Actions ausgeführt:
- `.github/workflows/qa.yml`

## Best Practices

1. **Isolation**: Jeder Test muss unabhängig laufen
2. **Fixtures**: Nutze CSV/XML für reproduzierbare Testdaten
3. **Schema-First**: Validiere immer gegen JSON Schemas
4. **Attribute**: Nutze PHP 8 Attributes (`#[Test]`) statt Annotationen










