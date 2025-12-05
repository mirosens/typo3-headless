# Testing Setup - Schnellstart-Anleitung

## Voraussetzungen

Dieses Projekt nutzt **DDEV** für die Entwicklungsumgebung. Composer ist über DDEV verfügbar.

## Installation der Test-Dependencies

### 1. Root-Dependencies installieren

```bash
# Im Root-Verzeichnis
ddev composer install
```

### 2. Package-Dependencies installieren

Die Test-Dependencies müssen in jedem Package separat installiert werden:

```bash
# fahn_core Extension
cd packages/fahn_core
ddev composer install

# fahn_core_fahndung Extension
cd packages/fahn_core_fahndung
ddev composer install
```

## Tests ausführen

### Über Root-Composer (empfohlen)

```bash
# Alle Tests für alle Packages
ddev composer test

# Nur Unit Tests
ddev composer test:unit

# Nur Functional Tests
ddev composer test:functional

# Alle QA-Checks
ddev composer qa
```

### Direkt in einem Package

```bash
# In packages/fahn_core oder packages/fahn_core_fahndung
ddev composer test
ddev composer test:unit
ddev composer test:functional
ddev composer qa
```

## Einzelne Tools

### PHPStan (Statische Analyse)
```bash
cd packages/fahn_core
ddev composer phpstan
```

### PHP CS Fixer (Code-Stil)
```bash
cd packages/fahn_core
ddev composer cs:check    # Prüfen
ddev composer cs:fix     # Automatisch fixen
```

### Rector (Deprecations)
```bash
cd packages/fahn_core
ddev composer rector        # Dry-run
ddev composer rector:fix    # Automatisch refaktorisieren
```

## Troubleshooting

### Composer nicht gefunden

Wenn `composer` nicht direkt verfügbar ist, nutze immer `ddev composer`:

```bash
# ❌ Falsch
composer install

# ✅ Richtig
ddev composer install
```

### Dependencies nicht installiert

Falls die Test-Dependencies nicht installiert werden:

1. Prüfe, ob `composer.json` in den Packages korrekt ist
2. Führe `ddev composer update` im Package-Verzeichnis aus
3. Prüfe die Fehlermeldungen in der Ausgabe

### Tests schlagen fehl

1. Stelle sicher, dass alle Dependencies installiert sind
2. Prüfe, ob die PHP-Version korrekt ist (PHP 8.2+)
3. Prüfe die `phpunit.xml` Konfiguration
4. Für Functional Tests: Stelle sicher, dass die Datenbank erreichbar ist

## CI/CD

Die Tests werden automatisch in GitHub Actions ausgeführt (`.github/workflows/qa.yml`).








