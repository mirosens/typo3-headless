# Backend-Setup Anleitung: TypoScript-Konfiguration aktivieren

## Problem: 404-Fehler bei API-Endpunkten

Wenn Sie den Fehler "Backend-Endpunkt nicht gefunden (404)" erhalten, bedeutet dies, dass die TypoScript-Konfiguration nicht geladen wurde.

## Lösung: TypoScript im TYPO3-Backend aktivieren

### Schritt 1: TYPO3-Backend öffnen

1. Öffnen Sie das TYPO3-Backend: `https://fahn-core-typo3.ddev.site/typo3`
2. Melden Sie sich mit Admin-Credentials an

### Schritt 2: Root-Template erstellen/bearbeiten

1. Navigieren Sie zu **Web > Template**
2. Wählen Sie die Root-Seite (meist "Home" oder die oberste Seite)
3. Klicken Sie auf **"Info/Modify"** oder **"Edit the whole template record"**

### Schritt 3: TypoScript einbinden

Im Tab **"Includes"** (oder **"Setup"**):

1. **Constants**: Fügen Sie hinzu:
   ```
   @import 'EXT:fahn_core/Configuration/TypoScript/constants.typoscript'
   ```

2. **Setup**: Fügen Sie hinzu:
   ```
   @import 'EXT:fahn_core/Configuration/TypoScript/setup.typoscript'
   ```

### Schritt 4: Cache leeren

1. Im TYPO3-Backend: **Admin Tools > Flush TYPO3 and PHP caches**
2. Oder per CLI:
   ```bash
   cd /home/miro/projects/FAHN-CORE/typo3-headless
   ddev exec vendor/bin/typo3 cache:flush
   ```

### Schritt 5: Testen

Testen Sie den Endpunkt direkt im Browser oder mit curl:

```bash
curl -X GET "https://fahn-core-typo3.ddev.site/?tx_fahncore_login[action]=session"
```

Erwartete Antwort:
```json
{"success":true,"data":{"authenticated":false,"user":null}}
```

## Alternative: Automatische Einbindung via Site-Konfiguration

Falls die TypoScript-Konfiguration automatisch geladen werden soll, können Sie die Site-Konfiguration anpassen:

**Datei:** `config/sites/main/config.yaml`

Fügen Sie hinzu:
```yaml
base: 'https://fahn-core-typo3.ddev.site/'
rootPageId: 1
websiteTitle: 'FAHN-CORE'
baseVariants: []
languages:
  - title: Deutsch
    enabled: true
    languageId: 0
    base: /
    typo3Language: de
    locale: de_DE.UTF-8
    iso-639-1: de
    navigationTitle: Deutsch
    hreflang: de-DE
    direction: ltr
    flag: de
    websiteTitle: ''
errorHandling: []
routes: []
```

**WICHTIG:** Die TypoScript-Konfiguration muss trotzdem im Root-Template eingebunden werden (siehe Schritt 3).

## Prüfung: Ist die Konfiguration geladen?

1. Im TYPO3-Backend: **Web > Template > Template Analyzer**
2. Prüfen Sie, ob `fahn_core` in der Liste der geladenen Extensions erscheint
3. Prüfen Sie, ob die PAGE-Konfiguration mit `typeNum = 0` vorhanden ist

## Häufige Fehler

### Fehler 1: Extension nicht aktiviert
- **Lösung:** Im Extension Manager prüfen, ob `fahn_core` und `fahn_core_fahndung` aktiviert sind

### Fehler 2: TypoScript-Syntax-Fehler
- **Lösung:** Im Template Analyzer prüfen, ob Fehler angezeigt werden

### Fehler 3: Cache nicht geleert
- **Lösung:** Cache komplett leeren (siehe Schritt 4)

## Demo-Daten vs. TYPO3-Daten

**Aktuell:** Das Frontend verwendet Demo-Daten für die Anzeige.

**Empfehlung:** 
- Für Entwicklung: Demo-Daten beibehalten (schnelleres Testen)
- Für Produktion: TYPO3-Daten einbinden (echte Fahndungen aus der Datenbank)

Die Demo-Daten können später entfernt werden, sobald echte TYPO3-Daten verfügbar sind.

