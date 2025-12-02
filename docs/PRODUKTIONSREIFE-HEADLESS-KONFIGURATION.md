# Produktionsreife TYPO3 Headless Konfiguration

## Übersicht

Dieses Dokument beschreibt die produktionsreife Konfiguration zur Behebung von Routing-Anomalien und leeren Antworten in der TYPO3 Headless-Architektur.

## Implementierte Lösungen

### 1. PageTypeSuffix Enhancer (config.yaml)

**Problem**: Fehlende Zuordnung von `.json`-Suffix zu `type=834` führt zu HTML-Fallback und leeren Antworten.

**Lösung**: Implementierung des `PageTypeSuffix` Enhancers als erste Route in der `routeEnhancers`-Sektion.

```yaml
PageTypeSuffix:
  type: PageType
  default: ''  # Wichtig: Leer lassen für HTML-Standard (falls Mixed Mode)
  index: ''
  map:
    '.json': 834
    'menu.json': 835
```

**Vorteile**:
- Saubere URL-Struktur (`/seite.json` statt `/seite?type=834`)
- SEO-freundlich
- Verhindert HTML-Fallback bei fehlendem type-Parameter
- Caching-kompatibel

### 2. Extbase Plugin Routing Absicherung

**Problem**: Das `.json`-Suffix wird vom Slug-Parameter absorbiert, was zu fehlenden Argumenten im Controller führt.

**Lösung**: Implementierung von `requirements` und `defaultController` in der `FahndungenDetail` Route.

```yaml
FahndungenDetail:
  type: Extbase
  limitToPages: []
  extension: Fahndungen
  plugin: Pi1
  routes:
    - routePath: /api/fahndungen/{slug}
      _controller: 'Fahndung::show'
      _arguments:
        slug: fahndung
  defaultController: 'Fahndung::list'  # Fallback bei fehlendem Slug
  defaults:
    slug: ''
  requirements:
    # KRITISCH: Verhindert, dass ".json" Teil des Slugs wird
    slug: '^[a-zA-Z0-9\-_]+$'
  aspects:
    slug:
      type: PersistedAliasMapper
      tableName: tx_fahndungen_domain_model_fahndung
      routeFieldName: path_segment
```

**Vorteile**:
- Verhindert, dass `.json` vom Slug-Mapper verarbeitet wird
- Klarer Fallback-Mechanismus bei fehlenden Argumenten
- Semantisch korrekte 404-Fehler statt leerer Antworten

### 3. TypoScript-Optimierung

**Problem**: Fehlende `lib.page`-Definition kann zu leeren JSON-Strukturen führen.

**Lösung**: Explizite Definition von `lib.page` für typeNum 834.

```typoscript
lib.page = JSON
lib.page {
    fields {
        content = CONTENT
        content {
            table = tt_content
            select {
                orderBy = sorting
                where = {#colPos}=0
                languageField = sys_language_uid
            }
            renderObj = JSON
            renderObj {
                fields {
                    id = INT
                    id.field = uid
                    type = TEXT
                    type.field = CType
                }
            }
        }
    }
}
```

**Vorteile**:
- Explizite Kontrolle über die JSON-Struktur
- Verhindert leere `content`-Arrays
- Bessere Debugging-Möglichkeiten

### 4. Error Handling für 404

**Problem**: 404-Fehler werden als HTML zurückgegeben, was Frontend-Parsing-Fehler verursacht.

**Lösung**: In einem reinen Headless-Setup (`headless: 1`) wird die Standard-TYPO3-Fehlerbehandlung verwendet, die automatisch `typeNum 0` verwendet. Da `typeNum 0` für JSON konfiguriert ist, werden 404-Fehler automatisch als JSON zurückgegeben.

**Hinweis**: Die `errorHandling`-Konfiguration in `config.yaml` unterstützt keine `typeNum`-Parameter. Die Syntax `t3://page?type=838` ist ungültig. Stattdessen wird die Standard-Fehlerbehandlung verwendet, die `typeNum 0` nutzt.

**Alternative**: Falls eine spezielle 404-Seite benötigt wird, kann eine Page-ID konfiguriert werden:
```yaml
errorHandling:
  -
    errorCode: '404'
    errorHandler: Page
    errorContentSource: 't3://page?uid=1'  # Page-ID, nicht typeNum!
```

**Vorteile**:
- Konsistente JSON-Antworten auch bei Fehlern (durch typeNum 0)
- Frontend kann Fehler korrekt verarbeiten
- Keine zusätzliche Konfiguration erforderlich

### 5. Middleware-Konfiguration

**Status**: Bereits implementiert in `config/system/additional.php`

```php
'features' => [
    'headless.redirectMiddlewares' => true,
    'headless.elementBodyResponse' => true,
],
```

**Funktion**: 
- Redirects werden als JSON-Payload zurückgegeben statt HTTP-Header
- Verhindert HTML-Antworten bei Redirects

## Checkliste für die Produktionsfreigabe

