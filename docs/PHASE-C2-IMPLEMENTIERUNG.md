# Phase C2 – Backend API (Controller & TypoScript) - Implementierung

**Status**: ✅ **VOLLSTÄNDIG IMPLEMENTIERT**

## Übersicht

Alle Komponenten der Phase C2 wurden gemäß Spezifikation implementiert:

1. ✅ FahndungController mit allen CRUD-Actions
2. ✅ LoginController mit Session-Auth und Rate-Limiting
3. ✅ Minimalistische TypoScript-Konfiguration
4. ✅ Plugin-Registrierungen
5. ✅ Constants.typoscript für CORS-Origin

## Implementierte Komponenten

### C2.1 Controller-Implementierung

#### FahndungController
**Datei**: `packages/fahn_core_fahndung/Classes/Controller/FahndungController.php`

✅ **listAction()** – Paginierte Liste mit Filterung
- Liest `page` und `limit` aus Request-Parametern
- Ruft `findActive()`, `findBySearchTerm()` oder `findByCategory()` auf
- Serialisiert Ergebnis ohne tiefe Relationen
- Liefert JSON mit `success`, `data` und `pagination`

✅ **showAction()** – Einzelne Fahndung
- Lädt Fahndung anhand UID
- Prüft `isPublished = true`
- Liefert 404 bei nicht gefundenen/unveröffentlichten Datensätzen

✅ **createAction()** – Neue Fahndung erstellen
- Liest JSON-Body
- Validiert Pflichtfelder (`title`, `description`)
- Maskiert Strings mit `htmlspecialchars()` (Stored-XSS-Schutz)
- Nur für angemeldete Benutzer (401 bei fehlender Auth)
- Liefert HTTP 201 bei Erfolg

✅ **updateAction()** – Fahndung aktualisieren
- Selektives Update (nur vorhandene Felder werden gesetzt)
- Login-geschützt
- 404 bei nicht vorhandenen Datensätzen

✅ **deleteAction()** – Fahndung löschen
- Prüft Existenz
- Login-geschützt
- 401/404 bei Fehlern

✅ **Helper-Methoden**:
- `jsonResponse()` – Standardisierte JSON-Antwort mit CORS-Headern
- `serializeFahndung()` – Domain-Model zu Array
- `isUserLoggedIn()` – Session-Prüfung

✅ **PSR-3 Logging** – Fehler werden geloggt, keine internen Details nach außen

#### LoginController
**Datei**: `packages/fahn_core/Classes/Controller/LoginController.php`

✅ **loginAction()** – Authentifizierung
- Liest `username` und `password` aus POST-Body
- Rate-Limiter: Max. 5 falsche Logins in 10 Minuten pro IP
- Verifiziert Benutzer via `$GLOBALS['TSFE']->fe_user`
- Erzeugt Session (Cookie `fe_typo_user` wird gesetzt)
- Liefert JSON mit `success` und Benutzerdaten
- 401 bei falschen Zugangsdaten

✅ **sessionAction()** – Session-Status prüfen
- Prüft vorhandenes Session-Cookie
- Liefert `authenticated: true/false` und Benutzerdaten
- Ermöglicht Frontend-Initialisierung

✅ **logoutAction()** – Session invalidieren
- Ruft `$GLOBALS['TSFE']->fe_user->logoff()` auf
- Invalidiert Session in DB und löscht Cookie

✅ **Rate-Limiting**:
- Nutzt TYPO3 Cache Framework (`fahn_core_login`)
- IP-basiert (MD5-Hash für Cache-Key)
- 5 Versuche, 10 Minuten Sperre

### C2.2 TypoScript-Konfiguration

#### setup.typoscript
**Datei**: `packages/fahn_core/Configuration/TypoScript/setup.typoscript`

