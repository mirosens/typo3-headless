# Backend-Verifikations-Checkliste: FAHN-CORE API-Konfiguration

**Datum**: 2025-01-27  
**Zweck**: Systematische Verifikation der TypoScript-API-Konfiguration und Troubleshooting

---

## 📋 Übersicht

Diese Checkliste führt Sie Schritt für Schritt durch die Verifikation der Backend-Konfiguration, um sicherzustellen, dass die API-Endpunkte korrekt funktionieren.

**Geschätzte Dauer**: 15-20 Minuten

---

## ✅ Phase 1: Extension-Status prüfen

### 1.1 Extension Manager öffnen

1. **TYPO3-Backend öffnen**: `https://fahn-core-typo3.ddev.site/typo3`
2. **Einloggen** mit Admin-Credentials
3. **Navigieren zu**: `Admin Tools > Extensions`

### 1.2 Extension-Status verifizieren

**Prüfen Sie folgende Extensions:**

- [ ] **fahn_core** 
  - Status: ✅ **Aktiviert** (grüner Haken)
  - Version: sollte sichtbar sein
  - Falls nicht aktiviert: Klicken Sie auf "Aktivieren"

- [ ] **fahn_core_fahndung**
  - Status: ✅ **Aktiviert** (grüner Haken)
  - Version: sollte sichtbar sein
  - Falls nicht aktiviert: Klicken Sie auf "Aktivieren"

**⚠️ WICHTIG**: Nach dem Aktivieren von Extensions:
```bash
# Cache leeren (im Terminal)
ddev exec vendor/bin/typo3 cache:flush
```

---

## ✅ Phase 2: TypoScript-Template-Verifikation

### 2.1 Root-Template finden

1. **Navigieren zu**: `Web > Template`
2. **Root-Seite auswählen** (meist "Home" oder die oberste Seite mit UID 1)
3. **Klicken Sie auf**: `Info/Modify` oder `Edit the whole template record`

### 2.2 TypoScript-Imports prüfen

**Im Tab "Includes" (oder "Setup"):**

- [ ] **Constants-Tab**:
  ```
  @import 'EXT:fahn_core/Configuration/TypoScript/constants.typoscript'
  ```
  - Sollte vorhanden sein
  - Falls nicht: Manuell hinzufügen

- [ ] **Setup-Tab**:
  ```
  @import 'EXT:fahn_core/Configuration/TypoScript/setup.typoscript'
  ```
  - Sollte vorhanden sein
  - Falls nicht: Manuell hinzufügen

**Alternative: Statische Datei einbinden**

Falls die @import-Statements nicht funktionieren:

1. **Tab "Includes"** öffnen
2. **"Include static (from extensions)"** auswählen
3. **"FAHN CORE API Configuration"** aktivieren
4. **Speichern**

---

## ✅ Phase 3: Template Analyzer - TypoScript-Verifikation

### 3.1 Template Analyzer öffnen

1. **Navigieren zu**: `Web > Template > Template Analyzer`
2. **Root-Seite auswählen** (UID 1)
3. **Klicken Sie auf**: "Active TypoScript"

### 3.2 Plugin-Konfigurationen suchen

**Suchen Sie nach folgenden Objekten:**

#### Plugin: Login API
- [ ] **`plugin.tx_fahncore_login`**
  - Sollte in der Liste erscheinen
  - Sollte folgende Eigenschaften haben:
    - `userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run`
    - `extensionName = FahnCore`
    - `pluginName = Login`
    - `features.skipDefaultArguments = 1` ⚠️ **KRITISCH für cHash-Lösung**

#### Plugin: Fahndung API
- [ ] **`plugin.tx_fahncorefahndung_api`**
  - Sollte in der Liste erscheinen
  - Sollte folgende Eigenschaften haben:
    - `userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run`
    - `extensionName = FahnCoreFahndung`
    - `pluginName = Api`
    - `features.skipDefaultArguments = 1` ⚠️ **KRITISCH für cHash-Lösung**