- [x] Site Config: Flag `headless: 1` ist gesetzt
- [x] Routing: `PageTypeSuffix` mapped `.json` auf 834
- [x] Plugins: Alle `RouteEnhancers` haben Regex-Requirements, die `.json` ausschließen
- [x] TypoScript: `lib.page` ist für typeNum 834 definiert
- [x] Middleware: Redirect-Middleware für JSON ist aktiviert
- [x] Error Handling: 404-Fehler werden als JSON zurückgegeben
- [ ] Security: CORS-Header sind für die Frontend-Domain whitelisted (siehe `.ddev/config.yaml`)
- [ ] Cache: JSON-Antworten werden korrekt gecacht (Tags prüfen)
- [ ] Frontend: API-Calls nutzen `.json` Suffix statt Query-Params

## Debugging-Strategien

### 1. JsonView Backend Modul

Das JsonView Backend Modul sollte in jeder Entwicklungsumgebung aktiviert sein. Es erlaubt das Rendern der JSON-Ansicht direkt im TYPO3-Backend.

**Diagnose-Nutzen**:
- Wenn JsonView korrekte Daten anzeigt, aber Frontend leere Antwort erhält → Problem liegt in Netzwerkinfrastruktur (CORS, Firewall, Varnish Cache) oder Frontend-Code
- Wenn auch JsonView leere Daten zeigt → Problem liegt im Routing oder TypoScript

### 2. AdminPanel für JSON

Durch Anhängen von `?type=834&debug=1` (bei entsprechender IP-Freigabe) kann das TypoScript-Trace analysiert werden.

**Prüfungen**:
- Wird `lib.page` ausgeführt?
- Ist die SQL-Query korrekt?
- Werden Datensätze gefunden aber nicht gerendert?

### 3. Logging und Monitoring

In Mixed-Mode-Umgebungen ist es ratsam, spezifische Log-Einträge zu generieren, wenn der headless-Renderer aufgerufen wird.

**Strukturierter Debugging-Ansatz**:
1. **Route-Check**: Wird der korrekte Controller/Action aufgerufen? (Prüfung via Middleware-Debugging oder xDebug im Controller)
2. **Argument-Check**: Kommen die Argumente (UIDs) im Controller an?
3. **View-Check**: Werden Daten an den View übergeben?

## Häufige Fehlerquellen

### 1. Fehlende CType-Definitionen

**Symptom**: Das `content`-Array im JSON enthält Lücken. Elemente mit Standard-CTypes werden ausgegeben, benutzerdefinierte CTypes fehlen gänzlich.

**Lösung**: Jeder benutzerdefinierte CType muss explizit im TypoScript für den headless-Kontext registriert werden:

```typoscript
tt_content.my_custom_ctype = JSON
tt_content.my_custom_ctype {
    fields {
        header = TEXT
        header.field = header
        bodytext = TEXT
        bodytext.field = bodytext
        my_custom_field = TEXT
        my_custom_field.field = tx_myext_customfield
    }
}
```

### 2. FLUIDTEMPLATE im Headless-Kontext

**Warnung**: Die Verwendung von `FLUIDTEMPLATE` im Headless-Modus sollte nur sehr sparsam und kontrolliert eingesetzt werden. Wenn ein Fluid-Template fehlschlägt, fängt TYPO3 dies oft ab und gibt nichts zurück.

**Best Practice**: Nutzen Sie native TypoScript-Objekte (`TEXT`, `CONTENT`, `JSON`) zur Konstruktion der Antwort.

### 3. cHash-Dilemma

**Problem**: Headless-Anfragen tendieren dazu, URL-Parameter zu minimieren. TYPO3 nutzt jedoch den `cHash`, um die Integrität von Parametern zu gewährleisten.

**Lösung**: In einer Headless-Umgebung sollte für reine GET-APIs geprüft werden, ob der `cHash` für bestimmte Parameter deaktiviert werden kann (`excludeParametersFromcHash`), sofern die Sicherheit durch strikte Mappers gewährleistet ist.

## Frontend-Integration

### Nuxt.js / Next.js

**Wichtig**: Die `baseURL` in der Nuxt-Konfiguration muss korrekt gesetzt sein. Doppelte Slashes oder fehlendes Protokoll können zu Routing-Fehlern führen.

**Empfehlung**: Konfigurieren Sie den API-Client im Frontend so, dass er Suffixe (`.json`) verwendet, anstatt sich auf Query-Parameter zu verlassen. Dies harmonisiert mit der `PageTypeSuffix`-Strategie.

## Weitere Ressourcen

- [TYPO3 Headless Documentation](https://docs.typo3.org/p/friendsoftypo3/headless/main/en-us/)
- [TYPO3 Routing Documentation](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Routing/Index.html)
- [TYPO3 TypoScript Reference](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/)

## Changelog

### 2024-XX-XX
- Implementierung des `PageTypeSuffix` Enhancers
- Absicherung der `FahndungenDetail` Route mit `requirements` und `defaultController`
- Optimierung der TypoScript-Konfiguration mit expliziter `lib.page`-Definition
- Aktivierung des JSON-basierten Error Handlers für 404-Fehler

