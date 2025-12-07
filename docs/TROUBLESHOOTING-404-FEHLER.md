# Troubleshooting: 404-Fehler bei API-Endpunkten

## Problem
API-Endpunkte geben 404-Fehler zurück, obwohl die TypoScript-Konfiguration implementiert wurde.

## Mögliche Ursachen und Lösungen

### 1. Extension nicht aktiviert

**Symptom**: Template Analyzer zeigt keine `tx_fahncore`-Objekte

**Lösung**:
1. Backend: `Admin Tools > Extensions`
2. Prüfen: `fahn_core` und `fahn_core_fahndung` aktiviert?
3. Falls nicht: Aktivieren
4. Cache leeren: `ddev exec vendor/bin/typo3 cache:flush`

### 2. TypoScript nicht im Template eingebunden

**Symptom**: Template Analyzer zeigt keine Plugin-Konfigurationen

**Lösung**:
1. Backend: `Web > Template`
2. Root-Seite auswählen (UID 1)
3. `Info/Modify` klicken
4. Tab "Includes":
   - Constants: `@import 'EXT:fahn_core/Configuration/TypoScript/constants.typoscript'`
   - Setup: `@import 'EXT:fahn_core/Configuration/TypoScript/setup.typoscript'`
5. ODER: Statische Datei einbinden
   - Tab "Includes"
   - "Include static (from extensions)"
   - "FAHN CORE API Configuration" aktivieren
6. Speichern
7. Cache leeren

### 3. TypoScript-Bedingung funktioniert nicht

**Symptom**: TypoScript wird geladen, aber Page-Konfiguration nicht aktiv

**Lösung**: Manuell testen ohne Bedingung

**Temporäre Lösung** (nur für Tests):
Die Page-Konfiguration sollte immer aktiv sein. Prüfen Sie in `setup.typoscript`, ob die Bedingung korrekt ist.

### 4. cHash-Validierung blockiert Requests

**Symptom**: 404 mit "cHash empty" oder "Request parameters could not be validated"

**Lösung A**: Extension Utility konfigurieren (empfohlen)

In `ext_localconf.php` sollte die Plugin-Registrierung `features.skipDefaultArguments` enthalten:

```php
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'FahnCore',
    'Login',
    [\Fahn\Core\Controller\LoginController::class => 'login, session, logout'],
    [\Fahn\Core\Controller\LoginController::class => 'login, session, logout'],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN,
    '',
    '',
    [
        'features' => [
            'skipDefaultArguments' => 1,
        ],
    ]
);
```

**Lösung B**: cHash-Validierung temporär deaktivieren (nur für Entwicklung!)

In `config/system/settings.php`:

```php
'FE' => [
    'cacheHash' => [
        'enforceValidation' => false, // ⚠️ NUR FÜR ENTWICKLUNG!
    ],
],
```

### 5. Template Analyzer: Keine Plugin-Objekte sichtbar

**Diagnose-Schritte**:

1. **Template Analyzer öffnen**: `Web > Template > Template Analyzer`
2. **Root-Seite auswählen**
3. **"Active TypoScript" klicken**
4. **Suchen nach**: `tx_fahncore`

**Erwartete Ergebnisse**:
- `plugin.tx_fahncore_login` sollte vorhanden sein
- `plugin.tx_fahncorefahndung_api` sollte vorhanden sein
- `lib.fahn_core_api_router` sollte vorhanden sein

**Falls 0 Treffer**:
- TypoScript wird nicht geladen
- Siehe Lösung 2

### 6. Quick-Fix: Direkter Test ohne cHash

**Test-URL mit noCache-Parameter**:

```bash
# Fügt &no_cache=1 hinzu, um Cache-Validierung zu umgehen
curl "https://fahn-core-typo3.ddev.site/?tx_fahncore_login%5Baction%5D=session&no_cache=1"
```

**Falls das funktioniert**: Problem ist cHash-Validierung → Siehe Lösung 4

## Systematische Diagnose

### Schritt 1: Extension-Status prüfen
```bash
# Im DDEV-Container
ddev ssh
cd /var/www/html
./vendor/bin/typo3 extension:list | grep fahn
```

**Erwartet**: Beide Extensions sollten als "active" gelistet sein

### Schritt 2: TypoScript-Dateien prüfen
```bash
# Prüfen ob Dateien existieren
ls -la packages/fahn_core/Configuration/TypoScript/
```

**Erwartet**: 
- `constants.typoscript` vorhanden
- `setup.typoscript` vorhanden

### Schritt 3: Template-Datenbank prüfen
```bash
# Prüfen ob Template TypoScript-Imports hat
ddev mysql -e "SELECT uid, title, constants, setup FROM sys_template WHERE pid = 1 LIMIT 1;"
```

**Erwartet**: Constants und Setup sollten die @import-Statements enthalten

### Schritt 4: Cache-Status prüfen
```bash
# Cache-Verzeichnis prüfen
ls -la var/cache/code/typo3/
```

**Falls viele alte Dateien**: Cache komplett leeren

## Empfohlene Reihenfolge

1. ✅ Extension-Status prüfen (Backend)
2. ✅ TypoScript-Imports prüfen (Backend)
3. ✅ Template Analyzer prüfen (Backend)
4. ✅ Cache leeren (CLI)
5. ✅ API-Endpunkt testen (CLI)
6. ✅ Falls weiterhin 404: cHash-Validierung prüfen

## Kontakt

Bei anhaltenden Problemen:
1. Template Analyzer Screenshot erstellen
2. Extension Manager Screenshot erstellen
3. Test-Script Output speichern: `./scripts/test_api_endpoints.sh > test_output.txt`