#### Router-Konfiguration
- [ ] **`lib.fahn_core_api_router`**
  - Sollte vorhanden sein
  - Sollte zwei CASE-Objekte enthalten:
    - `10` (für Fahndung API)
    - `20` (für Login API)

#### Page-Konfiguration
- [ ] **`page`** (mit Bedingung)
  - Sollte vorhanden sein
  - Sollte `typeNum = 0` haben
  - Sollte `config.disableAllHeaderCode = 1` haben
  - Sollte CORS-Header enthalten

### 3.3 Suche nach "tx_fahncore"

**Im Template Analyzer:**

1. **Suchfeld verwenden**: Geben Sie `tx_fahncore` ein
2. **Erwartete Ergebnisse**:
   - [ ] Mindestens 2 Treffer (Login + Fahndung Plugin)
   - [ ] Falls 0 Treffer: TypoScript wird nicht geladen ⚠️

---

## ✅ Phase 4: Cache-Verifikation

### 4.1 Alle Caches leeren

**Im TYPO3-Backend:**

1. **Navigieren zu**: `Admin Tools > Flush TYPO3 and PHP caches`
2. **Alle Optionen aktivieren**:
   - [ ] TYPO3 Cache
   - [ ] PHP Cache
   - [ ] TypoScript Cache
   - [ ] System Cache
3. **"Flush all caches"** klicken

**Oder per CLI:**
```bash
ddev exec vendor/bin/typo3 cache:flush
```

### 4.2 Cache-Status prüfen

- [ ] **Keine Fehlermeldungen** nach Cache-Leerung
- [ ] **Template Analyzer** zeigt aktuelle Konfiguration

---

## ✅ Phase 5: API-Endpunkt-Test

### 5.1 Session-Endpunkt testen

**Im Terminal (DDEV):**

```bash
# Test 1: Session-Endpunkt (sollte JSON zurückgeben)
curl -s "https://fahn-core-typo3.ddev.site/?tx_fahncore_login%5Baction%5D=session"

# Erwartete Antwort:
# {"success":true,"data":{"authenticated":false,"user":null}}
# ODER
# {"authenticated":false}
```

**Ergebnis:**
- [ ] ✅ **JSON-Response** erhalten (kein HTML)
- [ ] ❌ **404-Fehler** → Weiter zu Troubleshooting
- [ ] ❌ **HTML-Response** → TypoScript nicht aktiv

### 5.2 Fahndungen-Liste testen

```bash
# Test 2: Fahndungen-Liste
curl -s "https://fahn-core-typo3.ddev.site/?tx_fahncorefahndung_api%5Baction%5D=list&tx_fahncorefahndung_api%5Bpage%5D=1"

# Erwartete Antwort: JSON mit Fahndungen-Array
```

**Ergebnis:**
- [ ] ✅ **JSON-Response** mit Fahndungen
- [ ] ❌ **404-Fehler** → Weiter zu Troubleshooting

---

## 🔧 Phase 6: Troubleshooting

### Problem 1: "0 Treffer" bei Suche nach "tx_fahncore"

**Ursache**: TypoScript wird nicht geladen

**Lösungsschritte:**

1. **Template-Imports prüfen** (siehe Phase 2.2)
2. **Statische Datei manuell einbinden**:
   - Template bearbeiten
   - Tab "Includes"
   - "FAHN CORE API Configuration" aktivieren
3. **Cache leeren** (siehe Phase 4)
4. **Template Analyzer erneut prüfen**

### Problem 2: 404-Fehler mit "cHash empty"

**Ursache**: cHash-Validierung schlägt fehl

**Lösungsschritte:**

1. **Prüfen Sie `features.skipDefaultArguments`**:
   - Im Template Analyzer
   - `plugin.tx_fahncore_login` öffnen
   - Sollte `features.skipDefaultArguments = 1` haben
   
2. **Falls nicht vorhanden**:
   - TypoScript-Datei prüfen: `packages/fahn_core/Configuration/TypoScript/setup.typoscript`
   - Sicherstellen, dass Plugin-Definitionen korrekt sind