✅ **Minimalistische JSON-API-Konfiguration**:
- `page = PAGE` mit `typeNum = 0`
- `disableAllHeaderCode = 1`
- CORS-Header: `Access-Control-Allow-Origin`, `Access-Control-Allow-Credentials`, etc.
- Security Headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`

✅ **Router-Logik**:
- CASE-Object für Fahndung-API (list, show, create, update, delete)
- CASE-Object für Login-API (login, session, logout)
- Copy-Statements (`< list`, `< login`) für DRY-Prinzip
- Fallback bei unbekannter Action

#### constants.typoscript
**Datei**: `packages/fahn_core/Configuration/TypoScript/constants.typoscript`

✅ **CORS-Origin-Konstante**:
```typoscript
fahn.api.corsOrigin = http://localhost:3000
```

### C2.3 Plugin-Registrierung

#### fahn_core_fahndung/ext_localconf.php
✅ **Fahndung-API-Plugin**:
- Extension: `FahnCoreFahndung`
- Plugin: `Api`
- Controller: `FahndungController`
- Actions: `list, show, create, update, delete`
- Alle Actions als non-cacheable markiert

#### fahn_core/ext_localconf.php
✅ **Login-Plugin**:
- Extension: `FahnCore`
- Plugin: `Login`
- Controller: `LoginController`
- Actions: `login, session, logout`
- Alle Actions als non-cacheable markiert

✅ **Cache-Registrierung**:
- `fahn_core_login` Cache für Rate-Limiting

## API-Endpunkte

### Fahndungen-API

| Method | Endpoint | Auth | Beschreibung |
|--------|----------|------|--------------|
| GET | `/?tx_fahncorefahndung_api[action]=list&page=1&limit=10` | Nein | Paginierte Liste |
| GET | `/?tx_fahncorefahndung_api[action]=list&category=5` | Nein | Nach Kategorie filtern |
| GET | `/?tx_fahncorefahndung_api[action]=list&search=term` | Nein | Volltextsuche |
| GET | `/?tx_fahncorefahndung_api[action]=show&uid=123` | Nein | Einzelne Fahndung |
| POST | `/?tx_fahncorefahndung_api[action]=create` | ✅ Ja | Neue Fahndung |
| POST | `/?tx_fahncorefahndung_api[action]=update&uid=123` | ✅ Ja | Fahndung aktualisieren |
| POST | `/?tx_fahncorefahndung_api[action]=delete&uid=123` | ✅ Ja | Fahndung löschen |

### Login-API

| Method | Endpoint | Auth | Beschreibung |
|--------|----------|------|--------------|
| POST | `/?tx_fahncore_login[action]=login` | Nein | Login (Credentials im Body) |
| GET | `/?tx_fahncore_login[action]=session` | Nein | Session-Status prüfen |
| POST | `/?tx_fahncore_login[action]=logout` | Nein | Logout |

## Testing & Verification

### Backend-Test (cURL)

#### Liste der Fahndungen
```bash
curl "http://localhost:8080/?tx_fahncorefahndung_api[action]=list&page=1&limit=10"
```

**Erwartung**: JSON mit `success: true`, `data`-Array und `pagination`-Objekt

#### Fahndung anzeigen
```bash
curl "http://localhost:8080/?tx_fahncorefahndung_api[action]=show&uid=3"
```

**Erwartung**: Fahndung oder HTTP 404

#### Login mit Rate-Limiting
```bash
# Login (speichert Session-Cookie)
curl -X POST -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"pass"}' \
     -c cookies.txt \
     "http://localhost:8080/?tx_fahncore_login[action]=login"

# Session-Status prüfen
curl -b cookies.txt "http://localhost:8080/?tx_fahncore_login[action]=session"
```

**Erwartung**: `authenticated: true` nach erfolgreichem Login

#### Create/Update/Delete
```bash
# Neue Fahndung anlegen
curl -X POST -b cookies.txt -H "Content-Type: application/json" \
     -d '{"title":"Test","description":"Beschreibung","isPublished":true}' \
     "http://localhost:8080/?tx_fahncorefahndung_api[action]=create"

# Fahndung aktualisieren
curl -X POST -b cookies.txt -H "Content-Type: application/json" \
     -d '{"title":"Neu","isPublished":false}' \
     "http://localhost:8080/?tx_fahncorefahndung_api[action]=update&uid=1"

# Fahndung löschen
curl -X POST -b cookies.txt \
     "http://localhost:8080/?tx_fahncorefahndung_api[action]=delete&uid=1"
```

## Frontend-Integration

### Wichtige Hinweise

1. **Credentials mit senden**:
   ```typescript
   fetch(url, {
     credentials: 'include' // Wichtig für Session-Cookies!
   })
   ```

2. **CORS-Origin konfigurieren**:
   - In `constants.typoscript`: `fahn.api.corsOrigin` auf Frontend-Domain setzen
   - Ohne `Access-Control-Allow-Credentials: true` werden Session-Cookies nicht übermittelt

3. **Error-Handling**:
   - 401 = Unauthorized (nicht eingeloggt)
   - 404 = Not Found (Ressource existiert nicht)
   - 429 = Too Many Requests (Rate-Limit erreicht)
   - 500 = Internal Server Error (keine Details nach außen)

## Deliverables

✅ **FahndungController.php**
- Actions: `listAction()`, `showAction()`, `createAction()`, `updateAction()`, `deleteAction()`
- Helper: `jsonResponse()`, `serializeFahndung()`, `isUserLoggedIn()`
- PSR-3 Logging

✅ **LoginController.php**
- Actions: `loginAction()`, `sessionAction()`, `logoutAction()`
- Rate-Limiting via Cache Framework
- Session-Handling via TYPO3 Core

✅ **TypoScript-Konfiguration**
- `setup.typoscript` – Minimalistische JSON-API-Konfiguration
- `constants.typoscript` – CORS-Origin-Konstante
- CASE-basiertes Routing

✅ **Plugin-Registrierungen**
- `fahn_core_fahndung/ext_localconf.php` – Fahndung-API-Plugin
- `fahn_core/ext_localconf.php` – Login-Plugin + Cache-Registrierung

## Nächste Schritte

Nach Abschluss von Phase C2 folgt **Phase C3**: Next.js-Frontend-Integration

- TYPO3Client in TypeScript implementieren
- `credentials: 'include'` für alle Requests
- Auth-Hooks (`useAuth`)
- Middleware-Redirect im Frontend
- Konsistentes Error-Handling
- CORS-Header und Cookies prüfen

---

**Status**: ✅ **BEREIT FÜR PHASE C3**