3. **Alternative: cHash global deaktivieren** (nur für Entwicklung):
   ```php
   // In config/system/settings.php oder LocalConfiguration.php
   'FE' => [
       'cacheHash' => [
           'enforceValidation' => false, // ⚠️ NUR FÜR ENTWICKLUNG!
       ],
   ],
   ```

### Problem 3: HTML-Response statt JSON

**Ursache**: Page-Konfiguration wird nicht aktiviert

**Lösungsschritte:**

1. **TypoScript-Bedingung prüfen**:
   - Im Template Analyzer nach `page` suchen
   - Sollte eine Bedingung haben: `[globalVar = GP:tx_fahncore_login|action != ""]`
   
2. **Manuell testen**:
   - URL direkt im Browser öffnen
   - Parameter müssen korrekt sein: `?tx_fahncore_login[action]=session`

3. **Falls Bedingung fehlt**:
   - TypoScript-Datei prüfen
   - Sicherstellen, dass die Bedingung korrekt ist

### Problem 4: Extension kann nicht aktiviert werden

**Ursache**: Abhängigkeiten fehlen oder Fehler in Extension

**Lösungsschritte:**

1. **Extension Manager > Log prüfen**:
   - Rote Fehlermeldungen beachten
   - Abhängigkeiten prüfen

2. **Composer-Abhängigkeiten prüfen**:
   ```bash
   cd /home/miro/projects/FAHN-CORE/typo3-headless
   composer install
   ```

3. **Autoloader neu generieren**:
   ```bash
   composer dump-autoload
   ```

---

## 📊 Phase 7: Erfolgs-Verifikation

### Checkliste für erfolgreiche Konfiguration:

- [ ] ✅ Beide Extensions aktiviert
- [ ] ✅ TypoScript-Imports im Template vorhanden
- [ ] ✅ Plugin-Konfigurationen im Template Analyzer sichtbar
- [ ] ✅ `features.skipDefaultArguments = 1` bei beiden Plugins
- [ ] ✅ Router-Konfiguration (`lib.fahn_core_api_router`) vorhanden
- [ ] ✅ Page-Konfiguration mit CORS-Headern vorhanden
- [ ] ✅ Cache geleert
- [ ] ✅ API-Endpunkte geben JSON zurück (kein 404)

---

## 🎯 Schnelltest-Kommandos

**Alle Tests in einem Durchgang:**

```bash
# 1. Cache leeren
ddev exec vendor/bin/typo3 cache:flush

# 2. Session-Endpunkt testen
echo "=== Session Test ==="
curl -s "https://fahn-core-typo3.ddev.site/?tx_fahncore_login%5Baction%5D=session" | jq .

# 3. Fahndungen-Liste testen
echo "=== Fahndungen List Test ==="
curl -s "https://fahn-core-typo3.ddev.site/?tx_fahncorefahndung_api%5Baction%5D=list&tx_fahncorefahndung_api%5Bpage%5D=1" | jq .

# 4. Health Check testen
echo "=== Health Check Test ==="
curl -s "https://fahn-core-typo3.ddev.site/?type=8999" | jq .
```

**Erwartete Ergebnisse:**
- Alle drei Tests sollten **JSON** zurückgeben
- Keine HTML-Responses
- Keine 404-Fehler

---

## 📝 Notizen und Beobachtungen

**Datum**: _______________

**Durchgeführt von**: _______________

**Ergebnisse**:
- Extension-Status: ☐ OK ☐ Probleme
- TypoScript-Laden: ☐ OK ☐ Probleme  
- API-Endpunkte: ☐ OK ☐ Probleme

**Gefundene Probleme**:
```
[Hier Probleme dokumentieren]
```

**Lösungen**:
```
[Hier Lösungen dokumentieren]
```

---

## 🔗 Weitere Ressourcen

- **Architektur-Bericht**: Siehe Projekt-Dokumentation
- **TypoScript-Dokumentation**: `packages/fahn_core/Configuration/TypoScript/setup.typoscript`
- **TYPO3-Dokumentation**: https://docs.typo3.org/

---

**Status**: ☐ Erfolgreich abgeschlossen ☐ Probleme gefunden  
**Nächste Schritte**: _______________


